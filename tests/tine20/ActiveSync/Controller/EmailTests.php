<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Config::IMAP);
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
     * validate fetching email by filereference(hashid-partid)
     */
    public function testGetFileReference()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));
        
        $message = $this->_emailTestClass->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        
        $fileReference = $message->getId() . '-2';
        
        $syncrotonFileReference = $controller->getFileReference($fileReference);
        
        $this->assertEquals('text/plain', $syncrotonFileReference->contentType);
        $this->assertEquals(2787, strlen(stream_get_contents($syncrotonFileReference->data)));
    }
    
    /**
     * test invalid chars
     */
    public function _testInvalidBodyChars()
    {
        //invalid_body_chars.eml
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));
        
        $message = $this->_emailTestClass->messageTestHelper('invalid_body_chars.eml', 'invalidBodyChars');
        
        $options = array();
        $properties = $this->_domDocument->createElementNS('uri:ItemOperations', 'Properties');
        $controller->appendXML($properties, $message->folder_id, $message->getId(), $options);
        $this->_domDocument->documentElement->appendChild($properties);
        
        $this->_domDocument->formatOutput = true;
        $xml = $this->_domDocument->saveXML();
        
        $this->assertEquals(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', null, $xml), $xml);
    }
    
    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testToSyncrotonModel()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));
        
        $message = $this->_emailTestClass->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        
        $syncrotonEmail = $controller->toSyncrotonModel($message, array('mimeSupport' => Syncroton_Command_Sync::MIMESUPPORT_SEND_MIME, 'bodyPreferences' => array(4 => array('type' => 4))));
        
        #foreach ($syncrotonEmail as $key => $value) {echo "$key => "; var_dump($value);}
        
        $this->assertEquals('[gentoo-dev] Automated Package Removal and Addition Tracker, for the week ending 2009-04-12 23h59 UTC', $syncrotonEmail->subject);
        // size of the body
        $this->assertEquals(9606, $syncrotonEmail->body->estimatedDataSize);
        // size of the attachment
        $this->assertEquals(2787, $syncrotonEmail->attachments[0]->estimatedDataSize);
    }
    
    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testToSyncrotonModelTruncated()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_WEBOS));
        
        $message = $this->_emailTestClass->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        
        $syncrotonEmail = $controller->toSyncrotonModel($message, array('mimeSupport' => Syncroton_Command_Sync::MIMESUPPORT_SEND_MIME, 'bodyPreferences' => array(4 => array('type' => 4, 'truncationSize' => 2000))));
        
        #foreach ($syncrotonEmail->body as $key => $value) {echo "$key => "; var_dump($value);}
        
        $this->assertEquals(1, $syncrotonEmail->body->truncated);
        $this->assertEquals(2000, strlen($syncrotonEmail->body->data));
    }
    
    public function testSendEmail()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        #$message = $this->_emailTestClass->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        
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
    
    public function testForwardEmail()
    {
        $controller = $this->_getController($this->_getDevice(Syncroton_Model_Device::TYPE_ANDROID_40));
        
        $message = $this->_emailTestClass->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_plain.eml');
        $email = str_replace('gentoo-dev@lists.gentoo.org, webmaster@changchung.org', $this->_emailTestClass->getEmailAddress(), $email);
        $email = str_replace('gentoo-dev+bounces-35440-lars=kneschke.de@lists.gentoo.org', $this->_emailTestClass->getEmailAddress(), $email);
                        
        $controller->forwardEmail(array('collectionId' => 'foobar', 'itemdId' => $message->getId()), $email, true, false);
        
        // check if mail is in INBOX of test account
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'text/plain';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        
        $this->assertEquals("Re: [gentoo-dev] `paludis --info' is not like `emerge --info'", $message->subject);
        $this->assertEquals(1, $message->has_attachment);
        
        // check duplicate headers
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        $this->assertEquals(1, count($completeMessage->headers['mime-version']));
        $this->assertEquals(1, count($completeMessage->headers['content-type']));
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
