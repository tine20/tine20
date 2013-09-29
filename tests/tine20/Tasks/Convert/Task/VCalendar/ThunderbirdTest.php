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
 * Test class for Tasks_Convert_Task_VCalendar_Thunderbird
 */
class Tasks_Convert_Task_VCalendar_ThunderbirdTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar WebDAV Thunderbird Event Tests');
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
        $this->markTestIncomplete('tests not yet implemented');
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
     */
    public function testConvertToTine20Model()
    {
        $vcalendarStream = fopen(dirname(__FILE__) . '/../../../Import/files/lightning.ics', 'r');
        
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_GENERIC);
        
        $event = $converter->toTine20Model($vcalendarStream);
        $organizer = Addressbook_Controller_Contact::getInstance()->get($event->organizer);
        
        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->class);
        $this->assertEquals('Hamburg',                           $event->location);
        $this->assertEquals('l.kneschke@metaways.de',            $organizer->email);
        $this->assertGreaterThan(0, count($event->attendee->filter('user_id', $event->organizer)), 'Organizer must be attendee too');
    }

    /**
     * testXMozSnooze
     * 
     * @see 0007682: CalDav - Tine - Thunderbird - Palm Pre
     */
    public function testXMozSnooze()
    {
        $vcalendarStream = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning_snooze.ics');
        $converter = Tasks_Convert_Task_VCalendar_Factory::factory(Tasks_Convert_Task_VCalendar_Factory::CLIENT_THUNDERBIRD);
        
        $event = $converter->toTine20Model($vcalendarStream);
        $this->assertTrue(isset($event->attendee[0]->alarm_snooze_time));
        $this->assertEquals('2013-04-12 06:24:46', $event->attendee[0]->alarm_snooze_time->toString());
    }
}
