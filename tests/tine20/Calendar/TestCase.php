<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
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
    
    /**
     * @var Tinebase_Record_RecordSet test calendars to be deleted on tearDown
     */
    protected $_testCalendars;
    
    /**
     * @var Tinebase_Model_FullUser
     */
    protected $_testUser;
    
    /**
     * @var Addressbook_Model_Contact
     */
    protected $_testUserContact;
    
    /**
     * personas
     *
     * @var array
     */
    protected $_personas;
    
    /**
     * personas contacts
     * @var array
     */
    protected $_personasContacts;
    
    /**
     * persona calendars
     *
     * @var array
     */
    protected $_personasDefaultCals = array();
    
    /**
     * transaction id if test is wrapped in an transaction
     */
    protected $_transactionId = NULL;
    /**
     * set up tests
     *
     */
    public function setUp()
    {
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_backend = new Calendar_Backend_Sql();
        
        $this->_personas = Zend_Registry::get('personas');
        foreach ($this->_personas as $loginName => $user) {
            $defaultCalendarId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $user->getId());
            $this->_personasContacts[$loginName] = Addressbook_Controller_Contact::getInstance()->getContactByUserId($user->getId());
            $this->_personasDefaultCals[$loginName] = Tinebase_Container::getInstance()->getContainerById($defaultCalendarId);
        }
        
        $this->_testUser = Tinebase_Core::getUser();
        $this->_testUserContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($this->_testUser->getId());
        $this->_testCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'PHPUnit test calendar',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => $this->_backend->getType(),
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()
        ), true));
        
        $this->_testCalendars = new Tinebase_Record_RecordSet('Tinebase_Model_Container');
        $this->_testCalendars->addRecord($this->_testCalendar);
    }
    
    /**
     * tear down tests
     *
     */
    public function tearDown()
    {
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->rollBack();
        }
        
        else {
            $events = $this->_backend->search(new Calendar_Model_EventFilter(array(
                array('field' => 'container_id', 'operator' => 'in', 'value' => $this->_testCalendars->getId()),
            )), new Tinebase_Model_Pagination(array()));
            
            // delete alarms
            Tinebase_Alarm::getInstance()->deleteAlarmsOfRecord('Calendar_Model_Event', $events->getArrayOfIds());
            
            foreach ($events as $event) {
                $this->_backend->delete($event->getId());
            }
            
            foreach ($this->_testCalendars as $cal) {
                Tinebase_Container::getInstance()->deleteContainer($cal, true);
            }
        }
    }
    
    /**
     * returns a simple event
     *
     * @return Calendar_Model_Event
     */
    protected function _getEvent($_now=FALSE)
    {
        $event = new Calendar_Model_Event(array(
            'summary'     => 'Wakeup',
            'dtstart'     => '2009-03-25 06:00:00',
            'dtend'       => '2009-03-25 06:15:00',
            'description' => 'Early to bed and early to rise, makes a men healthy, wealthy and wise',
            'attendee'    => $this->_getAttendee(),
        
            'container_id' => $this->_testCalendar->getId(),
            'organizer'    => $this->_testUserContact->getId(),
            'uid'          => Calendar_Model_Event::generateUID(),
        
            Tinebase_Model_Grants::GRANT_READ    => true,
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            Tinebase_Model_Grants::GRANT_DELETE  => true,
        ));
        
        if ($_now) {
            $event->dtstart = Tinebase_DateTime::now();
            $event->dtend = Tinebase_DateTime::now()->addMinute(15);
        }
        
        return $event;
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
                'minutes_before'    => 0
            ),
        ), TRUE);
        
        return $event;
    }
    
    /**
     * get test attendee
     *
     * @return Tinebase_Record_RecordSet
     */
    protected function _getAttendee()
    {
        return new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'        => $this->_testUserContact->getId(),
                'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            ),
            array(
                'user_id'        => $this->_personasContacts['sclever']->getId(),
                'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
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
    
    protected function _testNeedsTransaction()
    {
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
            $this->_transactionId = NULL;
        }
    }
}
