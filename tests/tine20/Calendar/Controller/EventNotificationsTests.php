<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

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
     * (non-PHPdoc)
     * @see tests/tine20/Calendar/Calendar_TestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();
        
        Calendar_Controller_Event::getInstance()->sendNotifications(true);
        
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (empty($smtpConfig)) {
             $this->markTestSkipped('No SMTP config found: this is needed to send notifications.');
        }
        
        $this->_eventController = Calendar_Controller_Event::getInstance();
        $this->_notificationController = Calendar_Controller_EventNotifications::getInstance();
        
        $this->_setupPreferences();
        
        Calendar_Config::getInstance()->set(Calendar_Config::MAX_NOTIFICATION_PERIOD_FROM, /* last 10 years */ 52 * 10);
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

        Calendar_Config::getInstance()->set(Calendar_Config::MAX_NOTIFICATION_PERIOD_FROM, /* last week */ 1);
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
     * Test event creation with muted invitation
     */
    public function testMuteToogleCreation()
    {
        $event = $this->_getEvent(TRUE, /* $mute = */ 1);
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');

        self::flushMailer();
        $persistentEvent = $this->_eventController->create($event);
        $this->_assertMail('jsmith, pwulf, sclever, jmcblack, rwright', NULL);

        $this->assertEquals($event->mute, 1);
    }
    
    /**
     * Test event reschedul with muted invitation
     */
    public function testMuteToogleReschedul()
    {
        $event = $this->_getEvent(TRUE, /* $mute = */ 1);
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->summary = 'reschedule notification has precedence over normal update';
        $persistentEvent->dtstart->addHour(1);
        $persistentEvent->dtend->addHour(1);
        $persistentEvent->mute = 1;
        $this->assertEquals($persistentEvent->mute, 1);
        
        self::flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf, sclever, jmcblack, rwright', NULL);
    }
    
    /**
     * testMuteToogleUpdateAttendeeStatus
     */
    public function testMuteToogleUpdateAttendeeStatus()
    {
        $event = $this->_getEvent(TRUE, /* $mute = */ 1);
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
        $persistentEvent = $this->_eventController->create($event);
    
        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        $persistentEvent->mute = 1;
        $this->assertEquals($persistentEvent->mute, 1);
    
        self::flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf, sclever, jmcblack, rwright', NULL);
    }

    /**
     * testInvitationWithAttachment
     * 
     * @see 0008592: append event file attachments to invitation mail
     * @see 0009246: Mail address of organizer is broken in invite mails
     *
     * @group nogitlabci
     * gitlabci: RSVP=TRUE;EMAIL=pwulf@example.org not found for pwulf in: ...
     */
    public function testInvitationWithAttachment()
    {
        $event = $this->_getEvent(TRUE);
        $event->attendee = $this->_getPersonaAttendee('pwulf, sclever');
        
        $tempFileBackend = new Tinebase_TempFile();
        $tempFile = $tempFileBackend->createTempFile(dirname(dirname(dirname(__FILE__))) . '/Filemanager/files/test.txt');
        $event->attachments = array(array('tempFile' => array('id' => $tempFile->getId())));
        
        self::flushMailer();
        $persistentEvent = $this->_eventController->create($event);
        
        $messages = self::getMessages();
        
        $this->assertEquals(2, count($messages));
        $parts = $messages[0]->getParts();
        $this->assertEquals(2, count($parts));
        $fileAttachment = $parts[1];
        $this->assertEquals('text/plain; name="=?utf-8?Q?tempfile.tmp?="', $fileAttachment->type);
        
        $this->_assertMail('pwulf', 'SENT-BY="mailto:' . Tinebase_Core::getUser()->accountEmailAddress . '":mailto:', 'ics');
        $this->_assertMail('pwulf', 'RSVP=TRUE;EMAIL=' . $this->_personas['pwulf']->accountEmailAddress, 'ics');
        $this->_assertMail('pwulf', 'RSVP=FALSE;EMAIL=' . $this->_personas['sclever']->accountEmailAddress, 'ics');

        // @todo assert attachment content (this seems to not work with array mailer, maybe we need a "real" email test here)
//         $content = $fileAttachment->getDecodedContent();
//         $this->assertEquals('test file content', $content);
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
            $persistentEvent->attendee->find('user_id', $this->_getPersonasContacts('pwulf')->getId())
        );
        $persistentEvent->attendee->find('user_id', $this->_getPersonasContacts('rwright')->getId())->status =
            Calendar_Model_Attender::STATUS_ACCEPTED;
        $persistentEvent->attendee->find('user_id', $this->_getPersonasContacts('jmcblack')->getId())->status =
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
        $persistentEvent->status = Calendar_Model_Event::STATUS_TENTATIVE;
        
        self::flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf, sclever', NULL);
        $this->_assertMail('jmcblack, rwright', 'update');

        $messages = self::getMessages();
        $this->assertContains('"' . Tinebase_Translation::getTranslation('Calendar')->translate('Tentative') . '"', $messages[0]->getBodyText()->getRawContent(), 'keyfield not resolved');
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
     * testAttenderStatusUpdate
     *
     * @see 0013630: no emails are sent on external invitation reply
     */
    public function testAttenderStatusUpdate()
    {
        $event = $this->_getEvent(TRUE);
        $event->attendee = $this->_getPersonaAttendee('rwright');
        $persistentEvent = $this->_eventController->create($event);
        $rwright = $persistentEvent->attendee[0];
        $rwright->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        self::flushMailer();
        $this->_eventController->attenderStatusUpdate($persistentEvent, $rwright, $rwright->status_authkey);
        $this->_assertMail('rwright', 'accept');
    }

    /**
     * testAttenderStatusUpdateOrganizerIsAttendee
     *
     * skip dublicate notification for external organizer
     */
    public function testAttenderStatusUpdateOrganizerIsAttendee()
    {
        $event = $this->_getEvent(TRUE);
        $event->organizer = $this->_getPersonasContacts('rwright')->getId();
        $event->attendee = $this->_getPersonaAttendee('rwright');
        $persistentEvent = $this->_eventController->create($event);
        $rwright = $persistentEvent->attendee[0];
        $rwright->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        self::flushMailer();
        $this->_eventController->attenderStatusUpdate($persistentEvent, $rwright, $rwright->status_authkey);
        $this->assertEquals(1, count(self::getMessages()));
    }

    /**
     * testOrganizerNotificationSupress
     */
    public function testOrganizerNotificationSupress()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf');
        $event->organizer = $this->_getPersonasContacts('jsmith')->getId();
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        self::flushMailer();
        $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith, pwulf', NULL);
    }
    
    /**
     * testOrganizerNotificationSend
     */
    public function testOrganizerNotificationSend()
    {
        $event = $this->_getEvent(TRUE);
        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf');
        $event->organizer = $this->_getPersonasContacts('pwulf')->getId();
        $persistentEvent = $this->_eventController->create($event);
        
        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        self::flushMailer();
        $this->_eventController->update($persistentEvent);
        $this->_assertMail('jsmith', NULL);
        $this->_assertMail('pwulf', 'decline');
    }
    
    /**
     * testNotificationToNonAccounts
     */
    public function testNotificationToNonAccounts()
    {
        $persistentEvent = $this->_createEventWithExternal();

        // invitation should be send to internal and external attendee
        $this->_assertMail('pwulf,externer@example.org', 'invitation');
        
        // add alarm
        $persistentEvent->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        
        self::flushMailer();
        $this->_eventController->update($persistentEvent);
        
        // don't send alarm change to external attendee
        $this->_assertMail('externer@example.org');
        
        self::flushMailer();
        $persistentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        $this->_eventController->update($persistentEvent);
        
        $this->_assertMail('externer@example.org');
        $this->_assertMail('pwulf', 'declined');
    }

    /**
     * @param bool $externalOrganizer
     * @return Calendar_Model_Event
     */
    protected function _createEventWithExternal($externalOrganizer = false)
    {
        $event = $this->_getEvent(TRUE);
        $event->attendee = $this->_getPersonaAttendee('pwulf');

        // add nonaccount attender
        $nonAccountEmail = 'externer@example.org';
        $nonAccountAttender = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family'  => 'externer',
            'email'     => $nonAccountEmail,
        )));
        $event->attendee->addRecord($this->_createAttender($nonAccountAttender->getId()));

        if ($externalOrganizer) {
            $event->organizer = $nonAccountAttender->getId();
        } else {
            $event->organizer = $this->_getPersonasContacts('pwulf')->getId();
        }

        self::flushMailer();
        return $this->_eventController->create($event);
    }

    /**
     * testExternalInvitationContainer - container should be hidden by xprop!
     */
    public function testExternalInvitationContainer()
    {
        $this->_createEventWithExternal(true);
        $externalAttender = Calendar_Model_Attender::resolveEmailToContact([
            'email' => 'externer@example.org'
        ]);
        Calendar_Controller::getInstance()->getInvitationContainer($externalAttender);

        $containers = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), Calendar_Model_Event::class, Tinebase_Model_Grants::GRANT_EDIT);

        foreach ($containers as $container) {
            if ($container->name === 'externer@example.org') {
                self::fail('external invitation container found!');
            }
        }
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
                'container_id'  => $this->_getTestCalendar()->getId(),
                'attendee'      => $this->_getPersonaAttendee('jmcblack'),
        ));
        
        self::flushMailer();
        $persistentEvent = $this->_eventController->create($event);
        $this->_assertMail('jmcblack', 'Recurrance rule:    Daily', 'body');
        
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
        $this->_assertMail('jmcblack', 'This is an event series exception', 'body');
        $this->_assertMail('jmcblack', 'update');
        
        // reschedule instance
        self::flushMailer();
        $updatedBaseEvent = $this->_eventController->getRecurBaseEvent($recurSet[6]);
        $recurSet[6]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $recurSet[6]->dtstart->addHour(2);
        $recurSet[6]->dtend->addHour(2);
        $this->_eventController->createRecurException($recurSet[6], FALSE, FALSE); //2012-03-21
        $this->_assertMail('jmcblack', 'reschedule');
        
        // cancel thisandfuture
        // @TODO check RANGE in ics
        // @TODO add RANGE text to message
        self::flushMailer();
        $updatedBaseEvent = $this->_eventController->getRecurBaseEvent($recurSet[16]);
        $recurSet[16]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $this->_eventController->createRecurException($recurSet[16], TRUE, TRUE); //2012-03-31
        $this->_assertMail('jmcblack', 'cancel');
        
        // first instance exception (update not reschedule)
        self::flushMailer();
        $updatedBaseEvent = $this->_eventController->getRecurBaseEvent($persistentEvent);
        $updatedBaseEvent->summary = 'update first occurence';
        $this->_eventController->createRecurException($updatedBaseEvent, FALSE, FALSE); // 2012-03-14
        $this->_assertMail('jmcblack', 'has been updated');
    }
    
    public function testAttendeeAlarmSkip()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('sclever, pwulf');
        $event->organizer = $this->_getPersonasContacts('sclever')->getId();
        
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
                'user_id'   => $this->_getPersonasContacts('pwulf')->getId(),
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
        $event->organizer = $this->_getPersonasContacts('sclever')->getId();
        
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
            'user_id'   => $this->_getPersonasContacts('pwulf')->getId()
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

    public function testAlarmSkipPreference()
    {
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        
        $calPreferences = Tinebase_Core::getPreference('Calendar');
        $calPreferences->setValueForUser(
            Calendar_Preference::SEND_ALARM_NOTIFICATIONS,
            0,
            $this->_getPersona('sclever')->getId(), TRUE
        );

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

        self::flushMailer();
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $this->_assertMail('sclever', NULL);
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
        
        $this->_eventController->create($event);
        self::flushMailer();
        try {
            $scheduler = Tinebase_Core::getScheduler();
            /** @var Tinebase_Model_SchedulerTask $task */
            $task = $scheduler->getBackend()->getByProperty('Tinebase_Alarm', 'name');
            $task->config->run();
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Something strange happened and the async jobs did not complete ... maybe the test system is not configured correctly for this: ' . $e);
            static::fail($e->getMessage());
        }
        
        $assertString = ' at ' . $event->dtstart->getClone()->setTimezone(Tinebase_Core::getUserTimezone())->format('M j');
        $this->_assertMail('sclever', $assertString);
    }
    
    /**
     * testRecuringAlarm
     *
     * TODO 0012858: fix event notification tests on daylight saving boundaries
     */
    public function testRecuringAlarm()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('pwulf');
        $event->organizer = $this->_getPersonasContacts('pwulf')->getId();
        
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
        $assertString = ' at ' . $event->dtstart->getClone()->addDay(1)->setTimezone(Tinebase_Core::getUserTimezone())
                ->format('M j');
        $this->_assertMail('pwulf', $assertString);

        // check adjusted alarm time
        $loadedEvent = $this->_eventController->get($persistentEvent->getId());
        $recurid = $loadedEvent->alarms->getFirstRecord()->getOption('recurid');
        $nextAlarmEventStart = new Tinebase_DateTime(substr($recurid, -19));
        
        $this->assertTrue($nextAlarmEventStart > Tinebase_DateTime::now()->addDay(1), 'alarmtime is not adjusted: '
            . $nextAlarmEventStart->toString() . ' should be greater than '
            . Tinebase_DateTime::now()->addDay(1)->toString());
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
     *
     * TODO 0012858: fix event notification tests on daylight saving boundaries
     */
    public function testRecuringAlarmException()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('pwulf');
        $event->organizer = $this->_getPersonasContacts('pwulf')->getId();
        
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
        $assertString = ' at ' . $event->dtstart->getClone()->addDay(1)->setTimezone(Tinebase_Core::getUserTimezone())
                ->format('M j');
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
    
    /**
     * testRecuringAlarmCustomDate
     */
    public function testRecuringAlarmCustomDate()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('pwulf');
        $event->organizer = $this->_getPersonasContacts('pwulf')->getId();
        
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
        $assertString = ' at ' . $event->dtstart->getClone()->setTimezone(Tinebase_Core::getUserTimezone())
                ->format('M j');
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
            'container_id' => $this->_getTestCalendar()->getId(),
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
    
    public function testAlarmSkipDeclined()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getPersonaAttendee('sclever, pwulf');
        $event->organizer = $this->_getPersonasContacts('sclever')->getId();
        
        $event->dtstart = Tinebase_DateTime::now()->addMinute(25);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(30);
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        
        $persistentEvent = $this->_eventController->create($event);
        $sclever = Calendar_Model_Attender::getAttendee($persistentEvent->attendee, $event->attendee[0]);
        $sclever->status = Calendar_Model_Attender::STATUS_DECLINED;
        $this->_eventController->attenderStatusUpdate($persistentEvent, $sclever, $sclever->status_authkey);
        
        self::flushMailer();
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $this->_assertMail('pwulf', 'Alarm');
        $this->assertEquals(1, count(self::getMessages()));
    }
    
    /**
     * Trying to reproduce a fatal error but won't work yet
     */
    public function testAlarmWithoutOrganizer()
    {
        $calInstance = Addressbook_Controller_Contact::getInstance();
        $newContactData = array(
            'n_given'           => 'foo',
            'n_family'          => 'PHPUNIT',
            'email'             => 'foo@tine20.org',
            'tel_cell_private'  => '+49TELCELLPRIVATE',
        );
        $newContact = $calInstance->create(new Addressbook_Model_Contact($newContactData));
        
        $event = $this->_getEvent();
        $event->attendee = $this->_createAttender($newContact->getId());
        $event->organizer = $newContact->getId();
        
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $persistentEvent = $this->_eventController->create($event);
        
        $calInstance->delete(array($newContact->getId()));
        self::flushMailer();
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $this->assertEquals(0, count(self::getMessages()));
    }
    
    /**
     * testRecuringAlarmAfterSeriesEnds
     * 
     * @see 0008386: alarm is sent for recur series that is already over
     */
    public function testRecuringAlarmAfterSeriesEnds()
    {
        $this->_recurAlarmTestHelper();
    }
    
    /**
     * helper for recurring alarm tests
     * 
     * @param boolean $allFollowing
     * @param integer $alarmMinutesBefore
     */
    protected function _recurAlarmTestHelper($allFollowing = TRUE, $alarmMinutesBefore = 60)
    {
        $event = $this->_getEvent();
        
        // lets flush mailer so next flushing ist faster!
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        self::flushMailer();
        
        // make sure next occurence contains now
        $event->dtstart = Tinebase_DateTime::now()->subDay(2)->addHour(1);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(60);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => $alarmMinutesBefore
            ), TRUE)
        ));
        
        // check alarm
        $persistentEvent = $this->_eventController->create($event);
        $this->assertEquals(1, count($persistentEvent->alarms));
        $alarm = $persistentEvent->alarms->getFirstRecord();
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $alarm->sent_status);
        $persistentDtstart = clone $persistentEvent->dtstart;
        $this->assertEquals($persistentDtstart->subMinute($alarmMinutesBefore), $alarm->alarm_time, print_r($alarm->toArray(), TRUE));
        
        // delete all following
        $from = $event->dtstart;
        $until = $event->dtend->addDay(3);
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        $recurEvent = $recurSet[1]; // today
        $persistentEvent = $this->_eventController->createRecurException($recurEvent, TRUE, $allFollowing);
        
        $baseEvent = $this->_eventController->getRecurBaseEvent($persistentEvent);
        if ($allFollowing) {
            $until = $recurSet[1]->dtstart->getClone()->subSecond(1);

            $this->assertEquals('FREQ=DAILY;INTERVAL=1;UNTIL=' . $until->toString(), (string) $baseEvent->rrule, 'rrule mismatch');
            $this->assertEquals(1, count($baseEvent->alarms));
            $this->assertEquals('Nothing to send, series is over', $baseEvent->alarms->getFirstRecord()->sent_message,
                'alarm adoption failed: ' . print_r($baseEvent->alarms->getFirstRecord()->toArray(), TRUE));
        } else {
            $this->assertEquals('FREQ=DAILY;INTERVAL=1', (string) $baseEvent->rrule);
            $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $baseEvent->alarms->getFirstRecord()->sent_status);
            $this->assertEquals('', $baseEvent->alarms->getFirstRecord()->sent_message);
        }
        
        // assert no alarm
        self::flushMailer();
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $messages = self::getMessages();
        $this->assertEquals(0, count($messages), 'no alarm message should be sent: ' . print_r($messages, TRUE));
    }
    
    /**
     * testRecuringAlarmWithRecurException
     * 
     * @see 0008386: alarm is sent for recur series that is already over
     */
    public function testRecuringAlarmWithRecurException()
    {
        $this->_recurAlarmTestHelper(FALSE);
    }

    /**
     * testRecuringAlarmWithRecurException120MinutesBefore
     * 
     * @see 0008386: alarm is sent for recur series that is already over
     */
    public function testRecuringAlarmWithRecurException120MinutesBefore()
    {
        $this->_recurAlarmTestHelper(FALSE, 120);
    }

    /**
     * testRecuringAlarmWithRecurExceptionMoved
     * 
     * @see 0008386: alarm is sent for recur series that is already over
     */
    public function testRecuringAlarmWithRecurExceptionMoved()
    {
        $event = $this->_getEvent();
        
        // lets flush mailer so next flushing ist faster!
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        self::flushMailer();
        
        // make sure next occurence contains now
        $event->dtstart = Tinebase_DateTime::now()->subWeek(2)->addDay(1);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(60);
        $event->rrule = 'FREQ=WEEKLY;INTERVAL=1;WKST=MO;BYDAY=' . array_search($event->dtstart->format('w'), Calendar_Model_Rrule::$WEEKDAY_DIGIT_MAP);
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 1440
            ), TRUE)
        ));
        
        $persistentEvent = $this->_eventController->create($event);
        
        // adopt alarm time (previous alarms have been sent already)
        $alarm = $persistentEvent->alarms->getFirstRecord();
        $alarm->alarm_time->addWeek(2);
        Tinebase_Alarm::getInstance()->update($alarm);
        
        // move next occurrence
        $from = $event->dtstart;
        $until = $event->dtend->addWeek(3);
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        $recurEvent = $recurSet[1]; // tomorrow
        
        $recurEvent->dtstart->addDay(5);
        $recurEvent->dtend = clone $recurEvent->dtstart;
        $recurEvent->dtend->addMinute(60);
        $persistentEvent = $this->_eventController->createRecurException($recurEvent);
        
        $baseEvent = $this->_eventController->getRecurBaseEvent($persistentEvent);
        $alarm = $baseEvent->alarms->getFirstRecord();
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $alarm->sent_status);
        
        // assert no alarm
        sleep(1);
        self::flushMailer();
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        $messages = self::getMessages();
        $this->assertEquals(0, count($messages), 'no alarm message should be sent: ' . print_r($messages, TRUE));
    }

    /**
     * testRecuringAlarmWithThisAndFutureSplit
     * 
     * @see 0008386: alarm is sent for recur series that is already over
     */
    public function testRecuringAlarmWithThisAndFutureSplit()
    {
        $this->markTestSkipped('@see 0009816: fix failing testRecuringAlarmWithThisAndFutureSplit test');
        
        $event = $this->_getEvent();
        
        // lets flush mailer so next flushing ist faster!
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        self::flushMailer();
        
        // make sure next occurence contains now
        $event->dtstart = Tinebase_DateTime::now()->subMonth(1)->addDay(1)->subHour(2);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(60);
        $event->rrule = 'FREQ=MONTHLY;INTERVAL=1;BYMONTHDAY=' . $event->dtstart->format('d');
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 2880
            ), TRUE)
        ));
        
        $persistentEvent = $this->_eventController->create($event);
        
        // make sure, next alarm is for next month's event
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        self::flushMailer();
        
        // split THISANDFUTURE, alarm of old series should be set to SUCCESS because it no longer should be sent
        $from = $event->dtstart;
        $until = $event->dtend->addMonth(2);
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        $recurEvent = (count($recurSet) > 1) ? $recurSet[1] : $recurSet[0]; // next month
        $recurEvent->summary = 'split series';
        $newPersistentEvent = $this->_eventController->createRecurException($recurEvent, FALSE, TRUE);
        
        // check alarms
        $oldSeriesAlarm = Tinebase_Alarm::getInstance()
            ->getAlarmsOfRecord('Calendar_Model_Event', $persistentEvent->getId())
            ->getFirstRecord();
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_SUCCESS, $oldSeriesAlarm->sent_status,
            'no pending alarm should exist for old series: ' . print_r($oldSeriesAlarm->toArray(), TRUE));
    }
    
    /**
     * put an exception event created by "remind" option of alarm in iCal
     */
    public function testPutEventExceptionAlarmReminder()
    {
        $event = $this->_createRecurringCalDavEvent();
        $messages = self::getMessages();
        $this->assertEquals(1, count($messages), 'one invitation should be send to sclever');
        $this->_assertMail('sclever', 'invitation');
    
        // create alarm reminder/snooze exception
        Calendar_Controller_EventNotificationsTests::flushMailer();
        $vcalendar = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/../Import/files/apple_ical_remind_part2.ics');
        $event->put($vcalendar);
    
        // assert no reschedule mail
        $messages = Calendar_Controller_EventNotificationsTests::getMessages();
        $this->assertEquals(0, count($messages), 'no reschedule mails should be send for implicit exception');
    }
    
    /**
     * createRecurringCalDavEvent
     * 
     * @return Calendar_Frontend_WebDAV_Event
     */
    protected function _createRecurringCalDavEvent()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';
        
        self::flushMailer();
        $vcalendar = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/../Import/files/apple_ical_remind_part1.ics');
        $id = Tinebase_Record_Abstract::generateUID();
        $event = Calendar_Frontend_WebDAV_Event::create($this->_getTestCalendar(), "$id.ics", $vcalendar);
        
        return $event;
    }
    
    /**
     * testNotificationPeriodConfig
     * 
     * @see 0010048: config for notifications for past events
     */
    public function testNotificationPeriodConfig()
    {
        Calendar_Config::getInstance()->set(Calendar_Config::MAX_NOTIFICATION_PERIOD_FROM, /* last week */ 1);
        $event = $this->_createRecurringCalDavEvent();
        $messages = self::getMessages();
        $this->assertEquals(0, count($messages), 'no invitation should be send to sclever');
    }

    /**
     * testAdoptAlarmDSTBoundaryAllDayEvent
     * 
     * @see 0009820: Infinite loop in adoptAlarmTime / computeNextOccurrence (DST Boundary)
     */
    public function testAdoptAlarmDSTBoundaryAllDayEvent()
    {
        $event = $this->_getEvent();
        $event->is_all_day_event = 1;
        $event->dtstart = new Tinebase_DateTime('2014-03-03 23:00:00');
        $event->dtend = new Tinebase_DateTime('2014-03-04 22:59:59');
        $event->originator_tz = 'Europe/Berlin';
        $event->rrule = 'FREQ=DAILY';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 15,
            ), TRUE)
        ));
        
        $savedEvent = Calendar_Controller_Event::getInstance()->create($event);
        
        $alarm = $savedEvent->alarms->getFirstRecord();
        $alarm->sent_time = new Tinebase_DateTime('2014-03-29 22:46:01');
        $alarm->alarm_time = new Tinebase_DateTime('2014-03-29 22:45:00');
        $alarm->setOption('recurid', $savedEvent->uid . '-2014-03-29 23:00:00');
        Tinebase_Alarm::getInstance()->update($alarm);
        $alarm = $this->_eventController->get($savedEvent->getId())->alarms->getFirstRecord();
        
        Calendar_Controller_Event::getInstance()->adoptAlarmTime($savedEvent, $alarm, 'instance');
        
        $this->assertEquals('2014-03-30 21:45:00', $alarm->alarm_time->toString());
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
    protected function _assertMail($_personas, $_assertString = NULL, $_location = 'subject')
    {
        $messages = self::getMessages();
        
        foreach (explode(',', $_personas) as $personaName) {
            $mailsForPersona = array();
            $otherRecipients = array();
            $personaEmail = strstr($personaName, '@') ? 
                $personaName : 
                $this->_getPersona(trim($personaName))->accountEmailAddress;
            
            foreach ($messages as $message) {
                if (Tinebase_Helper::array_value(0, $message->getRecipients()) == $personaEmail) {
                    array_push($mailsForPersona, $message);
                } else {
                    array_push($otherRecipients, $message->getRecipients());
                }
            }
            
            if (! $_assertString) {
                $this->assertEquals(0, count($mailsForPersona), 'No mail should be send for '. $personaName);
            } else {
                $this->assertEquals(1, count($mailsForPersona), 'One mail should be send for '. $personaName . ' other recipients: ' . print_r($otherRecipients, true));
                $this->assertEquals('UTF-8', $mailsForPersona[0]->getCharset());
                
                switch ($_location) {
                    case 'subject':
                        $subject = $mailsForPersona[0]->getSubject();
                        $this->assertTrue(FALSE !== strpos($subject, $_assertString), 'Mail subject for ' . $personaName . ' should contain "' . $_assertString . '" but '. $subject . ' is given');
                        break;
                        
                    case 'body':
                        $bodyPart = $mailsForPersona[0]->getBodyText(FALSE);
                        
                        // so odd!
                        $s = fopen('php://temp','r+');
                        fputs($s, $bodyPart->getContent());
                        rewind($s);
                        $bodyPartStream = new Zend_Mime_Part($s);
                        $bodyPartStream->encoding = $bodyPart->encoding;
                        $bodyText = $bodyPartStream->getDecodedContent();
                        
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                            . ' body text: ' . $bodyText);
                        
                        $this->assertContains($_assertString, $bodyText);
                        break;

                    case 'ics':
                        $parts = $mailsForPersona[0]->getParts();
                        $vcalendarPart = $parts[0];
                        $vcalendar = quoted_printable_decode($vcalendarPart->getContent());

                        $this->assertContains($_assertString, str_replace("\r\n ", '', $vcalendar), $_assertString . ' not found for ' . $personaName . " in:\n" . $vcalendar);

                        break;
                    default:
                        throw new Exception('no such location '. $_location);
                        break;
                }
                
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
            $attendee->addRecord($this->_createAttender($this->_getPersonasContacts(trim($personaName))->getId()));
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
            $this->_getPersona('jsmith')->getId(), TRUE
        );
        $calPreferences->setValueForUser(
            Calendar_Preference::NOTIFICATION_LEVEL, 
            Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_INVITE_CANCEL,
            $this->_getPersona('pwulf')->getId(), TRUE
        );
        $calPreferences->setValueForUser(
            Calendar_Preference::NOTIFICATION_LEVEL, 
            Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_EVENT_RESCHEDULE,
            $this->_getPersona('sclever')->getId(), TRUE
        );
        $calPreferences->setValueForUser(
            Calendar_Preference::NOTIFICATION_LEVEL, 
            Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_EVENT_UPDATE,
            $this->_getPersona('jmcblack')->getId(), TRUE
        );
        $calPreferences->setValueForUser(
            Calendar_Preference::NOTIFICATION_LEVEL, 
            Calendar_Controller_EventNotifications::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE,
            $this->_getPersona('rwright')->getId(), TRUE
        );
        
        // set all languages to en
        $preferences = Tinebase_Core::getPreference('Tinebase');
        foreach ($this->_getPersonas() as $name => $account) {
            $preferences->setValueForUser(Tinebase_Preference::LOCALE, 'en', $account->getId(), TRUE);
        }
    }
    
    /**
     * testResourceNotification
     * 
     * checks if notification mail is sent to configured mail address of a resource
     * 
     * @see 0009954: resource manager and email handling
     *
     * @param boolean $suppress_notification
     */
    public function testResourceNotification($suppress_notification = false)
    {
        // create resource with email address of unittest user
        $resource = $this->_getResource();
        $resource->email = $this->_GetPersonasContacts('pwulf')->email;
        $resource->suppress_notification = $suppress_notification;
        $persistentResource = Calendar_Controller_Resource::getInstance()->create($resource);
        
        // create event with this resource as attender
        $event = $this->_getEvent(/* now = */ true);
        $event->attendee->addRecord($this->_createAttender($persistentResource->getId(), Calendar_Model_Attender::USERTYPE_RESOURCE));

        self::flushMailer();
        $persistentEvent = $this->_eventController->create($event);
        
        $this->assertEquals(3, count($persistentEvent->attendee));

        $messages = self::getMessages();

        if ($suppress_notification) {
            $this->assertEquals(1, count($messages), 'one mail should be send to attender (invite sclever) ' . print_r($messages, true));
        } else {
            $this->assertEquals(2, count($messages), 'two mails should be send (resource=pwulf + attender invite=sclever) '
                . print_r($messages, true));
        }

        // assert user agent
        // @see 0011498: set user agent header for notification messages
        $headers = $messages[0]->getHeaders();
        $this->assertEquals(Tinebase_Core::getTineUserAgent('Notification Service'), $headers['User-Agent'][0]);
    }

    /**
     * Enable by a preference which sends mails to every user who got permissions to edit the resource
     */
    public function testResourceNotificationForGrantedUsers($userIsAttendee = true, $suppress_notification = false)
    {
        // Enable feature, disabled by default!
        Calendar_Config::getInstance()->set(Calendar_Config::RESOURCE_MAIL_FOR_EDITORS, true);

        $resource = $this->_getResource();
        $resource->email = Tinebase_Core::getUser()->accountEmailAddress;
        $resource->suppress_notification = $suppress_notification;
        $persistentResource = Calendar_Controller_Resource::getInstance()->create($resource);

        $event = $this->_getEvent(/*now = */ true);
        $event->attendee->addRecord($this->_createAttender($persistentResource->getId(), Calendar_Model_Attender::USERTYPE_RESOURCE));

        if (! $userIsAttendee) {
            // remove organizer attendee
            foreach ($event->attendee as $idx => $attender) {
                if ($attender->user_id === $event->organizer) {
                    $event->attendee->removeRecord($attender);
                }
            }
        }

        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($resource->container_id, true);

        $newGrants = [
                'account_id' => $this->_personas['sclever']->getId(),
                'account_type' => 'user',
                Calendar_Model_ResourceGrants::RESOURCE_READ => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true,
                Calendar_Model_ResourceGrants::RESOURCE_EDIT => true,
                Calendar_Model_ResourceGrants::EVENTS_EDIT => true,
        ];

        Tinebase_Container::getInstance()->setGrants($resource->container_id,
            new Tinebase_Record_RecordSet(Calendar_Model_ResourceGrants::class, array_merge([$newGrants],
                $grants->toArray())), true, false);

        self::flushMailer();

        $persistentEvent = $this->_eventController->create($event);

        $messages = self::getMessages();

        Tinebase_Container::getInstance()->setGrants($resource->container_id, $grants, true, false);

        if ($suppress_notification) {
            $this->assertEquals(1, count($messages), 'one mail should be send to current user (attender)');
        } else {
            $this->assertEquals(4, count($messages), 'four mails should be send to current user (resource + attender + everybody who is allowed to edit this resource)');
            $this->assertEquals(count($event->attendee), count($persistentEvent->attendee));
            $this->assertContains('Resource "' . $persistentResource->name . '" was booked', print_r($messages, true));
            $this->assertContains('Meeting Room (Required, No response)', print_r($messages, true));
        }
    }

    /**
     * @see 0011272: ressource invitation: organizer receives no mail if he is no attendee
     */
    public function testResourceNotificationForNonAttendeeOrganizer()
    {
        $this->testResourceNotificationForGrantedUsers(/* $userIsAttendee = */ false);
    }

    /**
     * testNotificationForNonAttendeeOrganizer
     */
    public function testNotificationForNonAttendeeOrganizer()
    {
        $jmcblack =  $this->_getPersona('jmcblack');
        $event = $this->_getEvent(/*now = */ true);
        
        // remove organizer attendee
        foreach ($event->attendee as $idx => $attender) {
            if ($attender->user_id === $event->organizer) {
                $event->attendee->removeRecord($attender);
            }
        }
       
        // Switch organizer. The current user would not get the mail because he changes the status => own changes => no mail 
        $event->organizer = $jmcblack->contact_id;

        self::flushMailer();

        $persistentEvent = $this->_eventController->create($event);

        $messages = self::getMessages();

        $this->assertEquals(count($event->attendee), count($persistentEvent->attendee));
        $this->assertEquals(1, count($messages),
            'one mail (Invitation) should be send to sclever. Event: '
            . print_r($persistentEvent->toArray(), true) . ' Messages: '
            . print_r($messages, true)
        );

        $persistentEvent->attendee[0]->status = Calendar_Model_Attender::STATUS_DECLINED;

        self::flushMailer();
        $updatedEvent = $this->_eventController->update($persistentEvent);
        
        // One mail is sent to organizer. Sclever will not get one because it is her status that changed => send level not reached
        $this->_assertMail('jmcblack', 'decline');
    }

    /**
     * testResourceNotificationMuteForEditors
     *
     * @see 0011312: Make resource notification handling and default status configurable
     */
    public function testResourceNotificationMuteForEditors()
    {
        Calendar_Config::getInstance()->set(Calendar_Config::RESOURCE_MAIL_FOR_EDITORS, true);
        $this->testResourceNotification(/* $suppress_notification = */ true);
        $this->testResourceNotificationForGrantedUsers(/* $userIsAttendee = */ false, /* $suppress_notification = */ true);
    }

    /**
     * testResourceNotificationMute without editors config
     *
     */
    public function testResourceNotificationMute()
    {
        Calendar_Config::getInstance()->set(Calendar_Config::RESOURCE_MAIL_FOR_EDITORS, false);
        $this->testResourceNotification(/* $suppress_notification = */ true);
        $this->testResourceNotificationForGrantedUsers(/* $userIsAttendee = */ false, /* $suppress_notification = */ true);
    }
    
    /**
     * testGroupInvitation
     */
    public function testGroupInvitation()
    {
        $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        
        $event = $this->_getEvent(TRUE);
        
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
                'user_id'   => $defaultUserGroup->getId(),
                'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        ));
        
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
     * funktion for NotificationsTest
     * @param $sendTentative '0','1'
     * @param $attendee @boolean
     */
    public function _tentativeNotification($sendTentative, $attendee)
    {
        $tentConf = Calendar_Config::getInstance()->{Calendar_Config::TENTATIVE_NOTIFICATIONS};
        $oldValue = $tentConf->{Calendar_Config::TENTATIVE_NOTIFICATIONS_ENABLED};
        $tentConf->{Calendar_Config::TENTATIVE_NOTIFICATIONS_ENABLED} = true;
        Tinebase_Core::getPreference('Calendar')->setValueForUser(Calendar_Preference::SEND_NOTIFICATION_FOR_TENTATIVE, $sendTentative,
            Tinebase_FullUser::getInstance()->getFullUserByLoginName('sclever')->getId());
        try {
            $event = $this->_getEvent(true);
            if(!$attendee)
            {
                $event->attendee = null;
            }
            $event->dtstart->addHour(15);
            $event->dtend->addHour(15);
            $event->status = Calendar_Model_Event::STATUS_TENTATIVE;
            $event->organizer = $this->_getPersonasContacts('sclever')->getId();
            $this->_eventController->create($event);

            self::flushMailer();
            $this->_eventController->sendTentativeNotifications();
            if($sendTentative) {
                $this->_assertMail('sclever', 'Tentative');
            } else
            {
                $this->_assertMail('sclever', '');
            }
        } finally {
            $tentConf->{Calendar_Config::TENTATIVE_NOTIFICATIONS_ENABLED} = $oldValue;
        }
    }

    /**
     * testSendTentativeNotifications
     */
    public function testSendTentativeNotifications()
    {
        $this->_tentativeNotification('0',true);
    }

    /**
     * testSendTentativeNotificationsNoAttenders
     */
    public function testSendTentativeNotificationsNoAttenders()
    {
        $this->_tentativeNotification('0',false);
    }

    /**
     * testSendNotificationForTentative
     */
    public function testSendNotificationForTentative()
    {
        $this->_tentativeNotification('1',false);
    }

    /**
     * testSendNotificationForNoTentative
     */
    public function testSendNotificationForNoTentative()
    {
        $this->_tentativeNotification('1',true);
    }
}
