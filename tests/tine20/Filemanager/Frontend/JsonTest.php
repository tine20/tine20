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
     * personal container
     *
     * @var Tinebase_Model_Container
     */
    protected $_personalContainer;
    
    /**
     * shared container
     *
     * @var Tinebase_Model_Container
     */
    protected $_sharedContainer;
    
    /**
     * other user container
     *
     * @var Tinebase_Model_Container
     */
    protected $_otherUserContainer;
    
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
        
        $this->_setupTestContainers();
        $this->_setupTestPaths();
    }
    
    /**
     * init test container
     */
    protected function _setupTestContainers()
    {
        $this->_personalContainer = Tinebase_Container::getInstance()->getDefaultContainer(Tinebase_Core::getUser()->getId(), 'Filemanager');
        
        $search = Tinebase_Container::getInstance()->search(new Tinebase_Model_ContainerFilter(array(
            'application_id' => $this->_application->getId(),
            'name'           => 'shared',
            'type'           => Tinebase_Model_Container::TYPE_SHARED,
        )));
        $this->_sharedContainer = (count($search) > 0) 
            ? $search->getFirstRecord()
            : Tinebase_Container::getInstance()->create(new Tinebase_Model_Container(array(
                'name'           => 'shared',
                'type'           => Tinebase_Model_Container::TYPE_SHARED,
                'backend'        => 'sql',
                'application_id' => $this->_application->getId(),
            )));
            
        $personas = Zend_Registry::get('personas');
        $this->_otherUserContainer = Tinebase_Container::getInstance()->getDefaultContainer($personas['sclever']->getId(), 'Filemanager');            
    }
    
    /**
     * setup the test paths
     */
    protected function _setupTestPaths()
    {
        $testPaths = array();
        
        // add personal path
        $testPaths[] = Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName 
            . '/' . $this->_personalContainer->getId() . '/unittestdir_personal';
        
        // add other users path
        $testPaths[] = Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName 
            . '/' . $this->_otherUserContainer->getId() . '/unittestdir_other';
        
        // add shared path
        $testPaths[] = Tinebase_Model_Container::TYPE_SHARED . '/' . $this->_sharedContainer->getId();
        $testPaths[] = Tinebase_Model_Container::TYPE_SHARED . '/' . $this->_sharedContainer->getId() . '/unittestdir_shared';
        
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
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/personal/' . Tinebase_Core::getUser()->accountLoginName . '/' . $this->_personalContainer->name
        ));
        $this->_searchHelper($filter, 'unittestdir_personal');
    }
    
    /**
     * search node helper
     * 
     * @param array $_filter
     * @param string $_expectedName
     */
    protected function _searchHelper($_filter, $_expectedName)
    {
        $result = $this->_json->searchNodes($_filter, array());
        
        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals($_expectedName, $result['results'][0]['name']);
        
    }
}
