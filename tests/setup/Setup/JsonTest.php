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
    protected $_json;
    
    /**
     * Authentication data as stored in config before a test runs.
     * Needed to restore originakl state after a test ran. 
     * @see setUp()
     * @see teardown()
     * 
     * @var array
     */
    protected $_originalAuthenticationData;
    
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
        $this->_originalAuthenticationData = $this->_json->loadAuthenticationData();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $authenticationData = $this->_json->loadAuthenticationData();
        if ($this->_originalAuthenticationData !== $authenticationData) {
            $this->_json->saveAuthentication($this->_originalAuthenticationData);
        }
        $this->_installAllApps();
    }
    
    public function testUninstallApplications()
    {
      try {
        $result = $this->_json->uninstallApplications(array('ActiveSync'));
      } catch (Tinebase_Exception_NotFound $e) {
        $this->_json->installApplications(array('ActiveSync'));
        $result = $this->_json->uninstallApplications(array('ActiveSync'));
      }
              
        $this->assertTrue($result['success']);

      $this->_json->installApplications(array('ActiveSync')); //cleanup
    }

    public function testUninstallTinebaseShouldThrowDependencyException()
    {
        $this->setExpectedException('Setup_Exception_Dependency');
        $result = $this->_json->uninstallApplications(array('Tinebase'));
    }
    
    public function testSearchApplications()
    {
        $apps = $this->_json->searchApplications();
        $this->assertGreaterThan(0, $apps['totalcount']);
    }

    public function testInstallApplications()
    {
        try {
            $result = $this->_json->installApplications(array('ActiveSync'));
        } catch (Exception $e) {
            $this->_json->uninstallApplications(array('ActiveSync'));
            $result = $this->_json->installApplications(array('ActiveSync'));
        }
        
        $this->assertTrue($result['success']);
    }

    /**
     * test update application
     *
     * @todo test real update process; currently this test case only tests updating an already uptodate application 
     */
    public function testUpdateApplications()
    {
        $result = $this->_json->updateApplications(array('ActiveSync'));
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
    }


    
    public function testLoginWithWrongUsernameAndPassword()
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
        $this->assertFalse($result['setupRequired']);
        $this->assertTrue(is_array($result['authenticationData']));
    }
    
    public function testLoadAuthenticationData()
    {
        $result = $this->_json->loadAuthenticationData();
        
        $this->assertTrue(is_array($result));
        $this->assertTrue(array_key_exists('authentication', $result));
        $this->assertTrue(array_key_exists('accounts', $result));
        $authentication = $result['authentication'];
        $this->assertContains($authentication['backend'], array(Tinebase_Auth_Factory::SQL, Tinebase_Auth_Factory::LDAP));
        $this->assertTrue(is_array($authentication[Tinebase_Auth_Factory::SQL]));
        $this->assertTrue(is_array($authentication[Tinebase_Auth_Factory::LDAP]));
    }
    
    public function testSaveAuthenticationSql()
    {
        $testAuthenticationData = $this->_json->loadAuthenticationData();

        $testAuthenticationData['authentication']['backend'] = Tinebase_Auth::SQL;
        $testAuthenticationData['authentication'][Tinebase_Auth::SQL]['adminLoginName'] = 'phpunit-admin';
        $testAuthenticationData['authentication'][Tinebase_Auth::SQL]['adminPassword'] = 'phpunit-password';
        $testAuthenticationData['authentication'][Tinebase_Auth::SQL]['adminPasswordConfirmation'] = 'phpunit-password';
        
        $this->_uninstallAllApps();
        
        $result = $this->_json->saveAuthentication($testAuthenticationData);
        
        $savedAuthenticationData = $this->_json->loadAuthenticationData();

        $adminUser = Tinebase_User::getInstance()->getFullUserByLoginName('phpunit-admin');
        $this->assertTrue($adminUser instanceof Tinebase_Model_User);
        
        //test if Tinebase stack was installed
        $apps = $this->_json->searchApplications();
        $baseApplicationStack = array('Tinebase', 'Admin', 'Addressbook');
        foreach ($apps['results'] as $app) {
            if ($app['install_status'] === 'uptodate' &&
                false !== ($index = array_search($app['name'], $baseApplicationStack))) {
                unset($baseApplicationStack[$index]);
            }
        }

        $this->assertTrue(empty($baseApplicationStack), 'Assure that base application stack was installed after saving authentication');

        $this->_uninstallAllApps(); //Ensure that all aps get re-installed with default username/password because some tests rely on these values
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
        $this->_json->saveConfig($configData);

        // load
        $result = $this->_json->loadConfig();
        
        // check
        $this->assertTrue(isset($result['test']));
        $this->assertEquals('value', $result['test']);
        $this->assertEquals($configData['database'], $result['database']);
    }
    
    protected function _uninstallAllApps()
    {
        $installedApplications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        $installedApplications = $installedApplications->name;

        $this->_json->uninstallApplications($installedApplications);
    }
    
    protected function _installAllApps()
    {
        $installableApplications = Setup_Controller::getInstance()->getInstallableApplications();
        $installableApplications = array_keys($installableApplications);
        $this->_json->installApplications($installableApplications);
    }
}
