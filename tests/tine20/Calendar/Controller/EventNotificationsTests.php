<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
    
    /**
     * (non-PHPdoc)
     * @see tests/tine20/Calendar/Calendar_TestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();
        
        $smtpConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Config::SMTP);
        if (empty($smtpConfig)) {
             $this->markTestSkipped('No SMTP config found: this is needed to send notifications.');
        }
        
        $this->_eventController = Calendar_Controller_Event::getInstance();
        $this->_notificationController = Calendar_Controller_EventNotifications::getInstance();
        
        $this->_mailer = Tinebase_Smtp::getDefaultTransport();
        
        $this->_setupPreferences();
    }
    
    /**
     * testInvitation
     */
    public function testInvitation()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        
        $this->_flushMailer();
        $persistentEvent = $this->_eventController->create($event);
        $this->_assertMail('jsmith', NULL);
        $this->_assertMail('pwulf, sclever, jmcblack, rwright', 'invit');
        
        $this->_flushMailer();
        $persistentEvent = $this->_eventController->delete($persistentEvent);
        $this->_assertMail('jsmith', NULL);
        $this->_assertMail('pwulf, sclever, jmcblack, rwright', 'cancel');
    }
    
    /**
     * testUpdateEmpty
     */
    public function testUpdateEmpty()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persistentEvent = $this->_eventController->create($event);
        
        // no updates
        $this->_flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf, sclever, jmcblack, rwright', NULL);
    }
    
    /**
     * testUpdateChangeAttendee
     */
    public function testUpdateChangeAttendee()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('pwulf, jmcblack, rwright');
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->attendee->merge($this->_getPersonaAttendee('jsmith, sclever'));
        $persistentEvent->attendee->removeRecord(
            $persistentEvent->attendee->find('user_id', $this->_personasContacts['pwulf']->getId())
        );
        $persistentEvent->attendee->find('user_id', $this->_personasContacts['rwright']->getId())->status =
            Calendar_Model_Attender::STATUS_ACCEPTED;
        $persistentEvent->attendee->find('user_id', $this->_personasContacts['jmcblack']->getId())->status =
            Calendar_Model_Attender::STATUS_DECLINED;
            
        $this->_flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, jmcblack', NULL);
        $this->_assertMail('sclever', 'invit');
        $this->_assertMail('pwulf', 'cancel');
        $this->_assertMail('rwright', 'Attendee');
    }
    
    /**
     * testUpdateReschedule
     */
    public function testUpdateReschedule()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->summary = 'reschedule notification has precedence over normal update';
        $persistentEvent->dtstart->addHour(1);
        $persistentEvent->dtend->addHour(1);
        
        $this->_flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf', NULL);
        $this->_assertMail('sclever, jmcblack, rwright', 'reschedul');
    }
    
    /**
     * testUpdateDetails
     */
    public function testUpdateDetails()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->summary = 'detail update notification has precedence over attendee update';
        $persistentEvent->url = 'http://somedetail.com';
        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        
        $this->_flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf, sclever', NULL);
        $this->_assertMail('jmcblack, rwright', 'update');
    }
        
    /**
     * testUpdateAttendeeStatus
     */
    public function testUpdateAttendeeStatus()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        $this->_flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf, sclever, jmcblack', NULL);
        $this->_assertMail('rwright', 'decline');
    }
    
    /**
     * testOrganizerNotificationSupress
     */
    public function testOrganizerNotificationSupress()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf');
        $event->organizer = $this->_personasContacts['jsmith']->getId();
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        $this->_flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf', NULL);
    }
    
    /**
     * testOrganizerNotificationSend
     */
    public function testOrganizerNotificationSend()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf');
        $event->organizer = $this->_personasContacts['pwulf']->getId();
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        $this->_flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith', NULL);
        $this->_assertMail('pwulf', 'decline');
    }
    
    /**
     * testNotificationToNonAccounts
     */
    public function testNotificationToNonAccounts()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('pwulf');
        $event->organizer = $this->_personasContacts['pwulf']->getId();
        
        // add nonaccount attender
        $nonAccountEmail = 'externer@example.org';
        $nonAccountAttender = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family'  => 'externer',
            'email'     => $nonAccountEmail,
        )));
        $event->attendee->addRecord($this->_createAttender($nonAccountAttender->getId()));
        
        $persistentEvent = $this->_eventController->create($event);
        
        // add alarm
        $persistentEvent->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $updatedEvent = $this->_eventController->update($persistentEvent);
        
        $this->_flushMailer();

        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        $updatedEvent = $this->_eventController->update($persistentEvent);
        
        // make sure messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue();
        }
        
        // check mailer messages
        $foundNonAccountMessage = FALSE;
        $foundPWulfMessage = FALSE;
        foreach($this->_mailer->getMessages() as $message) {
            if (in_array($nonAccountEmail, $message->getRecipients())) {
                $foundNonAccountMessage = TRUE;
            }
            if (in_array($this->_personas['pwulf']->accountEmailAddress, $message->getRecipients())) {
                $foundPWulfMessage = TRUE;
            }
        }
        
        $this->assertTrue($foundNonAccountMessage, 'notification has not been sent to non-account');
        $this->assertTrue($foundPWulfMessage, 'notfication for pwulf not found');
    }
    
    public function testRecuringAlarm()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('pwulf');
        $event->organizer = $this->_personasContacts['pwulf']->getId();
        
        // lets flush mailer so next flushing ist faster!
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $this->_flushMailer();
        
        // make sure next occurence contains now
        // next occurance now+29min 
        $event->dtstart = Tinebase_DateTime::now()->subDay(1)->addMinute(28);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(30);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        
        $persistentEvent = $this->_eventController->create($event);

        // assert alarm
        $this->_flushMailer();
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $assertString = ' at ' . Tinebase_DateTime::now()->format('M j');
        $this->_assertMail('pwulf', $assertString);

        // check adjusted alarm time
        $loadedEvent = $this->_eventController->get($persistentEvent->getId());
        
        $orgiginalAlarm = $persistentEvent->alarms->getFirstRecord()->alarm_time;
        $adjustedAlarm = $loadedEvent->alarms->getFirstRecord()->alarm_time;
        $this->assertTrue($adjustedAlarm->isLater($orgiginalAlarm), 'alarmtime is not adjusted');

        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $loadedEvent->alarms->getFirstRecord()->sent_status, 'alarmtime is set to pending');
    }
    
    /**
     * flush mailer (send all remaining mails first)
     */
    protected function _flushMailer()
    {
        // make sure all messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        $this->_mailer->flush();
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
        // make sure messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(100);
        }
        
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
                $this->assertEquals('UTF-8', $mailsForPersona[0]->getCharset());
            }
        }
    }
    
    /**
     * get attendee
     * 
     * @param string $_personas
     * @return Tinebase_Record_RecordSet
     */
    protected function _getPersonaAttendee($_personas)
    {
        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        foreach (explode(',', $_personas) as $personaName) {
            $attendee->addRecord($this->_createAttender($this->_personasContacts[trim($personaName)]->getId()));
        }
        
        return $attendee;
    }
    
    /**
     * create new attender
     * 
     * @param string $_userId
     * @return Calendar_Model_Attender
     */
    protected function _createAttender($_userId)
    {
        return new Calendar_Model_Attender(array(
            'user_id'        => $_userId,
            'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
            'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
            'status_authkey' => Tinebase_Record_Abstract::generateUID(),
        ));
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
            Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_INVITE_CANCEL,
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
