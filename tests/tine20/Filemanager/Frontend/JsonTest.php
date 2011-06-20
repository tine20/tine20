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
            $this->_fsController->rmDir($path);
        }
    }
    
    /**
     * test search nodes
     */
    public function testSearchNodes()
    {
        $personalContainer = Tinebase_Container::getInstance()->getDefaultContainer(Tinebase_Core::getUser()->getId(), 'Filemanager');
        $path = $this->_fsController->getApplicationBasePath(
            Tinebase_Application::getInstance()->getApplicationByName('Filemanager'),
            Tinebase_Model_Container::TYPE_PERSONAL
        ) . '/' . $personalContainer->getId() . '/unittestdir';
        $this->_objects['paths'][] = $path;
        $this->_fsController->mkDir($path);
        
        $filter = array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => array(array('path' => '/personal/' . $personalContainer->getId())))
        );
        $result = $this->_json->searchNodes($filter, array());
        //print_r($result);
        
        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals('unittestdir', $result['results'][0]['name']);
        
        // @todo make sure that resolved filter is available in result
    }
}		
