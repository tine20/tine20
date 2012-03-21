<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Frontend_CalDAVTest::main');
}

/**
 * Test class for Calendar_Frontend_CalDAV
 */
class Calendar_Frontend_CalDAVTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar CalDAV Tests');
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
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        )));
        
        $this->objects['containerToDelete'][] = $this->objects['initialContainer'];
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->objects['containerToDelete'] as $containerId) {
            $containerId = $containerId instanceof Tinebase_Model_Container ? $containerId->getId() : $containerId;
            
            try {
                Tinebase_Container::getInstance()->deleteContainer($containerId);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }
    }
    
    /**
     * test getChildren
     */
    public function testGetChildren()
    {
        $collection = new Calendar_Frontend_CalDAV();
        
        $children = $collection->getChildren();
        
        $this->assertTrue($children[0] instanceof Calendar_Frontend_WebDAV_Container);
    }
        
    /**
     * test getChild
     */
    public function testGetChild()
    {
        $collection = new Calendar_Frontend_CalDAV();
        
        $child = $collection->getChild($this->objects['initialContainer']->getId());
        
        $this->assertTrue($child instanceof Calendar_Frontend_WebDAV_Container);
    }
        
    public function testCreateFile()
    {
        $collection = new Calendar_Frontend_CalDAV();
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $collection->createFile('foobar');
    }
    
    public function testCreateDirectory()
    {
        $collection = new Calendar_Frontend_CalDAV();
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $collection->createDirectory('foobar');
    }
}
