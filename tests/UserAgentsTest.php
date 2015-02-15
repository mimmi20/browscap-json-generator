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

/**
 * Class UserAgentsTest
 *
 * @category   BrowscapTest
 * @package    Test
 * @author     James Titcumb <james@asgrim.com>
 */
class UserAgentsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public function testCreatingJsonFile()
    {
        // First, generate the INI files
        $buildNumber = time();

        $resourceFolder = 'vendor/browscap/browscap/resources/';

        $buildFolder = 'vendor/browscap/browscap/build/browscap-ua-test-' . $buildNumber;
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

    public function testCreateTestFiles()
    {
        self::markTestSkipped('for test only');

        $sourceDirectory = 'vendor/browscap/browscap/tests/fixtures/issues/';

        $iterator = new \RecursiveDirectoryIterator($sourceDirectory);

        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            /** @var $file \SplFileInfo */
            if (!$file->isFile() || $file->getExtension() != 'php') {
                continue;
            }

            $filename    = str_replace('.php', '.js', $file->getFilename());
            $testnummer  = str_replace('issue-', '', $file->getBasename($file->getExtension()));
            $filecontent = 'var assert = require(\'assert\'),
  browscap = require(\'../browscap.js\'),
  browser;

suite(\'checking for issue ' . $testnummer . '\', function () {
';

            $tests = require_once $file->getPathname();

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
                $filecontent .= '    browser = browscap.getBrowser("' . $test[0] . '");' . "\n\n";

                foreach ($test[1] as $property => $value) {
                    $filecontent .= '    assert.strictEqual(browser[\'' . $property . '\'], \'' . $value . '\');' . "\n";
                }

                $filecontent .= '  });' . "\n";
            }

            $filecontent .= '});' . "\n\n";

            file_put_contents($filename, $filecontent);
        }
    }
}
