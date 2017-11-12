<?php
declare(strict_types = 1);
namespace BrowscapJson;

use Symfony\Component\Console\Application;

class BrowscapWithJson extends Application
{
    /**
     * Class constructor
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
