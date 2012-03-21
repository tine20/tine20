<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_SqlTest::main');
}

/**
 * Test class for Tinebase_User
 */
class Tinebase_User_SqlTest extends PHPUnit_Framework_TestCase
{
    /**
     * sql user backend
     *
     * @var Tinebase_User_Sql
     */
    protected $_backend = NULL;
    
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_SqlTest');
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
        if (Tinebase_User::getConfiguredBackend() !== Tinebase_User::SQL) {
            $this->markTestSkipped('SQL backend not enabled');
        }
        
        $this->_backend = Tinebase_User::factory(Tinebase_User::SQL);

        // remove user left over by broken tests
        try {
            $user = $this->_backend->getUserByLoginName('tine20phpunituser', 'Tinebase_Model_FullUser');
            $this->_backend->deleteUser($user);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing 
        }
        
        $this->objects['users'] = array();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->objects['users'] as $user) {
            $this->_backend->deleteUser($user);
        }
    }
    
    /**
     * try to add an account
     *
     */
    public function testAddUser()
    {
        $testUser = $this->getTestRecord();
        $testUser->setId(Tinebase_Record_Abstract::generateUID());
        
        $this->objects['users']['addedUser'] = $this->_backend->addUser($testUser);
        
        $this->assertEquals($testUser->getId(),      $this->objects['users']['addedUser']->getId());
        $this->assertEquals('hidden',                $this->objects['users']['addedUser']->visibility);
        $this->assertEquals('Tinebase_Model_FullUser', get_class($testUser), 'wrong type');
        
        return $this->objects['users']['addedUser'];
    }
    
    /**
     * try to get all accounts containing phpunit in there name
     *
     */
    public function testGetUsers()
    {
        // add at least one user
        $this->testAddUser();
        
        $users = $this->_backend->getUsers('tine20phpunituser', 'accountStatus');
        
        $this->assertGreaterThanOrEqual(1, count($users));
    }
    
    /**
     * try to get an user by loginname
     *
     */
    public function testGetUserByLoginName()
    {
        // add a test user
        $user = $this->testAddUser();
        
        $testUser = $this->_backend->getFullUserByLoginName($user->accountLoginName);
        
        $this->assertEquals($user->accountLoginName, $testUser->accountLoginName);
        $this->assertEquals('Tinebase_Model_FullUser', get_class($testUser), 'wrong type');
    }
    
    /**
     * try to get an user by userId
     *
     */
    public function testGetUserById()
    {
        $user = $this->testAddUser();
        
        $testUser = $this->_backend->getFullUserById($user->getId());
        
        $this->assertEquals($user->accountLoginName, $testUser->accountLoginName);
        $this->assertEquals('Tinebase_Model_FullUser', get_class($testUser), 'wrong type');
    }
        
    /**
     * try to update an account
     *
     */
    public function testUpdateUser()
    {
        // add a test user
        $testUser = $this->testAddUser();
        
        $testUser->contact_id        = null;
        $testUser->visibility        = 'displayed';
        $testUser->accountLoginName  = 'tine20phpunit-updated';
        $testUser->accountStatus     = 'disabled';
        
        $user = $this->_backend->updateUser($testUser);
        
        $this->assertEquals($user->accountLoginName, $user->accountLoginName);
        $this->assertEquals('hidden',                $user->visibility);
        $this->assertEquals('disabled',              $user->accountStatus);
    }
    
    /**
     * try to enable an account
     *
     */
    public function testSetStatusEnabled()
    {
        // add a test user
        $testUser = $this->testAddUser();
        $testUser->accountStatus = 'disabled';
        $testUser = $this->_backend->updateUser($testUser);
        
        $this->_backend->setStatus($testUser, 'enabled');
        
        $user = $this->_backend->getUserById($testUser, 'Tinebase_Model_FullUser');
        
        $this->assertEquals('enabled', $user->accountStatus);
    }
    
    /**
     * try to enable an account
     *
     */
    public function testUserIsBlocked()
    {
        // add a test user (enabled by default)
        $testUser = $this->testAddUser();
        
        for ($i=0; $i<7; $i++) {
            $this->_backend->setLastLoginFailure($testUser->accountLoginName);
        }
        
        $user = $this->_backend->getUserById($testUser, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(Tinebase_User::STATUS_BLOCKED, $user->accountStatus);
    }
    
    /**
     * try to enable an account
     *
     */
    public function testUserIsDisabled()
    {
        // add a test user (enabled by default)
        $testUser = $this->testAddUser();
        $this->_backend->setStatus($testUser, 'disabled');
        
        for ($i=0; $i<7; $i++) {
            $this->_backend->setLastLoginFailure($testUser->accountLoginName);
        }
        
        $user = $this->_backend->getUserById($testUser, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(Tinebase_User::STATUS_DISABLED, $user->accountStatus);
    }
    
    /**
     * try to enable an account
     *
     */
    public function testUserIsEnabledAgain()
    {
        // add a test user (enabled by default)
        $testUser = $this->testAddUser();
        
        for ($i=0; $i<7; $i++) {
            $this->_backend->setLastLoginFailure($testUser->accountLoginName);
        }
        
        $testUser = $this->_backend->getUserById($testUser, 'Tinebase_Model_FullUser');
        
        // enable blocked user again
        $testUser->accountStatus = Tinebase_User::STATUS_ENABLED;
        $user = $this->_backend->updateUser($testUser, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(Tinebase_User::STATUS_ENABLED, $user->accountStatus);
    }
    
    /**
     * try to disable an account
     *
     */
    public function testSetStatusDisabled()
    {
        // add a test user (enabled by default)
        $testUser = $this->testAddUser();
        
        $this->_backend->setStatus($testUser, Tinebase_User::STATUS_DISABLED);

        $user = $this->_backend->getUserById($testUser, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(Tinebase_User::STATUS_DISABLED, $user->accountStatus);
    }
    
    /**
     * try to update the logintimestamp
     *
     */
    public function testSetLoginTime()
    {
        $user = $this->testAddUser();
        
        $this->_backend->setLoginTime($user, '127.0.0.1');
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertNotEquals($user->accountLastLogin, $testUser->accountLastLogin);
    }
    
    /**
     * try to set password
     *
     */
    public function testSetPassword()
    {
        $user = $this->testAddUser();

        $this->_backend->setPassword($user, Tinebase_Record_Abstract::generateUID());
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertNotEquals($user->accountLastPasswordChange, $testUser->accountLastPasswordChange);
    }
        
    /**
     * try to set the expirydate
     *
     */
    public function testSetExpiryDate()
    {
        $user = $this->testAddUser();
        
        
        $this->_backend->setExpiryDate($user, Tinebase_DateTime::now()->subDay(1));
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertEquals('Tinebase_DateTime', get_class($testUser->accountExpires), 'wrong type');
        $this->assertEquals(Tinebase_User::STATUS_EXPIRED, $testUser->accountStatus);
        

        $this->_backend->setExpiryDate($user, NULL);
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(NULL, $testUser->accountExpires);
        $this->assertEquals(Tinebase_User::STATUS_ENABLED, $testUser->accountStatus);
    }
        
    /**
     * try to delete an accout
     *
     */
    public function testDeleteUser()
    {
        // add a test user (enabled by default)
        $testUser = $this->testAddUser();
        
        $this->_backend->deleteUser($testUser);
        unset($this->objects['users']['addedUser']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $this->_backend->getUserById($testUser, 'Tinebase_Model_FullUser');
    }
    
    public function testSanitizeAccountPrimaryGroupId()
    {
        $account = Tinebase_Core::get('currentAccount');
        $originalGroupId = $account->accountPrimaryGroup;
        $defaultGroupId = Tinebase_Group::getInstance()->getDefaultGroup()->getId();
        $adminGroupId   = Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId();
        $nonExistingId  = '77777666999';
        
        $account->accountPrimaryGroup = $defaultGroupId;
        $this->assertEquals($defaultGroupId, $account->sanitizeAccountPrimaryGroup());
        $this->assertEquals($defaultGroupId, $account->accountPrimaryGroup);
        
        $account->accountPrimaryGroup = $adminGroupId;
        $this->assertEquals($adminGroupId, $account->sanitizeAccountPrimaryGroup());
        $this->assertEquals($adminGroupId, $account->accountPrimaryGroup);
        
        $account->accountPrimaryGroup = $nonExistingId;
        $this->assertEquals($defaultGroupId, $account->sanitizeAccountPrimaryGroup());
        $this->assertEquals($defaultGroupId, $account->accountPrimaryGroup);
        
        $account->accountPrimaryGroup = $originalGroupId;
    }
    
    /**
     * @return Tinebase_Model_FullUser
     */
    public static function getTestRecord()
    {
        $user  = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'tine20phpunituser',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->id,
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit User',
            'accountEmailAddress'   => 'phpunit@tine20.org'
        ));
        
        return $user;
    }
}       
    

if (PHPUnit_MAIN_METHOD == 'Tinebase_User_SqlTest::main') {
    Tinebase_User_SqlTest::main();
}
