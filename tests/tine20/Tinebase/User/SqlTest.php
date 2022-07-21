<?php

/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Tinebase_User
 */
class Tinebase_User_SqlTest extends TestCase
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
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        if (Tinebase_User::getConfiguredBackend() !== Tinebase_User::SQL) {
            $this->markTestSkipped('SQL backend not enabled');
        }
        
        $this->_backend = Tinebase_User::factory(Tinebase_User::SQL);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Tinebase_Config::getInstance()->set(Tinebase_Config::ACCOUNT_DELETION_EVENTCONFIGURATION, new Tinebase_Config_Struct(array(
        )));
    }

    /**
     * try to add an account
     *
     * @return Tinebase_Model_FullUser
     */
    public function testAddUser()
    {
        $testUser = $this->getTestRecord();
        $testUser->setId(Tinebase_Record_Abstract::generateUID());
        
        $this->objects['users']['addedUser'] = $this->_backend->addUser($testUser);
        
        $this->assertEquals($testUser->getId(),      $this->objects['users']['addedUser']->getId());
        $this->assertEquals('displayed',             $this->objects['users']['addedUser']->visibility);
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
        $this->assertEquals('displayed',             $user->visibility);
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

        $password = '0b%@%g1SL?](;*HNE%';
        $this->_backend->setPassword($user, $password);
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertNotEquals($user->accountLastPasswordChange, $testUser->accountLastPasswordChange);

        // try tp authenticate with pw
        $auth = Tinebase_Auth_Factory::factory(Tinebase_Auth::SQL);
        $auth->setIdentity($user->accountLoginName);
        $auth->setCredential($password);
        $result = $auth->authenticate();
        $this->assertTrue($result->isValid());
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

        if (Tinebase_User::getInstance()->isHardDeleteEnabled()) {
            $this->expectException('Tinebase_Exception_NotFound');
        }

        $user = $this->_backend->getUserById($testUser, 'Tinebase_Model_FullUser');
        if (! Tinebase_User::getInstance()->isHardDeleteEnabled()) {
            $this->assertTrue((bool)$user->is_deleted);
            $this->assertSame(Tinebase_Model_User::ACCOUNT_STATUS_DISABLED, $user->accountStatus);
        }
    }

    /**
     * test if deleted users data is removed
     *
     * TODO add test cases for keepOrganizerEvents and $_keepAsContact and $_keepAsContact
     */
    public function testDeleteUsersData()
    {
        // configure removal of data
        Tinebase_Config::getInstance()->set(Tinebase_Config::ACCOUNT_DELETION_EVENTCONFIGURATION, new Tinebase_Config_Struct(array(
            Tinebase_Config::ACCOUNT_DELETION_DELETE_PERSONAL_CONTAINER => true,
        )));

        // we need a valid group and a contact for this test
        $userContact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_given' => 'testuser'
        )));
        $testUser = $this->getTestRecord();
        $testUser->contact_id = $userContact->getId();
        $this->_backend->addUser($testUser);
        Tinebase_Group::getInstance()->addGroupMember($testUser->accountPrimaryGroup, $testUser->getId());

        $this->_setUser($testUser);

        // add a contact and an event to personal folders
        $event = Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event(array(
            'summary' => 'testevent',
            'dtstart' => '2015-12-24 12:00:00',
            'dtend' => '2015-12-24 13:00:00',
            'organizer' => $testUser->accountEmailAddress,
        ), true));
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_given' => 'testcontact'
        )));
        $this->_setUser($this->_originalTestUser);

        $this->_backend->deleteUser($testUser);

        // check if contact and event are removed
        $adbBackend = new Addressbook_Backend_Sql();
        try {
            $adbBackend->get($contact->getId());
            $this->fail('contact should be deleted');
        } catch (Exception $e) {
            $this->assertTrue($e instanceof Tinebase_Exception_NotFound, 'contact should be deleted');
        }
        $calBackend = new Calendar_Backend_Sql();
        try {
            $calBackend->get($event->getId());
            $this->fail('Event should be deleted: ' . print_r($event->toArray(), true));
        } catch (Exception $e) {
            $this->assertTrue($e instanceof Tinebase_Exception_NotFound, $e);
        }

        // check if container was removed, too
        try {
            $container = Tinebase_Container::getInstance()->getContainerById($contact->container_id);
            self::fail('personal container should be deleted: ' . print_r($container->toArray(), true));
        } catch (Tinebase_Exception_NotFound $tenf) {
            self::assertStringContainsString('Tinebase_Model_Container record with id', $tenf->getMessage());
        }
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
        $domain = TestServer::getPrimaryMailDomain();

        $user  = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'tine20phpunituser',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->id,
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit User',
            'accountEmailAddress'   => 'phpunit@' . $domain
        ));
        
        return $user;
    }

    /**
     * @see 0013188: set interval for user password change
     */
    public function testPasswordMustChange()
    {
        $user = $this->testAddUser();
        self::assertTrue($user->mustChangePassword());

        $user->setPassword(Tinebase_Record_Abstract::generateUID(20));
        // refetch
        $user = Tinebase_User::getInstance()->getFullUserById($user->getId());
        self::assertFalse($user->mustChangePassword());

        // change days config to 10
        Tinebase_Config::getInstance()->set(Tinebase_Config::PASSWORD_POLICY_CHANGE_AFTER, 10);

        // set password change: 11 days ago)
        $now = Tinebase_DateTime::now();
        $accountData['last_password_change'] = $now->subDay(11)->get(Tinebase_Record_Abstract::ISO8601LONG);
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        $where = array(
            $accountsTable->getAdapter()->quoteInto(
                $accountsTable->getAdapter()->quoteIdentifier('id') . ' = ?', $user->getId()
            )
        );
        $accountsTable->update($accountData, $where);

        // refetch
        $user = Tinebase_User::getInstance()->getFullUserById($user->getId());
        self::assertTrue($user->mustChangePassword(), 'user should need pw change: ' . print_r($user->toArray(), true));
    }
}
