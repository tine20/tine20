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
 *  - add free/busy cleanup tests
 * 
 * @package     Calendar
 */
class Calendar_Controller_EventGrantsTests extends Calendar_TestCase
{
    
    /**
     * @var Calendar_Controller_Event controller unter test
     */
    protected $_uit;
    
    public function setUp()
    {
        parent::setUp();
        
        /**
         * set up personas personal container grants:
         * 
         *  jsmith:    anyone readGrant, addGrant, editGrant, deleteGrant
         *  pwulf:     anyone readGrant, sclever addGrant, readGrant, editGrant, deleteGrant, privateGrant
         *  sclever:   testuser addGrant, readGrant, editGrant, deleteGrant, privateGrant
         *  jmcblack:  prim group of testuser readGrant, testuser privateGrant
         *  rwright:   sclever has readGrant and editGrant
         */
        $this->_setupTestCalendars();
        
        $this->_uit = Calendar_Controller_Event::getInstance();
    }
    
    public function tearDown()
    {
        parent::tearDown();
        $this->cleanupTestCalendars();
    }
    
    /**
     * reads an event of the personal calendar of jsmith
     *  -> anyone has readGrant, editGrant and deleteGrant
     */
    public function testGrantsByContainerAnyone()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('jsmith', 'jsmith', 'jsmith');
        
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    /**
     * reads an event of the personal calendar of sclever
     *  -> test user has readGrant, editGrant and deleteGrant
     */
    public function testGrantsByContainerUser()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('sclever', 'sclever', 'sclever');
        
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    /**
     * reads an event of the personal calendar of jmcblack
     *  -> default group of testuser has readGrant
     */
    public function testGrantsByContainerGroup()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('jmcblack', 'jmcblack', 'jmcblack');
        
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    /**
     * try to read an event of the personal calendar of rwright
     *  -> no access
     */
    public function testReadGrantByContainerFail()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', 'rwright');
        
