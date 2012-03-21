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

/**
 * Test class for Calendar_Convert_Event_VCalendar_Thunderbird
 */
class Calendar_Convert_Event_VCalendar_ThunderbirdTest extends PHPUnit_Framework_TestCase
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
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        $event = $converter->toTine20Model($vcalendarStream);
        $organizer = Addressbook_Controller_Contact::getInstance()->get($event->organizer);
        
        $this->assertEquals(Calendar_Model_Event::CLASS_PRIVATE, $event->class);
        $this->assertEquals('Hamburg',                           $event->location);
        $this->assertEquals('l.kneschke@metaways.de',            $organizer->email);
        $this->assertGreaterThan(0, count($event->attendee->filter('user_id', $event->organizer)), 'Organizer must be attendee too');
    }
}
