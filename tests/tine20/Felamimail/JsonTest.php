<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
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
        
        $this->_json = new Felamimail_Frontend_Json();        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->_createdFolders as $folderName) {
            Felamimail_Controller_Folder::getInstance()->delete($this->_account->getId(), $folderName);
        }
        
        if (! empty($this->_foldersToClear)) {
            $imap = Felamimail_Backend_ImapFactory::factory($this->_account);
            
            foreach ($this->_foldersToClear as $folderName) {
                // delete test messages from given folders on imap server (search by special header)
                $imap->selectFolder($folderName);
                $result = $imap->search(array(
                    'HEADER X-Tine20TestMessage jsontest'
                ));
                //print_r($result);
                foreach ($result as $messageUid) {
                    $imap->removeMessage($messageUid);
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
     *
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
     *
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
     * try to create a folder
     */
    public function testCreateFolder()
    {
        $result = $this->_json->addFolder('test', $this->_testFolderName, $this->_account->getId());
        $this->_createdFolders[] = $this->_testFolderName . $this->_account->delimiter . 'test';
        
        $this->assertEquals('test', $result['localname']);
        $this->assertEquals($this->_testFolderName . $this->_account->delimiter . 'test', $result['globalname']);
        $this->assertEquals(Felamimail_Model_Folder::CACHE_STATUS_EMPTY, $result['cache_status']);
    }

    /**
     * testUpdateFolderCache
     *
     */
    public function testUpdateFolderCache()
    {
        $imap = Felamimail_Backend_ImapFactory::factory($this->_account);
        
        // create folder directly on imap server
        $imap->createFolder('test', $this->_testFolderName, $this->_account->delimiter);
        // if something goes wrong, we need to delete this folder in tearDown
        $this->_createdFolders[] = $this->_testFolderName . $this->_account->delimiter . 'test';
        
        // update cache and check if folder is found
        $result = $this->_json->updateFolderCache($this->_account->getId(), $this->_testFolderName);
        $testfolder = $result[0];
        $this->assertGreaterThan(0, count($result));
        $this->assertEquals($this->_testFolderName . $this->_account->delimiter . 'test', $testfolder['globalname']);
        
        // delete folder directly on imap server
        $imap->removeFolder($this->_testFolderName . $this->_account->delimiter . 'test');
        $this->_createdFolders = array();
        
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
     *
     */
    public function testSearchAccounts()
    {
        $results = $this->_json->searchAccounts(array());

        $this->assertGreaterThan(0, $results['totalcount']);
        $default = array();
        foreach ($results['results'] as $result) {
            if ($result['name'] == 'unittest@tine20.org') {
                $default = $result;
            }
        }
        $this->assertTrue(! empty($default));
        $this->assertEquals('mail.metaways.net', $result['host']);
        $this->assertEquals('mail01.metaways.net', $result['sieve_hostname']);
    }
    
    /**
     * test create / get / delete of account
     *
     */
    public function testCreateChangeDeleteAccount() 
    {
        // save & resolve
        $account = $this->_json->saveAccount($this->_getAccountData());
        
        $accountRecord = new Felamimail_Model_Account($account, TRUE);
        $accountRecord->resolveCredentials(FALSE);
        
        // checks
        $this->assertEquals(Tinebase_Core::getConfig()->imap->password, $accountRecord->password);
        $this->assertEquals('mail.metaways.net', $account['host']);
        
        // change credentials & resolve
        $this->_json->changeCredentials($account['id'], $account['user'], 'neuespasswort');
        $account = $this->_json->getAccount($account['id']);
        
        $accountRecord = new Felamimail_Model_Account($account, TRUE);
        $accountRecord->resolveCredentials(FALSE);
        
        // checks
        $this->assertEquals('neuespasswort', $accountRecord->password);
        
        // delete
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
            $this->assertEquals($result['imap_uidnext'], $result['cache_uidnext'], 'uidnext values should be equal');
            $this->assertEquals($result['imap_totalcount'], $result['cache_totalcount'], 'totalcounts should be equal');
        } else if ($result['cache_status'] == Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE) {
            $this->assertEquals($result['imap_uidnext'], $result['cache_uidnext'], 'uidnext values should be equal');
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
        $this->assertEquals($message['to'],       $messageToSend['to'][0], 'recipient not found');
        
        // check if email note has been added to contact(s)
        $contact = Addressbook_Controller_Contact::getInstance()->get($contact->getId());
        $emailNoteType = Tinebase_Notes::getInstance()->getNoteTypeByName('email');
        
        // check / delete notes
        $emailNoteIds = array();
        foreach ($contact->notes as $note) {
            if ($note->note_type_id == $emailNoteType->getId()) {
                $this->assertEquals(1, preg_match('/' . $messageToSend['subject'] . '/', $note->note));
                $this->assertEquals(Tinebase_Core::getUser()->getId(), $note->created_by);
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
        $imap = Felamimail_Backend_ImapFactory::factory($this->_account);
        $imap->removeMessage($message['messageuid']);
        Tinebase_Core::getCache()->clean();
        $this->setExpectedException('Felamimail_Exception_IMAPMessageNotFound');
        $message = $this->_json->getMessage($message['id']);
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
        $this->assertTrue($inboxBefore->cache_unreadcount - 1 == $inboxAfter->cache_unreadcount);
        
        $message = $this->_json->getMessage($message['id']);
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_SEEN, $message['flags']));
        
        $this->_json->clearFlags(array($message['id']), Zend_Mail_Storage::FLAG_SEEN);
        
        $message = $this->_json->getMessage($message['id']);
        $this->assertFalse(in_array(Zend_Mail_Storage::FLAG_SEEN, $message['flags']));

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
    
    /*********************** sieve tests ****************************/
    
    /**
     * set and get vacation sieve script
     */
    public function testGetSetVacation()
    {
        $vacationData = array(
            'id'                    => $this->_account->getId(),
            'addresses'             => array(),
            'subject'               => 'unittest vacation subject',
            'from'                  => $this->_account->email,
            'days'                  => 7,
            'enabled'               => TRUE,
            'reason'                => 'unittest vacation message',
            'mime'                  => '',
        );
        
        $this->_vacationTestHelper($vacationData);
        
        // check if script was activated
        $activeScriptName = Felamimail_Controller_Sieve::getInstance()->getActiveScriptName($this->_account->getId());
        $this->assertEquals($this->_testSieveScriptName, $activeScriptName);
        
        $result = $this->_json->getVacation($this->_account->getId());

        unset($result['vacationObject']);
        $this->assertEquals($vacationData, $result);
    }
    
    /**
     * test mime vacation sieve script
     */
    public function testMimeVacation()
    {
        $vacationData = array(
            'id'                    => $this->_account->getId(),
            'addresses'             => array(),
            'subject'               => 'unittest vacation subject',
            'from'                  => $this->_account->email,
            'days'                  => 7,
            'enabled'               => TRUE,
            'reason'                => '<html><body><h1>unittest vacation message</h1></body></html>',
            'mime'                  => 'text/html',
        );
        
        $this->_vacationTestHelper($vacationData);
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
            'from'      => $this->_account->getId(),
            'subject'   => 'test',
            'to'        => array('unittest@tine20.org'),
            'body'      => 'aaaaaä <br>',
            //'flags'     => array('\Answered'),
            'headers'   => array('X-Tine20TestMessage' => 'jsontest'),
        );
    }

    /**
     * get account data
     *
     * @return array
     */
    protected function _getAccountData()
    {
        $account = Tinebase_Core::getConfig()->imap->toArray(); 
        $account['email'] = $account['user'];
        
        return $account;
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
        $message = array(); 
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $messageToSend['subject']) {
                $message = $mail;
            }
        }
        $this->assertTrue(! empty($message), 'Sent message not found.');
        
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
     * vacation test helper
     * 
     * @param array $_vacationData
     */
    protected function _vacationTestHelper($_vacationData)
    {
        $this->_oldActiveSieveScriptName = Felamimail_Controller_Sieve::getInstance()->getActiveScriptName($this->_account->getId());
        
        // use another name for test script
        $this->_testSieveScriptName = 'Felamimail_Unittest';
        Felamimail_Controller_Sieve::getInstance()->setScriptName($this->_testSieveScriptName);
        
        $resultSet = $this->_json->saveVacation($_vacationData);
        
        unset($resultSet['vacationObject']);
        $this->assertEquals($_vacationData, $resultSet);
    }
}
