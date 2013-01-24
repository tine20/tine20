<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for ActiveSync_Controller_Email
 * 
 * @package     Calendar
 */
class ActiveSync_Controller_EmailTests extends PHPUnit_Framework_TestCase
{
    /**
     * 
     * @var unknown_type
     */
    protected $_domDocument;
    
    /**
     * email test class for checking emails on IMAP server
     * 
     * @var Felamimail_Controller_MessageTest
     */
    protected $_emailTestClass;
    
    /**
     * test controller name
     * 
     * @var string
     */
    protected $_controllerName = 'ActiveSync_Controller_Email';
    
    /**
     * @var ActiveSync_Controller_Abstract controller
     */
    protected $_controller;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * xml output
     * 
     * @var string
     */
    protected $_testXMLOutput = '<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/"><Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Email="uri:Email"><Collections><Collection><Class>Email</Class><SyncKey>17</SyncKey><CollectionId>Inbox</CollectionId><Commands><Change><ClientId>1</ClientId><ApplicationData/></Change></Commands></Collection></Collections></Sync>';
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Controller Email Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * set up test environment
     */
    protected function setUp()
    {
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        if (empty($imapConfig) || !array_key_exists('useSystemAccount', $imapConfig) || $imapConfig['useSystemAccount'] != true) {
            $this->markTestSkipped('IMAP backend not configured');
        }
        $this->_testUser    = Tinebase_Core::getUser();
        
        $this->_emailTestClass = new Felamimail_Controller_MessageTest();
        $this->_emailTestClass->setup();
        $this->_createdMessages = new Tinebase_Record_RecordSet('Felamimail_Model_Message');
        
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->objects['devices'] = array();
        
        Syncroton_Registry::set(Syncroton_Registry::DEVICEBACKEND,       new Syncroton_Backend_Device(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::FOLDERBACKEND,       new Syncroton_Backend_Folder(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::SYNCSTATEBACKEND,    new Syncroton_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::CONTENTSTATEBACKEND, new Syncroton_Backend_Content(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set('loggerBackend',                         Tinebase_Core::getLogger());
        
        Syncroton_Registry::setContactsDataClass('ActiveSync_Controller_Contacts');
        Syncroton_Registry::setCalendarDataClass('ActiveSync_Controller_Calendar');
        Syncroton_Registry::setEmailDataClass('ActiveSync_Controller_Email');
        Syncroton_Registry::setTasksDataClass('ActiveSync_Controller_Tasks');
        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        if ($this->_emailTestClass instanceof Felamimail_Controller_MessageTest) {
            $this->_emailTestClass->tearDown();
        }
        
        Felamimail_Controller_Message_Flags::getInstance()->addFlags($this->_createdMessages, array(Zend_Mail_Storage::FLAG_DELETED));
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * validate getEntry
     */
    public function testGetEntry()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $message = $this->_createTestMessage();
        
        $syncrotonModelEmail = $controller->getEntry(
            new Syncroton_Model_SyncCollection(array('collectionId' => 'foobar', 'options' => array('bodyPreferences' => array('2' => array('type' => '2'))))), 
            $message->getId()
        );
        
        $this->assertEquals('9631', $syncrotonModelEmail->body->estimatedDataSize);
        #$this->assertEquals(2787, strlen(stream_get_contents($syncrotonFileReference->data)));
    }
    
    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testGetFileReference()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));
        
        $message = $this->_createTestMessage();
        
        $fileReference = $message->getId() . ActiveSync_Controller_Abstract::LONGID_DELIMITER . '2';
        
        $syncrotonFileReference = $controller->getFileReference($fileReference);
        
        $this->assertEquals('text/plain', $syncrotonFileReference->contentType);
        $this->assertEquals(2787, strlen(stream_get_contents($syncrotonFileReference->data)));
    }
    
    /**
     * create test message with $this->_emailTestClass->messageTestHelper()
     * 
     * @return Felamimail_Model_Message
     */
    protected function _createTestMessage()
    {
        $testMessageId = Tinebase_Record_Abstract::generateUID();
        
        $message = $this->_emailTestClass->messageTestHelper(
            'multipart_mixed.eml',
            $testMessageId,
            null,
            array('X-Tine20TestMessage: multipart/mixed', 'X-Tine20TestMessage: ' . $testMessageId)
        );
        
        return $message;
    }
    
    /**
     * test seen flag
     * 
     * @see 0007008: add test for seen flag
     */
    public function testMarkAsRead()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $message = $this->_createTestMessage();
        
        $controller->updateEntry(null, $message->getId(), new Syncroton_Model_Email(array('read' => 1)));
        
        $message = Felamimail_Controller_Message::getInstance()->get($message->getId());
        $this->assertEquals(array(Zend_Mail_Storage::FLAG_SEEN), $message->flags);
    }
    
