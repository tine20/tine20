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
        $config = Tinebase_Config::REDIRECTTOREFERRER;
        $this->_oldConfigs[$config] = Tinebase_Config::getInstance()->get($config);

        $values = array(1, "true");
        foreach ($values as $configValue) {
            $output = $this->_cliHelper('setconfig', array('--setconfig', '--', 'configkey=' . $config, 'configvalue=' . $configValue));
            $this->assertContains('OK - Updated configuration option ' . $config . ' for application Tinebase', $output);
            $result = Tinebase_Config_Abstract::factory('Tinebase')->get($config);
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

    /**
     * Test compare
     */
    public function testCompare()
    {
        $this->testSetConfig();
        $result = $this->_cliHelper('compare', array('--compare','--','otherdb='
            . Tinebase_Config::getInstance()->get('database')->dbname));
        $this->assertContains("Array
(
)", $result);
    }

    /**
     * Test setpassword of replicationuser
     */
    public function testSetPassword()
    {
        $this->testSetConfig();
        $result = $this->_cliHelper('setpassword', array('--setpassword','--','username='
            . Tinebase_User::SYSTEM_USER_REPLICATION, 'password=xxxx1234'));
        self::assertEmpty($result);
        $auth = Tinebase_Auth::getInstance()->authenticate(Tinebase_User::SYSTEM_USER_REPLICATION, 'xxxx1234');
        self::assertTrue($auth->isValid(), print_r($auth, true));
    }

    /**
     * Test isInstalled
     *
     * Test expects Tinebase_Application->isInstalled('Tinebase', ture) to work correctly.
     * Testing with out mocking requires to uninstall and install all applications.
     * After uninstalling 'Tinebase' is still cached in Tinebase_PerRequestCache. So that isInstalled dose not work correctly.
     * Resetting the cache solves that. But than reinstalling the applications fails.
     */
    public function testIsInstalled() {
        $opts = new Zend_Console_Getopt(array('is_installed' => 'is_installed'));
        $opts->setArguments(array('--is_installed'));

        $stub = $this->getMockBuilder(Tinebase_Application::class)->disableOriginalConstructor()->getMock();
        $stub->method('isInstalled')->with(
            $this->equalTo('Tinebase'),
            $this->equalTo(true)
        )->willReturn(true);
        $result = (new Setup_Frontend_Cli($stub))->handle($opts, false);
        self::assertEquals(0, $result);

        $stub = $this->getMockBuilder(Tinebase_Application::class)->disableOriginalConstructor()->getMock();
        $stub->method('isInstalled')->with(
            $this->equalTo('Tinebase'),
            $this->equalTo(true)
        )->willReturn(false);
        $result = (new Setup_Frontend_Cli($stub))->handle($opts, false);
        self::assertEquals(1, $result);

        $stub = $this->getMockBuilder(Tinebase_Application::class)->disableOriginalConstructor()->getMock();
        $stub->method('isInstalled')->with(
            $this->equalTo('Tinebase'),
            $this->equalTo(true)
        )->willThrowException(new Exception("A test Exception"));
        $result = (new Setup_Frontend_Cli($stub))->handle($opts, false);
        self::assertEquals(1, $result);
    }
}
