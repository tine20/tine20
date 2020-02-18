<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Felamimail_Frontend_ActiveSync
 * 
 * @package     Felamimail
 */
class Felamimail_Frontend_ActiveSyncTest extends TestCase
{
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
    protected $_controllerName = 'Felamimail_Frontend_ActiveSync';
    
    /**
     * @var ActiveSync_Frontend_Abstract controller
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
        parent::setUp();
        
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        if (empty($imapConfig) || !(isset($imapConfig['useSystemAccount']) || array_key_exists('useSystemAccount', $imapConfig)) || $imapConfig['useSystemAccount'] != true) {
            $this->markTestSkipped('IMAP backend not configured');
        }
        $this->_testUser    = Tinebase_Core::getUser();

        $this->_emailTestClass = new Felamimail_Controller_MessageTest();
        $this->_emailTestClass->setup();
        $this->_createdMessages = new Tinebase_Record_RecordSet('Felamimail_Model_Message');
        
        $this->objects['devices'] = array();
        
        Syncroton_Registry::set(Syncroton_Registry::DEVICEBACKEND,       new Syncroton_Backend_Device(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::FOLDERBACKEND,       new Syncroton_Backend_Folder(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::SYNCSTATEBACKEND,    new Syncroton_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::CONTENTSTATEBACKEND, new Syncroton_Backend_Content(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set('loggerBackend',                         Tinebase_Core::getLogger());
        Syncroton_Registry::set(Syncroton_Registry::POLICYBACKEND,       new Syncroton_Backend_Policy(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        
        Syncroton_Registry::setContactsDataClass('Addressbook_Frontend_ActiveSync');
        Syncroton_Registry::setCalendarDataClass('Calendar_Frontend_ActiveSync');
        Syncroton_Registry::setEmailDataClass('Felamimail_Frontend_ActiveSync');
        Syncroton_Registry::setTasksDataClass('Tasks_Frontend_ActiveSync');
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
        
        parent::tearDown();
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
        
        $this->assertEquals('9661', $syncrotonModelEmail->body->estimatedDataSize);
    }

    /**
     * validate getEntry with an Emoji
     */
    public function testGetEntryWithEmoji()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
    
        $message = $this->_createTestMessage('emoji.eml', 'emoji.eml');
    
        $syncrotonModelEmail = $controller->getEntry(
                new Syncroton_Model_SyncCollection(array('collectionId' => 'foobar', 'options' => array('bodyPreferences' => array('2' => array('type' => '2'))))),
                $message->getId()
        );
        
