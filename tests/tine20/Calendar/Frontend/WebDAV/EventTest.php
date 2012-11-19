<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Frontend_WebDAV_Event
 */
class Calendar_Frontend_WebDAV_EventTest extends Calendar_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar WebDAV Event Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        )));
        $this->objects['sharedContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        )));
        
        $prefs = new Calendar_Preference();
        $prefs->setValue(Calendar_Preference::DEFAULTCALENDAR, $this->objects['initialContainer']->getId());
        
        $_SERVER['REQUEST_URI'] = 'lars';
    }

    /**
     * test create event with internal organizer
     * 
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateEventWithInternalOrganizer()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        $record = $event->getRecord();

        $this->assertEquals('New Event', $record->summary);
        
        return $event;
    }
    
    /**
     * test create event with external organizer
     * 
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateEventWithExternalOrganizer()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
        
        $vcalendar = file_get_contents(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        $record = $event->getRecord();

        $this->assertEquals('New Event', $record->summary);
        
        return $event;
    }
    
    /**
     * create an event which already exists on the server
     * - this happen when the client moves an event to another calendar -> see testMove*
     * - or when an client processes an iMIP which is not already loaded by CalDAV
     *
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateEventWhichExistsAlready()
    {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $oldUserAgent = $_SERVER['HTTP_USER_AGENT'];
        }
        
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $existingEvent = $this->testCreateEventWithInternalOrganizer();
        $existingRecord = $existingEvent->getRecord();
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], $existingEvent->getRecord()->uid . '.ics', $vcalendar);
        
        if (isset($oldUserAgent)) {
            $_SERVER['HTTP_USER_AGENT'] = $oldUserAgent;
        }
        
        $record = $event->getRecord();
        
        $this->assertEquals($existingRecord->getId(), $record->getId(), 'event got duplicated');
    }
    
    /**
     * test create repeating event
     *
     * @return Calendar_Frontend_WebDAV_Event
     */
    public function testCreateRepeatingEvent()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        }
    
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning_repeating_daily.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendarStream);
    
        $record = $event->getRecord();
//         print_r($record->exdate->is_deleted);
        $this->assertEquals('New Event', $record->summary);
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, $record->exdate[0]->organizer);
        $this->assertEquals(Tinebase_Core::getUser()->contact_id, $record->exdate[0]->attendee[0]->user_id);
        return $event;
    }
    
    /**
     * #7388: Wrong container id's in calendar (maybe CalDAV related)
     */
    public function testCreateEventInviteInternalAttendee()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/event_with_persona_attendee.ics');
        
