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
 * @todo        refactor tests (delete all mails first, write one test mail to unittest account, ...)
 * @todo        add tests for attachments
 * @todo        use testmails from files/ dir
 * @todo        activate tests again with caching
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
     * test search messages
     *
     */
    public function testSearchMessages()
    {
        // get inbox folder id
        Felamimail_Controller_Folder::getInstance()->getSubFolders();
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = $folderBackend->getByBackendAndGlobalName('default', 'INBOX');
        Felamimail_Controller_Cache::getInstance()->clear($folder->getId());
        
        $filter = $this->_getMessageFilter($folder->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
        
        $this->assertGreaterThan(0, $result['totalcount']);

        $firstMail = $result['results'][0];
        $this->assertEquals('testmail', $firstMail['subject']);
        $this->assertEquals('unittest@tine20.org', $firstMail['to']);
    }
    
    /**
     * try to get a message from imap server (with complete body, attachments, etc)
     *
     * @todo check for correct charset/encoding
     */
    public function testGetMessage()
    {
        $inbox = $this->_getFolder();
        $filter = $this->_getMessageFilter($inbox->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
        
        $firstMail = $result['results'][0];
        
        // get complete message
        $message = $this->_json->getMessage($firstMail['id']);
        
        $this->assertGreaterThan(0, preg_match('/Metaways Infosystems GmbH/', $message['body']));
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
     * test flags
     * 
     */
    public function testSetAndClearFlags()
    {
        $inbox = $this->_getFolder();
        $filter = $this->_getMessageFilter($inbox->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
        $firstMail = $result['results'][0];
        
        // add flag
        $this->_json->setFlag(Zend_Json::encode(array($firstMail['id'])), Zend_Json::encode('\\Flagged'));
        
        // check flags
        $message = $this->_json->getMessage($firstMail['id']);
        $this->assertTrue(preg_match('/\\Flagged/', $message['flags']) > 0);
        
        // remove flag
        $this->_json->clearFlag(Zend_Json::encode(array($firstMail['id'])), Zend_Json::encode('\\Flagged'));
        
        // check flags
        $message = $this->_json->getMessage($firstMail['id']);
        $this->assertTrue(preg_match('/\\Flagged/', $message['flags']) == 0);
    }
    
    /**
     * test send and delete
     * 
     */
    public function testSendAndDeleteMessage()
    {
        /*
        // clear cache and sent folder
        $sent = $this->_getFolder('Sent');
        Felamimail_Controller_Cache::getInstance()->clear($sent->getId());
        Felamimail_Controller_Folder::getInstance()->emptyFolder($sent->getId());
        
        $messageToSend = $this->_getMessageData();
        $returned = $this->_json->saveMessage(Zend_Json::encode($messageToSend));
        
        // check if message is in sent folder
        $filter = $this->_getMessageFilter($sent->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
        //print_r($result);
        
        $this->assertTrue(isset($result['results'][0]));
        $firstMail = $result['results'][0];
        $this->assertEquals($firstMail['subject'],  $messageToSend['subject']);
        $this->assertEquals($firstMail['to'],       $messageToSend['to'][0]);
        //$this->assertEquals($firstMail['body'],     $messageToSend['body']);
        
        // delete message from inbox & sent
        $this->_json->deleteMessages($firstMail['id']);
        
        $sent = $this->_getFolder();
        $filter = $this->_getMessageFilter($sent->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $messageToSend['subject']) {
                $this->_json->deleteMessages($mail['id']);
            }
        }
        */
    }

    /**
     * test reply mail
     * 
     */
    public function testReplyMessage()
    {
        /*
        $inbox = $this->_getFolder();
        $filter = $this->_getMessageFilter($inbox->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
        $firstMail = $result['results'][0];
        
        //print_r($firstMail);
        $messageToSend              = $this->_getMessageData();
        $messageToSend['flags']     = '\\Answered';
        $messageToSend['subject']   = 'Re: ' . $firstMail['subject'];
        $messageToSend['id']        = $firstMail['id'];
        $returned                   = $this->_json->saveMessage(Zend_Json::encode($messageToSend));
        
        //-- delete from sent folder?
        
        // check answered flag and remove it afterwards
        $inbox = $this->_getFolder();
        $filter = $this->_getMessageFilter($inbox->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
        $firstMail = $result['results'][0];
        
        $this->assertTrue(preg_match("/\\\\Answered/", $firstMail['flags']) > 0, 'could not find flag');
        
        //-- check In-Reply-To header
        
        $this->_json->clearFlag(Zend_Json::encode(array($firstMail['id'])), Zend_Json::encode('\\Answered'));
        */
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
}
