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
class Setup_JsonTest extends PHPUnit_Framework_TestCase
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
        $this->assertEquals('uninstalled', $activeSyncApp['install_status']);

	    $this->_json->installApplications(Zend_Json::encode(array('ActiveSync'))); //cleanup
    }
    
    /**
     * test uninstall application
     *
     */
    public function testUninstallTinebaseShouldThrowDependencyException()
    {
        $this->setExpectedException('Setup_Exception_Dependency');
    	$result = $this->_json->uninstallApplications(Zend_Json::encode(array('Tinebase')));
    }
    
    /**
     * test search applications
     *
     */
    public function testSearchApplications()
    {
        $apps = $this->_json->searchApplications();
        
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
        $this->assertTrue(isset($activeSyncApp['id']));
        $this->assertEquals('uptodate', $activeSyncApp['install_status']);
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
     * @todo test real update process; currently this test case only tests updating an already uptodate application 
     */
    public function testUpdateApplications()
    {
        $result = $this->_json->updateApplications(Zend_Json::encode(array('ActiveSync')));
        $this->assertTrue($result['success']);
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

    public function testCheckCOnfig()
    {
    	$result = $this->_json->checkConfig();
    	$this->assertTrue(is_array($result));
    	$this->assertTrue($result['configExists']);
    	$this->assertTrue(isset($result['configWritable']));
    }
    
    public function testLogin()
    {
    	$result = $this->_json->login('unknown_user_xxyz', 'wrong_password');
    	$this->assertTrue(is_array($result));
        $this->assertFalse($result['success']);
        $this->assertTrue(isset($result['errorMessage']));
        
//        $config = Tinebase_Core::getConfig();
//        $result = $this->_json->login($config->setupuser->username, $config->setupuser->password);

    }
    
    /**
     * test load config
     *
     */
    public function testLoadConfig()
    {
        $result = $this->_json->loadConfig();
        
        $this->assertTrue(is_array($result));
        $this->assertTrue(isset($result['database']));
        $this->assertGreaterThan(1, count($result));
    }
    
    public function testGetRegistryData()
    {
    	$result = $this->_json->getRegistryData();
    	
    	$this->assertTrue(is_array($result));
    	$this->assertTrue(isset($result['configExists']));
        $this->assertTrue(isset($result['configWritable']));
        $this->assertTrue(isset($result['checkDB']));
        $this->assertTrue(isset($result['setupChecks']));
    }
    
//    public function testGetAllRegistryData()
//    {
//    	ob_start();
//    	$this->_json->getAllRegistryData();
//    	$result = ob_get_contents();
//    	ob_end_clean();
//    	//var_dump(Zend_Json::decode($result));
//    }

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
