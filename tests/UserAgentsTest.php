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
}
