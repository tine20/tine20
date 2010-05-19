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
        $event = $this->_getEvent();
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
     * returns a simple recure event
     *
     * @return Calendar_Model_Event
     */
    protected function _getRecurEvent()
    {
        return new Calendar_Model_Event(array(
            'summary'     => 'Breakfast',
            'dtstart'     => '2009-03-01 06:00:00',
            'dtend'       => '2009-03-01 06:15:00',
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
