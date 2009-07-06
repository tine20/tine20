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
    define('PHPUnit_MAIN_METHOD', 'Calendar_Controller_EventGrantsTests::main');
}

/**
 * Test class for Calendar_Controller_Event
 * 
 * @todo:
 *  - add grants spoofing test from JSON frontend
 *  - add free/busy cleanup tests
 * 
 * @package     Calendar
 */
class Calendar_Controller_EventGrantsTests extends PHPUnit_Framework_TestCase
{
    
    /**
     * @var Calendar_Controller_Event controller unter test
     */
    protected $_controller;
    
    /**
     * @var Tinebase_Model_Container
     */
    protected $_testCalendar;
    
    protected $_personas;
    
    protected $_personasDefaultCals = array();
    
    public function setUp()
    {
        $this->_personas = Zend_Registry::get('personas');
        foreach ($this->_personas as $loginName => $user) {
            $defaultCalendarId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $user->getId());
            $this->_personasDefaultCals[$loginName] = Tinebase_Container::getInstance()->getContainerById($defaultCalendarId);
        }
        
        
        $this->_controller = Calendar_Controller_Event::getInstance();
        
        // test calendar for test user
        $this->_testCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'PHPUnit test calendar',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'        => 'sometype',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()
        ), true));
        
        // anyone has GRANT_READ on default cal. of jsmith
        Tinebase_Container::getInstance()->setGrants($this->_personasDefaultCals['jsmith'], new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => $this->_personas['jsmith']->getId(),
            'account_type'  => 'user',
            'readGrant'     => true,
            'addGrant'      => true,
            'editGrant'     => true,
            'deleteGrant'   => true,
            'adminGrant'    => true,
        ), array(
            'account_id'    => 0,
            'account_type'  => 'anyone',
            'readGrant'     => true,
            'addGrant'      => false,
            'editGrant'     => false,
            'deleteGrant'   => false,
            'adminGrant'    => false,
        ))), true);
        
        // test user has GRANT_READ on default cal. of pwulf
        // sclever has GRANT_READ,GRANT_UPDATE and GRANT_DELETE on default cal. of pwulf (secritary)
        Tinebase_Container::getInstance()->setGrants($this->_personasDefaultCals['pwulf'], new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => $this->_personas['pwulf']->getId(),
            'account_type'  => 'user',
            'readGrant'     => true,
            'addGrant'      => true,
            'editGrant'     => true,
            'deleteGrant'   => true,
            'adminGrant'    => true,
        ),array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'readGrant'     => true,
            'addGrant'      => false,
            'editGrant'     => false,
            'deleteGrant'   => false,
            'adminGrant'    => false,
        ), array(
            'account_id'    => $this->_personas['sclever']->getId(),
            'account_type'  => 'user',
            'readGrant'     => true,
            'addGrant'      => true,
            'editGrant'     => true,
            'deleteGrant'   => true,
            'adminGrant'    => false,
        ))), true);
    }
    
    public function tearDown()
    {
        // we need to have some rights to delete all events via controller ;-)
        Tinebase_Container::getInstance()->setGrants($this->_testCalendar, new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'editGrant'     => true,
            'deleteGrant'   => true,
            'adminGrant'    => true,
        ))), true);
        
        $eventIds = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        )), new Tinebase_Model_Pagination(array()), false, true);
        
        $this->_controller->delete($eventIds);
        
        Tinebase_Container::getInstance()->deleteContainer($this->_testCalendar, true);
    }
    
    public function testCreateEvent()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'   => Tinebase_Core::getUser()->getId(),
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            ),
            array(
                'user_id'   => $this->_personas['pwulf']->getId(),
            ),
        ));
        $persitentEvent = $this->_controller->create($event);
        
//        $db = Tinebase_Core::getDb();
//        $_backend = new Calendar_Backend_Sql();
//        $_select = $_backend->getTestSelect();
//        
//        $_select->joinLeft(
//                /* table  */ array('attendee' => $_backend->getTablePrefix() . 'cal_attendee'), 
//                /* on     */ $db->quoteIdentifier('attendee.cal_event_id') . ' = ' . $db->quoteIdentifier('cal_events.id'));//,
//                ///* select */ array());
//        
//        $_select->joinLeft(
//                /* table  */ array('dispgrants' => $_backend->getTablePrefix() . 'container_acl'), 
//                /* on     */ $db->quoteIdentifier('dispgrants.container_id') . ' = ' . $db->quoteIdentifier('attendee.displaycontainer_id') . 
//                               ' AND ' . self::getContainGrantCondition('dispgrants'));
//                ///* select */ array());
//                
//        $_select->joinLeft(
//                /* table  */ array('physgrants' => $_backend->getTablePrefix() . 'container_acl'), 
//                /* on     */ $db->quoteIdentifier('physgrants.container_id') . ' = ' . $db->quoteIdentifier('cal_events.container_id'));//,
//                ///* select */ array());
//                
//        
//                
//        echo $_select;
//        die();
        
