<?php

namespace Browscap\Generator;

use Browscap\Data\DataCollection;
use Browscap\Data\Expander;
use Browscap\Data\PropertyHolder;
use Browscap\Helper\CollectionCreator;

/**
 * Class BrowscapJsonGenerator
 *
 * @package Browscap\Generator
 */
class BrowscapJsonGenerator extends AbstractBuildGenerator
{
    /**
     * Options for regex patterns.
     *
     * REGEX_DELIMITER: Delimiter of all the regex patterns in the whole class.
     * REGEX_MODIFIERS: Regex modifiers.
     */
    const REGEX_DELIMITER = '@';
    const REGEX_MODIFIERS = 'i';
    const COMPRESSION_PATTERN_START = '@';
    const COMPRESSION_PATTERN_DELIMITER = '|';

    /**
     * @return \Browscap\Helper\CollectionCreator
     */
    public function getCollectionCreator()
    {
        if (null === $this->collectionCreator) {
            $this->collectionCreator = new CollectionCreator();
        }

        return $this->collectionCreator;
    }

    /**
     * Entry point for generating builds for a specified version
     *
     * @param string      $version
     * @param string|null $jsonFile
     *
     * @return string|void
     */
    public function run($version, $jsonFile = null)
    {
        $this->getLogger()->info('Resource folder: ' . $this->resourceFolder . '');
        $this->getLogger()->info('Build folder: ' . $this->buildFolder . '');

        $this->getLogger()->info('started creating a data collection');

        $dataCollection = new DataCollection($version);
        $dataCollection->setLogger($this->getLogger());

        $collectionCreator = $this->getCollectionCreator();

        $collectionCreator
            ->setLogger($this->getLogger())
            ->setDataCollection($dataCollection)
        ;

        $collection = $collectionCreator->createDataCollection($this->resourceFolder);

        $this->getLogger()->info('started initialisation of expander');

        $expander = new Expander();
        $expander
            ->setDataCollection($collection)
            ->setLogger($this->getLogger())
        ;

        $this->getLogger()->info('finished initialisation of expander');

        $comments = array(
            'Provided courtesy of http://browscap.org/',
            'Created on ' . $collection->getGenerationDate()->format('l, F j, Y \a\t h:i A T'),
            'Keep up with the latest goings-on with the project:',
            'Follow us on Twitter <https://twitter.com/browscap>, or...',
            'Like us on Facebook <https://facebook.com/browscap>, or...',
            'Collaborate on GitHub <https://github.com/browscap>, or...',
            'Discuss on Google Groups <https://groups.google.com/forum/#!forum/browscap>.'
        );

        $this->getLogger()->info('finished creating a data collection');

        $this->getLogger()->debug('build output for processed json file');

        $division      = $collection->getDefaultProperties();
        $ua            = $division->getUserAgents();
        $allProperties = array('Parent') + array_keys($ua[0]['properties']);

        $this->getLogger()->debug('rendering all divisions');

        $allInputDivisions = array('DefaultProperties' => $ua[0]['properties']);

        foreach ($collection->getDivisions() as $division) {
            /** @var \Browscap\Data\Division $division */

            // run checks on division before expanding versions because the checked properties do not change between
            // versions
            $sections = $expander->expand($division, $division->getName());

            $this->getLogger()->info('checking division ' . $division->getName());

            foreach (array_keys($sections) as $sectionName) {
                $section = $sections[$sectionName];

                $collection->checkProperty($sectionName, $section);
            }

            $versions = $division->getVersions();

            foreach ($versions as $version) {
                list($majorVer, $minorVer) = $expander->getVersionParts($version);

                $divisionName = $expander->parseProperty($division->getName(), $majorVer, $minorVer);

                $this->getLogger()->info('handle division ' . $divisionName);

                $encodedSections = json_encode($sections);
                $encodedSections = $expander->parseProperty($encodedSections, $majorVer, $minorVer);

                $sectionsWithVersion = json_decode($encodedSections, true);

                foreach (array_keys($sectionsWithVersion) as $sectionName) {
                    if (array_key_exists($sectionName, $allInputDivisions)) {
                        $this->getLogger()->debug(
                            'tried to add section "' . $sectionName . '" more than once -> skipped'
                        );
                        continue;
                    }

                    $section = $sectionsWithVersion[$sectionName];

                    $allInputDivisions[$sectionName] = $section;
                }

                unset($divisionName, $majorVer, $minorVer);
            }
        }

        $allDivisions   = array();
        $propertyHolder = new PropertyHolder();

        foreach ($allInputDivisions as $key => $properties) {
            $this->getLogger()->debug('checking division "' . $properties['Comment']);

            if (!in_array($key, array('DefaultProperties', '*'))) {
                $parent = $allInputDivisions[$properties['Parent']];
            } else {
                $parent = array();
            }

            $propertiesToOutput = $properties;

            foreach ($propertiesToOutput as $property => $value) {
                if (!isset($parent[$property])) {
                    continue;
                }

                $parentProperty = $parent[$property];

                switch ((string) $parentProperty) {
                    case 'true':
                        $parentProperty = true;
                        break;
                    case 'false':
                        $parentProperty = false;
                        break;
                    default:
                        $parentProperty = trim($parentProperty);
                        break;
                }

                if ($parentProperty !== $value) {
                    continue;
                }

                unset($propertiesToOutput[$property]);
            }

            $allDivisions[$key] = array();

            foreach ($allProperties as $property) {
                if (!isset($propertiesToOutput[$property])) {
                    continue;
                }

                if (!$propertyHolder->isOutputProperty($property)) {
                    continue;
                }

                $value       = $propertiesToOutput[$property];
                $valueOutput = $value;

                switch ($propertyHolder->getPropertyType($property)) {
                    case PropertyHolder::TYPE_BOOLEAN:
                        if (true === $value || $value === 'true') {
                            $valueOutput = true;
                        } else {
                            $valueOutput = false;
                        }
                        break;
                    case PropertyHolder::TYPE_IN_ARRAY:
                        try {
                            $valueOutput = $propertyHolder->checkValueInArray($property, $value);
                        } catch (\InvalidArgumentException $e) {
                            $valueOutput = '';
                        }
                        break;
                    default:
                        // nothing t do here
                        break;
                }

                $allDivisions[$key][$property] = $valueOutput;

                unset($value, $valueOutput);
            }
        }

        $output = array(
            'comments'             => $comments,
            'GJK_Browscap_Version' => array(
                'version'  => $version,
                'released' => $collection->getGenerationDate()->format('r'),
                'format'   => 'json',
                'type'     => 'FULL',
            ),
            'patterns'             => array(),
            'browsers'             => array(),
            'userAgents'           => array(),
        );

        array_unshift(
            $allProperties,
            'browser_name',
            'browser_name_regex',
            'browser_name_pattern'
        );
        ksort($allProperties);

        $tmp_user_agents = array_keys($allDivisions);

        $this->getLogger()->debug('sort useragent rules by length');

        $fullLength    = array();
        $reducedLength = array();

        foreach ($tmp_user_agents as $k => $a) {
            $fullLength[$k]    = strlen($a);
            $reducedLength[$k] = strlen(str_replace(array('*', '?'), '', $a));
        }

        array_multisort(
            $fullLength, SORT_DESC, SORT_NUMERIC,
            $reducedLength, SORT_DESC, SORT_NUMERIC,
            $tmp_user_agents
        );

        unset($fullLength, $reducedLength);

        $user_agents_keys = array_flip($tmp_user_agents);
        //$properties_keys  = array_flip($allProperties);

        $tmp_patterns = array();

        $this->getLogger()->debug('process all useragents');

        foreach ($tmp_user_agents as $i => $user_agent) {
            if (empty($allDivisions[$user_agent]['Comment'])
                || false !== strpos($user_agent, '*')
                || false !== strpos($user_agent, '?')
            ) {
                $pattern = $this->pregQuote($user_agent);

                $matches_count = preg_match_all(self::REGEX_DELIMITER . '\d' . self::REGEX_DELIMITER, $pattern, $matches);

                if (!$matches_count) {
                    $tmp_patterns[$pattern] = $i;
                } else {
                    $compressed_pattern = preg_replace(self::REGEX_DELIMITER . '\d' . self::REGEX_DELIMITER, '(\d)', $pattern);

                    if (!isset($tmp_patterns[$compressed_pattern])) {
                        $tmp_patterns[$compressed_pattern] = array('first' => $pattern);
                    }

                    $tmp_patterns[$compressed_pattern][$i] = $matches[0];
                }
            }

            if (!empty($allDivisions[$user_agent]['Parent'])) {
                $parent = $allDivisions[$user_agent]['Parent'];

                $parent_key = $user_agents_keys[$parent];

                $allDivisions[$user_agent]['Parent']       = $parent_key;
                $output['userAgents'][$parent_key] = $tmp_user_agents[$parent_key];
            };

            $browser = array();
            foreach ($allDivisions[$user_agent] as $property => $value) {
                /*
                if (!isset($properties_keys[$property]) || !CollectionParser::isOutputProperty($property)) {
                    continue;
                }
                /**/
                $browser[$property] = $value;
            }

            $output['browsers'][$i] = json_encode($browser, JSON_FORCE_OBJECT);
        }

        // reducing memory usage by unsetting $tmp_user_agents
        unset($tmp_user_agents);

        ksort($output['userAgents']);
        ksort($output['browsers']);

        $this->getLogger()->debug('process all patterns');

        foreach ($tmp_patterns as $pattern => $pattern_data) {
            if (is_int($pattern_data) || is_string($pattern_data)) {
                $output['patterns'][$pattern] = $pattern_data;
            } elseif (2 == count($pattern_data)) {
                end($pattern_data);
                $output['patterns'][$pattern_data['first']] = key($pattern_data);
            } else {
                unset($pattern_data['first']);

                $pattern_data = $this->deduplicateCompressionPattern($pattern_data, $pattern);

                $output['patterns'][$pattern] = $pattern_data;
            }
        }

        // reducing memory usage by unsetting $tmp_user_agents
        unset($tmp_patterns);

        file_put_contents($jsonFile, json_encode($output, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT));
    }

