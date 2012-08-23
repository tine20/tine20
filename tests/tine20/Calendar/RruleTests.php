<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_RruleTests::main');
}

/**
 * Test class for Calendar_Model_Rrule
 * 
 * @package     Calendar
 */
class Calendar_RruleTests extends PHPUnit_Framework_TestCase
{
    
    public function setUp()
    {
        
    }
    
    public function testSetFromString()
    {
        $rrule = new Calendar_Model_Rrule(array());
        $rrule->setFromString("FREQ=WEEKLY;INTERVAL=3;UNTIL=2009-06-05 00:00:00;WKST=SU;BYDAY=TU,TH");
        
        $this->assertEquals(Calendar_Model_Rrule::FREQ_WEEKLY, $rrule->freq);
        $this->assertTrue($rrule->until->equals(new Tinebase_DateTime('2009-06-05 00:00:00')));
    }
    
    public function testToString()
    {
        $rruleString = "FREQ=WEEKLY;INTERVAL=3;UNTIL=2009-06-05 00:00:00;WKST=SU;BYDAY=TU,TH";
        $rrule = new Calendar_Model_Rrule(array());
        $rrule->setFromString($rruleString);
        
        $this->assertEquals($rruleString, (string)$rrule);
    }
    
    public function testIsValid()
    {
        // test invalid by normal validation actions
        $rrule = new Calendar_Model_Rrule(array(
            'interval'  => 'NotAnInt'
        ), true);
        $rrule->bypassFilters = false;
        $this->assertFalse($rrule->isValid(false));
        
        // count and until are not allowed together
        $rrule = new Calendar_Model_Rrule(array(
            'count'  => '10',
            'until'  => '2012-01-09 09:33:00'
        ), true);
        $this->assertFalse($rrule->isValid(false), 'until & count');
        
        // test invalid by setFromString
        $this->setExpectedException('Tinebase_Exception_Record_Validation');
        $rruleString = "FREQ=WEEKLY;INTERVAL=NotAnInt";
        $rrule = new Calendar_Model_Rrule(array());
        $rrule->setFromString($rruleString);
        
        
    }
    /************************ recur computation tests ************************/
    
