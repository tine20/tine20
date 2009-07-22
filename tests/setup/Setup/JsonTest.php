<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id:JsonTest.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        make this work again (when setup tests have been moved)
 * @todo        add more tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Setup_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Setup_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Setup_Frontend_Json
     */
    protected $_json = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Setup Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_json = new Setup_Frontend_Json();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {        
    }

    /**
     * test uninstall application
     *
     */
    public function testUninstallApplications()
    {
    	try {
    		$result = $this->_json->uninstallApplications(Zend_Json::encode(array('ActiveSync')));
    	} catch (Tinebase_Exception_NotFound $e) {
    		$this->_json->installApplications(Zend_Json::encode(array('ActiveSync')));
    		$result = $this->_json->uninstallApplications(Zend_Json::encode(array('ActiveSync')));
    	}
        
        $this->assertEquals(
	        array(
	            'success'=> true,
	        ),
	        $result);

	    $result = $this->_json->installApplications(Zend_Json::encode(array('ActiveSync')));
    }
    
    /**
     * test search applications
     *
     */
    public function testSearchApplications()
    {
        /*
        $apps = $this->_json->searchApplications();
        //print_r($apps);
        
        $this->assertGreaterThan(0, $apps['totalcount']);
        
        // get active sync
        foreach ($apps['results'] as $app) {
            if ($app['name'] == 'ActiveSync') {
                $activeSyncApp = $app;
                break;
            }
        }
        
        // checks
        $this->assertTrue(isset($activeSyncApp));
        $this->assertTrue(!isset($activeSyncApp['id']));
        $this->assertEquals('uninstalled', $activeSyncApp['install_status']);
        */
    }
    
    /**
     * test install application
     *
     */
    public function testInstallApplications()
    {
        try {
            $result = $this->_json->installApplications(Zend_Json::encode(array('ActiveSync')));
        } catch (Exception $e) {
            $this->_json->uninstallApplications(Zend_Json::encode(array('ActiveSync')));
            $result = $this->_json->installApplications(Zend_Json::encode(array('ActiveSync')));
        }
        

        $apps = $this->_json->searchApplications();
        
        // get active sync
        foreach ($apps['results'] as $app) {
            if ($app['name'] == 'ActiveSync') {
                $activeSyncApp = $app;
                break;
            }
        }
        
        // checks
        $this->assertTrue(isset($activeSyncApp));
        $this->assertTrue(isset($activeSyncApp['id']));
        $this->assertEquals('enabled', $activeSyncApp['status']);
        $this->assertEquals('uptodate', $activeSyncApp['install_status']);
    }

    /**
     * test update application
     *
     * @todo implement
     */
    public function testUpdateApplications()
    {
        
    }

    /**
     * test env check
     *
     */
    public function testEnvCheck()
    {
        $result = $this->_json->envCheck();
        
        $this->assertTrue(isset($result['success']));
        $this->assertGreaterThan(16, count($result['results']));
    }

    /**
     * test load config
     *
     */
    public function testLoadConfig()
    {
        $result = $this->_json->loadConfig();
        
        $this->assertTrue(isset($result['database']));
        $this->assertGreaterThan(1, count($result));
    }

    /**
     * test load config
     *
     */
    public function testSaveConfig()
    {
        $configData = $this->_json->loadConfig();
        
        // add something to config
        $configData['test'] = 'value';
        $this->_json->saveConfig(Zend_Json::encode($configData));

        // load
        $result = $this->_json->loadConfig();
        
        // check
        $this->assertTrue(isset($result['test']));
        $this->assertEquals('value', $result['test']);
        $this->assertEquals($configData['database'], $result['database']);
    }
}
