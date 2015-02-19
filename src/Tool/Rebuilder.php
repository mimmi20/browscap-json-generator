<?php

namespace Browscap\Tool;

use Browscap\Parser\ParserInterface;
use Browscap\Parser\IniParser;

class Rebuilder
{
    /**
     * @var \Browscap\Parser\ParserInterface
     */
    protected $parser;

    protected $buildDir;

    public function __construct($buildDir)
    {
        $this->buildDir = $buildDir;
    }

    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @return \Browscap\Parser\ParserInterface
     */
    public function getParser()
    {
        if (!$this->parser) {
            $this->parser = new IniParser($this->buildDir . '/browscap.ini');
        }

        return $this->parser;
    }

    public function rebuild()
    {
        $parser   = $this->getParser();
        $fileData = $parser->parse();

        $versionData = $fileData['GJK_Browscap_Version'];

        $metadata = array(
            'version'   => $versionData['Version'],
            'released'  => $versionData['Released'],
            'filesizes' => array(
                'BrowsCapINI'          => $this->getKbSize($this->buildDir . '/browscap.ini'),
                'Full_BrowsCapINI'     => $this->getKbSize($this->buildDir . '/full_asp_browscap.ini'),
                'Lite_BrowsCapINI'     => $this->getKbSize($this->buildDir . '/lite_asp_browscap.ini'),
                'PHP_BrowsCapINI'      => $this->getKbSize($this->buildDir . '/php_browscap.ini'),
                'Full_PHP_BrowsCapINI' => $this->getKbSize($this->buildDir . '/full_php_browscap.ini'),
                'Lite_PHP_BrowsCapINI' => $this->getKbSize($this->buildDir . '/lite_php_browscap.ini'),
                'BrowsCapXML'          => $this->getKbSize($this->buildDir . '/browscap.xml'),
                'BrowsCapCSV'          => $this->getKbSize($this->buildDir . '/browscap.csv'),
                'BrowsCapJSON'         => $this->getKbSize($this->buildDir . '/browscap.json'),
                'BrowsCapZIP'          => $this->getKbSize($this->buildDir . '/browscap.zip'),
            ),
        );

        $this->writeArray($this->buildDir . '/metadata.php', $metadata);

        $this->niceDelete($this->buildDir . '/../cache/browscap.ini');
        $this->niceDelete($this->buildDir . '/../cache/cache.php');
    }

    public function niceDelete($filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    public function writeArray($filename, $array)
    {
        $phpArray = var_export($array, true);
        file_put_contents($filename, "<?php\n\nreturn " . $phpArray . ";");
    }

    public function getKbSize($filename)
    {
        return round(filesize($filename) / 1024);
    }
}
