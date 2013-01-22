<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
class Felamimail_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Felamimail_Frontend_Json
     */
    protected $_json = array();

    /**
     * message ids to delete
     *
     * @var array
     */
    protected $_messageIds = array();
    
    /**
     * @var Felamimail_Model_Account
     */
    protected $_account = NULL;
    
    /**
     * imap backend

     * @var Felamimail_Backend_ImapProxy
     */
    protected $_imap = NULL;
    
    /**
     * name of the folder to use for tests
     * @var string
     */
    protected $_testFolderName = 'Junk';
    
    /**
     * folders to delete in tearDown()
     * 
     * @var array
     */
    protected $_createdFolders = array();
    
    /**
     * are there messages to delete?
     * 
     * @var array
     */
    protected $_foldersToClear = array();

    /**
     * active sieve script name to be restored
     * 
     * @var string
     */
    protected $_oldActiveSieveScriptName = NULL;

    /**
     * was sieve_vacation_active ?
     * 
     * @var boolean
     */
    protected $_oldSieveVacationActiveState = FALSE;
    
    /**
     * old sieve data
     * 
     * @var Felamimail_Sieve_Backend_Sql
     */
    protected $_oldSieveData = NULL;

    /**
     * sieve script name to delete
     * 
     * @var string
     */
    protected $_testSieveScriptName = NULL;

    /**
     * sieve vacation template file name
     * 
     * @var string
     */
    protected $_sieveVacationTemplateFile = 'vacation_template.tpl';
    
    /**
     * test email domain
     * 
     * @var string
     */
    protected $_mailDomain = 'tine20.org';
    
    /**
     * @var Felamimail_Model_Folder
     */
    protected $_folder = NULL;
    
    /**
     * paths in the vfs to delete
     * 
     * @var array
     */
    protected $_pathsToDelete = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Felamimail Json Tests');
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
        // get (or create) test accout
        $this->_account = Felamimail_Controller_Account::getInstance()->search()->getFirstRecord();
        $this->_oldSieveVacationActiveState = $this->_account->sieve_vacation_active;
        try {
            $this->_oldSieveData = new Felamimail_Sieve_Backend_Sql($this->_account);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing
        }
        
        $this->_json = new Felamimail_Frontend_Json();
        $this->_imap = Felamimail_Backend_ImapFactory::factory($this->_account);
        
        foreach (array($this->_testFolderName, $this->_account->sent_folder, $this->_account->trash_folder) as $folderToCreate) {
            // create folder if it does not exist
            $this->_getFolder($folderToCreate);
        }
        
        $config = TestServer::getInstance()->getConfig();
        $this->_mailDomain = ($config->maildomain) ? $config->maildomain : 'tine20.org';
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        if (count($this->_createdFolders) > 0) {
            foreach ($this->_createdFolders as $folderName) {
                //echo "delete $folderName\n";
                try {
                    $this->_imap->removeFolder(Felamimail_Model_Folder::encodeFolderName($folderName));
                } catch (Zend_Mail_Storage_Exception $zmse) {
                    // already deleted
                }
            }
            Felamimail_Controller_Cache_Folder::getInstance()->clear($this->_account);
        }
        
        if (! empty($this->_foldersToClear)) {
            foreach ($this->_foldersToClear as $folderName) {
                // delete test messages from given folders on imap server (search by special header)
                $this->_imap->selectFolder($folderName);
                $result = $this->_imap->search(array(
                    'HEADER X-Tine20TestMessage jsontest'
                ));
                //print_r($result);
                foreach ($result as $messageUid) {
                    $this->_imap->removeMessage($messageUid);
                }
                
                // clear message cache
                $folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($this->_account->getId(), $folderName);
                Felamimail_Controller_Cache_Message::getInstance()->clear($folder);
            }
        }
        
        // sieve cleanup
        if ($this->_testSieveScriptName !== NULL) {
            Felamimail_Controller_Sieve::getInstance()->setScriptName($this->_testSieveScriptName);
            try {
                Felamimail_Controller_Sieve::getInstance()->deleteScript($this->_account->getId());
            } catch (Zend_Mail_Protocol_Exception $zmpe) {
                // do not delete script if active
            }
            Felamimail_Controller_Account::getInstance()->setVacationActive($this->_account, $this->_oldSieveVacationActiveState);
            
            if ($this->_oldSieveData !== NULL) {
                $this->_oldSieveData->save();
            }
        }
        if ($this->_oldActiveSieveScriptName !== NULL) {
            Felamimail_Controller_Sieve::getInstance()->setScriptName($this->_oldActiveSieveScriptName);
            Felamimail_Controller_Sieve::getInstance()->activateScript($this->_account->getId());
        }
        
        // vfs cleanup
        foreach ($this->_pathsToDelete as $path) {
            $webdavRoot = new Sabre_DAV_ObjectTree(new Tinebase_WebDav_Root());
            //echo "delete $path";
            $webdavRoot->delete($path);
        }
    }

    /************************ test functions *********************************/
    
    /*********************** folder tests ****************************/
    
    /**
     * test search folders (check order of folders as well)
     */
    public function testSearchFolders()
    {
        $filter = $this->_getFolderFilter();
        $result = $this->_json->searchFolders($filter);
        
        $this->assertGreaterThan(1, $result['totalcount']);
        $expectedFolders = array('INBOX', $this->_testFolderName, $this->_account->trash_folder, $this->_account->sent_folder);
        
        $foundCount = 0;
        foreach ($result['results'] as $index => $folder) {
            if (in_array($folder['localname'], $expectedFolders)) {
                $foundCount++;
            }
        }
        $this->assertEquals(count($expectedFolders), $foundCount);
    }
    
    /**
     * clear test folder
     */
    public function testClearFolder()
    {
        $folderName = $this->_testFolderName;
        $folder = $this->_getFolder($this->_testFolderName);
        $folder = Felamimail_Controller_Folder::getInstance()->emptyFolder($folder->getId());

        $filter = $this->_getMessageFilter($folder->getId());
        $result = $this->_json->searchMessages($filter, '');
        
        $this->assertEquals(0, $result['totalcount'], 'Found too many messages in folder ' . $this->_testFolderName);
        $this->assertEquals(0, $folder->cache_totalcount);
    }

    /**
     * try to create some folders
     */
    public function testCreateFolders()
    {
        $filter = $this->_getFolderFilter();
        $result = $this->_json->searchFolders($filter);
        
        $foldernames = array('test' => 'test', 'Schlüssel' => 'Schlüssel', 'test//1' => 'test1', 'test\2' => 'test2');
        
        foreach ($foldernames as $foldername => $expected) {
            $result = $this->_json->addFolder($foldername, $this->_testFolderName, $this->_account->getId());
            $globalname = $this->_testFolderName . $this->_account->delimiter . $expected;
            $this->_createdFolders[] = $globalname;
            $this->assertEquals($expected, $result['localname']);
            $this->assertEquals($globalname, $result['globalname']);
            $this->assertEquals(Felamimail_Model_Folder::CACHE_STATUS_EMPTY, $result['cache_status']);
        }
    }
    
    /**
     * test emtpy folder (with subfolder)
     */
    public function testEmptyFolderWithSubfolder()
    {
        $folderName = $this->_testFolderName;
        $folder = $this->_getFolder($this->_testFolderName);
        $this->testCreateFolders();
        
        $folderArray = $this->_json->emptyFolder($folder->getId());
        $this->assertEquals(0, $folderArray['has_children']);
        
        $result = $this->_json->updateFolderCache($this->_account->getId(), $this->_testFolderName);
        $this->assertEquals(0, count($result));
    }
    
    /**
     * testUpdateFolderCache
     */
    public function testUpdateFolderCache()
    {
        $result = $this->_json->updateFolderCache($this->_account->getId(), '');
        
        // create folders directly on imap server
        $this->_imap->createFolder('test', $this->_testFolderName, $this->_account->delimiter);
        $this->_imap->createFolder('testsub', $this->_testFolderName . $this->_account->delimiter . 'test', $this->_account->delimiter);
        // if something goes wrong, we need to delete these folders in tearDown
        $this->_createdFolders[] = $this->_testFolderName . $this->_account->delimiter . 'test' . $this->_account->delimiter . 'testsub';
        $this->_createdFolders[] = $this->_testFolderName . $this->_account->delimiter . 'test';
        
        // update cache and check if folder is found
        $result = $this->_json->updateFolderCache($this->_account->getId(), $this->_testFolderName);
        $testfolder = $result[0];
        //print_r($testfolder);
        $this->assertGreaterThan(0, count($result));
        $this->assertEquals($this->_testFolderName . $this->_account->delimiter . 'test', $testfolder['globalname']);
        $this->assertEquals(TRUE, (bool)$testfolder['has_children'], 'should have children');
        
        // delete subfolder directly on imap server
        $this->_imap->removeFolder($this->_testFolderName . $this->_account->delimiter . 'test' . $this->_account->delimiter . 'testsub');
        array_shift($this->_createdFolders);
        
        // check if has_children got updated and folder is removed from cache
        $this->_json->updateFolderCache($this->_account->getId(), '');
        $testfolder = $this->_getFolder($this->_testFolderName . $this->_account->delimiter . 'test');
        $this->assertEquals(FALSE, (bool)$testfolder['has_children'], 'should have no children');
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $testfoldersub = $this->_getFolder($this->_testFolderName . $this->_account->delimiter . 'test' . $this->_account->delimiter . 'testsub');

        $this->_imap->removeFolder($this->_testFolderName . $this->_account->delimiter . 'test');
        array_shift($this->_createdFolders);
        
        // try to update message cache of nonexistant folder
        $this->setExpectedException('Felamimail_Exception_IMAPFolderNotFound');
        $removedTestfolder = $this->_json->updateMessageCache($testfolder['id'], 1);
        
        // update cache and check if folder is deleted
        $result = $this->_json->updateFolderCache($this->_account->getId(), $this->_testFolderName);
        $this->assertEquals(0, count($result));
    }
    
    /*********************** accounts tests **************************/
    
    /**
     * test search for accounts and check default account from config
     */
    public function testSearchAccounts()
    {
        $system = $this->_getSystemAccount();
        
        $this->assertTrue(! empty($system), 'no accounts found');
        if (TestServer::getInstance()->getConfig()->mailserver) {
            $this->assertEquals(TestServer::getInstance()->getConfig()->mailserver, $system['host']);
            $this->assertEquals(TestServer::getInstance()->getConfig()->mailserver, $system['sieve_hostname']);
        }
    }
    
    /**
     * get system account
     * 
     * @return array
     */
    protected function _getSystemAccount()
    {
        $results = $this->_json->searchAccounts(array());
        
        $this->assertGreaterThan(0, $results['totalcount']);
        $system = array();
        foreach ($results['results'] as $result) {
            if ($result['name'] == Tinebase_Core::getUser()->accountLoginName . '@' . $this->_mailDomain) {
                $system = $result;
            }
        }
        
        return $system;
    }
    
    /**
     * test change / delete of account
     */
    public function testChangeDeleteAccount() 
    {
        $system = $this->_getSystemAccount();
        unset($system['id']);
        $system['type'] = Felamimail_Model_Account::TYPE_USER;
        $account = $this->_json->saveAccount($system);
        
        $accountRecord = new Felamimail_Model_Account($account, TRUE);
        $accountRecord->resolveCredentials(FALSE);
        if (TestServer::getInstance()->getConfig()->mailserver) {
            $this->assertEquals(TestServer::getInstance()->getConfig()->mailserver, $account['host']);
        }
        
        $this->_json->changeCredentials($account['id'], $accountRecord->user, 'neuespasswort');
        $account = $this->_json->getAccount($account['id']);
        
        $accountRecord = new Felamimail_Model_Account($account, TRUE);
        $accountRecord->resolveCredentials(FALSE);
        $this->assertEquals('neuespasswort', $accountRecord->password);
        
        $this->_json->deleteAccounts($account['id']);
    }
    
    /*********************** message tests ****************************/
    
    /**
     * test update message cache
     */
    public function testUpdateMessageCache()
    {
        $message = $this->_sendMessage();
        $inbox = $this->_getFolder('INBOX');
        // update message cache and check result
        $result = $this->_json->updateMessageCache($inbox['id'], 30);
        
        if ($result['cache_status'] == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE) {
            $this->assertEquals($result['imap_totalcount'], $result['cache_totalcount'], 'totalcounts should be equal');
        } else if ($result['cache_status'] == Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE) {
            $this->assertNotEquals(0, $result['cache_job_actions_est']);
        }
    }
    
    /**
     * test folder status
     */
    public function testGetFolderStatus()
    {
        $filter = $this->_getFolderFilter();
        $result = $this->_json->searchFolders($filter);
        $this->assertGreaterThan(1, $result['totalcount']);
        $expectedFolders = array('INBOX', $this->_testFolderName, $this->_account->trash_folder, $this->_account->sent_folder);
        
        foreach ($result['results'] as $folder) {
            $this->_json->updateMessageCache($folder['id'], 30);
        }
        
        $message = $this->_sendMessage();
        
        $status = $this->_json->getFolderStatus(array(array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())));
        $this->assertEquals(1, count($status));
        $this->assertEquals($this->_account->sent_folder, $status[0]['localname']);
    }

    /**
     * test folder status of deleted folder
     * 
     * @see 0007134: getFolderStatus should ignore non-existent folders
     */
    public function testGetFolderStatusOfDeletedFolder()
    {
        $this->testCreateFolders();
        // remove one of the created folders
        $removedFolder = $this->_createdFolders[0];
        $this->_imap->removeFolder(Felamimail_Model_Folder::encodeFolderName($removedFolder));
        
        $status = $this->_json->getFolderStatus(array(array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())));
        $this->assertGreaterThan(2, count($status), 'Expected more than 2 folders that need an update: ' . print_r($status, TRUE));
        foreach ($status as $folder) {
            if ($folder['globalname'] == $removedFolder) {
                $this->fail('removed folder should not appear in status array!');
            }
        }
    }
    
    /**
     * test send message
     */
    public function testSendMessage()
    {
        // set email to unittest@tine20.org
        $contactFilter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_family', 'operator' => 'equals', 'value' => 'Clever')
        ));
        $contactIds = Addressbook_Controller_Contact::getInstance()->search($contactFilter, NULL, FALSE, TRUE);
        $contact = Addressbook_Controller_Contact::getInstance()->get($contactIds[0]);
        $originalEmail =  $contact->email;
        $contact->email = $this->_account->email;
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact, FALSE);

        // send email
        $messageToSend = $this->_getMessageData('unittestalias@' . $this->_mailDomain);
        $messageToSend['note'] = 1;
        $messageToSend['bcc']  = array('unittest@' . $this->_mailDomain);
        //print_r($messageToSend);
        $returned = $this->_json->saveMessage($messageToSend);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);
        
        // check if message is in sent folder
        $message = $this->_searchForMessageBySubject($messageToSend['subject'], $this->_account->sent_folder);
        $this->assertEquals($message['from_email'], $messageToSend['from_email']);
        $this->assertTrue(isset($message['to'][0]));
        $this->assertEquals($message['to'][0],      $messageToSend['to'][0], 'recipient not found');
        $this->assertEquals($message['bcc'][0],     $messageToSend['bcc'][0], 'bcc recipient not found');
        $this->assertEquals($message['subject'],    $messageToSend['subject']);
        
        // check if email note has been added to contact(s)
        $contact = Addressbook_Controller_Contact::getInstance()->get($contact->getId());
        $emailNoteType = Tinebase_Notes::getInstance()->getNoteTypeByName('email');
        
        // check / delete notes
        $emailNoteIds = array();
        foreach ($contact->notes as $note) {
            if ($note->note_type_id == $emailNoteType->getId()) {
                $this->assertEquals(1, preg_match('/' . $messageToSend['subject'] . '/', $note->note));
                $this->assertEquals(Tinebase_Core::getUser()->getId(), $note->created_by);
                $this->assertContains('aaaaaä', $note->note);
                $emailNoteIds[] = $note->getId();
            }
        }
        $this->assertGreaterThan(0, count($emailNoteIds), 'no email notes found');
        Tinebase_Notes::getInstance()->deleteNotes($emailNoteIds);
        
        // reset sclevers original email address
        $contact->email = $originalEmail;
        Addressbook_Controller_Contact::getInstance()->update($contact, FALSE);
    }
    
    /**
     * try to get a message from imap server (with complete body, attachments, etc)
     * 
     * @see 0006300: add unique message-id header to new messages (for message-id check)
     */
    public function testGetMessage()
    {
        $message = $this->_sendMessage();
        
        // get complete message
        $message = $this->_json->getMessage($message['id']);
        
        // check
        $this->assertTrue(isset($message['headers']) && $message['headers']['message-id']);
        $this->assertContains('@' . $this->_mailDomain, $message['headers']['message-id']);
        $this->assertGreaterThan(0, preg_match('/aaaaaä/', $message['body']));
        
        // delete message on imap server and check if correct exception is thrown when trying to get it
        $this->_imap->removeMessage($message['messageuid']);
        Tinebase_Core::getCache()->clean();
        $this->setExpectedException('Felamimail_Exception_IMAPMessageNotFound');
        $message = $this->_json->getMessage($message['id']);
    }
    
    /**
     * try to get a message as plain/text
     */
    public function testGetPlainTextMessage()
    {
        $accountBackend = new Felamimail_Backend_Account();
        $message = $this->_sendMessage();
        
        // get complete message
        $this->_account->display_format = Felamimail_Model_Account::DISPLAY_PLAIN;
        $accountBackend->update($this->_account);
        $message = $this->_json->getMessage($message['id']);
        $this->_account->display_format = Felamimail_Model_Account::DISPLAY_HTML;
        $accountBackend->update($this->_account);
        
        // check
        $this->assertEquals("aaaaaä \n\r\n", $message['body']);
    }
    
    /**
     * try search for a message with path filter
     */
    public function testSearchMessageWithPathFilter()
    {
        $sentMessage = $this->_sendMessage();
        $filter = array(array(
            'field' => 'path', 'operator' => 'in', 'value' => '/' . $this->_account->getId()
        ));
        $result = $this->_json->searchMessages($filter, '');
        $message = $this->_getMessageFromSearchResult($result, $sentMessage['subject']);
        $this->assertTrue(! empty($message), 'Sent message not found with account path filter');

        $inbox = $this->_getFolder('INBOX');
        $filter = array(array(
            'field' => 'path', 'operator' => 'in', 'value' => '/' . $this->_account->getId() . '/' . $inbox->getId()
        ));
        $result = $this->_json->searchMessages($filter, '');
        $message = $this->_getMessageFromSearchResult($result, $sentMessage['subject']);
        $this->assertTrue(! empty($message), 'Sent message not found with path filter');
        foreach ($result['results'] as $mail) {
            $this->assertEquals($inbox->getId(), $mail['folder_id'], 'message is in wrong folder: ' . print_r($mail, TRUE));
        }
    }
    
    /**
     * try search for a message with all inboxes and flags filter
     */
    public function testSearchMessageWithAllInboxesFilter()
    {
        $sentMessage = $this->_sendMessage();
        $filter = array(
            array('field' => 'path',  'operator' => 'in',       'value' => Felamimail_Model_MessageFilter::PATH_ALLINBOXES),
            array('field' => 'flags', 'operator' => 'notin',    'value' => Zend_Mail_Storage::FLAG_FLAGGED),
        );
        $result = $this->_json->searchMessages($filter, '');
        $this->assertGreaterThan(0, $result['totalcount']);
        $this->assertEquals($result['totalcount'], count($result['results']));
        
        $message = $this->_getMessageFromSearchResult($result, $sentMessage['subject']);
        $this->assertTrue(! empty($message), 'Sent message not found with all inboxes filter');
    }
    
    /**
     * try search for a message with empty path filter
     */
    public function testSearchMessageEmptyPath()
    {
        $sentMessage = $this->_sendMessage();
        
        $filter = array(
            array('field' => 'path',  'operator' => 'equals',   'value' => ''),
        );
        $result = $this->_json->searchMessages($filter, '');
        
        $this->assertEquals(0, $result['totalcount']);
        $accountFilterFound = FALSE;
        
        foreach ($result['filter'] as $filter) {
            if ($filter['field'] === 'account_id' && empty($filter['value'])) {
                $accountFilterFound = TRUE;
                break;
            }
        }
        $this->assertTrue($accountFilterFound);
    }
    
    /**
     * test flags (add + clear + deleted)
     * 
     */
    public function testAddAndClearFlags()
    {
        $message = $this->_sendMessage();
        $inboxBefore = $this->_getFolder('INBOX');
        
        $this->_json->addFlags($message['id'], Zend_Mail_Storage::FLAG_SEEN);
        
        // check if unread count got decreased
        $inboxAfter = $this->_getFolder('INBOX');
        $this->assertTrue($inboxBefore->cache_unreadcount - 1 == $inboxAfter->cache_unreadcount, 'wrong cache unreadcount');
        
        $message = $this->_json->getMessage($message['id']);
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_SEEN, $message['flags']), 'seen flag not set');
        
        // try with a filter
        $filter = array(
            array('field' => 'id', 'operator' => 'in', array($message['id']))
        );
        $this->_json->clearFlags($filter, Zend_Mail_Storage::FLAG_SEEN);
        
        $message = $this->_json->getMessage($message['id']);
        $this->assertFalse(in_array(Zend_Mail_Storage::FLAG_SEEN, $message['flags']), 'seen flag should not be set');

        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_json->addFlags(array($message['id']), Zend_Mail_Storage::FLAG_DELETED);
        $this->_json->getMessage($message['id']);
    }

    /**
     * test delete from trash
     */
    public function testDeleteFromTrashWithFilter()
    {
        $message = $this->_sendMessage();
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder, $this->_account->trash_folder);
        
        $trash = $this->_getFolder($this->_account->trash_folder);
        $result = $this->_json->moveMessages(array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'])
        )), $trash->getId());

        $messageInTrash = $this->_searchForMessageBySubject($message['subject'], $this->_account->trash_folder);
        
        // delete messages in trash with filter
        $this->_json->addFlags(array(array(
            'field' => 'folder_id', 'operator' => 'equals', 'value' => $trash->getId()
        ), array(
            'field' => 'id', 'operator' => 'in', 'value' => array($messageInTrash['id'])
        )), Zend_Mail_Storage::FLAG_DELETED);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_json->getMessage($messageInTrash['id']);
    }
    
    /**
     * move message to trash with trash folder constant (Felamimail_Model_Folder::FOLDER_TRASH)
     */
    public function testMoveMessagesToTrash()
    {
        $message = $this->_sendMessage();
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder, $this->_account->trash_folder);
        
        $result = $this->_json->moveMessages(array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'])
        )), Felamimail_Model_Folder::FOLDER_TRASH);

        $messageInTrash = $this->_searchForMessageBySubject($message['subject'], $this->_account->trash_folder);
    }
    
    /**
     * test reply mail and check some headers
     * 
     * @see 0006106: Add References header / https://forge.tine20.org/mantisbt/view.php?id=6106
     */
    public function testReplyMessage()
    {
        $message = $this->_sendMessage();
        
        $replyMessage = $this->_getReply($message);
        $returned = $this->_json->saveMessage($replyMessage);
        
        $result = $this->_getMessages();
        
        $replyMessageFound = array();
        $originalMessage = array();
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $replyMessage['subject']) {
                $replyMessageFound = $mail;
            }
            if ($mail['subject'] == $message['subject']) {
                $originalMessage = $mail;
            }
        }
        $replyMessageFound = $this->_json->getMessage($replyMessageFound['id']);
        $originalMessage = $this->_json->getMessage($originalMessage['id']);
        
        $this->assertTrue(! empty($replyMessageFound), 'replied message not found');
        $this->assertTrue(! empty($originalMessage), 'original message not found');
        
        // check headers
        $this->assertTrue(isset($replyMessageFound['headers']['in-reply-to']));
        $this->assertEquals($originalMessage['headers']['message-id'], $replyMessageFound['headers']['in-reply-to']);
        $this->assertTrue(isset($replyMessageFound['headers']['references']));
        $this->assertEquals($originalMessage['headers']['message-id'], $replyMessageFound['headers']['references']);
        
        // check answered flag
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_ANSWERED, $originalMessage['flags'], 'could not find flag'));
    }
    
    /**
     * get reply message data
     * 
     * @param array $_original
     * @return array
     */
    protected function _getReply($_original)
    {
        $replyMessage               = $this->_getMessageData();
        $replyMessage['subject']    = 'Re: ' . $_original['subject'];
        $replyMessage['original_id']= $_original['id'];
        $replyMessage['flags']      = Zend_Mail_Storage::FLAG_ANSWERED;
        
        return $replyMessage;
    }

    /**
     * test reply mail in sent folder
     */
    public function testReplyMessageInSentFolder()
    {
        $messageInSent = $this->_sendMessage($this->_account->sent_folder);
        $replyMessage = $this->_getReply($messageInSent);
        $returned = $this->_json->saveMessage($replyMessage);
        
        $result = $this->_getMessages();
        $sentMessage = $this->_getMessageFromSearchResult($result, $replyMessage['subject']);
        $this->assertTrue(! empty($sentMessage));
    }

    /**
     * test reply mail with long references header
     * 
     * @see 0006644: "At least one mail header line is too long"
     */
    public function testReplyMessageWithLongHeader()
    {
        $messageInSent = $this->_sendMessage($this->_account->sent_folder, array(
            'references' => '<c95d8187-2c71-437e-adb8-5e1dcdbdc507@email.test.org>
   <2601bbfa-566e-4490-a3db-aad005733d32@email.test.org>
   <20120530154350.1854610131@ganymed.de>
   <7e393ce1-d193-44fc-bf5f-30c61a271fe6@email.test.org>
   <4FC8B49C.8040704@funk.de>
   <dba2ad5c-6726-4171-8710-984847c010a1@email.test.org>
   <20120601123551.5E98610131@ganymed.de>
   <f1cc3195-8641-46e3-8f20-f60f3e16b107@email.test.org>
   <20120619093658.37E4210131@ganymed.de>
   <CA+6Rn2PX2Q3tOk2tCQfCjcaC8zYS5XZX327OoyJfUb+w87vCLQ@mail.net.com>
   <20120619130652.03DD310131@ganymed.de>
   <37616c6a-4c47-4b54-9ca6-56875bc9205d@email.test.org>
   <20120620074843.42E2010131@ganymed.de>
   <CA+6Rn2MAb2x0qeSfcaW6F=0S7LEQL442Sx2ha9RtwMs4B0esBg@mail.net.com>
   <20120620092902.88C8C10131@ganymed.de>
   <c95d8187-2c71-437e-adb8-5e1dcdbdc507@email.test.org>
   <2601bbfa-566e-4490-a3db-aad005733d32@email.test.org>
   <20120530154350.1854610131@ganymed.de>
   <7e393ce1-d193-44fc-bf5f-30c61a271fe6@email.test.org>
   <4FC8B49C.8040704@funk.de>
   <dba2ad5c-6726-4171-8710-984847c010a1@email.test.org>
   <20120601123551.5E98610131@ganymed.de>
   <f1cc3195-8641-46e3-8f20-f60f3e16b107@email.test.org>
   <20120619093658.37E4210131@ganymed.de>
   <CA+6Rn2PX2Q3tOk2tCQfCjcaC8zYS5XZX327OoyJfUb+w87vCLQ@mail.net.com>
   <20120619130652.03DD310131@ganymed.de>
   <37616c6a-4c47-4b54-9ca6-56875bc9205d@email.test.org>
   <20120620074843.42E2010131@ganymed.de>
   <CA+6Rn2MAb2x0qeSfcaW6F=0S7LEQL442Sx2ha9RtwMs4B0esBg@mail.net.com>
   <20120620092902.88C8C10131@ganymed.de>'
        ));
        $replyMessage = $this->_getReply($messageInSent);
        $returned = $this->_json->saveMessage($replyMessage);
        
        $result = $this->_getMessages();
        $sentMessage = $this->_getMessageFromSearchResult($result, $replyMessage['subject']);
        $this->assertTrue(! empty($sentMessage));
    }
    
    /**
     * test move
     */
    public function testMoveMessage()
    {
        $message = $this->_sendMessage();
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder, $this->_testFolderName);
        
        $inbox = $this->_getFolder('INBOX');
        $inboxBefore = $this->_json->updateMessageCache($inbox['id'], 30);
        
        // move
        $testFolder = $this->_getFolder($this->_testFolderName);
        $result = $this->_json->moveMessages(array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'])
        )), $testFolder->getId());

        $inboxAfter = $this->_getFolder('INBOX');
        
        // check if count was decreased correctly
        $this->assertEquals($inboxBefore['cache_unreadcount'] - 1, $inboxAfter['cache_unreadcount']);
        $this->assertEquals($inboxBefore['cache_totalcount'] - 1, $inboxAfter['cache_totalcount']);
        
        $result = $this->_getMessages($this->_testFolderName);
        $movedMessage = array();
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $message['subject']) {
                $movedMessage = $mail;
            }
        }
        $this->assertTrue(! empty($movedMessage), 'moved message not found');
    }
    
    /**
     * forward message test
     * 
     * @see 0007624: losing umlauts in attached filenames
     */
    public function testForwardMessageWithAttachment()
    {
        $testFolder = $this->_getFolder($this->_testFolderName);
        $message = fopen(dirname(__FILE__) . '/files/multipart_related.eml', 'r');
        Felamimail_Controller_Message::getInstance()->appendMessage($testFolder, $message);
        
        $subject = 'Tine 2.0 bei Metaways - Verbessurngsvorschlag';
        $message = $this->_searchForMessageBySubject($subject, $this->_testFolderName);
        
        $fwdSubject = 'Fwd: ' . $subject;
        $forwardMessageData = array(
            'account_id'    => $this->_account->getId(),
            'subject'       => $fwdSubject,
            'to'            => array('unittest@' . $this->_mailDomain),
            'body'          => "aaaaaä <br>",
            'headers'       => array('X-Tine20TestMessage' => 'jsontest'),
            'original_id'   => $message['id'],
            'attachments'   => array(new Tinebase_Model_TempFile(array(
                'type'  => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822,
                'name'  => 'Verbessurüngsvorschlag',
            ), TRUE)),
            'flags'         => Zend_Mail_Storage::FLAG_PASSED,
        );
        
        $this->_foldersToClear[] = 'INBOX';
        $this->_json->saveMessage($forwardMessageData);
        $forwardMessage = $this->_searchForMessageBySubject($fwdSubject);
        
        // check attachment name
        $forwardMessageComplete = $this->_json->getMessage($forwardMessage['id']);
        $this->assertEquals(1, count($forwardMessageComplete['attachments']));
        $this->assertEquals('Verbessurüngsvorschlag.eml', $forwardMessageComplete['attachments'][0]['filename'], 'umlaut missing from attachment filename');
        
        $forwardMessage = $this->_json->getMessage($forwardMessage['id']);
        $this->assertTrue(array_key_exists('structure', $forwardMessage), 'structure should be set when fetching complete message: ' . print_r($forwardMessage, TRUE));
        $this->assertEquals(Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822, $forwardMessage['structure']['parts'][2]['contentType']);
        
        $message = $this->_json->getMessage($message['id']);
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_PASSED, $message['flags']), 'forwarded flag missing in flags: ' . print_r($message, TRUE));
    }
    
    /**
     * save message in folder (draft) test
     * 
     * @see 0007178: BCC does not save the draft message
     */
    public function testSaveMessageInFolder()
    {
        $messageToSave = $this->_getMessageData();
        $messageToSave['bcc'] = array('bccaddress@email.org');
        
        $draftsFolder = $this->_getFolder($this->_account->drafts_folder);
        $returned = $this->_json->saveMessageInFolder($this->_account->drafts_folder, $messageToSave);
        $this->_foldersToClear = array($this->_account->drafts_folder);
        
        // check if message is in drafts folder
        $message = $this->_searchForMessageBySubject($messageToSave['subject'], $this->_account->drafts_folder);
        $this->assertEquals($messageToSave['subject'],  $message['subject']);
        $this->assertEquals($messageToSave['to'][0],    $message['to'][0], 'recipient not found');
        $this->assertEquals(1, count($message['bcc']), 'bcc recipient not found: ' . print_r($message, TRUE));
        $this->assertEquals($messageToSave['bcc'][0],   $message['bcc'][0], 'bcc recipient not found');
    }
    
    /*********************** sieve tests ****************************/
    
    /**
     * set and get vacation sieve script
     */
    public function testGetSetVacation()
    {
        $vacationData = $this->_getVacationData();
        $this->_sieveTestHelper($vacationData);
        
        // check if script was activated
        $activeScriptName = Felamimail_Controller_Sieve::getInstance()->getActiveScriptName($this->_account->getId());
        $this->assertEquals($this->_testSieveScriptName, $activeScriptName);
        $updatedAccount = Felamimail_Controller_Account::getInstance()->get($this->_account->getId());
        $this->assertTrue((bool) $updatedAccount->sieve_vacation_active);
        
        $result = $this->_json->getVacation($this->_account->getId());

        $this->assertEquals($this->_account->email, $result['addresses'][0]);
        
        $sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());
        if (preg_match('/dbmail/i', $sieveBackend->getImplementation())) {
            $translate = Tinebase_Translation::getTranslation('Felamimail');
            $vacationData['subject'] = sprintf($translate->_('Out of Office reply from %1$s'), Tinebase_Core::getUser()->accountFullName);
        }
        
        foreach (array('reason', 'enabled', 'subject', 'from', 'days') as $field) {
            $this->assertEquals($vacationData[$field], $result[$field], 'vacation data mismatch: ' . $field);
        }
    }
    
    /**
     * get vacation data
     * 
     * @return array
     */
    protected function _getVacationData()
    {
        return array(
            'id'                    => $this->_account->getId(),
            'subject'               => 'unittest vacation subject',
            'from'                  => $this->_account->from . ' <' . $this->_account->email . '>',
            'days'                  => 7,
            'enabled'               => TRUE,
            'reason'                => 'unittest vacation message<br /><br />signature',
            'mime'                  => NULL,
        );
    }
    
    /**
     * test mime vacation sieve script
     */
    public function testMimeVacation()
    {
        $vacationData = $this->_getVacationData();
        $vacationData['reason'] = "\n<html><body><h1>unittest vacation&nbsp;message</h1></body></html>";
        
        $_sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());
        if (! in_array('mime', $_sieveBackend->capability())) {
            $vacationData['mime'] = 'text/html';
        }
        
        $this->_sieveTestHelper($vacationData, TRUE);
    }
    
    /**
     * test get/set of rules sieve script
     */
    public function testGetSetRules()
    {
        $ruleData = $this->_getRuleData();
        
        $this->_sieveTestHelper($ruleData);
        
        // check getRules
        $result = $this->_json->getRules($this->_account->getId());
        $this->assertEquals($result['totalcount'], count($ruleData));
        
        // check by sending mail
        $messageData = $this->_getMessageData('', 'viagra');
        $returned = $this->_json->saveMessage($messageData);
        $this->_foldersToClear = array('INBOX', $this->_testFolderName);
        // check if message is in test folder
        $message = $this->_searchForMessageBySubject($messageData['subject'], $this->_testFolderName);
    }
    
    /**
     * testRemoveRules
     * 
     * @see 0006490: can not delete single filter rule
     */
    public function testRemoveRules()
    {
        $this->testGetSetRules();
        $this->_json->saveRules($this->_account->getId(), array());
        
        $result = $this->_json->getRules($this->_account->getId());
        $this->assertEquals(0, $result['totalcount'], 'found rules: ' . print_r($result, TRUE));
    }
    
    /**
     * get sieve rule data
     * 
     * @return array
     */
    protected function _getRuleData()
    {
        return array(array(
            'id'            => 1,
            'action_type'   => Felamimail_Sieve_Rule_Action::FILEINTO, 
            'action_argument' => $this->_testFolderName,
            'conditions'    => array(array(
                'test'          => Felamimail_Sieve_Rule_Condition::TEST_ADDRESS,
                'comperator'    => Felamimail_Sieve_Rule_Condition::COMPERATOR_CONTAINS,
                'header'        => 'From',
                'key'           => '"abcd" <info@example.org>',
            )),
            'enabled'       => 1,
        ), array(
            'id'            => 2,
            'action_type'   => Felamimail_Sieve_Rule_Action::FILEINTO, 
            'action_argument' => $this->_testFolderName,
            'conditions'    => array(array(
                'test'          => Felamimail_Sieve_Rule_Condition::TEST_ADDRESS,
                'comperator'    => Felamimail_Sieve_Rule_Condition::COMPERATOR_CONTAINS,
                'header'        => 'From',
                'key'           => 'info@example.org',
            )),
            'enabled'       => 0,
        ), array(
            'id'            => 3,
            'action_type'   => Felamimail_Sieve_Rule_Action::FILEINTO, 
            'action_argument' => $this->_testFolderName,
            'conditions'    => array(array(
                'test'          => Felamimail_Sieve_Rule_Condition::TEST_HEADER,
                'comperator'    => Felamimail_Sieve_Rule_Condition::COMPERATOR_REGEX,
                'header'        => 'subject',
                'key'           => '[vV]iagra|cyalis',
            )),
            'enabled'       => 1,
        ));
    }
    
    /**
     * test to set a forward rule to this accounts email address
     * -> should throw exception to prevent mail cycling
     */
    public function testSetForwardRuleToSelf()
    {
        $ruleData = array(array(
            'id'            => 1,
            'action_type'   => Felamimail_Sieve_Rule_Action::REDIRECT, 
            'action_argument' => $this->_account->email,
            'conditions'    => array(array(
                'test'          => Felamimail_Sieve_Rule_Condition::TEST_ADDRESS,
                'comperator'    => Felamimail_Sieve_Rule_Condition::COMPERATOR_CONTAINS,
                'header'        => 'From',
                'key'           => 'info@example.org',
            )),
            'enabled'       => 1,
        ));
        
        try {
            $this->_sieveTestHelper($ruleData);
            $this->assertTrue(FALSE, 'it is not allowed to set own email address for redirect!');
        } catch (Felamimail_Exception_Sieve $e) {
            $this->assertTrue(TRUE);
        }

        // this should work
        $ruleData[0]['enabled'] = 0;
        $this->_sieveTestHelper($ruleData);
    }
    
    /**
     * testGetVacationTemplates
     * 
     * @return array
     */
    public function testGetVacationTemplates()
    {
        $this->_addVacationTemplateFile();
        $result = $this->_json->getVacationMessageTemplates();
        
        $this->assertTrue($result['totalcount'] > 0, 'no templates found');
        $found = FALSE;
        foreach ($result['results'] as $template) {
            if ($template['name'] === $this->_sieveVacationTemplateFile) {
                $found = TRUE;
                break;
            }
        }
        
        $this->assertTrue($found, 'wrong templates: ' . print_r($result['results'], TRUE));
        
        return $template;
    }
    
    /**
     * add vacation template file to vfs
     */
    protected function _addVacationTemplateFile()
    {
        $webdavRoot = new Sabre_DAV_ObjectTree(new Tinebase_WebDav_Root());
        $path = '/webdav/Felamimail/shared/Vacation Templates';
        $node = $webdavRoot->getNodeForPath($path);
        $this->_pathsToDelete[] = $path . '/' . $this->_sieveVacationTemplateFile;
        $node->createFile($this->_sieveVacationTemplateFile, fopen(dirname(__FILE__) . '/files/' . $this->_sieveVacationTemplateFile, 'r'));
    }
    
    /**
     * testGetVacationMessage
     */
    public function testGetVacationMessage()
    {
        $result = $this->_getVacationMessageWithTemplate();
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        $this->assertEquals("Ich bin vom 18.04.2012 bis zum 20.04.2012 im Urlaub. Bitte kontaktieren Sie<br /> Paul Wulf (pwulf@tine20.org) oder Susan Clever (" .
            $sclever->accountEmailAddress . ").<br /><br />I am on vacation until Apr 20, 2012. Please contact Paul Wulf<br />(pwulf@tine20.org) or Susan Clever (" .
            $sclever->accountEmailAddress . ") instead.<br /><br />" .
            Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId())->n_fn, $result['message']);
    }
    
    /**
     * get vacation message with template
     * 
     * @return array
     */
    protected function _getVacationMessageWithTemplate()
    {
        $template = $this->testGetVacationTemplates();
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        $result = $this->_json->getVacationMessage(array(
            'start_date' => '2012-04-18',
            'end_date'   => '2012-04-20',
            'contact_ids' => array(
                Tinebase_User::getInstance()->getFullUserByLoginName('pwulf')->contact_id,
                $sclever->contact_id,
            ),
            'template_id' => $template['id'],
            'signature' => $this->_account->signature
        ));
        
        return $result;
    }
    
    /**
     * testGetVacationWithSignature
     * 
     * @see 0006866: check signature linebreaks in vacation message from template
     */
    public function testGetVacationWithSignature()
    {
        $this->_sieveVacationTemplateFile = 'vacation_template_sig.tpl';
        
        // set signature with <br> + linebreaks
        $this->_account->signature = "llalala<br>\nxyz<br>\nblubb<br>";
        
        $result = $this->_getVacationMessageWithTemplate();
        $this->assertContains('-- <br />llalala<br />xyz<br />blubb<br />', $result['message'], 'wrong linebreaks or missing signature');
    }
    
    /**
    * testSetVacationWithStartAndEndDate
    *
    * @see 0006266: automatic deactivation of vacation message
    */
    public function testSetVacationWithStartAndEndDate()
    {
        $vacationData = $this->_getVacationData();
        $vacationData['start_date'] = '2012-04-18';
        $vacationData['end_date'] = '2012-04-20';
        $result = $this->_sieveTestHelper($vacationData);
        
        $this->assertContains($vacationData['start_date'], $result['start_date']);
        $this->assertContains($vacationData['end_date'], $result['end_date']);
    }
    
    /**
     * testSieveRulesOrder
     * 
     * @see 0007240: order of sieve rules changes when vacation message is saved
     */
    public function testSieveRulesOrder()
    {
        $this->_setTestScriptname();
        
        // disable vacation first
        $this->_setDisabledVacation();
        
        $sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());
        
        $ruleData = $this->_getRuleData();
        $ruleData[0]['id'] = $ruleData[2]['id'];
        $ruleData[2]['id'] = 11;
        $resultSet = $this->_json->saveRules($this->_account->getId(), $ruleData);
        $sieveScriptRules = $sieveBackend->getScript($this->_testSieveScriptName);
        
        $this->_setDisabledVacation();
        $sieveScriptVacation = $sieveBackend->getScript($this->_testSieveScriptName);
        
        // compare sieve scripts
        $this->assertContains($sieveScriptRules, $sieveScriptVacation, 'rule order changed');
    }
    
    /**
     * use another name for test sieve script
     */
    protected function _setTestScriptname()
    {
        $this->_oldActiveSieveScriptName = Felamimail_Controller_Sieve::getInstance()->getActiveScriptName($this->_account->getId());
        $this->_testSieveScriptName = 'Felamimail_Unittest';
        Felamimail_Controller_Sieve::getInstance()->setScriptName($this->_testSieveScriptName);
    }
    
    /**
     * set disabled vacation message
     */
    protected function _setDisabledVacation()
    {
        $vacationData = $this->_getVacationData();
        $vacationData['enabled'] = FALSE;
        $resultSet = $this->_json->saveVacation($vacationData);
    }
    
    /**
     * get folder filter
     *
     * @return array
     */
    protected function _getFolderFilter()
    {
        return array(array(
            'field' => 'globalname', 'operator' => 'equals', 'value' => ''
        ));
    }

    /**
     * get message filter
     *
     * @param string $_folderId
     * @return array
     */
    protected function _getMessageFilter($_folderId)
    {
        $result = array(array(
            'field' => 'folder_id', 'operator' => 'equals', 'value' => $_folderId
        ));
        
        return $result;
    }
    
    /**
     * get mailbox
     *
     * @param string $_name
     * @return Felamimail_Model_Folder
     */
    protected function _getFolder($_name)
    {
        Felamimail_Controller_Cache_Folder::getInstance()->update($this->_account->getId());
        try {
            $folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($this->_account->getId(), $_name);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $folder = Felamimail_Controller_Folder::getInstance()->create($this->_account, $_name);
        }
        
        return $folder;
    }

    /**
     * get message data
     *
     * @return array
     */
    protected function _getMessageData($_emailFrom = '', $_subject = 'test')
    {
        return array(
            'account_id'    => $this->_account->getId(),
            'subject'       => $_subject,
            'to'            => array('unittest@' . $this->_mailDomain),
            'body'          => 'aaaaaä <br>',
            'headers'       => array('X-Tine20TestMessage' => 'jsontest'),
            'from_email'    => $_emailFrom,
            'content_type'  => Felamimail_Model_Message::CONTENT_TYPE_HTML,
        );
    }

    /**
     * send message and return message array
     *
     * @param string $folderName
     * @param array $addtionalHeaders
     * @return array
     */
    protected function _sendMessage($folderName = 'INBOX', $addtionalHeaders = array())
    {
        $messageToSend = $this->_getMessageData();
        $messageToSend['headers'] = array_merge($messageToSend['headers'], $addtionalHeaders);
        $returned = $this->_json->saveMessage($messageToSend);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);
        
        $result = $this->_getMessages($folderName);
        $message = $this->_getMessageFromSearchResult($result, $messageToSend['subject']);
        
        $this->assertTrue(! empty($message), 'Sent message not found.');
        
        return $message;
    }
    
    /**
     * returns message array from result
     * 
     * @param array $_result
     * @param string $_subject
     * @return array
     */
    protected function _getMessageFromSearchResult($_result, $_subject)
    {
        $message = array();
        foreach ($_result['results'] as $mail) {
            if ($mail['subject'] == $_subject) {
                $message = $mail;
            }
        }
        
        return $message;
    }
    
    /**
     * get messages from folder
     * 
     * @param string $_folderName
     * @return array
     */
    protected function _getMessages($_folderName = 'INBOX')
    {
        $folder = $this->_getFolder($_folderName);
        $filter = $this->_getMessageFilter($folder->getId());
        // update cache
        $folder = Felamimail_Controller_Cache_Message::getInstance()->updateCache($folder, 10, 1);
        $i = 0;
        while ($folder->cache_status != Felamimail_Model_Folder::CACHE_STATUS_COMPLETE && $i < 10) {
            $folder = Felamimail_Controller_Cache_Message::getInstance()->updateCache($folder, 10);
            $i++;
        }
        $result = $this->_json->searchMessages($filter, '');
        //print_r($result);
        
        return $result;
    }
    
    /**
     * search for message defined by subject in folder
     * 
     * @param string $_subject
     * @param string $_folderName
     * @return string message data
     */
    protected function _searchForMessageBySubject($_subject, $_folderName = 'INBOX')
    {
        $result = $this->_getMessages($_folderName);
        
        $message = array();
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $_subject) {
                $message = $mail;
            }
        }
        $this->assertGreaterThan(0, $result['totalcount'], 'folder is empty');
        $this->assertTrue(! empty($message), 'Message not found');
        
        return $message;
    }
    
    /**
     * sieve test helper
     * 
     * @param array $_sieveData
     * @return array
     */
    protected function _sieveTestHelper($_sieveData, $_isMime = FALSE)
    {
        $this->_setTestScriptname();
        
        // check which save fn to use
        if (array_key_exists('reason', $_sieveData)) {
            $resultSet = $this->_json->saveVacation($_sieveData);
            $this->assertEquals($this->_account->email, $resultSet['addresses'][0]);
            
            $_sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());
            
            if (preg_match('/dbmail/i', $_sieveBackend->getImplementation())) {
                $translate = Tinebase_Translation::getTranslation('Felamimail');
                $this->assertEquals(sprintf(
                    $translate->_('Out of Office reply from %1$s'), Tinebase_Core::getUser()->accountFullName), 
                    $resultSet['subject']
                );
            } else {
                $this->assertEquals($_sieveData['subject'], $resultSet['subject']);
            }
            
            if ($_isMime) {
                $this->assertEquals(html_entity_decode('unittest vacation&nbsp;message', ENT_NOQUOTES, 'UTF-8'), $resultSet['reason']);
            } else {
                $this->assertEquals($_sieveData['reason'], $resultSet['reason']);
            }
            
        } else if (array_key_exists('action_type', $_sieveData[0])) {
            $resultSet = $this->_json->saveRules($this->_account->getId(), $_sieveData);
            $this->assertEquals($_sieveData, $resultSet);
        }
        
        return $resultSet;
    }
}
