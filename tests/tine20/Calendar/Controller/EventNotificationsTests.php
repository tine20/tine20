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
    define('PHPUnit_MAIN_METHOD', 'Calendar_Controller_EventNotificationsTests::main');
}

/**
 * Test class for Calendar_Controller_EventNotifications
 * 
 * @package     Calendar
 */
class Calendar_Controller_EventNotificationsTests extends Calendar_TestCase
{
    /**
     * @var Calendar_Controller_Event controller unter test
     */
    protected $_eventController;
    
    /**
     * @var Calendar_Controller_EventNotifications controller unter test
     */
    protected $_notificationController;
    
    /**
     * @var Zend_Mail_Transport_Array
     */
    protected $_mailer = NULL;
    
    /**
     * @var Tinebase_Model_Container
     */
    protected $_testCalendar;
    
    public function setUp()
    {
        parent::setUp();
        $this->_eventController = Calendar_Controller_Event::getInstance();
        $this->_notificationController = Calendar_Controller_EventNotifications::getInstance();
        
        $this->_mailer = Tinebase_Smtp::getDefaultTransport();
        
        $this->_setupPreferences();

    }
    
    public function testInvitation()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        
        $this->_mailer->flush();
        $persitentEvent = $this->_eventController->create($event);
        $this->_assertMail('jsmith', NULL);
        $this->_assertMail('pwulf, sclever, jmcblack, rwright', 'invit');
        
