<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Syncroton_Command_Sync
 * 
 * @package     ActiveSync
 */
class ActiveSync_Command_SyncTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var ActiveSync_Model_Device
     */
    protected $_device;
    
    /**
     * @var ActiveSync_Backend_Device
     */
    protected $_deviceBackend;
    
    /**
     * @var Syncroton_Backend_Folder
     */
    protected $_folderBackend;

    /**
     * @var Syncroton_Backend_SyncState
     */
    protected $_syncStateBackend;
    
    /**
     * @var Syncroton_Backend_IContent
     */
    protected $_contentStateBackend;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Sync Command Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::setUp()
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_setGeoData = Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(FALSE);
        
        Syncroton_Registry::setDatabase(Tinebase_Core::getDb());
        Syncroton_Registry::setTransactionManager(Tinebase_TransactionManager::getInstance());
        
        Syncroton_Registry::set(Syncroton_Registry::DEVICEBACKEND,       new Syncroton_Backend_Device(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::FOLDERBACKEND,       new Syncroton_Backend_Folder(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::SYNCSTATEBACKEND,    new Syncroton_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::CONTENTSTATEBACKEND, new Syncroton_Backend_Content(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::POLICYBACKEND,       new Syncroton_Backend_Policy(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        
        Syncroton_Registry::setContactsDataClass('ActiveSync_Controller_Contacts');
        Syncroton_Registry::setCalendarDataClass('ActiveSync_Controller_Calendar');
        Syncroton_Registry::setEmailDataClass('ActiveSync_Controller_Email');
        Syncroton_Registry::setTasksDataClass('ActiveSync_Controller_Tasks');
        
        $this->_device = Syncroton_Registry::getDeviceBackend()->create(
            ActiveSync_TestCase::getTestDevice()
        );
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * test sync of existing contacts folder
     */
    public function testSyncOfContacts()
    {
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Tinebase_Core::getUser(),
            'Addressbook', 
            Tinebase_Core::getUser(),
            Tinebase_Model_Grants::GRANT_EDIT
        )->getFirstRecord();
        
        // first do a foldersync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $folderSync->getResponse();
        
        
        // request initial synckey
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>0</SyncKey><CollectionId>' . $personalContainer->getId() . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(1, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        
        // now do the first sync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1</SyncKey><CollectionId>' . $personalContainer->getId() . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        #$this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $this->assertEquals("uri:Contacts", $syncDoc->lookupNamespaceURI('Contacts'), $syncDoc->saveXML());
    }
    
    public function testCreateContact()
    {
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Tinebase_Core::getUser(),
            'Addressbook', 
            Tinebase_Core::getUser(),
            Tinebase_Model_Grants::GRANT_EDIT
        )->getFirstRecord();
        
        $this->testSyncOfContacts();
        
        // lets add one contact
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:Contacts="uri:Contacts" xmlns:AirSyncBase="uri:AirSyncBase"><Collections>
                <Collection>
                    <Class>Contacts</Class><SyncKey>2</SyncKey><CollectionId>' . $personalContainer->getId() . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize>
                    <Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
                    <Commands><Add><ClientId>42</ClientId><ApplicationData><Contacts:FirstName>aaaadde</Contacts:FirstName><Contacts:LastName>aaaaade</Contacts:LastName></ApplicationData></Add></Commands>
                </Collection>
            </Collections></Sync>'
        );
        
        // decode to wbxml and back again to test the wbxml en-/decoder
        $xmlStream = fopen("php://temp", 'r+');
        
        $encoder = new Syncroton_Wbxml_Encoder($xmlStream, 'UTF-8', 3);
        $encoder->encode($doc);
        
        rewind($xmlStream);
        
        $decoder = new Syncroton_Wbxml_Decoder($xmlStream);
        $doc = $decoder->decode();
        #$doc->formatOutput = true; echo $doc->saveXML();
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Contacts', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(3, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Responses/AirSync:Add/AirSync:ServerId');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertFalse(empty($nodes->item(0)->nodeValue), $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Responses/AirSync:Add/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
    }
    
    /**
     * test sync of existing events folder
     */
    public function testSyncOfEvents()
    {
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Tinebase_Core::getUser(),
            'Calendar', 
            Tinebase_Core::getUser(),
            Tinebase_Model_Grants::GRANT_EDIT
        )->getFirstRecord();
        
        
        // add a test event
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'SyncTest',
            'dtstart'       => Tinebase_DateTime::now()->addMonth(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:00:00',
            'dtend'         => Tinebase_DateTime::now()->addMonth(1)->addHour(1)->toString(Tinebase_Record_Abstract::ISO8601LONG), //'2009-04-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'container_id'  => $personalContainer->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            'attendee'      => new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
                array(
                    'user_id' => Tinebase_Core::getUser()->contact_id,
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'status' => Calendar_Model_Attender::STATUS_ACCEPTED
                )
            ))
        ));
        
        $event = Calendar_Controller_Event::getInstance()->create($event);
        
        
        // first do a foldersync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $folderSync->getResponse();
        
        
        // request initial synckey
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Calendar</Class><SyncKey>0</SyncKey><CollectionId>' . $personalContainer->getId() . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><FilterType>4</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Calendar', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(1, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        
        // now do the first sync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Calendar</Class><SyncKey>1</SyncKey><CollectionId>' . $personalContainer->getId() . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><FilterType>4</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Calendar', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        
        $this->assertEquals("uri:Calendar", $syncDoc->lookupNamespaceURI('Calendar'), $syncDoc->saveXML());
    }
    
    /**
     * test sync of existing imap folder
     */
    public function testSyncOfEmails()
    {
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
        if (! $imapConfig || ! isset($imapConfig->useSystemAccount) || $imapConfig->useSystemAccount != TRUE) {
            $this->markTestSkipped('IMAP backend not configured');
        }
        
        // this test needs at least one email in the INBOX
        $emailTest = new Felamimail_Controller_MessageTest();
        $emailTest->setUp();
        $inbox = $emailTest->getFolder('INBOX');
        $emailTest->messageTestHelper('multipart_mixed.eml', 'multipart/mixed', $inbox);
        
        $emailController = new ActiveSync_Controller_Email($this->_device, new Tinebase_DateTime(null, null, 'de_DE'));

        $folders = $emailController->getAllFolders();
        
        foreach ($folders as $folder) {
            if (strtoupper($folder->displayName) == 'INBOX') {
                break;
            }
        }
        
        // first do a foldersync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $syncDoc = $folderSync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        // request initial synckey
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Email</Class><SyncKey>0</SyncKey><CollectionId>' . $folder->serverId . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><FilterType>4</FilterType><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Email', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(1, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        
        // now do the first sync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Collections>
                    <Collection>
                        <Class>Email</Class>
                        <SyncKey>1</SyncKey>
                        <CollectionId>' . $folder->serverId . '</CollectionId>
                        <DeletesAsMoves/>
                        <GetChanges/>
                        <WindowSize>100</WindowSize>
                        <Options>
                            <AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict>
                        </Options>
                    </Collection>
                </Collections>
            </Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        $syncDoc = $sync->getResponse();
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();
        
        $xpath = new DomXPath($syncDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Class');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals('Email', $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:SyncKey');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Status');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Sync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $syncDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:Collections/AirSync:Collection/AirSync:Commands');
        $this->assertEquals(1, $nodes->length, $syncDoc->saveXML());
        
        $this->assertEquals("uri:Email", $syncDoc->lookupNamespaceURI('Email'), $syncDoc->saveXML());
        
        $emailTest->tearDown();
    }
}