    /**
     * Converts browscap match patterns into preg match patterns.
     *
     * @param string $user_agent
     *
     * @return string
     */
    private function pregQuote($user_agent)
    {
        $pattern = preg_quote($user_agent, self::REGEX_DELIMITER);

        // the \\x replacement is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match
        return self::REGEX_DELIMITER
            . '^'
            . str_replace(array('\*', '\?', '\\x'), array('.*', '.', '\\\\x'), $pattern)
            . '$'
            . self::REGEX_DELIMITER;
    }

    /**
     * That looks complicated...
     *
     * All numbers are taken out into $matches, so we check if any of those numbers are identical
     * in all the $matches and if they are we restore them to the $pattern, removing from the $matches.
     * This gives us patterns with "(\d)" only in places that differ for some matches.
     *
     * @param array  $matches
     * @param string $pattern
     *
     * @return array of $matches
     */
    private function deduplicateCompressionPattern($matches, &$pattern)
    {
        $tmpMatches  = $matches;
        $first_match = array_shift($tmpMatches);
        $differences = array();

        foreach ($tmpMatches as $someMatch) {
            $differences += array_diff_assoc($first_match, $someMatch);
        }

        $identical = array_diff_key($first_match, $differences);

        $preparedMatches = array();

        foreach ($matches as $i => $someMatch) {
            $key = self::COMPRESSION_PATTERN_START
                . implode(self::COMPRESSION_PATTERN_DELIMITER, array_diff_assoc($someMatch, $identical));

            $preparedMatches[$key] = $i;
        }

        $patternParts = explode('(\d)', $pattern);

        foreach ($identical as $position => $value) {
            $patternParts[$position + 1] = $patternParts[$position] . $value . $patternParts[$position + 1];
            unset($patternParts[$position]);
        }

        $pattern = implode('(\d)', $patternParts);

        return $preparedMatches;
    }