//         $prefs = new Calendar_Preference();
//         $prefs->setValueForUser(Calendar_Preference::DEFAULTCALENDAR, '', $this->_personas['pwulf']->getId());
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        $event = Calendar_Controller_Event::getInstance()->get($id);
        $pwulf = $event->attendee->filter('user_id', $this->_personasContacts['pwulf']->getId())->getFirstRecord();
        
        $this->assertEquals($this->_personasDefaultCals['pwulf']->getId(), $pwulf->displaycontainer_id, 'event not in pwulfs personal calendar');
    }
    
    public function testCreateEventMissingOwnAttendee()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/iphone.ics');
        
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        $this->assertTrue(!! Calendar_Model_Attender::getOwnAttender($event->getRecord()->attendee), 'own attendee has not been added');
        
        // Simulate OSX which updates w.o. fetching first
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/iphone.ics', 'r');
        $event->put($vcalendarStream);
        
        $this->assertTrue(!! Calendar_Model_Attender::getOwnAttender($event->getRecord()->attendee), 'own attendee has not been preserved');
    }
    
    public function testFilterRepeatingException()
    {
        // create event in shared calendar test user is attendee
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning_repeating_daily.ics');
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendarStream);
        
        // decline exception -> no implicit fallout as exception is still in initialContainer via displaycal
        $exception = $event->getRecord()->exdate->filter('is_deleted', 0)->getFirstRecord();
        $exception->attendee[0]->status = Calendar_Model_Attender::STATUS_DECLINED;
        Calendar_Controller_Event::getInstance()->update($exception);
        $event = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], $event->getRecord()->getId());
        $vcalendar = stream_get_contents($event->get());
        $this->assertContains('DTSTART;VALUE=DATE-TIME;TZID=Europe/Berlin:20111008T130000', $vcalendar, 'exception must not be implicitly deleted');
        
        // delete attendee from exception -> implicit fallout exception is not longer in displaycal
        $exception = $event->getRecord()->exdate->filter('is_deleted', 0)->getFirstRecord();
        $exception->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        Calendar_Controller_Event::getInstance()->update($exception);
        $event = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], $event->getRecord()->getId());
        $vcalendar = stream_get_contents($event->get());
        $this->assertContains('EXDATE;VALUE=DATE-TIME:20111008T080000Z', $vcalendar, 'exception must be implicitly deleted');
        
        // save back event -> implicit delete must not be deleted on server
        $event->put($vcalendar);
        $event = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], $event->getRecord()->getId());
        $vcalendar = stream_get_contents($event->get());
        $this->assertContains('DTSTART;VALUE=DATE-TIME;TZID=Europe/Berlin:20111008T130000', $vcalendar, 'exception must not be implicitly deleted after update');
    }
    
    /**
     * test get vcard
     * @depends testCreateEventWithInternalOrganizer
     */
    public function testGetVCalendar()
    {
        $event = $this->testCreateEventWithInternalOrganizer();
        
        $vcalendar = stream_get_contents($event->get());
        
        //var_dump($vcalendar);
        
        $this->assertContains('SUMMARY:New Event', $vcalendar);
        $this->assertContains('ORGANIZER;CN=', $vcalendar);
    }
    
    /**
     * test get vcard
     */
    public function testGetRepeatingVCalendar()
    {
        $event = $this->testCreateRepeatingEvent();
    
        $event = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], $event->getRecord()->getId());
        
        $vcalendar = stream_get_contents($event->get());
        #var_dump($vcalendar);
        $this->assertContains('SUMMARY:New Event', $vcalendar);
        $this->assertContains('EXDATE;VALUE=DATE-TIME:20111005T080000Z', $vcalendar);
        $this->assertContains('RECURRENCE-ID;VALUE=DATE-TIME;TZID=Europe/Berlin:20111008T100000', $vcalendar);
        $this->assertContains('TRIGGER;VALUE=DURATION:-PT1H30M', $vcalendar); // base alarm
        $this->assertContains('TRIGGER;VALUE=DURATION:-PT1H15M', $vcalendar); // exception alarm
    }
    
    /**
     * test updating existing event
     */
    public function testPutEventFromThunderbird()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $event = $this->testCreateEventWithInternalOrganizer();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        
        $event->put($vcalendarStream);
        
        $record = $event->getRecord();
        
        $this->assertEquals('New Event', $record->summary);
        $this->assertTrue(! empty($record->attendee[0]["status_authkey"]));
    }
    
    /**
     * test updating existing event
     */
    public function testPutEventWithExternalOrganizer()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $event = $this->testCreateEventWithExternalOrganizer();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        
        $event->put($vcalendarStream);
        
        $record = $event->getRecord();
        
        $this->assertEquals('New Event', $record->summary);
    }
    
    /**
     * test updating existing event
     */
    public function testPutEventFromMacOsX()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $event = $this->testCreateEventWithInternalOrganizer();
    
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
    
        $event->put($vcalendarStream);
    
        $record = $event->getRecord();
    
        $this->assertEquals('New Event', $record->summary);
    }
    
    /**
     * test updating existing event
     */
    public function testPutEventFromGenericClient()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';
        
        $event = $this->testCreateEventWithInternalOrganizer();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        
        $this->setExpectedException('Sabre_DAV_Exception_Forbidden');
        
        $event->put($vcalendarStream);
        
        $record = $event->getRecord();
        
        $this->assertEquals('New Event', $record->summary);
    }
    
    public function testPutEventMultipleAlarms()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $event = $this->testCreateEventWithInternalOrganizer();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../Import/files/event_with_multiple_alarm.ics', 'r');
        
        $event->put($vcalendarStream);
        
        $record = $event->getRecord();
        
        $this->assertEquals('3', count($record->alarms));
    }
    
    /**
     * test get name of vcard
     */
    public function testGetNameOfEvent()
    {
        $event = $this->testCreateEventWithInternalOrganizer();
        
        $record = $event->getRecord();
        
        $this->assertEquals($event->getName(), $record->getId() . '.ics');
    }
    
    /**
     * move event orig container shared -> personal
     */
    public function testMoveOriginEvent()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        
        // move event (origin container)
        Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", stream_get_contents($event->get()));
        $oldEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $oldEvent->delete();
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
        $this->assertEquals($this->objects['initialContainer']->getId(), $loadedEvent->getRecord()->container_id, 'origin container not updated');
        
    }
    
    /**
     * (organizer) move originpersonal -> originshared
     * 
     * NOTE: this is the hard case, as a attendee reference stays in personal which must not be deleted
     * 
     * solution: prohibit deleting from displaycal when origcal != displaycal
     *  Exception: implicit decline if curruser owner of displaycal && ! organizer 
     */
    public function testMoveOriginPersonalToShared()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        // move event origin to shared (origin and display where the same)
        Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", stream_get_contents($event->get()));
        $oldEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
        $oldEvent->delete();
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $this->assertEquals($this->objects['sharedContainer']->getId(), $loadedEvent->getRecord()->container_id, 'origin container not updated');
        
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($loadedEvent->getRecord()->attendee);
        $this->assertEquals($this->objects['initialContainer']->getId(), $ownAttendee->displaycontainer_id, 'display container must not change');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttendee->status, 'event must not be declined');
    }
    
    /**
     * move event displaycal onedisplaycal -> otherdisplaycal
     */
    public function testMoveDisplayEvent()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($event->getRecord()->attendee);
        $displayCalendar = Tinebase_Container::getInstance()->getContainerById($ownAttendee->displaycontainer_id);
        
        $personalCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        )));
        
        // move event (displaycontainer)
        $displayCalendarEvent = new Calendar_Frontend_WebDAV_Event($displayCalendar, "$id.ics");
        Calendar_Frontend_WebDAV_Event::create($personalCalendar, "$id.ics", stream_get_contents($displayCalendarEvent->get()));
        $oldEvent = new Calendar_Frontend_WebDAV_Event($displayCalendar, "$id.ics");
        $oldEvent->delete();
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($displayCalendar, "$id.ics");
        $this->assertEquals($this->objects['sharedContainer']->getId(), $loadedEvent->getRecord()->container_id, 'origin container must not be updated');
        
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($loadedEvent->getRecord()->attendee);
        $this->assertEquals($personalCalendar->getId(), $ownAttendee->displaycontainer_id, 'display container not changed');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttendee->status, 'event must not be declined');
    }
    
    public function testGetAlarm()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        
        $personalEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
        $sharedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        
        $personalVCalendar = stream_get_contents($personalEvent->get());
        $sharedVCalendar = stream_get_contents($sharedEvent->get());
        #var_dump($personalVCalendar);
        #var_dump($sharedVCalendar);
        $this->assertNotContains('X-MOZ-LASTACK;VALUE=DATE-TIME:21', $personalVCalendar, $personalVCalendar);
        $this->assertContains('X-MOZ-LASTACK;VALUE=DATE-TIME:21', $sharedVCalendar, $sharedVCalendar);
    }
    
    public function testGetNoAlarmAsNonAttendee()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = file_get_contents(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $currentCU = Calendar_Controller_MSEventFacade::getInstance()->setCalendarUser(new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => 'someoneelse'
        )));
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        Calendar_Controller_MSEventFacade::getInstance()->setCalendarUser($currentCU);
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $ics = stream_get_contents($loadedEvent->get());
        
        $this->assertNotContains('BEGIN:VALARM', $ics, $ics);
    }
    
    public function testDeleteOrignEvent()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/event_with_custom_alarm.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $loadedEvent->delete();
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $loadedEvent->getRecord();
    }
    
    public function testDeleteImplicitDecline()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/event_with_custom_alarm.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", $vcalendar);
        
        $pwulf = new Calendar_Model_Attender(array(
            'user_type'  => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'    => array_value('pwulf', Zend_Registry::get('personas'))->contact_id
        ));
        
        $pwulfAttendee = Calendar_Model_Attender::getAttendee($event->getRecord()->attendee, $pwulf);
        $pwulfPersonalCal = Tinebase_Container::getInstance()->getContainerById($pwulfAttendee->displaycontainer_id);
        $pwulfPersonalEvent = new Calendar_Frontend_WebDAV_Event($pwulfPersonalCal, "$id.ics");
        $pwulfPersonalEvent->delete();
        
        $pwulfPersonalEvent = new Calendar_Frontend_WebDAV_Event($pwulfPersonalCal, "$id.ics");
        $pwulfAttendee = Calendar_Model_Attender::getAttendee($pwulfPersonalEvent->getRecord()->attendee, $pwulf);
        $this->assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $pwulfAttendee->status, 'event must be declined for pwulf');
        $this->assertEquals(0, $pwulfPersonalEvent->getRecord()->is_deleted, 'event must not be deleted');
    }
    
    /**
     * NOTE: As noted in {@see testMoveOriginPersonalToShared} we can't delete/decline for organizer in his
     *       personal cal because a move personal->shared would delete/decline the event.
     *       
     *       To support intensional delete/declines we allow the delte/decline only if ther is some
     *       time between this and the last update
     */
    public function testDeleteImplicitDeclineOrganizer()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->objects['initialContainer'], "$id.ics", $vcalendar);
        
        // move event origin to shared (origin and display where the same)
        Calendar_Frontend_WebDAV_Event::create($this->objects['sharedContainer'], "$id.ics", stream_get_contents($event->get()));
