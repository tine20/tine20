<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Felamimail_Controller_FolderTest::main');
}

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
     * folders to delete in tearDown()
     * 
     * @var array
     */
    protected $_foldersToDelete = array();
    
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
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->_foldersToDelete as $foldername) {
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
     *
     */
    public function testGetFolders()
    {
        $result = $this->_controller->search($this->_getFolderFilter(''));
        
        $this->assertGreaterThan(0, count($result));
        
        // get inbox folder and do more checks
        $inboxFolder = $result->filter('localname', 'INBOX')->getFirstRecord();
        $this->assertFalse($inboxFolder === NULL, 'inbox not found');
        $this->assertTrue(($inboxFolder->is_selectable == 1), 'should be selectable');
        $this->assertTrue(($inboxFolder->has_children == 0), 'has children');
        
        // check if entry is created/exists in db
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = $folderBackend->getByBackendAndGlobalName($this->_account->getId(), 'INBOX');
        //print_r($folder->toArray());
        $this->assertTrue(!empty($folder->id));
        $this->assertEquals('INBOX', $folder->localname);
    }
    
    /**
     * create a mail folder on the server
     *
     */
    public function testCreateFolder()
    {
        $newFolder = $this->_controller->create($this->_account->getId(), 'test', 'INBOX');

        // check returned data (id)
        $this->assertTrue(!empty($newFolder->id));
        $this->assertEquals('INBOX/test', $newFolder->globalname);
        
        $this->_foldersToDelete[] = 'INBOX/test';
        
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
     * rename mail folder on the server
     *
     */
    public function testRenameFolder()
    {
        $this->_controller->create($this->_account->getId(), 'test', 'INBOX');

        $renamedFolder = $this->_controller->rename($this->_account->getId(), 'test_renamed', 'INBOX/test');
        
        $this->assertEquals('test_renamed', $renamedFolder->localname);
        $this->_foldersToDelete[] = 'INBOX/test_renamed';
        
        $resultInboxSub = $this->_controller->search($this->_getFolderFilter());
        $this->assertGreaterThan(0, count($resultInboxSub), 'No subfolders found.');
        $testFolder = $resultInboxSub->filter('localname', 'test_renamed')->getFirstRecord();
        
        $this->assertFalse($testFolder === NULL, 'No renamed folder found.');
        $this->assertTrue(($testFolder->is_selectable == 1));
    }
    
    /**
     * rename mail folder on the server
     *
     */
    public function testRenameFolderWithSubfolder()
    {
        $this->_controller->create($this->_account->getId(), 'test', 'INBOX');
        $this->_controller->create($this->_account->getId(), 'testsub', 'INBOX/test');

        $renamedFolder = $this->_controller->rename($this->_account->getId(), 'test_renamed', 'INBOX/test');

        $this->_foldersToDelete[] = 'INBOX/test_renamed/testsub';
        $this->_foldersToDelete[] = 'INBOX/test_renamed';
        
        $this->assertEquals('test_renamed', $renamedFolder->localname);
        
        $resultTestSub = $this->_controller->search($this->_getFolderFilter('INBOX/test'));
        $this->assertGreaterThan(0, count($resultTestSub), 'No subfolders found.');
        $testFolder = $resultTestSub->filter('localname', 'testsub')->getFirstRecord();
        
        //print_r($testFolder->toArray());
        $this->assertFalse($testFolder === NULL, 'No renamed folder found.');
        $this->assertTrue(($testFolder->is_selectable == 1));
        $this->assertEquals('INBOX/test_renamed/testsub', $testFolder->globalname);
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
