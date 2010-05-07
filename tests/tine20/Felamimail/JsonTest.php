<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id:JsonTest.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        add tests for attachments
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
        $expectedFolders = array('INBOX', 'Drafts', 'Sent', 'Templates', 'Junk', 'Trash');
        
        foreach ($expectedFolders as $index => $folderName) {
            $this->assertEquals($folderName, $result['results'][$index]['localname']);
        }
    }
    
    /**
     * clear sent, inbox & trash
     *
     */
    public function testClearFolders()
    {
        $foldersToClear = array('Sent', 'INBOX', 'Trash');
        
        foreach ($foldersToClear as $folderName) {
            $folder = $this->_getFolder($folderName);
            $folder = Felamimail_Controller_Folder::getInstance()->emptyFolder($folder->getId());

            $filter = $this->_getMessageFilter($folder->getId());
            $result = $this->_json->searchMessages($filter, '');
            
            $this->assertEquals(0, $result['totalcount'], 'Found too many messages in folder ' . $folderName);
            $this->assertEquals(0, $folder->cache_totalcount);
        }
    }

    /**
     * testUpdateFolderCache
     *
     */
    public function testUpdateFolderCache()
    {
        $imap = Felamimail_Backend_ImapFactory::factory($this->_account);
        
        // create folder directly on imap server
        $imap->createFolder('test', 'INBOX', $this->_account->delimiter);
        $this->_foldersToDelete[] = 'INBOX/test';
        
        // update cache and check if folder is found
        $result = $this->_json->updateFolderCache($this->_account->getId(), 'INBOX');
        $this->assertGreaterThan(0, count($result));
        $this->assertEquals('INBOX/test', $result[0]['globalname']);
        
        // delete folder directly on imap server
        $imap->removeFolder('INBOX/test');
        
        // update cache and check if folder is deleted
        $result = $this->_json->updateFolderCache($this->_account->getId(), 'INBOX');
        $this->assertEquals(0, count($result));
    }
    
    /**
     * testUpdateFolderStatus
     *
     */
    public function testUpdateFolderStatus()
    {
        $result = $this->_json->updateFolderStatus($this->_account->getId(), '');
        $inbox = array();
        foreach ($result['results'] as $folder) {
            if ($folder['globalname'] == 'INBOX') {
                $inbox = $folder;
                break;
            }
        }
        $this->assertTrue(! empty($inbox), 'inbox not found');
        
        // check if single folder returns the same result
        $result = $this->_json->updateFolderStatus($this->_account->getId(), array($inbox['id']));
        // timestamps can be different
        unset($inbox['imap_timestamp']);
        unset($result['results'][0]['imap_timestamp']);
        $this->assertEquals($inbox, $result['results'][0]);
        
        // save some values and send mail
        $oldTotalCount = $inbox['imap_totalcount'];
        
        $messageToSend = $this->_getMessageData();
        $message = $this->_json->saveMessage($messageToSend);
        
        // get inbox status again
        $result = $this->_json->updateFolderStatus($this->_account->getId(), '');
        $inbox = array();
        foreach ($result['results'] as $folder) {
            if ($folder['globalname'] == 'INBOX') {
                $inbox = $folder;
                break;
            }
        }
        
        // checks
        $this->assertEquals($oldTotalCount+1, $inbox['imap_totalcount']);
        $this->assertEquals(Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE, $inbox['cache_status']);
        
        // delete message from inbox
        $this->_deleteMessage($messageToSend['subject']);
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
        $this->assertEquals(143, $result['port']);
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
        
        // get inbox status
        $result = $this->_json->updateFolderStatus($this->_account->getId(), array());
        $inbox = array();
        foreach ($result['results'] as $folder) {
            if ($folder['globalname'] == 'INBOX') {
                $inbox = $folder;
                break;
            }
        }
        $this->assertTrue(! empty($inbox), 'inbox not found');
        
        // update message cache and check result
        $result = $this->_json->updateMessageCache($inbox['id'], 10);
        
        $this->assertEquals(Felamimail_Model_Folder::CACHE_STATUS_COMPLETE, $result['cache_status'], 'cache status should be complete');
        $this->assertEquals($inbox['imap_uidnext'], $result['cache_uidnext'], 'uidnext values should be equal');
        $this->assertEquals($inbox['imap_totalcount'], $result['cache_totalcount'], 'totalcounts should be equal');
                
        // delete message from inbox and clear cache
        $this->_deleteMessage($messageToSend['subject']);
        Felamimail_Controller_Cache_Message::getInstance()->clear($inbox['id']);
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
        $contact->email = 'unittest@tine20.org';
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);

        // send email
        $messageToSend = $this->_getMessageData();
        $messageToSend['note'] = 1;
        //print_r($messageToSend);
        $returned = $this->_json->saveMessage($messageToSend);
        
        //sleep(10);
        
        // check if message is in sent folder
        $result = $this->_getMessages('Sent');
        //print_r($result);
        
        $message = array(); 
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $messageToSend['subject']) {
                $message = $mail;
            }
        }
        //print_r($message);
        $this->assertGreaterThan(0, $result['totalcount'], 'folder is empty');
        $this->assertTrue(! empty($message));
        $this->assertEquals($message['subject'],  $messageToSend['subject']);
        $this->assertEquals($message['to'],       $messageToSend['to'][0], 'recipient not found');
        
        // delete message from inbox & sent
        $this->_json->deleteMessages($message['id']);
        $this->_deleteMessage($messageToSend['subject']);

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
        
        // delete
        $this->_deleteMessage($message['subject']);
    }
    
    /**
     * test flags
     * 
     */
    public function testSetAndClearFlags()
    {
        $message = $this->_sendMessage();
        
        // add flag
        $this->_json->setFlag(array($message['id']), '\\Flagged');
        
        // check flags
        $message = $this->_json->getMessage($message['id']);
        $this->assertTrue(preg_match('/\\Flagged/', $message['flags']) > 0);
        
        // remove flag
        $this->_json->clearFlag(array($message['id']), '\\Flagged');
        
        // check flags
        $message = $this->_json->getMessage($message['id']);
        $this->assertTrue(preg_match('/\\Flagged/', $message['flags']) == 0);
        
        // delete
        $this->_deleteMessage($message['subject']);
    }
    
    /**
     * test reply mail
     * 
     * @todo check In-Reply-To header
     */
    public function testReplyMessage()
    {
        $message = $this->_sendMessage();
        
        $replyMessage               = $this->_getMessageData();
        $replyMessage['flags']      = '\\Answered';
        $replyMessage['subject']    = 'Re: ' . $message['subject'];
        $replyMessage['original_id']= $message['id'];
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
        $this->assertTrue(preg_match("/\\\\Answered/", $originalMessage['flags']) > 0, 'could not find flag');
        
        // delete
        $this->_deleteMessage($message['subject']);
        $this->_deleteMessage($replyMessage['subject']);
    }
    
    /**
     * test move
     */
    public function testMoveMessage()
    {
        $message = $this->_sendMessage();
        $result = $this->_json->updateFolderStatus($this->_account->getId(), '');
        $inbox = array();
        foreach ($result['results'] as $folder) {
            if ($folder['globalname'] == 'INBOX') {
                $inbox = $folder;
                break;
            }
        }
        
        // move
        $drafts = $this->_getFolder('Drafts');
        $result = $this->_json->moveMessages(array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'])
        )), $drafts->getId());
        
        // check if counts were decreased correctly
        $this->assertEquals($inbox['cache_totalcount'] - 1, $result['cache_totalcount']);
        $this->assertEquals($inbox['cache_unreadcount'] - 1, $result['cache_unreadcount']);
        
        $result = $this->_getMessages('Drafts');
        $movedMessage = array();
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $message['subject']) {
                $movedMessage = $mail;
            }
        }
        $this->assertTrue(! empty($movedMessage), 'moved message not found');
        
        // delete
        $this->_deleteMessage($message['subject'], 'Drafts');
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
        return array(array(
            'field' => 'folder_id', 'operator' => 'equals', 'value' => $_folderId
        ));
    }
    
    /**
     * get mailbox
     *
     * @param string $_name
     * @return Felamimail_Model_Folder
     */
    protected function _getFolder($_name = 'INBOX')
    {
        Felamimail_Controller_Cache_Folder::getInstance()->update($this->_account->getId());
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = $folderBackend->getByBackendAndGlobalName($this->_account->getId(), $_name);
        
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
            //'flags'     => array('\Answered')
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
     * delete message
     *
     * @param string $_subject
     */
    protected function _deleteMessage($_subject, $_folderName = 'INBOX') 
    {
        $inbox = $this->_getFolder($_folderName);
        $filter = $this->_getMessageFilter($inbox->getId());
        $result = $this->_json->searchMessages($filter, '');
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $_subject) {
                $this->_json->deleteMessages($mail['id']);
            }
        }
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
        $folder = Felamimail_Controller_Cache_Folder::getInstance()->updateStatus($this->_account->getId(), NULL, $folder->getId())->getFirstRecord();
        $i = 0;
        while ($folder->cache_status != Felamimail_Model_Folder::CACHE_STATUS_COMPLETE && $i < 10) {
            $folder = Felamimail_Controller_Cache_Message::getInstance()->update($folder, 10);
            $i++;
        }
        $result = $this->_json->searchMessages($filter, '');
        //print_r($result);
        
        return $result;
    }
}
