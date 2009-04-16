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
class Calendar_Controller_EventTests extends PHPUnit_Framework_TestCase
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
        $this->_controller = Calendar_Controller_Event::getInstance();
        $this->_testCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'PHPUnit test calendar',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'        => 'sometype',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()
        ), true));
    }
    
    public function tearDown()
    {
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        )), new Tinebase_Model_Pagination(array()), false);
        
        // we need to have some rights to delete all events via controller ;-)
        Tinebase_Container::getInstance()->setGrants($this->_testCalendar, new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'editGrant'     => true,
            'deleteGrant'   => true,
            'adminGrant'    => true,
        ))), true);
        
        // only delete events from our testcalendar. (container_id filter also allowes implicts from other calendars)
        foreach ($events as $event) {
        	if ($event->container_id == $this->_testCalendar->getId()) {
        	    $this->_controller->delete($event->getId());
        	}
        }
        
        Tinebase_Container::getInstance()->deleteContainer($this->_testCalendar, true);
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
    }
    
    /**
     * @todo use exception api once we have it!
     *
     */
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
    
    /**
     * @todo use exception api once we have it!
     *
     */
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
    
    /**
     * part of generic controller, but needs to be tested somewhere...
     * @todo move to a better place
     *
     */
    public function testDeleteACL()
    {
        $event = $this->_getEvent();
        $persitentEvent = $this->_controller->create($event);
        
        // remove all container grants
        Tinebase_Container::getInstance()->setGrants($this->_testCalendar, new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array()), true);
        
        $this->setExpectedException('Tinebase_Exception_AccessDenied');
        $this->_controller->delete($persitentEvent->getId());
    }
    
    /**
     * tests implicit READ grants for organizer and participants
     */
    public function testImplicitOrganizerGrants()
    {
        $event = $this->_getEvent();
        $event->organizer = Tinebase_Core::getUser()->getId();
        
        $persitentEvent = $this->_controller->create($event);
        
        // remove all container grants
        Tinebase_Container::getInstance()->setGrants($this->_testCalendar, new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array()), true);
        
        $loadedEvent = $this->_controller->get($persitentEvent->getId());
        $this->assertEquals($persitentEvent->getId(), $loadedEvent->getId(), 'organizer should have implicit read grant!');
        
        $persitentEvent->summary = 'Lunchtime';
        $updatedEvent = $this->_controller->update($persitentEvent);
        $this->assertEquals($persitentEvent->summary, $updatedEvent->summary, 'organizer should have implicit edit grant');
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId() + 1),
        ));
        
        $foundEvents = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertGreaterThanOrEqual(1, count($foundEvents), 'organizer should have implicit read rights in search action');
        
        $this->_controller->delete($persitentEvent->getId());
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_controller->get($persitentEvent->getId());
    }
    
    public function testImplicitAttendeeGrants()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attendee', array(
            /*array(
                'user_id'   => Tinebase_Core::getUser()->getId(),
                'role'      => Calendar_Model_Attendee::ROLE_REQUIRED
            ),*/
            array(
                'user_id'   => Tinebase_Core::getUser()->accountPrimaryGroup,
                'user_type' => Calendar_Model_Attendee::USERTYPE_GROUP
            )
        ));
        
        $persitentEvent = $this->_controller->create($event);
        
        // remove all container grants
        Tinebase_Container::getInstance()->setGrants($this->_testCalendar, new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array()), true);
        
        $loadedEvent = $this->_controller->get($persitentEvent->getId());
        $this->assertEquals($persitentEvent->getId(), $loadedEvent->getId(), 'attendee should have implicit read grant!');
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId() + 1),
        ));
        $foundEvents = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertGreaterThanOrEqual(1, count($foundEvents), 'attendee should have implicit read rights in search action');
        
        $this->setExpectedException('Tinebase_Exception_AccessDenied');
        $this->_controller->update($persitentEvent);
        $this->_controller->delete(($persitentEvent->getId()));
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
        ));
    }
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_Controller_EventTests::main') {
    Calendar_Controller_EventTests::main();
}
