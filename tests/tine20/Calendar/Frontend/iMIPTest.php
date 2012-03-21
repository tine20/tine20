<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Frontend_iMIP
 */
class Calendar_Frontend_iMIPTest extends PHPUnit_Framework_TestCase
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
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Calendar iMIP Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
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
        if (! empty($this->_eventIdsToDelete)) {
            Calendar_Controller_Event::getInstance()->delete($this->_eventIdsToDelete);
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
        $ics = file_get_contents(dirname(__FILE__) . '/files/invitation_request_external.ics' );
        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'REQUEST',
            'originator'     => 'l.kneschke@caldav.org',
        ));
        
        $this->_iMIPFrontend->autoProcess($iMIP);
        $prepared = $this->_iMIPFrontend->prepareComponent($iMIP);

        $this->assertEquals(3, count($prepared->event->attendee));
        $this->assertEquals('test mit extern', $prepared->event->summary);
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
        $event = $this->_getEvent();
        $event = Calendar_Controller_Event::getInstance()->create($event);
        $this->_eventIdsToDelete[] = $event->getId();
        
        // get iMIP invitation for event
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $vevent = $converter->fromTine20Model($event);
        $vevent->METHOD = $_method;
        $ics = $vevent->serialize();
        
        $testConfig = Zend_Registry::get('testConfig');
        $email = ($testConfig->email) ? $testConfig->email : 'unittest@tine20.org';
        
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
        $ownAttender->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($iMIP->getEvent(), $ownAttender, $ownAttender->status_authkey);
        
        $prepared = $this->_iMIPFrontend->prepareComponent($iMIP);
        $this->assertFalse(empty($prepared->preconditions));
        $this->assertTrue(array_key_exists(Calendar_Model_iMIP::PRECONDITION_RECENT, $prepared->preconditions));
    }
    
    /**
    * returns a simple event
    *
    * @return Calendar_Model_Event
    */
    protected function _getEvent()
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
     * testExternalInvitationRequestProcess
     * - uses felamimail to cache external invitation message
     * 
     * -> external invitation requests are not supported atm
     */
    public function testExternalInvitationRequestProcess()
    {
        $this->_checkIMAPConfig();
        
        $testConfig = Zend_Registry::get('testConfig');
        $email = ($testConfig->email) ? $testConfig->email : 'unittest@tine20.org';
        
        // handle message with fmail (add to cache)
        $message = $this->_emailTestClass->messageTestHelper('calendar_request.eml', NULL, NULL, array('unittest@tine20.org', $email));
        $complete = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        
        $iMIP = $complete->preparedParts->getFirstRecord()->preparedData;
        
        Calendar_Controller_EventNotificationsTests::flushMailer();
        $result = $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        $this->_iMIPFrontend->prepareComponent($iMIP);
        $this->_eventIdsToDelete[] = $iMIP->event->getId();
        
        // assert external organizer
        $this->assertEquals('l.kneschke@caldav.org', $iMIP->event->organizer->email, 'wrong organizer');
        $this->assertTrue(empty($iMIP->event->organizer->account_id), 'organizer must not have an account');
        
        // assert attendee
        $this->assertEquals(1, count($iMIP->event->attendee), 'all attendee but curruser must be whiped');
        $this->assertEquals($email, $iMIP->event->attendee->getFirstRecord()->user_id->email, 'wrong attendee mail');
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $iMIP->event->attendee->getFirstRecord()->user_id->account_id, 'wrong attendee');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $iMIP->event->attendee->getFirstRecord()->status);
        
        // assert REPLY message
        $messages = Calendar_Controller_EventNotificationsTests::getMessages();
        $this->assertEquals(1, count($messages), 'only one mails should be send');
        $this->assertTrue(in_array('l.kneschke@caldav.org', $messages[0]->getRecipients()), 'organizer is not a receipient');
        $this->assertContains('METHOD:REPLY', var_export($messages[0], TRUE), 'method missing');
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
        
        $event = Calendar_Controller_MSEventFacade::getInstance()->lookupExistingEvent($iMIP->getEvent());
        
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
     * testInvitationInternalReplyPreconditions
     */
    public function testInvitationInternalReplyPreconditions()
    {
        $iMIP = $this->_getiMIP('REPLY');
        $prepared = $this->_iMIPFrontend->prepareComponent($iMIP);
        
        $this->assertFalse(empty($prepared->preconditions), 'empty preconditions');
        $this->assertTrue(array_key_exists(Calendar_Model_iMIP::PRECONDITION_TOPROCESS, $prepared->preconditions), 'missing PRECONDITION_TOPROCESS');
    }
    
    /**
     * test no seq update
     * test no notifications
     *
    public function testInvitationInternalReplyAutoProcess()
    {
        // flush mailer
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        Tinebase_Smtp::getDefaultTransport()->flush();
        
        $iMIP = $this->_getiMIP('REPLY', TRUE);
        $event = $iMIP->getEvent();
        
        print_r($event->getId());
        try {
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('autoProcess throwed Exception');
        }
        
        
    }
    */
    
    /**
     * testInvitationExternalReply
     */
    public function testInvitationExternalReply()
    {
        $testConfig = Zend_Registry::get('testConfig');
        $email = ($testConfig->email) ? $testConfig->email : 'unittest@tine20.org';
        
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
    
        // TEST NON ACCEPTABLE NON RECENT REPLY
        $this->setExpectedException('Calendar_Exception_iMIP', 'iMIP preconditions failed: RECENT');
        $iMIP->preconditionsChecked = false;
        $this->_iMIPFrontend->autoProcess($iMIP);
    }

//     /**
//      * testInvitationCancel
//      * 
//      * @todo implement
//      */
//     public function testInvitationCancel()
//     {
        
//     }
    
//     public function testOrganizerSendBy()
//     {
        
//     }
}
