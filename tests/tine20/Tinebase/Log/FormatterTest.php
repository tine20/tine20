<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Log_Formatter
 */
class Tinebase_Log_FormatterTest extends PHPUnit_Framework_TestCase
{
    /**
     * the logger
     * 
     * @var Zend_Log
     */
    protected $_logger = null;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Log_FormatterTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_logger = new Zend_Log();
    }

    /**
     * test pw replacements
     */
    public function testPWReplacements()
    {
        $logfile = tempnam(Tinebase_Core::getTempDir(), 'testlog');
        $writer = new Zend_Log_Writer_Stream($logfile);
        $formatter = new Tinebase_Log_Formatter();
        $formatter->setReplacements();
        $writer->setFormatter($formatter);
        $this->_logger->addWriter($writer);
        $filter = new Zend_Log_Filter_Priority(5);
        $this->_logger->addFilter($filter);
        
        $config = Tinebase_Core::getConfig();
        $this->_logger->notice($config->database->password);
        $loggerFile = file_get_contents($logfile);
        $writer->shutdown();
        unlink($logfile);
        
        $this->assertFalse(strpos($loggerFile, $config->database->password), 'pw found!');
        $this->assertContains('********', $loggerFile);
        $this->assertContains(Tinebase_Core::getUser()->accountLoginName . ' - ', $loggerFile);
    }
}
