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
 * @todo        implement tests
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
     * @todo add 'get subfolders' test 
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
        $resultInboxSub = $this->_controller->getSubFolders('default', $inboxFolder->localName);
        
        //print_r($resultInboxSub->toArray());
    }
    
    /**
     * create a mail folder on the server
     *
     * @todo implement
     */
    public function testCreateFolder()
    {
        /*
        $this->_controller->createFolder();
        $this->_controller->deleteFolder();
        */
    }

    /**
     * rename mail folder on the server
     *
     * @todo implement
     */
    public function testRenameFolder()
    {
        /*
        $this->_controller->createFolder();
        $this->_controller->renameFolder();
        $this->_controller->deleteFolder();
        */
    }
}
