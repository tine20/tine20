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
 * @todo        add tests for create/update/delete accounts
 * @todo        add tests for attachments
 * @todo        use testmails from files/ dir
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
    
    /**
     * test search folders
     *
     */
    public function testSearchFolders()
    {
        $filter = $this->_getFolderFilter();
        $result = $this->_json->searchFolders(Zend_Json::encode($filter));
        
        $this->assertEquals(6, $result['totalcount']);
        $expectedFolders = array('Drafts', 'INBOX', 'Junk', 'Sent', 'Templates', 'Trash');
        foreach ($result['results'] as $folder) {
            $this->assertTrue(in_array($folder['localname'], $expectedFolders));
        }
    }
    
    /**
     * test search for accounts and check default account from config
     *
     */
    public function testSearchAccounts()
    {
        $results = $this->_json->searchAccounts('');

        $this->assertGreaterThan(0, $results['totalcount']);
        $default = array();
        foreach ($results['results'] as $result) {
            if ($result['user'] == 'unittest@tine20.org') {
                $default = $result;
            }
        }
        $this->assertTrue(! empty($default));
        $this->assertEquals(143, $result['port']);
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
            Felamimail_Controller_Cache::getInstance()->clear($folder->getId());
            Felamimail_Controller_Folder::getInstance()->emptyFolder($folder->getId());
        }

        $filter = $this->_getMessageFilter($folder->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
        
        $this->assertEquals(0, $result['totalcount']);
    }

    /**
     * test search messages
     *
     */
    public function testSendMessage()
    {
        $messageToSend = $this->_getMessageData();
        $returned = $this->_json->saveMessage(Zend_Json::encode($messageToSend));
        
        //sleep(10);
        
        // check if message is in sent folder
        $sent = $this->_getFolder('Sent');
        $filter = $this->_getMessageFilter($sent->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
        //print_r($result);
        
        $message = array(); 
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $messageToSend['subject']) {
                $message = $mail;
            }
        }
        $this->assertGreaterThan(0, $result['totalcount'], 'folder is empty');
        $this->assertTrue(! empty($message));
        $this->assertEquals($message['subject'],  $messageToSend['subject']);
        $this->assertEquals($message['to'],       $messageToSend['to'][0]);
        
        // delete message from inbox & sent
        $this->_json->deleteMessages($message['id']);
        $this->_deleteMessage($messageToSend['subject']);
    }
    
    /**
     * try to get a message from imap server (with complete body, attachments, etc)
     *
     * @todo check for correct charset/encoding
     */
    public function testGetMessage()
    {
        $message = $this->_sendMessage();     
        
        // get complete message
        $message = $this->_json->getMessage($message['id']);
        
        // check
        $this->assertGreaterThan(0, preg_match('/aaaaaa/', $message['body']));
        
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
        $this->_json->setFlag(Zend_Json::encode(array($message['id'])), Zend_Json::encode('\\Flagged'));
        
        // check flags
        $message = $this->_json->getMessage($message['id']);
        $this->assertTrue(preg_match('/\\Flagged/', $message['flags']) > 0);
        
        // remove flag
        $this->_json->clearFlag(Zend_Json::encode(array($message['id'])), Zend_Json::encode('\\Flagged'));
        
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
        
        $replyMessage              = $this->_getMessageData();
        $replyMessage['flags']     = '\\Answered';
        $replyMessage['subject']   = 'Re: ' . $message['subject'];
        $replyMessage['id']        = $message['id'];
        $returned                   = $this->_json->saveMessage(Zend_Json::encode($replyMessage));
        
        $inbox = $this->_getFolder();
        $filter = $this->_getMessageFilter($inbox->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
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
        $this->assertTrue(! empty($replyMessageFound));
        
        // check answered flag
        $this->assertTrue(preg_match("/\\\\Answered/", $originalMessage['flags']) > 0, 'could not find flag');
        
        // delete
        $this->_deleteMessage($message['subject']);
        $this->_deleteMessage($replyMessage['subject']);
    }
    
    /**
     * test move
     * 
     * @todo implement
     */
    public function testMoveMessage()
    {
        
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
            'field' => 'globalName', 'operator' => 'equals', 'value' => ''
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
        Felamimail_Controller_Folder::getInstance()->getSubFolders();
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = $folderBackend->getByBackendAndGlobalName('default', $_name);
        
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
            'from'      => 'default',
            'subject'   => 'test',
            'to'        => array('unittest@tine20.org'),
            'body'      => 'aaaaaa <br>',
            //'flags'     => array('\Answered')
        );
    }

    /**
     * delete message
     *
     * @param string $_subject
     */
    protected function _deleteMessage($_subject) 
    {
        $inbox = $this->_getFolder();
        $filter = $this->_getMessageFilter($inbox->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
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
        $returned = $this->_json->saveMessage(Zend_Json::encode($messageToSend));
        
        //sleep(10);
        
        $inbox = $this->_getFolder();
        $filter = $this->_getMessageFilter($inbox->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
        
        $message = array(); 
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $messageToSend['subject']) {
                $message = $mail;
            }
        }
        $this->assertTrue(! empty($message));
        
        return $message;
    }
}
