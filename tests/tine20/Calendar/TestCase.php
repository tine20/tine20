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
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Backend_Sql
 * 
 * @package     Calendar
 */
abstract class Calendar_TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var Calendar_Backend_Sql SQL Backend in test
     */
    protected $_backend;
    
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
        
        $this->_backend = new Calendar_Backend_Sql();
        
        $this->_testCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'PHPUnit test calendar',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'        => $this->_backend->getType(),
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()
        ), true));
    }
    
    public function tearDown()
    {
        $events = $this->_backend->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        )), new Tinebase_Model_Pagination(array()));
        
        // delete alarms
        Tinebase_Alarm::getInstance()->deleteAlarmsOfRecord('Calendar_Model_Event', $events->getArrayOfIds());
        
        foreach ($events as $event) {
            $this->_backend->delete($event->getId());
        }
        
        Tinebase_Container::getInstance()->deleteContainer($this->_testCalendar, true);
    }
    
    /**
     * returns a simple event
     *
     * @return Calendar_Model_Event
     */
    protected function _getEvent()
    {
        return new Calendar_Model_Event(array(
            'summary'     => 'Wakeup',
            'dtstart'     => '2009-03-25 06:00:00',
            'dtend'       => '2009-03-25 06:15:00',
            'description' => 'Early to bed and early to rise, makes a men healthy, wealthy and wise',
            'attendee'    => $this->_getAttendee(),
        
            'container_id' => $this->_testCalendar->getId(),
            'organizer'    => Tinebase_Core::getUser()->getId(),
            'uid'          => Calendar_Model_Event::generateUID(),
        
            'readGrant'    => true,
            'editGrant'    => true,
            'deleteGrant'  => true,
        ));
    }

    /**
     * returns a simple event
     *
     * @return Calendar_Model_Event
     * 
     * @todo add options?
     */
    protected function _getEventWithAlarm()
    {
        $event = $this->_getEvent();
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            array(
                'model'             => 'Calendar_Model_Event',
                'alarm_time'        => Zend_Date::now(),
                'sent_status'       => Tinebase_Model_Alarm::STATUS_PENDING, 
                'options'           => ''
            ),
        ), TRUE);
        
        return $event;
    }
    
    protected function _getAttendee()
    {
        return new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'        => Tinebase_Core::getUser()->getId(),
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            ),
            array(
                'user_id'        => $this->_personas['sclever']->getId(),
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            ),
            /* no group suppoert yet
            array(
                'user_id'        => Tinebase_Core::getUser()->accountPrimaryGroup,
                'user_type'      => Calendar_Model_Attender::USERTYPE_GROUP,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            )
            */
        ));
    }
}