    public function testCalcDaily()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'change t-shirt',
            'dtstart'       => '1979-06-05 08:00:00',
            'dtend'         => '1979-06-05 08:05:00',
            'rrule'         => 'FREQ=DAILY;INTERVAL=2;UNTIL=2009-04-01 08:00:00',
            'exdate'        => '2009-03-31 07:00:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule_until'   => '2009-04-01 08:00:00',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event', array(
            array(
                'uid'           => $event->uid,
                'summary'       => 'take the blue t-shirt',
                'dtstart'       => '2009-03-25 08:00:00',
                'dtend'         => '2009-03-25 08:05:00',
                'recurid'       => $event->uid . '-' . '2009-03-25 08:00:00',
                Tinebase_Model_Grants::GRANT_EDIT     => true,
            )
        ));
        
        // note: 2009-03-29 Europe/Berlin switched to DST
        $from = new Tinebase_DateTime('2009-03-23 00:00:00');
        $until = new Tinebase_DateTime('2009-04-03 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals('2009-03-23 08:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-03-27 08:00:00', $recurSet[1]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-03-29 07:00:00', $recurSet[2]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        //$this->assertEquals('2009-04-02 07:00:00', $recurSet[3]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals(3, count($recurSet));
        
        // lets also cover the case when recurevent start during calcualtion period:
        $from = new Tinebase_DateTime('1979-06-01 00:00:00');
        $until = new Tinebase_DateTime('1979-06-14 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(4, count($recurSet), 'recur start in period failed');

        // lets cover the case when search period boudaries are in the middle of the recur events
        // lets also cover the case when recurevent start during calcualtion period:
        $from = new Tinebase_DateTime('2009-03-27 08:03:00');
        $until = new Tinebase_DateTime('2009-03-29 07:03:00');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(2, count($recurSet), 'boundary inclusions failed');
        
        // and finaly lets cover the case when period boundaries are the boundaries of the recur events
        $from = new Tinebase_DateTime('2009-03-01 08:05:00');
        $until = new Tinebase_DateTime('2009-03-03 08:00:00');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(1, count($recurSet), 'boundary exclusion failed');
        $this->assertEquals('2009-03-03 08:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testCalcWeekly()
    {
        // note: 1979-06-05 was a tuesday
        // note: 1979 had no DST in Europe/Berlin
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'take a bath',
            'dtstart'       => '1979-06-05 18:00:00',
            'dtend'         => '1979-06-05 19:00:00',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=SU',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2009-06-01 00:00:00');
        $until = new Tinebase_DateTime('2009-06-30 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(4, count($recurSet), '2013-06 has 4 sundays');
        
        $from = new Tinebase_DateTime('2013-06-01 00:00:00');
        $until = new Tinebase_DateTime('2013-06-30 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(5, count($recurSet), '2013-06 has 5 sundays');
        
        $from = new Tinebase_DateTime('1979-06-01 00:00:00');
        $until = new Tinebase_DateTime('1979-06-20 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(2, count($recurSet), 'test the first sunday (1979-06-10)');
        
        // period boudaries in the middle of the recur events
        $from = new Tinebase_DateTime('2009-04-05 17:30:00');
        $until = new Tinebase_DateTime('2009-04-12 17:30:00');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(2, count($recurSet), 'boundaries inclusion failed');
        
        // odd interval
        $event->rrule = 'FREQ=WEEKLY;INTERVAL=2;BYDAY=SU';
        $from = new Tinebase_DateTime('2009-04-12 00:00:00');
        $until = new Tinebase_DateTime('2009-05-03 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(2, count($recurSet), 'odd interval failed');
    }
    
    /**
     * BYDAY are meant in the week of baseEvent -> WKST
     * 
     */
    public function testCalcWeeklyWKST()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'weekly with BYDAY and WKST',
            'dtstart'       => '2011-11-16 10:30:00',
            'dtend'         => '2011-11-16 12:30:00',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,TH;WKST=MO',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2011-11-14 00:00:00');
        $until = new Tinebase_DateTime('2011-12-04 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(3, count($recurSet), 'recur set failed');
        $this->assertTrue(array_search('2011-11-17 10:30:00', $recurSet->dtstart) !== FALSE);
        $this->assertTrue(array_search('2011-11-28 10:30:00', $recurSet->dtstart) !== FALSE);
        $this->assertTrue(array_search('2011-12-01 10:30:00', $recurSet->dtstart) !== FALSE);
    }
    
    /**
     * RRULE:FREQ=WEEKLY
     * => treat like INTERVAL=1;BYDAY=SU
     */
    public function testCalcWeeklyOmmitByDay()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'weekly without byday clause',
            'dtstart'       => '2011-11-16 10:30:00',
            'dtend'         => '2011-11-16 12:30:00',
            'rrule'         => 'FREQ=WEEKLY',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2011-11-16 00:00:00');
        $until = new Tinebase_DateTime('2011-11-30 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(2, count($recurSet), 'recur set failed');
        $this->assertTrue(array_search('2011-11-23 10:30:00', $recurSet->dtstart) !== FALSE);
        $this->assertTrue(array_search('2011-11-30 10:30:00', $recurSet->dtstart) !== FALSE);
    }
    
    /**
     * 2009-07-15 if wday skipping in calculation is done in UTC, we get an extra event
     *            and all Recurrences are calculated one day late...
     *            
     */
    public function testCalcWeeklyAllDay()
    {
        // NOTE allday in Europe/Berlin leads to UTC daybreak
        $event = new Calendar_Model_Event(array(
            'uid'              => Tinebase_Record_Abstract::generateUID(),
            'summary'          => 'testCalcWeeklyAllDay',
            'dtstart'          => '2009-05-31 22:00:00',
            'dtend'            => '2009-06-01 21:59:00',
            'is_all_day_event' => true,
            'rrule'            => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO',
            'originator_tz'    => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT        => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2009-05-31 22:00:00');
        $until = new Tinebase_DateTime('2009-07-05 21:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        
        $this->assertEquals(4, count($recurSet), 'odd interval failed');
        $this->assertEquals('2009-06-07 22:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-06-08 21:59:00', $recurSet[0]->dtend->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    /**
     * RRULE:FREQ=MONTHLY
     * => INTEVAL=1;BYMONTHDAY=<monthday of dtstart>
     * 
     */
    public function testCalcMonthlyOmmitByClause()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'monthly without by* clause',
            'dtstart'       => '2011-11-16 10:30:00',
            'dtend'         => '2011-11-16 12:30:00',
            'rrule'         => 'FREQ=MONTHLY',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2011-12-01 00:00:00');
        $until = new Tinebase_DateTime('2011-12-30 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(1, count($recurSet), 'recur set failed');
        $this->assertTrue(array_search('2011-12-16 10:30:00', $recurSet->dtstart) !== FALSE);
    }
    
    public function testCalcMonthlyByMonthDay()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'celebrate my month day',
            'dtstart'       => '1979-06-05 15:00:00',
            'dtend'         => '1979-06-05 16:00:00',
            'rrule'         => 'FREQ=MONTHLY;INTERVAL=1;BYMONTHDAY=5',
            'exdate'        => '2009-02-05 15:00:00,2009-05-05 14:00:00',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event', array(
            array(
                'uid'           => $event->uid,
                'summary'       => 'official birthday party',
                'dtstart'       => '2009-06-05 20:00:00',
                'dtend'         => '2009-06-06 05:00:00',
                'recurid'       => $event->uid . '-' . '2009-06-05 14:00:00',
                Tinebase_Model_Grants::GRANT_EDIT     => true,
            )
        ));
        
        // note: 2009-03-29 Europe/Berlin switched to DST
        $from = new Tinebase_DateTime('2009-01-01 00:00:00');
        $until = new Tinebase_DateTime('2009-06-30 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        
        $this->assertEquals('2009-01-05 15:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-03-05 15:00:00', $recurSet[1]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-04-05 14:00:00', $recurSet[2]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals(3, count($recurSet));
        
        // lets also cover the case when recurevent start during calcualtion period:
        $from = new Tinebase_DateTime('1979-06-01 00:00:00');
        $until = new Tinebase_DateTime('1979-10-31 23:49:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(4, count($recurSet), 'recur start in period failed');
        
        // lets cover the case when search period boudaries are in the middle of the recur events
        // lets also cover the case when recurevent start during calcualtion period:
        $from = new Tinebase_DateTime('2009-03-05 15:30:00');
        $until = new Tinebase_DateTime('2009-04-05 14:30:00');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(2, count($recurSet), 'boundary inclusions failed');
        
        // and finaly lets cover the case when period boundaries are the boundaries of the recur events
        $from = new Tinebase_DateTime('2009-03-05 16:00:00');
        $until = new Tinebase_DateTime('2009-04-05 14:00:00');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(1, count($recurSet), 'boundary exclusion failed');
        $this->assertEquals('2009-04-05 14:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        
        // try odd interval
        $event->rrule = 'FREQ=MONTHLY;INTERVAL=5;BYMONTHDAY=5';
        $from = new Tinebase_DateTime('2009-01-05 00:00:00');
        $until = new Tinebase_DateTime('2009-11-05 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(2, count($recurSet), 'odd interval failed');
        $this->assertEquals('2009-01-05 15:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-11-05 15:00:00', $recurSet[1]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        
    }
    
    /**
     * test dtstart of base event is not dtstart of first recur instance
     */
    public function testCalcMonthlyByMonthDayStart()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'testCalcMonthlyByDayStart',
            'dtstart'       => '2009-07-10 10:00:00',
            'dtend'         => '2009-07-10 11:00:00',
            'rrule'         => 'FREQ=MONTHLY;INTERVAL=1;BYMONTHDAY=20',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2009-07-01 00:00:00');
        $until = new Tinebase_DateTime('2009-08-31 23:59:59');
        
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(2, count($recurSet));
        $this->assertEquals('2009-07-20 10:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testCalcMonthlyByMonthDayStartAllDay()
    {
        $event = new Calendar_Model_Event(array(
            'uid'             => Tinebase_Record_Abstract::generateUID(),
            'summary'         => 'testCalcMonthlyByMonthDayStartAllDay',
            'dtstart'         => '2009-07-14 22:00:00',
            'dtend'           => '2009-07-15 21:59:00',
            'is_all_day_event' => true,
            'rrule'           => 'FREQ=MONTHLY;INTERVAL=1;BYMONTHDAY=15',
            'originator_tz'   => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT       => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2009-06-28 22:00:00');
        $until = new Tinebase_DateTime('2009-09-06 21:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(1, count($recurSet));
        $this->assertEquals('2009-08-14 22:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-08-15 21:59:00', $recurSet[0]->dtend->get(Tinebase_Record_Abstract::ISO8601LONG));

        // test switch from DST to NO DST
        $from = new Tinebase_DateTime('2009-11-30 22:00:00');
        $until = new Tinebase_DateTime('2009-12-31 21:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(1, count($recurSet));
        $this->assertEquals('2009-12-14 23:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-12-15 22:59:00', $recurSet[0]->dtend->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testCalcMonthlyByDay()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'thanks for moni',
            'dtstart'       => '1974-02-11 15:00:00',
            'dtend'         => '1974-02-11 16:00:00',
            'rrule'         => 'FREQ=MONTHLY;INTERVAL=5;BYDAY=4SU',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2009-02-22 00:00:00');
        $until = new Tinebase_DateTime('2009-07-26 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(2, count($recurSet), 'forward skip failed');
        $this->assertEquals('2009-02-22 15:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-07-26 14:00:00', $recurSet[1]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testCalcMonthlyByDaySupressToEarly()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'monthly sencond th',
            'dtstart'       => '2011-11-24 08:00:00',
            'dtend'         => '2011-11-24 10:00:00',
            'rrule'         => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=2TH',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2011-10-31 00:00:00');
        $until = new Tinebase_DateTime('2011-12-04 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(0, count($recurSet), 'events before baseEvent dtstart must not be included');
        
    }
    
    public function testCalcMonthlyByDayBackwardSkip()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'two monthly last wendsday',
            'dtstart'       => '2009-04-29 15:00:00',
            'dtend'         => '2009-04-29 16:00:00',
            'rrule'         => 'FREQ=MONTHLY;INTERVAL=2;BYDAY=-1WE',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event', array(
            array(
                'uid'           => $event->uid,
                'summary'       => 'two monthly last wendsday exception',
                'dtstart'       => '2009-06-24 15:00:00',
                'dtend'         => '2009-06-24 16:00:00',
                'recurid'       => $event->uid . '-' . '2009-06-24 15:00:00',
                Tinebase_Model_Grants::GRANT_EDIT     => true,
            )
        ));
        
        $from = new Tinebase_DateTime('2009-02-01 15:00:00');
        $until = new Tinebase_DateTime('2009-12-31 14:00:00');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(3, count($recurSet), 'backward skip failed');
        $this->assertEquals('2009-08-26 15:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-10-28 16:00:00', $recurSet[1]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-12-30 16:00:00', $recurSet[2]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    /**
     * test dtstart of base event is not dtstart of first recur instance
     */
    public function testCalcMonthlyByDayStart()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'testCalcMonthlyByDayStart',
            'dtstart'       => '2009-07-10 10:00:00',
            'dtend'         => '2009-07-10 11:00:00',
            'rrule'         => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=-1FR',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2009-07-01 00:00:00');
        $until = new Tinebase_DateTime('2009-08-31 23:59:59');
        
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        //print_r($recurSet->toArray());
        $this->assertEquals(2, count($recurSet));
        $this->assertEquals('2009-07-31 10:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testCalcMonthlyByDayStartAllDay()
    {
        
        $event = new Calendar_Model_Event(array(
            'uid'             => Tinebase_Record_Abstract::generateUID(),
            'summary'         => 'testCalcMonthlyByDayStartAllDay',
            'dtstart'         => '2009-07-21 22:00:00',
            'dtend'           => '2009-07-22 21:59:00',
            'is_all_day_event' => true,
            'rrule'           => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=4WE',
            'originator_tz'   => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT       => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2009-06-28 22:00:00');
        $until = new Tinebase_DateTime('2009-09-06 21:59:59');
        
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(1, count($recurSet));
        $this->assertEquals('2009-08-25 22:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-08-26 21:59:00', $recurSet[0]->dtend->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testCalcYearlyByMonthDay()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'yearly by day',
            'dtstart'       => '2009-07-10 10:00:00',
            'dtend'         => '2009-07-10 11:00:00',
            'rrule'         => 'FREQ=YEARLY;INTERVAL=1;BYMONTH=7;BYMONTHDAY=10',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2010-06-08 22:00:00');
        $until = new Tinebase_DateTime('2010-07-31 22:00:00');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(1, count($recurSet));
        $this->assertEquals('2010-07-10 10:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        
        $from = new Tinebase_DateTime('2010-06-10 22:00:00');
        $until = new Tinebase_DateTime('2010-07-31 22:00:00');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(1, count($recurSet));
        $this->assertEquals('2010-07-10 10:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    /**
     * @see #5170
     */
    public function testCalcYearlyByMonthDayAllDayEvent()
    {
        $event = new Calendar_Model_Event(array(
            'uid'               => Tinebase_Record_Abstract::generateUID(),
            'summary'           => 'yearly by day',
            'dtstart'           => '2011-10-31 23:00:00',
            'dtend'             => '2011-11-01 22:59:00',
            'is_all_day_event'  => 1,
            'rrule'             => 'FREQ=YEARLY;INTERVAL=1;BYMONTH=11;BYMONTHDAY=1',
            'originator_tz'     => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2011-10-31 23:00:00');
        $until = new Tinebase_DateTime('2011-12-04 23:00:00');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(0, count($recurSet));
    }
    
    public function testCalcYearlyByMonthDayLeapYear()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'yearly 29.feb',
            'dtstart'       => '2008-02-29 08:00:00',
            'dtend'         => '2008-02-29 10:00:00',
            'rrule'         => 'FREQ=YEARLY;INTERVAL=1;BYMONTH=2;BYMONTHDAY=29',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        // note: 2009-03-29 Europe/Berlin switched to DST
        $from = new Tinebase_DateTime('2008-02-25 00:00:00');
        $until = new Tinebase_DateTime('2013-03-01 23:59:59');
        $until->setTimezone('Europe/Berlin');
        $from->setTimezone('Europe/Berlin');
        
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(1, count($recurSet), 'leapyear only failed');
        $this->assertEquals('2012-02-29 08:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testCalcYearlyByDay()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'yearly last friday in february',
            'dtstart'       => '2008-02-29 08:00:00',
            'dtend'         => '2008-02-29 10:00:00',
            'rrule'         => 'FREQ=YEARLY;INTERVAL=1;BYMONTH=2;BYDAY=-1FR',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2008-02-25 00:00:00');
        $until = new Tinebase_DateTime('2013-03-01 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(5, count($recurSet), 'yearlybyday failed');
    }
    
    /**
     * skip BYMONTHDAY, shift BYMONTH
     *  => respect BYMONTH
     *  => only evaluate first month
     */
    public function testCalcYearlyByMonth()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'yearly with BYMONTH shift',
            'dtstart'       => '2011-11-16 10:30:00',
            'dtend'         => '2011-11-16 12:30:00',
            'rrule'         => 'FREQ=YEARLY;INTERVAL=1;BYMONTH=6,12',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2012-01-01 00:00:00');
        $until = new Tinebase_DateTime('2012-12-31 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(1, count($recurSet), 'recur set failed');
        $this->assertTrue(array_search('2012-06-16 09:30:00', $recurSet->dtstart) !== FALSE);
    }
    
    public function testCalcYearlyOmmitByClause()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'yearly without by* clause',
            'dtstart'       => '2011-11-16 10:30:00',
            'dtend'         => '2011-11-16 12:30:00',
            'rrule'         => 'FREQ=YEARLY',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2012-01-01 00:00:00');
        $until = new Tinebase_DateTime('2012-12-31 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(1, count($recurSet), 'recur set failed');
        $this->assertTrue(array_search('2012-11-16 10:30:00', $recurSet->dtstart) !== FALSE);
    }
    
    /**
     * NOTE: virtual exdates are persistent exceptions -> non persistent exdates 
     *       which might occour due to scopeing or attendee status filtering
     */
    //public function testCalcWithVirtualExdate()
    //{
        // mhh hard to test as we don't deal with persistent stuff in this test class
        // -> move to controller tests
    //}
    
    public function testMultipleTimezonesOriginatingInSeatle()
    {
        date_default_timezone_set('US/Pacific');
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'conference',
            'dtstart'       => '2003-03-28 10:00:00',
            'dtend'         => '2003-03-28 12:00:00',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=FR',
            'originator_tz' => 'US/Pacific',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        $event->setTimezone('UTC');
        date_default_timezone_set('UTC');
        
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2003-04-04 00:00:00');
        $until = new Tinebase_DateTime('2003-04-11 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $recurSet->setTimezone('US/Pacific');
        $this->assertEquals(10, $recurSet[0]->dtstart->get('G'), 'for orginator dtstart should be stable...');
        $this->assertEquals(10, $recurSet[1]->dtstart->get('G'), 'for orginator dtstart should be stable...');
        
        $recurSet->setTimezone('US/Arizona');
        $this->assertEquals(11, $recurSet[0]->dtstart->get('G'), 'for US/Arizona dtstart before DST should be 11');
        $this->assertEquals(10, $recurSet[1]->dtstart->get('G'), 'for US/Arizona dtstart after DST shoud be 10');
        
        $recurSet->setTimezone('America/New_York');
        $this->assertEquals(13, $recurSet[0]->dtstart->get('G'), 'for America/New_York dtstart before DST should be 13');
        $this->assertEquals(13, $recurSet[1]->dtstart->get('G'), 'for America/New_York dtstart after DST shoud be 13');
        
        $recurSet->setTimezone('UTC');
        $this->assertEquals(18, $recurSet[0]->dtstart->get('G'), 'for UTC dtstart before DST should be 18');
        $this->assertEquals(17, $recurSet[1]->dtstart->get('G'), 'for UTC dtstart after DST shoud be 17');
    }
    
    public function testMultipleTimezonesOriginatingInArizona()
    {
        date_default_timezone_set('US/Arizona');
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'conference',
            'dtstart'       => '2003-03-28 11:00:00',
            'dtend'         => '2003-03-28 13:00:00',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=FR',
            'originator_tz' => 'US/Arizona',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        $event->setTimezone('UTC');
        date_default_timezone_set('UTC');
        
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2003-04-04 00:00:00');
        $until = new Tinebase_DateTime('2003-04-11 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $recurSet->setTimezone('US/Pacific');
        $this->assertEquals(10, $recurSet[0]->dtstart->get('G'), 'for US/Pacific dtstart before DST should be 10');
        $this->assertEquals(11, $recurSet[1]->dtstart->get('G'), 'for US/Pacific dtstart before DST should be 11');
        
        $recurSet->setTimezone('US/Arizona');
        $this->assertEquals(11, $recurSet[0]->dtstart->get('G'), 'for orginator dtstart should be stable...');
        $this->assertEquals(11, $recurSet[1]->dtstart->get('G'), 'for orginator dtstart should be stable...');
        
        $recurSet->setTimezone('America/New_York');
        $this->assertEquals(13, $recurSet[0]->dtstart->get('G'), 'for US/Arizona dtstart before DST should be 13');
        $this->assertEquals(14, $recurSet[1]->dtstart->get('G'), 'for US/Arizona dtstart after DST shoud be 14');
        
        $recurSet->setTimezone('UTC');
        $this->assertEquals(18, $recurSet[0]->dtstart->get('G'), 'for UTC dtstart before DST should be 18');
        $this->assertEquals(18, $recurSet[1]->dtstart->get('G'), 'for UTC dtstart after DST shoud be 18');
    }
    
    public function testBrokenRrule()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'weekly with out interval',
            'dtstart'       => '2009-08-28 08:00:00',
            'dtend'         => '2009-08-28 10:00:00',
            'rrule'         => 'FREQ=WEEKLY;BYDAY=TU',
            'originator_tz' => 'Europe/Berlin',
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2009-08-28 00:00:00');
        $until = new Tinebase_DateTime('2009-09-28 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        $this->assertEquals(4, count($recurSet));
        
    }
    
    
    public function testComputeNextOccurrence()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'weekly',
            'dtstart'       => '2009-09-09 08:00:00',
            'dtend'         => '2009-09-09 10:00:00',
            'rrule'         => 'FREQ=WEEKLY;BYDAY=WE,FR;INTERVAL=1;UNTIL=2009-09-27 10:00:00',
            'originator_tz' => 'Europe/Berlin',
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2008-01-21 00:00:00');
        $nextOccurrence = Calendar_Model_Rrule::computeNextOccurrence($event, $exceptions, $from);
        $this->assertTrue($event === $nextOccurrence, 'given event is next occurrence');

        
        $nextOccurrence = Calendar_Model_Rrule::computeNextOccurrence($event, $exceptions, $nextOccurrence->dtstart);
        
        $this->assertEquals('2009-09-11 08:00:00', $nextOccurrence->dtstart->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $nextOccurrence = Calendar_Model_Rrule::computeNextOccurrence($event, $exceptions, $nextOccurrence->dtstart);
        $this->assertEquals('2009-09-16 08:00:00', $nextOccurrence->dtstart->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
    }
    
    public function testComputeNextOccurrenceWithWhich()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'weekly',
            'dtstart'       => '2009-09-09 08:00:00',
            'dtend'         => '2009-09-09 10:00:00',
            'rrule'         => 'FREQ=WEEKLY;BYDAY=WE,FR;INTERVAL=1',
            'originator_tz' => 'Europe/Berlin',
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('now');
        $nextOccurrence = Calendar_Model_Rrule::computeNextOccurrence($event, $exceptions, $from, 0);
        $this->assertEquals($event, $nextOccurrence, 'which = 0 means same event');
        
        $nextOccurrence = Calendar_Model_Rrule::computeNextOccurrence($event, $exceptions, $from, 4);
        $this->assertLessThan($nextOccurrence->dtstart, $from);
        $this->assertGreaterThan($nextOccurrence->dtstart, $from->modify('+ 3 weeks'));
    }
    
    public function testWeeklyWithCount()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'weekly end by count',
            'dtstart'       => '2012-01-10 10:00:00',
            'dtend'         => '2012-01-10 11:00:00',
            'rrule'         => 'FREQ=WEEKLY;BYDAY=TU;INTERVAL=1;COUNT=10',
            'originator_tz' => 'Europe/Berlin',
        ));
        
        $event->setRruleUntil();
        
        $this->assertEquals('2012-03-13 11:00:00', $event->rrule_until->toString(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    
    /************************** date helper tests ***************************/
    
    public function testSkipWday()
    {
        // $_n == +1
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-04-12 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_SUNDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-04-09 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_THURSDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-04-15 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_WEDNESDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-05 00:00:00');
        $this->assertEquals('2009-04-12 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_SUNDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-05 00:00:00');
        $this->assertEquals('2009-04-09 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_THURSDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-05 00:00:00');
        $this->assertEquals('2009-04-08 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_WEDNESDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-04 00:00:00');
        $this->assertEquals('2009-04-05 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_SUNDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-04 00:00:00');
        $this->assertEquals('2009-04-09 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_THURSDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-04 00:00:00');
        $this->assertEquals('2009-04-08 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_WEDNESDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        // $_n == +2
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-04-19 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_SUNDAY, 2)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-04-21 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_TUESDAY, 2)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        // $_n == -1
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-04-06 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_MONDAY, -1)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-04-02 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_THURSDAY, -1)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-04-01 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_WEDNESDAY, -1)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-05 00:00:00');
        $this->assertEquals('2009-04-04 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_SATURDAY, -1)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-04 00:00:00');
        $this->assertEquals('2009-03-29 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_SUNDAY, -1)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        // $_n == -2
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-03-29 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_SUNDAY, -2)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-03-31 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_TUESDAY, -2)->toString(Tinebase_Record_Abstract::ISO8601LONG));
    
        // $_considerDateItself == TRUE
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-04-08 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_WEDNESDAY, 1, TRUE)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-04-08 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_WEDNESDAY, -1, TRUE)->toString(Tinebase_Record_Abstract::ISO8601LONG));
    
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-04-15 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_WEDNESDAY, 2, TRUE)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Tinebase_DateTime('2009-04-08 00:00:00');
        $this->assertEquals('2009-04-01 00:00:00', Calendar_Model_Rrule::skipWday($date, Calendar_Model_Rrule::WDAY_WEDNESDAY, -2, TRUE)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
    }
    
    public function testDatenArrayConverstions()
    {
        $date = new Tinebase_DateTime('1979-06-05 11:22:33');
        $dateArray = array(
            'day'       => 5 ,
            'month'     => 6, 
            'year'      => 1979, 
            'hour'      => 11, 
            'minute'    => 22, 
            'second'    => 33
        );
        
        $this->assertTrue($date->equals(Calendar_Model_Rrule::array2date($dateArray)), 'array2date failed');
        $this->assertEquals($dateArray, Calendar_Model_Rrule::date2array($date), 'date2array failed');
    }
    
    public function testGetMonthDiff()
    {
        $from  = new Tinebase_DateTime('1979-06-05 00:00:00');

        $until = new Tinebase_DateTime('1980-06-05 00:00:00');
        $this->assertEquals(12, Calendar_Model_Rrule::getMonthDiff($from, $until));
        
        $until = new Tinebase_DateTime('1982-07-05 00:00:00');
        $this->assertEquals(37, Calendar_Model_Rrule::getMonthDiff($from, $until));
    }
    
    public function testAddMonthIngnoringDay()
    {
        $date  = new Tinebase_DateTime('2009-01-31 00:00:00');
        for ($i=0; $i<12; $i++) {
            $dateArr = Calendar_Model_Rrule::addMonthIngnoringDay($date, $i);
            $this->assertEquals(31, $dateArr['day']);
            $this->assertEquals($i+1, $dateArr['month']);
            $this->assertEquals(2009, $dateArr['year']);
        }
        
        for ($i=12; $i<24; $i++) {
            $dateArr = Calendar_Model_Rrule::addMonthIngnoringDay($date, $i);
            $this->assertEquals(31, $dateArr['day']);
            $this->assertEquals($i-11, $dateArr['month']);
            $this->assertEquals(2010, $dateArr['year']);
        }
    }
    
    public function testIsRecurException()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => '839404a34a8005d2ebd0da68b6aa922460ae945a',
            'summary'       => 'conference exception (late)',
            'dtstart'       => '2003-03-28 10:00:00',
            'dtend'         => '2003-03-28 12:00:00',
            'originator_tz' => 'US/Pacific',
            'recurid'       => '839404a34a8005d2ebd0da68b6aa922460ae945a-2003-03-28 08:00:00'
        ));
        
        $this->assertTrue($event->isRecurException(), 'recur exception was not detected');
        $this->assertEquals('2003-03-28 08:00:00', $event->getOriginalDtStart()->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testAlarmAdjust()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'custom alarm',
            'dtstart'       => '2011-12-30 10:30:00',
            'dtend'         => '2011-12-30 12:30:00',
            'rrule'         => 'FREQ=YEARLY',
            'originator_tz' => 'Europe/Berlin',
            'alarms'        => array(array(
                'alarm_time'        => '2011-12-20 10:30:00',
                'minutes_before'    => 'custom',
                'options'           => Zend_Json::encode(array(
                    'minutes_before' => 14400,
                    'recurid'        => NULL,
                    'custom'         => TRUE,
                ))
            )),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2012-01-01 00:00:00');
        $until = new Tinebase_DateTime('2012-12-31 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals(1, count($recurSet), 'recur set failed');
        $this->assertEquals('2012-12-20 10:30:00', (string) $recurSet->getFirstRecord()->alarms->getFirstRecord()->alarm_time);
    }
    
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_RruleTests::main') {
    Calendar_RruleTests::main();
}
