<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo		move files/*.eml to Felamimail tests or remove them?
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
        
        $this->_emailTestClass = new Felamimail_Controller_MessageTest();
        $this->_emailTestClass->setup();
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
     * @return Calendar_Model_iMIP
     */
    protected function _getiMIP($_method)
    {
        $event = $this->_getEvent();
        $event = Calendar_Controller_Event::getInstance()->create($event);
        $this->_eventIdsToDelete[] = $event->getId();
        
        // get iMIP invitation for event
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $vevent = $converter->fromTine20Model($event);
        $ics = $vevent->serialize();
        
        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
        	'ics'            => $ics,
            'method'         => $_method,
            'originator'     => 'unittest@tine20.org',
        ));
        
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
        
        $this->assertEquals(2, count($prepared->event->attendee));
        $this->assertEquals('Sleep very long', $prepared->event->summary);
        $this->assertTrue(empty($prepared->preconditions));
    }

    /**
    * testInternalInvitationRequestAutoProcessOwnStatusAlreadySet
    */
    public function testInternalInvitationRequestPreconditionOwnStatusAlreadySet()
    {
//         $iMIP = $this->_getiMIP('REQUEST');
        // -- set own status
//         $prepared = $this->_iMIPFrontend->prepareComponent($iMIP);
        // -- assert RECENT precondition fail?
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
            'organizer'    => Tinebase_Core::getUser()->contact_id,
            'uid'          => Calendar_Model_Event::generateUID(),
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
     * 
     * -> external invitation requests are not supported atm
     */
    public function testExternalInvitationRequestProcess()
    {
        // handle message with fmail (add to cache)
        $message = $this->_emailTestClass->messageTestHelper('calendar_request.eml');
        $complete = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        
        $iMIP = $complete->preparedParts->getFirstRecord()->preparedData;
        
        $this->setExpectedException('Calendar_Exception_iMIP', 'iMIP preconditions failed: ORGANIZER');
        $result = $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
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
     * testInvitationInternalReply
     * 
     * @todo implement
     */
    public function testInvitationInternalReply()
    {
        // -- auto process / should do nothing
    }

    /**
     * testInvitationExternalReply
     * 
     * @todo implement
     */
    public function testInvitationExternalReply()
    {
        // -- auto process / should process
        // -- prepareComponent / assert recent precondition
    }

    /**
     * testInvitationCancel
     * 
     * @todo implement
     */
    public function testInvitationCancel()
    {
        
    }
}
