<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Calendar_Frontend_CalDAV
 * 
 * @package     Calendar
 */
class Calendar_Frontend_CalDAVTest extends TestCase
{
    /**
     * Tree
     *
     * @var Sabre\DAV\ObjectTree
     */
    protected $_webdavTree;
    
    /**
     * test getChildren
     */
    public function testGetChildren()
    {
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
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
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $child = $collection->getChild($this->_getCalendarTestContainer()->getId());
        
        $this->assertTrue($child instanceof Calendar_Frontend_WebDAV_Container);
    }
    
    /**
     * test get calendar inbox
     */
    public function testGetCalendarInbox()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/calendars/' . Tinebase_Core::getUser()->contact_id . '/inbox');
        
        $this->assertInstanceOf('Calendar_Frontend_CalDAV_ScheduleInbox', $node, 'wrong child class');
    }
    
    /**
     * test get calendar outbox
     */
    public function testGetCalendarOutbox()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/calendars/' . Tinebase_Core::getUser()->contact_id . '/outbox');
        
        $this->assertInstanceOf('\Sabre\CalDAV\Schedule\Outbox', $node, 'wrong child class');
    }
    
    /**
     * test getChild
     */
    public function testGetTasksChild()
    {
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        $children = $this->testGetChildren();
        
        $taskContainer = array_reduce($children, function($result, $container){
            return $container instanceof Tasks_Frontend_WebDAV_Container ? $container : NULL;
        }, NULL);
        $child = $collection->getChild($taskContainer->getName());
    
        $this->assertTrue($child instanceof Tasks_Frontend_WebDAV_Container);
    }
    
    /**
     * test testGetTasksChild (Mac_OS_X)
     * 
     * @backupGlobals enabled
     */
    public function testGetTasksChildMacOSX()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';
        
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT, true);
        $children = $this->testGetChildren();
    }
    
    /**
     * test to a create file. this should not be possible at this level
     */
    public function testCreateFile()
    {
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $this->setExpectedException('Sabre\DAV\Exception\Forbidden');
        
        $collection->createFile('foobar');
    }
    
    /**
     * test to create a new directory
     */
    public function testCreateDirectory()
    {
        $randomName = Tinebase_Record_Abstract::generateUID();
        
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $collection->createDirectory($randomName);
        
        $container = Tinebase_Container::getInstance()->getContainerByName('Calendar', $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        
        $this->assertTrue($container instanceof Tinebase_Model_Container);
    }

    /**
     * test to create a new directory
     */
    public function testCreateExtendedCollectionVEvent()
    {
        $randomName = Tinebase_Record_Abstract::generateUID();
        
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $collection->createExtendedCollection(
            'B1B3BEA0-F1F9-409F-B1A0-43E41119F851', 
            array('{DAV:}collection', '{urn:ietf:params:xml:ns:caldav}calendar'),
            array(
                '{DAV:}displayname' => $randomName,
                '{http://apple.com/ns/ical/}calendar-color' => '#711A76FF',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new \Sabre\CalDAV\Property\SupportedCalendarComponentSet(array('VEVENT'))
            )
        );
        
        $container = Tinebase_Container::getInstance()->getContainerByName('Calendar', $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        $this->assertTrue($container instanceof Tinebase_Model_Container);
        
        $subCollection = $collection->getChild('B1B3BEA0-F1F9-409F-B1A0-43E41119F851');
        $this->assertEquals('B1B3BEA0-F1F9-409F-B1A0-43E41119F851', $subCollection->getName());
        
        $properties = $subCollection->getProperties(array('{DAV:}displayname'));
        $this->assertEquals($randomName, $properties['{DAV:}displayname']);
    }

    /**
     * test to create a new directory
     */
    public function testCreateExtendedCollectionVTodo()
    {
        $randomName = Tinebase_Record_Abstract::generateUID();
        
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $collection->createExtendedCollection(
            'B1B3BEA0-F1F9-409F-B1A0-43E41119F851', 
            array('{DAV:}collection', '{urn:ietf:params:xml:ns:caldav}calendar'),
            array(
                '{DAV:}displayname' => $randomName,
                '{http://apple.com/ns/ical/}calendar-color' => '#711A76FF',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new \Sabre\CalDAV\Property\SupportedCalendarComponentSet(array('VTODO'))
            )
        );
        
        $container = Tinebase_Container::getInstance()->getContainerByName('Tasks', $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        $this->assertTrue($container instanceof Tinebase_Model_Container);
        
        $subCollection = $collection->getChild('B1B3BEA0-F1F9-409F-B1A0-43E41119F851');
        $this->assertEquals('B1B3BEA0-F1F9-409F-B1A0-43E41119F851', $subCollection->getName());
        
        $properties = $subCollection->getProperties(array('{DAV:}displayname'));
        $this->assertEquals($randomName, $properties['{DAV:}displayname']);
    }

    /**
     * 
     * @return \Sabre\DAV\ObjectTree
     */
    protected function _getWebDAVTree()
    {
        if (! $this->_webdavTree instanceof \Sabre\DAV\ObjectTree) {
            $this->_webdavTree = new \Sabre\DAV\ObjectTree(new Tinebase_WebDav_Root());
        }
        
        return $this->_webdavTree;
    }
    
    /**
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getCalendarTestContainer()
    {
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => 'Calendar_Model_Event',
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        )));
        
        return $container;
    }
    
    /**
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getTasksTestContainer()
    {
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => 'Tasks_Model_Task',
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
        )));
        
        return $container;
    }    
}