//        $stmt = $db->query($_select);
        //print_r($stmt->fetchAll());
        
        //self::getContainGrantCondition('disgrants', Tinebase_Core::getUser(), Tinebase_Model_Container::GRANT_READ);
        
        $this->assertEquals($event->description, $persitentEvent->description);
        $this->assertTrue($event->dtstart->equals($persitentEvent->dtstart));
        $this->assertEquals(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE), $persitentEvent->originator_tz);
        
        return $persitentEvent;
    }
    
    /**
     * part of generic controller, but needs to be tested somewhere...
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
    
    /**
     * this testcase is wrong. the acl filter _always_ includes a container filter by design
     * if we remove ourselfes all container grants, the implicit grant is not resolveable any more!
     * 
     * - an owner free personal container does not occour in real operation!
     * - if current user is attender and the event is in one of his personal containers, always the container is implied in the acl filter!
     *
     */
    public function testImplicitAttendeeGrants()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'   => Tinebase_Core::getUser()->getId(),
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            ),/*
            array(
                'user_id'   => Tinebase_Core::getUser()->accountPrimaryGroup,
                'user_type' => Calendar_Model_Attender::USERTYPE_GROUP
            )*/
        ));
        
        $persitentEvent = $this->_controller->create($event);
        
        // remove all container grants
        Tinebase_Container::getInstance()->setGrants($this->_testCalendar, new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array()), true);
        
        $loadedEvent = $this->_controller->get($persitentEvent->getId());
        $this->assertEquals($persitentEvent->getId(), $loadedEvent->getId(), 'attendee should have implicit read grant');

        // in a group invitation, no displaycontainer_id is set for attendee!
        // @todo rework group-invitations to directly expand group to attendee
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'specialNode', 'value' => 'all'                   ),
            array('field' => 'id',           'operator' => 'equals',      'value' => $persitentEvent->getId()),
            
        ));
        $foundEvents = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertGreaterThanOrEqual(1, count($foundEvents), 'attendee should have implicit read rights in search action');
        
        $this->setExpectedException('Tinebase_Exception_AccessDenied');
        $this->_controller->update($persitentEvent);
        $this->_controller->delete(($persitentEvent->getId()));
    }
    
    public function testSetAttendeeStatusViaSaveEvent()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        unset($event->attendee[1]);
        
        print_r($event->toArray());
        
        /*
        $eventData = $this->testCreateEvent();
        $eventData['container_id'] = $eventData['container_id']['id'];
        
        // should be ok to only test test user
        $eventData['attendee'][0]['status'] = Calendar_Model_Attender::STATUS_TENTATIVE;
        $eventData['summary'] = 'This text must not be saved!';
        
        // force attendee saving w.o. event saving  
        $eventData['editGrant'] = false;
        
        $updatedEventData = $this->_uit->saveEvent(Zend_Json::encode($eventData));
        
        $loadedEventData = $this->_uit->getEvent($eventData['id']);
        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $eventData['attendee'][0]['status']);
        $this->assertNotEquals($eventData['summary'], $loadedEventData['summary'], 'event must not be updated!');
        */
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
    
    protected function _getAttendee()
    {
        return new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'   => Tinebase_Core::getUser()->getId(),
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            ),
            array(
                'user_id'   => Tinebase_User::getInstance()->getUserByLoginName('sclever')->getId(),
            ),
            array(
                'user_id'   => Tinebase_User::getInstance()->getUserByLoginName('sclever')->getId(),
            ),
            array(
                'user_id'   => Tinebase_Core::getUser()->accountPrimaryGroup,
                'user_type' => Calendar_Model_Attender::USERTYPE_GROUP
            )
        ));
    }
    
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_Controller_EventGrantsTests::main') {
    Calendar_Controller_EventGrantsTests::main();
}
