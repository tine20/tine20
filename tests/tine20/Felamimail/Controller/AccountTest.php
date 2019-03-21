<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Felamimail_Controller_Account
 */
class Felamimail_Controller_AccountTest extends TestCase
{
    /**
     * @var Felamimail_Controller_Account
     */
    protected $_controller = array();
    
    /**
     * @var Felamimail_Model_Account
     */
    protected $_account = NULL;
    
    /**
     * folders to delete in tearDown
     * 
     * @var array
     */
    protected $_foldersToDelete = array();
    
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
        $this->_account = $this->_controller->search()->getFirstRecord();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->_foldersToDelete as $foldername) {
            try {
                Felamimail_Controller_Folder::getInstance()->delete($this->_account->getId(), $foldername);
            } catch (Felamimail_Exception_IMAP $fei) {
                // do nothing
            }
        }
        
        parent::tearDown();
        
        if ($this->_pwChanged) {
            $testCredentials = TestServer::getInstance()->getTestCredentials();
            $this->_setCredentials($testCredentials['username'], $testCredentials['password']);
        }

        if ($this->_oldConfig) {
            Tinebase_Config::getInstance()->set(Tinebase_Config::IMAP, $this->_oldConfig);
        }

        if ($this->_userChanged) {
            Admin_Controller_User::getInstance()->update($this->_originalTestUser);
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
        $this->assertEquals($this->_account->getId(), Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}, 'current account is not the default account');

        $userAccount = clone($this->_account);
        unset($userAccount->id);
        $userAccount->type = Felamimail_Model_Account::TYPE_USER;
        $userAccount = $this->_controller->create($userAccount);

        // deleting original account and check if user account is new default account
        $this->_controller->delete($this->_account->getId());
        $this->assertEquals($userAccount->getId(), Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}, 'other account is not default account');
        $this->assertNotEmpty($userAccount->host, 'host not set from defaults');
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
            Felamimail_Controller_Account::ACCOUNT_CAPABILITIES_CACHEID. '_' . $account->getId());
        $this->assertTrue(Tinebase_Core::getCache()->test($cacheId) == true);
    }

    /**
     * test reset account capabilities
     */
    public function testResetAccountCapabilities()
    {
        $this->_controller->updateCapabilities($this->_account);
        
        $account = clone($this->_account);
        $account->host = 'unittest.org';
        $account->type = Felamimail_Model_Account::TYPE_USER;
        $this->_controller->update($account);

        $cacheId = Tinebase_Helper::convertCacheId(
            Felamimail_Controller_Account::ACCOUNT_CAPABILITIES_CACHEID. '_' . $account->getId());
        $this->assertFalse(Tinebase_Core::getCache()->test($cacheId));
    }
    
    /**
     * test create trash on the fly
     */
    public function testCreateTrashOnTheFly()
    {
        // make sure that the delimiter is correct / fetched from server
        $capabilities = $this->_controller->updateCapabilities($this->_account);
        
        // set another trash folder
        $this->_account->trash_folder = 'newtrash';
        $this->_foldersToDelete[] = 'newtrash';
        $accountBackend = new Felamimail_Backend_Account();
        $account = $accountBackend->update($this->_account);
        $newtrash = $this->_controller->getSystemFolder($account, Felamimail_Model_Folder::FOLDER_TRASH);
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
        if (isset($imapConfig['domain']) && ! empty($imapConfig['domain'])) {
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
        $this->assertEquals('abcde', $savedAccount->password);
    }

    /**
     * testUseEmailAsLoginName without dovecot imap user backend
     *
     * @see 0012404: useEmailAsUsername IMAP config option not working for standard system accounts
     */
    public function testUseEmailAsLoginName()
    {
        // change config to standard imap backend
        $this->_oldConfig = $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
        $imapConfig->backend = Tinebase_EmailUser::IMAP_STANDARD;
        $imapConfig->domain = '';
        $imapConfig->instanceName = '';
        $imapConfig->useEmailAsUsername = true;
        Tinebase_Config::getInstance()->set(Tinebase_Config::IMAP, $imapConfig);

        Felamimail_Controller_Account::getInstance()->delete(array($this->_account->getId()));
        $this->_account = $this->_controller->search()->getFirstRecord();
        // make sure the user is resolved again
        unset($this->_account->user);
        $this->_account->resolveCredentials();
        $this->assertEquals(Tinebase_Core::getUser()->accountEmailAddress, $this->_account->user);
    }

    /**
     * test if email address and name of system account changes if user email is updated
     */
    public function testChangeSystemAccountEmailAddress()
    {
        $user = Admin_Controller_User::getInstance()->get(Tinebase_Core::getUser()->getId());
        $user->accountEmailAddress = 'someaddress@' . TestServer::getPrimaryMailDomain();
        // TODO find out why we lose the right sometimes ...
        try {
            Admin_Controller_User::getInstance()->update($user);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            self::markTestSkipped('FIXME: somehow we lost the view/manage accounts right ...');
        }
        $this->_userChanged = true;
        $account = $this->_controller->search()->getFirstRecord();

        self::assertEquals($user->accountEmailAddress, $account->name,
            'name mismatch: ' . print_r($account->toArray(), true));
        self::assertEquals($user->accountEmailAddress, $account->email,
            'email mismatch: ' . print_r($account->toArray(), true));
    }
}
