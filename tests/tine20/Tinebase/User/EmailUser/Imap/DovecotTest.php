<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_DovecotTest
 */
class Tinebase_User_EmailUser_Imap_DovecotTest extends PHPUnit_Framework_TestCase
{
    /**
     * email user backend
     *
     * @var Tinebase_User_Plugin_Abstract
     */
    protected $_backend = null;

    /**
     * @var array test objects
     */
    protected $_objects = array();

    /**
     * @var array config
     */
    protected $_config;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_config = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP,
            new Tinebase_Config_Struct())->toArray();
        if (!isset($this->_config['backend']) || !('Imap_' . ucfirst($this->_config['backend']) == Tinebase_EmailUser::IMAP_DOVECOT) || $this->_config['active'] != true) {
            $this->markTestSkipped('Dovecot MySQL backend not configured or not enabled');
        }

        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            // error: Zend_Ldap_Exception: 0x44 (Already exists; 00002071: samldb: Account name (sAMAccountName)
            // 'tine20phpunituser' already in use!): adding: cn=PHPUnit User Tine 2.0,cn=Users,dc=example,dc=org
            $this->markTestSkipped('skipped for ad backends as it does not allow duplicate CNs');
        }

        $this->_backend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);

        $personas = Zend_Registry::get('personas');
        $this->_objects['user'] = clone $personas['jsmith'];
        //$this->_objects['user']->setId(Tinebase_Record_Abstract::generateUID());

        $this->_objects['addedUsers'] = array();
        $this->_objects['fullUsers'] = array();
        $this->_objects['emailUserIds'] = array();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // delete email account
        foreach ($this->_objects['addedUsers'] as $user) {
            $this->_backend->inspectDeleteUser($user);
        }

        foreach ($this->_objects['fullUsers'] as $user) {
            Tinebase_User::getInstance()->deleteUser($user);
        }

        // also remove remaining stuff from dovecot table - mail accounts no longer linked to a tine user account
        foreach ($this->_objects['emailUserIds'] as $userId) {
            $this->_backend->deleteUserById($userId);
        }

        // also delete from smtp
        $smtpBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
        foreach ($this->_objects['emailUserIds'] as $userId) {
            $smtpBackend->deleteUserById($userId);
        }
    }

    /**
     * try to add an email account
     */
    public function testAddEmailAccount()
    {
        $emailUser = clone $this->_objects['user'];
        $emailUser->imapUser = new Tinebase_Model_EmailUser(array(
            'emailPassword' => Tinebase_Record_Abstract::generateUID(),
            'emailUID' => '1000',
            'emailGID' => '1000'
        ));

        $this->_backend->inspectAddUser($this->_objects['user'], $emailUser);
        $this->_objects['addedUsers']['emailUser'] = $this->_objects['user'];

        $this->_assertImapUser();
        return $this->_objects['user'];
    }

    /**
     * try to update an email account
     */
    public function testUpdateAccount()
    {
        // add smtp user
        $user = $this->testAddEmailAccount();

        // update user
        $user->imapUser->emailMailQuota = 600 * 1024 * 1024;

        $this->_backend->inspectUpdateUser($this->_objects['user'], $user);
        $this->_assertImapUser(array('emailMailQuota' => 600  * 1024 * 1024));
    }

    /**
     * asserts that imapUser object contains the correct data
     *
     * @param array $additionalExpectations
     */
    protected function _assertImapUser($additionalExpectations = array())
    {
        $this->assertEquals(array_merge(array(
            'emailUserId' => $this->_objects['user']->getId(),
            'emailUsername' => $this->_objects['user']->imapUser->emailUsername,
            'emailMailQuota' => 2097152000,
            'emailUID' => !empty($this->_config['dovecot']['uid']) ? $this->_config['dovecot']['uid'] : '1000',
            'emailGID' => !empty($this->_config['dovecot']['gid']) ? $this->_config['dovecot']['gid'] : '1000',
            'emailLastLogin' => null,
            'emailMailSize' => 0,
            'emailSieveSize' => null,
            'emailPort' => $this->_config['port'],
            'emailSecure' => $this->_config['ssl'],
            'emailHost' => $this->_config['host']
        ), $additionalExpectations), $this->_objects['user']->imapUser->toArray());
    }

    /**
     * testSavingDuplicateAccount
     *
     * @see 0006546: saving user with duplicate imap/smtp user entry fails
     */
    public function testSavingDuplicateAccount()
    {
        $user = $this->_addUser();
        $userId = $user->getId();
        $this->_objects['emailUserIds'][] = $userId;

        // delete user in tine accounts table
        $userBackend = new Tinebase_User_Sql();
        $userBackend->deleteUserInSqlBackend($userId);

        // create user again
        unset($user->accountId);
        $newUser = Tinebase_User::getInstance()->addUser($user);
        $this->_objects['fullUsers'] = array($newUser);

        $this->assertNotEquals($userId, $newUser->getId());
        $this->assertTrue(isset($newUser->imapUser), 'imapUser data not found: ' . print_r($newUser->toArray(), true));
    }

    /**
     * add user with email data
     *
     * @param string $username
     * @return Tinebase_Model_FullUser
     */
    protected function _addUser($username = null)
    {
        $user = TestCase::getTestUser();
        if ($username) {
            $user->accountLoginName = $username;
        }
        $user->imapUser = new Tinebase_Model_EmailUser(array(
            'emailPassword' => Tinebase_Record_Abstract::generateUID(),
            'emailUID' => '1000',
            'emailGID' => '1000'
        ));
        $user = Tinebase_User::getInstance()->addUser($user);
        $this->_objects['fullUsers'] = array($user);

        return $user;
    }

    /**
     * try to set password
     */
    public function testSetPassword()
    {
        $user = $this->testAddEmailAccount();

        $newPassword = Tinebase_Record_Abstract::generateUID();
        $this->_backend->inspectSetPassword($this->_objects['user']->getId(), $newPassword);

        // fetch email pw from db
        $dovecot = Tinebase_User::getInstance()->getSqlPlugin(Tinebase_EmailUser_Imap_Dovecot::class);
        $rawDovecotUser = $dovecot->getRawUserById($user);
        $hashPw = new Hash_Password();
        $this->assertTrue($hashPw->validate($rawDovecotUser['password'], $newPassword), 'password mismatch');
    }

    public function testSetLoginName()
    {
        // fetch dovecot user from db
        $dovecot = Tinebase_User::getInstance()->getSqlPlugin(Tinebase_EmailUser_Imap_Dovecot::class);
        $rawDovecotUser = $dovecot->getRawUserById(Tinebase_Core::getUser());
        self::assertTrue(isset($rawDovecotUser['loginname']), 'loginname property not found ' . print_r($rawDovecotUser, true));
        self::assertEquals(Tinebase_Core::getUser()->accountEmailAddress, $rawDovecotUser['loginname']);
    }

    /**
     * testDuplicateUserId
     *
     * @see 0007218: Duplicate userid in dovecot_users
     */
    public function testDuplicateUserId()
    {
        $emailDomain = TestServer::getPrimaryMailDomain();
        $user = $this->_addUser('testuser@' . $emailDomain);

        // update user login name
        $user->accountLoginName = 'testuser';
        $user = Tinebase_User::getInstance()->updateUser($user);

        $dovecot = Tinebase_User::getInstance()->getSqlPlugin(Tinebase_EmailUser_Imap_Dovecot::class);
        $rawDovecotUser = $dovecot->getRawUserById($user);
        self::assertNotNull($rawDovecotUser['username'], 'username missing: ' . print_r($rawDovecotUser, true));
        self::assertEquals($user->getId() . '@' . $this->_config['instanceName'], $rawDovecotUser['username'],
            'username has not been updated in dovecot user table ' . print_r($rawDovecotUser, true));
    }

    /**
     * testInstanceName
     *
     * @see 0013326: use userid@instancename and for email account name
     *
     * ALTER TABLE `dovecot_users` ADD `instancename` VARCHAR(80) NULL AFTER `username`, ADD INDEX `instancename` (`instancename`);
     */
    public function testInstanceName()
    {
        // check if is instanceName in config
        if (empty($this->_config['instanceName'])) {
            self::markTestSkipped('no instanceName set in config');
        }

        $user = $this->_addUser();

        // check email tables (username + instancename)
        $dovecot = Tinebase_User::getInstance()->getSqlPlugin(Tinebase_EmailUser_Imap_Dovecot::class);
        $rawDovecotUser = $dovecot->getRawUserById($user);

        self::assertTrue(is_array($rawDovecotUser), 'did not fetch dovecotuser: ' . print_r($rawDovecotUser, true));
        self::assertEquals($user->getId() . '@' . $this->_config['instanceName'], $rawDovecotUser['username']);
        self::assertTrue(isset($rawDovecotUser['instancename']), 'instancename missing: ' . print_r($rawDovecotUser, true));
        self::assertEquals($this->_config['instanceName'], $rawDovecotUser['instancename']);
    }
}
