<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Controller_MSEventFacade
 * 
 * @package     Calendar
 */
class Calendar_Controller_MSEventFacadeTest extends Calendar_TestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->_uit = Calendar_Controller_MSEventFacade::getInstance();
        $this->_uit->setEventFilter(new Calendar_Model_EventFilter(array(
            array('field' => 'attender', 'operator' => 'equals', 'value' => array(
                'user_type'    => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'      => Tinebase_Core::getUser()->contact_id,
            )),
            array(
                'field' => 'attender_status', 'operator' => 'notin', 'value' => array(
                    Calendar_Model_Attender::STATUS_DECLINED
                )
        ))));
    }
    
    public function getTestEvent()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $event->attendee[1]->transp = Calendar_Model_Event::TRANSP_TRANSP;
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            array('minutes_before' => 15),
            array('minutes_before' => 30),
            array('minutes_before' => 'custom', 'alarm_time' => '2009-03-25 04:33:00'),
            array('minutes_before' => 60),
            array('minutes_before' => 90),
        ), TRUE);
        $event->alarms[0]->setOption('skip', array(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_testUserContact->getId()
        )));
        $event->alarms[1]->setOption('attendee', array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_testUserContact->getId()
        ));
        $event->alarms[2]->setOption('skip', array(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_personasContacts['sclever']->getId()
        )));
        $event->alarms[3]->setOption('attendee', array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_personasContacts['sclever']->getId()
        ));
        
        $persistentException = clone $event;
        $persistentException->recurid = clone $persistentException->dtstart;
        $persistentException->recurid->addDay(1);
        $persistentException->dtstart->addDay(1)->addHour(2);
        $persistentException->dtend->addDay(1)->addHour(2);
        $persistentException->summary = 'exception';
        $exceptions->addRecord($persistentException);
        
        $deletedInstance = clone $event;
        $deletedInstance->dtstart->addDay(2);
        $deletedInstance->dtend->addDay(2);
        $deletedInstance->recurid = clone $deletedInstance->dtstart;
        $deletedInstance->is_deleted = TRUE;
        $exceptions->addRecord($deletedInstance);
        
        $event->exdate = $exceptions;
        return $event;
    }
    
    public function testCreate()
    {
        $event = $this->getTestEvent();
        
        $persistentEvent = $this->_uit->create($event);
        
        $this->_assertTestEvent($persistentEvent);
        
        return $persistentEvent;
    }
    
    public function testGet()
    {
        $event = $this->getTestEvent();
        
        $persistentEvent = $this->_uit->create($event);
        $persistentEvent = $this->_uit->get($persistentEvent->getId());
        
        $this->_assertTestEvent($persistentEvent);
    }
    
    public function testDelete()
    {
        $event = $this->getTestEvent();
        
        $persistentEvent = $this->_uit->create($event);
        $persistentEvent = $this->_uit->delete($event->getId());
    }
    
    public function testSearch()
    {
        $this->testCreate();
        
        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => $this->_testCalendars->getId()),
        )));
        
        $this->assertEquals(1, $events->count());
        $this->_assertTestEvent($events->getFirstRecord());
    }
    
    public function testSearchBaselessExceptions()
    {
        $event = $this->testCreate();
        
        // move baseEvent out of scope
        $cbe = new Calendar_Backend_Sql();
        $cbe->delete($event->getId());
        
        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => $this->_testCalendars->getId()),
        )));
        
        $this->assertEquals(1, $events->count());
        $this->assertEquals('exception', $events->getFirstRecord()->summary);
        
    }
    
    /**
     * test organizer based updates 
     */
    public function testUpdateFromOrganizer()
    {
        $event = $this->testCreate();
        
        // fake what the client does
        $event->alarms->setId(NULL);
        foreach ($event->exdate->alarms as $alarms) {
            $alarms->setId(NULL);
        }
        
        $this->_fixConcurrency($event);
        $event = $this->_uit->update($event);
        $this->_assertTestEvent($event);
        
        $sclever = new Calendar_Model_Attender(array(
            'user_id'        => $this->_personasContacts['sclever']->getId(),
            'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
        ));
        
        $currUser = $this->_uit->setCalendarUser($sclever);
        $event = $this->_uit->get($event->getId());
        $this->_uit->setCalendarUser($currUser);
        
        $this->assertEquals(Calendar_Model_Event::TRANSP_TRANSP, $event->transp, 'transp not from perspective');
        $this->assertEquals(3, $event->alarms->count(), 'alarms for 15, 60, 90 should be present for sclever'); 
        $this->assertEquals(1, $event->alarms->filter('minutes_before', 15)->count(), '15 min. before is not present');
        $this->assertEquals(1, $event->alarms->filter('minutes_before', 60)->count(), '60 min. before is not present');
        $this->assertEquals(1, $event->alarms->filter('minutes_before', 90)->count(), '90 min. before is not present');
    }
    
    /**
     * adjusts seq for event to prevent concurrency errors
     * 
     * @param Calendar_Model_Event $event
     */
    protected function _fixConcurrency($event)
    {
        $event->seq = 2;
    }
    
    /**
     * test attendee based updates 
     */
    public function testUpdateFromAttendee()
    {
        $event = $this->testCreate();
        
        $sclever = new Calendar_Model_Attender(array(
            'user_id'        => $this->_personasContacts['sclever']->getId(),
            'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
        ));
        $currUser = $this->_uit->setCalendarUser($sclever);
        $event = $this->_uit->get($event->getId());
        
        // fake what the client does
        $event->alarms->setId(NULL);
        $event->alarms->addRecord(new Tinebase_Model_Alarm(array(
            'minutes_before' => 5,
        ), TRUE));
        $event = $this->_uit->update($event);
        $this->_uit->setCalendarUser($currUser);
        
        $this->assertEquals(Calendar_Model_Event::TRANSP_TRANSP, $event->transp, 'transp not from perspective');
        $this->assertEquals(4, $event->alarms->count(), 'alarms for 5, 15, 60, 90 should be present for sclever'); 
        $this->assertEquals(1, $event->alarms->filter('minutes_before', 5)->count(), '5 min. before is not present');
    }
    
    public function testUpdateRemoveExceptions()
    {
        $event = $this->testCreate();

        $this->_fixConcurrency($event);
        $event->exdate = NULL;
        $updatedEvent = $this->_uit->update($event);
        
        $this->assertEquals(0, $updatedEvent->exdate->count());
    }
    
    public function testUpdateCreateExceptions()
    {
        $event = $this->testCreate();
        
        $newPersistentException = clone $event->exdate->filter('is_deleted', 0)->getFirstRecord();
        $newPersistentException->recurid = clone $event->dtstart;
        $newPersistentException->recurid->addDay(3);
        $newPersistentException->dtstart->addDay(2)->addHour(2);
        $newPersistentException->dtend->addDay(2)->addHour(2);
        $newPersistentException->summary = 'new exception';
        $event->exdate->addRecord($newPersistentException);
        
        $newDeletedInstance = clone $event->exdate->filter('is_deleted', 1)->getFirstRecord();
        $newDeletedInstance->dtstart->addDay(2);
        $newDeletedInstance->dtend->addDay(2);
        $newDeletedInstance->recurid = clone $newDeletedInstance->dtstart;
        $newDeletedInstance->is_deleted = TRUE;
        $event->exdate->addRecord($newDeletedInstance);
        
        $this->_fixConcurrency($event);
        $updatedEvent = $this->_uit->update($event);
        
        $this->assertEquals(4, $updatedEvent->exdate->count());
    }
    
    public function testUpdateUpdateExceptions()
    {
        $event = $this->testCreate();
        
        $persistentException = $event->exdate->filter('is_deleted', 0)->getFirstRecord();
        $persistentException->dtstart->addHour(2);
        $persistentException->dtend->addHour(2);
        $persistentException->summary = 'updated exception';
        
        $this->_fixConcurrency($event);
        $updatedEvent = $this->_uit->update($event);
        
        $this->assertEquals(2, $updatedEvent->exdate->count());
        $updatedPersistentException = $updatedEvent->exdate->filter('is_deleted', 0)->getFirstRecord();
        $this->assertEquals('updated exception', $updatedPersistentException->summary);
        $this->assertEquals('2009-03-26 10:00:00', $updatedPersistentException->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    /**
     * testUpdatePreserveAlarmProperties
     * 
     * @see #7430: Calendar sends too much alarms for recurring events
     */
    public function testUpdatePreserveAlarmProperties()
    {
        $alarm30 = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            array('minutes_before' => 30),
        ), TRUE);
        
        $event = $this->_getEvent();
        $event->dtstart = Tinebase_DateTime::now()->subDay(1)->addMinute(15);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addHour(2);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;COUNT=3';
        $event->alarms = clone $alarm30;
        $event = Calendar_Controller_Event::getInstance()->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $event->dtstart, Tinebase_DateTime::now()->addDay(1));
        $exceptionEvent = Calendar_Controller_Event::getInstance()->createRecurException($recurSet->getFirstRecord());
        
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        Calendar_Controller_EventNotificationsTests::flushMailer();
        
        $event = $this->_uit->get($event->getId());
        $persistentAlarm = $event->exdate[0]->alarms->getFirstRecord();
        $event->alarms = $event->alarms = clone $alarm30;
        foreach ($event->exdate as $exdate) {
            $exdate->alarms = clone $alarm30;
        }
        $updatedEvent = $this->_uit->update($event);
        $updatedAlarm = $updatedEvent->exdate[0]->alarms->getFirstRecord();
        
        $diff = $persistentAlarm->diff($updatedAlarm);
        $this->assertTrue(empty($diff));
    }
    
    public function testAttendeeStatusUpdate()
    {
        $event = $this->testCreate();
        
        $testAttendee = new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => Tinebase_Core::getUser()->contact_id,
        ));
        
        // update base events status
        Calendar_Model_Attender::getAttendee($event->attendee, $testAttendee)->status = Calendar_Model_Attender::STATUS_TENTATIVE;
        $updatedEvent = $this->_uit->attenderStatusUpdate($event, $testAttendee);
        
        $this->assertEquals(2, count($updatedEvent->exdate), 'num exdate mismatch');
        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, Calendar_Model_Attender::getAttendee($updatedEvent->attendee, $testAttendee)->status, 'status of baseevent was not updated');
        $this->assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, Calendar_Model_Attender::getAttendee($updatedEvent->exdate->filter('is_deleted', 0)->getFirstRecord()->attendee, $testAttendee)->status, 'status of exdate must not be updated');
        
        
        // update exiting persitent exception
        Calendar_Model_Attender::getAttendee($updatedEvent->exdate->filter('is_deleted', 0)->getFirstRecord()->attendee, $testAttendee)->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        $updatedEvent = $this->_uit->attenderStatusUpdate($updatedEvent, $testAttendee);
        
        $this->assertEquals(2, count($updatedEvent->exdate), 'persistent exdate num exdate mismatch');
        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, Calendar_Model_Attender::getAttendee($updatedEvent->attendee, $testAttendee)->status, 'persistent exdate status of baseevent was not updated');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, Calendar_Model_Attender::getAttendee($updatedEvent->exdate->filter('is_deleted', 0)->getFirstRecord()->attendee, $testAttendee)->status, 'persistent exdate status of exdate must not be updated');
        
        // new exception
        $newException = clone $event;
        $newException->id = NULL;
        $newException->recurid = clone $newException->dtstart;
        $newException->recurid->addDay(3);
        $newException->dtstart->addDay(3)->addHour(2);
        $newException->dtend->addDay(3)->addHour(2);
        $newException->summary = 'new exception';
        $newException->exdate = NULL;
        $updatedEvent->exdate->addRecord($newException);
        
        Calendar_Model_Attender::getAttendee($newException->attendee, $testAttendee)->status = Calendar_Model_Attender::STATUS_DECLINED;
        $updatedEvent = $this->_uit->attenderStatusUpdate($updatedEvent, $testAttendee);
        
        $this->assertEquals(3, count($updatedEvent->exdate), 'new exdate num exdate mismatch');
    }
    
    /**
     * sclever declines event exception.
     * => from her iTIP perspective, with the filter, this is an fallout than
     */
    public function testPerspectiveExceptionFallout()
    {
        $event = $this->testCreate();
        
        $persistentException = $event->exdate->filter('is_deleted', 0)->getFirstRecord();
        
        $sclever = new Calendar_Model_Attender(array(
            'user_id'        => $this->_personasContacts['sclever']->getId(),
            'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
        ));
        
        $persistentSClever = Calendar_Model_Attender::getAttendee($persistentException->attendee, $sclever);
        $persistentException->attendee->removeRecord($persistentSClever);
        
        $currUser = $this->_uit->setCalendarUser($sclever);
        $this->_uit->setEventFilter(new Calendar_Model_EventFilter(array(
            array('field' => 'attender', 'operator' => 'equals', 'value' => array(
                'user_type'    => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'      => $this->_personasContacts['sclever']->getId(),
            )),
            array(
                'field' => 'attender_status', 'operator' => 'notin', 'value' => array(
                    Calendar_Model_Attender::STATUS_DECLINED
                )
        ))));
        
        $this->_fixConcurrency($event);
        $event = $this->_uit->update($event);
        
        $event = $this->_uit->get($event->getId());
        $this->_uit->setCalendarUser($currUser);
        
        $persistentException = $event->exdate->filter('is_deleted', 0)->getFirstRecord();
        $this->assertNull($persistentException);
    }
    
    /**
     * asserts tested event
     * 
     * @param Calendar_Model_Event $persistentEvent
     */
    protected function _assertTestEvent($persistentEvent)
    {
        $this->assertEquals(2, $persistentEvent->exdate->count());
        
        $this->assertEquals(Calendar_Model_Event::TRANSP_OPAQUE, $persistentEvent->transp, 'base transp from perspective');
        $this->assertEquals(3, count($persistentEvent->alarms), 'base alarms not from perspective');
        $this->assertEquals(0, count($persistentEvent->alarms->filter('minutes_before', 15)), '15 min. before is not skipped');
        $this->assertEquals(0, count($persistentEvent->alarms->filter('minutes_before', 60)), '60 min. before is not for test CU');
        
        $persistException = $persistentEvent->exdate->filter('is_deleted', 0)->getFirstRecord();
        $this->assertEquals('2009-03-26 08:00:00', $persistException->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-03-26 06:00:00', $persistException->getOriginalDtStart()->format(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('exception', $persistException->summary);
        $this->assertEquals(Calendar_Model_Event::TRANSP_OPAQUE, $persistException->transp, 'recur transp from perspective');
        $this->assertEquals(3, count($persistException->alarms), 'exception alarms not from perspective');
        $this->assertEquals(0, count($persistException->alarms->filter('minutes_before', 15)), '15 min. before is not skipped');
        $this->assertEquals(0, count($persistException->alarms->filter('minutes_before', 60)), '60 min. before is not for test CU');
        
        $deletedInstance = $persistentEvent->exdate->filter('is_deleted', 1)->getFirstRecord();
        $this->assertEquals('2009-03-27 06:00:00', $deletedInstance->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
}
