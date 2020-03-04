<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * Test class for Calendar_ICalTests
 */
class Calendar_Export_ICalTest extends Calendar_TestCase
{
    /**
     * the test event
     *
     * @var Calendar_Model_Event
     */
    protected $_testEvent = null;

    public function setUp()
    {
        $this->_testEvent = new Calendar_Model_Event(array(
            'dtstart'       => '2010-12-30 12:00:00',
            'dtend'         => '2010-12-30 13:00:00',
            'originator_tz' => 'Europe/Berlin',
            'summary'       => 'take a nap',
            'description'   => 'hard working man needs some silence',
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'seq'           => 3,
            'transp'        => Calendar_Model_Event::TRANSP_OPAQUE,
            'class'         => Calendar_Model_Event::CLASS_PUBLIC,
            'location'      => 'couch',
            'priority'      => 1,
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=2015-12-30 13:00:00'
        ));
        parent::setUp();
    }
    
    public function testExport()
    {
        $exporter = new Calendar_Export_Ical();
        $ics = $exporter->eventToIcal($this->_testEvent);

        // assert basics
        $this->assertEquals(1, preg_match("/SUMMARY:{$this->_testEvent->summary}\r\n/", $ics), 'SUMMARY not correct');
        
        // assert dtstart/dtend tz
        $this->assertEquals(1, preg_match("/DTSTART;TZID=Europe\/Berlin:20101230T130000\r\n/", $ics), 'DTSTART not correct');
        $this->assertEquals(1, preg_match("/DTEND;TZID=Europe\/Berlin:20101230T140000\r\n/", $ics), 'DTEND not correct');
        
        // assert vtimezone
        $this->assertEquals(1, preg_match("/BEGIN:VTIMEZONE\r\n/", $ics), 'VTIMEZONE missing');
        $this->assertEquals(1, preg_match("/BEGIN:DAYLIGHT\r\nTZOFFSETFROM:\+0100\r\nTZOFFSETTO:\+0200\r\nRRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\nEND:DAYLIGHT\r\n/", $ics), 'DAYLIGHT not correct');

        // assert rrule
        $this->assertEquals(1, preg_match("/RRULE:FREQ=DAILY;INTERVAL=1;UNTIL=20151230T140000Z\r\n/", $ics), 'RRULE broken');
    }
    
    public function testExportAllDayEvent()
    {
        $this->_testEvent->is_all_day_event = TRUE;
        $this->_testEvent->dtend = $this->_testEvent->dtend->addDay(1);
        
        $exporter = new Calendar_Export_Ical();
        $ics = $exporter->eventToIcal($this->_testEvent);

        // assert dtstart/dtend tz
        $this->assertEquals(1, preg_match("/DTSTART;VALUE=DATE:20101230\r\n/", $ics), 'DTSTART not correct');
        $this->assertEquals(1, preg_match("/DTEND;VALUE=DATE:20101231\r\n/", $ics), 'DTEND not correct');
        
    }
    
    public function testExportRecurId()
    {
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($this->_testEvent, $exceptions, $this->_testEvent->dtend);
        
        $exporter = new Calendar_Export_Ical();
        $ics = $exporter->eventToIcal($nextOccurance);

        // assert recurid
        $this->assertEquals(1, preg_match("/RECURRENCE-ID;TZID=Europe\/Berlin:20101231T130000\r\n/", $ics), 'RECURRENCE-ID broken');
    }
    
    public function testExportExdate()
    {
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $from = clone $this->_testEvent->dtstart;
        $until = clone $this->_testEvent->dtend;
        $until->addDay(2);
        
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($this->_testEvent, $exceptions, $from, $until);
        $this->_testEvent->exdate = $recurSet->dtstart;

        $exporter = new Calendar_Export_Ical();
        $ics = $exporter->eventToIcal($this->_testEvent);

        // assert exdate
        $this->assertEquals(1, preg_match("/EXDATE;TZID=Europe\/Berlin:20101231T130000\r\n/", $ics), 'RECURRENCE-ID broken');
        $this->assertEquals(1, preg_match("/EXDATE;TZID=Europe\/Berlin:20110101T130000\r\n/", $ics), 'RECURRENCE-ID broken');
    }
    