//         $oldEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
//         $oldEvent->delete();
        
        // wait some time
        $cbs = new Calendar_Backend_Sql();
        $cbs->updateMultiple(array($id), array(
            'creation_time'      => Tinebase_DateTime::now()->subMinute(5),
            'last_modified_time' => Tinebase_DateTime::now()->subMinute(3),
        ));
        
        $personalEvent = new Calendar_Frontend_WebDAV_Event($this->objects['initialContainer'], "$id.ics");
        $personalEvent->delete();
        
        $loadedEvent = new Calendar_Frontend_WebDAV_Event($this->objects['sharedContainer'], "$id.ics");
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($loadedEvent->getRecord()->attendee);
        $this->assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $ownAttendee->status, 'event must be declined');
    }
    
    /**
     * validate that users can set alarms for events with external organizers
     * 
     */
    public function testSetAlarmForEventWithExternalOrganizer()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $event = $this->testCreateEventWithExternalOrganizer();
        
        $vcalendar = file_get_contents(dirname(__FILE__) . '/../../Import/files/lightning.ics', 'r');
        $vcalendar = preg_replace('/PT1H15M/', 'PT1H30M', $vcalendar);
        
        $event->put($vcalendar);
        
        $record = $event->getRecord();
        
        $this->assertEquals('2011-10-04 06:30:00', (string) $record->alarms->getFirstRecord()->alarm_time);
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
        
        $vcalendar = preg_replace(
            array(
                '/l.kneschke@metaway\n s.de/', 
                '/pwulf\n @tine20.org/'
            ), 
            array(
                Tinebase_Core::getUser()->accountEmailAddress, 
                array_value('pwulf', Zend_Registry::get('personas'))->accountEmailAddress
            ), 
            $vcalendar
        );
        
        return $vcalendar;
    }
}
