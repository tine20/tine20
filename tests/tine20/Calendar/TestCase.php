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
    protected $_testCalendar = NULL;
    
    /**
     * @var Tinebase_Record_RecordSet test calendars to be deleted on tearDown
     */
    protected $_testCalendars = NULL;
    
    /**
     * @var Tinebase_Model_FullUser
     */
    protected $_testUser = NULL;
    
    /**
     * @var Addressbook_Model_Contact
     */
    protected $_testUserContact = NULL;
    
    /**
     * personas
     *
     * @var array
     */
    protected $_personas = NULL;
    
    /**
     * personas contacts
     * @var array
     */
    protected $_personasContacts = array();
    
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
        $this->_testUser = Tinebase_Core::getUser();
    }
    
    /**
     * tear down tests
     *
     */
    public function tearDown()
    {
        Calendar_Controller_Event::getInstance()->sendNotifications(false);
        
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->rollBack();
        } elseif ($this->_testCalendar !== NULL) {
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
        // do NOT unset _testUser,the tear down of Calendar_Frontend_WebDAV_EventTest still requires _testUser
        $this->_testUserContact = NULL;
        $this->_testCalendar = NULL;
        $this->_testCalendars = NULL;
        $this->_personas = NULL;
        $this->_personasContacts = array();
        $this->_personasDefaultCals = array();
    }
    
    /**
     * returns test persons contacts
     * 
     * @param string $loginName
     * @return Addressbook_Model_Contact
     */
    protected function _getPersonasContacts($loginName)
    {
        if (!isset($this->_personasContacts[$loginName])) {
            $user = $this->_getPersona($loginName);
            $this->_personasContacts[$loginName] = Addressbook_Controller_Contact::getInstance()->getContactByUserId($user->getId());
        }
        return $this->_personasContacts[$loginName];
    }
    
    /**
     * returns test persons default calendar
     * 
     * @param string $loginName
     * @return Tinebase_Model_Container
     */
    protected function _getPersonasDefaultCals($loginName)
    {
        if (!isset($this->_personasDefaultCals[$loginName])) {
            $user = $this->_getPersona($loginName);
            $defaultCalendarId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $user->getId());
            $this->_personasDefaultCals[$loginName] = Tinebase_Container::getInstance()->getContainerById($defaultCalendarId);
        }
        return $this->_personasDefaultCals[$loginName];
    }
    
    /**
     * returns all test persons default calendar
     * 
     * @return array
     */
    protected function _getAllPersonasDefaultCals()
    {
        foreach ($this->_getPersonas() as $loginName => $user)
        {
            $this->_getPersonasDefaultCals($loginName);
        }
        return $this->_personasDefaultCals;
    }
    
    /** return a test person
     * @return 
     */
    protected function _getPersona($loginName)
    {
        if ($this->_personas === NULL) {
            $this->_getPersonas();
        }
        return $this->_personas[$loginName];
    }
    
    /**
     * returns an array of test persons
     * 
     * @return array
     */
    protected function _getPersonas()
    {
        if ($this->_personas === NULL) {
            $this->_personas = Zend_Registry::get('personas');
        }
        return $this->_personas;
    }
    
    /**
     * returns a test user
     * 
     * @return Tinebase_Model_FullUser
     */
    protected function _getTestUser()
    {
        return $this->_testUser;
    }
    
    /**
     * returns the test users contact
     * 
     * @return
     */
    protected function _getTestUserContact()
    {
        if ($this->_testUserContact === NULL) {
            $this->_testUserContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($this->_getTestUser()->getId());
        }
        return $this->_testUserContact;
    }
    /**
     * returns a test calendar set
     * 
     * @return Tinebase_Record_RecordSet
     */
    protected function _getTestCalendars()
    {
        if ($this->_testCalendars === NULL) {
            $this->_getTestCalendar();
        }
        return $this->_testCalendars;
    }
    /**
     * returns a test calendar
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getTestCalendar()
    {
        if ($this->_testCalendar === NULL) {
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
        return $this->_testCalendar;
    }
    
    /**
     * returns a simple event
     * 
     * @param bool $now
     * @return Calendar_Model_Event
     */
    protected function _getEvent($now = FALSE)
    {
        $event = new Calendar_Model_Event(array(
            'summary'     => 'Wakeup',
            'dtstart'     => '2009-03-25 06:00:00',
            'dtend'       => '2009-03-25 06:15:00',
            'description' => 'Early to bed and early to rise, makes a men healthy, wealthy and wise',
            'attendee'    => $this->_getAttendee(),
        
            'container_id' => $this->_getTestCalendar()->getId(),
            'organizer'    => $this->_getTestUserContact()->getId(),
            'uid'          => Calendar_Model_Event::generateUID(),
        
            Tinebase_Model_Grants::GRANT_READ    => true,
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            Tinebase_Model_Grants::GRANT_DELETE  => true,
        ));
        
        if ($now) {
            $event->dtstart = Tinebase_DateTime::now();
            $event->dtend = Tinebase_DateTime::now()->addMinute(15);
        }
        
        return $event;
    }

    /**
     * returns a simple event
     *
     * @param bool $now use date of now
     * @return Calendar_Model_Event
     */
    protected function _getEventWithAlarm($now = FALSE)
    {
        $event = $this->_getEvent($now);
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
                'user_id'        => $this->_getTestUserContact()->getId(),
                'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            ),
            array(
                'user_id'        => $this->_GetPersonasContacts('sclever')->getId(),
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
    
    /**
     * test needs transaction
     */
    protected function _testNeedsTransaction()
    {
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
            $this->_transactionId = NULL;
        }
    }
    
    /**
     * get all calendar grants
     * 
     * @param Tinebase_Model_User $user
     * @return array
     */
    protected function _getAllCalendarGrants($user = null)
    {
        return array(
            'account_id'    => $user ? $user->getId() : Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_PRIVATE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Tinebase_Model_Grants::GRANT_FREEBUSY => true,
        );
    }
}