    /**
     * test invalid chars
     */
    public function testInvalidBodyChars()
    {
        $device = $this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS);
        
        $controller = $this->_getController($device);
        
        $message = $this->_emailTestClass->messageTestHelper('invalid_body_chars.eml', 'invalidBodyChars');
        
        $syncrotonEmail = $controller->toSyncrotonModel($message, array('mimeSupport' => Syncroton_Command_Sync::MIMESUPPORT_SEND_MIME, 'bodyPreferences' => array(4 => array('type' => 4))));
        
        $syncrotonEmail->subject = "Hallo\x0E";
        
        $imp                   = new DOMImplementation();
        $dtd                   = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $testDoc               = $imp->createDocument('uri:AirSync', 'Sync', $dtd);
        $testDoc->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Syncroton', 'uri:Syncroton');
        $testDoc->formatOutput = true;
        $testDoc->encoding     = 'utf-8';
        
        $syncrotonEmail->appendXML($testDoc->documentElement, $device);
        
        #echo $testDoc->saveXML();
        
        $xml = $testDoc->saveXML();
        
        $this->assertEquals(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', null, $xml), $xml);
        
        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDoc);
    }
    
    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testToSyncrotonModel()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));
        
        $message = $this->_createTestMessage();
        $message->flags = array(
            Zend_Mail_Storage::FLAG_SEEN, 
            Zend_Mail_Storage::FLAG_ANSWERED
        );
        
        $syncrotonEmail = $controller->toSyncrotonModel($message, array('mimeSupport' => Syncroton_Command_Sync::MIMESUPPORT_SEND_MIME, 'bodyPreferences' => array(4 => array('type' => 4))));
        
        $this->assertEquals('[gentoo-dev] Automated Package Removal and Addition Tracker, for the week ending 2009-04-12 23h59 UTC', $syncrotonEmail->subject);
        // size of the body
        $this->assertEquals(9631, $syncrotonEmail->body->estimatedDataSize);
        // size of the attachment
        $this->assertEquals(2787, $syncrotonEmail->attachments[0]->estimatedDataSize);
        $this->assertEquals(Syncroton_Model_Email::LASTVERB_REPLYTOSENDER, $syncrotonEmail->lastVerbExecuted, 'reply flag missing');
    }
    
    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testToSyncrotonModelTruncated()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));
        
        $message = $this->_createTestMessage();
        
        $syncrotonEmail = $controller->toSyncrotonModel($message, array('mimeSupport' => Syncroton_Command_Sync::MIMESUPPORT_SEND_MIME, 'bodyPreferences' => array(4 => array('type' => 4, 'truncationSize' => 2000))));
        
        #foreach ($syncrotonEmail->body as $key => $value) {echo "$key => "; var_dump($value);}
        
        $this->assertEquals(1, $syncrotonEmail->body->truncated);
        $this->assertEquals(2000, strlen($syncrotonEmail->body->data));
    }
    
    /**
     * testSendEmail
     */
    public function testSendEmail()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_plain.eml');
        $email = str_replace('gentoo-dev@lists.gentoo.org, webmaster@changchung.org', $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace('gentoo-dev+bounces-35440-lars=kneschke.de@lists.gentoo.org', $this->_emailTestClass->getEmailAddress(), $email);
        
        $controller->sendEmail($email, true);
        
        // check if mail is in INBOX of test account
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'text/plain';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        $this->assertEquals("Re: [gentoo-dev] `paludis --info' is not like `emerge --info'", $message->subject);
        
        // check duplicate headers
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        $this->assertEquals(1, count($completeMessage->headers['mime-version']));
        $this->assertEquals(1, count($completeMessage->headers['content-type']));
    }
    
