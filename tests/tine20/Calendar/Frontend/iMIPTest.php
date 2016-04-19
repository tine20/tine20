<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        add test testOrganizerSendBy
 * @todo extend Calendar_TestCase
 */

/**
 * Test class for Calendar_Frontend_iMIP
 */
class Calendar_Frontend_iMIPTest extends TestCase
{
    /**
     * event ids that should be deleted in tearDown
     * 
     * @var unknown_type
     */
    protected $_eventIdsToDelete = array();
    
    /**
     * iMIP frontent to be tested
     * 
     * @var Calendar_Frontend_iMIP
     */
    protected $_iMIPFrontend = NULL;
    
    /**
     * iMIP frontent to be tested
     * 
     * @var Calendar_Frontend_iMIPMock
     */
    protected $_iMIPFrontendMock = NULL;
    
    /**
    * email test class
    *
    * @var Felamimail_Controller_MessageTest
    */
    protected $_emailTestClass;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            // account email addresses are empty with AD backend
            $this->markTestSkipped('skipped for ad backend');
        }

        Calendar_Controller_Event::getInstance()->sendNotifications(true);
        
        Calendar_Config::getInstance()->set(Calendar_Config::DISABLE_EXTERNAL_IMIP, false);
        
        $this->_iMIPFrontend = new Calendar_Frontend_iMIP();
        $this->_iMIPFrontendMock = new Calendar_Frontend_iMIPMock();
        
        try {
            $this->_emailTestClass = new Felamimail_Controller_MessageTest();
            $this->_emailTestClass->setup();
        } catch (Exception $e) {
            // do nothing
        }
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Calendar_Controller_Event::getInstance()->sendNotifications(false);
        
        if (! empty($this->_eventIdsToDelete)) {
            $this->_deleteEvents(TRUE);
        }
        
        if ($this->_emailTestClass instanceof Felamimail_Controller_MessageTest) {
            $this->_emailTestClass->tearDown();
        }
    }
    
    /**
     * testExternalInvitationRequestAutoProcess
     */
    public function testExternalInvitationRequestAutoProcess()
    {
        $ics = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/files/invitation_request_external.ics' );
        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'REQUEST',
            'originator'     => 'l.kneschke@caldav.org',
        ));
        
        $this->_iMIPFrontend->autoProcess($iMIP);
        $prepared = $this->_iMIPFrontend->prepareComponent($iMIP);
        
        $this->assertEmpty($prepared->existing_event, 'there should be no existing event');
        $this->assertEmpty($prepared->preconditions, 'no preconditions should be raised');
        $this->assertEquals(5, count($prepared->event->attendee));
        $this->assertEquals('test mit extern', $prepared->event->summary);
        
        return $iMIP;
    }

    /**
     * testSearchSharedCalendarsForExternalEvents
     *
     * @see 0011024: don't show external imip events in shared calendars
     */
    public function testSearchSharedCalendarsForExternalEvents()
    {
        $iMIP = $this->testExternalInvitationRequestAutoProcess();
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        $this->_eventIdsToDelete[] = $iMIP->event->getId();

        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => '/shared')
        ));
        $eventsInShared = Calendar_Controller_Event::getInstance()->search($filter);

        $this->assertFalse(in_array($iMIP->event->getId(), $eventsInShared->getArrayOfIds()),
            'found event in shared calendar: ' . print_r($iMIP->event->toArray(), true));
    }

    /**
    * testSupportedPrecondition
    */
    public function testUnsupportedPrecondition()
    {
        $iMIP = $this->_getiMIP('PUBLISH');
            
        $prepared = $this->_iMIPFrontend->prepareComponent($iMIP);
    
        $this->assertEquals(1, count($prepared->preconditions));
        $this->assertEquals('processing published events is not supported yet', $prepared->preconditions[Calendar_Model_iMIP::PRECONDITION_SUPPORTED][0]['message']);
        $this->assertFalse($prepared->preconditions[Calendar_Model_iMIP::PRECONDITION_SUPPORTED][0]['check']);
    }
    
    /**
     * get iMIP record from internal event
     * 
     * @param string $_method
     * @param boolean $_addEventToiMIP
     * @return Calendar_Model_iMIP
     */
    protected function _getiMIP($_method, $_addEventToiMIP = FALSE, $_testEmptyMethod = FALSE)
    {
        $email = $this->_getEmailAddress();
        
        $event = $this->_getEvent();
        $event = Calendar_Controller_Event::getInstance()->create($event);
        $this->_eventIdsToDelete[] = $event->getId();
        
        if ($_method == 'REPLY') {
            $personas = Zend_Registry::get('personas');
            $sclever = $personas['sclever'];
            
            $scleverAttendee = $event->attendee
                ->filter('status', Calendar_Model_Attender::STATUS_NEEDSACTION)
                ->getFirstRecord();
            
            $scleverAttendee->status = Calendar_Model_Attender::STATUS_ACCEPTED;
            Calendar_Controller_Event::getInstance()->attenderStatusUpdate($event, $scleverAttendee, $scleverAttendee->status_authkey);
            $event = Calendar_Controller_Event::getInstance()->get($event->getId());
            $email = $sclever->accountEmailAddress;
        }
        
        // get iMIP invitation for event
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $vevent = $converter->fromTine20Model($event);
        $vevent->METHOD = $_method;
        $ics = $vevent->serialize();
        
        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => ($_testEmptyMethod) ? NULL : $_method,
            'originator'     => $email,
        ));
        
        if ($_addEventToiMIP) {
            $iMIP->event = $event;
        }
        
        return $iMIP;
    }
    
    /**
     * testInternalInvitationRequestAutoProcess
     */
    public function testInternalInvitationRequestAutoProcess()
    {
        $iMIP = $this->_getiMIP('REQUEST');
        
        $this->_iMIPFrontend->autoProcess($iMIP);
        $prepared = $this->_iMIPFrontend->prepareComponent($iMIP);
        
        $this->assertEquals(2, count($prepared->event->attendee), 'expected 2 attendee');
        $this->assertEquals('Sleep very long', $prepared->event->summary);
        $this->assertTrue(empty($prepared->preconditions));
    }

    /**
    * testInternalInvitationRequestAutoProcessOwnStatusAlreadySet
    */
    public function testInternalInvitationRequestPreconditionOwnStatusAlreadySet()
    {
        $iMIP = $this->_getiMIP('REQUEST', TRUE);
        
        // set own status
        $ownAttender = Calendar_Model_Attender::getOwnAttender($iMIP->getEvent()->attendee);
        $ownAttender->status = Calendar_Model_Attender::STATUS_TENTATIVE;
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($iMIP->getEvent(), $ownAttender, $ownAttender->status_authkey);
        
        $prepared = $this->_iMIPFrontend->prepareComponent($iMIP);
        $this->assertTrue(empty($prepared->preconditions), "it's ok to reanswer without reschedule!");
        
        // reschedule
        $event = Calendar_Controller_Event::getInstance()->get($prepared->existing_event->getId());
        $event->dtstart->addHour(2);
        $event->dtend->addHour(2);
        Calendar_Controller_Event::getInstance()->update($event, false);

        $this->_iMIPFrontend->getExistingEvent($iMIP, true);
        $iMIP->preconditionsChecked = false;
        $prepared = $this->_iMIPFrontend->prepareComponent($iMIP);
        
        $this->assertFalse(empty($prepared->preconditions), 'do not accept this iMIP after reshedule');
        $this->assertTrue((isset($prepared->preconditions[Calendar_Model_iMIP::PRECONDITION_RECENT]) || array_key_exists(Calendar_Model_iMIP::PRECONDITION_RECENT, $prepared->preconditions)));
    }
    
    /**
     * returns a simple event
     *
     * @return Calendar_Model_Event
     * @param bool $_now
     * @param bool $mute
     * @todo replace with TestCase::_getEvent
     */
    protected function _getEvent($now = FALSE, $mute = NULL)
    {
        return new Calendar_Model_Event(array(
            'summary'     => 'Sleep very long',
            'dtstart'     => '2012-03-25 01:00:00',
            'dtend'       => '2012-03-25 11:15:00',
            'description' => 'Early to bed and early to rise, makes a men healthy, wealthy and wise ... not.',
            'attendee'    => $this->_getAttendee(),
            'organizer'   => Tinebase_Core::getUser()->contact_id,
            'uid'         => Calendar_Model_Event::generateUID(),
        ));
    }
    
    /**
     * get test attendee
     *
     * @return Tinebase_Record_RecordSet
     */
    protected function _getAttendee()
    {
        $personas = Zend_Registry::get('personas');
        $sclever = $personas['sclever'];
        
        return new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'        => Tinebase_Core::getUser()->contact_id,
                'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status'         => Calendar_Model_Attender::STATUS_ACCEPTED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            ),
            array(
                'user_id'        => $sclever->contact_id,
                'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            ),
        ));
    }
    
    /**
     * delete events
     *
     * @return NULL
     * @param boolean $purgeRecords true database delete or use is_deleted = 1
     */
    protected function _deleteEvents($purgeRecords = FALSE)
    {
        if ($purgeRecords) {
            $be = new Calendar_Backend_Sql();
            foreach ($this->_eventIdsToDelete as $idToDelete) {
                $be->delete($idToDelete);
            }
        } else {
            Calendar_Controller_Event::getInstance()->delete($this->_eventIdsToDelete);
        }
    }

    /**
     * testExternalInvitationRequestProcess
     */
    public function testExternalInvitationRequestProcess()
    {
        $ics = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/files/invitation_request_external.ics' );
        $ics = preg_replace('#DTSTART;VALUE=DATE-TIME;TZID=Europe/Berlin:20111121T130000#', 'DTSTART;VALUE=DATE-TIME;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->addHour(1)->format('Ymd\THis'), $ics);
        $ics = preg_replace('#DTEND;VALUE=DATE-TIME;TZID=Europe/Berlin:20111121T140000#', 'DTEND;VALUE=DATE-TIME;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->addHour(2)->format('Ymd\THis'), $ics);
        
        $iMIP = new Calendar_Model_iMIP(array(
                'id'             => Tinebase_Record_Abstract::generateUID(),
                'ics'            => $ics,
                'method'         => 'REQUEST',
                'originator'     => 'l.kneschke@caldav.org',
        ));
        
        Calendar_Controller_EventNotificationsTests::flushMailer();
        $result = $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        
        $this->_iMIPFrontend->prepareComponent($iMIP);
        $this->_eventIdsToDelete[] = $iMIP->event->getId();
        
        // assert external organizer
        $this->assertEquals('l.kneschke@caldav.org', $iMIP->event->organizer->email, 'wrong organizer');
        $this->assertTrue(empty($iMIP->event->organizer->account_id), 'organizer must not have an account');
        
        // assert attendee
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($iMIP->event->attendee);
        $this->assertTrue(!! $ownAttendee, 'own attendee missing');
        $this->assertEquals(5, count($iMIP->event->attendee), 'all attendee must be keeped');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttendee->status, 'must be ACCEPTED');
        
        // assert REPLY message to organizer only
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP);
        if (isset($smtpConfig->from) && ! empty($smtpConfig->from)) {
            $messages = Calendar_Controller_EventNotificationsTests::getMessages();
            $this->assertEquals(1, count($messages), 'exactly one mail should be send');
            $this->assertTrue(in_array('l.kneschke@caldav.org', $messages[0]->getRecipients()), 'organizer is not a receipient');
            $this->assertContains('accepted', $messages[0]->getSubject(), 'wrong subject');
            $this->assertContains('METHOD:REPLY', var_export($messages[0], TRUE), 'method missing');
            $this->assertContains('SEQUENCE:0', var_export($messages[0], TRUE), 'external sequence has not been keepted');
        }
    }
    
    /**
     * external organizer container should not be visible
     */
    public function testExternalContactContainer()
    {
        $this->testExternalInvitationRequestProcess();
        $containerFrontend = new Tinebase_Frontend_Json_Container();
        $result = $containerFrontend->getContainer('Calendar', Tinebase_Model_Container::TYPE_SHARED, null, null);
        
        foreach ($result as $container) {
            if ($container['name'] === 'l.kneschke@caldav.org') {
                $this->fail('found external organizer container: ' . print_r($container, true));
            }
        }
    }
    
    /**
     * adds new imip message to Felamimail cache
     * 
     * @return Felamimail_Model_Message
     */
    protected function _addImipMessageToEmailCache()
    {
        $this->_checkIMAPConfig();
        
        // handle message with fmail (add to cache)
        $message = $this->_emailTestClass->messageTestHelper('calendar_request.eml', NULL, NULL, array('unittest@tine20.org', $this->_getEmailAddress()));
        return Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
    }
    
    /**
     * testDisabledExternalImip
     */
    public function testDisabledExternalImip()
    {
        Calendar_Config::getInstance()->set(Calendar_Config::DISABLE_EXTERNAL_IMIP, true);
        $complete = $this->_addImipMessageToEmailCache();
        $fmailJson = new Felamimail_Frontend_Json();
        $jsonMessage = $fmailJson->getMessage($complete->getId());
        Calendar_Config::getInstance()->set(Calendar_Config::DISABLE_EXTERNAL_IMIP, false);
        $this->assertEmpty($jsonMessage['preparedParts']);
    }
    
    /**
     * check IMAP config and marks test as skipped if no IMAP backend is configured
     */
    protected function _checkIMAPConfig()
    {
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
        if (! $imapConfig || ! isset($imapConfig->useSystemAccount)
            || $imapConfig->useSystemAccount != TRUE
            || ! $this->_emailTestClass instanceof Felamimail_Controller_MessageTest
        ) {
            $this->markTestSkipped('IMAP backend not configured');
        }
    }

    /**
     * testExternalPublishProcess
     * - uses felamimail to cache external publish message
     * 
     * NOTE: meetup sends REQUEST w.o. attendee. We might think of autoconvert this to PUBLISH
     */
    public function testExternalPublishProcess()
    {
        $this->_checkIMAPConfig();
        
        // handle message with fmail (add to cache)
        $message = $this->_emailTestClass->messageTestHelper('meetup.eml');
        $complete = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        
        $iMIP = $complete->preparedParts->getFirstRecord()->preparedData;
        
        $this->setExpectedException('Calendar_Exception_iMIP', 'iMIP preconditions failed: ATTENDEE');
        $result = $this->_iMIPFrontend->process($iMIP);
    }

    /**
     * testInternalInvitationRequestProcess
     */
    public function testInternalInvitationRequestProcess()
    {
        $iMIP = $this->_getiMIP('REQUEST');
        $result = $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_TENTATIVE);
        
        $event = $this->_iMIPFrontend->getExistingEvent($iMIP, true);
        
        $attender = Calendar_Model_Attender::getOwnAttender($event->attendee);
        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $attender->status);
    }

    /**
     * testEmptyMethod
     */
    public function testEmptyMethod()
    {
        $iMIP = $this->_getiMIP('REQUEST', FALSE, TRUE);
        
        $this->assertEquals('REQUEST', $iMIP->method);
    }
    
    /**
     * testInternalInvitationReplyPreconditions
     * 
     * an internal reply does not need to be processed of course
     */
    public function testInternalInvitationReplyPreconditions()
    {
        $iMIP = $this->_getiMIP('REPLY');
        $prepared = $this->_iMIPFrontend->prepareComponent($iMIP);
        
        $this->assertFalse(empty($prepared->preconditions), 'empty preconditions');
        $this->assertTrue((isset($prepared->preconditions[Calendar_Model_iMIP::PRECONDITION_TOPROCESS]) || array_key_exists(Calendar_Model_iMIP::PRECONDITION_TOPROCESS, $prepared->preconditions)), 'missing PRECONDITION_TOPROCESS');
    }
    
    /**
     * testInternalInvitationReplyAutoProcess
     * 
     * an internal reply does not need to be processed of course
     */
    public function testInternalInvitationReplyAutoProcess()
    {
        // flush mailer
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        Tinebase_Smtp::getDefaultTransport()->flush();
        
        $iMIP = $this->_getiMIP('REPLY', TRUE);
        $event = $iMIP->getEvent();
        
        try {
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->assertContains('TOPROCESS', $e->getMessage());
            return;
        }
        
        $this->fail("autoProcess did not throw TOPROCESS Exception $e");
    }
    
    /**
     * testInvitationExternalReply
     */
    public function testInvitationExternalReply()
    {
        $email = $email = $this->_getEmailAddress();
        
        $ics = file_get_contents(dirname(__FILE__) . '/files/invitation_reply_external_accepted.ics' );
        $ics = preg_replace('/unittest@tine20\.org/', $email, $ics);
        
        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'REPLY',
            'originator'     => 'mail@corneliusweiss.de',
        ));
        
        $this->assertEquals(1, $iMIP->getEvent()->seq);
        $this->assertTrue(! empty($iMIP->getEvent()->last_modified_time));
        
        // force creation of external attendee
        $externalAttendee = new Calendar_Model_Attender(array(
            'user_type'     => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'       => $iMIP->getEvent()->attendee->getFirstRecord()->user_id,
            'status'        => Calendar_Model_Attender::STATUS_NEEDSACTION
        ));
        
        // create matching event
        $event = new Calendar_Model_Event(array(
            'summary'     => 'TEST7',
            'dtstart'     => '2011-11-30 14:00:00',
            'dtend'       => '2011-11-30 15:00:00',
            'description' => 'Early to bed and early to rise, makes a men healthy, wealthy and wise ...',
            'attendee'    => $this->_getAttendee(),
            'organizer'   => Tinebase_Core::getUser()->contact_id,
            'uid'         => 'a8d10369e051094ae9322bd65e8afecac010bfc8',
        ));
        $event->attendee->addRecord($externalAttendee);
        $event = Calendar_Controller_Event::getInstance()->create($event);
        $this->_eventIdsToDelete[] = $event->getId();
        
        // TEST NORMAL REPLY
        try {
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST NORMAL REPLY autoProcess throws Exception: ' . $e);
        }
        unset($iMIP->existing_event);
        
        $updatedEvent = Calendar_Controller_Event::getInstance()->get($event->getId());
        $updatedExternalAttendee = Calendar_Model_Attender::getAttendee($updatedEvent->attendee, $externalAttendee);
        
        $this->assertEquals(3, count($updatedEvent->attendee));
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedExternalAttendee->status, 'status not updated');
        
        // TEST ACCEPTABLE NON RECENT REPLY
        $updatedExternalAttendee->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($updatedEvent, $updatedExternalAttendee, $updatedExternalAttendee->status_authkey);
        try {
            $iMIP->preconditionsChecked = false;
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST ACCEPTABLE NON RECENT REPLY autoProcess throws Exception: ' . $e);
        }
        unset($iMIP->existing_event);
        
        $updatedEvent = Calendar_Controller_Event::getInstance()->get($event->getId());
        $updatedExternalAttendee = Calendar_Model_Attender::getAttendee($updatedEvent->attendee, $externalAttendee);
        
        $this->assertEquals(3, count($updatedEvent->attendee));
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedExternalAttendee->status, 'status not updated');
        
        // check if attendee are resolved
        $existingEvent = $this->_iMIPFrontend->getExistingEvent($iMIP);
        $this->assertTrue($iMIP->existing_event->attendee instanceof Tinebase_Record_RecordSet);
        $this->assertEquals(3, count($iMIP->existing_event->attendee));
        
        // TEST NON ACCEPTABLE NON RECENT REPLY
        $iMIP->preconditionsChecked = false;
        try {
            $this->_iMIPFrontend->autoProcess($iMIP);
            $this->fail('autoProcess should throw Calendar_Exception_iMIP');
        } catch (Calendar_Exception_iMIP $cei) {
            $this->assertContains('iMIP preconditions failed: RECENT', $cei->getMessage());
        }
    }

    /**
     * testExternalInvitationCancelProcessEvent
     *
     */
    public function testExternalInvitationCancelProcessEvent()
    {
        $iMIP = $this->testExternalInvitationRequestAutoProcess();
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        $this->_eventIdsToDelete[] = $iMIP->event->getId();

        $ics = file_get_contents(dirname(__FILE__) . '/files/invitation_cancel.ics' );

        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'CANCEL',
            'originator'     => 'l.kneschke@caldav.org',
        ));

        // TEST CANCEL
        try {
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST NORMAL CANCEL autoProcess throws Exception: ' . $e);
        }
        unset($iMIP->existing_event);

        $existingEvent = $this->_iMIPFrontend->getExistingEvent($iMIP,true);
        $this->assertNull($existingEvent, 'event must be deleted');

        $existingEvent =  $this->_iMIPFrontend->getExistingEvent($iMIP, true, true);
        $this->assertEquals($existingEvent->is_deleted, 1, 'event must be deleted');
    }

    /**
     * testExternalInvitationCancelProcessAttendee
     *
     */
    public function testExternalInvitationCancelProcessAttendee()
    {
        $iMIP = $this->testExternalInvitationRequestAutoProcess();
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        $this->_eventIdsToDelete[] = $eventId = $iMIP->event->getId();

        $ics = file_get_contents(dirname(__FILE__) . '/files/invitation_cancel.ics' );
        // set status to not cancelled, so that only attendees are removed from the event
        $ics = preg_replace('#STATUS:CANCELLED#', 'STATUS:CONFIRMED', $ics);

        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'CANCEL',
            'originator'     => 'l.kneschke@caldav.org',
        ));

        // TEST CANCEL
        try {
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST NORMAL CANCEL autoProcess throws Exception: ' . $e);
        }
        unset($iMIP->existing_event);

        $updatedEvent = Calendar_Controller_Event::getInstance()->get($eventId);
        $this->assertEquals(3, count($updatedEvent->attendee), 'attendee count must be 3');
    }

    /**
      * testInvitationCancel
      * 
      * @todo implement
      */
     public function testOrganizerSendBy()
     {
         $this->markTestIncomplete('implement me');
     }
}