        $this->setExpectedException('Tinebase_Exception_AccessDenied');
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
    }
    
    /**
     * reads an event of the personal calendar of rwight
     *  -> test user is attender with implicit readGrant
     */
    public function testGrantsByAttender()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', NULL);
        
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    /**
     * reads an event of the personal calendar of rwright
     *  -> set testuser to organizer! -> implicit readGrand and editGrant
     */
    public function testGrantsByOrganizer()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', NULL, 'rwright');
        
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    /**
     * reads an event of the personal calendar of rwright
     *  -> sclever is attender -> testuser has readGrant for scelver
     */
    public function testGrantsByInheritedAttendeeContainerGrants()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', 'sclever');
        
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    /**
     * try to get/search event of rwright
     *  -> testuser has not Grants, but freebusy
     */
    public function testFreeBusy()
    {
    	Tinebase_Core::getPreference('Calendar')->setValueForUser(Calendar_Preference::FREEBUSY, 1, $this->_personas['rwright']->getId(), TRUE);
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', 'rwright');
        
        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('field' => 'id', 'operator' => 'equals', 'value' => $persistentEvent->getId())
        )));
        
        $event = $events->getFirstRecord();
        $this->assertTrue(empty($event->summary), 'event with freebusy only is not cleaned up');
        $this->assertFalse((bool)$event->{Tinebase_Model_Grants::GRANT_READ});
        $this->assertFalse((bool)$event->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertFalse((bool)$event->{Tinebase_Model_Grants::GRANT_DELETE});
        
        // direct get of freebusy only events is not allowed
        $this->setExpectedException('Tinebase_Exception_AccessDenied');
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
    }
    
    /**
     * reads an private event of jmcblack
     *  -> test user has private grant
     */
    public function testPrivateViaContainer()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('jmcblack', 'jmcblack', 'jmcblack', Calendar_Model_Event::CLASS_PRIVATE);
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_READ});
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    /**
     * attempt to read an private event of pwulf
     *  -> test user has no private grant
     */
    public function testPrivateViaContainerFail()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('pwulf', 'pwulf', 'pwulf', Calendar_Model_Event::CLASS_PRIVATE);
        
        $this->setExpectedException('Tinebase_Exception_AccessDenied');
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
    }
    
    /**
     * reads an private event of rwright with testuser as attender
     *  -> test user should have implicit read grant
     */
    public function testPrivateViaAttendee()
    {
        
    }
    
    /**
     * reads an private event of pwulf and sclever
     *  -> test user has private grant for sclever
     */
    public function testPrivateViaAttendeeInherritance()
    {
        
    }
    
    protected function _createEventInPersonasCalendar($_calendarPersona, $_organizerPersona = NULL, $_attenderPersona = NULL, $_classification = Calendar_Model_Event::CLASS_PUBLIC)
    {
        $calendarId  = $this->_personasDefaultCals[$_calendarPersona]->getId();
        $organizerId = $_organizerPersona ? $this->_personasContacts[$_organizerPersona]->getId() : $this->_testUserContact->getId();
        $attenderId  = $_attenderPersona ? $this->_personasContacts[$_attenderPersona]->getId() : $this->_testUserContact->getId();
        
        $event = $this->_getEvent();
        $event->class = $_classification;
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'        => $attenderId,
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            )
        ));
        $persistentEvent = $this->_uit->create($event);
        
        // we need to adopt conainer through backend, to bypass rights control
        $persistentEvent->container_id = $calendarId;
        $persistentEvent->organizer = $organizerId;
        $this->_backend->update($persistentEvent);
        
        return $persistentEvent;
    }
    
    /**
     * set up personas personal container grants:
     * 
     *  jsmith:    anyone readGrant, addGrant, editGrant, deleteGrant
     *  pwulf:     anyone readGrant, sclever addGrant, readGrant, editGrant, deleteGrant, privateGrant
     *  sclever:   testuser addGrant, readGrant, editGrant, deleteGrant, privateGrant
     *  jmcblack:  prim group of testuser readGrant, testuser privateGrant
     *  rwright:   sclever has readGrant and editGrant
     */
    protected function _setupTestCalendars()
    {
        // jsmith:     anyone readGrant, addGrant, editGrant, deleteGrant
        Tinebase_Container::getInstance()->setGrants($this->_personasDefaultCals['jsmith'], new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => $this->_personas['jsmith']->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
        ), array(
            'account_id'    => 0,
            'account_type'  => 'anyone',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ))), true);
        
        // pwulf:      anyone readGrant, sclever addGrant, readGrant, editGrant, deleteGrant, privateGrant
        Tinebase_Container::getInstance()->setGrants($this->_personasDefaultCals['pwulf'], new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => $this->_personas['pwulf']->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
        ), array(
            'account_id'    => 0,
            'account_type'  => 'anyone',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => false,
            Tinebase_Model_Grants::GRANT_EDIT     => false,
            Tinebase_Model_Grants::GRANT_DELETE   => false,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ), array(
            'account_id'    => $this->_personas['sclever']->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ))), true);
        
        // sclever:   testuser addGrant, readGrant, editGrant, deleteGrant, privateGrant
        Tinebase_Container::getInstance()->setGrants($this->_personasDefaultCals['sclever'], new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => $this->_personas['sclever']->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
        ),array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ))), true);
        
        // jmacblack: prim group of testuser readGrant, testuser privateGrant
        Tinebase_Container::getInstance()->setGrants($this->_personasDefaultCals['jmcblack'], new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => $this->_personas['jmcblack']->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
        ),array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => false,
            Tinebase_Model_Grants::GRANT_EDIT     => false,
            Tinebase_Model_Grants::GRANT_DELETE   => false,
            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ),array(
            'account_id'    => Tinebase_Core::getUser()->accountPrimaryGroup,
            'account_type'  => 'group',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => false,
            Tinebase_Model_Grants::GRANT_EDIT     => false,
            Tinebase_Model_Grants::GRANT_DELETE   => false,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ))), true);
        
        // rwright:   nothing
        Tinebase_Container::getInstance()->setGrants($this->_personasDefaultCals['rwright'], new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => $this->_personas['rwright']->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
        ), array(
            'account_id'    => $this->_personas['sclever']->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => false,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => false,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ))), true);
    }
    
    /**
     * resets all grants of personas calendars and deletes events from it
     */
    protected function cleanupTestCalendars()
    {
        foreach ($this->_personasDefaultCals as $loginName => $calendar) {
            Tinebase_Container::getInstance()->setGrants($calendar, new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
	            'account_id'    => $this->_personas[$loginName]->getId(),
	            'account_type'  => 'user',
	            Tinebase_Model_Grants::GRANT_READ     => true,
	            Tinebase_Model_Grants::GRANT_ADD      => true,
	            Tinebase_Model_Grants::GRANT_EDIT     => true,
	            Tinebase_Model_Grants::GRANT_DELETE   => true,
	            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
	            Tinebase_Model_Grants::GRANT_ADMIN    => true,
	        ))), true);
	        
	        $events = $this->_backend->search(new Calendar_Model_EventFilter(array(
	            array('field' => 'container_id', 'operator' => 'equals', 'value' => $calendar->getId()),
	        )), new Tinebase_Model_Pagination(array()));
	        
	        // delete alarms
	        Tinebase_Alarm::getInstance()->deleteAlarmsOfRecord('Calendar_Model_Event', $events->getArrayOfIds());
	        
	        // delete events
	        foreach ($events as $event) {
	            $this->_backend->delete($event->getId());
	        }
    	}
    }
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_Controller_EventGrantsTests::main') {
    Calendar_Controller_EventGrantsTests::main();
}
