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
 * @todo        add removeFolder
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Felamimail_Controller_FolderTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Felamimail_Controller_FolderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Felamimail_Controller_Folder
     */
    protected $_controller = array();
    
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
    }

    /**
     * get folders from the server
     *
     */
    public function testGetFolders()
    {
        $result = $this->_controller->getSubFolders();
        
        $this->assertGreaterThan(0, count($result));
        
        // get inbox folder and do more checks
        $inboxFolder = $result->filter('localName', 'INBOX')->getFirstRecord();
        $this->assertFalse($inboxFolder === NULL);
        $this->assertTrue($inboxFolder->isSelectable);
        $this->assertTrue($inboxFolder->hasChildren);

        // get subfolders of INBOX
        $resultInboxSub = $this->_controller->getSubFolders($inboxFolder->localName);
        $this->assertGreaterThan(0, count($resultInboxSub), 'No subfolders found.');
        
        $first = $resultInboxSub->getFirstRecord();
        $this->assertTrue(preg_match("/^INBOX\//", $first->globalName) == 1);
    }
    
    /**
     * create a mail folder on the server
     *
     */
    public function testCreateFolder()
    {
        $this->_controller->createFolder('test', 'INBOX');

        $resultInboxSub = $this->_controller->getSubFolders('INBOX');
        $this->assertGreaterThan(0, count($resultInboxSub), 'No subfolders found.');
        $testFolder = $resultInboxSub->filter('localName', 'test')->getFirstRecord();
        
        $this->assertFalse($testFolder === NULL, 'No test folder created.');
        $this->assertTrue($testFolder->isSelectable);
        
        //$this->_controller->removeFolder();
    }

    /**
     * rename mail folder on the server
     *
     */
    public function testRenameFolder()
    {
        $this->_controller->createFolder('test', 'INBOX');

        $this->_controller->renameFolder('INBOX/test', 'INBOX/test_renamed');
        
        $resultInboxSub = $this->_controller->getSubFolders('INBOX');
        $this->assertGreaterThan(0, count($resultInboxSub), 'No subfolders found.');
        $testFolder = $resultInboxSub->filter('localName', 'test_renamed')->getFirstRecord();
        
        $this->assertFalse($testFolder === NULL, 'No renamed folder found.');
        $this->assertTrue($testFolder->isSelectable);
        
        //$this->_controller->removeFolder();
    }
}
