<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */


/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Model_AttenderTests::main');
}

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
    
    public function testIsRescheduled()
    {
        $event1 = new Calendar_Model_Event(array(
            'dtstart' => new Tinebase_DateTime('2011-11-23 14:25:00'),
            'dtend'   => new Tinebase_DateTime('2011-11-23 15:25:00'),
            'rrule' => 'FREQ=DAILY;INTERVAL=2',
        ));
        $event2 = clone $event1;
        
        $this->assertFalse($event1->isRescheduled($event2), 'failed same');
        
        $event2->dtstart->addMinute(30);
        $this->assertTrue($event1->isRescheduled($event2), 'failed by dtstart');
        
        $event2 = clone $event1;
        $event2->dtend->addMinute(30);
        $this->assertTrue($event1->isRescheduled($event2), 'failed by dtend');
        
        $event2 = clone $event1;
        $event2->rrule = 'FREQ=DAILY;INTERVAL=1';
        $this->assertTrue($event1->isRescheduled($event2), 'failed by rrule');
    }
}