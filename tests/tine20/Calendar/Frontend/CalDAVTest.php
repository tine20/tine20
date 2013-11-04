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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => 'Calendar_Model_Event',
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        )));
        
        $this->objects['tasksContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => 'Tasks_Model_Task',
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
        )));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        $_SERVER['HTTP_USER_AGENT'] = '';
    }
    
    /**
     * test getChildren
     */
    public function testGetChildren()
    {
        $collection = new Calendar_Frontend_CalDAV();
        
        $children = $collection->getChildren();
        
        $this->assertTrue($children[0] instanceof Calendar_Frontend_WebDAV_Container);
        
        $this->assertTrue(array_reduce($children, function($result, $container){
            return $result || $container instanceof Tasks_Frontend_WebDAV_Container;
        }, FALSE), 'tasks container is missing');
        
        return $children;
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
    
    /**
     * test getChild
     */
    public function testGetTasksChild()
    {
        $collection = new Calendar_Frontend_CalDAV();
        $children = $this->testGetChildren();
        
        $taskContainer = array_reduce($children, function($result, $container){
            return $container instanceof Tasks_Frontend_WebDAV_Container ? $container : NULL;
        }, NULL);
        $child = $collection->getChild($taskContainer->getName());
    
        $this->assertTrue($child instanceof Tasks_Frontend_WebDAV_Container);
    }
    
    /**
     * test testGetTasksChild (Mac_OS_X)
     */
    public function testGetTasksChildMacOSX()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';
        
        $collection = new Calendar_Frontend_CalDAV();
        $children = $this->testGetChildren();
    }
    
    /**
     * test to a create file. this should not be possible at this level
     */
    public function testCreateFile()
    {
        $collection = new Calendar_Frontend_CalDAV();
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $collection->createFile('foobar');
    }
    
    /**
     * test to create a new directory
     */
    public function testCreateDirectory()
    {
        $randomName = Tinebase_Record_Abstract::generateUID();
        
        $collection = new Calendar_Frontend_CalDAV();
        
        $collection->createDirectory($randomName);
        
        $container = Tinebase_Container::getInstance()->getContainerByName('Calendar', $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        
        $this->assertTrue($container instanceof Tinebase_Model_Container);
        
    }
}
