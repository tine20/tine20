<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Felamimail_Controller_Account
 */
class Felamimail_Controller_AccountTest extends Felamimail_TestCase
{
    /**
     * @var Felamimail_Controller_Account
     */
    protected $_controller = null;

    /**
     * was the pw changed during tests? if yes, revert.
     *
     * @var boolean
     */
    protected $_pwChanged = false;

    protected $_oldConfig = null;

    protected $_userChanged = false;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_controller = Felamimail_Controller_Account::getInstance();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        if ($this->_userChanged) {
            $originalUser = Admin_Controller_User::getInstance()->update($this->_originalTestUser);
            Tinebase_Core::setUser($originalUser);
        }

        parent::tearDown();

        if ($this->_pwChanged) {
            $testCredentials = TestServer::getInstance()->getTestCredentials();
            $this->_setCredentials($testCredentials['username'], $testCredentials['password']);
        }

        if ($this->_oldConfig) {
            Tinebase_Config::getInstance()->set(Tinebase_Config::IMAP, $this->_oldConfig);
        }
    }

    /**
     * set new password & credentials
     *
     * @param string $_username
     * @param string $_password
     */
    protected function _setCredentials($_username, $_password)
    {
        Tinebase_User::getInstance()->setPassword(Tinebase_Core::getUser(), $_password, true, false);

        $oldCredentialCache = Tinebase_Core::getUserCredentialCache();

        // update credential cache
        $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($_username, $_password);
        Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);
        $event = new Tinebase_Event_User_ChangeCredentialCache($oldCredentialCache);
        Tinebase_Event::fireEvent($event);

        $this->_pwChanged = true;
    }

    /**
     * check if default account pref is set
     */
    public function testDefaultAccountPreference()
    {
        $this->assertEquals($this->_account->getId(), Tinebase_Core::getPreference(
            'Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}, 'current account is not the default account');

        $userAccount = clone($this->_account);
        unset($userAccount->id);
        $userAccount->type = Felamimail_Model_Account::TYPE_USER;
        $userAccount = $this->_controller->create($userAccount);

        // deleting original account and check if user account is new default account
        $this->_controller->delete($this->_account->getId());
        $this->assertEquals($userAccount->getId(), Tinebase_Core::getPreference(
            'Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}, 'other account is not default account');
    }

    /**
     * test account capabilities
     */
    public function testGetAccountCapabilities()
    {
        $capabilities = $this->_controller->updateCapabilities($this->_account);
        $account = $this->_controller->get($this->_account);
        $accountToString = print_r($this->_account->toArray(), TRUE);

        $this->assertEquals('', $account->ns_personal, $accountToString);
        $this->assertEquals(1, preg_match('@/|\.@', $account->delimiter), $accountToString);

        $this->assertTrue(in_array('IMAP4', $capabilities['capabilities'])
            || in_array('IMAP4rev1', $capabilities['capabilities']),
            'no IMAP4(rev1) capability found in ' . print_r($capabilities['capabilities'], TRUE));

        $this->assertTrue(in_array('QUOTA', $capabilities['capabilities']), 'no QUOTA capability found in '
            . print_r($capabilities['capabilities'], TRUE));
        $cacheId = Tinebase_Helper::convertCacheId(
            Felamimail_Controller_Account::ACCOUNT_CAPABILITIES_CACHEID . '_' . $account->getId());
        $this->assertTrue(Tinebase_Core::getCache()->test($cacheId) == true);
    }

    /**
     * test reset account capabilities
     */
    public function testResetAccountCapabilities()
    {
        $account = clone($this->_account);
        $account->type = Felamimail_Model_Account::TYPE_USER;
        $account->setId(null);
        $account = $this->_controller->create($account);

        $account->host = 'unittest.org';
        $this->_controller->update($account);

        $cacheId = Tinebase_Helper::convertCacheId(
            Felamimail_Controller_Account::ACCOUNT_CAPABILITIES_CACHEID . '_' . $account->getId());
        $this->assertFalse(Tinebase_Core::getCache()->test($cacheId));
    }

    /**
     * test create trash on the fly
     */
    public function testCreateTrashOnTheFly()
    {
        // make sure that the delimiter is correct / fetched from server
        $this->_controller->updateCapabilities($this->_account);

        // set another trash folder
        $this->_account->trash_folder = 'newtrash';
        $this->_foldersToDelete[] = 'newtrash';
        $accountBackend = new Felamimail_Backend_Account();
        $account = $accountBackend->update($this->_account);
        $newtrash = $this->_controller->getSystemFolder($account, Felamimail_Model_Folder::FOLDER_TRASH);
        self::assertNotNull($newtrash);
    }

    /**
     * test change pw + credential cache
     */
    public function testChangePasswordAndUpdateCredentialCache()
    {
        $this->markTestSkipped('FIXME 0009250: fix test testChangePasswordAndUpdateCredentialCache');
        $testCredentials = TestServer::getInstance()->getTestCredentials();

        $account = clone($this->_account);
        unset($account->id);
        $account->type = Felamimail_Model_Account::TYPE_USER;
        $account->user = $testCredentials['username'];
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        if (isset($imapConfig['domain']) && !empty($imapConfig['domain'])) {
            $account->user .= '@' . $imapConfig['domain'];
        }
        $account->password = $testCredentials['password'];
        $account = $this->_controller->create($account);

        $testPw = 'testpwd';

        // change pw & update credential cache
        $this->_setCredentials($testCredentials['username'], $testPw);
        $account = $this->_controller->get($account->getId());

        // try to connect to imap
        $loginSuccessful = TRUE;
        try {
            $imap = Felamimail_Backend_ImapFactory::factory($account);
        } catch (Felamimail_Exception_IMAPInvalidCredentials $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' config: ' . print_r($imapAccountConfig, true) . ' / exception:' . $e);
            $loginSuccessful = FALSE;
        }

        $this->assertTrue($loginSuccessful, 'wrong credentials');
    }

    /**
     * testEmptySignature
     *
     * @see 0006666: Signature delimeter not removed if no Signature is used
     */
    public function testEmptySignature()
    {
        $this->_account->signature = '<html><body><div><br /></div><p>   </p>&nbsp;<br /></body></html>';
        $account = $this->_controller->update($this->_account);

        $this->assertEquals('', $account->signature, 'did not save empty signature');
    }

    /**
     * @see 0011810: credential cache decode fails sometimes
     */
    public function testSaveAccountAndCredentialCache()
    {
        $account = new Felamimail_Model_Account(array(
            'from' => 'Admin Account, Tine 2.0',
            'port' => '143',
            'smtp_port' => '25',
            'smtp_ssl' => 'none',
            'sieve_port' => '2000',
            'sieve_ssl' => 'none',
            'signature' => 'Sent with love from the Tine 2.0 email client ...<br>Please visit <a href="http://www.tine20.com">http://www.tine20.com</a>',
            'sent_folder' => 'Sent',
            'trash_folder' => 'Trash',
            'name' => 'test',
            'user' => 'abcde@tine20.org',
            'host' => 'mail.abc.de',
            'email' => 'abcde@tine20.org',
            'password' => 'abcde',
            'organization' => '',
            'ssl' => 'none',
            'display_format' => 'html',
            'signature_position' => 'below',
            'smtp_auth' => 'login',
        ));

        $savedAccount = $this->_controller->create($account);

        $savedAccount->resolveCredentials();
        $this->assertEquals('abcde@tine20.org', $savedAccount->user);
        $this->assertEmpty($savedAccount->password);

        $savedAccount->resolveCredentials(false);
        static::assertSame('abcde', $savedAccount->password);
    }

    /**
     * testUseEmailAsLoginName without dovecot imap user backend
     *
     * @see 0012404: useEmailAsUsername IMAP config option not working for standard system accounts
     */
    public function testUseEmailAsLoginName()
    {
        $raii = new Tinebase_RAII(function () {
            Tinebase_EmailUser::clearCaches();
        });

        // change config to standard imap backend
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
        $this->_oldConfig = clone $imapConfig;
        $imapConfig->backend = 'Standard'; //Tinebase_EmailUser::IMAP_STANDARD;
        $imapConfig->domain = '';
        $imapConfig->instanceName = '';
        $imapConfig->useEmailAsUsername = true;
        Tinebase_Config::getInstance()->set(Tinebase_Config::IMAP, $imapConfig);
        Tinebase_EmailUser::clearCaches();

        Felamimail_Controller_Account::getInstance()->delete(array($this->_account->getId()));

        $credentialsBackend = Tinebase_Auth_CredentialCache::getInstance();

        $credentials = Tinebase_Core::getUserCredentialCache();
        $credentialsBackend->getCachedCredentials($credentials);
        // $credentialCachePwd = substr($credentials->password, 0, 24); ?!?

        $this->_account = $this->_controller->addSystemAccount(Tinebase_Core::getUser(), $credentials->password);
        // make sure the user is resolved again
        unset($this->_account->user);
        $this->_account->resolveCredentials();
        $this->assertEquals(Tinebase_Core::getUser()->accountEmailAddress, $this->_account->user);

        // for unused variable check only
        unset($raii);
    }

    /**
     * test if email address and name of system account changes if user email is updated
     */
    public function testChangeSystemAccountEmailAddress()
    {
        $user = $this->_createUserWithEmailAccount();
        $user->accountEmailAddress = 'someaddress@' . TestServer::getPrimaryMailDomain();

        Admin_Controller_User::getInstance()->update($user);
        $account = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($user);

        self::assertEquals($user->accountEmailAddress, $account->name,
            'name mismatch: ' . print_r($account->toArray(), true));
        self::assertEquals($user->accountEmailAddress, $account->email,
            'email mismatch: ' . print_r($account->toArray(), true));
    }

    public function testSearchMailsInSharedAccount()
    {
        $this->_testNeedsTransaction();

        $account = $this->_createSharedAccount();
        // write mail to shared account
        $message = $this->_sendAndAssertMail([$account->email], $account);

        // try to access the message
        $messageViaGet = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message['id']);
        self::assertContains('aaaaaä', $messageViaGet->body);
    }

    public function testUpdateSharedAccount()
    {
        // change email address and check if email user is updated, too
        $this->_testNeedsTransaction();
        $account = $this->_createSharedAccount();
        $account->email = 'shared' . Tinebase_Record_Abstract::generateUID(10) . '@' . TestServer::getPrimaryMailDomain();
        Felamimail_Controller_Account::getInstance()->update($account);
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($account);
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
        $userInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertEquals($account->email, $userInBackend['email'], 'email was not updated');
    }

    public function testDeleteSharedAccount()
    {
        $this->_testNeedsTransaction();
        $account = $this->_createSharedAccount();
        Felamimail_Controller_Account::getInstance()->delete($account->getId());
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($account);
        // make sure email user is deleted, too
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $userInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertFalse($userInBackend, 'user should be deleted from backend');
    }

    public function testChangeAccountFromByUserUpdate()
    {
        $user = $this->_createUserWithEmailAccount();

        // change name of user
        $user->accountLastName = 'lala';
        $updatedUser = Admin_Controller_User::getInstance()->update($user);

        // from of system account should change
        $account = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($user);
        self::assertEquals($updatedUser->accountFullName, $account->from);
    }

    public function testChangeAccountNameByUserUpdate()
    {
        // change name of user
        $user = $this->_createUserWithEmailAccount();
        $oldMail = $user->accountEmailAddress;
        $user->accountEmailAddress = 'shaaclewver@' . TestServer::getPrimaryMailDomain();
        $updatedUser = Admin_Controller_User::getInstance()->update($user);

        // name of system account should change
        Felamimail_Controller_Account::getInstance()->doContainerACLChecks(false);
        $account = Felamimail_Controller_Account::getInstance()->getSystemAccount($user);
        self::assertEquals($updatedUser->accountEmailAddress, $account->name);

        // change account name (via admin controller) - email change should not change name
        $account->name = 'my custom name';
        $updatedAccount = Admin_Controller_EmailAccount::getInstance()->update($account);
        $updatedUser->accountEmailAddress = $oldMail;
        Admin_Controller_User::getInstance()->update($updatedUser);
        $account = Felamimail_Controller_Account::getInstance()->get($account->getId());
        self::assertEquals($updatedAccount->name, $account->name);
        Felamimail_Controller_Account::getInstance()->doContainerACLChecks(true);
    }

    public function testSharedAccountAcl()
    {
        $account = $this->_createSharedAccount();

        // switch user and check if user sees the shared account
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        Tinebase_Core::set(Tinebase_Core::USER, $sclever);

        $filter = Felamimail_Controller_Account::getInstance()->getVisibleAccountsFilterForUser();
        $scleverAccounts = Felamimail_Controller_Account::getInstance()->search($filter);
        self::assertFalse($scleverAccounts->getById($account->getId()), 'sclever should not see the shared account!');
    }

    public function testSharedAccountSendGrant()
    {
        $account = $this->_createSharedAccount(false);

        $message = new Felamimail_Model_Message(array(
            'account_id'    => $account->getId(),
            'subject'       => 'xxx',
            'to'            => Tinebase_Core::getUser()->accountEmailAddress,
            'body'          => 'aaaaaä <br>',
        ));

        try {
            Felamimail_Controller_Message_Send::getInstance()->sendMessage($message);
            self::fail('it should not be possible to send a mail without addGrant');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            self::assertEquals('User is not allowed to send a message with this account', $tead->getMessage());
        }
    }

    public function testCreateUserInternalAccount()
    {
        $this->_skipIfXpropsUserIdDeactivated();

        $scleverExtraAccount = $this->_createUserInternalAccount($this->_personas['sclever']);
        $json = new Felamimail_Frontend_Json();
        $result = $json->searchAccounts([]);
        foreach ($result['results'] as $account) {
            if ($scleverExtraAccount->getId() === $account['id']) {
                self::fail('should not find sclevers account! ' . print_r($account, true));
            }
        }

        $extraUserInBackend = self::checkInternalUserAccount($scleverExtraAccount);

        // compare with original email home (should be different!)
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($this->_personas['sclever']);
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $originalUserInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertNotEquals($originalUserInBackend['home'], $extraUserInBackend['home']);
    }

    public static function checkInternalUserAccount(Felamimail_Model_Account $account)
    {
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($account, [
            'user_id' => Felamimail_Controller_Account::getUserInternalEmailUserId($account)
        ]);
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $extraUserInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertNotFalse($extraUserInBackend, 'email user not found');
        self::assertNotEmpty($extraUserInBackend['loginname'], 'loginname empty: '
            . print_r($extraUserInBackend, true));
        self::assertNotEmpty($extraUserInBackend['username'], 'username empty: '
            . print_r($extraUserInBackend, true));
        self::assertContains(TestServer::getPrimaryMailDomain() . '/'
            . $extraUserInBackend['userid'], $extraUserInBackend['home'],
            'home not matching: ' . print_r($extraUserInBackend, true));

        return $extraUserInBackend;
    }

    public function testCreateNewUserAccountWithINBOX()
    {
        $this->_testNeedsTransaction();

        $creds = TestServer::getInstance()->getTestCredentials();
        $user = $this->_createUserWithEmailAccount([
            'password' => $creds['password']
        ]);
        Tinebase_Core::setUser($user);
        $json = new Felamimail_Frontend_Json();
        $result = $json->searchAccounts([]);
        self::assertEquals(1, $result['totalcount']);
        $account = $result['results'][0];
        $folders = $json->searchFolders([
            ['field' => 'account_id', 'operator' => 'equals', 'value' => $account['id']],
            ['field' => 'globalname', 'operator' => 'equals', 'value' => ''],
        ]);
        self::assertEquals(5, $folders['totalcount'], 'should find 5 initial folders. got: '
            . print_r($folders, true));
    }

    public function testConvertAccountsToSaveUserIdInXprops()
    {
        // switch xprops in user off
        Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS} = false;

        $account = $this->_createSharedAccount();

        Felamimail_Controller_Account::getInstance()->convertAccountsToSaveUserIdInXprops();

        $convertedAccount = Felamimail_Controller_Account::getInstance()->get($account->getId());
        self::assertNotEmpty($convertedAccount->xprops[Felamimail_Model_Account::XPROP_EMAIL_USERID_IMAP],
            'XPROP_EMAIL_USERID_IMAP empty ' . print_r($convertedAccount->toArray(), true));
        self::assertNotEmpty($convertedAccount->xprops[Felamimail_Model_Account::XPROP_EMAIL_USERID_SMTP],
            'XPROP_EMAIL_USERID_SMTP empty ' . print_r($convertedAccount->toArray(), true));
    }
}
