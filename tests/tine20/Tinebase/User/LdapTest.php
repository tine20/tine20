<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_User_Ldap
 */
class Tinebase_User_LdapTest extends TestCase
{
    /**
     * ldap group backend
     *
     * @var Tinebase_User_LDAP
     */
    protected $_backend = NULL;
        
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        if (Tinebase_User::getConfiguredBackend() !== Tinebase_User::LDAP) {
            $this->markTestSkipped('LDAP backend not enabled');
        }
        $this->_backend = Tinebase_User::factory(Tinebase_User::LDAP);
    }
    
    /**
     * try to add an user
     * 
     * @return Tinebase_Model_FullUser
     */
    public function testAddUser()
    {
        $user = $this->getTestRecord();

        $this->_usernamesToDelete[] = $user->accountLoginName;
        $testUser = $this->_backend->addUser($user);

        $this->assertEquals($user->accountLoginName, $testUser->accountLoginName);
        $this->assertEquals('Tinebase_Model_FullUser', get_class($testUser), 'wrong type');
        
        return $user;
    }
    
    /**
     * try to get all users containing phpunit in there name
     */
    public function testGetUsers()
    {
        $this->testAddUser();
        
        $users = $this->_backend->getUsers('phpunit', 'accountStatus');
        
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
     * try to update an user
     *
     */
    public function testUpdateUser()
    {
        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::LDAP);
        
        $user = $this->testAddUser();
        $groupsBackend->addGroupMemberInSyncBackend($user->accountPrimaryGroup, $user);
        $groupsBeforeUpdate = $groupsBackend->getGroupMembershipsFromSyncBackend($user);
        
        $user->accountLoginName = 'tine20phpunituser-updated';
        $this->_usernamesToDelete[] = $user->accountLoginName;
        $testUser = $this->_backend->updateUser($user);
        $groupsAfterUpdate = $groupsBackend->getGroupMembershipsFromSyncBackend($testUser);
        
        sort($groupsBeforeUpdate);
        sort($groupsAfterUpdate);
        
        $this->assertEquals($user->accountLoginName, $testUser->accountLoginName);
        $this->assertEquals($groupsBeforeUpdate, $groupsAfterUpdate);
    }
    
    /**
     * try to enable an account
     *
     */
    public function testSetStatus()
    {
        $user = $this->testAddUser();

        $this->_backend->setStatus($user, Tinebase_User::STATUS_DISABLED);
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(Tinebase_User::STATUS_DISABLED, $testUser->accountStatus);
        
        
        $this->_backend->setStatus($user, Tinebase_User::STATUS_ENABLED);
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(Tinebase_User::STATUS_ENABLED, $testUser->accountStatus);
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
     * try to delete an user
     *
     */
    public function testDeleteUser()
    {
        $user = $this->testAddUser();
        
        $this->_backend->deleteUser($user);
        unset($this->objects['users']['testUser']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
    }
    
    /**
     * execute Tinebase_User::syncUser
     */
    public function testSyncUser()
    {
        $user = $this->testAddUser();
        
        Tinebase_User::syncUser($user, Tinebase_Application::getInstance()->isInstalled('Addressbook'));
    }
        
    /**
     * @return Tinebase_Model_FullUser
     */
    public static function getTestRecord()
    {
        $emailDomain = TestServer::getPrimaryMailDomain();
        
        $user  = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'tine20phpunituser',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->id,
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit User',
            'accountEmailAddress'   => 'phpunit@' . $emailDomain,
        ));
        
        return $user;
    }

    /**
     * @see 0011192: LDAP sync should delete contacts
     */
    public function testSyncDeleted()
    {
        $user = $this->testAddUser();

        // add user contact
        $contact = Admin_Controller_User::getInstance()->createOrUpdateContact($user);
        $user->contact_id = $contact->getId();
        Tinebase_User::getInstance()->updateUser($user);

        // delete user in ldap
        Tinebase_User::getInstance()->deleteUserInSyncBackend($user->getId());

        // check if still in tine20 db
        $sqlUser = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $user->getId());
        $this->assertEquals($user->getId(), $sqlUser->getId());

        // set sync config/option + start user sync
        $syncOptions = array('deleteUsers' => true);
        Tinebase_User::syncUsers($syncOptions);

        $now = Tinebase_DateTime::now();
        $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $user->getId(), 'Tinebase_Model_FullUser');
        $this->assertTrue($now->isLaterOrEquals($user->accountExpires), 'user should be expired');

        sleep(1);
        Tinebase_User::syncUsers($syncOptions);
        $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $user->getId(), 'Tinebase_Model_FullUser');
        $this->assertEquals($now->toString(), $user->accountExpires->toString(), 'expiry date should still be the same');

        // set expired to -1 year -> user should be deleted
        $user->accountExpires = $now->subYear(1);
        Tinebase_User::getInstance()->updateUserInSqlBackend($user);

        // sync again
        sleep(1);
        Tinebase_User::syncUsers($syncOptions);

        // check if user is deleted in tine, too
        try {
            Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $user->getId());
            $this->fail('user should be deleted from tine20 db');
        } catch (Tinebase_Exception_NotFound $tenf) {
            $this->assertContains('User with accountId = ' . $sqlUser->getId(), $tenf->getMessage());
        }

        // check if user contact is deleted, too
        try {
            Addressbook_Controller_Contact::getInstance()->get($sqlUser->contact_id);
            $this->fail('user contact should be deleted from tine20 db');
        } catch (Tinebase_Exception_NotFound $tenf) {
            $this->assertContains('Addressbook_Model_Contact record with id = ' . $sqlUser->contact_id, $tenf->getMessage());
        }
    }

    /**
     * test user status sync tine <-> ldap
     *
     * @see 0011554: improve ldap account status handling
     */
    public function testSyncUserStatus()
    {
        $user = $this->testAddUser();

        // set user status in tine (disabled, expired, enabled)
        $statusToTest = array(
            Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED,
            Tinebase_Model_User::ACCOUNT_STATUS_DISABLED,
            Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED,
            Tinebase_Model_User::ACCOUNT_STATUS_ENABLED,
        );

        foreach($statusToTest as $status) {
            Tinebase_User::getInstance()->setStatus($user, $status);

            // sync user -> user status should be the same
            $syncedUser = Tinebase_User::syncUser($user, array('syncAccountStatus' => true));
            $this->assertEquals($status, $syncedUser->accountStatus, print_r($syncedUser->toArray(), true));
        }
    }
}
