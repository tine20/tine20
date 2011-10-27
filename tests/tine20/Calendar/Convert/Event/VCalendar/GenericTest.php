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

        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->class);
        $this->assertEquals('Hamburg',                           $event->location);
        $this->assertEquals('Europe/Berlin',                     $event->originator_tz);
        $this->assertEquals("2011-10-04 10:00:00",               (string)$event->dtend);
        $this->assertEquals("2011-10-04 08:00:00",               (string)$event->dtstart);
        
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
        
        #var_dump($event->dtstart);
        #var_dump($event->dtend);
        
        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->class);
        $this->assertEquals('Hamburg',                           $event->location);
        $this->assertEquals("2011-10-19 23:59:59",               (string)$event->dtend   , 'DTEND mismatch');
        $this->assertEquals("2011-10-19 00:00:00",               (string)$event->dtstart , 'DTSTART mismatch');
        $this->assertTrue($event->is_all_day_event , 'All day event mismatch');
    
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
     * test converting vcard from sogo connector to Calendar_Model_Event 
     */
    public function testConvertToTine20ModelWithUpdate()
    {
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/lightning.ics', 'r');
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $event = $converter->toTine20Model($vcalendarStream);

        // status_authkey must be kept after second convert
        $event->attendee[0]->status_authkey = 'FooBar';
        
        rewind($vcalendarStream);
        $event = $converter->toTine20Model($vcalendarStream, $event);
        
        $this->assertEquals('FooBar', $event->attendee[0]->status_authkey);
    }    

    /**
     * 
     * @depends testConvertToTine20Model
     */
    public function testConvertFromTine20Model()
    {
        $event = $this->testConvertToTine20Model();
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $vevent = $converter->fromTine20Model($event);
        
        // required fields
        $this->assertContains('VERSION:2.0', $vevent, $vevent);
        $this->assertContains('PRODID:-//tine20.org//Calendar', $vevent, $vevent);
        $this->assertContains('LOCATION:Hamburg', $vevent, $vevent);
        $this->assertContains('CLASS:PRIVATE', $vevent, $vevent);
    }
}
