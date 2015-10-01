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
     * @var \Browscap\Data\DataCollection
     */
    private $collection = null;

    /**
     * @var array
     */
    private $comments = array();

    /**
     * @var \Browscap\Data\Expander
     */
    private $expander = null;

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
     * runs before the build
     *
     * @return \Browscap\Generator\AbstractBuildGenerator
     */
    protected function preBuild()
    {
        $this->getLogger()->info('Resource folder: ' . $this->resourceFolder . '');
        $this->getLogger()->info('Build folder: ' . $this->buildFolder . '');

        return $this;
    }

    /**
     * Entry point for generating builds for a specified version
     *
     * @param string      $version
     * @param string|null $jsonFilePatterns
     * @param string|null $jsonFileBrowsers
     * @param string|null $jsonFileUas
     * @param string|null $jsonFileVersion
     *
     * @return string|void
     */

    public function run(
        $version,
        $jsonFilePatterns = null,
        $jsonFileBrowsers = null,
        $jsonFileUas = null,
        $jsonFileVersion = null
    ) {
        return $this
            ->preBuild()
            ->build($version)
            ->postBuild(
                $version,
                $jsonFilePatterns,
                $jsonFileBrowsers,
                $jsonFileUas,
                $jsonFileVersion
            );
    }

    /**
     * runs the build
     *
     * @param string $version
     *
     * @return \Browscap\Generator\AbstractBuildGenerator
     */
    protected function build($version)
    {
        $this->getLogger()->info('started creating a data collection');

        $dataCollection = new DataCollection($version);
        $dataCollection->setLogger($this->getLogger());

        $collectionCreator = $this->getCollectionCreator();

        $collectionCreator
            ->setLogger($this->getLogger())
            ->setDataCollection($dataCollection)
        ;

        $this->collection = $collectionCreator->createDataCollection($this->resourceFolder);

        $this->setCollectionCreator($collectionCreator);

        $this->getLogger()->info('started initialisation of expander');

        $this->expander = new Expander();
        $this->expander
            ->setDataCollection($this->collection)
            ->setLogger($this->getLogger())
        ;

        $this->getLogger()->info('finished initialisation of expander');

        $this->comments = array(
            'Provided courtesy of http://browscap.org/',
            'Created on ' . $this->collection->getGenerationDate()->format('l, F j, Y \a\t h:i A T'),
            'Keep up with the latest goings-on with the project:',
            'Follow us on Twitter <https://twitter.com/browscap>, or...',
            'Like us on Facebook <https://facebook.com/browscap>, or...',
            'Collaborate on GitHub <https://github.com/browscap>, or...',
            'Discuss on Google Groups <https://groups.google.com/forum/#!forum/browscap>.'
        );

        $this->getLogger()->info('finished creating a data collection');

        return $this;
    }

    /**
     * runs after the build
     *
     * @param string      $inputVersion
     * @param string|null $jsonFilePatterns
     * @param string|null $jsonFileBrowsers
     * @param string|null $jsonFileUas
     * @param string|null $jsonFileVersion
     *
     * @return \Browscap\Generator\BuildGenerator
     */
    protected function postBuild(
        $inputVersion = null,
        $jsonFilePatterns = null,
        $jsonFileBrowsers = null,
        $jsonFileUas = null,
        $jsonFileVersion = null
    ) {
        $this->getLogger()->info('create preprocessed json files (version)');

        $versionOutput = array(
            'comments'             => $this->comments,
            'GJK_Browscap_Version' => array(
                'version'  => $inputVersion,
                'released' => $this->collection->getGenerationDate()->format('r'),
                'format'   => 'json',
                'type'     => 'FULL',
            ),
        );

        file_put_contents(
            $jsonFileVersion,
            json_encode($versionOutput, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT)
        );

        $this->getLogger()->debug('build output for processed json file');

        $division      = $this->collection->getDefaultProperties();
        $ua            = $division->getUserAgents();
        $allProperties = array_keys($ua[0]['properties']);
        array_unshift($allProperties, 'Parent');

        $this->getLogger()->info('checking and expanding all divisions');

        $allInputDivisions = array('DefaultProperties' => $ua[0]['properties']);

        foreach ($this->collection->getDivisions() as $division) {
            /** @var \Browscap\Data\Division $division */

            // run checks on division before expanding versions because the checked properties do not change between
            // versions
            $sections = $this->expander->expand($division, $division->getName());

            $this->getLogger()->debug('checking and expanding division ' . $division->getName());

            foreach (array_keys($sections) as $sectionName) {
                $section = $sections[$sectionName];

                $this->collection->checkProperty($sectionName, $section);
            }

            $versions = $division->getVersions();

            foreach ($versions as $version) {
                list($majorVer, $minorVer) = $this->expander->getVersionParts($version);

                $divisionName = $this->expander->parseProperty($division->getName(), $majorVer, $minorVer);

                $this->getLogger()->info('handle division ' . $divisionName);

                $encodedSections = json_encode($sections);
                $encodedSections = $this->expander->parseProperty($encodedSections, $majorVer, $minorVer);

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

        $division = $this->collection->getDefaultBrowser();
        $ua       = $division->getUserAgents();

        $allInputDivisions += array($ua[0]['userAgent'] => $ua[0]['properties']);

        $allDivisions   = array();
        $propertyHolder = new PropertyHolder();

        $this->getLogger()->info('removing unchanged properties');

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

        array_unshift(
            $allProperties,
            'browser_name',
            'browser_name_regex'
        );

        $tmpUserAgents  = array_keys($allDivisions);
        $userAgentsKeys = array_flip($tmpUserAgents);

        $this->getLogger()->info('process all useragents');

        $propertyHolder = new PropertyHolder();

        $outputUseragents = array();
        $outputBrowsers   = array();
        $tmpPatterns      = array();

        foreach ($tmpUserAgents as $i => $userAgent) {
            if (empty($allDivisions[$userAgent]['Comment'])
                || false !== strpos($userAgent, '*')
                || false !== strpos($userAgent, '?')
            ) {
                $pattern = $this->pregQuote($userAgent);

                $countMatches = preg_match_all(
                    self::REGEX_DELIMITER . '\d' . self::REGEX_DELIMITER,
                    $pattern,
                    $matches
                );

                if (!$countMatches) {
                    $tmpPatterns[$pattern] = $i;
                } else {
                    $compressedPattern = preg_replace(
                        self::REGEX_DELIMITER . '\d' . self::REGEX_DELIMITER,
                        '(\d)',
                        $pattern
                    );

                    if (!isset($tmpPatterns[$compressedPattern])) {
                        $tmpPatterns[$compressedPattern] = array('first' => $pattern);
                    }

                    $tmpPatterns[$compressedPattern][$i] = $matches[0];
                }
            }

            if (!empty($allDivisions[$userAgent]['Parent'])) {
                $parent = $allDivisions[$userAgent]['Parent'];

                $parentKey = $userAgentsKeys[$parent];

                $allDivisions[$userAgent]['Parent'] = $parentKey;
                $outputUseragents[$parentKey]       = $tmpUserAgents[$parentKey];
            };

            $properties = array();
            foreach ($allDivisions[$userAgent] as $property => $value) {
                if (!$propertyHolder->isOutputProperty($property)) {
                    continue;
                }

                if (isset($allDivisions[$userAgent]['Parent']) && 'Parent' !== $property) {
                    if ('DefaultProperties' === $allDivisions[$userAgent]['Parent']
                        || !isset($allDivisions[$allDivisions[$userAgent]['Parent']])
                    ) {
                        if (isset($defaultproperties[$property])
                            && $defaultproperties[$property] === $allDivisions[$userAgent][$property]
                        ) {
                            continue;
                        }
                    } else {
                        $parentProperties = $allDivisions[$allDivisions[$userAgent]['Parent']];

                        if (isset($parentProperties[$property])
                            && $parentProperties[$property] === $allDivisions[$userAgent][$property]
                        ) {
                            continue;
                        }
                    }
                }

                $properties[$property] = $value;
            }

            $outputBrowsers[$i] = json_encode($properties, JSON_FORCE_OBJECT);
        }

        $this->getLogger()->info('create preprocessed json files (browser data)');

        file_put_contents(
            $jsonFileBrowsers,
            json_encode(array('browsers' => $outputBrowsers), JSON_PRETTY_PRINT | JSON_FORCE_OBJECT)
        );

        $this->getLogger()->info('create preprocessed json files (useragent names)');

        file_put_contents(
            $jsonFileUas,
            json_encode(array('userAgents' => $outputUseragents), JSON_PRETTY_PRINT | JSON_FORCE_OBJECT)
        );

        // reducing memory usage by unsetting variables
        unset($tmpUserAgents);
        unset($outputBrowsers);
        unset($outputUseragents);

        $this->getLogger()->info('process all patterns');

        $outputPatterns = array();

        foreach ($tmpPatterns as $pattern => $patternData) {
            if (is_int($patternData) || is_string($patternData)) {
                $outputPatterns[$pattern] = $patternData;
            } elseif (2 == count($patternData)) {
                end($patternData);
                $outputPatterns[$patternData['first']] = key($patternData);
            } else {
                unset($patternData['first']);

                $patternData = $this->deduplicateCompressionPattern($patternData, $pattern);

                $outputPatterns[$pattern] = $patternData;
            }
        }

        // reducing memory usage by unsetting $tmp_user_agents
        unset($tmpPatterns);

        $this->getLogger()->info('sort all patterns');

        $positionIndex = array();
        $lengthIndex   = array();
        $shortLength   = array();
        $patternArray  = array();
        $counter       = 0;

        foreach (array_keys($outputPatterns) as $pattern) {
            $decodedPattern = str_replace('(\d)', 0, $this->pregUnQuote($pattern, false));

            // force "defaultproperties" (if available) to first position, and "*" to last position
            if ($decodedPattern === 'defaultproperties') {
                $positionIndex[$pattern] = 0;
            } elseif ($decodedPattern === '*') {
                $positionIndex[$pattern] = 2;
            } else {
                $positionIndex[$pattern] = 1;
            }

            // sort by length
            $lengthIndex[$pattern] = strlen($decodedPattern);
            $shortLength[$pattern] = strlen(str_replace(array('*', '?'), '', $decodedPattern));

            // sort by original order
            $patternArray[$pattern] = $counter;

            $counter++;
        }

        array_multisort(
            $positionIndex,
            SORT_ASC,
            SORT_NUMERIC,
            $lengthIndex,
            SORT_DESC,
            SORT_NUMERIC,
            $shortLength,
            SORT_DESC,
            SORT_NUMERIC,
            $patternArray,
            SORT_ASC,
            SORT_NUMERIC,
            $outputPatterns
        );

        $this->getLogger()->info('create preprocessed json files (patterns)');

        file_put_contents(
            $jsonFilePatterns,
            json_encode(array('patterns' => $outputPatterns), JSON_PRETTY_PRINT | JSON_FORCE_OBJECT)
        );

        $this->getLogger()->info('create testfiles for browscap.js');

        $this->createTestfiles();
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
        return str_replace(array('\*', '\?', '\\x'), array('.*', '.', '\\\\x'), $pattern);
    }

    /**
     * Converts preg match patterns back to browscap match patterns.
     *
     * @param string        $pattern
     * @param array|boolean $matches
     *
     * @return string
     */
    private function pregUnQuote($pattern, $matches)
    {
        // list of escaped characters: http://www.php.net/manual/en/function.preg-quote.php
        // to properly unescape '?' which was changed to '.', I replace '\.' (real dot) with '\?',
        // then change '.' to '?' and then '\?' to '.'.
        $search  = array(
            '\\' . self::REGEX_DELIMITER, '\\.', '\\\\', '\\+', '\\[', '\\^', '\\]', '\\$', '\\(', '\\)', '\\{', '\\}',
            '\\=', '\\!', '\\<', '\\>', '\\|', '\\:', '\\-', '.*', '.', '\\?'
        );
        $replace = array(
            self::REGEX_DELIMITER, '\\?', '\\', '+', '[', '^', ']', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|',
            ':', '-', '*', '?', '.'
        );

        $result = substr(str_replace($search, $replace, $pattern), 2, -2);

        if ($matches) {
            foreach ($matches as $oneMatch) {
                $position = strpos($result, '(\d)');
                $result   = substr_replace($result, $oneMatch, $position, 4);
            }
        }

        return $result;
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

    /**
     * creates the testfiles for browscap.js
     */
    private function createTestfiles()
    {
        if (!file_exists($this->buildFolder . '/test/')) {
            mkdir($this->buildFolder . '/test/', 0775, true);
        }

        $sourceDirectory = 'vendor/browscap/browscap/tests/fixtures/issues/';
        $iterator        = new \RecursiveDirectoryIterator($sourceDirectory);

        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            /** @var $file \SplFileInfo */
            if (!$file->isFile() || $file->getExtension() != 'php') {
                continue;
            }

            try {
                $this->createTestFile($file, $this->buildFolder);
            } catch (\RuntimeException $e) {
                $this->getLogger()->error($e);
            }
        }
    }

    /**
     * @param \SplFileInfo $file
     * @param string       $buildFolder
     *
     * @throws \RuntimeException
     */
    private function createTestFile(\SplFileInfo $file, $buildFolder)
    {
        $filename    = str_replace('.php', '.js', $file->getFilename());
        $testnumber  = str_replace('issue-', '', $file->getBasename($file->getExtension()));
        $filecontent = 'var assert = require(\'assert\'),
    browscap = require(\'../browscap.js\'),
    browser;

suite(\'checking for issue ' . $testnumber . '\', function () {
';

        $tests = require_once $file->getPathname();

        $propertyHolder = new PropertyHolder();

        foreach ($tests as $key => $test) {
            if (isset($data[$key])) {
                throw new \RuntimeException('Test data is duplicated for key "' . $key . '"');
            }

            if (isset($checks[$test[0]])) {
                throw new \RuntimeException(
                    'UA "' . $test[0] . '" added more than once, now for key "' . $key . '", before for key "'
                    . $checks[$test[0]] . '"'
                );
            }

            $rule = $test[0];
            $rule = str_replace(array('\\', '"'), array('\\\\', '\"'), $rule);

            $filecontent .= '  test(\'' . $key . ' ["' . addcslashes($rule, "'") . '"]\', function () {' . "\n";
            $filecontent .= '    browser = browscap.getBrowser(\'' . addcslashes($rule, "'") . '\');' . "\n\n";

            foreach ($test[1] as $property => $value) {
                if (!$propertyHolder->isOutputProperty($property)) {
                    continue;
                }

                $valueOutput = '\'' . $value . '\'';

                switch ($propertyHolder->getPropertyType($property)) {
                    case PropertyHolder::TYPE_BOOLEAN:
                        if (true === $value || $value === 'true') {
                            $valueOutput = 'true';
                        } else {
                            $valueOutput = 'false';
                        }
                        break;
                    case PropertyHolder::TYPE_IN_ARRAY:
                        try {
                            $valueOutput = '\'' . $propertyHolder->checkValueInArray($property, $value) . '\'';
                        } catch (\InvalidArgumentException $e) {
                            $valueOutput = '""';
                        }
                        break;
                    default:
                        // nothing t do here
                        break;
                }

                $message      = "'Expected actual \"$property\" to be " . addcslashes($valueOutput, "'") . " (was \\'' + browser['$property'] + '\\'; used pattern: ' + browser['browser_name_regex'] + ')'";
                $filecontent .= '    assert.strictEqual(browser[\'' . $property . '\'], ' . $valueOutput . ', ' . $message . ');' . "\n";
            }

            $filecontent .= '  });' . "\n";
        }

        $filecontent .= '});' . "\n";

        file_put_contents($buildFolder . '/test/' . $filename, $filecontent);
    }
}
