<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * @version     $Id: RecurTest.php 14014 2010-04-26 08:43:54Z g.ciyiltepe@metaways.de $
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
     * @var Calendar_Controller_Event controller
     */
    protected $_controller;
    
    public function setUp()
    {
    	parent::setUp();
        $this->_controller = Calendar_Controller_Event::getInstance();
    }
    
    /**
     * Conflict between an existing and recurring event when create the event
     */
    public function testCreateConflictBetweenRecurAndExistEvent()
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') handle event of type ');
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
        $from = new Zend_Date('2010-06-01 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $until = new Zend_Date('2010-06-31 23:59:59', Tinebase_Record_Abstract::ISO8601LONG);
        
        $event = $this->_getRecurEvent();
        $event->rrule = "FREQ=MONTHLY;INTERVAL=1;BYDAY=3TH";
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['sclever']->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_personasContacts['pwulf']->getId())
        ));
        
        $persistentRecurEvent = $this->_controller->create($event);
        
        // get first recurrance
        $eventSet = new Tinebase_Record_RecordSet('Calendar_Model_Event', array($persistentRecurEvent));
        Calendar_Model_Rrule::mergeRecuranceSet($eventSet, 
            new Zend_Date('2010-06-01 00:00:00', Tinebase_Record_Abstract::ISO8601LONG),
            new Zend_Date('2010-06-31 23:59:59', Tinebase_Record_Abstract::ISO8601LONG)
        );
        $firstRecurrance = $eventSet[1];
        
        // create exception of this first occurance: 17.6. -> 24.06.
        $firstRecurrance->dtstart->add(1, Zend_Date::WEEK);
        $firstRecurrance->dtend->add(1, Zend_Date::WEEK);
        $this->_controller->createRecurException($firstRecurrance);
        
        // fetch weekview 14.06 - 20.06.
        $from = new Zend_Date('2010-06-14 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $until = new Zend_Date('2010-06-20 23:59:59', Tinebase_Record_Abstract::ISO8601LONG);
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid', 'operator' => 'equals', 'value' => $persistentRecurEvent->uid),
            array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $from, 'until' => $until),
        ))));
        Calendar_Model_Rrule::mergeRecuranceSet($weekviewEvents, $from, $until);
        
        // make shure the 17.6. is not in the set
        $this->assertEquals(1, count($weekviewEvents), '17.6. is an exception date and must not be part of this weekview');
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
    

if (PHPUnit_MAIN_METHOD == 'Calendar_Controller_EventTests::main') {
    Calendar_Controller_EventTests::main();
}
