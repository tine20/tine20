<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Controller_RecurTest::main');
}

/**
 * Test class for Calendar_Controller_Event
 * 
 * @package     Calendar
 */
class Calendar_Controller_RecurTest extends Calendar_TestCase
{
    /**
     * @var Calendar_Controller_Event controller
     */
    protected $_controller;
    
    public function setUp()
    {
    	parent::setUp();
        $this->_controller = Calendar_Controller_Event::getInstance();
    }
    
    public function testFirstInstanceExcepetion()
    {
        $from = new Tinebase_DateTime('2011-04-18 00:00:00');
        $until = new Tinebase_DateTime('2011-04-24 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2011-04-20 14:00:00',
            'dtend'         => '2011-04-20 15:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=3;WKST=SU;BYDAY=TU,TH',
            'container_id'  => $this->_testCalendar->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        $eventException = clone $persistentEvent;
        $eventException->summary = 'Dinner';
        $eventException->dtstart->addHour(2);
        $eventException->dtend->addHour(2);
        $persistentEventException = $this->_controller->createRecurException($eventException);
        
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        )));
        
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        $this->assertEquals(2, count($weekviewEvents), 'there should only be 2 events in the set');
        $this->assertFalse(in_array($persistentEvent->getId(), $weekviewEvents->getId()), 'baseEvent should not be in the set!');
    }
    
    /**
     * http://forge.tine20.org/mantisbt/view.php?id=4810
     */
    public function testWeeklyException()
    {
        $from = new Tinebase_DateTime('2011-09-01 00:00:00');
        $until = new Tinebase_DateTime('2011-09-30 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'uid'               => Tinebase_Record_Abstract::generateUID(),
            'summary'           => 'weekly',
            'dtstart'           => '2011-09-11 22:00:00',
            'dtend'             => '2011-09-12 21:59:59',
            'is_all_day_event'  => true,
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,TU,WE,TH',
            'container_id'  => $this->_testCalendar->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        )));
        
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        $this->assertEquals(12, count($weekviewEvents), 'there should be 12 events in the set');
        
        // delte one instance
        $exception = $weekviewEvents->filter('dtstart', new Tinebase_DateTime('2011-09-19 22:00:00'))->getFirstRecord();
        $persistentEventException = $this->_controller->createRecurException($exception, TRUE);
        
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        )));
        
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        $this->assertEquals(11, count($weekviewEvents), 'there should be 11 events in the set');
        
        $exception = $weekviewEvents->filter('dtstart', new Tinebase_DateTime('2011-09-19 22:00:00'))->getFirstRecord();
        $this->assertTrue(!$exception, 'exception must not be in eventset');
    }
    
    public function testAttendeeSetStatusRecurException()
    {
        // note: 2009-03-29 Europe/Berlin switched to DST
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2009-03-25 18:00:00',
            'dtend'         => '2009-03-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-03-31 17:30:00',
            'exdate'        => '2009-03-27 18:00:00,2009-03-29 17:00:00',
            'container_id'  => $this->_testCalendar->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        $event->attendee = $this->_getAttendee();
        unset($event->attendee[1]);
        
        $persistentEvent = $this->_controller->create($event);
        $attendee = $persistentEvent->attendee[0];
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $from = new Tinebase_DateTime('2009-03-26 00:00:00');
        $until = new Tinebase_DateTime('2009-04-01 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        
        $exception = $recurSet->getFirstRecord();
        $attendee = $exception->attendee[0];
        $attendee->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        
        $this->_controller->attenderStatusCreateRecurException($exception, $attendee, $attendee->status_authkey);
        
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
            array('field' => 'uid', 'operator' => 'equals', 'value' => $persistentEvent->uid)
        )));
        
        $recurid = array_values(array_filter($events->recurid));
        $this->assertEquals(1, count($recurid), 'only recur instance must have a recurid');
        $this->assertEquals('2009-03-26 18:00:00', substr($recurid[0], -19));
        $this->assertEquals(2, count($events));
    }
    
    public function testFirstInstanceAttendeeSetStatusRecurException()
    {
        $from = new Tinebase_DateTime('2011-04-18 00:00:00');
        $until = new Tinebase_DateTime('2011-04-24 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2011-04-20 14:00:00',
            'dtend'         => '2011-04-20 15:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=3;WKST=SU;BYDAY=TU,TH',
            'container_id'  => $this->_testCalendar->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        $event->attendee = $this->_getAttendee();
        unset($event->attendee[1]);
        
        $persistentEvent = $this->_controller->create($event);
        $attendee = $persistentEvent->attendee[0];
        $attendee->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        
        $this->_controller->attenderStatusCreateRecurException(clone $persistentEvent, $attendee, $attendee->status_authkey);
        
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        )));
        
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        
        $this->assertEquals(2, count($weekviewEvents), 'there should only be 2 events in the set');
        $this->assertFalse(in_array($persistentEvent->getId(), $weekviewEvents->getId()), 'baseEvent should not be in the set!');
    }
    
    /**
     * Conflict between an existing and recurring event when create the event
     */
    public function testCreateConflictBetweenRecurAndExistEvent()
    {
        $event = $this->_getEvent();
        $event->dtstart = '2010-05-20 06:00:00';
        $event->dtend = '2010-05-20 06:15:00';
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        $this->_controller->create($event);

        $event1 = $this->_getRecurEvent();
        $event1->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        
        $this->setExpectedException('Calendar_Exception_AttendeeBusy');
        $this->_controller->create($event1, TRUE);
    }
    
    /**
     * Conflict between an existing and recurring event when update the event
     */
    public function testUpdateConflictBetweenRecurAndExistEvent()
    {
        $event = $this->_getEvent();
        $event->dtstart = '2010-05-20 06:00:00';
        $event->dtend = '2010-05-20 06:15:00';
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        $this->_controller->create($event);

        $event1 = $this->_getRecurEvent();
        $event1->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        
        $event1 = $this->_controller->create($event1);
        $event1->rrule = "FREQ=DAILY;INTERVAL=2";
        
        $this->setExpectedException('Calendar_Exception_AttendeeBusy');
        $this->_controller->update($event1, TRUE);
    }
    
    /**
     * check that fake clones of dates of persistent exceptions are left out in recur set calculation
     */
    public function testRecurSetCalcLeafOutPersistentExceptionDates()
    {
        // month 
        $from = new Tinebase_DateTime('2010-06-01 00:00:00');
        $until = new Tinebase_DateTime('2010-06-31 23:59:59');
        
        $event = $this->_getRecurEvent();
        $event->rrule = "FREQ=MONTHLY;INTERVAL=1;BYDAY=3TH";
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        
        $persistentRecurEvent = $this->_controller->create($event);
        
        // get first recurrance
        $eventSet = new Tinebase_Record_RecordSet('Calendar_Model_Event', array($persistentRecurEvent));
        Calendar_Model_Rrule::mergeRecurrenceSet($eventSet, 
            new Tinebase_DateTime('2010-06-01 00:00:00'),
            new Tinebase_DateTime('2010-06-31 23:59:59')
        );
        $firstRecurrance = $eventSet[1];
        
        // create exception of this first occurance: 17.6. -> 24.06.
        $firstRecurrance->dtstart->add(1, Tinebase_DateTime::MODIFIER_WEEK);
        $firstRecurrance->dtend->add(1, Tinebase_DateTime::MODIFIER_WEEK);
        $this->_controller->createRecurException($firstRecurrance);
        
        // fetch weekview 14.06 - 20.06.
        $from = new Tinebase_DateTime('2010-06-14 00:00:00');
        $until = new Tinebase_DateTime('2010-06-20 23:59:59');
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid', 'operator' => 'equals', 'value' => $persistentRecurEvent->uid),
            array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $from, 'until' => $until),
        ))));
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        
        // make shure the 17.6. is not in the set
        $this->assertEquals(1, count($weekviewEvents), '17.6. is an exception date and must not be part of this weekview');
    }
    
    public function testCreateRecurExceptionAllFollowing()
    {
        $from = new Tinebase_DateTime('2011-04-21 00:00:00');
        $until = new Tinebase_DateTime('2011-04-28 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Latte bei Schweinske',
            'dtstart'       => '2011-04-21 10:00:00',
            'dtend'         => '2011-04-21 12:00:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=2011-04-28 12:00:00',
            'container_id'  => $this->_testCalendar->getId()
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        
        // create exceptions
        $recurSet->summary = 'Limo bei Schweinske';
        $recurSet[5]->dtstart->addHour(2);
        $recurSet[5]->dtend->addHour(2);
        
        $this->_controller->createRecurException($recurSet[1], TRUE);  // (23) delete instance
        $this->_controller->createRecurException($recurSet[2], FALSE); // (24) move instance
        $this->_controller->createRecurException($recurSet[4], TRUE);  // (26) delete instance
        $this->_controller->createRecurException($recurSet[5], FALSE); // (27) move instance
        
        // now test update allfollowing
        $recurSet[3]->summary = 'Spezi bei Schwinske';
        $recurSet[3]->dtstart->addHour(4);
        $recurSet[3]->dtend->addHour(4);
        
        $newBaseEvent = $this->_controller->createRecurException($recurSet[3], FALSE, TRUE);
        
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $from, 'until' => $until),
        ))));
        
        Calendar_Model_Rrule::mergeRecurrenceSet($events, $from, $until);
        
        $this->assertEquals(6, count($events), 'there should be exactly 6 events');
        
        $oldSeries = $events->filter('uid', $persistentEvent->uid);
        $newSeries = $events->filter('uid', $newBaseEvent->uid);
        $this->assertEquals(3, count($oldSeries), 'there should be exactly 3 events with old uid');
        $this->assertEquals(3, count($newSeries), 'there should be exactly 3 events with new uid');
        
        $this->assertEquals(1, count($oldSeries->filter('recurid', "/^$/", TRUE)), 'there should be exactly one old base event');
        $this->assertEquals(1, count($newSeries->filter('recurid', "/^$/", TRUE)), 'there should be exactly one new base event');
        
        $this->assertEquals(1, count($oldSeries->filter('recurid', "/^.+/", TRUE)->filter('rrule', '/^$/', TRUE)), 'there should be exactly one old persitent event exception');
        $this->assertEquals(1, count($newSeries->filter('recurid', "/^.+/", TRUE)->filter('rrule', '/^$/', TRUE)), 'there should be exactly one new persitent event exception');
        
        $this->assertEquals(1, count($oldSeries->filter('id', "/^fake.*/", TRUE)), 'there should be exactly one old fake event');
        $this->assertEquals(1, count($newSeries->filter('id', "/^fake.*/", TRUE)), 'there should be exactly one new fake event'); //26 (reset)
        
        $oldBaseEvent = $oldSeries->filter('recurid', "/^$/", TRUE)->getFirstRecord();
        $newBaseEvent = $newSeries->filter('recurid', "/^$/", TRUE)->getFirstRecord();
        
        $this->assertFalse(!!array_diff($oldBaseEvent->exdate, array(
            new Tinebase_DateTime('2011-04-23 10:00:00'),
            new Tinebase_DateTime('2011-04-24 10:00:00'),
        )), 'exdate of old series');
        
        $this->assertFalse(!!array_diff($newBaseEvent->exdate, array(
            new Tinebase_DateTime('2011-04-27 14:00:00'),
        )), 'exdate of new series');
        
        $this->assertFalse(!!array_diff($oldSeries->dtstart, array(
            new Tinebase_DateTime('2011-04-21 10:00:00'),
            new Tinebase_DateTime('2011-04-22 10:00:00'),
            new Tinebase_DateTime('2011-04-24 10:00:00'),
        )), 'dtstart of old series');
        
        $this->assertFalse(!!array_diff($newSeries->dtstart, array(
            new Tinebase_DateTime('2011-04-25 14:00:00'),
            new Tinebase_DateTime('2011-04-26 14:00:00'),
            new Tinebase_DateTime('2011-04-27 12:00:00'),
        )), 'dtstart of new series');
    }
    
    /**
     * returns a simple recure event
     *
     * @return Calendar_Model_Event
     */
    protected function _getRecurEvent()
    {
        return new Calendar_Model_Event(array(
            'summary'     => 'Breakfast',
            'dtstart'     => '2010-05-20 06:00:00',
            'dtend'       => '2010-05-20 06:15:00',
            'description' => 'Breakfast',
            'rrule'       => 'FREQ=DAILY;INTERVAL=1',    
            'container_id' => $this->_testCalendar->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true,
        ));
    }
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_Controller_RecurTest::main') {
    Calendar_Controller_RecurTest::main();
}
