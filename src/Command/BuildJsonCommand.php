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
namespace Browscap\Command;

use Assert\Assert;
use Browscap\Cache\Adapter\JsonFile;
use Browscap\Cache\JsonCache;
use Browscap\Data\Factory\DataCollectionFactory;
use Browscap\Generator\BrowscapJsonGenerator;
use Browscap\Generator\BuildGenerator;
use Browscap\Helper\LoggerHelper;
use Browscap\Writer\Factory\FullPhpWriterFactory;
use BrowscapPHP\BrowscapUpdater;
use PackageInfo\Package;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildJsonCommand extends Command
{
    /**
     * @var string
     */
    private const DEFAULT_BUILD_FOLDER = 'build';

    /**
     * @var string
     */
    private const DEFAULT_RESOURCES_FOLDER = 'vendor/browscap/browscap/resources';

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $defaultBuildFolder    = self::DEFAULT_BUILD_FOLDER;
        $defaultResourceFolder = self::DEFAULT_RESOURCES_FOLDER;

        $this
            ->setName('build')
            ->setDescription('The JSON source files and builds the INI files')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Where to output the build files to', $defaultBuildFolder)
            ->addOption('resources', null, InputOption::VALUE_REQUIRED, 'Where the resource files are located', $defaultResourceFolder);
    }

    /**
     * Executes the current command.
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @throws \Assert\AssertionFailedException
     * @throws \BrowscapPHP\Exception
     * @throws \Exception
     *
     * @return int|null null or 0 if everything went fine, or an error code
     *
     * @see    setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loggerHelper = new LoggerHelper();
        $logger       = $loggerHelper->create($output);

        $output->writeln('Build started.');

        $package        = new Package('browscap/browscap');
        $packageVersion = $package->getVersion();
        $version        = $this->convertPackageVersionToBuildNumber($packageVersion);
        $buildFolder    = $input->getOption('output') . '/build-' . $version . '/';

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

        if (!file_exists($buildFolder . 'test/v1/')) {
            mkdir($buildFolder . 'test/v1/', 0775, true);
        }

        if (!file_exists($buildFolder . 'test/v3/')) {
            mkdir($buildFolder . 'test/v3/', 0775, true);
        }

        $cacheAdapter = new JsonFile([JsonFile::DIR => $buildFolder . 'sources/']);
        $cache        = new JsonCache($cacheAdapter);

        $writerCollectionFactory = new FullPhpWriterFactory();
        $writerCollection        = $writerCollectionFactory->createCollection($logger, $buildFolder);
        $dataCollectionFactory   = new DataCollectionFactory($logger);

        $buildGenerator = new BuildGenerator(
            $input->getOption('resources'),
            $buildFolder,
            $logger,
            $writerCollection,
            $dataCollectionFactory
        );

        $buildGenerator->run((string) $version, false);

        $output->writeln('Build done.');
        $output->writeln('Converting started.');

        $browscapUpdater = new BrowscapUpdater();
        $browscapUpdater->setLogger($logger);
        $browscapUpdater->setCache($cache);

        $browscapUpdater->convertFile($buildFolder . 'full_php_browscap.ini');

        $output->writeln('Converting done.');
        $output->writeln('Creating Testfiles started.');

        $testGenerator = new BrowscapJsonGenerator();
        $testGenerator->setLogger($logger);
        $testGenerator->createTestfiles($buildFolder . 'test/');

        $output->writeln('Creating Testfiles done.');

        return 0;
    }

    /**
     * Converts a package number e.g. 1.2.3 into a "build number" e.g. 1002003
     *
     * There are three digits for each version, so 001002003 becomes 1002003 when cast to int to drop the leading zeros
     *
     * @param string $version
     *
     * @return int
     */
    private function convertPackageVersionToBuildNumber(string $version): int
    {
        Assert::that($version)->regex('#^(\d+\.)(\d+\.)(\d+)$#');

        return (int) sprintf('%03d%03d%03d', ...explode('.', $version));
    }
}
