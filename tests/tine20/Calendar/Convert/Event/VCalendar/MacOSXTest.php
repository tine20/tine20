<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Calendar_Convert_Event_VCalendar_MacOSX
 */
class Calendar_Convert_Event_VCalendar_MacOSXTest extends Calendar_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * testBackslashInDescription
     *
     * @see 0009176: iCal adds another backslash to description field
     */
    public function testBackslashInDescription()
    {
        $event = new Calendar_Model_Event(array(
            'summary' => 'CalDAV test',
            'dtstart' => Tinebase_DateTime::now(),
            'dtend' => Tinebase_DateTime::now()->addHour(1),
            'description' => 'lalala \\\\',
            'originator_tz' => 'Europe/Berlin',
            'creation_time' => Tinebase_DateTime::now(),
            'uid' => Tinebase_Record_Abstract::generateUID(),
            'seq' => 1,
        ));

        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX);
        $vevent = $converter->fromTine20Model($event)->serialize();

        $convertedEvent = $converter->toTine20Model($vevent);

        $this->assertEquals($event->description, $convertedEvent->description);
    }

    /**
     * test converting vcard from apple iCal to Calendar_Model_Event
     */
    public function testConvertToTine20Model()
    {
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/apple_caldendar_mavericks_organizer_only.ics', 'r');

        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX, '10.9');

        $event = $converter->toTine20Model($vcalendarStream);

        // assert testuser is not attendee
        $this->assertEquals(1, $event->attendee->count(), 'there sould only be one attendee');
        $this->assertNotEquals($event->organizer, $event->attendee[0]->user_id, 'organizer should not attend');

        // assert alarm
        $this->assertEquals(1, $event->alarms->count(), 'there should be exactly one alarm');
        $this->assertFalse((bool)$event->alarms->getFirstRecord()->getOption('custom'), 'alarm should be duration alarm');
        $this->assertEquals(15, $event->alarms->getFirstRecord()->minutes_before, 'alarm should be 15 min. before');
        $this->assertEquals('2013-11-15 11:47:23', Calendar_Controller_Alarm::getAcknowledgeTime($event->alarms->getFirstRecord())->format(Tinebase_Record_Abstract::ISO8601LONG), 'ACKNOWLEDGED was not imported properly');
    }

    public function testConvertToTine20ModelXCalendarAccess()
    {
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX, '10.10.2');

        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/apple_calendar_lion_access_private.ics', 'r');
        $event = $converter->toTine20Model($vcalendarStream);
        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->class);

        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/apple_calendar_lion_access_attendee.ics', 'r');
        $event = $converter->toTine20Model($vcalendarStream);
        $this->assertEquals(Calendar_Model_Event::CLASS_PUBLIC, $event->class);

        $iosPrivateIcs = dirname(__FILE__) . '/../../../Import/files/ios_private.ics';
        $vcalendarStream = fopen($iosPrivateIcs, 'r');
        $event = $converter->toTine20Model($vcalendarStream);
        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->class);

        // try again with ios user agent
        $iosUserAgent = 'iOS/8.2 (12D508) dataaccessd/1.0';
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($iosUserAgent);
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory($backend, $version);
        $vcalendarStream = fopen($iosPrivateIcs, 'r');
        $event = $converter->toTine20Model($vcalendarStream);
        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->class);
    }

    /**
     * testConvertAllDayEventWithExdate
     *
     * - exdate is the base event
     */
    public function testConvertAllDayEventWithExdate()
    {
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX, '10.10.2');

        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/apple_calendar_birthday.ics', 'r');
        $updateEvent = $converter->toTine20Model($vcalendarStream);
        $eventWithExdateOnBaseEvent = Calendar_Controller_MSEventFacade::getInstance()->create($updateEvent);

        $this->assertEquals(1, count($eventWithExdateOnBaseEvent->exdate));

        // refetch existing event here and pass it to converter
        $eventWithExdateOnBaseEvent = Calendar_Controller_Event::getInstance()->get($eventWithExdateOnBaseEvent->getId());

        $vcalendarStream2 = fopen(dirname(__FILE__) . '/../../../Import/files/apple_calendar_birthday2.ics', 'r');
        $updateEvent2 = $converter->toTine20Model($vcalendarStream2, $eventWithExdateOnBaseEvent);

        $this->assertEquals(1, count($updateEvent2->exdate), print_r($updateEvent2->toArray(), true));
        $this->assertEquals('2015-04-27 21:59:59', $updateEvent2->rrule_until);
    }
}
