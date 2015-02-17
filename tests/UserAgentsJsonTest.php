<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Refer to the LICENSE file distributed with this package.
 *
 * @category   BrowscapTest
 * @package    Test
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    MIT
 */

namespace BrowscapTest;

use Browscap\Generator\BrowscapJsonGenerator;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Browscap\Data\PropertyHolder;

/**
 * Class UserAgentsTest
 *
 * @category   BrowscapTest
 * @package    Test
 * @author     James Titcumb <james@asgrim.com>
 */
class UserAgentsJsonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test to make sure the preprocessed json file is created
     */
    public function testCreatingJsonFile()
    {
        // First, generate the INI files
        $resourceFolder = 'vendor/browscap/browscap/resources/';

        $buildFolder = 'resources/';
        $jsonFile    = $buildFolder . '/browscap.preprocessed.json';

        if (!file_exists($buildFolder)) {
            mkdir($buildFolder, 0777, true);
        }

        $logger = new Logger('browscap');
        $logger->pushHandler(new NullHandler(Logger::DEBUG));

        $builder = new BrowscapJsonGenerator($resourceFolder, $buildFolder);
        $builder->setLogger($logger);
        $builder->run('test', $jsonFile);

        self::assertTrue(file_exists($jsonFile));
    }

    /**
     * @return array[]
     */
    public function userAgentDataProvider()
    {
        $data            = array();
        $sourceDirectory = 'vendor/browscap/browscap/tests/fixtures/issues/';

        $iterator = new \RecursiveDirectoryIterator($sourceDirectory);

        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            /** @var $file \SplFileInfo */
            if (!$file->isFile() || $file->getExtension() != 'php') {
                continue;
            }

            $data[] = array($file);
        }

        return $data;
    }

    /**
     * @dataProvider userAgentDataProvider
     * @coversNothing
     * @param \SplFileInfo $file
     */
    public function testCreateTestFiles(\SplFileInfo $file)
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

            $filecontent .= '  test(\'' . $key . '\', function () {' . "\n";

            $rule = $test[0];
            $rule = str_replace(array('\\', '"'), array('\\\\', '\"'), $rule);

            $filecontent .= '    browser = browscap.getBrowser("' . $rule . '");' . "\n\n";

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

                $filecontent .= '    assert.strictEqual(browser[\'' . $property . '\'], ' . $valueOutput . ');' . "\n";
            }

            $filecontent .= '  });' . "\n";
        }

        $filecontent .= '});' . "\n";

        file_put_contents('resources/test/' . $filename, $filecontent);

        self::assertTrue(file_exists('resources/test/' . $filename));
    }
}
