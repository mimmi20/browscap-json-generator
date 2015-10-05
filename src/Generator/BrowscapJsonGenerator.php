<?php

namespace Browscap\Generator;

use Browscap\Data\PropertyHolder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class BrowscapJsonGenerator
 *
 * @package Browscap\Generator
 */
class BrowscapJsonGenerator
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger = null;

    /**
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \BrowscapPHP\Browscap
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * returns a logger instance
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
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
     */
    public function createTestfiles($buildFolder)
    {
        $sourceDirectory = 'vendor/browscap/browscap/tests/fixtures/issues/';
        $iterator        = new \RecursiveDirectoryIterator($sourceDirectory);

        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            /** @var $file \SplFileInfo */
            if (!$file->isFile() || $file->getExtension() != 'php') {
                continue;
            }

            try {
                $this->createTestFile($file, $buildFolder);
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
    Browscap = require(\'../browscap.js\'),
    browscap = new Browscap(),
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

        file_put_contents($buildFolder . $filename, $filecontent);
    }
}
