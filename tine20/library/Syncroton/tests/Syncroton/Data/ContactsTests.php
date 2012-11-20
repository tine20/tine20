<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Tests
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to test <...>
 *
 * @package     Syncroton
 * @subpackage  Tests
 */
class Syncroton_Data_ContactsTests extends Syncroton_Command_ATestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    protected $_exampleXMLNotExisting = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<Sync xmlns="uri:AirSync" xmlns:Contacts="uri:Contacts"><Collections><Collection><Class>Contacts</Class><SyncKey>1</SyncKey><CollectionId>addressbook-root</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>50</WindowSize><Options><FilterType>0</FilterType><Truncation>2</Truncation><Conflict>0</Conflict></Options><Commands><Add><ClientId>1</ClientId><ApplicationData><Contacts:FileAs>ads2f, asdfadsf</Contacts:FileAs><Contacts:FirstName>asdf </Contacts:FirstName><Contacts:LastName>asdfasdfaasd </Contacts:LastName><Contacts:MobilePhoneNumber>+4312341234124</Contacts:MobilePhoneNumber><Contacts:Body>&#13;
</Contacts:Body></ApplicationData></Add></Commands></Collection></Collections></Sync>';
    
    protected $_exampleXMLExisting = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<Sync xmlns="uri:AirSync" xmlns:Contacts="uri:Contacts"><Collections><Collection><Class>Contacts</Class><SyncKey>1</SyncKey><CollectionId>addressbook-root</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>50</WindowSize><Options><FilterType>0</FilterType><Truncation>2</Truncation><Conflict>0</Conflict></Options><Commands><Add><ClientId>1</ClientId><ApplicationData><Contacts:FileAs>Kneschke, Lars</Contacts:FileAs><Contacts:FirstName>Lars</Contacts:FirstName><Contacts:LastName>Kneschke</Contacts:LastName></ApplicationData></Add></Commands></Collection></Collections></Sync>';
    
    protected $_xmlContactBirthdayWithoutTimeAndroid = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
<Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Contacts="uri:Contacts"><Collections><Collection><Class>Contacts</Class><SyncKey>3</SyncKey><CollectionId>addressbook-root</CollectionId><DeletesAsMoves/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options><Commands><Add><ClientId>4600</ClientId><ApplicationData><Contacts:FileAs>Fritzchen</Contacts:FileAs><Contacts:FirstName>Fritzchen</Contacts:FirstName><Contacts:LastName>Meinen</Contacts:LastName><Contacts:Birthday>1969-12-31</Contacts:Birthday><AirSyncBase:Body><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:EstimatedDataSize>0</AirSyncBase:EstimatedDataSize><AirSyncBase:Data></AirSyncBase:Data></AirSyncBase:Body><Contacts:Categories/><Contacts:Picture/></ApplicationData></Add></Commands></Collection></Collections></Sync>';
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton contacts tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * validate getFolders for IPhones
     */
    public function testGetFoldersIPhone()
    {
        try {
            $device = Syncroton_Registry::getDeviceBackend()->getUserDevice('1234', 'iphone-abcd');
            Syncroton_Registry::getDeviceBackend()->delete($device);
        } catch (Syncroton_Exception_NotFound $e) {
            // do nothing => it's ok
        }
        $device = Syncroton_Registry::getDeviceBackend()->create(
            Syncroton_Backend_DeviceTests::getTestDevice(Syncroton_Model_Device::TYPE_IPHONE)
        );
                
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
        
        $folders = $dataController->getAllFolders();
                
        $this->assertArrayNotHasKey("addressbook-root", $folders, "key addressbook-root found");
        $this->assertGreaterThanOrEqual(1, count($folders));
    }
    
    /**
     * validate xml generation for all devices except IPhone
     */
    public function testAppendXmlPalm()
    {
        $imp                   = new DOMImplementation();
        
        $dtd                   = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $testDoc               = $imp->createDocument('uri:AirSync', 'Sync', $dtd);
        $testDoc->formatOutput = true;
        $testDoc->encoding     = 'utf-8';
        
        $testDoc->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:Contacts', 'uri:Contacts');
        $applicationData = $testDoc->documentElement->appendChild($testDoc->createElementNS('uri:AirSync', 'ApplicationData'));
        
        $device = Syncroton_Registry::getDeviceBackend()->create(
            Syncroton_Backend_DeviceTests::getTestDevice(Syncroton_Model_Device::TYPE_WEBOS)
        );
        
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
        
        $allEntries = $dataController->getServerEntries('addressbookFolderId', null);
        
        $collection = new Syncroton_Model_SyncCollection();
        $collection->collectionId = 'addressbookFolderId';
        
        $dataController
            ->getEntry($collection, $allEntries[0])
            ->appendXML($applicationData, $device);
        
        #echo $testDoc->saveXML();
        
        $xpath = new DomXPath($testDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        $xpath->registerNamespace('Contacts', 'uri:Contacts');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Contacts:FirstName');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('Lars', $nodes->item(0)->nodeValue, $testDoc->saveXML());
         
        // offset birthday 12 hours + user TZ and namespace === uri:Contacts
        #$this->assertEquals(Tinebase_Translation::getCountryNameByRegionCode('DE'), @$testDoc->getElementsByTagNameNS('uri:Contacts', 'BusinessCountry')->item(0)->nodeValue, $testDoc->saveXML());
        #$this->assertEquals('1975-01-02T16:00:00.000Z', @$testDoc->getElementsByTagNameNS('uri:Contacts', 'Birthday')->item(0)->nodeValue, $testDoc->saveXML());
    }
    
    /**
     * test add contact
     * @return Syncroton_Model_Contact
     */
    public function testAddContact()
    {
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $this->_device, new DateTime(null, new DateTimeZone('UTC')));
        $dataClass = 'Syncroton_Model_Contact';
        
        $xml = new SimpleXMLElement($this->_xmlContactBirthdayWithoutTimeAndroid);
        
        $id = $dataController->createEntry('addressbookFolderId', new $dataClass($xml->Collections->Collection->Commands->Add->ApplicationData));
        
        $entry = $dataController->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => 'addressbookFolderId')), $id);
        
        #$userTimezone = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        #$bday = new Tinebase_DateTime('1969-12-31', $userTimezone);
        #$bday->setTimezone('UTC');        
        
        #$this->assertEquals($bday->toString(), $result->bday->toString());
        $this->assertEquals('Fritzchen', $entry->firstName);
        $this->assertEquals('Meinen',    $entry->lastName);
        
        return $entry;
    }
    
    /**
     * test search contacts
     */
    public function testSearchGAL()
    {
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $this->_device, new DateTime(null, new DateTimeZone('UTC')));
        
        $entry = $this->testAddContact();
        
        $searchResult = $dataController->search(new Syncroton_Model_StoreRequest(array(
            'query' => 'lars',
            'options' => array()
        )));
        
        #var_dump($searchResult);
        
        $this->assertTrue($searchResult instanceof Syncroton_Model_StoreResponse);
    }
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function testAppendXmlIPhone()
    {
        $imp                   = new DOMImplementation();
        
        $dtd                   = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $testDoc               = $imp->createDocument('uri:AirSync', 'Sync', $dtd);
        $testDoc->formatOutput = true;
        $testDoc->encoding     = 'utf-8';
        $testDoc->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:Contacts', 'uri:Contacts');
        
        $collections     = $testDoc->documentElement->appendChild($testDoc->createElementNS('uri:AirSync', 'Collections'));
        $collection      = $collections->appendChild($testDoc->createElementNS('uri:AirSync', 'Collection'));
        $commands        = $collection->appendChild($testDoc->createElementNS('uri:AirSync', 'Commands'));
        $add             = $commands->appendChild($testDoc->createElementNS('uri:AirSync', 'Add'));
        $applicationData = $add->appendChild($testDoc->createElementNS('uri:AirSync', 'ApplicationData'));
        
        try {
            $device = Syncroton_Registry::getDeviceBackend()->getUserDevice('1234', 'iphone-abcd');
            Syncroton_Registry::getDeviceBackend()->delete($device);
        } catch (Syncroton_Exception_NotFound $e) {
            // do nothing => it's ok
        }
        $device = Syncroton_Registry::getDeviceBackend()->create(
            Syncroton_Backend_DeviceTests::getTestDevice(Syncroton_Model_Device::TYPE_IPHONE)
        );
        
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
        
        $collection = new Syncroton_Model_SyncCollection();
        $collection->collectionId = 'addressbookFolderId';
        
        $allEntries = $dataController->getServerEntries('addressbookFolderId', null);
        
        $dataController
            ->getEntry($collection, $allEntries[0])
            ->appendXML($applicationData, $device);
        
        
        // no offset and namespace === uri:Contacts
        #$this->assertEquals('1975-01-02T03:00:00.000Z', @$testDoc->getElementsByTagNameNS('uri:Contacts', 'Birthday')->item(0)->nodeValue, $testDoc->saveXML());
        
        #echo $testDoc->saveXML();

        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDoc);
        
        #rewind($outputStream);
        #fpassthru($outputStream);
    }
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function testGetServerEntries()
    {
        try {
            $device = Syncroton_Registry::getDeviceBackend()->getUserDevice('1234', 'iphone-abcd');
            Syncroton_Registry::getDeviceBackend()->delete($device);
        } catch (Syncroton_Exception_NotFound $e) {
            // do nothing => it's ok
        }
        $device = Syncroton_Registry::getDeviceBackend()->create(
                Syncroton_Backend_DeviceTests::getTestDevice(Syncroton_Model_Device::TYPE_IPHONE)
        );
        
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
        
        $entries = $dataController->getServerEntries('addressbookFolderId', null);
        
        $this->assertEquals(10, count($entries));
    }
    
    /**
     * test getChanged entries
     */
    public function testGetChanged()
    {
        try {
            $device = Syncroton_Registry::getDeviceBackend()->getUserDevice('1234', 'iphone-abcd');
            Syncroton_Registry::getDeviceBackend()->delete($device);
        } catch (Syncroton_Exception_NotFound $e) {
            // do nothing => it's ok
        }
        $device = Syncroton_Registry::getDeviceBackend()->create(
            Syncroton_Backend_DeviceTests::getTestDevice(Syncroton_Model_Device::TYPE_IPHONE)
        );
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
        
        Syncroton_Data_AData::$changedEntries['Syncroton_Data_Contacts'][] = 'contact1';
        
        $entries = $dataController->getChangedEntries('addressbook-root', new DateTime(null, new DateTimeZone('UTC')));
        
        $this->assertContains('contact1', $entries);
        $this->assertNotContains('contact2', $entries);
    }
    
    public function testDeleteEntry()
    {
        try {
            $device = Syncroton_Registry::getDeviceBackend()->getUserDevice('1234', 'iphone-abcd');
            Syncroton_Registry::getDeviceBackend()->delete($device);
        } catch (Syncroton_Exception_NotFound $e) {
            // do nothing => it's ok
        }
        $device = Syncroton_Registry::getDeviceBackend()->create(
            Syncroton_Backend_DeviceTests::getTestDevice(Syncroton_Model_Device::TYPE_IPHONE)
        );
        
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
        
        $entries = $dataController->getServerEntries('addressbookFolderId', null);
        
        $dataController->deleteEntry('addressbookFolderId', $entries[0], array());
        
        $newEntries = $dataController->getServerEntries('addressbookFolderId', null);
        
        $this->assertArrayNotHasKey($entries[0], $newEntries);
    }
}