        $this->_mailer->flush();
        $persitentEvent = $this->_eventController->delete($persitentEvent);
        $this->_assertMail('jsmith', NULL);
        $this->_assertMail('pwulf, sclever, jmcblack, rwright', 'cancel');
    }
    
    public function testUpdateEmpty()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persitentEvent = $this->_eventController->create($event);
        
        // no updates
        $this->_mailer->flush();
        $updatedEvent = $this->_eventController->update($persitentEvent);
        $this->_assertMail('jsmith, pwulf, sclever, jmcblack, rwright', NULL);
    }
    
    public function testUpdateChangeAttendee()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('pwulf, jmcblack, rwright');
        $persitentEvent = $this->_eventController->create($event);
        
        $persitentEvent->attendee->merge($this->_getPersonaAttendee('jsmith, sclever'));
        $persitentEvent->attendee->removeRecord(
            $persitentEvent->attendee->find('user_id', $this->_personasContacts['pwulf']->getId())
        );
        $persitentEvent->attendee->find('user_id', $this->_personasContacts['rwright']->getId())->status =
            Calendar_Model_Attender::STATUS_ACCEPTED;
        $persitentEvent->attendee->find('user_id', $this->_personasContacts['jmcblack']->getId())->status =
            Calendar_Model_Attender::STATUS_DECLINED;
            
        $this->_mailer->flush();
        $updatedEvent = $this->_eventController->update($persitentEvent);
        $this->_assertMail('jsmith, jmcblack', NULL);
        $this->_assertMail('sclever', 'invit');
        $this->_assertMail('pwulf', 'cancel');
        $this->_assertMail('rwright', 'Attendee');
    }
    
    public function testUpdateReschedule()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persitentEvent = $this->_eventController->create($event);
        
        $persitentEvent->summary = 'reschedule notification has precedence over normal update';
        $persitentEvent->dtstart->addHour(1);
        $persitentEvent->dtend->addHour(1);
        
        $this->_mailer->flush();
        $updatedEvent = $this->_eventController->update($persitentEvent);
        $this->_assertMail('jsmith, pwulf', NULL);
        $this->_assertMail('sclever, jmcblack, rwright', 'reschedul');
    }
    
    public function testUpdateDetails()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persitentEvent = $this->_eventController->create($event);
        
        $persitentEvent->summary = 'detail update notification has precedence over attendee update';
        $persitentEvent->url = 'http://somedetail.com';
        $persitentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        
        $this->_mailer->flush();
        $updatedEvent = $this->_eventController->update($persitentEvent);
        $this->_assertMail('jsmith, pwulf, sclever', NULL);
        $this->_assertMail('jmcblack, rwright', 'update');
    }
        
    public function testUpdateAttendeeStatus()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persitentEvent = $this->_eventController->create($event);
        
        $persitentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        $this->_mailer->flush();
        $updatedEvent = $this->_eventController->update($persitentEvent);
        $this->_assertMail('jsmith, pwulf, sclever, jmcblack', NULL);
        $this->_assertMail('rwright', 'decline');
    }
    
    public function testOrganizerNotificationSupress()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf');
        $event->organizer = $this->_personasContacts['jsmith']->getId();
        $persitentEvent = $this->_eventController->create($event);
        
        $persitentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        $this->_mailer->flush();
        $updatedEvent = $this->_eventController->update($persitentEvent);
        $this->_assertMail('jsmith, pwulf', NULL);
    }
    
    public function testOrganizerNotificationSend()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf');
        $event->organizer = $this->_personasContacts['pwulf']->getId();
        $persitentEvent = $this->_eventController->create($event);
        
        $persitentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        $this->_mailer->flush();
        $updatedEvent = $this->_eventController->update($persitentEvent);
        $this->_assertMail('jsmith', NULL);
        $this->_assertMail('pwulf', 'decline');
    }
    
    
    /**
     * checks if mail for persona got send
     * 
     * @param string $_personas
     * @param string $_assertString
     * @return void
     */
    protected function _assertMail($_personas, $_assertString = NULL)
    {
        foreach (explode(',', $_personas) as $personaName) {
            $mailsForPersona = array();
            $personaEmail = $this->_personas[trim($personaName)]->accountEmailAddress;
            
            foreach($this->_mailer->getMessages() as $message) {
                if (array_value(0, $message->getRecipients()) == $personaEmail) {
                    array_push($mailsForPersona, $message);
                }
            }
            
            if (! $_assertString) {
                $this->assertEquals(0, count($mailsForPersona), 'No mail should be send for '. $personaName);
            } else {
                $this->assertEquals(1, count($mailsForPersona), 'One mail should be send for '. $personaName);
                $subject = $mailsForPersona[0]->getSubject();
                $this->assertTrue(FALSE !== strpos($subject, $_assertString), 'Mail subject for ' . $personaName . ' should contain "' . $_assertString . '" but '. $subject . ' is given');
            }
        }
    }
    
    protected function _getPersonaAttendee($_personas)
    {
        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        foreach (explode(',', $_personas) as $personaName) {
            $attendee->addRecord(new Calendar_Model_Attender(array(
                'user_id'        => $this->_personasContacts[trim($personaName)]->getId(),
                'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            )));
        }
        
        return $attendee;
    }
    
    /**
     * setup preferences for personas
     * 
     * jsmith   -> no updates
     * pwulf    -> on invitaion/cancelation
     * sclever  -> on reschedules
     * jmblack  -> on updates except answers
     * rwright  -> even on ansers
     * 
     * @return void
     */
    protected function _setupPreferences()
    {
        // set notification levels
        $calPreferences = Tinebase_Core::getPreference('Calendar');
        $calPreferences->setValueForUser(
            Calendar_Preference::NOTIFICATION_LEVEL, 
            Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_NONE,
            $this->_personas['jsmith']->getId(), TRUE
        );
        $calPreferences->setValueForUser(
            Calendar_Preference::NOTIFICATION_LEVEL, 
            Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_INVITE_CANCLE,
            $this->_personas['pwulf']->getId(), TRUE
        );
        $calPreferences->setValueForUser(
            Calendar_Preference::NOTIFICATION_LEVEL, 
            Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_EVENT_RESCHEDULE,
            $this->_personas['sclever']->getId(), TRUE
        );
        $calPreferences->setValueForUser(
            Calendar_Preference::NOTIFICATION_LEVEL, 
            Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_EVENT_UPDATE,
            $this->_personas['jmcblack']->getId(), TRUE
        );
        $calPreferences->setValueForUser(
            Calendar_Preference::NOTIFICATION_LEVEL, 
            Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE,
            $this->_personas['rwright']->getId(), TRUE
        );
        
        // set all languages to en
        $preferences = Tinebase_Core::getPreference('Tinebase');
        foreach ($this->_personas as $name => $account) {
            $preferences->setValueForUser(Tinebase_Preference::LOCALE, 'en', $account->getId(), TRUE);
        }
    }
}

if (PHPUnit_MAIN_METHOD == 'Calendar_Controller_EventNotificationsTests::main') {
    Calendar_Controller_EventNotificationsTests::main();
}