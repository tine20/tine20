<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */


/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Model_Event
 * 
 * @package     Calendar
 */
class Calendar_Model_EventTests extends PHPUnit_Framework_TestCase
{
    /**
     * test isObsoletedBy
     * => same event does not obsolete each other
     */
    public function testIsObsoletedBy()
    {
        $event1 = new Calendar_Model_Event(array(
            'seq'   => 2,
            'last_modified_time' => new Tinebase_DateTime('2011-11-23 14:25:00'),
        ));
        $event2 = new Calendar_Model_Event(array(
            'seq'   => '3',
            'last_modified_time' => new Tinebase_DateTime('2011-11-23 14:26:00'),
        ));
        
        $this->assertTrue($event1->isObsoletedBy($event2), 'failed by seq');
        
        $event1->seq = 3;
        $this->assertTrue($event1->isObsoletedBy($event2), 'failed by modified');
        
        $event1->last_modified_time = clone $event2->last_modified_time;
        $this->assertFalse($event1->isObsoletedBy($event2), 'failed same');
    }
    
    /**
     * testIsRescheduled
     */
    public function testIsRescheduled()
    {
        $event1 = new Calendar_Model_Event(array(
            'dtstart' => new Tinebase_DateTime('2011-11-23 14:25:00'),
            'dtend'   => new Tinebase_DateTime('2011-11-23 15:25:00'),
            'rrule' => 'FREQ=DAILY;INTERVAL=2;UNTIL=2011-12-24 15:25:00',
        ));
        $event2 = clone $event1;
        
        $this->assertFalse($event1->isRescheduled($event2), 'failed same');
        
        $event2->dtstart->addMinute(30);
        $this->assertTrue($event1->isRescheduled($event2), 'failed by dtstart');
        
        $event2 = clone $event1;
        $event2->dtend->addMinute(30);
        $this->assertTrue($event1->isRescheduled($event2), 'failed by dtend');
        
        $event2 = clone $event1;
        $event2->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2011-12-24 15:25:00';
        $this->assertTrue($event1->isRescheduled($event2), 'failed by rrule interval');
        
        $event2 = clone $event1;
        $event2->rrule = 'FREQ=DAILY;INTERVAL=2;UNTIL=2011-12-23 15:25:00';
        $this->assertTrue($event1->isRescheduled($event2), 'failed by rrule until diff greater one day');
        
        $event2 = clone $event1;
        $event2->rrule = 'FREQ=DAILY;INTERVAL=2;UNTIL=2011-12-24 22:59:59';
        $this->assertFalse($event1->isRescheduled($event2), 'failed by rrule until diff less one day');
    }
    
    /**
     * testGetTranslatedValue
     * 
     * @see 0008600: Fix fatal error in Calendar/Model/Event.php
     */
    public function testGetTranslatedValue()
    {
        $event = new Calendar_Model_Event(array(
            'dtstart'   => new Tinebase_DateTime('2011-11-23 14:25:00'),
            'dtend'     => new Tinebase_DateTime('2011-11-23 15:25:00'),
            'summary'   => 'test event',
            'organizer' => Tinebase_Core::getUser()->contact_id
        ));
        
        $translation = Tinebase_Translation::getTranslation('Calendar');
        $timezone = Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::TIMEZONE, Tinebase_Core::getUser()->getId());
        $fileas = Calendar_Model_Event::getTranslatedValue('organizer', $event->organizer, $translation, $timezone);
        
        $userContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        $this->assertEquals($userContact->n_fileas, $fileas);
    }
    
    public function testRruleDiff()
    {
        $event = $event = new Calendar_Model_Event(array(
            'dtstart'   => new Tinebase_DateTime('2011-11-23 14:25:00'),
            'dtend'     => new Tinebase_DateTime('2011-11-23 15:25:00'),
            'rrule'     => 'FREQ=WEEKLY;INTERVAL=1;WKST=MO;BYDAY=TH;UNTIL=2011-12-24 15:25:00',
            'summary'   => 'test event',
            'organizer' => Tinebase_Core::getUser()->contact_id
        ));
        
        $update = clone $event;
        
        $update->rrule = 'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;WKST=MO;UNTIL=2011-12-24 22:59:59';
        $diff = $event->diff($update);
        $this->assertFalse(array_key_exists('rrule', $diff->diff), 'parts order change:' . print_r($diff->toArray(), TRUE));
        
        // real change
        $update->rrule = 'FREQ=WEEKLY;INTERVAL=;BYDAY=TH;WKST=SU';
        $diff = $event->diff($update);
        $this->assertTrue(array_key_exists('rrule',$diff->diff), 'real change should have diff! diff:' . print_r($diff->toArray(), TRUE));
    }
}
