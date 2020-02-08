<?php
/**
 * This file is part of the browscap-json-generator package.
 *
 * Copyright (c) 2012-2018, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
namespace Browscap\Generator;

use Browscap\Data\PropertyHolder;
use Browscap\Writer\JsonWriter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class BrowscapJsonGenerator
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * returns a logger instance
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        if (null === $this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * creates the testfiles for browscap.js
     *
     * @param string $buildFolder
     *
     * @return void
     */
    public function createTestfiles(string $buildFolder): void
    {
        $sourceDirectory = 'vendor/browscap/browscap/tests/issues/';
        $iterator        = new \RecursiveDirectoryIterator($sourceDirectory);

        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            try {
                $this->createTestFile($file, $buildFolder);
            } catch (\Exception $e) {
                $this->getLogger()->error($e);
            }
        }
    }

    /**
     * @param \SplFileInfo $file
     * @param string       $buildFolder
     *
     * @throws \Exception
     *
     * @return void
     */
    private function createTestFile(\SplFileInfo $file, string $buildFolder): void
    {
        $filenameV13 = str_replace('.php', '.js', $file->getFilename());
        $filenameV5  = str_replace('.php', '.ts', $file->getFilename());
        $testnumber  = str_replace('issue-', '', $file->getBasename($file->getExtension()));

        $tests   = require_once $file->getPathname();
        $testKey = 'full';

        $testCount = 0;

        foreach ($tests as $key => $test) {
            if (!array_key_exists($testKey, $test)) {
                continue;
            }

            if (!$test[$testKey]) {
                continue;
            }

            ++$testCount;
        }

        $filecontentV1 = '"use strict";

var assert = require(\'assert\'),
    Browscap = require(\'../browscap.js\'),
    browscap = new Browscap(),
    browser;

suite(\'checking for issue ' . $testnumber . ' (' . $testCount . ' test' . (1 !== $testCount ? 's' : '') . ')\', function () {
';

        $filecontentV3 = '\'use strict\';

const assert = require(\'assert\');
const Browscap = require(\'../src/index.js\');

suite(\'checking for issue ' . $testnumber . ' (' . $testCount . ' test' . (1 !== $testCount ? 's' : '') . ')\', function () {
';

        $filecontentV5 = '
import assert from \'assert\';
import Browscap from \'../src/browscap.ts\';

suite(\'checking for issue ' . $testnumber . ' (' . $testCount . ' test' . (1 !== $testCount ? 's' : '') . ')\', function () {
';

        $propertyHolder = new PropertyHolder();
        $writer         = new JsonWriter($buildFolder . 'dummy.json', $this->getLogger());

        foreach ($tests as $key => $test) {
            if (!array_key_exists($testKey, $test)) {
                continue;
            }

            if (!$test[$testKey]) {
                continue;
            }

            $rule = $test['ua'];
            $rule = str_replace(['\\', '"'], ['\\\\', '\"'], $rule);

            $filecontentV1 .= '  test(\'' . $key . ' ["' . addcslashes($rule, "'") . '"]\', function () {' . "\n";
            $filecontentV1 .= '    browser = browscap.getBrowser(\'' . addcslashes($rule, "'") . '\');' . "\n\n";

            $filecontentV3 .= '  test(\'' . $key . ' ["' . addcslashes($rule, "'") . '"]\', function () {' . "\n";
            $filecontentV3 .= '    const browscap = new Browscap();' . "\n";
            $filecontentV3 .= '    const browser = browscap.getBrowser(\'' . addcslashes($rule, "'") . '\');' . "\n\n";

            $filecontentV5 .= '  test(\'' . $key . ' ["' . addcslashes($rule, "'") . '"]\', function () {' . "\n";
            $filecontentV5 .= '    let browscap = new Browscap();' . "\n";
            $filecontentV5 .= '    let browser = browscap.getBrowser(\'' . addcslashes($rule, "'") . '\');' . "\n\n";

            foreach ($test['properties'] as $property => $value) {
                if (!$propertyHolder->isOutputProperty($property, $writer)) {
                    continue;
                }

                if ($propertyHolder->isDeprecatedProperty($property)) {
                    continue;
                }

                switch ($propertyHolder->getPropertyType($property)) {
                    case PropertyHolder::TYPE_BOOLEAN:
                        if (true === $value || 'true' === $value) {
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
                        $valueOutput = '\'' . addcslashes($value, "'") . '\'';

                        break;
                }

                $message = "'Expected actual \"{$property}\" to be " . addcslashes($valueOutput, "'\\") . " (was \\'' + browser['{$property}'] + '\\'; used pattern: ' + browser['browser_name_regex'] + ')'";
                $filecontentV1 .= '    assert.strictEqual(browser[\'' . $property . '\'], ' . $valueOutput . ', ' . $message . ');' . "\n";
                $filecontentV3 .= '    assert.strictEqual(browser[\'' . $property . '\'], ' . $valueOutput . ', ' . $message . ');' . "\n";
                $filecontentV5 .= '    assert.strictEqual(browser[\'' . $property . '\'], ' . $valueOutput . ', ' . $message . ');' . "\n";
            }

            $filecontentV1 .= '  });' . "\n";
            $filecontentV3 .= '  });' . "\n";
            $filecontentV5 .= '  });' . "\n";
        }

        $filecontentV1 .= '});' . "\n";
        $filecontentV3 .= '});' . "\n";
        $filecontentV5 .= '});' . "\n";

        file_put_contents($buildFolder . 'v1/' . $filenameV13, $filecontentV1);
        file_put_contents($buildFolder . 'v3/' . $filenameV13, $filecontentV3);
        file_put_contents($buildFolder . 'v5/' . $filenameV5, $filecontentV5);

        $writer->close();
        unlink($buildFolder . 'dummy.json');
    }
}