        $this->assertEquals('1744', $syncrotonModelEmail->body->estimatedDataSize);
    }

    /**
     * validate getEntry with winmail.dat
     */
    public function testGetEntryWithWinmailDat()
    {
        if (! Tinebase_Core::systemCommandExists('tnef') && ! Tinebase_Core::systemCommandExists('ytnef')) {
            $this->markTestSkipped('The (y)tnef command could not be found!');
        }

        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));

        $message = $this->_createTestMessage('winmail_dat_attachment.eml', 'winmail_dat_attachment.eml');

        $syncrotonModelEmail = $controller->getEntry(
            new Syncroton_Model_SyncCollection(array('collectionId' => 'foobar', 'options' => array('bodyPreferences' => array('2' => array('type' => '2'))))),
            $message->getId()
        );

        $path = Tinebase_Core::getTempDir() . '/winmail/' . $message->getId() . '/';
        $content = file_get_contents($path . 'bookmark.htm');
        $dataSize = strlen($content);
        $this->assertStringStartsWith('<!DOCTYPE NETSCAPE-Bookmark-file-1>', $content);

        self::assertEquals(2, count($syncrotonModelEmail->attachments), print_r($syncrotonModelEmail->attachments, true));
        $this->assertEquals($dataSize, $syncrotonModelEmail->attachments[0]->estimatedDataSize);

        // try to get file by reference
        $syncrotonFileReference = $controller->getFileReference($syncrotonModelEmail->attachments[0]->fileReference);
        $this->assertEquals('text/html', $syncrotonFileReference->contentType);
        $this->assertEquals($dataSize, strlen(stream_get_contents($syncrotonFileReference->data)));
    }

    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testGetFileReference()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));
        
        $message = $this->_createTestMessage();
        
        $fileReference = $message->getId() . ActiveSync_Frontend_Abstract::LONGID_DELIMITER . '2';
        
        $syncrotonFileReference = $controller->getFileReference($fileReference);
        
        $this->assertEquals('text/plain', $syncrotonFileReference->contentType);
        $this->assertEquals(2787, strlen(stream_get_contents($syncrotonFileReference->data)));
    }
    
    /**
     * create test message with $this->_emailTestClass->messageTestHelper()
     * 
     * @return Felamimail_Model_Message
     */
    protected function _createTestMessage($emailFile = 'multipart_mixed.eml', $headerToReplace = 'multipart/mixed')
    {
        $testMessageId = Tinebase_Record_Abstract::generateUID();
        
        $message = $this->_emailTestClass->messageTestHelper(
            $emailFile,
            $testMessageId,
            null,
            array('X-Tine20TestMessage: ' . $headerToReplace, 'X-Tine20TestMessage: ' . $testMessageId)
        );
        
        return $message;
    }

    public function testDeleteMessageToTrashWithTrashDeleted()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        $message = $this->_createTestMessage();

        // delete trash folder
        $folder  = Felamimail_Controller_Folder::getInstance()->get($message->folder_id);
        $trashFolder = Felamimail_Controller_Account::getInstance()->getSystemFolder($folder->account_id,
            Felamimail_Model_Folder::FOLDER_TRASH);
        Felamimail_Controller_Folder::getInstance()->delete($folder->account_id, $trashFolder->globalname);

        $xml = simplexml_load_string('<Collection><DeletesAsMoves>1</DeletesAsMoves></Collection>');
        $syncCol = new Syncroton_Model_SyncCollection();
        $syncCol->setFromSimpleXMLElement($xml);

        $controller->deleteEntry($message->folder_id, $message->getId(), $syncCol);
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
        
        self::encodeXml($testDoc);
    }

    /**
     * try to encode XML until we have wbxml tests
     *
     * @param $testDoc
     * @return string returns encoded/decoded xml string
     */
    public static function encodeXml($testDoc)
    {
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDoc);

        rewind($outputStream);
        $decoder = new Syncroton_Wbxml_Decoder($outputStream);
        $xml = $decoder->decode();

        return $xml->saveXML();
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
        $this->assertEquals(9661, $syncrotonEmail->body->estimatedDataSize);
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
        // add account signature
        $account = $this->_getTestUserFelamimailAccount();
        $account->signatures = new Tinebase_Record_RecordSet(Felamimail_Model_Signature::class, [[
            'signature' => 'my special signature',
            'is_default' => 1,
            'name' => 'my sig',
            'id' => Tinebase_Record_Abstract::generateUID(), // client also sends some random uuid
            'notes' => []
        ]]);
        Felamimail_Controller_Account::getInstance()->update($account);

        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_plain.eml');
        $email = str_replace('gentoo-dev@lists.gentoo.org, webmaster@changchung.org',
            $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace('gentoo-dev+bounces-35440-lars=kneschke.de@lists.gentoo.org',
            $this->_emailTestClass->getEmailAddress(), $email);
        
        $controller->sendEmail($email, true);
        
        // check if mail is in INBOX of test account
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'text/plain';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        $this->assertEquals("Re: [gentoo-dev] `paludis --info' is not like `emerge --info'", $message->subject);

        // check duplicate headers
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);

        self::assertTrue(is_array($completeMessage->headers), 'headers are no array: '
            . print_r($completeMessage->toArray(), true));
        self::assertEquals('1.0', $completeMessage->headers['mime-version']);
        self::assertEquals('text/plain; charset=ISO-8859-1', $completeMessage->headers['content-type']);

        // check signature
        self::assertContains('my special signature', $completeMessage->body);
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
     * Test whether Base64Decoded Messages can be send or not
     * 
     * @see 0008572: email reply text garbled
     */
    public function testSendBase64DecodedMessage()
    {
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
     * Test whether Base64Decoded Messages can be send or not
     *
     * @see 0012320: Too much linebreaks using Nine Client
     */
    public function testSendBase64DecodedMessageNine()
    {
        $messageId = '<j4wxaho1t8ggvk5cef7kqc6i.1373048280847@email.android.com>';

        $email = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SendMail xmlns="uri:ComposeMail">
  <ClientId>36d4de51-539a-4dd3-a54f-7891e5bf053a-1</ClientId>
  <SaveInSentItems/>
  <Mime>Date: Thu, 01 Dec 2016 14:30:32 +0100&#13;
Subject: Test Mail&#13;
Message-ID: ' . htmlspecialchars($messageId) . '&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
MIME-Version: 1.0&#13;
Content-Type: multipart/alternative; boundary=--_com.ninefolders.hd3.email_118908611723655_alt&#13;
&#13;
----_com.ninefolders.hd3.email_118908611723655_alt&#13;
Content-Type: text/plain; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
SGksClBsZWFzZSBUaW5lMjAgYW5zd2VyIG1lLgpCZXN0CgoKCg==&#13;
----_com.ninefolders.hd3.email_118908611723655_alt&#13;
Content-Type: text/html; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
PGRpdiBzdHlsZT0iZm9udC1mYW1pbHk6SGVsdmV0aWNhLCBBcmlhbCwgc2Fucy1zZXJpZjsgZm9u&#13;
dC1zaXplOjEyLjBwdDsgbGluZS1oZWlnaHQ6MS4zOyBjb2xvcjojMDAwMDAwIj5IaSw8YnI+UGxl&#13;
YXNlIFRpbmUyMCBhbnN3ZXIgbWUuPGJyPkJlc3Q8YnI+PGJyPjxkaXYgaWQ9InNpZ25hdHVyZS14&#13;
IiBzdHlsZT0iLXdlYmtpdC11c2VyLXNlbGVjdDpub25lOyBmb250LWZhbWlseTpIZWx2ZXRpY2Es&#13;
IEFyaWFsLCBzYW5zLXNlcmlmOyBmb250LXNpemU6MTIuMHB0OyBjb2xvcjojMDAwMDAwIiBjbGFz&#13;
cyA9ICJzaWduYXR1cmVfZWRpdG9yIj48ZGl2Pjxicj48L2Rpdj48L2Rpdj48L2Rpdj4gPGJyIHR5&#13;
cGU9J2F0dHJpYnV0aW9uJz4=&#13;
----_com.ninefolders.hd3.email_118908611723655_alt--&#13;
</Mime>
</SendMail>';

        $stringToCheck = 'Please Tine20 answer me.';

        $this->_sendMailTestHelper($email, $messageId, $stringToCheck, "Syncroton_Command_SendMail");
    }

    /**
     * Test whether Base64Decoded Messages can be send or not
     *
     * TODO reply?
     * @see TODO add mantis
     */
    public function testSendBase64EncodedMessage ()
    {
        $messageId = '<j4wxaho1t8ggvk5cef7kqc6i.1373048280847@email.ipad>';

        $email = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SendMail xmlns="uri:ComposeMail">
  <ClientId>C3918F20-DAEA-48CD-963D-56E2CA6EC013</ClientId>
  <SaveInSentItems/>
  <Mime>Content-Type: multipart/signed;&#13;
	boundary=Apple-Mail-3CB7E652-FD9A-4AAF-B60E-B7101AC3752F;&#13;
	protocol="application/pkcs7-signature";&#13;
	micalg=sha1&#13;
Content-Transfer-Encoding: 7bit&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
Mime-Version: 1.0 (1.0)&#13;
Subject: Re: testmail&#13;
Message-ID: ' . htmlspecialchars($messageId) . '&#13;
Date: Thu, 12 Jan 2017 11:07:13 +0100&#13;
&#13;
&#13;
--Apple-Mail-3CB7E652-FD9A-4AAF-B60E-B7101AC3752F&#13;
Content-Type: text/plain;&#13;
	charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
PGRpdiBzdHlsZT0iZm9udC1mYW1pbHk6SGVsdmV0aWNhLCBBcmlhbCwgc2Fucy1zZXJpZjsgZm9u&#13;
dC1zaXplOjEyLjBwdDsgbGluZS1oZWlnaHQ6MS4zOyBjb2xvcjojMDAwMDAwIj5IaSw8YnI+UGxl&#13;
YXNlIFRpbmUyMCBhbnN3ZXIgbWUuPGJyPkJlc3Q8YnI+PGJyPjxkaXYgaWQ9InNpZ25hdHVyZS14&#13;
IiBzdHlsZT0iLXdlYmtpdC11c2VyLXNlbGVjdDpub25lOyBmb250LWZhbWlseTpIZWx2ZXRpY2Es&#13;
IEFyaWFsLCBzYW5zLXNlcmlmOyBmb250LXNpemU6MTIuMHB0OyBjb2xvcjojMDAwMDAwIiBjbGFz&#13;
cyA9ICJzaWduYXR1cmVfZWRpdG9yIj48ZGl2Pjxicj48L2Rpdj48L2Rpdj48L2Rpdj4gPGJyIHR5&#13;
cGU9J2F0dHJpYnV0aW9uJz4=&#13;
&#13;
--Apple-Mail-3CB7E652-FD9A-4AAF-B60E-B7101AC3752F&#13;
Content-Type: application/pkcs7-signature;&#13;
	name=smime.p7s&#13;
Content-Disposition: attachment;&#13;
	filename=smime.p7s&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
PGRpdiBzdHlsZT0iZm9udC1mYW1pbHk6SGVsdmV0aWNhLCBBcmlhbCwgc2Fucy1zZXJpZjsgZm9u&#13;
dC1zaXplOjEyLjBwdDsgbGluZS1oZWlnaHQ6MS4zOyBjb2xvcjojMDAwMDAwIj5IaSw8YnI+UGxl&#13;
YXNlIFRpbmUyMCBhbnN3ZXIgbWUuPGJyPkJlc3Q8YnI+PGJyPjxkaXYgaWQ9InNpZ25hdHVyZS14&#13;
IiBzdHlsZT0iLXdlYmtpdC11c2VyLXNlbGVjdDpub25lOyBmb250LWZhbWlseTpIZWx2ZXRpY2Es&#13;
IEFyaWFsLCBzYW5zLXNlcmlmOyBmb250LXNpemU6MTIuMHB0OyBjb2xvcjojMDAwMDAwIiBjbGFz&#13;
cyA9ICJzaWduYXR1cmVfZWRpdG9yIj48ZGl2Pjxicj48L2Rpdj48L2Rpdj48L2Rpdj4gPGJyIHR5&#13;
cGU9J2F0dHJpYnV0aW9uJz4=&#13;
--Apple-Mail-3CB7E652-FD9A-4AAF-B60E-B7101AC3752F--&#13;
</Mime>
</SendMail>';

        $stringToCheck = 'Please Tine20 answer me.';

        $this->_sendMailTestHelper($email, $messageId, $stringToCheck, "Syncroton_Command_SendMail", Syncroton_Model_Device::TYPE_IPHONE);
    }

    /**
     * @see 0011556: sending mails to multiple recipients fails
     */
    public function testSendMessageToMultipleRecipients()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));

        $messageId = '<j5wxaho1t8ggvk5cef7kqc6i.1373048280847@email.android.com>';

        $email = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SendMail xmlns="uri:ComposeMail">
  <ClientId>SendMail-158383807994574</ClientId>
  <SaveInSentItems/>
  <Mime>Date: Fri, 05 Jul 2013 20:18:00 +0200&#13;
