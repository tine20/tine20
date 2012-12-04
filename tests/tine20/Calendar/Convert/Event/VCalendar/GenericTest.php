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
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
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
     * test converting vcard from lighting to Calendar_Model_Event
     * 
     * @return Calendar_Model_Event
     */
    public function testConvertToTine20Model()
    {
        $event = $this->_convertHelper(dirname(__FILE__) . '/../../../Import/files/lightning.ics');
        //var_dump($event->toArray());
        
        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->class);
        $this->assertEquals('Hamburg',                           $event->location);
        $this->assertEquals('Europe/Berlin',                     $event->originator_tz);
        $this->assertEquals("2011-10-04 10:00:00",               (string)$event->dtend);
        $this->assertEquals("2011-10-04 08:00:00",               (string)$event->dtstart);
        $this->assertEquals("2011-10-04 06:45:00",               (string)$event->alarms[0]->alarm_time);
        $this->assertEquals("75",                                (string)$event->alarms[0]->minutes_before);
        $this->assertEquals("This is a descpription\nwith a linebreak and a ; , and :", $event->description);
        $this->assertEquals(2, count($event->attendee));
        $this->assertEquals(1, count($event->alarms));
        
        return $event;
    }
    
    /**
     * convert helper
     * 
     * @param string $filename
     * @return Calendar_Model_Event
     */
    protected function _convertHelper($filename)
    {
        $vcalendar = $this->_getVCalendar($filename);
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $event = $converter->toTine20Model($vcalendar);
        
        return $event;
    }
    
    /**
     * test converting vcalendar from iCloud to Calendar_Model_Event
     *
     * @return Calendar_Model_Event
     */
    public function testConvertToTine20ModelFromICloud()
    {
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/calendarserver_external_invitation.ics');
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $event = $converter->toTine20Model($vcalendar);
    
        //var_dump($event->toArray());
    
        $this->assertEquals(Calendar_Model_Event::CLASS_PUBLIC,     $event->class);
        $this->assertEquals('9320E052-6AF0-45E7-9352-04BBEC898D47', $event->uid);
        $this->assertEquals(2, count($event->attendee));
        $this->assertTrue(!empty($event->organizer));
    
        return $event;
    }
    
    /**
     * test converting vcalendar from Lotus Notes to Calendar_Model_Event
     *
     * @return Calendar_Model_Event
     */
    public function testConvertToTine20ModelFromLotusNotes()
    {
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/lotusnotes_external_invitation.ics');
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $event = $converter->toTine20Model($vcalendar);
    
        //var_dump($event->toArray());
    
        $this->assertEquals(Calendar_Model_Event::CLASS_PUBLIC,     $event->class);
        $this->assertEquals('A5C4058C8C5926C8C12579B100622D66-Lotus_Notes_Generated', $event->uid);
        $this->assertEquals(3, count($event->attendee));
        $this->assertTrue(!empty($event->organizer));
    
        return $event;
    }
    
    /**
    * test converting vcard from lighting to Calendar_Model_Event (with unrecognized timezone)
    */
    public function testConvertToTine20ModelWithBadTZ()
    {
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning_badTZ.ics', 'r');
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $event = $converter->toTine20Model($vcalendarStream);
        
        $this->assertEquals('Europe/Berlin', $event->originator_tz);
    }
    
    /**
     * test converting vcard from lighting to Calendar_Model_Event
     *
     * @return Calendar_Model_Event
     */
    public function testConvertToTine20ModelWithGroupInvitation()
    {
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Model_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (!isset($smtpConfig['primarydomain'])) {
            $this->markTestSkipped('no primary smtp domain configured');
        }
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning.ics');
    
        $vcalendar = preg_replace('/lars@kneschke.de/', 'users@' . $smtpConfig['primarydomain'], $vcalendar);
    
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $event = $converter->toTine20Model($vcalendar);
    
        #var_dump($event->attendee->toArray());
    
        $this->assertEquals(2, count($event->attendee));
        $this->assertContains('group', $event->attendee->user_type);
    
        return $event;
    }
    
   /**
    * test converting vcard with status
    */
    public function testConvertToTine20ModelWithStatus()
    {
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning.ics', 'r');
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $vcalendar = str_replace('LOCATION:Hamburg', 'STATUS:CONFIRMED', $vcalendar);
        $event = $converter->toTine20Model($vcalendar);
        $this->assertEquals(Calendar_Model_Event::STATUS_CONFIRMED, $event->status);
        
        $vcalendar = str_replace('STATUS:CONFIRMED', 'STATUS:TENTATIVE', $vcalendar);
        $event = $converter->toTine20Model($vcalendar);
        $this->assertEquals(Calendar_Model_Event::STATUS_TENTATIVE, $event->status);
        
        $vcalendar = str_replace('STATUS:TENTATIVE', 'STATUS:CANCELED', $vcalendar);
        $event = $converter->toTine20Model($vcalendar);
        $this->assertEquals(Calendar_Model_Event::STATUS_CANCELED, $event->status);
    }
    
    /**
     * test converting vcard from sogo connector to Calendar_Model_Event
     * @return Calendar_Model_Event
     */
    public function _testConvertFromIcalToTine20Model()
    {
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning.ics', 'r');
    
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
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning_allday.ics', 'r');
    
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
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning_repeating_daily.ics', 'r');
    
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
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/apple_caldendar_repeating_allday.ics', 'r');
    
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
        $this->assertContains('DTSTAMP;VALUE=DATE-TIME:',                       $vevent, $vevent);
        $this->assertContains('RRULE:FREQ=DAILY;UNTIL=20111112;INTERVAL=1',     $vevent, $vevent);
        $this->assertContains('EXDATE;VALUE=DATE:20111111',                     $vevent, $vevent);
        $this->assertContains('ORGANIZER;CN="' . Tinebase_Core::getUser()->accountDisplayName . '";EMAIL=' . Tinebase_Core::getUser()->accountEmailAddress . ':', $vevent, $vevent);
    }
    
    /**
     * test converting vcard with daily repeating event to Calendar_Model_Event
     * and merge with existing event
     * @return Calendar_Model_Event
     */
    public function disabled_testConvertRepeatingDailyEventToTine20ModelWithMerge()
    {
        $event = $this->testConvertRepeatingDailyEventToTine20Model();
        
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning_repeating_daily.ics', 'r');
    
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
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning.ics', 'r');
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $event = $converter->toTine20Model($vcalendarStream);
        
        // status_authkey must be kept after second convert
        $event->attendee[0]->quantity = 10;
        
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning.ics');
        // remove alarm part from vcalendar
        $vcalendar = preg_replace('/BEGIN:VALARM.*END:VALARM(\n|\r\n)/s', null, $vcalendar);
        
        $event = $converter->toTine20Model($vcalendar, $event);
        
        $this->assertEquals(10, $event->attendee[0]->quantity);
        $this->assertTrue($event->alarms instanceof Tinebase_Record_RecordSet);
        $this->assertEquals(0, count($event->alarms));
    }    

    /**
     * @depends testConvertToTine20Model
     */
    public function testConvertFromTine20Model()
    {
        $event = $this->testConvertToTine20Model();
        $event->creation_time      = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $event->last_modified_time = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attendee');
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $vevent = $converter->fromTine20Model($event)->serialize();
        // var_dump($vevent);
        // required fields
        $this->assertContains('VERSION:2.0',                                    $vevent, $vevent);
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar V',      $vevent, $vevent);
        $this->assertContains('CREATED;VALUE=DATE-TIME:20111111T111100Z',       $vevent, $vevent);
        $this->assertContains('LAST-MODIFIED;VALUE=DATE-TIME:20111111T121200Z', $vevent, $vevent);
        $this->assertContains('DTSTAMP;VALUE=DATE-TIME:',                       $vevent, $vevent);
        $this->assertContains('TZID:Europe/Berlin',               $vevent, $vevent);
        $this->assertContains('UID:' . $event->uid,               $vevent, $vevent);
        $this->assertContains('LOCATION:' . $event->location,     $vevent, $vevent);
        $this->assertContains('CLASS:PRIVATE',                    $vevent, $vevent);
        $this->assertContains('TZOFFSETFROM:+0100',  $vevent, $vevent);
        $this->assertContains('TZOFFSETTO:+0200',  $vevent, $vevent);
        $this->assertContains('TZNAME:CEST',  $vevent, $vevent);
        $this->assertContains('TZOFFSETFROM:+0200',  $vevent, $vevent);
        $this->assertContains('TZOFFSETTO:+0100',  $vevent, $vevent);
        $this->assertContains('TZNAME:CET',  $vevent, $vevent);
        
    }
    
    /**
     * @depends testConvertToTine20Model
     */
    public function testConvertFromTine20ModelWithCurrentUserAsAttendee()
    {
        $event = $this->testConvertToTine20Model();
        $event->creation_time      = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $event->last_modified_time = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        $event->attendee->addRecord(new Calendar_Model_Attender(array(
            'user_id'   => Tinebase_Core::getUser()->contact_id,
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
            'status'    => Calendar_Model_Attender::STATUS_ACCEPTED
        )));
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $vevent = $converter->fromTine20Model($event)->serialize();
        // var_dump($vevent);
        // required fields
        $this->assertContains('VERSION:2.0',                                    $vevent, $vevent);
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar V',      $vevent, $vevent);
        $this->assertContains('CREATED;VALUE=DATE-TIME:20111111T111100Z',       $vevent, $vevent);
        $this->assertContains('LAST-MODIFIED;VALUE=DATE-TIME:20111111T121200Z', $vevent, $vevent);
        $this->assertContains('DTSTAMP;VALUE=DATE-TIME:',         $vevent, $vevent);
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
        $event->attendee->addRecord(new Calendar_Model_Attender(array(
            'user_id'   => Tinebase_Core::getUser()->contact_id,
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
            'status'    => Calendar_Model_Attender::STATUS_ACCEPTED
        )));
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $vevent = $converter->fromTine20Model($event)->serialize();
        // var_dump($vevent);
        // required fields
        $this->assertContains('VERSION:2.0',                                    $vevent, $vevent);
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar V',      $vevent, $vevent);
        $this->assertContains('CREATED;VALUE=DATE-TIME:20111111T111100Z',       $vevent, $vevent);
        $this->assertContains('LAST-MODIFIED;VALUE=DATE-TIME:20111111T121200Z', $vevent, $vevent);
        $this->assertContains('DTSTAMP;VALUE=DATE-TIME:',         $vevent, $vevent);
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
        $event->creation_time          = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $event->last_modified_time     = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        $event->exdate->creation_time  = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $vevent = $converter->fromTine20Model($event)->serialize();
        #var_dump($vevent);
        // required fields
        $this->assertContains('VERSION:2.0', $vevent, $vevent);
        $this->assertContains('PRODID:-//tine20.org//Tine 2.0 Calendar V',      $vevent, $vevent);
        $this->assertContains('CREATED;VALUE=DATE-TIME:20111111T111100Z',       $vevent, $vevent);
        $this->assertContains('LAST-MODIFIED;VALUE=DATE-TIME:20111111T121200Z', $vevent, $vevent);
        $this->assertContains('DTSTAMP;VALUE=DATE-TIME:',                       $vevent, $vevent);
        $this->assertContains('RRULE:FREQ=DAILY;UNTIL=20111030T060000Z',        $vevent, $vevent);
        $this->assertContains('EXDATE;VALUE=DATE-TIME:20111005T080000Z',        $vevent, $vevent);
        $this->assertContains('EXDATE;VALUE=DATE-TIME:20111006T080000Z',        $vevent, $vevent);
        $this->assertContains('EXDATE;VALUE=DATE-TIME:20111007T080000Z',        $vevent, $vevent);
        $this->assertContains('TZID:Europe/Berlin',           $vevent, $vevent);
        $this->assertContains('UID:' . $event->uid,           $vevent, $vevent);
        $this->assertContains('LOCATION:' . $event->location, $vevent, $vevent);
        $this->assertContains('CLASS:PRIVATE',                $vevent, $vevent);
    }
    
    public function testConvertFromTine20ModelWithCustomAlarm()
    {
        $event = $this->testConvertToTine20Model();
        $event->creation_time      = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $event->last_modified_time = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        $event->organizer          = Tinebase_Core::getUser()->contact_id;
        
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(array(
            'model'            => 'Calendar_Model_Event',
            'alarm_time'       => '2011-10-04 07:10:00',
            'minutes_before'   => Tinebase_Model_Alarm::OPTION_CUSTOM
        )));
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $vevent = $converter->fromTine20Model($event)->serialize();
        #var_dump($vevent);
        $this->assertContains('TRIGGER;VALUE=DATE-TIME:20111004T071000Z',        $vevent, $vevent);
    }
    
    public function testConvertFromTine20ModelWithStatus()
    {
        $event = $this->testConvertToTine20Model();
        $event->creation_time      = new Tinebase_DateTime('2011-11-11 11:11', 'UTC');
        $event->last_modified_time = new Tinebase_DateTime('2011-11-11 12:12', 'UTC');
        $event->organizer          = Tinebase_Core::getUser()->contact_id;
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $event->status = Calendar_Model_Event::STATUS_CONFIRMED;
        $vevent = $converter->fromTine20Model($event)->serialize();
        #var_dump($vevent);
        $this->assertContains('STATUS:CONFIRMED',        $vevent, $vevent);
        
        $event->is_deleted = 1;
        $vevent = $converter->fromTine20Model($event)->serialize();
        #var_dump($vevent);
        $this->assertContains('STATUS:CANCELED',        $vevent, $vevent);
    }
    
    public function testConvertToTine20ModelWithCustomAlarm()
    {
        $vcalendar = $this->_getVCalendar(dirname(__FILE__) . '/../../../Import/files/event_with_custom_alarm.ics');
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $event = $converter->toTine20Model($vcalendar);
        
        $this->assertTrue($event->alarms instanceof Tinebase_Record_RecordSet);
        $this->assertEquals(1, count($event->alarms));
        
        $alarm = $event->alarms->getFirstRecord();
        
        $this->assertEquals(Tinebase_Model_Alarm::OPTION_CUSTOM, $alarm->minutes_before);
        $this->assertEquals('2012-02-14 17:00:00', $alarm->alarm_time->toString());
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

    /**
     * testConvertFromGoogleToTine20Model
     * 
     * @see 0006110: handle iMIP messages from outlook
     */
    public function testConvertFromGoogleToTine20Model()
    {
        $event = $this->_convertHelper(dirname(__FILE__) . '/../../../Import/files/invite_google.ics');
        
        $this->assertEquals('133st5tjius426l9n1k1sil5rk@google.com', $event->uid);
        $this->assertEquals('Test-Termin aus Google an Tine20', $event->summary);
    }
}
