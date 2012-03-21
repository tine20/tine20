<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Felamimail_Controller_Folder
 */
class Felamimail_Controller_FolderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Felamimail_Controller_Folder
     */
    protected $_controller = array();
    
    /**
     * @var Felamimail_Model_Account
     */
    protected $_account = NULL;
    
    /**
     * @var Felamimail_Backend_Imap
     */
    protected $_imap = NULL;
    
    /**
     * folders to delete in tearDown()
     * 
     * @var array
     */
    protected $_createdFolders = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Felamimail Folder Controller Tests');
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
        $this->_account = Felamimail_Controller_Account::getInstance()->search()->getFirstRecord();
        $this->_controller = Felamimail_Controller_Folder::getInstance();
        $this->_imap       = Felamimail_Backend_ImapFactory::factory($this->_account);
        
        // fill folder cache first
        $this->_controller->search($this->_getFolderFilter(''));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->_createdFolders as $foldername) {
            $this->_controller->delete($this->_account->getId(), $foldername);
        }
        
        // delete all remaining folders from cache of account
        $folderBackend = new Felamimail_Backend_Folder();
        $folders = $folderBackend->getMultipleByProperty($this->_account->getId(), 'account_id');
        foreach ($folders as $folder) {
            $folderBackend->delete($folder);
        }
    }

    /**
     * get folders from the server
     */
    public function testGetFolders()
    {
        $inboxFolder = $this->_getInbox();
        
        $this->assertFalse($inboxFolder === NULL, 'inbox not found');
        $this->assertTrue(($inboxFolder->is_selectable == 1), 'should be selectable');
        $this->assertTrue(($inboxFolder->has_children == 0), 'has children');
        
        // check if entry is created/exists in db
        $folder = $this->_controller->getByBackendAndGlobalName($this->_account->getId(), 'INBOX');
        //print_r($folder->toArray());
        $this->assertTrue(!empty($folder->id));
        $this->assertEquals('INBOX', $folder->localname);
    }
    
    /**
     * returns inbox
     * 
     * @return Felamimail_Model_Folder
     */
    protected function _getInbox()
    {
        $result = $this->_controller->search($this->_getFolderFilter(''));
        $this->assertGreaterThan(0, count($result));
        
        // get inbox folder and do more checks
        $inboxFolder = $result->filter('localname', 'INBOX')->getFirstRecord();
        return $inboxFolder;
    }
    
    /**
     * create a mail folder on the server
     */
    public function testCreateFolder()
    {
        $this->_createdFolders[] = 'INBOX' . $this->_account->delimiter . 'test';
        $newFolder = $this->_controller->create($this->_account->getId(), 'test', 'INBOX');

        // check returned data (id)
        $this->assertTrue(!empty($newFolder->id));
        $this->assertEquals('INBOX' . $this->_account->delimiter . 'test', $newFolder->globalname);
        
        // get inbox folder and do more checks -> inbox should have children now
        $result = $this->_controller->search($this->_getFolderFilter(''));
        $inboxFolder = $result->filter('localname', 'INBOX')->getFirstRecord();
        $this->assertTrue($inboxFolder->has_children == 1);
        
        // search for subfolders
        $resultInboxSub = $this->_controller->search($this->_getFolderFilter());
        
        $this->assertGreaterThan(0, count($resultInboxSub), 'No subfolders found.');
        $testFolder = $resultInboxSub->filter('localname', 'test')->getFirstRecord();
        
        $this->assertFalse($testFolder === NULL, 'No test folder created.');
        $this->assertTrue(($testFolder->is_selectable == 1));
    }

    /**
     * rename mail folder
     */
    public function testRenameFolder()
    {
        $this->_createdFolders[] = 'INBOX' . $this->_account->delimiter . 'test';
        $this->_controller->create($this->_account->getId(), 'test', 'INBOX');

        $this->_createdFolders = array('INBOX' . $this->_account->delimiter . 'test_renamed');
        $renamedFolder = $this->_controller->rename($this->_account->getId(), 'test_renamed', 'INBOX' . $this->_account->delimiter . 'test');
        
        $this->_checkFolder($renamedFolder);
    }
    
    /**
     * check folder
     * 
     * @param Felamimail_Model_Folder $_folder
     */
    protected function _checkFolder($_folder)
    {
        $this->assertEquals('test_renamed', $_folder->localname);
        
        $resultInboxSub = $this->_controller->search($this->_getFolderFilter());
        $this->assertGreaterThan(0, count($resultInboxSub), 'No subfolders found.');
        $testFolder = $resultInboxSub->filter('localname', $_folder->localname)->getFirstRecord();
        
        $this->assertFalse($testFolder === NULL, 'No folder found.');
        $this->assertTrue(($testFolder->is_selectable == 1));
    }

    /**
     * rename mail folder directly on the server (i.e. another client) and try to rename it with tine
     */
    public function testRenameFolderByAnotherClient()
    {
        $testFolderName = 'INBOX' . $this->_account->delimiter . 'test';
        $this->_controller->create($this->_account->getId(), 'test', 'INBOX');
        $this->_imap->renameFolder($testFolderName, $testFolderName . '_renamed');
        
        $this->_createdFolders = array($testFolderName . '_renamed');
        
        $this->setExpectedException('Felamimail_Exception_IMAPFolderNotFound');
        $renamedFolder = $this->_controller->rename($this->_account->getId(), $testFolderName, $testFolderName);
    }
    
    /**
     * rename mail folder on the server
     */
    public function testRenameFolderWithSubfolder()
    {
        $this->_controller->create($this->_account->getId(), 'test', 'INBOX');
        $this->_controller->create($this->_account->getId(), 'testsub', 'INBOX' . $this->_account->delimiter . 'test');

        $renamedFolder = $this->_controller->rename($this->_account->getId(), 'test_renamed', 'INBOX' . $this->_account->delimiter . 'test');

        $this->_createdFolders[] = 'INBOX' . $this->_account->delimiter . 'test_renamed' . $this->_account->delimiter . 'testsub';
        $this->_createdFolders[] = 'INBOX' . $this->_account->delimiter . 'test_renamed';
        
        $this->assertEquals('test_renamed', $renamedFolder->localname);
        
        $resultTestSub = $this->_controller->search($this->_getFolderFilter('INBOX' . $this->_account->delimiter . 'test'));
        $this->assertGreaterThan(0, count($resultTestSub), 'No subfolders found.');
        $testFolder = $resultTestSub->filter('localname', 'testsub')->getFirstRecord();
        
        //print_r($testFolder->toArray());
        $this->assertFalse($testFolder === NULL, 'No renamed folder found.');
        $this->assertTrue(($testFolder->is_selectable == 1));
        $this->assertEquals('INBOX' . $this->_account->delimiter . 'test_renamed' . $this->_account->delimiter . 'testsub', $testFolder->globalname);
    }

    /**
     * rename mail folder on the server and create a subfolder afterwards
     */
    public function testRenameFolderAndCreateSubfolder()
    {
        $this->_controller->create($this->_account->getId(), 'test', 'INBOX');

        $renamedFolder = $this->_controller->rename($this->_account->getId(), 'test_renamed', 'INBOX' . $this->_account->delimiter . 'test');

        $this->_createdFolders[] = 'INBOX' . $this->_account->delimiter . 'test_renamed' . $this->_account->delimiter . 'testsub';
        $this->_createdFolders[] = 'INBOX' . $this->_account->delimiter . 'test_renamed';

        $subfolder = $this->_controller->create($this->_account->getId(), 'testsub', 'INBOX' . $this->_account->delimiter . 'test_renamed');
        
        $this->assertEquals('INBOX' . $this->_account->delimiter . 'test_renamed' . $this->_account->delimiter . 'testsub', $subfolder->globalname);
    }
    
    /**
     * test update folder counter
     */
    public function testUpdateFolderCounter()
    {
        $inbox = $this->_getInbox();

        $this->_folderCountsTestHelper($inbox, array(
            'cache_totalcount'  => 0,
            'cache_recentcount' => 0,
            'cache_unreadcount' => 0
        ), array(
            'cache_totalcount'  => 0,
            'cache_unreadcount' => 0
        ));
        
        $this->_folderCountsTestHelper($inbox, array(
            'cache_totalcount'  => "+200",
            'cache_unreadcount' => "+25",
        ), array(
            'cache_totalcount'  => 200,
            'cache_unreadcount' => 25
        ));
        
        $this->_folderCountsTestHelper($inbox, array(
            'cache_totalcount'  => "-1",
            'cache_unreadcount' => "22",
        ), array(
            'cache_totalcount'  => 199,
            'cache_unreadcount' => 22
        ));
        
        $this->_folderCountsTestHelper($inbox, array(
            'cache_totalcount'  => "-100",
            'cache_unreadcount' => "-30",
        ), array(
            'cache_totalcount'  => 99,
            'cache_unreadcount' => 0
        ));
        
        // reset
        $updatedCounters = Felamimail_Controller_Cache_Folder::getInstance()->getCacheFolderCounter($inbox);
        $this->assertEquals(0, $updatedCounters['cache_totalcount'], 'cache_totalcount does not match.');
        $this->assertEquals(0, $updatedCounters['cache_unreadcount'], 'cache_unreadcount does not match.');
    }
    
    /**
     * folder counts test helper
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param array $_newCounters
     * @param array $_expectedValues
     */
    protected function _folderCountsTestHelper($_folder, $_newCounters, $_expectedValues)
    {
        $updatedFolder = $this->_controller->updateFolderCounter($_folder, $_newCounters);
        foreach ($_expectedValues as $key => $value) {
            $this->assertEquals($value, $updatedFolder->{$key}, $key . ' does not match.');
        }
        $folderInDb = $this->_controller->get($_folder->getId());
        $this->assertTrue($updatedFolder->toArray() == $folderInDb->toArray(), 'folder values do not match');
    }
    
    /**
     * get folder filter
     *
     * @return Felamimail_Model_FolderFilter
     */
    protected function _getFolderFilter($_globalname = 'INBOX')
    {
        return new Felamimail_Model_FolderFilter(array(
            array('field' => 'globalname', 'operator' => 'equals', 'value' => $_globalname),
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())
        ));
    }
}
