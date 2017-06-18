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
namespace Browscap\Command;

use Browscap\Cache\Adapter\JsonFile;
use Browscap\Cache\JsonCache;
use Browscap\Generator\BrowscapJsonGenerator;
use Browscap\Generator\BuildGenerator;
use Browscap\Helper\CollectionCreator;
use Browscap\Helper\LoggerHelper;
use Browscap\Writer\Factory\FullPhpWriterFactory;
use BrowscapPHP\Browscap;
use BrowscapPHP\BrowscapUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BuildCommand
 *
 * @category   BrowscapWithJson
 *
 * @author     James Titcumb <james@asgrim.com>
 */
class BuildJsonCommand extends Command
{
    /**
     * @var string
     */
    const DEFAULT_BUILD_FOLDER = 'build';

    /**
     * @var string
     */
    const DEFAULT_RESOURCES_FOLDER = 'vendor/browscap/browscap/resources';

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $defaultBuildFolder    = self::DEFAULT_BUILD_FOLDER;
        $defaultResourceFolder = self::DEFAULT_RESOURCES_FOLDER;

        $this
            ->setName('build')
            ->setDescription('The JSON source files and builds the INI files')
            ->addArgument('version', InputArgument::REQUIRED, 'Version number to apply')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Where to output the build files to', $defaultBuildFolder)
            ->addOption('resources', null, InputOption::VALUE_REQUIRED, 'Where the resource files are located', $defaultResourceFolder);
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @throws \LogicException When this abstract method is not implemented
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @see    setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loggerHelper = new LoggerHelper();
        $logger       = $loggerHelper->create($output);

        $logger->info('Build started.');

        $version     = $input->getArgument('version');
        $buildFolder = $input->getOption('output') . '/build-' . $version . '/';

        if (!file_exists($buildFolder)) {
            mkdir($buildFolder, 0775, true);
        }

        if (!file_exists($buildFolder . 'cache/')) {
            mkdir($buildFolder . 'cache/', 0775, true);
        }

        if (!file_exists($buildFolder . 'sources/')) {
            mkdir($buildFolder . 'sources/', 0775, true);
        }

        if (!file_exists($buildFolder . 'test/')) {
            mkdir($buildFolder . 'test/', 0775, true);
        }

        $cacheAdapter = new JsonFile([JsonFile::DIR => $buildFolder . 'sources/']);
        $cache        = new JsonCache($cacheAdapter);

        $buildGenerator = new BuildGenerator(
            $input->getOption('resources'),
            $buildFolder
        );

        $writerCollectionFactory = new FullPhpWriterFactory();
        $writerCollection        = $writerCollectionFactory->createCollection($logger, $buildFolder);

        $buildGenerator
            ->setLogger($logger)
            ->setCollectionCreator(new CollectionCreator())
            ->setWriterCollection($writerCollection);

        $buildGenerator->run($input->getArgument('version'));

        $logger->info('Build done.');
        $logger->info('Converting started.');

        $browscapUpdater = new BrowscapUpdater();
        $browscapUpdater->setLogger($logger);
        $browscapUpdater->setCache($cache);

        $browscapUpdater->convertFile($buildFolder . 'full_php_browscap.ini');

        $logger->info('Converting done.');
        $logger->info('Creating Testfiles started.');

        $testGenerator = new BrowscapJsonGenerator();
        $testGenerator->setLogger($logger);
        $testGenerator->createTestfiles($buildFolder . 'test/');

        $logger->info('Creating Testfiles done.');
    }
}
