<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
class Setup_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Setup_Controller
     */
    protected $_uit = null;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_uit = Setup_ControllerMock::getInstance();

        $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
        if (null === ($oldUser = Tinebase_Core::getUser())) {
            Tinebase_Core::set(Tinebase_Core::USER, $setupUser);
        }

        foreach ($setupUser->getGroupMemberships() as $gId) {
            Tinebase_Group::getInstance()->removeGroupMember($gId, $setupUser->accountId);
        }
        Tinebase_Group::unsetInstance();
        foreach (Tinebase_Acl_Roles::getInstance()->getRoleMemberships($setupUser->accountId) as $rId) {
            Tinebase_Acl_Roles::getInstance()->removeRoleMember($rId, $setupUser->accountId);
        }
        Tinebase_Acl_Roles::unsetInstance();

        if (null === $oldUser) {
            Tinebase_Core::unsetUser();
        }
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $testCredentials = Setup_TestServer::getInstance()->getTestCredentials();
        $this->_installAllApplications(array(
            'defaultAdminGroupName' => 'Administrators',
            'defaultUserGroupName'  => 'Users',
            'adminLoginName'        => $testCredentials['username'],
            'adminPassword'         => $testCredentials['password'],
        ));
    }

    /**
     * testLoginWithWrongUsernameAndPassword
     */
    public function testLoginWithWrongUsernameAndPassword()
    {
        $result = $this->_uit->login('unknown_user_xxyz', 'wrong_password');
        $this->assertFalse($result);
    }

    /**
     * test uninstall application and cache clearing
     *
     */
    public function testUninstallApplications()
    {
        $cache = Tinebase_Core::getCache();
        $cacheId = 'unittestcache';
        $cache->save('something', $cacheId);
        
        $this->_uit->uninstallApplications(array('ActiveSync'));
        
        $this->assertFalse($cache->test($cacheId), 'cache is not cleared');

        $apps = $this->_uit->searchApplications();
        
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
    }

    /**
     * test if app can be uninstalled and installed again
     */
    public function testUninstallAndInstallAgain()
    {
        $this->_uit->uninstallApplications(array('Filemanager'));
        $this->_uit->installApplications(array('Filemanager'));
        self::assertTrue(Setup_Controller::getInstance()->isInstalled('Filemanager'));
    }

    public function testReplicationInstall()
    {
        // uninstall and get instance sequence
        $this->_uit->uninstallApplications(['ActiveSync']);
        static::assertFalse($this->_uit->isInstalled('ActiveSync'));
        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        // install again and get modification logs
        $this->_uit->installApplications(['ActiveSync']);
        static::assertTrue($this->_uit->isInstalled('ActiveSync'));
        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getReplicationModificationsByInstanceSeq($instance_seq);
        $applicationModifications = $modifications->filter('record_type', Tinebase_Model_Application::class);
        static::assertEquals(1, $applicationModifications->count(), 'should have 1 mod logs to process');

        // uninstall again
        $this->_uit->uninstallApplications(['ActiveSync']);
        static::assertFalse($this->_uit->isInstalled('ActiveSync'));

        // apply modification log => application should be installed again
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs($applicationModifications);
        static::assertTrue($result, 'applyReplicationModLogs failed');
        static::assertTrue($this->_uit->isInstalled('ActiveSync'));
    }

    public function testReplicationUninstall()
    {
        // get instance sequence and uninstall
        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();
        $this->_uit->uninstallApplications(['ActiveSync']);
        static::assertFalse($this->_uit->isInstalled('ActiveSync'));

        // get modification logs
        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getReplicationModificationsByInstanceSeq($instance_seq);
        $applicationModifications = $modifications->filter('record_type', Tinebase_Model_Application::class);
        static::assertEquals(1, $applicationModifications->count(), 'should have 1 mod logs to process');

        // install again
        $this->_uit->installApplications(['ActiveSync']);
        static::assertTrue($this->_uit->isInstalled('ActiveSync'));

        // apply modification log => application should be uninstalled
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs($applicationModifications);
        static::assertTrue($result, 'applyReplicationModLogs failed');
        static::assertFalse($this->_uit->isInstalled('ActiveSync'));
    }
    
    /**
     * testInstallAdminAccountOptions
     */
    public function testInstallAdminAccountOptions()
    {
        $this->_uninstallAllApplications();
        $this->_uit->installApplications(array('Tinebase'), array('adminLoginName' => 'phpunit-admin', 'adminPassword' => 'phpunit-password'));
        $adminUser = Tinebase_User::getInstance()->getFullUserByLoginName('phpunit-admin');
        $this->assertTrue($adminUser instanceof Tinebase_Model_User);
        
        $this->assertNull(Tinebase_Auth::getBackendConfiguration('adminLoginName'));
        $this->assertNull(Tinebase_Auth::getBackendConfiguration('adminPassword'));
        $this->assertNull(Tinebase_Auth::getBackendConfiguration('adminConfirmation'));
        
        // cleanup
        $this->_uninstallAllApplications();
    }
    
    /**
     * testSaveAuthenticationRedirectSettings
     */
    public function testSaveAuthenticationRedirectSettings()
    {
        $originalRedirectSettings = array(
            Tinebase_Config::REDIRECTURL => Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTURL, ''),
            Tinebase_Config::REDIRECTTOREFERRER => Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTTOREFERRER, FALSE)
        );
         
        $newRedirectSettings = array(
            Tinebase_Config::REDIRECTURL => 'http://tine20.org',
            Tinebase_Config::REDIRECTTOREFERRER => TRUE
        );
        
        $this->_uit->saveAuthentication(array('redirectSettings' => $newRedirectSettings));
        
        $storedRedirectSettings = array(
            Tinebase_Config::REDIRECTURL => Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTURL),
            Tinebase_Config::REDIRECTTOREFERRER => Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTTOREFERRER)
        );
        
        $configNames = array(Tinebase_Config::REDIRECTURL, Tinebase_Config::REDIRECTTOREFERRER);
        foreach ($configNames as $configName) {
            $this->assertEquals($storedRedirectSettings[$configName], $newRedirectSettings[$configName],
                'new setting should match stored settings: ' . print_r($newRedirectSettings, TRUE));
        }
        
        // test empty redirectUrl
        $newRedirectSettings = array(
            Tinebase_Config::REDIRECTURL => '',
            Tinebase_Config::REDIRECTTOREFERRER => FALSE
        );
        
        $this->_uit->saveAuthentication(array('redirectSettings' => $newRedirectSettings));
        
        $storedRedirectSettings = array(
            Tinebase_Config::REDIRECTURL => Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTURL),
            Tinebase_Config::REDIRECTTOREFERRER => Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTTOREFERRER)
        );
        
        foreach ($configNames as $configName) {
            $this->assertEquals($storedRedirectSettings[$configName], $newRedirectSettings[$configName],
                'new setting should match stored settings (with empty redirect URL): ' . print_r($newRedirectSettings, TRUE));
        }
        
        $this->_uit->saveAuthentication($originalRedirectSettings);
    }
    
    /**
     * testInstallGroupNameOptions
     */
    public function testInstallGroupNameOptions()
    {
        $this->_uninstallAllApplications();
        $testCredentials = Setup_TestServer::getInstance()->getTestCredentials();
        $this->_installAllApplications(array(
            'defaultAdminGroupName' => 'phpunit-admins',
            'defaultUserGroupName'  => 'phpunit-users',
            'adminLoginName'        => $testCredentials['username'],
            'adminPassword'         => $testCredentials['password'],
        ));
        $adminUser = Tinebase_Core::get('currentAccount');
        $this->assertEquals('phpunit-admins', Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_ADMIN_GROUP_NAME_KEY));
        $this->assertEquals('phpunit-users', Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY));

        // setupuser and replication user should be disabled
        foreach (array(Tinebase_User::SYSTEM_USER_SETUP, Tinebase_User::SYSTEM_USER_REPLICATION) as $username) {
            $systemUser = Tinebase_User::getInstance()->getFullUserByLoginName($username);
            self::assertEquals(Tinebase_Model_User::ACCOUNT_STATUS_DISABLED, $systemUser->accountStatus,
                $username . ' should be disabled');
        }
    }
    
    /**
     * test uninstall application
     *
     */
    public function testUninstallTinebaseShouldThrowDependencyException()
    {
        $result = $this->_uit->uninstallApplications(array('Tinebase'));
        $this->assertTrue($this->_uit->setupRequired());
    }
    
    /**
     * test search applications
     *
     */
    public function testSearchApplications()
    {
        $apps = $this->_uit->searchApplications();
        
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
        $this->assertTrue(isset($activeSyncApp['id']), 'ActiveSync ID missing ' . print_r($apps['results'], true));
        $this->assertEquals('uptodate', $activeSyncApp['install_status']);
    }
    
    /**
     * test install application
     */
    public function testInstallApplications()
    {
        try {
            $result = $this->_uit->installApplications(array('ActiveSync'));
        } catch (Exception $e) {
            $this->_uit->uninstallApplications(array('ActiveSync'));
            $result = $this->_uit->installApplications(array('ActiveSync'));
        }
                
        $apps = $this->_uit->searchApplications();
        
        // get active sync
        foreach ($apps['results'] as $app) {
            if ($app['name'] == 'ActiveSync') {
                $activeSyncApp = $app;
                break;
            }
        }
        
        
        $applicationId = $activeSyncApp['id'];
        // checks
        $this->assertTrue(isset($activeSyncApp));
        $this->assertTrue(isset($applicationId));
        $this->assertEquals('enabled', $activeSyncApp['status']);
        $this->assertEquals('uptodate', $activeSyncApp['install_status']);
        
        //check if user role has the right to run the recently installed app
        $roles = Tinebase_Acl_Roles::getInstance();
        $userRole = $roles->getRoleByName('user role');
        $rights = $roles->getRoleRights($userRole->getId());
        $hasRight = false;
        foreach ($rights as $right) {
            if ($right['application_id'] === $applicationId &&
                $right['right'] === 'run') {
                $hasRight = true;
            }
        }
        $this->assertTrue($hasRight, 'User role has run right for recently installed app?');
    }

    /**
     * test install applications from dump
     *
     * @see 0012728: install from (backup) dump
     */
    public function testInstallFromDump()
    {
        if ($this->_uit->isInstalled('Tinebase')) {
            $this->_uninstallAllApplications();
        }

        $oldTinebaseId = '624f5f466ad6306dac31ad81e06cc6ca25090bb3';
        $options = array(
            'backupDir' => dirname(__DIR__) . '/files/2018-08-27-17-04-11',
            'db' => 1,
        );
        $result = $this->_uit->getInstance()->installFromDump($options);
        $this->assertTrue($result);
        $this->assertTrue($this->_uit->isInstalled('Addressbook'), 'Addressbook is not installed');
        $tinebaseId = Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId();
        $this->assertNotEquals($oldTinebaseId, $tinebaseId);
        $this->assertGreaterThan(40, Tinebase_Application::getInstance()->getApplicationTables($tinebaseId));

        $this->_uninstallAllApplications();
    }

    /**
     * test update application
     *
     * @todo test real update process; currently this test case only tests updating an already uptodate application
     */
    public function testUpdateApplications()
    {
        $applications = new Tinebase_Record_RecordSet('Tinebase_Model_Application');
        $applications->addRecord(Tinebase_Application::getInstance()->getApplicationByName('ActiveSync'));
        $result = $this->_uit->updateApplications($applications);
        $this->assertTrue(is_array($result));
    }

    /**
     * test env check
     *
     */
    public function testEnvCheck()
    {
        $result = $this->_uit->checkRequirements();
        
        $this->assertTrue(isset($result['success']));
        $this->assertGreaterThan(16, count($result['results']));
    }
    
    /**
     * uninstallAllApplications
     */
    protected function _uninstallAllApplications()
    {
        $installedApplications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        $this->_uit->uninstallApplications($installedApplications->name);
        Tinebase_Core::unsetTinebaseId();
        Tinebase_Group::unsetInstance();
        Tinebase_Acl_Roles::unsetInstance();
        Tinebase_Core::unsetUser();
        Tinebase_Cache_PerRequest::getInstance()->reset();
        Admin_Config::unsetInstance();
    }
    
    /**
     * installAllApplications
     *
     * @param array $_options
     * @throws Setup_Exception
     */
    protected function _installAllApplications($_options = null)
    {
        if (! $this->_uit) {
            throw new Setup_Exception('could not run test, Setup_Controller init failed');
        }

        Tinebase_Core::unsetTinebaseId();
        Tinebase_Group::unsetInstance();
        Tinebase_Cache_PerRequest::getInstance()->reset();
        Admin_Config::unsetInstance();
        $installableApplications = $this->_uit->getInstallableApplications();
        $installableApplications = array_keys($installableApplications);
        $this->_uit->installApplications($installableApplications, $_options);
    }

    /**
     * @see 11574: backup should only dump structure of some tables
     */
    public function testGetBackupStructureOnlyTables()
    {
        $tables = Setup_Controller::getInstance()->getBackupStructureOnlyTables();

        $this->assertTrue(in_array(SQL_TABLE_PREFIX . 'felamimail_cache_message', $tables), 'felamimail tables need to be in _getBackupStructureOnlyTables');
    }

    public function testSortInstallableApplications()
    {
        $apps = ['Tinebase','Addressbook','Courses','CoreData','Voipmanager','Filemanager','SimpleFAQ','HumanResources','Crm','Inventory','ExampleApplication','ActiveSync','Phone','Timetracker','Tasks','Projects','Felamimail','Admin','Calendar','Sales'];

        $applications = array();
        foreach ($apps as $applicationName) {
            $applications[$applicationName] = Setup_Controller::getInstance()->getSetupXml($applicationName);
        }

        $result = Setup_Controller::getInstance()->sortInstallableApplications($applications);
        $expected = array (
            0 => 'Tinebase',
            1 => 'Admin',
            2 => 'Addressbook',
            3 => 'Calendar',
            4 => 'CoreData',
            5 => 'Felamimail',
            6 => 'Sales',
            7 => 'ExampleApplication',
            8 => 'Inventory',
            9 => 'Projects',
            10 => 'Timetracker',
            11 => 'ActiveSync',
            12 => 'Filemanager',
            13 => 'Phone',
            14 => 'Crm',
            15 => 'Tasks',
            16 => 'Courses',
            17 => 'Voipmanager',
            18 => 'HumanResources',
            19 => 'SimpleFAQ',
        );
        self::assertEquals($expected, array_keys($result));
    }

    public function testApplicationUpdateInitialize()
    {
        $appCtrl = Tinebase_Application::getInstance();

        //try {
            $exampleApp = $appCtrl->getApplicationByName(ExampleApplication_Config::APP_NAME);
        /*} catch (Tinebase_Exception_NotFound $tenf) {
          install it on the fly?
        }*/

        $state = json_decode($appCtrl->getApplicationState($exampleApp, Tinebase_Application::STATE_UPDATES), true);
        static::assertTrue(is_array($state) && isset($state[ExampleApplication_Setup_Update_0::RELEASE000_UPDATE001])
            && isset($state[ExampleApplication_Setup_Update_12::RELEASE012_UPDATE001]), print_r($state, true));
        static::assertCount(4, $state, print_r($state, true));
    }
}
