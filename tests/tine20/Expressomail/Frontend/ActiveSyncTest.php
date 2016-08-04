<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 * @author      Jeferson Miranda <jeferson.miranda@serpro.gov.br>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Expressomail_Frontend_Json
 *
 * @package     Expressomail
 */
class Expressomail_Frontend_ActiveSyncTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $mimePath;

    /**
     * @var string
     */
    protected $resultPath;

    /**
     * @var array
     */
    protected $mimeFileNames = array();

    /**
     * @var array
     */
    protected $resultFileNames = array();

    /**
     * @var array
     */
    protected $mimes = array();

    /**
     * @var array
     */
    protected $results = array();

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->initTestCaseFiles();
        $this->initTestCases();
    }

    /**
     * loads mimes and result file names
     */
    public function initTestCaseFiles()
    {
        $this->mimePath = getcwd().'/tine20/Expressomail/Frontend/files/mime/';
        $this->resultPath = getcwd().'/tine20/Expressomail/Frontend/files/result/';
        foreach (glob($this->mimePath.'*.eml') as $filename) {
            array_push($this->mimeFileNames, basename($filename, '.eml'));
        }
        foreach (glob($this->resultPath.'*.json') as $filename) {
            array_push($this->resultFileNames, basename($filename, '.json'));
        }
    }

    /**
     * load test case file into memory
     */
    public function initTestCases()
    {
        foreach ($this->mimeFileNames as $filename) {
            $this->mimes[$filename] = stream_get_contents(fopen($this->mimePath.$filename.'.eml', 'r'));
        }
        foreach ($this->resultFileNames as $filename) {
            $this->results[$filename] = json_decode(file_get_contents($this->resultPath.$filename.'.json'), true);
        }
        $this->createResultFiles();
    }

    /**
     * create result files for new mime files
     */
    public function createResultFiles()
    {
        $controller = Expressomail_Controller_ActiveSync::getInstance();
        foreach ($this->mimes as $name => $data) {
            if (!array_key_exists($name, $this->results)) {
                $onlyInline = strpos($name, 'REPLY') !== FALSE ? TRUE : FALSE;
                $this->results[$name] = $controller->getHtmlBodyAndAttachmentData($data, $onlyInline);
                file_put_contents($this->resultPath.$name.'.json', json_encode($this->results[$name]));
            }
        }
    }

    /**
     * assert if processed mimes equals expected results
     */
    public function testMimesAndResults()
    {
        $controller = Expressomail_Controller_ActiveSync::getInstance();
        foreach ($this->mimes as $filename => $content) {
            $expected = $this->results[$filename];
            $onlyInline = strpos($filename, 'REPLY') !== FALSE ? TRUE : FALSE;
            $generated = $controller->getHtmlBodyAndAttachmentData($content, $onlyInline);
            $message = 'Expected:'.PHP_EOL.PHP_EOL.print_r($expected, TRUE).PHP_EOL.PHP_EOL.
                       'Generated:'.PHP_EOL.PHP_EOL.print_r($generated, TRUE).PHP_EOL.PHP_EOL.
                       'on file: ' . $filename;
            $this->assertEquals($expected, $generated, $message);
        }
    }
}
