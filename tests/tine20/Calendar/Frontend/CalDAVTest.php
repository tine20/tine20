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
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';
        
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $children = $collection->getChildren();
        
        $this->assertTrue($children[0] instanceof Calendar_Frontend_WebDAV_Container);
        
        $this->assertTrue(array_reduce($children, function($result, $container){
            return $result || $container instanceof Tasks_Frontend_WebDAV_Container;
        }, FALSE), 'tasks container is missing');

        return $children;
    }

    public function testGetUserDirectory()
    {
        $grants = Tinebase_Model_Grants::getPersonalGrants($this->_personas['sclever']->getId());
        $grants->merge(new Tinebase_Record_RecordSet($grants->getRecordClassName(), array(array(
            'account_id' => Tinebase_Core::getUser()->getId(),
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            Tinebase_Model_Grants::GRANT_READ => true,
            Tinebase_Model_Grants::GRANT_EXPORT => true,
            Tinebase_Model_Grants::GRANT_SYNC => true,
        ))));
        $scleverTestCal = $this->_getCalendarTestContainer(Tinebase_Model_Container::TYPE_PERSONAL, $grants);

        $_SERVER['HTTP_USER_AGENT'] = 'xxx';
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . $this->_personas['sclever']->contact_id, true);
        $children = $collection->getChildren();

        $containerIds = [];
        foreach ($children as $child) {
            $containerIds[] = $child->getName();
        }
        self::assertTrue(in_array($scleverTestCal->getId(), $containerIds));
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
     * testGetChildrenWithSharedFolder
     *
     * @see 0011078: CalDav calender not working after upgrade from 2013.10 (postgresql)
     */
    public function testGetChildrenWithSharedFolder()
    {
        $this->_getCalendarTestContainer(Tinebase_Model_Container::TYPE_SHARED);
        $calendarRoot = \Sabre\CalDAV\Plugin::CALENDAR_ROOT;
        $_SERVER['REQUEST_URI'] = '/tine20/' . $calendarRoot;
        $collection = new Calendar_Frontend_WebDAV($calendarRoot, true);

        $children = $collection->getChildren();

        $this->assertTrue(is_array($children));
        $this->assertTrue(count($children) > 0);
        $this->assertTrue($children[0] instanceof Calendar_Frontend_WebDAV);
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
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';
        
        $collection = new Calendar_Frontend_WebDAV(
            \Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id,
            array('useIdAsName' => true)
        );
        $children = $this->testGetChildren();
        
        $taskContainer = array_reduce($children, function($result, $container){
            return $container instanceof Tasks_Frontend_WebDAV_Container ? $container : NULL;
        }, NULL);
        $child = $collection->getChild($taskContainer->getName());
    
        $this->assertTrue($child instanceof Tasks_Frontend_WebDAV_Container, 'got ' . get_class($child));
    }
    
    /**
     * test testGetTasksChild (Mac_OS_X)
     * 
     * @backupGlobals enabled
     */
    public function testGetTasksChildMacOSX()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';
        
        new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT, true);
        $this->testGetChildren();
    }
    
    /**
     * test to a create file. this should not be possible at this level
     */
    public function testCreateFile()
    {
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        
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
        
        $container = Tinebase_Container::getInstance()->getContainerByName(Calendar_Model_Event::class, $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        
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
        
        $container = Tinebase_Container::getInstance()->getContainerByName(Calendar_Model_Event::class, $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
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
        
        $container = Tinebase_Container::getInstance()->getContainerByName(Tasks_Model_Task::class, $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        $this->assertTrue($container instanceof Tinebase_Model_Container);

        Calendar_Frontend_WebDAV::clearClassCache();
        $subCollection = $collection->getChild('B1B3BEA0-F1F9-409F-B1A0-43E41119F851');
        $this->assertEquals('B1B3BEA0-F1F9-409F-B1A0-43E41119F851', $subCollection->getName());
        
        $properties = $subCollection->getProperties(array('{DAV:}displayname'));
        $this->assertEquals($randomName, $properties['{DAV:}displayname']);
    }

    public function testCreateVTodoXML()
    {
        $randomName = Tinebase_Record_Abstract::generateUID();

        $body = '<?xml version="1.0" encoding="UTF-8"?>
<B:mkcalendar xmlns:B="urn:ietf:params:xml:ns:caldav">
  <A:set xmlns:A="DAV:">
    <A:prop>
      <B:schedule-calendar-transp>
        <B:transparent/>
      </B:schedule-calendar-transp>
      <B:calendar-timezone>BEGIN:VCALENDAR&#13;
VERSION:2.0&#13;
CALSCALE:GREGORIAN&#13;
BEGIN:VTIMEZONE&#13;
TZID:Europe/Berlin&#13;
BEGIN:DAYLIGHT&#13;
TZOFFSETFROM:+0100&#13;
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU&#13;
DTSTART:19810329T020000&#13;
TZNAME:MESZ&#13;
TZOFFSETTO:+0200&#13;
END:DAYLIGHT&#13;
BEGIN:STANDARD&#13;
TZOFFSETFROM:+0200&#13;
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU&#13;
DTSTART:19961027T030000&#13;
TZNAME:MEZ&#13;
TZOFFSETTO:+0100&#13;
END:STANDARD&#13;
END:VTIMEZONE&#13;
END:VCALENDAR&#13;
</B:calendar-timezone>
      <B:supported-calendar-component-set>
        <B:comp name="VTODO"/>
      </B:supported-calendar-component-set>
      <D:calendar-order xmlns:D="http://apple.com/ns/ical/">1</D:calendar-order>
      <A:displayname>' . $randomName. '</A:displayname>
    </A:prop>
  </A:set>
</B:mkcalendar>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'MKCALENDAR',
            'REQUEST_URI'    => '/' . \Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id . '/9708036E-674E-4067-A0E9-698066374C6B/'
        ));
        $request->setBody($body);

        $server = new Sabre\DAV\Server(new Tinebase_WebDav_Root());
        $server->addPlugin(new \Sabre\CalDAV\Plugin());
        $server->httpRequest = $request;
        $server->exec();

        $container = Tinebase_Container::getInstance()->getContainerByName(Tasks_Model_Task::class, $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        $this->assertTrue($container instanceof Tinebase_Model_Container);

        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        $subCollection = $collection->getChild('9708036E-674E-4067-A0E9-698066374C6B');
        $this->assertEquals('9708036E-674E-4067-A0E9-698066374C6B', $subCollection->getName());

        $properties = $subCollection->getProperties(array('{DAV:}displayname'));
        $this->assertEquals($randomName, $properties['{DAV:}displayname']);
    }

    public function testGetChildrenXML()
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>
<A:propfind xmlns:A="DAV:">
  <A:prop>
    <A:add-member/>
    <C:allowed-sharing-modes xmlns:C="http://calendarserver.org/ns/"/>
    <D:autoprovisioned xmlns:D="http://apple.com/ns/ical/"/>
    <E:bulk-requests xmlns:E="http://me.com/_namespace/"/>
    <B:calendar-alarm xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <D:calendar-color xmlns:D="http://apple.com/ns/ical/"/>
    <B:calendar-description xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <B:calendar-free-busy-set xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <D:calendar-order xmlns:D="http://apple.com/ns/ical/"/>
    <B:calendar-timezone xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <A:current-user-privilege-set/>
    <B:default-alarm-vevent-date xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <B:default-alarm-vevent-datetime xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <A:displayname/>
    <C:getctag xmlns:C="http://calendarserver.org/ns/"/>
    <C:invite xmlns:C="http://calendarserver.org/ns/"/>
    <D:language-code xmlns:D="http://apple.com/ns/ical/"/>
    <D:location-code xmlns:D="http://apple.com/ns/ical/"/>
    <A:owner/>
    <C:pre-publish-url xmlns:C="http://calendarserver.org/ns/"/>
    <C:publish-url xmlns:C="http://calendarserver.org/ns/"/>
    <C:push-transports xmlns:C="http://calendarserver.org/ns/"/>
    <C:pushkey xmlns:C="http://calendarserver.org/ns/"/>
    <A:quota-available-bytes/>
    <A:quota-used-bytes/>
    <D:refreshrate xmlns:D="http://apple.com/ns/ical/"/>
    <A:resource-id/>
    <A:resourcetype/>
    <B:schedule-calendar-transp xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <B:schedule-default-calendar-URL xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <C:source xmlns:C="http://calendarserver.org/ns/"/>
    <C:subscribed-strip-alarms xmlns:C="http://calendarserver.org/ns/"/>
    <C:subscribed-strip-attachments xmlns:C="http://calendarserver.org/ns/"/>
    <C:subscribed-strip-todos xmlns:C="http://calendarserver.org/ns/"/>
    <B:supported-calendar-component-set xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <B:supported-calendar-component-sets xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <A:supported-report-set/>
    <A:sync-token/>
  </A:prop>
</A:propfind>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI'    => '/' . \Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id . '/'
        ));
        $request->setBody($body);



        $server = new Sabre\DAV\Server(new Tinebase_WebDav_Root());
        $server->addPlugin(new \Sabre\CalDAV\Plugin());
        $server->httpRequest = $request;
        $response = new Sabre\HTTP\ResponseMock();
        $server->httpResponse = $response;
        $server->exec();

        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($response->body);
//        $responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');

        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:supported-calendar-component-set/cal:comp[@name="VTODO"]');
        $this->assertGreaterThanOrEqual(1, $nodes->length);
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
     * fetch test calendar for app
     *
     * @return Tinebase_Model_Container
     */
    protected function _getCalendarTestContainer($type = Tinebase_Model_Container::TYPE_PERSONAL, $grants = null)
    {
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => 'Calendar_Model_Event',
            'type'              => $type,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => Calendar_Model_Event::class,
        )), $grants);
        
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
            'model'             => Tasks_Model_Task::class,
        )));
        
        return $container;
    }    
}
