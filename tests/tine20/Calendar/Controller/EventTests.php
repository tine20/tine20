<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_Controller_EventTests::main');
}

/**
 * Test class for Calendar_Controller_Event
 * 
 * @package     Calendar
 */
class Calendar_Controller_EventTests extends Calendar_TestCase
{
    
    /**
     * @var Calendar_Controller_Event controller unter test
     */
    protected $_controller;
    
    /**
     * @var Tinebase_Model_Container
     */
    protected $_testCalendar;
    
    public function setUp()
    {
    	parent::setUp();
        $this->_controller = Calendar_Controller_Event::getInstance();

    }
    
    public function testCreateEvent()
    {
        $event = $this->_getEvent();
        $persitentEvent = $this->_controller->create($event);
        
        $this->assertEquals($event->description, $persitentEvent->description);
        $this->assertTrue($event->dtstart->equals($persitentEvent->dtstart));
        $this->assertEquals(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE), $persitentEvent->originator_tz);
        
        return $persitentEvent;
    }
    
    public function testGetEvent()
    {
        $persitentEvent = $this->testCreateEvent();
        $this->assertTrue((bool) $persitentEvent->{Tinebase_Model_Grants::GRANT_READ});
        $this->assertTrue((bool) $persitentEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertTrue((bool) $persitentEvent->{Tinebase_Model_Grants::GRANT_DELETE});
        
        $loadedEvent = $this->_controller->get($persitentEvent->getId());
        $this->assertTrue((bool) $loadedEvent->{Tinebase_Model_Grants::GRANT_READ});
        $this->assertTrue((bool) $loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertTrue((bool) $loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    public function testUpdateEvent()
    {
        $persitentEvent = $this->testCreateEvent();
        
        $currentTz = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, 'farfaraway');
        
        $persitentEvent->summary = 'Lunchtime';
        $updatedEvent = $this->_controller->update($persitentEvent);
        $this->assertEquals($persitentEvent->summary, $updatedEvent->summary);
        $this->assertEquals($currentTz, $updatedEvent->originator_tz, 'originator_tz must not be touchet if dtsart is not updatet!');
        
        $updatedEvent->dtstart->addHour(1);
        $updatedEvent->dtend->addHour(1);
        $secondUpdatedEvent = $this->_controller->update($updatedEvent);
        $this->assertEquals(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE), $secondUpdatedEvent->originator_tz, 'originator_tz must be adopted if dtsart is updatet!');
    
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, $currentTz);
    }
    
    public function testUpdateMultiple()
    {
        $persitentEvent = $this->testCreateEvent();
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($persitentEvent->getId()))
        ));
        
        $data = array(
            'summary' => 'multipleTest'
        );
        
        $this->_controller->updateMultiple($filter, $data);
        
        $updatedEvent = $this->_controller->get($persitentEvent->getId());
        $this->assertEquals('multipleTest', $updatedEvent->summary);
    }
    
    public function testAttendeeBasics()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_personasContacts['pwulf']->getId()
        ));
        
        $persistendEvent = $this->_controller->create($event);
        $this->assertEquals(2, count($persistendEvent->attendee));
        
        unset($persistendEvent->attendee[0]);
        $updatedEvent = $this->_controller->update($persistendEvent);
        $this->assertEquals(1, count($updatedEvent->attendee));
        
        $updatedEvent->attendee->getFirstRecord()->role = Calendar_Model_Attender::ROLE_OPTIONAL;
        $secondUpdatedEvent = $this->_controller->update($updatedEvent);
        $this->assertEquals(1, count($secondUpdatedEvent->attendee));
        $this->assertEquals(Calendar_Model_Attender::ROLE_OPTIONAL, $secondUpdatedEvent->attendee->getFirstRecord()->role);
    }
    
    public function testAttendeeFilter()
    {
        $event1 = $this->_getEvent();
        $event1->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => Tinebase_Core::getUser()->contact_id),
            array('user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        $persistentEvent1 = $this->_controller->create($event1);
        
        $event2 = $this->_getEvent();
        $event2->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => Tinebase_Core::getUser()->contact_id),
            array('user_id' => $this->_personasContacts['sclever']->getId()),
        ));
        $persistentEvent2 = $this->_controller->create($event2);
        
        $event3 = $this->_getEvent();
        $event3->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => Tinebase_Core::getUser()->contact_id),
            array('user_id' => $this->_personasContacts['sclever']->getId()),
        ));
        $persistentEvent3 = $this->_controller->create($event3);
        
        // test sclever
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $this->_personasContacts['sclever']->getId()
            )),
        ));
        $eventsFound = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertEquals(2, count($eventsFound), 'sclever attends to two events');
        
        // test pwulf
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $this->_personasContacts['pwulf']->getId()
            )),
        ));
        $eventsFound = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertEquals(1, count($eventsFound), 'pwulf attends to one events');
        
        // test sclever OR pwulf
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'attender'    , 'operator' => 'in',     'value' => array(
                array(
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'   => $this->_personasContacts['sclever']->getId()
                ),
                array (
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'   => $this->_personasContacts['pwulf']->getId()
                )
            )),
        ));
        $eventsFound = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertEquals(3, count($eventsFound), 'sclever OR pwulf attends to tree events');
    }
    
    public function testGetFreeBusyInfo()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        $persistentEvent = $this->_controller->create($event);
        
        $fbinfo = $this->_controller->getFreeBusyInfo($persistentEvent->dtstart, $persistentEvent->dtend, $persistentEvent->attendee);
        $this->assertGreaterThanOrEqual(2, count($fbinfo));
        
    }
    
    public function testCreateEventWithConfict()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        $persistentEvent = $this->_controller->create($event);
        
        $conflictEvent = $this->_getEvent();
        $conflictEvent->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        
        try {
            $exectionRaised = FALSE;
        	$this->_controller->create($conflictEvent, TRUE);
        } catch (Calendar_Exception_AttendeeBusy $busyException) {
            $fbData = $busyException->toArray();
            $this->assertGreaterThanOrEqual(2, count($fbData['freebusyinfo']));
            $exectionRaised = TRUE;
        }
        if (! $exectionRaised) {
            $this->fail('An expected exception has not been raised.');
        }
        $persitentConflictEvent = $this->_controller->create($conflictEvent, FALSE);
        
        return $persitentConflictEvent;
    }
    
    public function testCreateEventWithConfictFromGroupMember()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $persistentEvent = $this->_controller->create($event);
        
        $conflictEvent = $this->_getEvent();
        $conflictEvent->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        
        try {
            $this->_controller->create($conflictEvent, TRUE);
            $this->assertTrue(false, 'Failed to detect conflict from groupmember');
        } catch (Calendar_Exception_AttendeeBusy $busyException) {
            $fbData = $busyException->toArray();
            $this->assertGreaterThanOrEqual(2, count($fbData['freebusyinfo']));
            return;
        }
        
        $this->fail('An expected exception has not been raised.');
    }
    
    public function testCreateTransparentEventNoConflict()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $persistentEvent = $this->_controller->create($event);
        
        $nonConflictEvent = $this->_getEvent();
        $nonConflictEvent->transp = Calendar_Model_Event::TRANSP_TRANSP;
        $nonConflictEvent->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        
        $this->_controller->create($nonConflictEvent, TRUE);
    }
    
    public function testCreateNoConflictParallelTrasparentEvent()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $event->transp = Calendar_Model_Event::TRANSP_TRANSP;
        $persistentEvent = $this->_controller->create($event);
        
        $nonConflictEvent = $this->_getEvent();
        $nonConflictEvent->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        
        $this->_controller->create($nonConflictEvent, TRUE);
    }
    
    public function testUpdateWithConflictNoTimechange()
    {
        $persitentConflictEvent = $this->testCreateEventWithConfict();
        $persitentConflictEvent->summary = 'only time updates should recheck free/busy';
        
        $this->_controller->update($persitentConflictEvent, TRUE);
    }
    
    public function testUpdateWithConflictAttendeeChange()
    {
        $persitentConflictEvent = $this->testCreateEventWithConfict();
        $persitentConflictEvent->summary = 'attendee adds should recheck free/busy';
        
        $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $persitentConflictEvent->attendee->addRecord(new Calendar_Model_Attender(array(
            'user_id'   => $defaultUserGroup->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        )));        
        
        $this->setExpectedException('Calendar_Exception_AttendeeBusy');
        $this->_controller->update($persitentConflictEvent, TRUE);
    }
    
    public function testUpdateWithConflictWithTimechange()
    {
        $persitentConflictEvent = $this->testCreateEventWithConfict();
        $persitentConflictEvent->summary = 'time updates should recheck free/busy';
        $persitentConflictEvent->dtend->addHour(1);
        
        $this->setExpectedException('Calendar_Exception_AttendeeBusy');
        $this->_controller->update($persitentConflictEvent, TRUE);
    }
    
    public function testAttendeeAuthKeyPreserv()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        
        $persistendEvent = $this->_controller->create($event);
        $newAuthKey = Tinebase_Record_Abstract::generateUID();
        $persistendEvent->attendee->status_authkey = $newAuthKey;
        
        $updatedEvent = $this->_controller->update($persistendEvent);
        foreach ($updatedEvent->attendee as $attender) {
            $this->assertNotEquals($newAuthKey, $attender->status_authkey);
        }
    }
    
    public function testAttendeeStatusPreservViaSave()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $event->attendee[0]->user_id = Tinebase_User::getInstance()->getUserByLoginName('sclever')->contact_id;
        $event->attendee[0]->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        unset($event->attendee[1]);
        
        $persistendEvent = $this->_controller->create($event);
        $this->assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $persistendEvent->attendee[0]->status, 'creation of other attedee must not set status');
        
        $persistendEvent->attendee[0]->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        $persistendEvent->attendee[0]->status_authkey = NULL;
        $updatedEvent = $this->_controller->update($persistendEvent);
        $this->assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $updatedEvent->attendee[0]->status, 'updateing of other attedee must not set status');
    }
    
    public function testAttendeeSetStatus()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        unset($event->attendee[1]);
        
        $persistendEvent = $this->_controller->create($event);
        $attendee = $persistendEvent->attendee[0];
        
        $attendee->status = Calendar_Model_Attender::STATUS_DECLINED;
        $this->_controller->attenderStatusUpdate($persistendEvent, $attendee, $attendee->status_authkey);
        
        $loadedEvent = $this->_controller->get($persistendEvent->getId());
        $this->assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $loadedEvent->attendee[0]->status, 'status not set');
        
    }
    
    public function testAttendeeStatusFilter()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        unset($event->attendee[1]);
        
        $persitentEvent = $this->_controller->create($event);
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'uid',             'operator' => 'equals', 'value' => $persitentEvent->uid),
            array('field' => 'attender_status', 'operator' => 'not',    'value' => Calendar_Model_Attender::STATUS_DECLINED),
        ));
        
        $events = $this->_controller->search($filter);
        $this->assertEquals(1, count($events));
        
        $attender = $persitentEvent->attendee[0];
        $attender->status = Calendar_Model_Attender::STATUS_DECLINED;
        $updatedPersistentEvent = $this->_controller->update($persitentEvent);
        
        $events = $this->_controller->search($filter);
        $this->assertEquals(0, count($events));
        
    }
    
    public function testAttendeeDisplaycontainerContact()
    {
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
           'n_given'  => 'phpunit',
           'n_family' => 'cal attender'
        )));
         
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $contact->getId(),
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            ),
        ));
        $persitentEvent = $this->_controller->create($event);
        $attender = $persitentEvent->attendee[0];
        
        $this->assertTrue(empty($attender->displaycontainer_id), 'displaycontainer_id must not be set for contacts');
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
        
        $persitentEvent = $this->_controller->create($event);
        $attendee = $persitentEvent->attendee[0];
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $from = new Zend_Date('2009-03-26 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $until = new Zend_Date('2009-04-01 23:59:59', Tinebase_Record_Abstract::ISO8601LONG);
        $recurSet = Calendar_Model_Rrule::computeRecuranceSet($persitentEvent, $exceptions, $from, $until);
        
        $exception = $recurSet->getFirstRecord();
        $attendee = $exception->attendee[0];
        $attendee->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        
        $this->_controller->attenderStatusCreateRecurException($exception, $attendee, $attendee->status_authkey);
        
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
            array('field' => 'uid', 'operator' => 'equals', 'value' => $persitentEvent->uid)
        )));
        $this->assertEquals(2, count($events));
    }
    
    public function testAttendeeGroupMembers()
    {
        $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $defaultAdminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_id'   => $defaultUserGroup->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        ));
        
        $persitentEvent = $this->_controller->create($event);
        $defaultUserGroupMembers = Tinebase_Group::getInstance()->getGroupMembers($defaultUserGroup->getId());
        // user as attender + group + all members - supressed user 
        $this->assertEquals(1 + 1 + count($defaultUserGroupMembers) -1, count($persitentEvent->attendee));
        
        $groupAttender = $persitentEvent->attendee->find('user_type', Calendar_Model_Attender::USERTYPE_GROUP);
        $persitentEvent->attendee->removeRecord($groupAttender);
        
        $updatedPersistentEvent = $this->_controller->update($persitentEvent);
        $this->assertEquals(1, count($updatedPersistentEvent->attendee));
    }
    
    public function testAttendeeGroupMembersChange()
    {
        $defaultAdminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        
        // create event and invite admin group
        $event = $this->_getEvent();
        
        // only events in future will be changed!
        $event->dtstart = Zend_Date::now()->addHour(1);
        $event->dtend = Zend_Date::now()->addHour(2);
        
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_id'   => $defaultAdminGroup->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        ));
        $persitentEvent = $this->_controller->create($event);
        
        // assert test condition
        $pwulf = $persitentEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_personasContacts['pwulf']->getId());
        $this->assertEquals(0, count($pwulf), 'invalid test condition, pwulf should not be member or admin group');
        
        Admin_Controller_Group::getInstance()->addGroupMember($defaultAdminGroup->getId(), $this->_personasContacts['pwulf']->account_id);
        $loadedEvent = $this->_controller->get($persitentEvent->getId());
        // assert pwulf is in
        $pwulf = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_personasContacts['pwulf']->getId());
        $this->assertEquals(1, count($pwulf), 'pwulf is not attender of event, but should be');
        
        
        Admin_Controller_Group::getInstance()->removeGroupMember($defaultAdminGroup->getId(), $this->_personasContacts['pwulf']->account_id);
        $loadedEvent = $this->_controller->get($persitentEvent->getId());
        // assert pwulf is missing
        $pwulf = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_personasContacts['pwulf']->getId());
        $this->assertEquals(0, count($pwulf), 'pwulf is attender of event, but not should be');
    }
    
    public function testUpdateRecuingDtstart()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $event->exdate = array(new Zend_Date('2009-04-07 13:00:00', Tinebase_Record_Abstract::ISO8601LONG));
        $persitentEvent = $this->_controller->create($event);
        
        $exception = clone $persitentEvent;
        $exception->dtstart->addDay(2);
        $exception->dtend->addDay(2);
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->create($exception);
        
        $persitentEvent->dtstart->addHour(5);
        $persitentEvent->dtend->addHour(5);
        
        $updatedEvent = $this->_controller->update($persitentEvent);
        $updatedException = $this->_controller->get($persitentException->getId());
        $this->assertEquals('2009-04-07 18:00:00', $updatedEvent->exdate[0]->get(Tinebase_Record_Abstract::ISO8601LONG), 'failed to update exdate');
        $this->assertEquals('2009-04-08 18:00:00', substr($updatedException->recurid, -19), 'failed to update persistent exception');
        $this->assertEquals('2009-04-30 18:30:00', Calendar_Model_Rrule::getRruleFromString($updatedEvent->rrule)->until->get(Tinebase_Record_Abstract::ISO8601LONG), 'failed to update until in rrule');
        $this->assertEquals('2009-04-30 18:30:00', $updatedEvent->rrule_until->get(Tinebase_Record_Abstract::ISO8601LONG), 'failed to update rrule_until');
        
        sleep(1); // wait for modlog
        $updatedEvent->dtstart->subHour(5);
        $updatedEvent->dtend->subHour(5);
        $secondUpdatedEvent = $this->_controller->update($updatedEvent);
        $secondUpdatedException = $this->_controller->get($persitentException->getId());
        $this->assertEquals('2009-04-07 13:00:00', $secondUpdatedEvent->exdate[0]->get(Tinebase_Record_Abstract::ISO8601LONG), 'failed to update exdate (sub)');
        $this->assertEquals('2009-04-08 13:00:00', substr($secondUpdatedException->recurid, -19), 'failed to update persistent exception (sub)');
    }
    
    public function testUpdateRecurDtstartOverDst()
    {
        // note: 2009-03-29 Europe/Berlin switched to DST
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2009-03-25 18:00:00',
            'dtend'         => '2009-03-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-02 17:30:00',
            'exdate'        => '2009-03-27 18:00:00,2009-03-31 17:00:00',
            'container_id'  => $this->_testCalendar->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $persitentEvent = $this->_controller->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $from = new Zend_Date('2009-03-26 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $until = new Zend_Date('2009-04-03 23:59:59', Tinebase_Record_Abstract::ISO8601LONG);
        $recurSet = Calendar_Model_Rrule::computeRecuranceSet($persitentEvent, $exceptions, $from, $until); // 9 days
        
        // skip 27(exception), 31(exception), 03(until)
        $this->assertEquals(6, count($recurSet));
        
        $exceptionBeforeDstBoundary = clone $recurSet[1]; // 28. 
        $persistentExceptionBeforeDstBoundary = $this->_controller->createRecurException($exceptionBeforeDstBoundary);
        
        $exceptionAfterDstBoundary = clone $recurSet[5]; // 02.
        $persistentExceptionAfterDstBoundary = $this->_controller->createRecurException($exceptionAfterDstBoundary);
        
        $persitentEvent->dtstart->addDay(5); //30.
        $persitentEvent->dtend->addDay(5);
        $from->addDay(5); //31
        $until->addDay(5); //08
        
        // NOTE: with this, also until, and exceptions get moved, but not the persistent exceptions
        $updatedPersistenEvent = $this->_controller->update($persitentEvent);
        
        $persistentEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
            array('field' => 'uid', 'operator' => 'equals', 'value' => $persitentEvent->uid)
        )));
        
        //print_r($persistentEvents->toArray());
        // we don't 'see' the persistent exception from 28/
        $this->assertEquals(2, count($persistentEvents));
                
        $exceptions = $persistentEvents->filter('recurid', "/^{$persitentEvent->uid}-.*/", TRUE);
        $recurSet = Calendar_Model_Rrule::computeRecuranceSet($updatedPersistenEvent, $exceptions, $from, $until);
        
        // skip 31(exception), and 8 (until)
        $this->assertEquals(7, count($recurSet));
    }
    
    public function testUpdateImplicitDeleteRcuringExceptions()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $event->exdate = array(new Zend_Date('2009-04-07 13:00:00', Tinebase_Record_Abstract::ISO8601LONG));
        $persitentEvent = $this->_controller->create($event);
        
        $exception = clone $persitentEvent;
        $exception->dtstart->addDay(2);
        $exception->dtend->addDay(2);
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->create($exception);
        
        unset($persitentEvent->rrule);
        $updatedEvent = $this->_controller->update($persitentEvent);
        $this->assertNull($updatedEvent->exdate);
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_controller->get($persitentException->getId());
    }
    
    public function testDeleteEvent()
    {
        $event = $this->_getEvent();
        $persitentEvent = $this->_controller->create($event);
        
        $this->_controller->delete($persitentEvent->getId());
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_controller->get($persitentEvent->getId());
    }
    
    /**
     * @todo use exception api once we have it!
     *
     */
    public function testDeleteRecurExceptions()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $event->exdate = array(new Zend_Date('2009-04-07 13:00:00', Tinebase_Record_Abstract::ISO8601LONG));
        $persitentEvent = $this->_controller->create($event);
        
        $exception = clone $persitentEvent;
        $exception->dtstart->addDay(2);
        $exception->dtend->addDay(2);
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->create($exception);
        
        $this->_controller->delete($persitentEvent->getId());
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_controller->get($persitentException->getId());
    }
    
    public function testCreateRecurException()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $persitentEvent = $this->_controller->create($event);
        
        $exception = clone $persitentEvent;
        $exception->dtstart->addDay(3);
        $exception->dtend->addDay(3);
        $exception->summary = 'Abendbrot';
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->createRecurException($exception);
        
        $persitentEvent = $this->_controller->get($persitentEvent->getId());
        $this->assertNull($persitentEvent->exdate);
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $persitentEvent->uid),
        )));
        $this->assertEquals(2, count($events));
    }
    
    public function testDeleteNonPersistentRecurException()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $persitentEvent = $this->_controller->create($event);
        
        // create an exception (a fallout)
        $exception = clone $persitentEvent;
        $exception->dtstart->addDay(3);
        $exception->dtend->addDay(3);
        $exception->summary = 'Abendbrot';
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentEventWithExdate = $this->_controller->createRecurException($exception, true);
        
        $persitentEvent = $this->_controller->get($persitentEvent->getId());
        $this->assertType('Zend_Date', $persitentEventWithExdate->exdate[0]);
        $this->assertEquals($persitentEventWithExdate->exdate, $persitentEvent->exdate);
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $persitentEvent->uid),
        )));
        $this->assertEquals(1, count($events));
    }
    
    public function testDeletePersistentRecurException()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $persitentEvent = $this->_controller->create($event);
        
        $exception = clone $persitentEvent;
        $exception->dtstart->addDay(3);
        $exception->dtend->addDay(3);
        $exception->summary = 'Abendbrot';
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->createRecurException($exception);
        
        $this->_controller->delete($persitentException->getId());
        
        $persitentEvent = $this->_controller->get($persitentEvent->getId());
        
        $this->assertType('Zend_Date', $persitentEvent->exdate[0]);
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $persitentEvent->uid),
        )));
        $this->assertEquals(1, count($events));
    }
    
    /**
     * NOTE: virtual exdates are persistent exceptions -> non persistent exdates 
     *       which might occour due to scopeing or attendee status filtering
     */
    public function testGetVirtualExdates()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $persitentEvent = $this->_controller->create($event);
        
        // create 'usual' exception
        $exception = clone $persitentEvent;
        $exception->dtstart->addDay(2);
        $exception->dtend->addDay(2);
        $exception->summary = 'Abendbrot';
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->createRecurException($exception);
        
        // create virtual exception for test users calendar
        $exception = clone $persitentEvent;
        $exception->dtstart->addDay(3);
        $exception->dtend->addDay(3);
        $exception->summary = 'Mitternachtssnack';
        // decline test user on persistent exception -> virtual exdate for test users calendar
        $exception->attendee->find('user_id', Tinebase_Core::getUser()->contact_id)->status = Calendar_Model_Attender::STATUS_DECLINED;
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->createRecurException($exception);
        
        // search by attendee
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',             'operator' => 'equals', 'value' => $persitentEvent->uid),
            array('field' => 'attender',        'operator' => 'equals', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => Tinebase_Core::getUser()->contact_id
            )),
            array('field' => 'attender_status', 'operator' => 'not',    'value' => Calendar_Model_Attender::STATUS_DECLINED),
        )));
        
        // assert 'usual' exception but no virtual exception
        $this->assertEquals(2, count($events));
        $this->assertTrue($events->find('summary', 'Abendbrot') instanceof Calendar_Model_Event);
        
        $virtualExdates = $this->_controller->getRecurVirtualExdates($events);
        $this->assertEquals(1, count($virtualExdates));
        $this->assertTrue($virtualExdates->find('summary', 'Mitternachtssnack') instanceof Calendar_Model_Event);
    }
    
    public function testSetAlarm()
    {
        $event = $this->_getEvent();
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $persitentEvent = $this->_controller->create($event);
        $alarmTime = clone $persitentEvent->dtstart;
        $alarmTime->subMinute(30);
        $this->assertTrue($alarmTime->equals($persitentEvent->alarms->getFirstRecord()->alarm_time), 'initial alarm is not at expected time');
        
        
        $persitentEvent->dtstart->addHour(5);
        $persitentEvent->dtend->addHour(5);
        $updatedEvent = $this->_controller->update($persitentEvent);
        $alarmTime = clone $updatedEvent->dtstart;
        $alarmTime->subMinute(30);
        $this->assertTrue($alarmTime->equals($updatedEvent->alarms->getFirstRecord()->alarm_time), 'alarm of updated event is not adjusted');
    }
    
    public function testSetAlarmOfRecurSeries()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;INTERVAL=1';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $persitentEvent = $this->_controller->create($event);
        
        // assert alarm time is just before next occurence
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($persitentEvent, $exceptions, Zend_Date::now());
        
        $alarmTime = clone $nextOccurance->dtstart;
        $alarmTime->subMinute(30);
        $this->assertTrue($alarmTime->equals($persitentEvent->alarms->getFirstRecord()->alarm_time), 'initial alarm is not at expected time');
        
        
        // move whole series
        $persitentEvent->dtstart->addHour(5);
        $persitentEvent->dtend->addHour(5);
        $updatedEvent = $this->_controller->update($persitentEvent);

        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($updatedEvent, $exceptions, Zend_Date::now());
        
        $alarmTime = clone $nextOccurance->dtstart;
        $alarmTime->subMinute(30);
        $this->assertTrue($alarmTime->equals($updatedEvent->alarms->getFirstRecord()->alarm_time), 'updated alarm is not at expected time');
    }
    
    public function testSetAlarmOfRecurSeriesException()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;INTERVAL=1';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $persitentEvent = $this->_controller->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $exception = Calendar_Model_Rrule::computeNextOccurrence($persitentEvent, $exceptions, Zend_Date::now());
        $exception->dtstart->subHour(6);
        $exception->dtend->subHour(6);
        $persistentException = $this->_controller->createRecurException($exception);
        
        $baseEvent = $this->_controller->getRecurBaseEvent($persistentException);
        $this->_controller->getAlarms($baseEvent);
        
        
        $exceptions = $this->_controller->getRecurExceptions($persistentException);
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($baseEvent, $exceptions, Zend_Date::now());
        
        $alarmTime = clone $nextOccurance->dtstart;
        $alarmTime->subMinute(30);
        $this->assertTrue($alarmTime->equals($baseEvent->alarms->getFirstRecord()->alarm_time), 'next alarm got not adjusted');
        
        $alarmTime = clone $persistentException->dtstart;
        $alarmTime->subMinute(30);
        $this->assertTrue($alarmTime->equals($persistentException->alarms->getFirstRecord()->alarm_time), 'alarmtime of persistent exception is not correnct/set');
    }
    
    /**
     * returns a simple event
     *
     * @return Calendar_Model_Event
     */
    protected function _getEvent()
    {
        return new Calendar_Model_Event(array(
            'summary'     => 'Mittagspause',
            'dtstart'     => '2009-04-06 13:00:00',
            'dtend'       => '2009-04-06 13:30:00',
            'description' => 'Wieslaw Brudzinski: Das Gesetz garantiert zwar die Mittagspause, aber nicht das Mittagessen...',
        
            'container_id' => $this->_testCalendar->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true,
        ));
    }
    
    protected function _getAttendee()
    {
        return new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'   => Tinebase_Core::getUser()->contact_id,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            ),
            array(
                'user_id'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
                'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            )
        ));
    }
    
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_Controller_EventTests::main') {
    Calendar_Controller_EventTests::main();
}
