<?php
/**
 * This file is part of the browscap-json-generator package.
 *
 * Copyright (c) 2012-2017, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
namespace BrowscapTest;

use Browscap\Data\PropertyHolder;
use Browscap\Generator\BrowscapJsonGenerator;
use Monolog\Handler\NullHandler;
use Monolog\Logger;

/**
 * Class UserAgentsTest
 *
 * @category   BrowscapTest
 *
 * @author     James Titcumb <james@asgrim.com>
 */
class UserAgentsJsonTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test to make sure the preprocessed json file is created
     */
    public function testCreatingJsonFile()
    {
        self::markTestSkipped('not read yet');

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
        $data            = [];
        $sourceDirectory = 'vendor/browscap/browscap/tests/fixtures/issues/';

        $iterator = new \RecursiveDirectoryIterator($sourceDirectory);

        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            /** @var $file \SplFileInfo */
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $data[] = [$file];
        }

        return $data;
    }

    /**
     * @dataProvider userAgentDataProvider
     * @coversNothing
     *
     * @param \SplFileInfo $file
     */
    public function testCreateTestFiles(\SplFileInfo $file)
    {
        self::markTestSkipped('not read yet');

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
            $rule = str_replace(['\\', '"'], ['\\\\', '\"'], $rule);

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
