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
        // get inbox folder id
        Felamimail_Controller_Folder::getInstance()->getSubFolders();
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = $folderBackend->getByBackendAndGlobalName('default', 'INBOX');
        
        $filter = $this->_getMessageFilter($folder->getId());
        $result = $this->_json->searchMessages(Zend_Json::encode($filter), '');
        
        $firstMail = $result['results'][0];
        
        // get complete message
        $message = $this->_json->getMessage($firstMail['id']);
        
        $this->assertGreaterThan(0, preg_match('/Metaways Infosystems GmbH/', $message['body']));
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
     * @return array
     */
    protected function _getMessageFilter($_folderId)
    {
        return array(array(
            'field' => 'folder_id', 'operator' => 'equals', 'value' => $_folderId
        ));
    }
}