/**
     * testCalendarInvitation (should not be sent)
     * 
     * @see 0007568: do not send iMIP-messages via ActiveSync
     */
    public function testCalendarInvitation()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/iOSInvitation.eml');
        $email = str_replace('unittest@tine20.org', $this->_emailTestClass->getEmailAddress(), $email);
        $stream = fopen('data://text/plain;base64,' . base64_encode($email), 'r');
        
        $controller->sendEmail($email, true);
        
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'iOSInvitation.eml';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox, FALSE);
        
        $this->assertTrue(empty($message), 'message found: ' . var_export($message, TRUE));
    }
    
    /**
     * forward email test
     * 
     * @see 0007328: Answered flags were not synced by activesync
     * @see 0007456: add mail body on Forward via ActiveSync
     */
    public function testForwardEmail()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $originalMessage = $this->_createTestMessage();
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_plain.eml');
        $email = str_replace('gentoo-dev@lists.gentoo.org, webmaster@changchung.org', $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace('gentoo-dev+bounces-35440-lars=kneschke.de@lists.gentoo.org', $this->_emailTestClass->getEmailAddress(), $email);
        
        $controller->forwardEmail(array('collectionId' => 'foobar', 'itemId' => $originalMessage->getId()), $email, true, false);
        
        // check if mail is in INBOX of test account
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'text/plain';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        
        $this->assertEquals("Re: [gentoo-dev] `paludis --info' is not like `emerge --info'", $message->subject);
        $this->assertEquals(1, $message->has_attachment, 'attachment failure');
        
        // check duplicate headers
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        $this->assertEquals(1, count($completeMessage->headers['mime-version']));
        $this->assertEquals(1, count($completeMessage->headers['content-type']));
        
        // check forward flag
        $originalMessage = Felamimail_Controller_Message::getInstance()->get($originalMessage->getId());
        $this->assertTrue(in_array(Zend_Mail_Storage::FLAG_PASSED, $originalMessage->flags), 'forward flag missing in original message: ' . print_r($originalMessage->toArray(), TRUE));
        
        // check body
        $this->assertContains("The attached list notes all of the packages that were added or removed", $completeMessage->body);
    }
    
    /**
     * reply email test
     * 
     * @see 0007512: SmartReply with HTML message fails
     */
    public function testReplyEmail()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        $originalMessage = $this->_createTestMessage();
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_html.eml');
        $email = str_replace('gentoo-dev@lists.gentoo.org, webmaster@changchung.org', $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace('gentoo-dev+bounces-35440-lars=kneschke.de@lists.gentoo.org', $this->_emailTestClass->getEmailAddress(), $email);
        
        $controller->replyEmail($originalMessage->getId(), $email, FALSE, FALSE);
        
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'text_html.eml';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        
        $this->assertEquals("Re: [gentoo-dev] `paludis --info' is not like `emerge --info'", $message->subject);
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        $this->assertContains('Sebastian
The attached list notes all of the packages that were added or removed
from the tree, for the week ending 2009-04-12 23h59 UTC.', $completeMessage->body, 'reply body has not been appended correctly');
    }
    
    /**
     * validate getAllFolders
     */
    public function testGetAllFolders()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE));
        
        $folders = $controller->getAllFolders();
        
        $this->assertGreaterThanOrEqual(1, count($folders));
        $this->assertTrue(array_pop($folders) instanceof Syncroton_Model_Folder);
    }
    
    /**
     * test search for emails
     */
    public function testSearch()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE));
        
        $message = $this->_createTestMessage();
        
        $request = new Syncroton_Model_StoreRequest(array(
            'query' => array(
                'and' => array(
                    'freetext'     => 'Removal',
                    'classes'      => array('Email'),
                    'collections'  => array($this->_emailTestClass->getFolder()->getId())
                )
            ),
            'options' => array(
                'mimeSupport' => 0,
                'bodyPreferences' => array(
                    array(
                        'type' => 2,
                        'truncationSize' => 20000
                    )
                ),
                'range' => array(0,9)
            )
        ));
        
        $result = $controller->search($request);
    }
    
    /**
     * return active device
     * 
     * @param string $_deviceType
     * @return ActiveSync_Model_Device
     */
    protected function _getDevice($_deviceType)
    {
        if (isset($this->objects['devices'][$_deviceType])) {
            return $this->objects['devices'][$_deviceType];
        }
        
        $this->objects['devices'][$_deviceType] = Syncroton_Registry::getDeviceBackend()->create(
            ActiveSync_TestCase::getTestDevice($_deviceType)
        );

        return $this->objects['devices'][$_deviceType];
    }
    
    /**
     * get application activesync controller
     * 
     * @param ActiveSync_Model_Device $_device
     */
    protected function _getController(Syncroton_Model_IDevice $_device)
    {
        if ($this->_controller === null) {
            $this->_controller = new $this->_controllerName($_device, new Tinebase_DateTime(null, null, 'de_DE'));
        } 
        
        return $this->_controller;
    }
    
    /**
     * 
     * @return DOMDocument
     */
    #protected function _getOutputDOMDocument()
    #{
    #    $dom = new DOMDocument();
    #    $dom->formatOutput = false;
    #    $dom->encoding     = 'utf-8';
    #    $dom->loadXML($this->_testXMLOutput);
    #    #$dom->formatOutput = true; echo $dom->saveXML(); $dom->formatOutput = false;
    #    
    #    return $dom;
    #}
}
