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

    }
    
    public function testInvitation()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        
        $this->_mailer->flush();
        $persitentEvent = $this->_eventController->create($event);
        $subject = array_value(0, $this->_mailer->getMessages())->getSubject();
        $this->assertTrue((bool) strpos($subject, 'invit'), 'Mail subject should contain "invit" but '. $subject . ' is given');
        
        $this->_mailer->flush();
        $persitentEvent = $this->_eventController->delete($persitentEvent);
        $subject = array_value(0, $this->_mailer->getMessages())->getSubject();
        $this->assertTrue((bool) strpos($subject, 'cancel'), 'Mail subject should contain "cancel" but '. $subject . ' is given');
    }
    
    public function testUpdateEmpty()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $persitentEvent = $this->_eventController->create($event);
        
        // no updates
        $updatedEvent = $this->_eventController->update($persitentEvent);
        
        // assert 'invited'
        // assert no update etc.
    }
    
    public function testUpdateReschedule()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $persitentEvent = $this->_eventController->create($event);
        
        $persitentEvent->summary = 'reschedule notification has precedence over normal update';
        $persitentEvent->dtstart->addHour(1);
        $persitentEvent->dtend->addHour(1);
        $updatedEvent = $this->_eventController->update($persitentEvent);
        
        // assert reschedule
    }
    
    public function testUpdateDetails()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $persitentEvent = $this->_eventController->create($event);
        
        $persitentEvent->summary = 'detail update notification has precedence over attendee update';
        $persitentEvent->url = 'http://somedetail.com';
        $persitentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        
        $updatedEvent = $this->_eventController->update($persitentEvent);
        
        // assert update
        // assert update
    }
        
    public function testUpdateAttendeeStatus()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $persitentEvent = $this->_eventController->create($event);
        
        $persitentEvent->attendee[1]->status = Calendar_Model_Attender::STATUS_DECLINED;
        
        $updatedEvent = $this->_eventController->update($persitentEvent);
    }
}

if (PHPUnit_MAIN_METHOD == 'Calendar_Controller_EventNotificationsTests::main') {
    Calendar_Controller_EventNotificationsTests::main();
}