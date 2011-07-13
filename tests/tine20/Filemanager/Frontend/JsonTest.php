<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Filemanager_Frontend_Json
 * 
 * @package     Filemanager
 */
class Filemanager_Frontend_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * uit
     *
     * @var Filemanager_Frontend_Json
     */
    protected $_json;
    
    /**
     * uit
     *
     * @var Tinebase_FileSystem
     */
    protected $_fsController;
    
    /**
     * filemanager app
     *
     * @var Tinebase_Model_Application
     */
    protected $_application;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 webdav tree tests');
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
        $this->_json = new Filemanager_Frontend_Json();
        $this->_fsController = Tinebase_FileSystem::getInstance();
        $this->_application = Tinebase_Application::getInstance()->getApplicationByName('Filemanager');
        
        $this->_setupTestPaths();
    }
    
    /**
     * setup the test paths
     */
    protected function _setupTestPaths()
    {
        $testPaths = array();
        
        // add personal path
        $personalContainer = Tinebase_Container::getInstance()->getDefaultContainer(Tinebase_Core::getUser()->getId(), 'Filemanager');
        $testPaths[] = Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName 
            . '/' . $personalContainer->getId() . '/unittestdir_personal';
        
        // mkdir
        foreach ($testPaths as $path) {
            $path = Filemanager_Controller_Node::getInstance()->addBasePath($path);
            $this->_objects['paths'][] = $path;
            $this->_fsController->mkDir($path);
        }
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->_objects['paths'] as $path) {
            $this->_fsController->rmDir($path, TRUE);
        }
    }
    
    /**
     * test search nodes
     */
    public function testSearchNodes()
    {
        // @todo set container as member var
        $personalContainer = Tinebase_Container::getInstance()->getDefaultContainer(Tinebase_Core::getUser()->getId(), 'Filemanager');
        $filter = array(
            array(
                'field'    => 'path', 
                'operator' => 'equals', 
                'value'    => '/personal/' . Tinebase_Core::getUser()->accountLoginName . '/' . $personalContainer->name
            )
        );
        
        // @todo generalize
        $result = $this->_json->searchNodes($filter, array());
        
        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals('unittestdir_personal', $result['results'][0]['name']);
    }
}
