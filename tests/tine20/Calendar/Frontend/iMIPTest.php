<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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

        parent::setUp();
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

        parent::tearDown();
    }

    public function testExternalInvitationToOneOfARecurSeries()
    {
        $ics = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) .
            '/files/exchange_external_reoccuring_onlyone.ics');
        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'REQUEST',
            'originator'     => 'l.kneschke@caldav.org',
        ));

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        $this->_iMIPFrontendMock->process($iMIP);

        static::assertTrue($iMIP->event instanceof Calendar_Model_Event, 'imips event not set');
        static::assertEquals(1, count($iMIP->event->attendee));
        static::assertEquals('Daily Call', $iMIP->event->summary);
        static::assertEquals('RECURRENCE-ID:20180906T110000',
            $iMIP->event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['RECURRENCE-ID']);
        static::assertEquals('X-MICROSOFT-CDO-OWNERAPPTID:1983350753',
            $iMIP->event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['X-MICROSOFT-CDO-OWNERAPPTID']);

        // TODO test that msg send to external server contains proper recurid
        $iMIP->preconditionsChecked = true;
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var \Sabre\VObject\Component\VCalendar $vcalendar */
        $vcalendar = Calendar_Convert_Event_VCalendar_Factory::factory('')->fromTine20Model($iMIP->getEvent());
        $vCalBlob = $vcalendar->serialize();
        static::assertContains('RECURRENCE-ID:20180906T110000', $vCalBlob);
        static::assertContains('X-MICROSOFT-CDO-OWNERAPPTID:1983350753', $vCalBlob);
    }
    /**
     * testExternalInvitationRequestAutoProcess
     */
    public function testExternalInvitationRequestAutoProcess($_doAssertation = true, $_doAutoProcess = true)
    {
        return $this->_testExternalImap('invitation_request_external.ics', 5, 'test mit extern', $_doAssertation,
            $_doAutoProcess);
    }

    public function testExternalInvitationRequestMultiImport()
    {
        if (Tinebase_Core::getUser()->accountLoginName === 'travis') {
            static::markTestSkipped('FIXME on travis-ci');
        }

        $firstIMIP = $this->testExternalInvitationRequestAutoProcess();
        $this->_iMIPFrontendMock->process($firstIMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        Calendar_Model_Attender::clearCache();

        $secondIMIP = $this->testExternalInvitationRequestAutoProcess(false, false);

        static::assertTrue($secondIMIP->preconditionsChecked, 'preconditions have not been checked');
        static::assertNotEmpty($secondIMIP->existing_event, 'there should be an existing event');
        static::assertEmpty($secondIMIP->preconditions, 'no preconditions should be raised');
        static::assertEquals($secondIMIP->event->organizer->getId(), $secondIMIP->existing_event->organizer->getId(),
            'organizer mismatch');
        static::assertEquals(4, count($secondIMIP->event->attendee));
        static::assertEquals(5, count($secondIMIP->existing_event->attendee));
        static::assertEquals(2, $secondIMIP->existing_event->attendee->filter('status',
            Calendar_Model_Attender::STATUS_ACCEPTED)->count(), 'organizer and vagrant should have accepted');
        $this->_iMIPFrontendMock->process($secondIMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        $event = Calendar_Controller_Event::getInstance()->get($firstIMIP->event->getId());
        static::assertEquals(3, $event->attendee->filter('status', Calendar_Model_Attender::STATUS_ACCEPTED)->count(),
            'organizer, vagrant and sclever should have accepted');
    }

    protected function _testExternalImap($icsFilename, $numAttendee, $summary, $_doAssertation = true,
        $_doAutoProcess = true)
    {
        $ics = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/files/' . $icsFilename);
        $ics = preg_replace('#\d{8}T#', Tinebase_DateTime::now()->addDay(1)->format('Ymd') . 'T', $ics);

        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'REQUEST',
            'originator'     => 'l.Kneschke@caldav.org',
        ));

        if ($_doAutoProcess) {
            $this->_iMIPFrontend->autoProcess($iMIP);
        }
        $prepared = $this->_iMIPFrontend->prepareComponent($iMIP);

        if ($_doAssertation) {
            $this->assertEmpty($prepared->existing_event, 'there should be no existing event');
            $this->assertEmpty($prepared->preconditions, 'no preconditions should be raised');
            $this->assertEquals($numAttendee, count($prepared->event->attendee));
            $this->assertEquals($summary, $prepared->event->summary);
        }

        return $iMIP;
    }

    /**
     * testExternalInvitationRequestAutoProcessMozilla
     */
    public function testExternalInvitationRequestAutoProcessMozilla()
    {
        $this->_testExternalImap('invitation_request_external_mozilla.ics', 2, 'Input Plakat für Veranstaltung am 19.10.');
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
        // TODO should not depend on IMAP/SMTP config ...
        $this->_checkIMAPConfig();

        $ics = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/files/invitation_request_external.ics' );
        $ics = preg_replace('#DTSTART;VALUE=DATE-TIME;TZID=Europe/Berlin:20111121T130000#', 'DTSTART;VALUE=DATE-TIME;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->addHour(1)->format('Ymd\THis'), $ics);
        $ics = preg_replace('#DTEND;VALUE=DATE-TIME;TZID=Europe/Berlin:20111121T140000#', 'DTEND;VALUE=DATE-TIME;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->addHour(2)->format('Ymd\THis'), $ics);
        
        $iMIP = new Calendar_Model_iMIP(array(
                'id'             => Tinebase_Record_Abstract::generateUID(),
                'ics'            => $ics,
                'method'         => 'REQUEST',
                'originator'     => 'l.Kneschke@caldav.org',
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

        // assert no status authkey for external attendee
        foreach($iMIP->event->attendee as $attendee) {
            if (!$attendee->user_id->account_id) {
                $this->assertFalse(!!$attendee->user_id->status_authkey, 'authkey should be skipped');
            }
        }
        
        // assert REPLY message to organizer only
        $messages = Calendar_Controller_EventNotificationsTests::getMessages();
        $this->assertEquals(1, count($messages), 'exactly one mail should be send');
        $this->assertTrue(in_array('l.kneschke@caldav.org', $messages[0]->getRecipients()), 'organizer is not a receipient');
        $this->assertContains('accepted', $messages[0]->getSubject(), 'wrong subject');
        $this->assertContains('METHOD:REPLY', var_export($messages[0], TRUE), 'method missing');
        $this->assertContains('SEQUENCE:0', var_export($messages[0], TRUE), 'external sequence has not been keepted');
    }
    
    /**
     * external organizer container should not be visible
     */
    public function testExternalContactContainer()
    {
        $this->testExternalInvitationRequestProcess();
        $containerFrontend = new Tinebase_Frontend_Json_Container();
        $result = $containerFrontend->getContainer(Calendar_Model_Event::class, Tinebase_Model_Container::TYPE_SHARED, null, null);
        
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
        $this->assertFalse(empty($jsonMessage['preparedParts']));
        static::assertTrue(isset($jsonMessage['preparedParts'][0]['preparedData']['preconditions']) &&
            !empty($jsonMessage['preparedParts'][0]['preparedData']['preconditions']));
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

        self::assertGreaterThan(0, count($complete->preparedParts), 'no prepared parts found');
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
        
        $this->fail("autoProcess did not throw TOPROCESS Exception");
    }

    public function testGoogleExternalInviteAddAttenderFelamiMailFE()
    {
        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_invite.ics');
        $iMIP->originator = $iMIP->getEvent()->resolveOrganizer()->email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvent()->getId());
        static::assertSame('test', $createdEvent->summary);
        $unitAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $unitAttender->status);


        // test external invite update
        $iMIP = $this->_createiMIPFromFile('google_external_invite_addAttender.ics');

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        Calendar_Controller_MSEventFacade::unsetInstance();

        $iMIP->originator = $iMIP->getEvent()->resolveOrganizer()->email;
        $iMIP->method = 'REQUEST';

        static::assertNull($this->_iMIPFrontend->autoProcess($iMIP), 'auto process failed');
        $iMIPevent = $iMIP->getEvent();
        static::assertSame(3, $iMIPevent->attendee->count(), 'attendee count mismatch');
        $scleverAttender = Calendar_Model_Attender::getOwnAttender($iMIPevent->attendee);
        static::assertNotNull($scleverAttender, 'sclever attender not found');
        $unitAttender = $iMIPevent->attendee->find('user_id', $unitAttender->user_id);
        static::assertNotNull($unitAttender, 'unit attender not found');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $unitAttender->status);
    }

    public function testGoogleExternalInviteAddAttenderConcurrencyHandlingWebDAV()
    {
        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_invite.ics');
        $iMIP->originator = $iMIP->getEvent()->resolveOrganizer()->email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvent()->getId());
        static::assertSame('test', $createdEvent->summary);
        $unitAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $unitAttender->status);

        $createdEvent->summary = 'shooohoho';
        Calendar_Controller_Event::getInstance()->update($createdEvent);


        $oldHTTPAgent = $_SERVER['HTTP_USER_AGENT'];
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';

        try {
            $vcalendar = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) .
                '/files/google_external_invite_addAttender.ics');

            Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
            Calendar_Controller_MSEventFacade::unsetInstance();

            $id = '3evgs2i0jdkofmibc9u5cah0a9@googlePcomffffffffffffffff';
            $event = Calendar_Frontend_WebDAV_Event::create(Tinebase_Container::getInstance()->getPersonalContainer(
                $this->_personas['sclever'], Calendar_Model_Event::class,
                $this->_personas['sclever'])->getFirstRecord(),
                "$id.ics", $vcalendar);
            $record = $event->getRecord();

            static::assertSame($createdEvent->uid, $record->uid, 'uid does not match');
            static::assertSame($createdEvent->getId(), $record->getId());

            Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);

            $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
                ['field' => 'dtstart', 'operator' => 'equals', 'value' => $createdEvent->dtstart]
            ]));

            static::assertEquals(1, $events->count());
            $ownAttender = Calendar_Model_Attender::getOwnAttender($events->getFirstRecord()->attendee);
            static::assertNotNull($ownAttender, 'lost own attendee');
            static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
        } finally {
            $_SERVER['HTTP_USER_AGENT'] = $oldHTTPAgent;
        }
    }

    public function testGoogleExternalInviteAddAttenderConcurrencyHandling()
    {
        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_invite.ics');
        $iMIP->originator = $iMIP->getEvent()->resolveOrganizer()->email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvent()->getId());
        static::assertSame('test', $createdEvent->summary);
        $unitAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $unitAttender->status);

        $createdEvent->summary = 'shooohoho';
        Calendar_Controller_Event::getInstance()->update($createdEvent);

        // test external invite update
        $iMIP = $this->_createiMIPFromFile('google_external_invite_addAttender.ics');

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        Calendar_Controller_MSEventFacade::unsetInstance();

        $iMIP->originator = $iMIP->getEvent()->resolveOrganizer()->email;
        $iMIP->method = 'REQUEST';

        static::assertNull($this->_iMIPFrontend->autoProcess($iMIP), 'auto process failed');
        $iMIPevent = $iMIP->getEvent();
        static::assertSame(3, $iMIPevent->attendee->count(), 'attendee count mismatch');
        $scleverAttender = Calendar_Model_Attender::getOwnAttender($iMIPevent->attendee);
        static::assertNotNull($scleverAttender, 'sclever attender not found');
        $unitAttender = $iMIPevent->attendee->find('user_id', $unitAttender->user_id);
        static::assertNotNull($unitAttender, 'unit attender not found');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $unitAttender->status);
    }

    public function testGoogleExternalInviteAddAttender()

    {
        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_invite.ics');
        $iMIP->originator = $iMIP->getEvent()->resolveOrganizer()->email;
        $iMIP->method = 'REQUEST';

        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvent()->getId());
        static::assertSame('test', $createdEvent->summary);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);


        // test external invite update
        $iMIP = $this->_createiMIPFromFile('google_external_invite_addAttender.ics');

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        Calendar_Controller_MSEventFacade::unsetInstance();

        $iMIP->originator = $iMIP->getEvent()->resolveOrganizer()->email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $updatedEvent = Calendar_Controller_Event::getInstance()->get($createdEvent->getId());
        static::assertSame('test update', $updatedEvent->summary);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($updatedEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);


        Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
            ['field' => 'dtstart', 'operator' => 'equals', 'value' => $updatedEvent->dtstart]
        ]));

        static::assertEquals(1, $events->count());
        $ownAttender = Calendar_Model_Attender::getOwnAttender($events->getFirstRecord()->attendee);
        static::assertNotNull($ownAttender, 'lost own attendee');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
    }

    /**
     * testInvitationExternalReply
     */
    public function testInvitationExternalReply()
    {
        $iMIP = $this->_createiMIPFromFile('invitation_reply_external_accepted.ics');

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
        $this->assertTrue(isset($updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]) &&
            isset($updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE]),
            'xprops of attender not properly set: ' . print_r($updatedExternalAttendee->xprops(), true));
        $this->assertEquals($iMIP->getEvent()->seq, $updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE]);
        $this->assertEquals($iMIP->getEvent()->last_modified_time, $updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]);
        
        // TEST NORMAL REPLY
        $updatedExternalAttendee->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($updatedEvent, $updatedExternalAttendee, $updatedExternalAttendee->status_authkey);
        try {
            $iMIP->getEvent()->seq = $iMIP->getEvent()->seq + 1;
            $iMIP->preconditionsChecked = false;
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST NORMAL REPLY autoProcess throws Exception: ' . $e);
        }
        unset($iMIP->existing_event);
        
        $updatedEvent = Calendar_Controller_Event::getInstance()->get($event->getId());
        $updatedExternalAttendee = Calendar_Model_Attender::getAttendee($updatedEvent->attendee, $externalAttendee);
        
        $this->assertEquals(3, count($updatedEvent->attendee));
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedExternalAttendee->status, 'status not updated');
        $this->assertEquals($iMIP->getEvent()->seq, $updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE]);
        $this->assertEquals($iMIP->getEvent()->last_modified_time, $updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]);
        
        // check if attendee are resolved
        $existingEvent = $this->_iMIPFrontend->getExistingEvent($iMIP);
        $this->assertTrue($iMIP->existing_event->attendee instanceof Tinebase_Record_RecordSet);
        $this->assertEquals(3, count($iMIP->existing_event->attendee));
        
        // TEST NON RECENT REPLY (seq is the same as before)
        $iMIP->preconditionsChecked = false;
        try {
            $this->_iMIPFrontend->autoProcess($iMIP);
            $this->fail('autoProcess should throw Calendar_Exception_iMIP');
        } catch (Calendar_Exception_iMIP $cei) {
            $this->assertContains('iMIP preconditions failed: RECENT', $cei->getMessage());
        }
    }

    protected function _createiMIPFromFile($_filename)
    {
        $email = $email = $this->_getEmailAddress();

        $ics = file_get_contents(dirname(__FILE__) . '/files/' . $_filename);
        $ics = preg_replace('/unittest@tine20\.org/', $email, $ics);
        $ics = preg_replace('/@tine20\.org/', '@' . TestServer::getPrimaryMailDomain(), $ics);

        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'REPLY',
            'originator'     => 'mail@Corneliusweiss.de',
        ));

        $this->assertEquals(1, $iMIP->getEvent()->seq);
        $this->assertTrue(! empty($iMIP->getEvent()->last_modified_time));

        return $iMIP;
    }

    public function testExternalReplyFromGoogle()
    {
        $iMIP = $this->_createiMIPFromFile('google_confirm.ics');
        // force creation of external attendee
        $externalAttendee = new Calendar_Model_Attender(array(
            'user_type'     => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'       => 'mail@cOrneliusweiss.de',
            'status'        => Calendar_Model_Attender::STATUS_NEEDSACTION
        ));

        // create matching event
        $event = new Calendar_Model_Event(array(
            'summary'     => 'testtermin google confirm',
            'dtstart'     => '2017-11-16 10:30:00',
            'dtend'       => '2017-11-16 11:30:00',
            'attendee'    => $this->_getAttendee(),
            'organizer'   => Tinebase_Core::getUser()->contact_id,
            'uid'         => '62050f080e53ca8e00353ff0a89c6c6aa4af3dec',
        ));
        $event->attendee->addRecord($externalAttendee);
        Calendar_Controller_Event::getInstance()->create($event);

        // TEST NORMAL REPLY
        try {
            $result = $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST NORMAL REPLY autoProcess throws Exception: ' . $e);
        }
        unset($iMIP->existing_event);

        self::assertTrue($result);
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
            'originator'     => 'l.kneschke@calDav.org',
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
            'originator'     => 'l.kneschke@caldav.Org',
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

    /**
     * testExternalInvitationRescheduleOutlook
     */
    public function testExternalInvitationRescheduleOutlook()
    {
        // TODO should not depend on IMAP/SMTP config ...
        $this->_checkIMAPConfig();

        // initial invitation
        $iMIP = $this->_testExternalImap('outlook_invitation.ics',
            3, 'Metaways Folgetermin ');
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        $this->_eventIdsToDelete[] = $eventId = $iMIP->event->getId();


        // reschedule/reply first user
        Calendar_Controller_EventNotificationsTests::flushMailer();
        $ics = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/files/outlook_reschedule.ics');
        $ics = preg_replace('/20170816/', Tinebase_DateTime::now()->addDay(2)->format('Ymd'), $ics);
        $iMIP = new Calendar_Model_iMIP(array(
            'id' => Tinebase_Record_Abstract::generateUID(),
            'ics' => $ics,
            'method' => 'REQUEST',
            'originator' => 'l.kneschkE@caldav.org',
        ));
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_TENTATIVE);
        unset($iMIP->existing_event);

        $updatedEvent = Calendar_Controller_Event::getInstance()->get($eventId);
        $this->assertEquals(Tinebase_DateTime::now()->addDay(2)->format('Y-m-d') . ' 11:00:00',
            $updatedEvent->dtstart->setTimezone($updatedEvent->originator_tz)->toString());

        $messages = Calendar_Controller_EventNotificationsTests::getMessages();
        $this->assertEquals(1, count($messages), 'exactly one mail should be send');
        $this->assertTrue(in_array('l.kneschke@caldav.org', $messages[0]->getRecipients()), 'organizer is not a receipient');
        $this->assertContains('Tentative response', $messages[0]->getSubject(), 'wrong subject');
        $this->assertContains('METHOD:REPLY', var_export($messages[0], TRUE), 'method missing');
        $this->assertContains('SEQUENCE:4', var_export($messages[0], TRUE), 'external sequence has not been keepted');


        // reply from second internal attendee
        Calendar_Controller_EventNotificationsTests::flushMailer();
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        Calendar_Model_Attender::clearCache();
        $iMIP = new Calendar_Model_iMIP(array(
            'id' => Tinebase_Record_Abstract::generateUID(),
            'ics' => $ics,
            'method' => 'REQUEST',
            'originator' => 'l.kNeschke@caldav.org',
        ));
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_DECLINED);
        $messages = Calendar_Controller_EventNotificationsTests::getMessages();
        $this->assertEquals(1, count($messages), 'exactly one mail should be send');
        $this->assertContains('Clever, Susan declined event', $messages[0]->getSubject(), 'wrong subject');
        $this->assertContains('SEQUENCE:4', var_export($messages[0], TRUE), 'external sequence has not been keepted');

        // try outdated imip
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['pwulf']);
        try {
            $iMIP = $this->_testExternalImap('outlook_invitation.ics',
                3, 'Metaways Folgetermin ');
        } catch (Calendar_Exception_iMIP $preconditionException) {}
        $this->assertContains('RECENT', $preconditionException->getMessage());

    }

    public function testGoogleExternalInviteLongUID()
    {
        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_inviteLongUID.ics');
        $iMIP->originator = $iMIP->getEvent()->resolveOrganizer()->email;
        $iMIP->method = 'REQUEST';
        /*try {
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('external invite autoProcess throws Exception: ' . get_class($e) . ': ' . $e->getMessage());
        }*/
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvent()->getId());
        static::assertSame('test', $createdEvent->summary);
        static::assertSame('3evgs2i0jdkofmibc9u5cah0a9@googlePcomffffffffffffffff', $createdEvent->uid);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);


        // test external invite update
        $iMIP = $this->_createiMIPFromFile('google_external_inviteLongUID.ics');

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);

        $iMIP->originator = $iMIP->getEvent()->resolveOrganizer()->email;
        $iMIP->method = 'REQUEST';

        $existingEvent = $this->_iMIPFrontend->getExistingEvent($iMIP, true);
        static::assertNotNull($existingEvent, 'can\'t get existing event');
        static::assertSame($createdEvent->uid, $existingEvent->uid, 'uid does not match');
        static::assertSame($createdEvent->getId(), $existingEvent->getId());

        /*try {
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('external invite autoProcess throws Exception: ' . get_class($e) . ': ' . $e->getMessage());
        }*/
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $existingEvent = $this->_iMIPFrontend->getExistingEvent($iMIP, true);
        static::assertSame($createdEvent->uid, $existingEvent->uid, 'uid does not match');
        static::assertSame($createdEvent->getId(), $existingEvent->getId());
        $ownAttender = Calendar_Model_Attender::getOwnAttender($existingEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);

        Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
            ['field' => 'dtstart', 'operator' => 'equals', 'value' => $createdEvent->dtstart]
        ]));

        static::assertEquals(1, $events->count());
        $ownAttender = Calendar_Model_Attender::getOwnAttender($events->getFirstRecord()->attendee);
        static::assertNotNull($ownAttender, 'lost own attendee');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
    }

    public function testGoogleExternalInviteLongUIDWebDAV()
    {
        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_inviteLongUID.ics');
        $iMIP->originator = $iMIP->getEvent()->resolveOrganizer()->email;
        $iMIP->method = 'REQUEST';
        /*try {
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('external invite autoProcess throws Exception: ' . get_class($e) . ': ' . $e->getMessage());
        }*/
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontendMock->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvent()->getId());
        static::assertSame('test', $createdEvent->summary);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);


        $oldHTTPAgent = $_SERVER['HTTP_USER_AGENT'];
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';

        try {
            $vcalendar = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) .
                '/files/google_external_inviteLongUID.ics');

            Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);

            $id = '3evgs2i0jdkofmibc9u5cah0a9@googlePcomffffffffffffffff';
            $event = Calendar_Frontend_WebDAV_Event::create(Tinebase_Container::getInstance()->getPersonalContainer(
                $this->_personas['sclever'], Calendar_Model_Event::class,
                $this->_personas['sclever'])->getFirstRecord(),
                "$id.ics", $vcalendar);
            $record = $event->getRecord();

            static::assertSame($createdEvent->uid, $record->uid, 'uid does not match');
            static::assertSame($createdEvent->getId(), $record->getId());

            Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);

            $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
                ['field' => 'dtstart', 'operator' => 'equals', 'value' => $createdEvent->dtstart]
            ]));

            static::assertEquals(1, $events->count());
            $ownAttender = Calendar_Model_Attender::getOwnAttender($events->getFirstRecord()->attendee);
            static::assertNotNull($ownAttender, 'lost own attendee');
            static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
        } finally {
            $_SERVER['HTTP_USER_AGENT'] = $oldHTTPAgent;
        }
    }
}