Subject: Fgh&#13;
Message-ID: ' . htmlspecialchars($messageId) . '&#13;
From: l.kneschke@metaways.de&#13;
To: ' . $this->_emailTestClass->getEmailAddress() . ', ' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
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
        $this->assertEquals(1, count((array)$completeMessage->headers['mime-version']));
        $this->assertEquals(1, count((array)$completeMessage->headers['content-type']));
        
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
     * @see 0009390: linebreaks missing when replying or forwarding mail
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
The attached list notes all of the packages that were added or removed<br />from the tree, for the week ending 2009-04-12 23h59 UTC.<br />', $completeMessage->body,
            'reply body has not been appended correctly');
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
     * @return Felamimail_Model_Message
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

        //echo $completeMessage->body;
        $this->assertContains($stringToCheck, $completeMessage->body);
        return $completeMessage;
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
        
        $this->assertGreaterThanOrEqual(5, count($folders));
        $foundFolderTypes = array();
        foreach ($folders as $folder) {
            $foundFolderTypes[] = $folder->type;
        }
        $this->assertContains(Syncroton_Command_FolderSync::FOLDERTYPE_DRAFTS,       $foundFolderTypes, 'Drafts folder missing:' . print_r($foundFolderTypes, TRUE));
        $this->assertContains(Syncroton_Command_FolderSync::FOLDERTYPE_DELETEDITEMS, $foundFolderTypes, 'Trash folder missing:' .  print_r($foundFolderTypes, TRUE));
        $this->assertContains(Syncroton_Command_FolderSync::FOLDERTYPE_SENTMAIL,     $foundFolderTypes, 'Sent folder missing:' .   print_r($foundFolderTypes, TRUE));
        $this->assertContains(Syncroton_Command_FolderSync::FOLDERTYPE_OUTBOX,       $foundFolderTypes, 'Outbox folder missing:' . print_r($foundFolderTypes, TRUE));
        
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
     * testGetCountOfChanges for fake folder (outbox)
     */
    public function testGetCountOfChangesFakeFolder()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_IPHONE));
        
        $numberOfChanges = $controller->getCountOfChanges(
            Syncroton_Registry::getContentStateBackend(), 
            new Syncroton_Model_Folder(array(
                'id'             => Tinebase_Record_Abstract::generateUID(),
                'serverId'       => 'fake-' . Syncroton_Command_FolderSync::FOLDERTYPE_OUTBOX,
                'lastfiltertype' => Syncroton_Command_Sync::FILTER_NOTHING
            )), 
            new Syncroton_Model_SyncState(array(
                'lastsync' => Tinebase_DateTime::now()->subHour(1)
            ))
        );
        
        $this->assertEquals(0, $numberOfChanges);
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

    /**
     * check if recipient addresses are split correctly
     */
    public function testSendMailToRecipientsWithComma()
    {
        $messageId = '<2248dca3-809b-4bb9-8643-2e732c43e639@email.android.com>';
      
        $email = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<SendMail xmlns="uri:ComposeMail">
  <ClientId>SendMail-519120675237184</ClientId>
  <SaveInSentItems/>
  <Mime>Date: Thu, 01 Nov 2018 13:52:55 +0100&#13;
Subject: =?UTF-8?Q?10_=E2=82=AC_geliehen?=&#13;
Message-ID: ' . htmlspecialchars($messageId) . '&#13;
X-Android-Message-ID: &lt;2248dca3-809b-4bb9-8643-2e732c43e639@email.android.com&gt;&#13;
In-Reply-To: &lt;6c62aeff-b1f7-4d45-a9a7-443b5764be21@email.android.com&gt;&#13;
From: p.schuele@metaways.de&#13;
To: =?ISO-8859-1?Q?Sch=FCle=2C_Philipp?= &lt;' . $this->_emailTestClass->getEmailAddress() . '&gt;, some&#13;
 one &lt;' . $this->_emailTestClass->getEmailAddress() . '&gt;&#13;
Importance: Normal&#13;
X-Priority: 3&#13;
X-MSMail-Priority: Normal&#13;
MIME-Version: 1.0&#13;
Content-Type: text/html; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
PGRpdiBkaXI9J2F1dG8nPjxkaXY+PGJyPjxkaXYgY2xhc3M9ImdtYWlsX3F1b3RlIj4tLS0tLS0t&#13;
LS0tIFdlaXRlcmdlbGVpdGV0ZSBOYWNocmljaHQgLS0tLS0tLS0tLTxicj5Wb246IHAuc2NodWVs&#13;
ZUBtZXRhd2F5cy5kZTxicj5EYXR1bTogMzAuMTAuMjAxOCAxMjoxNTxicj5CZXRyZWZmOiAxMCDi&#13;
gqwgZ2VsaWVoZW48YnI+QW46IENocmlzdGlhbiBGZWl0bCAmbHQ7Yy5mZWl0bEBtZXRhd2F5cy5k&#13;
ZSZndDssIlNjaMO8bGUsIFBoaWxpcHAiICZsdDtwLnNjaHVlbGVAbWV0YXdheXMuZGUmZ3Q7PGJy&#13;
PkNjOiA8YnI+PGJyIHR5cGU9ImF0dHJpYnV0aW9uIj48YmxvY2txdW90ZSBjbGFzcz0icXVvdGUi&#13;
IHN0eWxlPSJtYXJnaW46MCAwIDAgLjhleDtib3JkZXItbGVmdDoxcHggI2NjYyBzb2xpZDtwYWRk&#13;
aW5nLWxlZnQ6MWV4Ij48ZGl2IGRpcj0iYXV0byI+PC9kaXY+PC9ibG9ja3F1b3RlPjwvZGl2Pjxi&#13;
cj48L2Rpdj48L2Rpdj4=&#13;
</Mime>
</SendMail>';

        $stringToCheck = 'geliehen';

        $message = $this->_sendMailTestHelper($email, $messageId, $stringToCheck, "Syncroton_Command_SendMail");
        self::assertEquals(1, count($message->to), 'message should have 1 recipient: ' . print_r($message->to, true));
    }
}
