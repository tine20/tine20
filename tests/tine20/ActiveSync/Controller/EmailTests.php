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
     * 
     * @todo move setup to abstract test case
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
        Syncroton_Registry::set(Syncroton_Registry::POLICYBACKEND,       new Syncroton_Backend_Policy(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        
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
        Felamimail_Controller_Message::getInstance()->delete($this->_createdMessages->getArrayOfIds());
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
     * 
     * @group longrunning
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
     * testSendEmailAndroid
     * 
     * @see 0008844: Mails sent without content (NIL)
     */
    public function testSendEmailAndroid()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/Android.eml');
        $email = str_replace('p.schuele@metaways.de', $this->_emailTestClass->getEmailAddress(), $email);
        
        $controller->sendEmail($email, true);
        
        // check if mail is in INBOX of test account
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'Android.eml';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        $this->assertEquals("Test", $message->subject);
        
        // check content
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        $this->assertContains('Test', $completeMessage->body);
    }
    
    /**
     * Test wether Base64Decoded Messages can be send or not
     * 
     * @see 0008572: email reply text garbled
     */
    public function testSendBase64DecodedMessage () {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $messageId = '<j4wxaho1t8ggvk5cef7kqc6i.1373048280847@email.android.com>';
        
        $email = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SendMail xmlns="uri:ComposeMail">
  <ClientId>SendMail-158383807994574</ClientId>
  <SaveInSentItems/>
  <Mime>Date: Fri, 05 Jul 2013 20:18:00 +0200&#13;
Subject: Fgh&#13;
Message-ID: ' . htmlspecialchars($messageId) . '&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
MIME-Version: 1.0&#13;
Content-Type: text/plain; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
dGVzdAo=&#13;
</Mime>
</SendMail>';
        
        $stringToCheck = 'test';
        
        $this->_sendMailTestHelper($email, $messageId, $stringToCheck, "Syncroton_Command_SendMail");
    }
    
    /**
     * testCalendarInvitation (should not be sent)
     * 
     * @see 0007568: do not send iMIP-messages via ActiveSync
     * 
     * @group longrunning
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
     * testReplyEmailNexus
     * 
     * @see 0008572: email reply text garbled
     * 
     * @group longrunning
     */
    public function testReplyEmailNexus1()
    {
        $originalMessage = $this->_createTestMessage();
        
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SmartReply xmlns="uri:ComposeMail">
  <ClientId>SendMail-78543534540370</ClientId>
  <SaveInSentItems/>
  <Source>
    <ItemId>' . $originalMessage->getId() . '</ItemId>
    <FolderId>' . $originalMessage->folder_id .  '</FolderId>
  </Source>
  <Mime>Date: Fri, 05 Jul 2013 09:14:15 +0200&#13;
Subject: Re: email test&#13;
Message-ID: &lt;hw6umldu85v6efjai6i9vqci.1373008455202@email.android.com&gt;&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
MIME-Version: 1.0&#13;
X-Tine20TestMessage: smartreply.eml&#13;
Content-Type: text/plain; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
TW9pbiEKCk1hbCB3YXMgbWl0IMOWIQoKTGFycwoKUGhpbGlwcCBTY2jDvGxlIDxwLnNjaHVlbGVA&#13;
bWV0YXdheXMuZGU+IHNjaHJpZWI6Cgo=&#13;
</Mime>
</SmartReply>';
        $messageId = '<hw6umldu85v6efjai6i9vqci.1373008455202@email.android.com>';
        $stringToCheck = 'Mal was mit Ö!';
        
        $this->_sendMailTestHelper($xml, $messageId, $stringToCheck);
    }
    
    /**
     * testReplyEmailNexus
     * 
     * @see 0008572: email reply text garbled
     */
    public function testReplyEmailNexus2()
    {
        $originalMessage = $this->_createTestMessage();
        
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SmartReply xmlns="uri:ComposeMail">
  <ClientId>SendMail-90061070551109</ClientId>
  <SaveInSentItems/>
  <Source>
    <ItemId>' . $originalMessage->getId() . '</ItemId>
    <FolderId>' . $originalMessage->folder_id .  '</FolderId>
  </Source>
  <Mime>Date: Fri, 05 Jul 2013 13:14:19 +0200&#13;
Subject: Re: email test&#13;
Message-ID: &lt;xs9f5842m44v6exce8v8swox.1373022859201@email.android.com&gt;&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&#13;
MIME-Version: 1.0&#13;
Content-Type: text/plain; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
TGFycyBsw7ZzY2h0IG5peC4uLgoKV2lya2xpY2ghCgpQaGlsaXBwIFNjaMO8bGUgPHAuc2NodWVs&#13;
ZUBtZXRhd2F5cy5kZT4gc2NocmllYjoKCg==&#13;
</Mime>
</SmartReply>';
        $messageId = '<xs9f5842m44v6exce8v8swox.1373022859201@email.android.com>';
        
        $stringToCheck = 'Lars löscht nix...';
        
        $this->_sendMailTestHelper($xml, $messageId, $stringToCheck);
    }
    
    /**
     * _sendMailTestHelper
     * 
     * @param string $xml
     * @param string $messageId
     * @param string $stringToCheck
     * @param string $command
     * @param string $device
     */
    protected function _sendMailTestHelper($xml, $messageId, $stringToCheck, $command = "Syncroton_Command_SmartReply", $device = Syncroton_Model_Device::TYPE_ANDROID_40)
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $device = $this->_getDevice($device);
        $sync = new $command($doc, $device, $device->policykey);
        
        $sync->handle();
        $sync->getResponse();
        
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $message = $this->_emailTestClass->searchAndCacheMessage($messageId, $inbox, TRUE, 'Message-ID');
        $this->_createdMessages->addRecord($message);
        
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        
        $this->assertContains($stringToCheck, $completeMessage->body);
    }
    
    /**
     * testForwardEmailiPhone
     * 
     * @see 0008572: email reply text garbled
     */
    public function testForwardEmailiPhone()
    {
        $originalMessage = $this->_createTestMessage();
        
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SmartForward xmlns="uri:ComposeMail">
  <ClientId>1F7C3F2D-B920-404F-97FE-27FE721A9E08</ClientId>
  <SaveInSentItems/>
  <ReplaceMime/>
  <Source>
    <FolderId>' . $originalMessage->folder_id .  '</FolderId>
    <ItemId>' . $originalMessage->getId() . '</ItemId>
  </Source>
  <Mime>Content-Type: multipart/alternative;&#13;
        boundary=Apple-Mail-31383BDF-6B42-495A-89DE-A608A255C644&#13;
Content-Transfer-Encoding: 7bit&#13;
Subject: Fwd: AW: Termin&#13;
From: l.kneschke@metaways.de&#13;
Message-Id: &lt;1F7C3F2D-B920-404F-97FE-27FE721A9E08@tine20.org&gt;&#13;
Date: Wed, 7 Aug 2013 15:27:46 +0200&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&#13;
Mime-Version: 1.0 (1.0)&#13;
&#13;
&#13;
--Apple-Mail-31383BDF-6B42-495A-89DE-A608A255C644&#13;
Content-Type: text/plain;&#13;
        charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
TGFycyBsw7ZzY2h0IG5peC4uLgoKV2lya2xpY2ghCgpQaGlsaXBwIFNjaMO8bGUgPHAuc2NodWVs&#13;
ZUBtZXRhd2F5cy5kZT4gc2NocmllYjoKCg==&#13;
&#13;
--Apple-Mail-31383BDF-6B42-495A-89DE-A608A255C644&#13;
Content-Type: text/html;&#13;
        charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
TGFycyBsw7ZzY2h0IG5peC4uLgoKV2lya2xpY2ghCgpQaGlsaXBwIFNjaMO8bGUgPHAuc2NodWVs&#13;
ZUBtZXRhd2F5cy5kZT4gc2NocmllYjoKCg==&#13;
--Apple-Mail-31383BDF-6B42-495A-89DE-A608A255C644--&#13;
</Mime>
</SmartForward>';
        $messageId = '<1F7C3F2D-B920-404F-97FE-27FE721A9E08@tine20.org>';
        
        $stringToCheck = 'Lars löscht nix...';
        
        $this->_sendMailTestHelper($xml, $messageId, $stringToCheck, 'Syncroton_Command_SmartForward', Syncroton_Model_Device::TYPE_IPHONE);
    }
    
    /**
     * validate getAllFolders
     * 
     * @see 0007206: ActiveSync doesn't show all folder tree until it's fully viewed in web-interface
     */
    public function testGetAllFolders()
    {
        // create a subfolder of INBOX
        $emailAccount = Felamimail_Controller_Account::getInstance()->search()->getFirstRecord();
        try {
            $subfolder = Felamimail_Controller_Folder::getInstance()->create($emailAccount->getId(), 'sub', 'INBOX');
        } catch (Zend_Mail_Storage_Exception $zmse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " " . $zmse);
        }
        
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE));
        
        $folders = $controller->getAllFolders();
        
        $this->assertGreaterThanOrEqual(1, count($folders));
        $this->assertTrue(array_pop($folders) instanceof Syncroton_Model_Folder);
        
        // look for 'INBOX/sub'
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $found = FALSE;
        $foundFolders = array();
        foreach ($folders as $folder) {
            $foundFolders[] = $folder->displayName;
            if ($folder->displayName === 'sub' && $folder->parentId === $inbox->getId()) {
                $found = TRUE;
                break;
            }
        }
        
        try {
            Felamimail_Controller_Folder::getInstance()->delete($emailAccount->getId(), 'INBOX/sub');
        } catch (Felamimail_Exception_IMAPFolderNotFound $feifnf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " " . $feifnf);
        }
        $this->assertTrue($found, 'could not find INBOX/sub with getAllFolders(): ' . print_r($foundFolders, TRUE));
    }
    
    /**
     * test if changed folders got returned
     * 
     * @see 0007786: changed email folder names do not sync to device
     * 
     * @todo implement
     */
    public function testGetChangedFolders()
    {
        $this->markTestIncomplete('not yet implemented in controller/felamimail');
        
        $syncrotonFolder = $this->testUpdateFolder();
        
        $controller = Syncroton_Data_Factory::factory($this->_class, $this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        $changedFolders = $controller->getChangedFolders(Tinebase_DateTime::now()->subMinute(1), Tinebase_DateTime::now());
        
        //var_dump($changedFolders);
        
        $this->assertEquals(1, count($changedFolders));
        $this->assertArrayHasKey($syncrotonFolder->serverId, $changedFolders);
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
     * testGetCountOfChanges (inbox folder cache should be updated here by _inspectGetServerEntries fn)
     * 
     * @see 0006232: Emails get only synched, if the user is logged on with an browser
     */
    public function testGetCountOfChanges()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE));
        
        // set inbox timestamp a long time ago (15 mins)
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $inbox->cache_timestamp = Tinebase_DateTime::now()->subMinute(15);
        $folderBackend = new Felamimail_Backend_Folder();
        $folderBackend->update($inbox);
        
        $numberOfChanges = $controller->getCountOfChanges(
            Syncroton_Registry::getContentStateBackend(), 
            new Syncroton_Model_Folder(array(
                'id'             => Tinebase_Record_Abstract::generateUID(),
                'serverId'       => $inbox->getId(),
                'lastfiltertype' => Syncroton_Command_Sync::FILTER_NOTHING
            )), 
            new Syncroton_Model_SyncState(array(
                'lastsync' => Tinebase_DateTime::now()->subHour(1)
            ))
        );
        
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        
        $this->assertEquals(1, $inbox->cache_timestamp->compare(Tinebase_DateTime::now()->subSecond(15)), 'inbox cache has not been updated: ' . print_r($inbox, TRUE));
    }

    /**
     * testSendMailWithoutSubject
     * 
     * @see 0007870: Can't send mail without subject
     */
    public function testSendMailWithoutSubject()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_plain.eml');
        $email = str_replace('gentoo-dev@lists.gentoo.org, webmaster@changchung.org', $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace('gentoo-dev+bounces-35440-lars=kneschke.de@lists.gentoo.org', $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace("Subject: Re: [gentoo-dev] `paludis --info' is not like `emerge --info'\n", '', $email);
        
        $controller->sendEmail($email, true);
        
        // check if mail is in INBOX of test account
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'text/plain';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        $this->assertTrue(empty($message->subject));
    }
}
