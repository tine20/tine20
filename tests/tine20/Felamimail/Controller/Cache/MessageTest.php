<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id:JsonTest.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        add test for a really big folder (subscribe mailing list?) and start 2-3 import jobs
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
        
        // init folder cache and get INBOX
        Felamimail_Controller_Cache_Folder::getInstance()->update($this->_account->getId());
        $this->_folder = $this->_getFolder();
        
        // send mail
        $mailAsString = file_get_contents(dirname(dirname(dirname(__FILE__))) . '/files/multipart_alternative.eml');
        $this->_imap->appendMessage($mailAsString, $this->_folder->globalname);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // clear message cache
        $this->_controller->clear($this->_folder);
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
     * @todo move update folder status stuff to  Felamimail_Controller_Cache_FolderTest ?
     */
    public function testUpdate()
    {
        // get folder and update folder status
        $folders = Felamimail_Controller_Cache_Folder::getInstance()->updateStatus($this->_account->getId(), NULL, $this->_folder->getId());
        $updatedFolder = $folders->getFirstRecord();
        
        // check folder status update
        $this->assertEquals(Felamimail_Model_Folder::IMAP_STATUS_OK, $updatedFolder->imap_status);
        $this->assertGreaterThan(0, $updatedFolder->imap_uidnext);
        $this->assertGreaterThan(0, $updatedFolder->imap_uidvalidity);
        $this->assertGreaterThan(-1, Zend_Date::now()->compare($updatedFolder->imap_timestamp), 'timestamp incorrect'); // later or equals
        
        // update message cache
        $result = $this->_controller->update($updatedFolder, 30);
        
        //print_r($result->toArray());
        
        // check folder status after update
        $this->assertEquals(Felamimail_Model_Folder::CACHE_STATUS_COMPLETE, $result->cache_status, 'cache status should be complete');
        $this->assertEquals($updatedFolder->imap_uidnext, $result->cache_uidnext, 'uidnext values should be equal');
        $this->assertEquals($updatedFolder->imap_totalcount, $result->cache_totalcount, 'totalcounts should be equal');
        $this->assertGreaterThan(-1, Zend_Date::now()->compare($result->cache_timestamp), 'timestamp incorrect'); // later or equals
        $this->assertEquals($result->cache_job_actions_estimate, $result->cache_job_actions_done, 'done/estimate wrong');
    }

    /**
     * test sync of deleted messages
     *
     */
    public function testSyncDelete()
    {
        // update cache
        $folders = Felamimail_Controller_Cache_Folder::getInstance()->updateStatus($this->_account->getId(), NULL, $this->_folder->getId());
        $updatedFolder = $folders->getFirstRecord();
        $resultBeforeDelete = $this->_controller->update($updatedFolder);
        
        // delete message on the imap server
        $messages = Felamimail_Controller_Message::getInstance()->search(new Felamimail_Model_MessageFilter(array(
            array('field' => 'folder_id', 'operator' => 'equals', 'value' => $this->_folder->getId()),
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId()),
        )));
        $this->_imap->selectFolder($this->_folder->globalname);
        foreach ($messages as $message) {
            //echo 'removing message ' . $message->messageuid . "\n";
            $this->_imap->removeMessage($message->messageuid);
        }
        
        // run update again
        $folders = Felamimail_Controller_Cache_Folder::getInstance()->updateStatus($this->_account->getId(), NULL, $this->_folder->getId());
        $updatedFolder = $folders->getFirstRecord();
        $result = $this->_controller->update($updatedFolder, 30);
        
        // check folder status after update
        //print_r($result->toArray());
        $this->assertGreaterThan($resultBeforeDelete->cache_job_actions_estimate, $result->cache_job_actions_estimate, 'job estimate not increased');
        if ($result->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE) {
            $this->assertEquals($result->cache_job_actions_estimate, $result->cache_job_actions_done, 'done/estimate wrong');
            $this->assertEquals(0, $result->cache_job_lowestuid, 'lowest job uid was not reset');
            $this->assertEquals($result->imap_totalcount, $result->cache_totalcount, 'totalcounts should be equal');
            $this->assertTrue($result->cache_unreadcount <= $result->cache_totalcount, 'unreadcount was not decreased');
        } else {
            $this->assertNotEquals($result->cache_job_actions_estimate, $result->cache_job_actions_done, 'done/estimate wrong');
            $this->assertGreaterThan(0, $result->cache_job_lowestuid, 'lowest job uid was not reset');
        }
    }
        
    /**
     * get folder
     *
     * @return Felamimail_Model_Folder
     */
    protected function _getFolder($_folderName = 'INBOX')
    {
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'globalname', 'operator' => 'equals', 'value' => '',),
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())
        ));
        $result = Felamimail_Controller_Folder::getInstance()->search($filter);
        $folder = $result->filter('localname', $_folderName)->getFirstRecord();
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
}
