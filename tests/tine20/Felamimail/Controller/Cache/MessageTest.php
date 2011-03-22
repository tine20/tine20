<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Felamimail_Controller_Cache_MessageTest::main');
}

/**
 * Test class for Felamimail_Controller_Cache_*
 */
class Felamimail_Controller_Cache_MessageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Felamimail_Controller_Cache_Message
     */
    protected $_controller = NULL;
    
    /**
     * @var Felamimail_Backend_Imap
     */
    protected $_imap = NULL;
    
    /**
     * @var Felamimail_Model_Account
     */
    protected $_account = NULL;
    
    /**
     * @var Felamimail_Model_Folder
     */
    protected $_folder = NULL;
    
    /**
     * 
     * @var Felamimail_Controller_MessageTest
     */
    protected $_emailTestClass;
    
    /**
     * name of the folder to use for tests
     * @var string
     */
    protected $_testFolderName = 'Junk';
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Felamimail Message Cache Controller Tests');
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
        
        // init controller and imap backend
        $this->_controller = Felamimail_Controller_Cache_Message::getInstance();
        $this->_imap = Felamimail_Backend_ImapFactory::factory($this->_account);
        try {
            $this->_imap->createFolder($this->_testFolderName, '', $this->_account->delimiter);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            // exists
        }
        $this->_imap->selectFolder($this->_testFolderName);
        
        // init folder cache and get INBOX
        Felamimail_Controller_Cache_Folder::getInstance()->update($this->_account->getId());
        
        $this->_folder = $this->_getFolder($this->_testFolderName);
        
        $this->_emailTestClass = new Felamimail_Controller_MessageTest();
        $this->_emailTestClass->setup();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        if ($this->_emailTestClass instanceof Felamimail_Controller_MessageTest) {
            $this->_emailTestClass->tearDown();
        }
    }
    
    /**
     * test clear message cache
     *
     */
    public function testClear()
    {
        $this->_controller->clear($this->_folder);
        
        $messageCacheBackend = new Felamimail_Backend_Cache_Sql_Message();
        $count = $messageCacheBackend->searchCountByFolderId($this->_folder->getId());
        
        // check if empty
        $this->assertEquals(0, $count);
        $this->assertEquals(Felamimail_Model_Folder::CACHE_STATUS_EMPTY, $this->_folder->cache_status);
        $this->assertEquals(0, $this->_folder->cache_job_actions_estimate);
    }

    /**
     * test update message cache
     *
     */
    public function testUpdate()
    {
        // update message cache
        $updatedFolder = $this->_controller->updateCache($this->_folder, 30);
        
        // check folder status after update
        if ($updatedFolder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE) {
            $this->assertEquals($updatedFolder->imap_totalcount, $updatedFolder->cache_totalcount, 'totalcounts should be equal');
            $this->assertGreaterThan(-1, Tinebase_DateTime::now()->compare($updatedFolder->cache_timestamp), 'timestamp incorrect'); // later or equals
            $this->assertEquals(0, $updatedFolder->cache_job_actions_done, 'done/estimate wrong');
            $this->assertEquals(0, $updatedFolder->cache_job_actions_estimate, 'done/estimate wrong');
        } else if ($updatedFolder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
            $this->assertEquals(0, $updatedFolder->cache_totalcount, 'cache should be empty');
        } else {
            $this->assertNotEquals($updatedFolder->imap_totalcount, $updatedFolder->cache_totalcount, 'totalcounts should not be equal: ' . print_r($updatedFolder->toArray(), TRUE));
            $this->assertGreaterThan(-1, Tinebase_DateTime::now()->compare($updatedFolder->cache_timestamp), 'timestamp incorrect'); // later or equals
            $this->assertNotEquals(0, $updatedFolder->cache_job_actions_done, 'done wrong');
            $this->assertNotEquals(0, $updatedFolder->cache_job_actions_estimate, 'estimate wrong');
        }
    }
    
    /**
     * test update of message cache counters only
     */
    public function testUpdateCountersOnly()
    {
        // update message cache
        $updatedFolder = $this->_controller->updateCache($this->_folder, 30);
        
        $this->_appendMessage('multipart_alternative.eml', $this->_testFolderName);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage multipart/alternative'
        ));
        
        // update message cache
        $updatedFolder = $this->_controller->updateCache($this->_folder, 0);
        
        foreach($result as $messageUid) {
            $this->_imap->removeMessage($messageUid);
        }
        
        // check folder status after update
        $this->assertEquals(Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE, $updatedFolder->cache_status);
        $this->assertNotEquals($updatedFolder->imap_totalcount, $updatedFolder->cache_totalcount, 'totalcounts should not be equal');
        $this->assertGreaterThan(-1, Tinebase_DateTime::now()->compare($updatedFolder->cache_timestamp), 'timestamp incorrect'); // later or equals
        $this->assertEquals(0, $updatedFolder->cache_job_actions_done, 'done wrong');
        $this->assertNotEquals(0, $updatedFolder->cache_job_actions_estimate, 'estimate wrong');
    }

    /**
     * test message cache unread counter sanitizing
     */
    public function testFolderCounterSanitizing()
    {
        $updatedFolder = $this->_controller->updateCache($this->_folder, 30);
        $unreadcount = $updatedFolder->cache_unreadcount;
        
        // change unreadcount of folder
        Felamimail_Controller_Folder::getInstance()->updateFolderCounter($updatedFolder, array('cache_unreadcount' => '+1'));
        $updatedFolder = $this->_controller->updateCache($this->_folder, 30);
        $this->assertEquals($unreadcount, $updatedFolder->cache_unreadcount, 'unreadcount should have been sanitized');
        
        // add new unread message
        $message = $this->_emailTestClass->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        $this->_imap->clearFlags($message->messageuid, array(Zend_Mail_Storage::FLAG_SEEN));
        $updatedFolder = $this->_controller->updateCache($this->_folder, 30);
        $this->assertEquals($unreadcount+1, $updatedFolder->cache_unreadcount, 'unreadcount should have been increased by 1');
        
        // mark message as seen twice
        Felamimail_Controller_Message::getInstance()->addFlags($message, array(Zend_Mail_Storage::FLAG_SEEN));
        Felamimail_Controller_Message::getInstance()->addFlags($message, array(Zend_Mail_Storage::FLAG_SEEN));
        $updatedFolder = $this->_controller->updateCache($this->_folder, 30);
        $this->assertEquals($unreadcount, $updatedFolder->cache_unreadcount, 'unreadcount should be the same as before');
    }    
    
    /**
     * get folder
     *
     * @return Felamimail_Model_Folder
     */
    protected function _getFolder($_folderName = null)
    {
        $folderName = ($_folderName !== null) ? $_folderName : $this->_testFolderName;
        
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'globalname', 'operator' => 'equals', 'value' => '',),
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())
        ));
        $result = Felamimail_Controller_Folder::getInstance()->search($filter);
        $folder = $result->filter('localname', $folderName)->getFirstRecord();
        if (empty($folder)) {
            print_r($result->toArray()); 
            throw new Exception('folder not found');
        }

        return $folder;
    }
    
    /**
     * append message (from given filename) to folder
     *
     * @param string $_filename
     * @param string $_folder
     */
    protected function _appendMessage($_filename, $_folder)
    {
        $mailAsString = file_get_contents(dirname(dirname(dirname(__FILE__))) . '/files/' . $_filename);
        Felamimail_Backend_ImapFactory::factory($this->_account->getId())
            ->appendMessage($mailAsString, $_folder);
    }
    
    /**
     * test flag update
     */
    public function testUpdateFlags() 
    {
        $message = $this->_emailTestClass->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        // appended messages already have the SEEN flag
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags), 'SEEN flag not found: ' . print_r($message->flags, TRUE));
        // add another flag
        Felamimail_Controller_Message::getInstance()->addFlags($message, Zend_Mail_Storage::FLAG_ANSWERED);
        
        while (! isset($updatedFolder) || $updatedFolder->cache_status === Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE) {
            $updatedFolder = $this->_controller->updateCache($this->_folder, 30);
        }
        
        // clear/add flag on imap
        $this->_imap->clearFlags($message->messageuid, array(Zend_Mail_Storage::FLAG_SEEN));
        $this->_imap->addFlags($message->messageuid, array(Zend_Mail_Storage::FLAG_FLAGGED, Zend_Mail_Storage::FLAG_DRAFT));
        
        $this->_controller->updateFlags($updatedFolder);
        
        $cachedMessage = Felamimail_Controller_Message::getInstance()->get($message->getId());
        $this->assertTrue(! in_array(Zend_Mail_Storage::FLAG_SEEN, $cachedMessage->flags),  'SEEN flag found: ' .           print_r($cachedMessage->flags, TRUE));
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_FLAGGED, $cachedMessage->flags), 'FLAGGED flag not found: ' .    print_r($cachedMessage->flags, TRUE));
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_DRAFT, $cachedMessage->flags),   'DRAFT flag not found: ' .      print_r($cachedMessage->flags, TRUE));
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_ANSWERED, $cachedMessage->flags),'ANSWERED flag not found: ' .   print_r($cachedMessage->flags, TRUE));
    }
}
