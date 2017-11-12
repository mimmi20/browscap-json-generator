<?php
declare(strict_types = 1);
namespace BrowscapJson\Command;

use Browscap\Cache\Adapter\JsonFile;
use Browscap\Cache\JsonCache;
use Browscap\Data\Factory\DataCollectionFactory;
use Browscap\Generator\BuildGenerator;
use Browscap\Helper\LoggerHelper;
use Browscap\Writer\Factory\FullPhpWriterFactory;
use BrowscapJson\Generator\BrowscapJsonGenerator;
use BrowscapPHP\Browscap;
use BrowscapPHP\BrowscapUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildJsonCommand extends Command
{
    /**
     * @var string
     */
    public const DEFAULT_BUILD_FOLDER = 'build';

    /**
     * @var string
     */
    public const DEFAULT_RESOURCES_FOLDER = 'vendor/browscap/browscap/resources';

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure() : void
    {
        $defaultBuildFolder    = self::DEFAULT_BUILD_FOLDER;
        $defaultResourceFolder = self::DEFAULT_RESOURCES_FOLDER;

        $defaultVersion = (string) trim(file_get_contents('vendor/browscap/browscap/BUILD_NUMBER'));

        $this
            ->setName('build')
            ->setDescription('The JSON source files and builds the INI files')
            ->addArgument('version', InputArgument::OPTIONAL, 'Version number to apply', $defaultVersion)
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
     * @return int|null null or 0 if everything went fine, or an error code
     *
     * @see    setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output) : ?int
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

        $writerCollectionFactory = new FullPhpWriterFactory();
        $writerCollection        = $writerCollectionFactory->createCollection($logger, $buildFolder);

        $buildGenerator = new BuildGenerator(
            $input->getOption('resources'),
            $buildFolder,
            $logger,
            $writerCollection,
            new DataCollectionFactory($logger)
        );

        $buildGenerator->run($input->getArgument('version'), false);

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

        return 0;
    }
}
