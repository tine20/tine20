<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
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
            : Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
                'name'           => 'shared',
                'type'           => Tinebase_Model_Container::TYPE_SHARED,
                'backend'        => 'sql',
                'application_id' => $this->_application->getId(),
            )));
            
        $personas = Zend_Registry::get('personas');
        $this->_otherUserContainer = Tinebase_Container::getInstance()->getDefaultContainer($personas['sclever']->getId(), 'Filemanager');
        Tinebase_Container::getInstance()->addGrants($this->_otherUserContainer->getId(), Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE, NULL, array(
            Tinebase_Model_Grants::GRANT_READ
        ), TRUE);
    }
    
    /**
     * setup the test paths
     * 
     * @param string|array $_types
     */
    protected function _setupTestPath($_types)
    {
        $testPaths = array();
        $types = (array) $_types;
        
        foreach ($types as $type) {
            switch ($type) {
                case Tinebase_Model_Container::TYPE_PERSONAL:
                    $testPaths[] = Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName 
                        . '/' . $this->_personalContainer->getId() . '/unittestdir_personal';
                    break;
                case Tinebase_Model_Container::TYPE_SHARED:
                    $testPaths[] = Tinebase_Model_Container::TYPE_SHARED . '/' . $this->_sharedContainer->getId();
                    $testPaths[] = Tinebase_Model_Container::TYPE_SHARED . '/' . $this->_sharedContainer->getId() . '/unittestdir_shared';
                    break;
                case Tinebase_Model_Container::TYPE_OTHERUSERS:
                    $testPaths[] = Tinebase_Model_Container::TYPE_PERSONAL . '/sclever/' . $this->_otherUserContainer->getId() . '/unittestdir_other';
                    break;
            }
        }
        
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
        if (isset($this->_objects['paths'])) {
            foreach ($this->_objects['paths'] as $path) {
                $this->_fsController->rmDir($path, TRUE);
            }
        }
    }
    
    /**
     * test search nodes (personal)
     */
    public function testSearchPersonalNodes()
    {
        $this->_setupTestPath(Tinebase_Model_Container::TYPE_PERSONAL);
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName . '/' . $this->_personalContainer->name
        ));
        $this->_searchHelper($filter, 'unittestdir_personal');
    }
    
    /**
     * search node helper
     * 
     * @param array $_filter
     * @param string $_expectedName
     * @return array search result
     */
    protected function _searchHelper($_filter, $_expectedName, $_toplevel = FALSE)
    {
        $result = $this->_json->searchNodes($_filter, array());
        
        $this->assertEquals(1, $result['totalcount']);
        if ($_toplevel) {
            // toplevel containers are resolved
            $this->assertEquals($_expectedName, $result['results'][0]['name']['name']);
        } else {
            $this->assertEquals($_expectedName, $result['results'][0]['name']);
        }
        
        return $result;
    }
    
    /**
     * test search nodes (shared)
     */
    public function testSearchSharedNodes()
    {
        $this->_setupTestPath(Tinebase_Model_Container::TYPE_SHARED);
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_Model_Container::TYPE_SHARED . '/' . $this->_sharedContainer->name
        ));
        $this->_searchHelper($filter, 'unittestdir_shared');
    }
    
    /**
     * test search nodes (other)
     */
    public function testSearchOtherUsersNodes()
    {
        $this->_setupTestPath(Tinebase_Model_Container::TYPE_OTHERUSERS);
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_Model_Container::TYPE_OTHERUSERS . '/sclever/' . $this->_otherUserContainer->name
        ));
        $this->_searchHelper($filter, 'unittestdir_other');
    }
    
    /**
     * search top level containers of user
     */
    public function testSearchTopLevelContainersOfUser()
    {
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName
        ));
        $this->_searchHelper($filter, $this->_personalContainer->name, TRUE);
    }

    /**
     * search shared top level containers 
     */
    public function testSearchSharedTopLevelContainers()
    {
        $this->_setupTestPath(Tinebase_Model_Container::TYPE_SHARED);
        
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_Model_Container::TYPE_SHARED
        ));
        $result = $this->_searchHelper($filter, $this->_sharedContainer->name, TRUE);
    }

    /**
     * search top level containers of other users
     */
    public function testSearchTopLevelContainersOfOtherUsers()
    {
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_Model_Container::TYPE_OTHERUSERS
        ));
        $this->_searchHelper($filter, 'sclever');
    }

    /**
     * search containers of other user
     */
    public function testSearchContainersOfOtherUser()
    {
        $filter = array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => '/' . Tinebase_Model_Container::TYPE_OTHERUSERS . '/sclever'
        ));
        $result = $this->_searchHelper($filter, $this->_otherUserContainer->name, TRUE);
        
        $expectedPath = $filter[0]['value'] . '/' . $this->_otherUserContainer->name;
        $this->assertEquals($expectedPath, $result['results'][0]['path'], 'node path mismatch');
        $this->assertEquals($filter[0]['value'], $result['filter'][0]['value'], 'filter path mismatch');
    }
}
