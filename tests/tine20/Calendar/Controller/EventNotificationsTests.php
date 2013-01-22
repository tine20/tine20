<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected static $_mailer = NULL;
    
    /**
     * @var Tinebase_Model_Container
     */
    protected $_testCalendar;
    
   /**
    * email test class
    *
    * @var Felamimail_Controller_MessageTest
    */
    protected $_emailTestClass;
    
    /**
     * (non-PHPdoc)
     * @see tests/tine20/Calendar/Calendar_TestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();
        
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (empty($smtpConfig)) {
             $this->markTestSkipped('No SMTP config found: this is needed to send notifications.');
        }
        
        $this->_eventController = Calendar_Controller_Event::getInstance();
        $this->_notificationController = Calendar_Controller_EventNotifications::getInstance();
        
        $this->_setupPreferences();
    }
    
    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    public function tearDown()
    {
        parent::tearDown();
        
        if ($this->_emailTestClass instanceof Felamimail_Controller_MessageTest) {
            $this->_emailTestClass->tearDown();
        }
    }
    
    /**
     * testInvitation
     */
    public function testInvitation()
    {
        $event = $this->_getEvent(TRUE);
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        
        self::flushMailer();
        $persistentEvent = $this->_eventController->create($event);
        $this->_assertMail('jsmith', NULL);
        $this->_assertMail('pwulf, sclever, jmcblack, rwright', 'invit');
        
        self::flushMailer();
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
        self::flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf, sclever, jmcblack, rwright', NULL);
    }
    
    /**
     * testUpdateChangeAttendee
     */
    public function testUpdateChangeAttendee()
    {
        $event = $this->_getEvent(TRUE);
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
            
        self::flushMailer();
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
        $event = $this->_getEvent(TRUE);
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->summary = 'reschedule notification has precedence over normal update';
        $persistentEvent->dtstart->addHour(1);
        $persistentEvent->dtend->addHour(1);
        
        self::flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf', NULL);
        $this->_assertMail('sclever, jmcblack, rwright', 'reschedul');
    }
    
    /**
     * testUpdateDetails
     */
    public function testUpdateDetails()
    {
        $event = $this->_getEvent(TRUE);
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->summary = 'detail update notification has precedence over attendee update';
        $persistentEvent->url = 'http://somedetail.com';
        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        
        self::flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf, sclever', NULL);
        $this->_assertMail('jmcblack, rwright', 'update');
    }
        
    /**
     * testUpdateAttendeeStatus
     */
    public function testUpdateAttendeeStatus()
    {
        $event = $this->_getEvent(TRUE);
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        self::flushMailer();
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
        
        self::flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf', NULL);
    }
    
    /**
     * testOrganizerNotificationSend
     */
    public function testOrganizerNotificationSend()
    {
        $event = $this->_getEvent(TRUE);
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf');
        $event->organizer = $this->_personasContacts['pwulf']->getId();
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        self::flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith', NULL);
        $this->_assertMail('pwulf', 'decline');
    }
    
    /**
     * testNotificationToNonAccounts
     */
    public function testNotificationToNonAccounts()
    {
        $event = $this->_getEvent(TRUE);
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
        
        self::flushMailer();

        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        $updatedEvent = $this->_eventController->update($persistentEvent);
        
        // make sure messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue();
        }
        
        // check mailer messages
        $foundNonAccountMessage = FALSE;
        $foundPWulfMessage = FALSE;
        foreach(self::getMailer()->getMessages() as $message) {
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
    
    /**
     * testRecuringExceptions
     */
    public function testRecuringExceptions()
    {
        $from = new Tinebase_DateTime('2012-03-01 00:00:00');
        $until = new Tinebase_DateTime('2012-03-31 23:59:59');
        
        $event = new Calendar_Model_Event(array(
                'summary'       => 'Some Daily Event',
                'dtstart'       => '2012-03-14 09:00:00',
                'dtend'         => '2012-03-14 10:00:00',
                'rrule'         => 'FREQ=DAILY;INTERVAL=1',
                'container_id'  => $this->_testCalendar->getId(),
                'attendee'      => $this->_getPersonaAttendee('jmcblack'),
        ));
        
        $persistentEvent = $this->_eventController->create($event);
        //$persistentSClever = Calendar_Model_Attender::getAttendee($persistentEvent->attendee, $event->attendee[1]);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        
        // cancel instance
        self::flushMailer();
        $this->_eventController->createRecurException($recurSet[4], TRUE, FALSE); //2012-03-19
        $this->_assertMail('jmcblack', 'cancel');
        
        // update instance
        self::flushMailer();
        $updatedBaseEvent = $this->_eventController->getRecurBaseEvent($recurSet[5]);
        $recurSet[5]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $recurSet[5]->summary = 'exceptional summary';
        $this->_eventController->createRecurException($recurSet[5], FALSE, FALSE); //2012-03-20
        $this->_assertMail('jmcblack', 'update');
        
        // reschedule instance
        self::flushMailer();
        $updatedBaseEvent = $this->_eventController->getRecurBaseEvent($recurSet[6]);
        $recurSet[6]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $recurSet[6]->dtstart->addHour(2);
        $recurSet[6]->dtend->addHour(2);
        $this->_eventController->createRecurException($recurSet[6], FALSE, FALSE); //2012-03-21
        $this->_assertMail('jmcblack', 'reschedule');
        
        // cancle thisandfuture
        // @TODO check RANGE in ics
        // @TODO add RANGE text to message
        self::flushMailer();
        $updatedBaseEvent = $this->_eventController->getRecurBaseEvent($recurSet[16]);
        $recurSet[16]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $this->_eventController->createRecurException($recurSet[16], TRUE, TRUE); //2012-03-31
        $this->_assertMail('jmcblack', 'cancel');
        
        // update thisandfuture
        
        // reschedule thisandfuture
        
        
    }
    public function testAttendeeAlarmSkip()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('sclever, pwulf');
        $event->organizer = $this->_personasContacts['sclever']->getId();
        
        $event->dtstart = Tinebase_DateTime::now()->addMinute(25);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(30);
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        
        // pwulf skips alarm
        $event->alarms->setOption('skip', array(
            array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $this->_personasContacts['pwulf']->getId(),
            )
        ));
        
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $persistentEvent = $this->_eventController->create($event);
        self::flushMailer();
        
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $this->_assertMail('sclever', 'Alarm for event');
        $this->_assertMail('pwulf');
    }
    
    public function testAttendeeAlarmOnly()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('sclever, pwulf');
        $event->organizer = $this->_personasContacts['sclever']->getId();
        
        $event->dtstart = Tinebase_DateTime::now()->addMinute(25);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(30);
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $event->alarms->setOption('attendee', array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_personasContacts['pwulf']->getId()
        ));
        
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $persistentEvent = $this->_eventController->create($event);
        self::flushMailer();
        
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $this->_assertMail('pwulf', 'Alarm for event');
        $this->_assertMail('sclever');
        
    }
    
    public function testAlarm()
    {
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        
        $event = $this->_getEvent();
        $event->dtstart = Tinebase_DateTime::now()->addMinute(15);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(30);
        $event->attendee = $this->_getAttendee();
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                    'minutes_before' => 30
            ), TRUE)
        ));
        
        $persistentEvent = $this->_eventController->create($event);
        Calendar_Model_Attender::getOwnAttender($persistentEvent->attendee)->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        // hack to get declined attendee
        $this->_eventController->sendNotifications(FALSE);
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_eventController->sendNotifications(TRUE);
        
        self::flushMailer();
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $this->_assertMail('sclever', 'Alarm');
        $this->assertEquals(1, count(self::getMessages()));
    }
    
    /**
     * CalDAV/Custom can have alarms with odd times
     */
    public function testAlarmRoundMinutes()
    {
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        
        $event = $this->_getEvent();
        $event->dtstart = Tinebase_DateTime::now()->addMinute(15);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(30);
        $event->attendee = $this->_getAttendee();
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                    'minutes_before' => 12.1
            ), TRUE)
        ));
        
        $persistentEvent = $this->_eventController->create($event);
        
        $this->assertEquals(12, $persistentEvent->alarms->getFirstRecord()->getOption('minutes_before'));
    }
    
    public function testSkipPastAlarm()
    {
        $event = $this->_getEvent();
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                    'minutes_before' => 30
            ), TRUE)
        ));
        
        $persistentEvent = $this->_eventController->create($event);
        self::flushMailer();
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $this->_assertMail('sclever');
    }
    
    /**
     * testParallelAlarmTrigger
     * 
     * @see 0004878: improve asyncJob fencing
     */
    public function testParallelAlarmTrigger()
    {
        $this->_testNeedsTransaction();
        
        try {
            $this->_emailTestClass = new Felamimail_Controller_MessageTest();
            $this->_emailTestClass->setup();
        } catch (Exception $e) {
            $this->markTestIncomplete('email not available.');
        }
        
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        self::flushMailer();
        $this->_getAlarmMails(TRUE);
        
        $event = $this->_getEvent();
        $event->dtstart = Tinebase_DateTime::now()->addMinute(15);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(30);
        $event->attendee = $this->_getAttendee();
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                    'minutes_before' => 30
            ), TRUE)
        ));
        
        $persistentEvent = $this->_eventController->create($event);
        try {
            Tinebase_AsyncJobTest::triggerAsyncEvents();
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Something strange happened and the async jobs did not complete ... maybe the test system is not configured correctly for this: ' . $e);
            $this->markTestIncomplete($e->getMessage());
        }
        
        $result = $this->_getAlarmMails(TRUE);
        $this->assertEquals(1, count($result), 'expected exactly 1 alarm mail, got: ' . print_r($result->toArray(), TRUE));
    }
    
    /**
     * testRecuringAlarm
     */
    public function testRecuringAlarm()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('pwulf');
        $event->organizer = $this->_personasContacts['pwulf']->getId();
        
        // lets flush mailer so next flushing ist faster!
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        self::flushMailer();
        
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
        self::flushMailer();
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $assertString = ' at ' . Tinebase_DateTime::now()->format('M j');
        $this->_assertMail('pwulf', $assertString);

        // check adjusted alarm time
        $loadedEvent = $this->_eventController->get($persistentEvent->getId());
        $recurid = $loadedEvent->alarms->getFirstRecord()->getOption('recurid');
        $nextAlarmEventStart = new Tinebase_DateTime(substr($recurid, -19));
        
        $this->assertTrue($nextAlarmEventStart > Tinebase_DateTime::now()->addDay(1), 'alarmtime is not adjusted');
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $loadedEvent->alarms->getFirstRecord()->sent_status, 'alarmtime is set to pending');
        
        // update series @see #7430: Calendar sends too much alarms for recurring events
        $this->_eventController->update($loadedEvent);
        $recurid = $loadedEvent->alarms->getFirstRecord()->getOption('recurid');
        $nextAlarmEventStart = new Tinebase_DateTime(substr($recurid, -19));
        
        $this->assertTrue($nextAlarmEventStart > Tinebase_DateTime::now()->addDay(1), 'alarmtime is wrong');
    }
    
    /**
     * if an event with an alarm gets an exception instance, also the alarm gets an exception instance
     * @see #6328
     */
    public function testRecuringAlarmException()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('pwulf');
        $event->organizer = $this->_personasContacts['pwulf']->getId();
        
        $event->dtstart = Tinebase_DateTime::now()->subDay(1)->addMinute(15);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(30);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
                new Tinebase_Model_Alarm(array(
                        'minutes_before' => 30
                ), TRUE)
        ));
        
        $persistentEvent = $this->_eventController->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $persistentEvent->dtstart, Tinebase_DateTime::now()->addDay(1));
        $exceptionEvent = $this->_eventController->createRecurException($recurSet->getFirstRecord());
        
        // assert one alarm only
        self::flushMailer();
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $assertString = ' at ' . Tinebase_DateTime::now()->format('M j');
        $this->_assertMail('pwulf', $assertString);
        
        // check series
        $loadedEvent = $this->_eventController->get($persistentEvent->getId());
        $recurid = $loadedEvent->alarms->getFirstRecord()->getOption('recurid');
        $nextAlarmEventStart = new Tinebase_DateTime(substr($recurid, -19));
        
        $this->assertTrue($nextAlarmEventStart > Tinebase_DateTime::now(), 'alarmtime of series is not adjusted');
        
        // check exception
        $recurid = $exceptionEvent->alarms->getFirstRecord()->getOption('recurid');
        $nextAlarmEventStart = new Tinebase_DateTime(substr($recurid, -19));
        
        $this->assertTrue($nextAlarmEventStart < Tinebase_DateTime::now()->addHour(1), 'alarmtime of exception is not adjusted');
        
        // update exception @see #7430: Calendar sends too much alarms for recurring events
        $exceptionEvent = $this->_eventController->update($exceptionEvent);
        $recurid = $exceptionEvent->alarms->getFirstRecord()->getOption('recurid');
        $nextAlarmEventStart = new Tinebase_DateTime(substr($recurid, -19));
        
        $this->assertTrue($nextAlarmEventStart < Tinebase_DateTime::now()->addHour(1), 'alarmtime of exception is wrong');
    }
    
    public function testRecuringAlarmCustomDate()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('pwulf');
        $event->organizer = $this->_personasContacts['pwulf']->getId();
        
        $event->dtstart = Tinebase_DateTime::now()->addWeek(1)->addMinute(15);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(30);
        $event->rrule = 'FREQ=YEARLY;INTERVAL=1;BYDAY=2TH;BYMONTH=12';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => Tinebase_Model_Alarm::OPTION_CUSTOM,
                // NOTE: user means one week and 30 mins before
                'alarm_time'     => Tinebase_DateTime::now()->subMinute(15)
            ), TRUE)
        ));
        
        $persistentEvent = $this->_eventController->create($event);
        
        // assert one alarm only
        self::flushMailer();
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $assertString = ' at ' . Tinebase_DateTime::now()->addWeek(1)->format('M j');
        $this->_assertMail('pwulf', $assertString);
        
        // check adjusted alarm time
        $loadedEvent = $this->_eventController->get($persistentEvent->getId());
        $recurid = $loadedEvent->alarms->getFirstRecord()->getOption('recurid');
        $nextAlarmEventStart = new Tinebase_DateTime(substr($recurid, -19));
        
        $this->assertTrue($nextAlarmEventStart > Tinebase_DateTime::now(), 'alarmtime of series is not adjusted');
    }
    
    /**
     * test alarm inspection from 24.03.2012 -> 25.03.2012
     */
    public function testAdoptAlarmDSTBoundary()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $persistentEvent = $this->_eventController->create($event);
        
        // prepare alarm for last non DST instance
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $from = new Tinebase_DateTime('2012-03-24 00:00:00');
        $until = new Tinebase_DateTime('2012-03-24 23:59:59');
        $recurSet =Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        
        $alarm = $persistentEvent->alarms->getFirstRecord();
        $alarm->setOption('recurid', $recurSet[0]->recurid);
        Tinebase_Alarm::getInstance()->update($alarm);
        
        $loadedBaseEvent = $this->_eventController->get($persistentEvent->getId());
        $alarm = $loadedBaseEvent->alarms->getFirstRecord();
        $this->assertEquals('2012-03-24', substr($alarm->getOption('recurid'), -19, -9), 'precondition failed');
        
        // adopt alarm
        $this->_eventController->adoptAlarmTime($loadedBaseEvent, $alarm, 'instance');
        $this->assertEquals('2012-03-25', substr($alarm->getOption('recurid'), -19, -9), 'alarm adoption failed');
    }
    
    /**
     * test alarm inspection from 24.03.2012 -> 25.03.2012
     */
    public function testAdoptAlarmDSTBoundaryWithSkipping()
    {
        $event = new Calendar_Model_Event(array(
            'summary'      => 'Cleanup',
            'dtstart'      => '2012-01-31 07:30:00',
            'dtend'        => '2012-01-31 10:30:00',
            'container_id' => $this->_testCalendar->getId(),
            'uid'          => Calendar_Model_Event::generateUID(),
            'rrule'        => 'FREQ=WEEKLY;INTERVAL=1;WKST=MO;BYDAY=TU',
            'originator_tz'=> 'Europe/Berlin',
        ));
        
        $alarm = new Tinebase_Model_Alarm(array(
            'model'        => 'Calendar_Model_Event',
            'alarm_time'   => '2012-03-26 06:30:00',
            'minutes_before' => 1440,
            'options'      => '{"minutes_before":1440,"recurid":"a7c55ce09cea9aec4ac37d9d72789183b12cad7c-2012-03-27 06:30:00","custom":false}',
        ));
        
        $this->_eventController->adoptAlarmTime($event, $alarm, 'instance');
        
        $this->assertEquals('2012-04-02 06:30:00', $alarm->alarm_time->toString());
    }
    
    /**
     * get test alarm emails
     * 
     * @param boolean $deleteThem
     * @return Tinebase_Record_RecordSet
     */
    protected function _getAlarmMails($deleteThem = FALSE)
    {
        // search and assert alarm mail
        $folder = $this->_emailTestClass->getFolder('INBOX');
        $folder = Felamimail_Controller_Cache_Message::getInstance()->updateCache($folder, 10, 1);
        $i = 0;
        while ($folder->cache_status != Felamimail_Model_Folder::CACHE_STATUS_COMPLETE && $i < 10) {
            $folder = Felamimail_Controller_Cache_Message::getInstance()->updateCache($folder, 10);
            $i++;
        }
        $account = Felamimail_Controller_Account::getInstance()->search()->getFirstRecord();
        $filter = new Felamimail_Model_MessageFilter(array(
            array('field' => 'folder_id',  'operator' => 'equals',     'value' => $folder->getId()),
            array('field' => 'account_id', 'operator' => 'equals',     'value' => $account->getId()),
            array('field' => 'subject',    'operator' => 'startswith', 'value' => 'Alarm for event "Wakeup" at'),
        ));
        
        $result = Felamimail_Controller_Message::getInstance()->search($filter);
        
        if ($deleteThem) {
            Felamimail_Controller_Message_Move::getInstance()->moveMessages($filter, Felamimail_Model_Folder::FOLDER_TRASH);
        }
        
        return $result;
    }
    
    public static function getMessages()
    {
        // make sure messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(100);
        }
        
        return self::getMailer()->getMessages();
    }
    
    public static function getMailer()
    {
        if (! self::$_mailer) {
            self::$_mailer = Tinebase_Smtp::getDefaultTransport();
        }
        
        return self::$_mailer;
    }
    
    /**
     * flush mailer (send all remaining mails first)
     */
    public static function flushMailer()
    {
        // make sure all messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        self::getMailer()->flush();
    }
    
    /**
     * checks if mail for persona got send
     * 
     * @param string $_personas
     * @param string $_assertString
     * @return void
     * 
     * @see #6800: add message-id to notification mails
     */
    protected function _assertMail($_personas, $_assertString = NULL)
    {
        $messages = self::getMessages();
        
        foreach (explode(',', $_personas) as $personaName) {
            $mailsForPersona = array();
            $personaEmail = $this->_personas[trim($personaName)]->accountEmailAddress;
            
            foreach($messages as $message) {
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
                
                $headers = $mailsForPersona[0]->getHeaders();
                $this->assertTrue(isset($headers['Message-Id']), 'message-id header not found');
                $this->assertContains('@' . php_uname('n'), $headers['Message-Id'][0], 'hostname not in message-id');
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
