<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
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
class Setup_CliTest extends TestCase
{
    protected $_oldConfigs = array();

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_cli = new Setup_Frontend_Cli();
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        // reset old configs
        foreach ($this->_oldConfigs as $name => $value) {
            Tinebase_Config::getInstance()->set($name, $value);
        }
    }

    /**
     * Test SetConfig
     */
    public function testSetConfig()
    {
        $this->_oldConfigs[Tinebase_Config::ALLOWEDJSONORIGINS] = Tinebase_Config::getInstance()->get(Tinebase_Config::ALLOWEDJSONORIGINS);
        $output = $this->_cliHelper('setconfig', array('--setconfig','--','configkey=allowedJsonOrigins', 'configvalue='.'["foo","bar"]'));
        $this->assertContains('OK - Updated configuration option allowedJsonOrigins for application Tinebase', $output);
        $result = Tinebase_Config_Abstract::factory('Tinebase')->get('allowedJsonOrigins');
        $this->assertEquals("foo", $result[0]);
        $this->assertEquals("bar", $result[1]);
    }

    /**
     * Test SetBoolConfig
     */
    public function testSetBoolConfig()
    {
        $this->_oldConfigs[Tinebase_Config::MAINTENANCE_MODE] = Tinebase_Config::getInstance()->get(Tinebase_Config::MAINTENANCE_MODE);

        $values = array(1, "true");
        foreach ($values as $configValue) {
            $output = $this->_cliHelper('setconfig', array('--setconfig', '--', 'configkey=maintenanceMode', 'configvalue=' . $configValue));
            $this->assertContains('OK - Updated configuration option maintenanceMode for application Tinebase', $output);
            $result = Tinebase_Config_Abstract::factory('Tinebase')->get(Tinebase_Config::MAINTENANCE_MODE);
            $this->assertTrue($result);
        }
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
}
