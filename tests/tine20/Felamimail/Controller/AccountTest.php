<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Felamimail_Controller_Account
 */
class Felamimail_Controller_AccountTest extends PHPUnit_Framework_TestCase
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
     * user pw has changed
     * 
     * @var boolean
     */
    protected $_passwordChanged = FALSE;
    
    /**
     * folders to delete in tearDown
     * 
     * @var array
     */
    protected $_foldersToDelete = array();
    
    /**
     * accounts to delete in tearDown
     * 
     * @var array
     */
    protected $_accountsToDelete = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Felamimail Account Controller Tests');
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
        // reset old account settings
        $accountBackend = new Felamimail_Backend_Account();
        $accountBackend->update($this->_account);
        
        foreach ($this->_foldersToDelete as $foldername) {
            try {
                Felamimail_Controller_Folder::getInstance()->delete($this->_account->getId(), $foldername);
            } catch (Felamimail_Exception_IMAP $fei) {
                // do nothing
            }
        }
        
        if ($this->_passwordChanged) {
            // reset password
            $testConfig = Zend_Registry::get('testConfig');
            $this->_setCredentials($testConfig->username, $testConfig->password);
        }
        
        foreach ($this->_accountsToDelete as $account) {
            Felamimail_Controller_Account::getInstance()->delete($account);
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
        
        $oldCredentialCache = Tinebase_Core::get(Tinebase_Core::USERCREDENTIALCACHE);
        
        // update credential cache
        $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($_username, $_password);
        Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);
        $event = new Tinebase_Event_User_ChangeCredentialCache($oldCredentialCache);
        Tinebase_Event::fireEvent($event);
    }

    /**
     * check if default account pref is set
     */
    public function testDefaultAccountPreference()
    {
        $this->assertEquals($this->_account->getId(), Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT});
        
        $userAccount = clone($this->_account);
        unset($userAccount->id);
        $userAccount->type = Felamimail_Model_Account::TYPE_USER;
        $userAccount = $this->_controller->create($userAccount);
        $this->_accountsToDelete[] = $userAccount;

        // deleting original account and check if user account is new default account
        $this->_controller->delete($this->_account->getId());
        $this->assertEquals($userAccount->getId(), Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT});
        
        $userAccount = $this->_controller->create($this->_account);
    }
    
    /**
     * test account capabilities
     */
    public function testGetAccountCapabilities()
    {
        $account = $this->_controller->updateCapabilities($this->_account);
        $accountToString = print_r($this->_account->toArray(), TRUE);
        
        $this->assertEquals('', $account->ns_personal, $accountToString);
        $this->assertEquals(1, preg_match('@/|\.@', $account->delimiter), $accountToString);
        
        // @todo need to check first, which email server we have
        //$this->assertEquals('#Users', $account->ns_other, $accountToString);
        //$this->assertEquals('#Public', $account->ns_shared, $accountToString);
    }
    
    /**
     * check for sent/trash folders and create them if they do not exist / begin with filled folder cache
     */
    public function testCheckSystemFolderFolders()
    {
        // make sure, folder cache is filled
        Felamimail_Controller_Folder::getInstance()->search(new Felamimail_Model_FolderFilter(array(
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())
        )));
        
        $this->_sentTrashCheckHelper();
    }
    
    /**
     * helper fn for testCheckSystemFolderFolders
     */
    protected function _sentTrashCheckHelper()
    {
        $account = clone($this->_account);
        $account->sent_folder = 'INBOX' . $account->delimiter . 'testsent';
        $account->trash_folder = 'INBOX' . $account->delimiter . 'testtrash';
        $this->_foldersToDelete = array($account->sent_folder, $account->trash_folder);
        
        $accountBackend = new Felamimail_Backend_Account();
        $account = $accountBackend->update($account);
        $this->_controller->checkSystemFolders($account);
        
        $inboxSubfolders = Felamimail_Controller_Folder::getInstance()->search(new Felamimail_Model_FolderFilter(array(
            array('field' => 'globalname', 'operator' => 'equals', 'value' => 'INBOX'),
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())
        )));
        
        $folderFoundCount = 0;
        foreach ($inboxSubfolders as $folder) {
            if ($folder->globalname == $account->sent_folder || $folder->globalname == $account->trash_folder) {
                $folderFoundCount++;
            }
        }
        
        $this->assertEquals(2, $folderFoundCount, 'sent/trash folders not found: ' . print_r($inboxSubfolders->globalname, TRUE));
    }

    /**
     * check for sent/trash folder defaults
     */
    public function testCheckSystemFolderFolderDefaults()
    {
        // make sure, folder cache is filled
        Felamimail_Controller_Folder::getInstance()->search(new Felamimail_Model_FolderFilter(array(
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())
        )));
        
        $account = clone($this->_account);
        $account->sent_folder = '';
        $account->trash_folder = '';
        
        $accountBackend = new Felamimail_Backend_Account();
        $account = $accountBackend->update($account);
        $this->_controller->checkSystemFolders($account);
        
        $updatedAccount = $this->_controller->get($account->getId());
        
        $this->assertEquals('Trash', $updatedAccount->trash_folder);
        $this->assertEquals('Sent', $updatedAccount->sent_folder);
    }
    
    /**
     * test change pw + credential cache
     */
    public function testChangePasswordAndUpdateCredentialCache()
    {
        $testConfig = Zend_Registry::get('testConfig');
        
        $account = clone($this->_account);
        unset($account->id);
        $account->type = Felamimail_Model_Account::TYPE_USER;
        $account->user = $testConfig->username;
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        if (isset($imapConfig['domain']) && ! empty($imapConfig['domain'])) {
            $account->user .= '@' . $imapConfig['domain'];
        }
        $account->password = $testConfig->password;
        $account = $this->_controller->create($account);
        $this->_accountsToDelete[] = $account;
        
        $testPw = 'testpwd';
        
        // change pw & update credential cache
        $this->_passwordChanged = TRUE;
        $this->_setCredentials($testConfig->username, $testPw);
        $account = $this->_controller->get($account->getId());

        // try to connect to imap
        $loginSuccessful = TRUE;
        try {
            $imap = Felamimail_Backend_ImapFactory::factory($account);
            $imapAccountConfig = $account->getImapConfig();
            $imap->connectAndLogin((object)$imapAccountConfig);
        } catch (Felamimail_Exception_IMAPInvalidCredentials $e) {
            $loginSuccessful = FALSE;
        }
        
        $this->assertTrue($loginSuccessful, 'wrong credentials');
    }
}
