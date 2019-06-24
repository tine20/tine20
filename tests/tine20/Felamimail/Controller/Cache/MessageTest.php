<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
     * delete messages with this header in tearDown
     * 
     * @var string
     */
    protected $_headerValueToDelete = NULL;
    
    /**
     * test user
     * 
     * @var Tinebase_Model_FullUser
     */
    protected $_testUser = null;
    
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
        
        $this->_testUser   = Tinebase_Core::getUser();
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
        
        if ($this->_headerValueToDelete !== NULL) {
            $result = $this->_imap->search(array(
                $this->_headerValueToDelete
            ));
            foreach($result as $messageUid) {
                $this->_imap->removeMessage($messageUid);
            }
        }
        
        // reset user if changed
        if (is_object($this->_testUser) && Tinebase_Core::getUser()->getId() !== $this->_testUser->getId()) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_testUser);
        }
    }
    
    /**
     *  test if there is a user logged in
     */
    public function testUser()
    {
        $this->assertTrue(is_object($this->_testUser), 'attribute user is of type ' . gettype($this->_testUser));
        $this->assertTrue(is_object(Tinebase_Core::get(Tinebase_Core::USER)), 'core user is of type ' . gettype($this->_testUser));
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
        $this->assertEquals(0, $this->_folder->cache_job_actions_est);
    }

    /**
     * test update message cache
     *
     */
    public function testUpdateCache()
    {
        // update message cache
        $updatedFolder = $this->_controller->updateCache($this->_folder, 30, 1);
        
        // check folder status after update
        if ($updatedFolder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE) {
            $this->assertEquals($updatedFolder->imap_totalcount, $updatedFolder->cache_totalcount, 'totalcounts should be equal');
            $this->assertGreaterThan(-1, Tinebase_DateTime::now()->compare($updatedFolder->cache_timestamp), 'timestamp incorrect'); // later or equals
            $this->assertEquals(0, $updatedFolder->cache_job_actions_done, 'done/estimate wrong');
            $this->assertEquals(0, $updatedFolder->cache_job_actions_est, 'done/estimate wrong');
        } else if ($updatedFolder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_EMPTY) {
            $this->assertEquals(0, $updatedFolder->cache_totalcount, 'cache should be empty');
        } else {
            $this->assertNotEquals($updatedFolder->imap_totalcount, $updatedFolder->cache_totalcount, 'totalcounts should not be equal: ' . print_r($updatedFolder->toArray(), TRUE));
            $this->assertGreaterThan(-1, Tinebase_DateTime::now()->compare($updatedFolder->cache_timestamp), 'timestamp incorrect'); // later or equals
            $this->assertNotEquals(0, $updatedFolder->cache_job_actions_done, 'done wrong');
            $this->assertNotEquals(0, $updatedFolder->cache_job_actions_est, 'estimate wrong');
        }
    }

    /**
     * test update message cache, remove oldest mail on imap + add new
     */
    public function testUpdateCacheAgainRemoveOldest()
    {
        $this->_updateAgainHelper('oldest');
    }
    
    /**
     * test update message cache again, remove latest mail on imap + add new
     */
    public function testUpdateCacheAgainRemoveLatest()
    {
        $this->_updateAgainHelper('latest');
    }
    
    /**
     * helper function for update again tests
     */
    protected function _updateAgainHelper($_mode)
    {
        // add three messages to folder
        for($i = 0; $i < 3; $i++) {
            $this->_appendMessage('multipart_alternative.eml', $this->_testFolderName);
        }
        $this->_headerValueToDelete = 'HEADER X-Tine20TestMessage multipart/alternative';
        
        // update message cache
        $loopCount = 1;
        do {
            $updatedFolder = $this->_controller->updateCache($this->_folder, 10, 1);
            $loopCount++;
        } while ($updatedFolder->cache_status != Felamimail_Model_Folder::CACHE_STATUS_COMPLETE && $loopCount < 10);
        
        $this->assertGreaterThan(0, $updatedFolder->cache_totalcount);
        $this->assertNotEquals(10, $loopCount, 'should complete cache update with < 10 iterations.');
        
        $result = $this->_imap->search(array(
            $this->_headerValueToDelete
        ));
        
        $this->_imap->selectFolder($this->_folder->globalname);
        
        if ($_mode == 'oldest') {
            // now lets delete one message from folder and add another one
            $this->_imap->removeMessage($result[0]);
            $this->_appendMessage('multipart_alternative.eml', $this->_testFolderName);
            $expected = $updatedFolder->cache_totalcount;
        } else {
            // just delete the newest message
            $this->_imap->removeMessage($result[count($result) - 1]);
            $expected = $updatedFolder->cache_totalcount - 1;
        }
        
        $updatedFolderAgain = $this->_controller->updateCache($this->_folder, 30, 1);
        $this->assertEquals($expected, $updatedFolderAgain->cache_totalcount);
    }

    /**
     * test update of message cache counters only
     */
    public function testUpdateCountersOnly()
    {
        // update message cache
        $this->_controller->updateCache($this->_folder, 30, 1);
        
        $this->_appendMessage('multipart_alternative.eml', $this->_testFolderName);
        $this->_headerValueToDelete = 'HEADER X-Tine20TestMessage multipart/alternative';
        
        // update message cache + check folder status after update
        $updatedFolder = $this->_controller->updateCache($this->_folder, 0);
        $this->assertEquals(Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE, $updatedFolder->cache_status);
        $this->assertNotEquals($updatedFolder->imap_totalcount, $updatedFolder->cache_totalcount, 'totalcounts should not be equal');
        $this->assertGreaterThan(-1, Tinebase_DateTime::now()->compare($updatedFolder->cache_timestamp), 'timestamp incorrect'); // later or equals
        $this->assertEquals(0, $updatedFolder->cache_job_actions_done, 'done wrong');
        $this->assertNotEquals(0, $updatedFolder->cache_job_actions_est, 'estimate wrong');
    }

    /**
     * test message cache unread counter sanitizing
     */
    public function testFolderCounterSanitizing()
    {
        $updatedFolder = $this->_controller->updateCache($this->_folder, 30, 1);
        $unreadcount = $updatedFolder->cache_unreadcount;
        
        // change unreadcount of folder
        Felamimail_Controller_Folder::getInstance()->updateFolderCounter($updatedFolder, array('cache_unreadcount' => '+1'));
        $updatedFolder = $this->_controller->updateCache($this->_folder, 30, 1);
        $this->assertEquals($unreadcount, $updatedFolder->cache_unreadcount, 'unreadcount should have been sanitized');
        
        // add new unread message
        $this->_imap->selectFolder($this->_folder->globalname);
        $message = $this->_emailTestClass->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        $this->_imap->clearFlags($message->messageuid, array(Zend_Mail_Storage::FLAG_SEEN));
        $updatedFolder = $this->_controller->updateCache($this->_folder, 30, 1);
        $this->assertEquals($unreadcount+1, $updatedFolder->cache_unreadcount, 'unreadcount should have been increased by 1');
        
        // mark message as seen twice
        $this->_imap->selectFolder($this->_folder->globalname);
        Felamimail_Controller_Message_Flags::getInstance()->addFlags($message, array(Zend_Mail_Storage::FLAG_SEEN));
        Felamimail_Controller_Message_Flags::getInstance()->addFlags($message, array(Zend_Mail_Storage::FLAG_SEEN));
        $updatedFolder = $this->_controller->updateCache($this->_folder, 30, 1);
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
        Felamimail_Controller_Message_Flags::getInstance()->addFlags($message, Zend_Mail_Storage::FLAG_ANSWERED);
        
        while (! isset($updatedFolder) || $updatedFolder->cache_status === Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE) {
            $updatedFolder = $this->_controller->updateCache($this->_folder, 30, 1);
        }
        
        $this->_imap->selectFolder($this->_folder->globalname);
        
        // clear/add flag on imap
        $this->_imap->clearFlags($message->messageuid, array(Zend_Mail_Storage::FLAG_SEEN));
        $flagsToAdd = array(Zend_Mail_Storage::FLAG_FLAGGED, Zend_Mail_Storage::FLAG_DRAFT, Zend_Mail_Storage::FLAG_PASSED);
        try {
            $this->_imap->addFlags($message->messageuid, $flagsToAdd);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            // some imap servers (dbmail, ...) do not support PASSED flag
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zmse->getMessage());
            $this->_imap->addFlags($message->messageuid,  array(Zend_Mail_Storage::FLAG_FLAGGED, Zend_Mail_Storage::FLAG_DRAFT));
        }
        
        $updatedFolder = $this->_controller->updateFlags($updatedFolder);
        
        $this->_imap->selectFolder($updatedFolder->globalname);
        $cachedMessage = Felamimail_Controller_Message::getInstance()->get($message->getId());
        $this->assertTrue(! in_array(Zend_Mail_Storage::FLAG_SEEN, $cachedMessage->flags),  'SEEN flag found: ' . print_r($cachedMessage->flags, TRUE));
        
        $expectedFlags = array(Zend_Mail_Storage::FLAG_FLAGGED, Zend_Mail_Storage::FLAG_DRAFT, Zend_Mail_Storage::FLAG_ANSWERED);
        $this->assertEquals(3, count($cachedMessage->flags), 'found too many flags: ' . print_r($cachedMessage->flags, TRUE));
        foreach ($expectedFlags as $expectedFlag) {
            $this->assertTrue(in_array($expectedFlag, $cachedMessage->flags), $expectedFlag . ' flag not found: ' . print_r($cachedMessage->flags, TRUE));
        }
        
        $this->_controller->updateFlags($updatedFolder);
        $cachedMessageAgain = Felamimail_Controller_Message::getInstance()->get($message->getId());
        // cached message should not have been updated again
        $this->assertEquals($cachedMessage->timestamp->__toString(), $cachedMessageAgain->timestamp->__toString());
    }

    /**
     * test update folder quota
     */
    public function testUpdateFolderQuota() 
    {
        $folderToTest = $this->_getFolder('INBOX');
        $folderToTest = $this->_controller->updateCache($folderToTest);
        
        $quota = $this->_imap->getQuota('INBOX');
        
        if (empty($quota)) {
            $this->assertEquals(0, $folderToTest->quota_usage);
            $this->assertEquals(0, $folderToTest->quota_limit);
        } else {
            $this->assertEquals($quota['STORAGE']['usage'], $folderToTest->quota_usage);

            // TODO fix this test
            // -> Failed asserting that 2147483648000 matches expected '2048000'.
            // $this->assertEquals($quota['STORAGE']['limit'], $folderToTest->quota_limit);
        }
    }

    /**
     * testGetFolderStatusOfOtherUser
     * 
     * @see 0009408: getFolderStatus must check user accounts
     */
    public function testGetFolderStatusOfOtherUser()
    {
        $this->_appendMessage('multipart_alternative.eml', $this->_testFolderName);
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        Tinebase_Core::set(Tinebase_Core::USER, $sclever);
        
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($this->_getFolder()->getId()))
        ));
        $status = $this->_controller->getFolderStatus($filter);

        // this finds sclevers Junk folder... ok or not ok?
        $this->assertLessThanOrEqual(1, count($status), 'max one folder should be found for update: '
            . print_r($status->toArray(), true));
    }

    public function testAddMessageToCache()
    {
        $message = new Felamimail_Model_Message([
            'subject' => 'test',
            'account_id' => $this->_account->getId(),
            'folder_id' => $this->_folder->getId(),
            'to' => [
                ['email' => 'somemail@somedomain.com', 'name' => 'someone'],
                ['email' => 'somemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail' .
                    '@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail@somedomain.comsomemail',
                'name' => 'someone'],
            ]
        ]);
        $result = $this->_controller->addMessageToCache($message);
        self::assertTrue($result !== false);
    }
}
