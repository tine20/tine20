<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Controller_EventNotifications
 * 
 * @package     Tinebase
 */
class Tinebase_NotificationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Zend_Mail_Transport_Array
     */
    protected $_mailer = NULL;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $smtpConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::SMTP);
        if (empty($smtpConfig)) {
             $this->markTestSkipped('No SMTP config found: this is needed to send notifications.');
        }
        
        $this->_mailer = Tinebase_Smtp::getDefaultTransport();
    }
    
    /**
     * testNotificationWithSpecialCharContactName
     * 
     * @todo implement
     */
    public function testNotificationWithSpecialCharContactName()
    {
        $this->_flushMailer();
        
        // @todo create contact with special chars

        // @todo send notification
        
        // @todo check mail encoding
        
//        $event = $this->_getEvent();
//        $event->attendee = $this->_getPersonaAttendee('jsmith, pwulf, sclever, jmcblack, rwright');
//        
//        $this->_flushMailer();
//        $persistentEvent = $this->_eventController->create($event);
//        $this->_assertMail('jsmith', NULL);
//        $this->_assertMail('pwulf, sclever, jmcblack, rwright', 'invit');
//        
//        $this->_flushMailer();
//        $persistentEvent = $this->_eventController->delete($persistentEvent);
//        $this->_assertMail('jsmith', NULL);
//        $this->_assertMail('pwulf, sclever, jmcblack, rwright', 'cancel');
    }
    
    /**
     * flush mailer (send all remaining mails first)
     */
    protected function _flushMailer()
    {
        // make sure all messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(100);
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
//        // make sure messages are sent if queue is activated
//        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
//            Tinebase_ActionQueue::getInstance()->processQueue();
//        }
//        
//        foreach (explode(',', $_personas) as $personaName) {
//            $mailsForPersona = array();
//            $personaEmail = $this->_personas[trim($personaName)]->accountEmailAddress;
//            
//            foreach($this->_mailer->getMessages() as $message) {
//                if (array_value(0, $message->getRecipients()) == $personaEmail) {
//                    array_push($mailsForPersona, $message);
//                }
//            }
//            
//            if (! $_assertString) {
//                $this->assertEquals(0, count($mailsForPersona), 'No mail should be send for '. $personaName);
//            } else {
//                $this->assertEquals(1, count($mailsForPersona), 'One mail should be send for '. $personaName);
//                $subject = $mailsForPersona[0]->getSubject();
//                $this->assertTrue(FALSE !== strpos($subject, $_assertString), 'Mail subject for ' . $personaName . ' should contain "' . $_assertString . '" but '. $subject . ' is given');
//            }
//        }
    }
}
