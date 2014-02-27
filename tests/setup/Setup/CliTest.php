<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Setup_CliTest extends PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_cli = new Setup_Frontend_Cli();
    }
    
    /**
     * Test SetConfig
     */
    public function testSetConfig()
    {
        $output = $this->_cliHelper('setconfig', array('--setconfig','--','configkey=allowedJsonOrigins', 'configvalue='.'["foo","bar"]'));
        $this->assertContains('OK - Updated configuration option allowedJsonOrigins for application Tinebase', $output);
        $result = Tinebase_Config_Abstract::factory('Tinebase')->get('allowedJsonOrigins');
        $this->assertEquals("foo", $result[0]);
        $this->assertEquals("bar", $result[1]);
    }
    
    /**
     * Test GetConfig
     */
    public function testGetConfig()
    {
        $this->testSetConfig();
        $result = $this->_cliHelper('getconfig', array('--getconfig','--','configkey=allowedJsonOrigins'));
        $result = Zend_Json::decode($result);
        $this->assertEquals("foo", $result[0]);
        $this->assertEquals("bar", $result[1]);
    }
    
    /**
     * call handle cli function with params
     * 
     * @param array $_params
     */
    protected function _cliHelper($command, $_params)
    {
        $opts = new Zend_Console_Getopt(array($command => $command));
        $opts->setArguments($_params);
        ob_start();
        $this->_cli->handle($opts, false);
        $out = ob_get_clean();
        return $out;
    }
}