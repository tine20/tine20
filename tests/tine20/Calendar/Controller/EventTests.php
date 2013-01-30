<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
        $persistentEvent = $this->_controller->create($event);
        
        $this->assertEquals($event->description, $persistentEvent->description);
        $this->assertTrue($event->dtstart->equals($persistentEvent->dtstart));
        $this->assertEquals(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE), $persistentEvent->originator_tz);
        
        return $persistentEvent;
    }
    
    public function testGetEvent()
    {
        $persistentEvent = $this->testCreateEvent();
        $this->assertTrue((bool) $persistentEvent->{Tinebase_Model_Grants::GRANT_READ});
        $this->assertTrue((bool) $persistentEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertTrue((bool) $persistentEvent->{Tinebase_Model_Grants::GRANT_DELETE});
        
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $this->assertTrue((bool) $loadedEvent->{Tinebase_Model_Grants::GRANT_READ});
        $this->assertTrue((bool) $loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertTrue((bool) $loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    public function testUpdateEvent()
    {
        $persistentEvent = $this->testCreateEvent();
        
        $currentTz = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, 'farfaraway');
        
        $persistentEvent->summary = 'Lunchtime';
        $updatedEvent = $this->_controller->update($persistentEvent);
        $this->assertEquals($persistentEvent->summary, $updatedEvent->summary);
        $this->assertEquals($currentTz, $updatedEvent->originator_tz, 'originator_tz must not be touchet if dtsart is not updatet!');
        
        $updatedEvent->dtstart->addHour(1);
        $updatedEvent->dtend->addHour(1);
        $secondUpdatedEvent = $this->_controller->update($updatedEvent);
        $this->assertEquals(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE), $secondUpdatedEvent->originator_tz, 'originator_tz must be adopted if dtsart is updatet!');
    
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, $currentTz);
    }
    
    public function testUpdateAttendeeStatus()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_personasContacts['pwulf']->getId(),
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        foreach ($persistentEvent->attendee as $attender) {
            $attender->status = Calendar_Model_Attender::STATUS_DECLINED;
            $this->_controller->attenderStatusUpdate($persistentEvent, $attender, $attender->status_authkey);
        }
        
        
        $persistentEvent->last_modified_time = $this->_controller->get($persistentEvent->getId())->last_modified_time;
        
        // update time
        $persistentEvent->dtstart->addHour(2);
        $persistentEvent->dtend->addHour(2);
        // NOTE: in normal operations the status authkey is removed by resolveAttendee
        //       we simulate this here by removeing the keys per hand. (also note that current user does not need an authkey)
        $persistentEvent->attendee->status_authkey = null;
        $updatedEvent = $this->_controller->update($persistentEvent);

        $currentUser = $updatedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_USER)
            ->filter('user_id', Tinebase_Core::getUser()->contact_id)
            ->getFirstRecord();
            
        $pwulf = $updatedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_USER)
            ->filter('user_id', $this->_personasContacts['pwulf']->getId())
            ->getFirstRecord();

        $this->assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $currentUser->status, 'current users status must not be touched');
        $this->assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $pwulf->status, 'pwulfs status must be reset');
    }
    
    public function testUpdateMultiple()
    {
        $persistentEvent = $this->testCreateEvent();
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($persistentEvent->getId()))
        ));
        
        $data = array(
            'summary' => 'multipleTest'
        );
        
        $this->_controller->updateMultiple($filter, $data);
        
        $updatedEvent = $this->_controller->get($persistentEvent->getId());
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
        $updatedEvent->attendee->getFirstRecord()->transp = Calendar_Model_Event::TRANSP_TRANSP;
        
        $secondUpdatedEvent = $this->_controller->update($updatedEvent);
        $this->assertEquals(1, count($secondUpdatedEvent->attendee));
        $this->assertEquals(Calendar_Model_Attender::ROLE_OPTIONAL, $secondUpdatedEvent->attendee->getFirstRecord()->role);
        $this->assertEquals(Calendar_Model_Event::TRANSP_TRANSP, $secondUpdatedEvent->attendee->getFirstRecord()->transp);
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
    
    public function testAttendeeGroupFilter()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => Tinebase_Core::getUser()->contact_id),
            array('user_id' => $this->_personasContacts['sclever']->getId())
        ));
        $persistentEvent = $this->_controller->create($event);
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'attender'    , 'operator' => 'in',     'value' => array(
                array(
                    'user_type' => Calendar_Model_AttenderFilter::USERTYPE_MEMBEROF,
                    'user_id'   => $this->_personas['sclever']->accountPrimaryGroup
                )
            )),
        ));
        $eventsFound = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertEquals(1, count($eventsFound), 'sclever is groupmember');
    }
        
    public function testGetFreeBusyInfo()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        $persistentEvent = $this->_controller->create($event);
        
        $fbinfo = $this->_controller->getFreeBusyInfo(array(array('from' => $persistentEvent->dtstart, 'until' => $persistentEvent->dtend)), $persistentEvent->attendee);
       
        $this->assertGreaterThanOrEqual(2, count($fbinfo));
        
        return $persistentEvent;
    }
    
    public function testSearchFreeTime() {
        $persistentEvent = $this->testGetFreeBusyInfo();
        
        $this->_controller->searchFreeTime($persistentEvent->dtstart->setHour(6), $persistentEvent->dtend->setHour(22), $persistentEvent->attendee);
    }
    
    /**
     * events from deleted calendars should not be shown
     */
    public function testSearchEventFromDeletedCalendar() {
        $testCal = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'PHPUnit test calendar',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => $this->_backend->getType(),
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()
        ), true));
        
        $this->_testCalendars->addRecord($testCal);
        
        // create event in testcal
        $event = $this->_getEvent();
        $event->container_id = $testCal->getId();
        $event->attendee = $this->_getAttendee();
        $persistentEvent = $this->_controller->create($event);

        // delete testcal
        Tinebase_Container::getInstance()->deleteContainer($testCal, TRUE);
        
        // search by attendee
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'attender', 'operator' => 'equals', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $this->_testUserContact->getId()
            ))
        )), NULL, FALSE, FALSE);
        
        $this->assertFalse(in_array($persistentEvent->getId(), $events->getId()), 'event in deleted (display) container shuld not be found');
    }
    
    public function testCreateEventWithConfict()
    {
        $this->_testNeedsTransaction();
        
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
    
    public function testCreateNoConflictParallelAtendeeTrasparentEvent()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        unset ($event->attendee[1]); // no group here
        $event->attendee->transp = Calendar_Model_Event::TRANSP_TRANSP;
        $persistentEvent = $this->_controller->create($event);
        
        $nonConflictEvent = $this->_getEvent();
        $nonConflictEvent->attendee = $this->_getAttendee();
        
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
        
        $persistentEvent = $this->_controller->create($event);
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'uid',             'operator' => 'equals', 'value' => $persistentEvent->uid),
            array('field' => 'attender_status', 'operator' => 'not',    'value' => Calendar_Model_Attender::STATUS_DECLINED),
        ));
        
        $events = $this->_controller->search($filter);
        $this->assertEquals(1, count($events), 'event should be found, but is not');
        
        $attender = $persistentEvent->attendee[0];
        $attender->status = Calendar_Model_Attender::STATUS_DECLINED;
        $updatedPersistentEvent = $this->_controller->update($persistentEvent);
        
        $events = $this->_controller->search($filter);
        $this->assertEquals(0, count($events), 'event should _not_ be found, but is');
        
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
        $persistentEvent = $this->_controller->create($event);
        $attender = $persistentEvent->attendee[0];
        
        $this->assertTrue(empty($attender->displaycontainer_id), 'displaycontainer_id must not be set for contacts');
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
        
        $persistentEvent = $this->_controller->create($event);
        $defaultUserGroupMembers = Tinebase_Group::getInstance()->getGroupMembers($defaultUserGroup->getId());
        // user as attender + group + all members - supressed user 
        $this->assertEquals(1 + 1 + count($defaultUserGroupMembers) -1, count($persistentEvent->attendee));
        
        $groupAttender = $persistentEvent->attendee->find('user_type', Calendar_Model_Attender::USERTYPE_GROUP);
        $persistentEvent->attendee->removeRecord($groupAttender);
        
        $updatedPersistentEvent = $this->_controller->update($persistentEvent);
        $this->assertEquals(1, count($updatedPersistentEvent->attendee));
    }
    
    public function testAttendeeGroupMembersChange()
    {
        $defaultAdminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        
        // create event and invite admin group
        $event = $this->_getEvent();
        
        // only events in future will be changed!
        $event->dtstart = Tinebase_DateTime::now()->addHour(1);
        $event->dtend = Tinebase_DateTime::now()->addHour(2);
        
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_id'   => $defaultAdminGroup->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        ));
        $persistentEvent = $this->_controller->create($event);
        
        // assert test condition
        $pwulf = $persistentEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_personasContacts['pwulf']->getId());
        $this->assertEquals(0, count($pwulf), 'invalid test condition, pwulf should not be member or admin group');
        
        Admin_Controller_Group::getInstance()->addGroupMember($defaultAdminGroup->getId(), $this->_personasContacts['pwulf']->account_id);
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        // assert pwulf is in
        $pwulf = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_personasContacts['pwulf']->getId());
        $this->assertEquals(1, count($pwulf), 'pwulf is not attender of event, but should be');
        
        
        Admin_Controller_Group::getInstance()->removeGroupMember($defaultAdminGroup->getId(), $this->_personasContacts['pwulf']->account_id);
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        // assert pwulf is missing
        $pwulf = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_personasContacts['pwulf']->getId());
        $this->assertEquals(0, count($pwulf), 'pwulf is attender of event, but not should be');
        
        // Test the same with update
        $group = Admin_Controller_Group::getInstance()->get($defaultAdminGroup->getId());
        $group->members = array_merge(Admin_Controller_Group::getInstance()->getGroupMembers($defaultAdminGroup->getId()), array(array_value('pwulf', Zend_Registry::get('personas'))->getId()));
        Admin_Controller_Group::getInstance()->update($group);
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        // assert pwulf is in
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $pwulf = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_personasContacts['pwulf']->getId());
        $this->assertEquals(1, count($pwulf), 'pwulf is not attender of event, but should be (via update)');
        
        $group->members = array_diff(Admin_Controller_Group::getInstance()->getGroupMembers($defaultAdminGroup->getId()), array(array_value('pwulf', Zend_Registry::get('personas'))->getId()));
        Admin_Controller_Group::getInstance()->update($group);
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        // assert pwulf is missing
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $pwulf = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_personasContacts['pwulf']->getId());
        $this->assertEquals(0, count($pwulf), 'pwulf is attender of event, but not should be');
    }
    
    public function testAttendeeGroupMembersAddUser()
    {
        try {
            // clenup if exists
            $cleanupUser = Tinebase_User::getInstance()->getFullUserByLoginName('testAttendeeGroupMembersAddUser');
            Tinebase_User::getInstance()->deleteUser($cleanupUser);
        } catch (Exception $e) {
            // do nothing
        }
        
        
        $defaultGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        
        // create event and invite admin group
        $event = $this->_getEvent();
        
        // only events in future will be changed!
        $event->dtstart = Tinebase_DateTime::now()->addHour(1);
        $event->dtend = Tinebase_DateTime::now()->addHour(2);
        
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_id'   => $defaultGroup->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        ));
        $persistentEvent = $this->_controller->create($event);
        
        // create a new user
        $newUser = Admin_Controller_User::getInstance()->create(new Tinebase_Model_FullUser(array(
//            'accountId'             => 'dflkjgldfgdfgd',
            'accountLoginName'      => 'testAttendeeGroupMembersAddUser',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => $defaultGroup->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        )), Zend_Registry::get('testConfig')->password, Zend_Registry::get('testConfig')->password);
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        // check if this user was added to event
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $user = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $newUser->contact_id);
        $this->assertEquals(1, count($user), 'added user is not attender of event, but should be. user: ' . print_r($newUser->toArray(), TRUE));
        
        // cleanup user
        Admin_Controller_User::getInstance()->delete($newUser->getId());
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        // check if user was removed from event
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $user = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $newUser->contact_id);
        $this->assertEquals(0, count($user), 'added user is attender of event, but should be (after deleting user)');
    }
    
    /**
     * testAttendeeGroupMembersRecurringAddUser
     * 
     * FIXME 0007352: fix Calendar_Controller_EventTests::testAttendeeGroupMembersRecurringAddUser
     */
    public function testAttendeeGroupMembersRecurringAddUser()
    {
        $this->markTestIncomplete('test fails sometimes / needs fixing');
        
        try {
            // cleanup if exists
            $cleanupUser = Tinebase_User::getInstance()->getFullUserByLoginName('testAttendeeGroupMembersAddUser');
            Tinebase_User::getInstance()->deleteUser($cleanupUser);
        } catch (Exception $e) {
            // do nothing
        }
        
        $defaultGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        
        // create event and invite admin group
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';
        
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_id'   => $defaultGroup->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        ));
        $persistentEvent = $this->_controller->create($event);
        
        // create a new user
        $newUser = Admin_Controller_User::getInstance()->create(new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'testAttendeeGroupMembersAddUser',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => $defaultGroup->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        )), Zend_Registry::get('testConfig')->password, Zend_Registry::get('testConfig')->password);
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        $events = $this->_backend->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => $this->_testCalendars->getId()),
        )), new Tinebase_Model_Pagination(array()));
        
        $oldSeries = $events->filter('rrule_until', '/.+/', TRUE)->getFirstRecord();
        $newSeries = $events->filter('rrule_until', '/^$/', TRUE)->getFirstRecord();
        
        $this->assertEquals(2, $events->count(), 'recur event must be splitted '. print_r($events->toArray(), TRUE));
        // check if this user was added to event
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $user = $oldSeries->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $newUser->contact_id);
        $this->assertEquals(0, count($user), 'added user is attender of old event, but should not be');
        $user = $newSeries->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $newUser->contact_id);
        $this->assertEquals(1, count($user), 'added user is not attender of new event, but should be');
        
        // cleanup user
        Admin_Controller_User::getInstance()->delete($newUser->getId());
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        $events = $this->_backend->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => $this->_testCalendars->getId()),
        )), new Tinebase_Model_Pagination(array()));
        
        $newSeries = $events->filter('rrule_until', '/^$/', TRUE)->getFirstRecord();
        
        // check if this user was deleted from event
        $user = $newSeries->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $newUser->contact_id);
        $this->assertEquals(0, count($user), 'deleted user is attender of new event, but should not be');
    }
    
    public function testRruleUntil()
    {
        $event = $this->_getEvent();
        
        $event->rrule_until = Tinebase_DateTime::now();
        $persistentEvent = $this->_controller->create($event);
        $this->assertNull($persistentEvent->rrule_until, 'rrul_until is not unset');
        
        $persistentEvent->rrule = 'FREQ=YEARLY;INTERVAL=1;BYMONTH=2;UNTIL=2010-04-01 08:00:00';
        $updatedEvent = $this->_controller->update($persistentEvent);
        $this->assertEquals('2010-04-01 08:00:00', $updatedEvent->rrule_until->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testUpdateRecuingDtstart()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $event->exdate = array(new Tinebase_DateTime('2009-04-07 13:00:00'));
        $persistentEvent = $this->_controller->create($event);
        
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(2);
        $exception->dtend->addDay(2);
        
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->create($exception);
        
        $persistentEvent->dtstart->addHour(5);
        $persistentEvent->dtend->addHour(5);
        
        $updatedEvent = $this->_controller->update($persistentEvent);
        
        $updatedException = $this->_controller->get($persitentException->getId());
        
        $this->assertEquals(1, count($updatedEvent->exdate), 'failed to reset exdate');
        $this->assertEquals('2009-04-08 18:00:00', $updatedEvent->exdate[0]->get(Tinebase_Record_Abstract::ISO8601LONG), 'failed to update exdate');
        $this->assertEquals('2009-04-08 18:00:00', substr($updatedException->recurid, -19), 'failed to update persistent exception');
        $this->assertEquals('2009-04-30 13:30:00', Calendar_Model_Rrule::getRruleFromString($updatedEvent->rrule)->until->get(Tinebase_Record_Abstract::ISO8601LONG), 'until in rrule must not be changed');
        $this->assertEquals('2009-04-30 13:30:00', $updatedEvent->rrule_until->get(Tinebase_Record_Abstract::ISO8601LONG), 'rrule_until must not be changed');
        
        $updatedEvent->dtstart->subHour(5);
        $updatedEvent->dtend->subHour(5);
        $secondUpdatedEvent = $this->_controller->update($updatedEvent);
        $secondUpdatedException = $this->_controller->get($persitentException->getId());
        $this->assertEquals('2009-04-08 13:00:00', $secondUpdatedEvent->exdate[0]->get(Tinebase_Record_Abstract::ISO8601LONG), 'failed to update exdate (sub)');
        $this->assertEquals('2009-04-08 13:00:00', substr($secondUpdatedException->recurid, -19), 'failed to update persistent exception (sub)');
    }
    
    /**
     * testUpdateRecurDtstartOverDst
     */
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
        
        $persistentEvent = $this->_controller->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $from = new Tinebase_DateTime('2009-03-26 00:00:00');
        $until = new Tinebase_DateTime('2009-04-03 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until); // 9 days
        
        // skip 27(exception), 31(exception), 03(until)
        $this->assertEquals(6, count($recurSet));
        
        $exceptionBeforeDstBoundary = clone $recurSet[1]; // 28. 
        $persistentExceptionBeforeDstBoundary = $this->_controller->createRecurException($exceptionBeforeDstBoundary);
        
        $updatedBaseEvent = $this->_controller->getRecurBaseEvent($recurSet[5]);
        $recurSet[5]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $exceptionAfterDstBoundary = clone $recurSet[5]; // 02.
        $persistentExceptionAfterDstBoundary = $this->_controller->createRecurException($exceptionAfterDstBoundary);
        
        $persistentEvent->dtstart->addDay(5); //30.
        $persistentEvent->dtend->addDay(5);
        $from->addDay(5); //31
        $until->addDay(5); //08
        
        $currentPersistentEvent = $this->_controller->get($persistentEvent);
        $persistentEvent->seq = 2; // satisfy modlog
        $updatedPersistenEvent = $this->_controller->update($persistentEvent);
        
        $persistentEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
            array('field' => 'uid', 'operator' => 'equals', 'value' => $persistentEvent->uid)
        )));
        
        // we don't 'see' the persistent exception from 28/
        $this->assertEquals(2, count($persistentEvents));
                
        $exceptions = $persistentEvents->filter('recurid', "/^{$persistentEvent->uid}-.*/", TRUE);
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($updatedPersistenEvent, $exceptions, $from, $until);
        
        // until is not adopted
        $this->assertEquals(2, count($recurSet));
    }
    
    public function testDeleteImplicitDeleteRcuringExceptions()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $event->exdate = array(new Tinebase_DateTime('2009-04-07 13:00:00'));
        $persistentEvent = $this->_controller->create($event);
        
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(2);
        $exception->dtend->addDay(2);
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->create($exception);
        
        unset($persistentEvent->rrule);
        $updatedEvent = $this->_controller->delete($persistentEvent);
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_controller->get($persitentException->getId());
    }
    
    /**
     * test delete event
     * - check here if content sequence of container has been increased
     */
    public function testDeleteEvent()
    {
        $event = $this->_getEvent();
        $persistentEvent = $this->_controller->create($event);
        
        $this->_controller->delete($persistentEvent->getId());
        
        $contentSeq = Tinebase_Container::getInstance()->getContentSequence($this->_testCalendar);
        $this->assertEquals(2, $contentSeq, 'container content seq should be increased 2 times!');
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_controller->get($persistentEvent->getId());
    }
    
    /**
     * @todo use exception api once we have it!
     *
     */
    public function testDeleteRecurExceptions()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $event->exdate = array(new Tinebase_DateTime('2009-04-07 13:00:00'));
        $persistentEvent = $this->_controller->create($event);
        
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(2);
        $exception->dtend->addDay(2);
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->create($exception);
        
        $this->_controller->delete($persistentEvent->getId());
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_controller->get($persitentException->getId());
    }
    
    public function testCreateRecurException()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $persistentEvent = $this->_controller->create($event);
        
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(3);
        $exception->dtend->addDay(3);
        $exception->summary = 'Abendbrot';
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->createRecurException($exception);
        
        $persistentEvent = $this->_controller->get($persistentEvent->getId());
        $this->assertEquals(1, count($persistentEvent->exdate));
        
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $persistentEvent->uid),
        )));
        $this->assertEquals(2, count($events));
        
        return $persitentException;
    }
    
    public function testDeleteNonPersistentRecurException()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $persistentEvent = $this->_controller->create($event);
        
        // create an exception (a fallout)
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(3);
        $exception->dtend->addDay(3);
        $exception->summary = 'Abendbrot';
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persistentEventWithExdate = $this->_controller->createRecurException($exception, true);
        
        $persistentEvent = $this->_controller->get($persistentEvent->getId());
        $this->assertEquals('Tinebase_DateTime', get_class($persistentEventWithExdate->exdate[0]));
        $this->assertEquals($persistentEventWithExdate->exdate[0]->format('c'), $persistentEvent->exdate[0]->format('c'));
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $persistentEvent->uid),
        )));
        $this->assertEquals(1, count($events));
    }
    
    public function testDeletePersistentRecurException()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $persistentEvent = $this->_controller->create($event);
        
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(3);
        $exception->dtend->addDay(3);
        $exception->summary = 'Abendbrot';
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persitentException = $this->_controller->createRecurException($exception);
        
        $this->_controller->delete($persitentException->getId());
        
        $persistentEvent = $this->_controller->get($persistentEvent->getId());
        
        $this->assertEquals('Tinebase_DateTime', get_class($persistentEvent->exdate[0]));
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $persistentEvent->uid),
        )));
        $this->assertEquals(1, count($events));
    }
    
    public function testSetAlarm()
    {
        $event = $this->_getEvent();
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $persistentEvent = $this->_controller->create($event);
        $alarmTime = clone $persistentEvent->dtstart;
        $alarmTime->subMinute(30);
        $this->assertTrue($alarmTime->equals($persistentEvent->alarms->getFirstRecord()->alarm_time), 'initial alarm is not at expected time');
        
        
        $persistentEvent->dtstart->addHour(5);
        $persistentEvent->dtend->addHour(5);
        $updatedEvent = $this->_controller->update($persistentEvent);
        $alarmTime = clone $updatedEvent->dtstart;
        $alarmTime->subMinute(30);
        $this->assertTrue($alarmTime->equals($updatedEvent->alarms->getFirstRecord()->alarm_time), 'alarm of updated event is not adjusted');
    }
    
    /**
     * testSetAlarmOfRecurSeries
     */
    public function testSetAlarmOfRecurSeries()
    {
        $event = $this->_getEvent();
        $event->dtstart = Tinebase_DateTime::now()->addHour(1);
        $event->dtend = Tinebase_DateTime::now()->addHour(2);
        
        $event->rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;INTERVAL=1';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $persistentEvent = $this->_controller->create($event);
        $this->assertEquals($event->dtstart->subMinute(30)->toString(), $persistentEvent->alarms->getFirstRecord()->alarm_time->toString(), 'inital alarm fails');
        
        // move whole series
        $persistentEvent->dtstart->addHour(5);
        $persistentEvent->dtend->addHour(5);
        $updatedEvent = $this->_controller->update($persistentEvent);
        $this->assertEquals($persistentEvent->dtstart->subMinute(30)->toString(), $updatedEvent->alarms->getFirstRecord()->alarm_time->toString(), 'update alarm fails');
    }
    
    /**
     * testSetAlarmOfRecurSeriesException
     */
    public function testSetAlarmOfRecurSeriesException()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;INTERVAL=1';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $persistentEvent = $this->_controller->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $exception = Calendar_Model_Rrule::computeNextOccurrence($persistentEvent, $exceptions, new Tinebase_DateTime());
        $exception->dtstart->subHour(6);
        $exception->dtend->subHour(6);
        $persistentException = $this->_controller->createRecurException($exception);
        
        $baseEvent = $this->_controller->getRecurBaseEvent($persistentException);
        $this->_controller->getAlarms($baseEvent);
        
        $exceptions = $this->_controller->getRecurExceptions($persistentException);
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($baseEvent, $exceptions, Tinebase_DateTime::now());
        
        $nextAlarmEventStart = new Tinebase_DateTime(substr($baseEvent->alarms->getFirstRecord()->getOption('recurid'), -19));
        
        $this->assertTrue($nextOccurance->dtstart->equals($nextAlarmEventStart), 'next alarm got not adjusted');
        
        $alarmTime = clone $persistentException->dtstart;
        $alarmTime->subMinute(30);
        $this->assertTrue($alarmTime->equals($persistentException->alarms->getFirstRecord()->alarm_time), 'alarmtime of persistent exception is not correnct/set');
    }
    
    public function testGetRecurExceptions()
    {
        $persitentException = $this->testCreateRecurException();
        
        $baseEvent = $this->_controller->getRecurBaseEvent($persitentException);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($baseEvent, $exceptions, $baseEvent->dtstart);
        $this->_controller->createRecurException($nextOccurance, TRUE);
        
        $exceptions = $this->_controller->getRecurExceptions($persitentException, TRUE);
        $dtstarts = $exceptions->dtstart;

        $this->assertTrue(in_array($nextOccurance->dtstart, $dtstarts), 'deleted instance missing');
        $this->assertTrue(in_array($persitentException->dtstart, $dtstarts), 'exception instance missing');
    }
    
    public function testPeriodFilter()
    {
        $persistentEvent = $this->testCreateEvent();
        
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'period', 'operator' => 'within', 'value' => array(
                'from'  => '2009-04-07',
                'until' => '2010-04-07'
            ))
        )), NULL, FALSE, FALSE);
        
        $this->assertEquals(0, count($events));
    }
    
    /**
     * returns a simple event
     *
     * @return Calendar_Model_Event
     */
    protected function _getEvent($_now=FALSE)
    {
        $event = new Calendar_Model_Event(array(
            'summary'     => 'Mittagspause',
            'dtstart'     => '2009-04-06 13:00:00',
            'dtend'       => '2009-04-06 13:30:00',
            'description' => 'Wieslaw Brudzinski: Das Gesetz garantiert zwar die Mittagspause, aber nicht das Mittagessen...',
        
            'container_id' => $this->_testCalendar->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true,
        ));
        
        if ($_now) {
            $event->dtstart = Tinebase_DateTime::now();
            $event->dtend = Tinebase_DateTime::now()->addMinute(15);
        }
        
        return $event;
    }
    
    /**
     * (non-PHPdoc)
     * @see Calendar_TestCase::_getAttendee()
     */
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
