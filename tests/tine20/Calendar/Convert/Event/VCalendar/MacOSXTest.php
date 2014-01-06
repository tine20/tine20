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
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Convert_Event_VCalendar_MacOSX
 */
class Calendar_Convert_Event_VCalendar_MacOSXTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar WebDAV MacOSX Event Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
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
        $this->assertEquals(1,$event->alarms->count(), 'there should be exactly one alarm');
        $this->assertFalse((bool)$event->alarms->getFirstRecord()->getOption('custom'), 'alarm should be duration alarm');
        $this->assertEquals(15, $event->alarms->getFirstRecord()->minutes_before, 'alarm shoud be 15 min. before');
    }
}
