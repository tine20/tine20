<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
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

    /**
     * testXMozSnooze
     * 
     * @see 0007682: CalDav - Tine - Thunderbird - Palm Pre
     */
    public function testXMozSnooze()
    {
        $vcalendarStream = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning_snooze.ics');
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD);
        
        $event = $converter->toTine20Model($vcalendarStream);
        $alarmSnoozeTime = Calendar_Controller_Alarm::getSnoozeTime($event->alarms->getFirstRecord());
        
        $this->assertTrue($alarmSnoozeTime instanceof DateTime);
        $this->assertEquals('2013-04-12 06:24:46', $alarmSnoozeTime->toString());
    }
    
    /**
     * testXMozAckExdate
     * 
     * @see 0009396: alarm_ack_time and alarm_snooze_time are not updated
     */
    public function testXMozAckExdate()
    {
        $vcalendarStream = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/../../../Import/files/lightning_repeating_exdate_mozlastack.ics');
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD);
        $event = $converter->toTine20Model($vcalendarStream);
        
        $this->assertEquals(1, count($event->alarms));
        $alarmOptions = Zend_Json::decode($event->alarms->getFirstRecord()->options);
        $alarmAckIndex = 'acknowledged-' . Tinebase_Core::getUser()->contact_id;
        $this->assertTrue(array_key_exists($alarmAckIndex, $alarmOptions), 'did not find index ' . $alarmAckIndex . ' in ' . print_r($alarmOptions, true));
        $this->assertEquals('2014-01-08 15:01:54', $alarmOptions[$alarmAckIndex]);
    }
}
