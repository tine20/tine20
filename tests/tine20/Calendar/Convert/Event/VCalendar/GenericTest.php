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
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Convert_Event_VCalendar_GenericTest::main');
}

/**
 * Test class for Calendar_Convert_Event_VCalendar_Generic
 */
class Calendar_Convert_Event_VCalendar_GenericTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar WebDAV Generic Event Tests');
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
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }
    
    /**
     * test converting vcard from sogo connector to Calendar_Model_Event 
     * @return Calendar_Model_Event
     */
    public function testConvertToTine20Model()
    {
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/lightning.ics', 'r');
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $event = $converter->toTine20Model($vcalendarStream);
        
        #var_dump($event->toArray());
        
        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->class);
        $this->assertEquals('Hamburg',                           $event->location);
        $this->assertEquals('Europe/Berlin',                     $event->originator_tz);
        $this->assertEquals("2011-10-04 10:00:00",               (string)$event->dtend);
        $this->assertEquals("2011-10-04 08:00:00",               (string)$event->dtstart);
        $this->assertEquals("2011-10-04 06:45:00",               (string)$event->alarms[0]->alarm_time);
        $this->assertEquals("75",                                (string)$event->alarms[0]->minutes_before);
        $this->assertEquals("This is a descpription\nwith a linebreak and a ; , and :", $event->description);
        $this->assertEquals(2, count($event->attendee));
        
        return $event;
    }
    
    /**
     * test converting vcard from sogo connector to Calendar_Model_Event
     * @return Calendar_Model_Event
     */
    public function _testConvertFromIcalToTine20Model()
    {
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/lightning.ics', 'r');
    
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
    
        $event = $converter->toTine20Model($vcalendarStream);
    
        #var_dump($event->toArray());
    
        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->class);
        $this->assertEquals('Hamburg',                           $event->location);
        $this->assertEquals('Europe/Berlin',                     $event->originator_tz);
        $this->assertEquals("2011-10-04 10:00:00",               (string)$event->dtend);
        $this->assertEquals("2011-10-04 08:00:00",               (string)$event->dtstart);
        $this->assertEquals("2011-10-04 06:45:00",               (string)$event->alarms[0]->alarm_time);
        $this->assertEquals("75",                                (string)$event->alarms[0]->minutes_before);
    
        return $event;
    }
    
    /**
     * test converting vcard from sogo connector to Calendar_Model_Event
     * @return Calendar_Model_Event
     */
    public function testConvertAllDayEventToTine20Model()
    {
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/lightning_allday.ics', 'r');
    
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
    
        $event = $converter->toTine20Model($vcalendarStream);
        
        #var_dump($event->toArray());
        #var_dump($event->dtend);
        
        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->class);
        $this->assertEquals('Hamburg',                           $event->location);
        $this->assertEquals("2011-10-19 21:59:59",               (string)$event->dtend   , 'DTEND mismatch');
        $this->assertEquals("2011-10-18 22:00:00",               (string)$event->dtstart , 'DTSTART mismatch');
        $this->assertTrue($event->is_all_day_event , 'All day event mismatch');
        $this->assertEquals("2011-10-19 00:00:00",               (string)$event->alarms[0]->alarm_time);
    
        return $event;
    }
    
    /**
     * test converting vcard with daily repeating event to Calendar_Model_Event
     * @return Calendar_Model_Event
     */
    public function testConvertRepeatingDailyEventToTine20Model()
    {
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/lightning_repeating_daily.ics', 'r');
    
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
    
        $event = $converter->toTine20Model($vcalendarStream);
    
        #var_dump($event->exdate[3]->recurid->format('hm'));
        #var_dump($event->dtstart->format('hm'));
    
        $this->assertEquals('FREQ=DAILY;UNTIL=2011-10-30 06:00:00', $event->rrule);
        $this->assertEquals(4, count($event->exdate));
        $this->assertEquals($event->uid,            $event->exdate[3]->uid);
        $this->assertEquals("2011-10-08 13:00:00",  (string)$event->exdate[3]->dtend   , 'DTEND mismatch');
        $this->assertEquals("2011-10-08 11:00:00",  (string)$event->exdate[3]->dtstart , 'DTSTART mismatch');
        $this->assertEquals($event->dtstart->format('hm'),  $event->exdate[3]->recurid->format('hm') , 'Recurid mismatch');
        
        return $event;
    }
    
    /**
     * test converting vcard with daily repeating event to Calendar_Model_Event
     * @return Calendar_Model_Event
     */
    public function testConvertRepeatingAllDayDailyEventToTine20Model()
    {
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/apple_caldendar_repeating_allday.ics', 'r');
    
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
    
        $event = $converter->toTine20Model($vcalendarStream);
    
        #var_dump($event->exdate->toArray());
        #var_dump($event->dtstart->format('hm'));
        
        $this->assertEquals('FREQ=DAILY;UNTIL=2011-11-11 23:00:00;INTERVAL=1', $event->rrule, 'until must be converted');
        $this->assertEquals(TRUE, $event->is_all_day_event);
        $this->assertEquals('TRANSPARENT', $event->transp);
        $this->assertEquals('PUBLIC', $event->class);
        $this->assertEquals("2011-11-07 23:00:00", (string) $event->dtstart, 'DTEND mismatch');
        $this->assertEquals("2011-11-08 22:59:59", (string) $event->dtend,   'DTSTART mismatch');
        $this->assertEquals("2011-11-10 23:00:00", (string) $event->exdate[0]->recurid, 'RECURID mismatch');
        
        return $event;
    }
    
    /**
     * test converting vcard with daily repeating event to Calendar_Model_Event
     * @return Calendar_Model_Event
     */
    public function testConvertRepeatingAllDayDailyEventFromTine20Model()
    {
        $event = $this->testConvertRepeatingAllDayDailyEventToTine20Model();
        $event->creation_time      = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $event->last_modified_time = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        $event->organizer          = Tinebase_Core::getUser()->contact_id;
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $vevent = $converter->fromTine20Model($event)->serialize();
        
        //var_dump($vevent);
        $this->assertContains('VERSION:2.0',                                    $vevent, $vevent);
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar V',      $vevent, $vevent);
        $this->assertContains('CREATED;VALUE=DATE-TIME:20111111T111100Z',       $vevent, $vevent);
        $this->assertContains('LAST-MODIFIED;VALUE=DATE-TIME:20111111T121200Z', $vevent, $vevent);
        $this->assertContains('DTSTAMP;VALUE=DATE-TIME:20111111T121200Z',       $vevent, $vevent);
        $this->assertContains('RRULE:FREQ=DAILY;UNTIL=20111112;INTERVAL=1',     $vevent, $vevent);
        $this->assertContains('EXDATE;VALUE=DATE:20111111',                     $vevent, $vevent);
        $this->assertContains('ORGANIZER;CN="' . Tinebase_Core::getUser()->accountDisplayName . '":mailto:' . Tinebase_Core::getUser()->accountEmailAddress, $vevent, $vevent);
        

    }
    
    /**
     * test converting vcard with daily repeating event to Calendar_Model_Event
     * and merge with existing event
     * @return Calendar_Model_Event
     */
    public function disabled_testConvertRepeatingDailyEventToTine20ModelWithMerge()
    {
        $event = $this->testConvertRepeatingDailyEventToTine20Model();
        
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/lightning_repeating_daily.ics', 'r');
    
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $event->exdate[3]->attendee[0]->status_authkey = 'TestMe';
        
        $updatedEvent = $converter->toTine20Model($vcalendarStream, clone $event);
    
        //var_dump($event->exdate->toArray());
        #var_dump($updatedEvent->exdate[3]->attendee->toArray());
        #var_dump($event->dtstart->format('hm'));
    
        $this->assertTrue(! empty($updatedEvent->exdate[3]->attendee[0]->status_authkey));
        $this->assertEquals($event->exdate[3]->attendee[0]->status_authkey, $updatedEvent->exdate[3]->attendee[0]->status_authkey);
    
        return $event;
    }
        
    /**
     * test converting vcard from sogo connector to Calendar_Model_Event 
     */
    public function testConvertToTine20ModelWithUpdate()
    {
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/lightning.ics', 'r');
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $event = $converter->toTine20Model($vcalendarStream);
        
        // status_authkey must be kept after second convert
        $event->attendee[0]->quantity = 10;
        
        rewind($vcalendarStream);
        $event = $converter->toTine20Model($vcalendarStream, $event);

        $this->assertEquals(10, $event->attendee[0]->quantity);
    }    

    /**
     * 
     * @depends testConvertToTine20Model
     */
    public function testConvertFromTine20Model()
    {
        $event = $this->testConvertToTine20Model();
        $event->creation_time      = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $event->last_modified_time = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $vevent = $converter->fromTine20Model($event)->serialize();
        // var_dump($vevent);
        // required fields
        $this->assertContains('VERSION:2.0',                                    $vevent, $vevent);
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar V',      $vevent, $vevent);
        $this->assertContains('CREATED;VALUE=DATE-TIME:20111111T111100Z',       $vevent, $vevent);
        $this->assertContains('LAST-MODIFIED;VALUE=DATE-TIME:20111111T121200Z', $vevent, $vevent);
        $this->assertContains('DTSTAMP;VALUE=DATE-TIME:20111111T121200Z',       $vevent, $vevent);
        $this->assertContains('TZID:Europe/Berlin',               $vevent, $vevent);
        $this->assertContains('UID:' . $event->uid,               $vevent, $vevent);
        $this->assertContains('LOCATION:' . $event->location,     $vevent, $vevent);
        $this->assertContains('CLASS:PRIVATE',                    $vevent, $vevent);
        $this->assertContains('TRIGGER;VALUE=DURATION:-PT1H15M',  $vevent, $vevent);
        $this->assertContains('TZOFFSETFROM:+0100',  $vevent, $vevent);
        $this->assertContains('TZOFFSETTO:+0200',  $vevent, $vevent);
        $this->assertContains('TZNAME:CEST',  $vevent, $vevent);
        $this->assertContains('TZOFFSETFROM:+0200',  $vevent, $vevent);
        $this->assertContains('TZOFFSETTO:+0100',  $vevent, $vevent);
        $this->assertContains('TZNAME:CET',  $vevent, $vevent);
        
    }
    
    /**
     * 
     * @depends testConvertAllDayEventToTine20Model
     */
    public function testConvertFromAllDayEventTine20Model()
    {
        $event = $this->testConvertAllDayEventToTine20Model();
        $event->creation_time      = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $event->last_modified_time = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $vevent = $converter->fromTine20Model($event)->serialize();
        // var_dump($vevent);
        // required fields
        $this->assertContains('VERSION:2.0',                                    $vevent, $vevent);
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar V',      $vevent, $vevent);
        $this->assertContains('CREATED;VALUE=DATE-TIME:20111111T111100Z',       $vevent, $vevent);
        $this->assertContains('LAST-MODIFIED;VALUE=DATE-TIME:20111111T121200Z', $vevent, $vevent);
        $this->assertContains('DTSTAMP;VALUE=DATE-TIME:20111111T121200Z',       $vevent, $vevent);
        $this->assertContains('DTSTART;VALUE=DATE:20111019',      $vevent, $vevent);
        $this->assertContains('DTEND;VALUE=DATE:20111020',        $vevent, $vevent);
        $this->assertContains('TZID:Europe/Berlin',               $vevent, $vevent);
        $this->assertContains('UID:' . $event->uid,               $vevent, $vevent);
        $this->assertContains('LOCATION:' . $event->location,     $vevent, $vevent);
        $this->assertContains('CLASS:PRIVATE',                    $vevent, $vevent);
        $this->assertContains('TRIGGER;VALUE=DATE-TIME:20111019T000000Z',  $vevent, $vevent);
        $this->assertContains('TZOFFSETFROM:+0100',  $vevent, $vevent);
        $this->assertContains('TZOFFSETTO:+0200',    $vevent, $vevent);
        $this->assertContains('TZNAME:CEST',         $vevent, $vevent);
        $this->assertContains('TZOFFSETFROM:+0200',  $vevent, $vevent);
        $this->assertContains('TZOFFSETTO:+0100',    $vevent, $vevent);
        $this->assertContains('TZNAME:CET',          $vevent, $vevent);
        
    }
    
    /**
     * 
     * @depends testConvertToTine20Model
     */
    public function testConvertRepeatingEventFromTine20Model()
    {
        $event = $this->testConvertRepeatingDailyEventToTine20Model();
        $event->creation_time      = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $event->last_modified_time = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $vevent = $converter->fromTine20Model($event)->serialize();
        #var_dump($vevent);
        // required fields
        $this->assertContains('VERSION:2.0', $vevent, $vevent);
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar V',      $vevent, $vevent);
        $this->assertContains('CREATED;VALUE=DATE-TIME:20111111T111100Z',       $vevent, $vevent);
        $this->assertContains('LAST-MODIFIED;VALUE=DATE-TIME:20111111T121200Z', $vevent, $vevent);
        $this->assertContains('DTSTAMP;VALUE=DATE-TIME:20111111T121200Z',       $vevent, $vevent);
        $this->assertContains('RRULE:FREQ=DAILY;UNTIL=20111030T060000Z',        $vevent, $vevent);
        $this->assertContains('EXDATE;VALUE=DATE-TIME:20111005T080000Z',        $vevent, $vevent);
        $this->assertContains('EXDATE;VALUE=DATE-TIME:20111006T080000Z',        $vevent, $vevent);
        $this->assertContains('EXDATE;VALUE=DATE-TIME:20111007T080000Z',        $vevent, $vevent);
        $this->assertContains('TZID:Europe/Berlin',           $vevent, $vevent);
        $this->assertContains('UID:' . $event->uid,           $vevent, $vevent);
        $this->assertContains('LOCATION:' . $event->location, $vevent, $vevent);
        $this->assertContains('CLASS:PRIVATE',                $vevent, $vevent);
    }
}
