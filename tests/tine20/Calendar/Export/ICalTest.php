<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_ICalTests
 */
class Calendar_Export_ICalTest extends PHPUnit_Framework_TestCase //extends Calendar_TestCase
{
    public function testExport()
    {
        $event = new Calendar_Model_Event(array(
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
        
        $ics = Calendar_Export_Ical::eventToIcal($event);
//        echo $ics;

        // assert basics
        $this->assertEquals(1, preg_match("/SUMMARY:{$event->summary}\r\n/", $ics), 'SUMMARY not correct');
        
        // assert dtstart/dtend tz
        $this->assertEquals(1, preg_match("/DTSTART;TZID=Europe\/Berlin:20101230T130000\r\n/", $ics), 'DTSTART not correct');
        $this->assertEquals(1, preg_match("/DTEND;TZID=Europe\/Berlin:20101230T140000\r\n/", $ics), 'DTEND not correct');
        
        // assert vtimezone
        $this->assertEquals(1, preg_match("/BEGIN:VTIMEZONE\r\n/", $ics), 'VTIMEZONE missing');
        $this->assertEquals(1, preg_match("/BEGIN:DAYLIGHT\r\nTZOFFSETFROM:\+0100\r\nTZOFFSETTO:\+0200\r\nRRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\nEND:DAYLIGHT\r\n/", $ics), 'DAYLIGHT not correct');

        // assert rrule
        $this->assertEquals(1, preg_match("/RRULE:FREQ=DAILY;INTERVAL=1;UNTIL=20151230T130000Z\r\n/", $ics), 'RRULE broken');
    }

}