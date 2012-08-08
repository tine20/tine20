<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
        $this->_installAllApps();
    }
    
    /**
     * testUninstallApplications
     */
    public function testUninstallApplications()
    {
        try {
            $result = $this->_json->uninstallApplications(array('ActiveSync'));
        } catch (Tinebase_Exception_NotFound $e) {
            $this->_json->installApplications(array('ActiveSync'));
            $result = $this->_json->uninstallApplications(array('ActiveSync'));
        }
              
        $this->assertTrue($result['success']);
    }

    /**
     * testUninstallTinebaseShouldThrowDependencyException
     *
     * tinebase uninstalls all other apps, too
     */
    public function testUninstallTinebaseShouldThrowDependencyException()
    {
        $result = $this->_json->uninstallApplications(array('Tinebase'));
        $this->assertTrue($result['success']);
        $this->assertTrue($result['setupRequired']);
    }
    
    /**
     * testSearchApplications
     */
    public function testSearchApplications()
    {
        $apps = $this->_json->searchApplications();
        $this->assertGreaterThan(0, $apps['totalcount']);
    }

    /**
     * testInstallApplications
     */
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

    /**
     * testLoginWithWrongUsernameAndPassword
     */
    public function testLoginWithWrongUsernameAndPassword()
    {
        $result = $this->_json->login('unknown_user_xxyz', 'wrong_password');
        $this->assertTrue(is_array($result));
        $this->assertFalse($result['success']);
        $this->assertTrue(isset($result['errorMessage']));
    }
    
    /**
     * test load config
     */
    public function testLoadConfig()
    {
        // register user first
        Setup_Core::set(Setup_Core::USER, 'setupuser');
        
        $result = $this->_json->loadConfig();
        
        $this->assertTrue(is_array($result), 'result is no array');
        $this->assertTrue(isset($result['database']), 'db config not found');
        $this->assertGreaterThan(1, count($result));
    }
    
    /**
     * testGetRegistryData
     */
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
    
    /**
     * testLoadAuthenticationData
     */
    public function testLoadAuthenticationData()
    {
        $result = $this->_json->loadAuthenticationData();
        
        $this->assertTrue(is_array($result));
        $this->assertTrue(array_key_exists('authentication', $result));
        $this->assertTrue(array_key_exists('accounts', $result));
        $authentication = $result['authentication'];
        $this->assertContains($authentication['backend'], array(Tinebase_Auth::SQL, Tinebase_Auth::LDAP));
        $this->assertTrue(is_array($authentication[Tinebase_Auth::SQL]));
        $this->assertTrue(is_array($authentication[Tinebase_Auth::LDAP]));
    }
    
    /**
     * testSaveAuthenticationSql
     */
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
    
    /**
     * testSavePasswordSettings
     * 
     * @see 0003008: add password policies
     */
    public function testSavePasswordSettings()
    {
        $testAuthenticationData = $this->_json->loadAuthenticationData();

        $configs = array(
            'changepw' => TRUE,
            'pwPolicyActive' => TRUE,
            'pwPolicyMinLength' => 1,
            'pwPolicyMinWordChars' => 1,
        );
        foreach ($configs as $config => $value) {
            $testAuthenticationData['password'][$config] = $value;
        }
        $result = $this->_json->saveAuthentication($testAuthenticationData);
        
        $this->assertTrue($result['success'], 'saveAuthentication unsuccessful');
        
        $testAuthenticationData = $this->_json->loadAuthenticationData();
        $this->assertTrue(isset($testAuthenticationData['password']), 'pw settings not found: ' . print_r($testAuthenticationData, TRUE));
        foreach ($configs as $config => $expected) {
            $this->assertEquals($expected, $testAuthenticationData['password'][$config], 'pw setting ' . $config . ' not found: ' . print_r($testAuthenticationData['password'], TRUE));
        }
    }
    
    /**
     * _uninstallAllApps helper
     */
    protected function _uninstallAllApps()
    {
        $installedApplications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        $installedApplications = $installedApplications->name;

        $this->_json->uninstallApplications($installedApplications);
    }
    
    /**
     * _installAllApps helper
     */
    protected function _installAllApps()
    {
        $installableApplications = Setup_Controller::getInstance()->getInstallableApplications();
        $installableApplications = array_keys($installableApplications);
        $this->_json->installApplications($installableApplications, array(
            'adminLoginName'        => Tinebase_Core::get('testconfig')->username,
            'adminPassword'         => Tinebase_Core::get('testconfig')->password,
        ));
    }
}