    /**
     * @param string  $key
     * @param array   $properties
     * @param array[] $allDivisions
     *
     * @throws \UnexpectedValueException
     * @return bool
     */
    protected function firstCheckProperty($key, array $properties, array $allDivisions)
    {
        $this->getLogger()->debug('check if all required propeties are available');

        if (!isset($properties['Version'])) {
            throw new \UnexpectedValueException('Version property not found for key "' . $key . '"');
        }

        if (!isset($properties['Parent']) && !in_array($key, array('DefaultProperties', '*'))) {
            throw new \UnexpectedValueException('Parent property is missing for key "' . $key . '"');
        }

        if (!in_array($key, array('DefaultProperties', '*')) && !isset($allDivisions[$properties['Parent']])) {
            throw new \UnexpectedValueException(
                'Parent "' . $properties['Parent'] . '" not found for key "' . $key . '"'
            );
        }

        if (!isset($properties['Device_Type'])) {
            throw new \UnexpectedValueException('property "Device_Type" is missing for key "' . $key . '"');
        }

        if (!isset($properties['isTablet'])) {
            throw new \UnexpectedValueException('property "isTablet" is missing for key "' . $key . '"');
        }

        if (!isset($properties['isMobileDevice'])) {
            throw new \UnexpectedValueException('property "isMobileDevice" is missing for key "' . $key . '"');
        }

        switch ($properties['Device_Type']) {
            case 'Tablet':
            case 'FonePad':
                if (true !== $properties['isTablet']) {
                    throw new \UnexpectedValueException(
                        'the device of type "' . $properties['Device_Type'] . '" is NOT marked as Tablet for key "'
                        . $key . '"'
                    );
                }
                if (true !== $properties['isMobileDevice']) {
                    throw new \UnexpectedValueException(
                        'the device of type "' . $properties['Device_Type']
                        . '" is NOT marked as Mobile Device for key "' . $key . '"'
                    );
                }
                break;
            case 'Mobile Phone':
            case 'Mobile Device':
            case 'Ebook Reader':
            case 'Console':
                if (true === $properties['isTablet']) {
                    throw new \UnexpectedValueException(
                        'the device of type "' . $properties['Device_Type'] . '" is marked as Tablet for key "'
                        . $key . '"'
                    );
                }
                if (true !== $properties['isMobileDevice']) {
                    throw new \UnexpectedValueException(
                        'the device of type "' . $properties['Device_Type']
                        . '" is NOT marked as Mobile Device for key "' . $key . '"'
                    );
                }
                break;
            case 'TV Device':
            case 'Desktop':
            default:
                if (true === $properties['isTablet']) {
                    throw new \UnexpectedValueException(
                        'the device of type "' . $properties['Device_Type'] . '" is marked as Tablet for key "'
                        . $key . '"'
                    );
                }
                if (true === $properties['isMobileDevice']) {
                    throw new \UnexpectedValueException(
                        'the device of type "' . $properties['Device_Type'] . '" is marked as Mobile Device for key "'
                        . $key . '"'
                    );
                }
                break;
        }

        return true;
    }
}
