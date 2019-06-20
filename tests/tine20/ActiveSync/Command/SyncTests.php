<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Syncroton_Command_Sync
 * 
 * @package     ActiveSync
 */
class ActiveSync_Command_SyncTests extends TestCase
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
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        Syncroton_Registry::setDatabase(Tinebase_Core::getDb());
        Syncroton_Registry::setTransactionManager(Tinebase_TransactionManager::getInstance());
        
        Syncroton_Registry::set(Syncroton_Registry::DEVICEBACKEND,       new Syncroton_Backend_Device(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::FOLDERBACKEND,       new Syncroton_Backend_Folder(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::SYNCSTATEBACKEND,    new Syncroton_Backend_SyncState(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::CONTENTSTATEBACKEND, new Syncroton_Backend_Content(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set(Syncroton_Registry::POLICYBACKEND,       new Syncroton_Backend_Policy(Tinebase_Core::getDb(), SQL_TABLE_PREFIX . 'acsync_'));
        Syncroton_Registry::set('loggerBackend', Tinebase_Core::getLogger());
        
        Syncroton_Registry::setContactsDataClass('Addressbook_Frontend_ActiveSync');
        Syncroton_Registry::setCalendarDataClass('Calendar_Frontend_ActiveSync');
        Syncroton_Registry::setEmailDataClass('Felamimail_Frontend_ActiveSync');
        Syncroton_Registry::setTasksDataClass('Tasks_Frontend_ActiveSync');
        
        $this->_device = Syncroton_Registry::getDeviceBackend()->create(
            ActiveSync_TestCase::getTestDevice()
        );
    }

    /**
     * tear down tests
     */
    protected function tearDown()
    {
        parent::tearDown();

        if (! $this->_transactionId) {
            Syncroton_Registry::getDeviceBackend()->delete($this->_device->id);
        }
    }

    /**
     * test sync of existing contacts folder
     */
    public function testSyncOfContacts()
    {
        $container = $this->_getPersonalContainer(Addressbook_Model_Contact::class);
        
        $this->_syncFolder();
        $this->_requestInitialSynckey($container);
        
        // now do the first sync
        $sync = $this->_sync($container);
        $syncDoc = $sync->getResponse();
        
        // we make sure that there are always > 0 contacts in this container
        if ($syncDoc === null) {
            $contact = new Addressbook_Model_Contact(array(
                'n_family'     => 'lala',
                'container_id' => $container->getId()
            ));
            Addressbook_Controller_Contact::getInstance()->create($contact);
            $sync = $this->_sync($container);
            $syncDoc = $sync->getResponse();
        }

        self::assertNotNull($syncDoc);
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
        
        $this->assertEquals("uri:Contacts", $syncDoc->lookupNamespaceURI('Contacts'), $syncDoc->saveXML());
    }
    
    /**
     * sync folder
     */
    protected function _syncFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $folderSync->getResponse();
    }
    
    /**
     * request inital synckey
     * 
     * @param Tinebase_Model_Container $container
     */
    protected function _requestInitialSynckey($container)
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>0</SyncKey><CollectionId>'
                . $container->getId() . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options>'
                . '<AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize>'
                . '</AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
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
    }
    
    /**
     * do sync request
     * 
     * @param Tinebase_Model_Container
     * @return Syncroton_Command_Sync
     */
    protected function _sync($container)
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1</SyncKey><CollectionId>'
                . $container->getId() . '</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        
        $sync->handle();
        
        return $sync;
    }
    
    /**
     * test create contact
     */
    public function testCreateContact()
    {
        $personalContainer = $this->_getPersonalContainer(Addressbook_Model_Contact::class);
        
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
        $this->assertEquals(1, $nodes->length, 'one contact should be in here: ' . $syncDoc->saveXML());
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
     * tests xml encoding/decoding
     */
    public function testWbXmlEncodeDecode()
    {
        $xmlFile = file_get_contents(dirname(__FILE__) . '/ibernard.xml');
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($xmlFile, LIBXML_NOWARNING);

        $output = Felamimail_Frontend_ActiveSyncTest::encodeXml($doc);
        $this->assertContains(' Mein Kopf ist gerade zu voll... 😃', $output);
    }
    
    /**
     * test sync of existing events folder
     */
    public function testSyncOfEvents()
    {
        $personalContainer = $this->_getPersonalContainer(Calendar_Model_Event::class);
        
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
        
        Calendar_Controller_Event::getInstance()->create($event);

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

        self::assertNotNull($syncDoc);
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
     *
     * @param string $filename
     * @param string $testHeaderValue
     * @return string output
     */
    public function testSyncOfEmails($filename = 'multipart_mixed.eml', $testHeaderValue = 'multipart/mixed')
    {
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
        if (! $imapConfig || ! isset($imapConfig->useSystemAccount) || $imapConfig->useSystemAccount != TRUE) {
            $this->markTestSkipped('IMAP backend not configured');
        }
        
        // this test needs at least one email in the INBOX
        $emailTest = new Felamimail_Controller_MessageTest();
        $emailTest->setUp();
        $inbox = $emailTest->getFolder('INBOX');
        $emailTest->messageTestHelper($filename, $testHeaderValue, $inbox);
        
        $emailController = new Felamimail_Frontend_ActiveSync($this->_device, new Tinebase_DateTime(null, null, 'de_DE'));

        $folders = $emailController->getAllFolders();

        foreach ($folders as $folder) {
            if (strtoupper($folder->displayName) == 'INBOX') {
                break;
            }
        }

        if (! isset($folder)) {
            $this->fail('should have an INBOX');
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

        // activate for xml output
        #$syncDoc->formatOutput = true; echo $syncDoc->saveXML();

        self::assertNotNull($syncDoc);
        
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

        $output = Felamimail_Frontend_ActiveSyncTest::encodeXml($syncDoc);

        $emailTest->tearDown();

        return $output;
    }

    public function testSyncOfGarbledEmail()
    {
        $this->_testNeedsTransaction();

        $output = $this->testSyncOfEmails('emoji.eml', 'emoji.eml');

        $this->assertContains(Tinebase_Core::filterInputForDatabase('Mein Kopf ist gerade zu voll...😃?'), $output,
            'handling of utf8mb4 failed');
    }
    
    /**
     * testResetSync
     * 
     * @see 0010584: CLI function for resetting sync on devices
     */
    public function testResetSync()
    {
        $class = 'Calendar';
        $this->testSyncOfEvents();
        
        $result = ActiveSync_Controller::getInstance()->resetSyncForUser(Tinebase_Core::getUser()->accountLoginName, array($class));
        
        $this->assertTrue($result);
        
        // check if synckey = 0
        $folderState = Syncroton_Registry::getFolderBackend()->getFolderState($this->_device->id, $class);
        
        $this->assertTrue(count($folderState) > 0);
        
        foreach ($folderState as $folder) {
            try {
                Syncroton_Registry::getSyncStateBackend()->getSyncState($this->_device->id, $folder->id);
                $this->fail('should not find sync state for folder');
            } catch (Exception $e) {
                $this->assertEquals('id not found', $e->getMessage());
            }
        }
    }
}
