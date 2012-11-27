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
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (empty($smtpConfig)) {
             $this->markTestSkipped('No SMTP config found: this is needed to send notifications.');
        }
        
        $this->_mailer = Tinebase_Smtp::getDefaultTransport();
    }
    
    /**
     * testNotificationWithSpecialCharContactName
     */
    public function testNotificationWithSpecialCharContactName()
    {
        $this->_mailer->flush();
        
        // create contact with special chars
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family'  => 'Hüßgen',
            'n_given'   => 'Silke',
            'email'     => 'unittest@tine20.org',
        )), FALSE);

        // send notification
        $subject = 'äöü unittest notification';
        $text = 'unittest notification text';
        Tinebase_Notification::getInstance()->send(Tinebase_Core::getUser(), array($contact), $subject, $text);
        
        // check mail (encoding)
        $messages = $this->_mailer->getMessages();
        $this->assertEquals(1, count($messages));
        $headers = $messages[0]->getHeaders();
        $this->assertEquals('=?UTF-8?Q?=C3=A4=C3=B6=C3=BC=20unittest=20notification?=', $headers['Subject'][0]);
        $this->assertEquals('"=?UTF-8?Q?Silke=20H=C3=BC=C3=9Fgen?=" <unittest@tine20.org>', $headers['To'][0]);
        $this->assertEquals('UTF-8', $messages[0]->getCharset());
    }
}
