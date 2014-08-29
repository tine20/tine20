<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Frontend_WebDAV_Container
 */
class Calendar_Frontend_WebDAV_ContainerTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar WebDAV Container Tests');
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
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        )));
        
        Tinebase_Container::getInstance()->addGrants($this->objects['initialContainer'], Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, Tinebase_Core::getUser()->accountPrimaryGroup, array(Tinebase_Model_Grants::GRANT_READ));
        
        // must be defined for Calendar/Frontend/WebDAV/Event.php
        $_SERVER['REQUEST_URI'] = 'foobar';
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
    }
    
    /**
     * assert that name of folder is container name
     */
    public function testGetName()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getName();
        
        $this->assertEquals($this->objects['initialContainer']->name, $result);
    }
    
    public function testGetOwner()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getOwner();
        
        $this->assertEquals('principals/users/' . Tinebase_Core::getUser()->contact_id, $result);
    }
    
    public function testGetACL()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getACL();
        
        //var_dump($result);
        
        $this->assertEquals(6, count($result));
    }
    
    public function testGetIdAsName()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer'], true);
        
        $result = $container->getName();
        
        $this->assertEquals($this->objects['initialContainer']->getId(), $result);
    }
    
    /**
     * test getProperties
     */
    public function testGetProperties()
    {
        $this->testCreateFile();
        
        $requestedProperties = array(
            '{http://calendarserver.org/ns/}getctag',
            '{DAV:}resource-id'
        );
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getProperties($requestedProperties);
       
        $this->assertTrue(! empty($result['{http://calendarserver.org/ns/}getctag']));
        $this->assertEquals($result['{DAV:}resource-id'], 'urn:uuid:' . $this->objects['initialContainer']->getId());
    }
    
    /**
     * test updateProperties of calendar folder
     */
    public function testUpdateProperties()
    {
        $this->testCreateFile();
        
        $mutations = array(
            '{http://apple.com/ns/ical/}calendar-color'      => '#123456FF',
            '{DAV:}displayname'                              => 'testUpdateProperties',
            '{http://calendarserver.org/ns/}invalidProperty' => null
        );
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->updateProperties($mutations);
        
        $updatedContainer = Tinebase_Container::getInstance()->get($this->objects['initialContainer']);
        
        $this->assertEquals($result[200]["{http://apple.com/ns/ical/}calendar-color"],      null);
        $this->assertEquals($result[200]["{DAV:}displayname"],                              null);
        $this->assertEquals($result[403]["{http://calendarserver.org/ns/}invalidProperty"], null);
        $this->assertEquals($updatedContainer->color, substr($mutations['{http://apple.com/ns/ical/}calendar-color'], 0, 7));
        $this->assertEquals($updatedContainer->name,  $mutations['{DAV:}displayname']);
    }
    
    /**
     * test getCreateFile
     * 
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateFile()
    {
        $GLOBALS['_SERVER']['HTTP_USER_AGENT'] = 'FooBar User Agent';
        
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $id = Tinebase_Record_Abstract::generateUID();
        
        $etag = $container->createFile("$id.ics", $vcalendarStream);
        $event = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
        $record = $event->getRecord();
        
        $this->assertTrue($event instanceof Calendar_Frontend_WebDAV_Event);
        $this->assertEquals($id, $record->getId(), 'ID mismatch');
        
        return $event;
    }
    
    /**
     * test getChildren
     * 
     */
    public function testGetChildren()
    {
        $event = $this->testCreateFile()->getRecord();
        
        // reschedule to match period filter
        $event->dtstart = Tinebase_DateTime::now();
        $event->dtend = Tinebase_DateTime::now()->addMinute(30);
        Calendar_Controller_MSEventFacade::getInstance()->update($event);
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $children = $container->getChildren();
        
        $this->assertEquals(1, count($children));
        $this->assertTrue($children[0] instanceof Calendar_Frontend_WebDAV_Event);
    }
    
    /**
     * test calendarQuery with start and end time set
     * 
     * @param boolean $timeRangeEndSet
     * @param boolean $removeOwnAttender
     */
    public function testCalendarQuery($timeRangeEndSet = true, $removeOwnAttender = false)
    {
        $event = $this->testCreateFile()->getRecord();
        
        // reschedule to match period filter
        $event->dtstart = Tinebase_DateTime::now();
        $event->dtend   = Tinebase_DateTime::now()->addMinute(30);
        if ($removeOwnAttender) {
            $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
                array(
                    'user_id'   => Tinebase_User::getInstance()->getUserByLoginName('sclever')->contact_id,
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                )
            ));
        }
        Calendar_Controller_MSEventFacade::getInstance()->update($event);
        
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $timeRange = $timeRangeEndSet ? array(
            'start' => Tinebase_DateTime::now()->subHour(1),
            'end'   => Tinebase_DateTime::now()->addHour(1)
        ) : array(
            'start' => Tinebase_DateTime::now()->subHour(1),
        );
        
        $urls = $container->calendarQuery(array(
            'name'         => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name'           => 'VEVENT',
                    'comp-filters'   => array(),
                    'prop-filters'   => array(),
                    'is-not-defined' => false,
                    'time-range'     => $timeRange,
                ),
            ),
            'prop-filters'   => array(),
            'is-not-defined' => false,
            'time-range'     => null
        ));
        
        $this->assertContains($event->getId(), $urls);
    }
    
    /**
     * test calendarQuery with start time set only
     */
    public function testCalendarQueryNoEnd()
    {
        $this->testCalendarQuery(false);
    }
    
    /**
     * testCalendarQueryNotAttender
     * 
     * @see 0009204: "Foreign" events won't sync/show up via CalDAV.
     */
    public function testCalendarQueryNotAttender()
    {
        $this->testCalendarQuery(true, true);
    }
    
    /**
     * test Tinebase_WebDav_Container_Abstract::getCalendarVTimezone
     */
    public function testGetCalendarVTimezone()
    {
        $vTimezone = Tinebase_WebDav_Container_Abstract::getCalendarVTimezone('Calendar');
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar', $vTimezone);
        
        $vTimezone = Tinebase_WebDav_Container_Abstract::getCalendarVTimezone(Tinebase_Application::getInstance()->getApplicationByName('Calendar'));
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar', $vTimezone);
    }
    
    /**
     * test Calendar_Frontend_WebDAV_Container::getShares
     */
    public function testGetShares()
    {
        $container = new Calendar_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $shares = $container->getShares();
        
        $this->assertEquals(3, count($shares));
    }
    
    /**
     * test Calendar_Frontend_WebDAV_Container::getShares for container user has no admin grant for
     */
    public function testGetSharesWithoutRights()
    {
        $jmcblack = array_value('jmcblack', Zend_Registry::get('personas'));
        $jmcblacksCalId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $jmcblack->getId());
        $jmcblacksCal = Tinebase_Container::getInstance()->get($jmcblacksCalId);
        
        $container = new Calendar_Frontend_WebDAV_Container($jmcblacksCal);
    
        $shares = $container->getShares();
    
        $this->assertEquals(1, count($shares));
        $this->assertTrue((bool)$shares[0]['readOnly']);
    }
    
    /**
     * return vcalendar as string and replace organizers email address with emailaddess of current user
     * 
     * @param string $_filename  file to open
     * @return string
     */
    protected function _getVCalendar($_filename)
    {
        $vcalendar = file_get_contents($_filename);
        
        $vcalendar = preg_replace('/l.kneschke@metaway\n s.de/', Tinebase_Core::getUser()->accountEmailAddress, $vcalendar);
        
        return $vcalendar;
    }
}
