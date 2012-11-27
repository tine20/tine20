<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
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
     * folders to delete in tearDown
     * 
     * @var array
     */
    protected $_foldersToDelete = array();
    
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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
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
        
        Tinebase_TransactionManager::getInstance()->rollBack();
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
        $this->assertEquals($this->_account->getId(), Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}, 'current account is not the default account');
        
        $userAccount = clone($this->_account);
        unset($userAccount->id);
        $userAccount->type = Felamimail_Model_Account::TYPE_USER;
        $userAccount = $this->_controller->create($userAccount);

        // deleting original account and check if user account is new default account
        $this->_controller->delete($this->_account->getId());
        $this->assertEquals($userAccount->getId(), Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}, 'other account is not default account');
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
        
        $this->assertTrue(in_array('IMAP4', $capabilities['capabilities']) || in_array('IMAP4rev1', $capabilities['capabilities']), 
            'no IMAP4(rev1) capability found in ' . print_r($capabilities['capabilities'], TRUE));
        $this->assertTrue(in_array('QUOTA', $capabilities['capabilities']), 'no QUOTA capability found in ' . print_r($capabilities['capabilities'], TRUE));
        
        $this->assertEquals($capabilities, array_value($this->_account->getId(), Tinebase_Core::getSession()->Felamimail));
    }

    /**
     * test reset account capabilities
     */
    public function testResetAccountCapabilities()
    {
        $capabilities = $this->_controller->updateCapabilities($this->_account);
        
        $account = clone($this->_account);
        $account->host = 'unittest.org';
        $account->type = Felamimail_Model_Account::TYPE_USER;
        $this->_controller->update($account);
        
        $this->assertFalse(array_key_exists($this->_account->getId(), Tinebase_Core::getSession()->Felamimail), print_r(Tinebase_Core::getSession()->Felamimail, TRUE));
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
        $testConfig = Zend_Registry::get('testConfig');
        
        $account = clone($this->_account);
        unset($account->id);
        $account->type = Felamimail_Model_Account::TYPE_USER;
        $account->user = $testConfig->username;
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        if (isset($imapConfig['domain']) && ! empty($imapConfig['domain'])) {
            $account->user .= '@' . $imapConfig['domain'];
        }
        $account->password = $testConfig->password;
        $account = $this->_controller->create($account);
        
        $testPw = 'testpwd';
        
        // change pw & update credential cache
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
}
