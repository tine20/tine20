<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Felamimail_JsonTest::main');
}

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
     * @var array
     */
    protected $_oldActiveSieveScriptName = NULL;

    /**
     * was sieve_vacation_active ?
     * 
     * @var boolean
     */
    protected $_oldSieveVacationActiveState = FALSE;

    /**
     * sieve script name to delete
     * 
     * @var array
     */
    protected $_testSieveScriptName = NULL;

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
        
        $this->_json = new Felamimail_Frontend_Json();
        $this->_imap = Felamimail_Backend_ImapFactory::factory($this->_account);
        
        // create test folder if it does not exist
        try {
            $this->_imap->createFolder($this->_testFolderName, '', $this->_account->delimiter);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            // exists
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
        if (count($this->_createdFolders) > 0) {
            foreach ($this->_createdFolders as $folderName) {
                //echo "delete $folderName\n";
                $this->_imap->removeFolder(Felamimail_Model_Folder::encodeFolderName($folderName));
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
            Felamimail_Controller_Sieve::getInstance()->deleteScript($this->_account->getId());
            Felamimail_Controller_Account::getInstance()->setVacationActive($this->_account, $this->_oldSieveVacationActiveState);
        }
        if ($this->_oldActiveSieveScriptName !== NULL) {
            Felamimail_Controller_Sieve::getInstance()->setScriptName($this->_oldActiveSieveScriptName);
            Felamimail_Controller_Sieve::getInstance()->activateScript($this->_account->getId());
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
        
        $this->assertEquals(6, $result['totalcount']);
        $expectedFolders = array('INBOX', 'Drafts', $this->_account->sent_folder, 'Templates', 'Junk', $this->_account->trash_folder);
        
        foreach ($expectedFolders as $index => $folderName) {
            $this->assertEquals($folderName, $result['results'][$index]['localname']);
        }
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
        $this->assertEquals('mail.metaways.net', $system['host']);
        $this->assertEquals('mailproxy.metaways.net', $system['sieve_hostname']);
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
            if ($result['name'] == 'unittest@tine20.org') {
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
        $this->assertEquals('mail.metaways.net', $account['host']);
        
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
        $messageToSend = $this->_getMessageData();
        $message = $this->_json->saveMessage($messageToSend);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);
        
        $inbox = $this->_getFolder('INBOX');
        
        // update message cache and check result
        $result = $this->_json->updateMessageCache($inbox['id'], 30);
        
        if ($result['cache_status'] == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE) {
            $this->assertEquals($result['imap_totalcount'], $result['cache_totalcount'], 'totalcounts should be equal');
        } else if ($result['cache_status'] == Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE) {
            $this->assertNotEquals(0, $result['cache_job_actions_estimate']);
        }
    }
    
    /**
     * test send message
     *
     */
    public function testSendMessage()
    {
        // set email to unittest@tine20.org
        $contactFilter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_family', 'operator' => 'equals', 'value' => 'Clever')
        ));
        $contactIds = Addressbook_Controller_Contact::getInstance()->search($contactFilter, NULL, FALSE, TRUE);
        $contact = Addressbook_Controller_Contact::getInstance()->get($contactIds[0]);
        $contact->email = $this->_account->email;
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);

        // send email
        $messageToSend = $this->_getMessageData();
        $messageToSend['note'] = 1;
        //print_r($messageToSend);
        $returned = $this->_json->saveMessage($messageToSend);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);
        
        // check if message is in sent folder
        $message = $this->_searchForMessageBySubject($messageToSend['subject'], $this->_account->sent_folder);
        $this->assertEquals($message['subject'],  $messageToSend['subject']);
        $this->assertEquals($message['to'][0],    $messageToSend['to'][0], 'recipient not found');
        
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
    }
    
    /**
     * try to get a message from imap server (with complete body, attachments, etc)
     *
     */
    public function testGetMessage()
    {
        $message = $this->_sendMessage();     
        
        // get complete message
        $message = $this->_json->getMessage($message['id']);
        
        // check
        $this->assertGreaterThan(0, preg_match('/aaaaaä/', $message['body']));
        
        // delete message on imap server and check if correct exception is thrown when trying to get it
        $this->_imap->removeMessage($message['messageuid']);
        Tinebase_Core::getCache()->clean();
        $this->setExpectedException('Felamimail_Exception_IMAPMessageNotFound');
        $message = $this->_json->getMessage($message['id']);
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
        
        $this->_json->clearFlags(array($message['id']), Zend_Mail_Storage::FLAG_SEEN);
        
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
     * test reply mail
     * 
     */
    public function testReplyMessage()
    {
        $message = $this->_sendMessage();
        
        $replyMessage               = $this->_getMessageData();
        $replyMessage['flags']      = '\\Answered';
        $replyMessage['subject']    = 'Re: ' . $message['subject'];
        $replyMessage['original_id']= $message['id'];
        $replyMessage['headers']    = array('X-Tine20TestMessage' => 'jsontest');
        $returned                   = $this->_json->saveMessage($replyMessage);
        
        $result = $this->_getMessages();
        //print_r($result);
        
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
        $this->assertTrue(! empty($replyMessageFound), 'replied message not found');
        $this->assertTrue(! empty($originalMessage), 'original message not found');
        
        // check answered flag
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_ANSWERED, $originalMessage['flags'], 'could not find flag'));
    }
    
    /**
     * test move
     * 
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
     */
    public function testForwardMessageWithAttachment()
    {
        $testFolder = $this->_getFolder($this->_testFolderName);
        $message = fopen(dirname(__FILE__) . '/files/multipart_related.eml', 'r');
        Felamimail_Controller_Message::getInstance()->appendMessage($testFolder, $message);
        
        $subject = 'Tine 2.0 bei Metaways - Verbessurngsvorschlag';
        $message = $this->_searchForMessageBySubject($subject, $this->_testFolderName);
        //print_r($message);
        
        $fwdSubject = 'Fwd: ' . $subject;
        $forwardMessageData = array(
            'account_id'    => $this->_account->getId(),
            'subject'       => $fwdSubject,
            'to'            => array('unittest@tine20.org'),
            'body'          => 'aaaaaä <br>',
            'headers'       => array('X-Tine20TestMessage' => 'jsontest'),
            'original_id'   => $message['id'],
            'attachments'   => array(array(
                'type'  => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822,
                'name'  => $subject,
            )),
        );
        
        $this->_foldersToClear[] = 'INBOX';
        $this->_json->saveMessage($forwardMessageData);
        $forwardMessage = $this->_searchForMessageBySubject($fwdSubject);
        
        //print_r($forwardMessage);
        
        $this->assertEquals(Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822, $forwardMessage['structure']['parts'][2]['contentType']);
    }
    
    /**
     * save message in folder test
     */
    public function testSaveMessageInFolder()
    {
        $messageToSave = $this->_getMessageData();
        $returned = $this->_json->saveMessageInFolder($this->_account->drafts_folder, $messageToSave);
        $this->_foldersToClear = array($this->_account->drafts_folder);
        
        // check if message is in drafts folder
        $message = $this->_searchForMessageBySubject($messageToSave['subject'], $this->_account->drafts_folder);
        $this->assertEquals($message['subject'],  $messageToSave['subject']);
        $this->assertEquals($message['to'][0],    $messageToSave['to'][0], 'recipient not found');
    }
    
    /*********************** sieve tests ****************************/
    
    /**
     * set and get vacation sieve script
     */
    public function testGetSetVacation()
    {
        $vacationData = array(
            'id'                    => $this->_account->getId(),
            'subject'               => 'unittest vacation subject',
            'from'                  => $this->_account->from . ' <' . $this->_account->email . '>',
            'days'                  => 7,
            'enabled'               => TRUE,
            'reason'                => 'unittest vacation message',
            'mime'                  => '',
        );
        
        $this->_sieveTestHelper($vacationData);
        
        // check if script was activated
        $activeScriptName = Felamimail_Controller_Sieve::getInstance()->getActiveScriptName($this->_account->getId());
        $this->assertEquals($this->_testSieveScriptName, $activeScriptName);
        $updatedAccount = Felamimail_Controller_Account::getInstance()->get($this->_account->getId());
        $this->assertTrue((bool) $updatedAccount->sieve_vacation_active);
        
        $result = $this->_json->getVacation($this->_account->getId());

        $this->assertEquals($this->_account->email, $result['addresses'][0]);
        unset($result['addresses']);
        $this->assertEquals($vacationData, $result);
    }
    
    /**
     * test mime vacation sieve script
     */
    public function testMimeVacation()
    {
        $vacationData = array(
            'id'                    => $this->_account->getId(),
            'subject'               => 'unittest vacation subject',
            'from'                  => $this->_account->from . ' <' . $this->_account->email . '>',
            'days'                  => 7,
            'enabled'               => TRUE,
            'reason'                => "\n<html><body><h1>unittest vacation message</h1></body></html>",
            'mime'                  => NULL,
        );
        
        $_sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());
        if (! preg_match('/dbmail/i', $_sieveBackend->getImplementation())) {
            $vacationData['mime'] = 'text/html';
        }
        
        $this->_sieveTestHelper($vacationData);
    }
    
    /**
     * test get/set of rules sieve script
     */
    public function testGetSetRules()
    {
        $ruleData = array(array(
            'id'            => 1,
            'action_type'   => Felamimail_Sieve_Rule_Action::FILEINTO, 
            'action_argument' => 'Junk',
            'conditions'    => array(array(
                'test'          => Felamimail_Sieve_Rule_Condition::TEST_ADDRESS,
                'comperator'    => Felamimail_Sieve_Rule_Condition::COMPERATOR_CONTAINS,
                'header'        => 'From',
                'key'           => 'info@example.com',
            )),
            'enabled'       => 1,
        ), array(
            'id'            => 2,
            'action_type'   => Felamimail_Sieve_Rule_Action::FILEINTO, 
            'action_argument' => 'Junk',
            'conditions'    => array(array(
                'test'          => Felamimail_Sieve_Rule_Condition::TEST_ADDRESS,
                'comperator'    => Felamimail_Sieve_Rule_Condition::COMPERATOR_CONTAINS,
                'header'        => 'From',
                'key'           => 'info@example.org',
            )),
            'enabled'       => 0,
        ));
        
        $this->_sieveTestHelper($ruleData);
        
        // check getRules
        $result = $this->_json->getRules($this->_account->getId());
        $this->assertEquals($result['totalcount'], count($ruleData));
    }
    
    /************************ protected functions ****************************/
    
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
        $folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($this->_account->getId(), $_name);
        
        return $folder;
    }

    /**
     * get message data
     *
     * @return array
     */
    protected function _getMessageData()
    {
        return array(
            'account_id'=> $this->_account->getId(),
            'subject'   => 'test',
            'to'        => array('unittest@tine20.org'),
            'body'      => 'aaaaaä <br>',
            //'flags'     => array('\Answered'),
            'headers'   => array('X-Tine20TestMessage' => 'jsontest'),
        );
    }

    /**
     * send message and return message array
     *
     * @return array
     */
    protected function _sendMessage()
    {
        $messageToSend = $this->_getMessageData();
        $returned = $this->_json->saveMessage($messageToSend);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder); 
        
        //sleep(10);
        $result = $this->_getMessages();
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
        $folder = Felamimail_Controller_Cache_Message::getInstance()->updateCache($folder, 10);
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
     */
    protected function _sieveTestHelper($_sieveData)
    {
        $this->_oldActiveSieveScriptName = Felamimail_Controller_Sieve::getInstance()->getActiveScriptName($this->_account->getId());
        
        // use another name for test script
        $this->_testSieveScriptName = 'Felamimail_Unittest';
        Felamimail_Controller_Sieve::getInstance()->setScriptName($this->_testSieveScriptName);
        
        // check which save fn to use
        if (array_key_exists('reason', $_sieveData)) {
            $resultSet = $this->_json->saveVacation($_sieveData);
            $this->assertEquals($this->_account->email, $resultSet['addresses'][0]);
            
            $_sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());
            if (preg_match('/dbmail/i', $_sieveBackend->getImplementation())) {
                $_sieveData['reason'] = 'unittest vacation message';
            }
            
            $this->assertContains($_sieveData['reason'], $resultSet['reason']);
            $this->assertEquals($_sieveData['subject'], $resultSet['subject']);
            
        } else if (array_key_exists('action_type', $_sieveData[0])) {
            $resultSet = $this->_json->saveRules($this->_account->getId(), $_sieveData);
            $this->assertEquals($_sieveData, $resultSet);
        }
    }
}
