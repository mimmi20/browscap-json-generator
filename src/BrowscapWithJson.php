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
namespace Browscap;

use Symfony\Component\Console\Application;

/**
 * Class BrowscapWithJson
 *
 * @category   BrowscapWithJson
 */
class BrowscapWithJson extends Application
{
    /**
     * BrowscapWithJson constructor.
     */
    public function __construct()
    {
        parent::__construct('Browser Capabilities Project', 'dev-master');

        $commands = [
            new Command\BuildJsonCommand(),
        ];

        foreach ($commands as $command) {
            $this->add($command);
        }
    }
}