    public function testExportRecurSet()
    {
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $from = clone $this->_testEvent->dtstart;
        $until = clone $this->_testEvent->dtend;
        $until->addDay(2);
        
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($this->_testEvent, $exceptions, $from, $until);
        $this->_testEvent->exdate = array($recurSet->dtstart[0]);
        
        $eventSet = new Tinebase_Record_RecordSet('Calendar_Model_Event', array(
            $this->_testEvent,
            $recurSet[1]
        ));

        $exporter = new Calendar_Export_Ical();
        $ics = $exporter->eventToIcal($eventSet);

        $this->assertEquals(2, preg_match_all('/BEGIN:VEVENT\r\n/', $ics, $matches), 'There should be exactly 2 VEVENT compontents');
    }
    
    /**
     * testExportOrganizer
     */
    public function testExportOrganizer()
    {
        $pwulf = Tinebase_Helper::array_value('pwulf', $this->_getPersonas());
        $this->_testEvent->organizer = $pwulf->contact_id;
        
        $exporter = new Calendar_Export_Ical();
        $ics = $exporter->eventToIcal($this->_testEvent);
        
        $this->assertContains("ORGANIZER;CN=\"Wulf, Paul\":mailto:" . $pwulf->accountEmailAddress,
            (string) $ics, 'ORGANIZER missing/broken');
    }
    
    /**
     * testExportAttendee
     */
    public function testExportAttendee()
    {
        $pwulf = Tinebase_Helper::array_value('pwulf', $this->_getPersonas());
        $this->_testEvent->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'role'          => Calendar_Model_Attender::ROLE_REQUIRED,
                'status'        => Calendar_Model_Attender::STATUS_ACCEPTED,
                'user_type'     => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'       => $pwulf->contact_id,
            )
        ));
        
        $exporter = new Calendar_Export_Ical();
        $ics = $exporter->eventToIcal($this->_testEvent);
        
        $this->assertContains("ATTENDEE;CN=\"Wulf, Paul\";CUTYPE=INDIVIDUAL;EMAIL=" . substr($pwulf->accountEmailAddress, 0, -14),
            (string) $ics, 'ATTENDEE missing/broken');
    }
    
    public function testExportAlarm()
    {
        // alarm handling is ugly...
        $alarmTime = clone $this->_testEvent->dtstart;
        $alarmTime->sub(15, Tinebase_DateTime::MODIFIER_MINUTE);
            
            
        $this->_testEvent->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 15,
                'alarm_time'     => $alarmTime
            ), TRUE)
        ));
        
        $exporter = new Calendar_Export_Ical();
        $ics = $exporter->eventToIcal($this->_testEvent);

        // assert organizer
        $this->assertEquals(1, preg_match("/TRIGGER:-PT15M\r\n/", $ics), 'TRIGGER missing/broken');
    }
    
    /**
     * test ical cli export
     * @group nogitlabci
     */
    public function testCliExport()
    {
        $eventData = $this->_getEvent(TRUE)->toArray();
        $this->_uit = new Calendar_Frontend_Json();
        $this->_uit->saveEvent($eventData);
        
        $this->_testNeedsTransaction();
        $cmd = realpath(__DIR__ . "/../../../../tine20/tine20.php") . ' --method Calendar.exportICS ' .
            $this->_getTestCalendar()->getId() ;
        
        $cmd = TestServer::assembleCliCommand($cmd, TRUE);
        exec($cmd, $output);
        $result = implode(',', $output);
        
        $failMessage = print_r($output, TRUE);
        $this->assertEquals(1, preg_match("/SUMMARY:{$eventData['summary']}/", $result), 'DESCRIPTION not correct: ' . $failMessage);
    }
    
    /**
     * test ical cli export with a empty Calendar
     * 
     */
    public function testCliExportEmptyCalendar()
    {
        $cli = new Calendar_Frontend_Cli();
        try {
            $cli->exportICS(new Zend_Console_Getopt('abp:', array($this->_getTestCalendar()->getId())));
            $this->fail('Expected tinbase exception.');
        } catch (Tinebase_Exception $e){
            $this->assertTrue(true);
        }
    }
}
