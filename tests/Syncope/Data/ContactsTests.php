<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Calendar_Controller_Event
 * 
 * @package     Calendar
 */
class Syncope_Data_ContactsTests extends Syncope_Command_ATestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Syncope contacts tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * validate getFolders for IPhones
     */
    public function testGetFoldersIPhone()
    {
        $device = $this->_deviceBackend->create(
            Syncope_Backend_DeviceTests::getTestDevice(Syncope_Model_Device::TYPE_IPHONE)
        );
        
        $dataController = Syncope_Data_Factory::factory(Syncope_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
        
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
        $testNode = $testDoc->documentElement->appendChild($testDoc->createElementNS('uri:AirSync', 'ApplicationData'));
        
        $device = $this->_deviceBackend->create(
            Syncope_Backend_DeviceTests::getTestDevice(Syncope_Model_Device::TYPE_WEBOS)
        );
        
        $dataController = Syncope_Data_Factory::factory(Syncope_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
                
    	$dataController->appendXML($testNode, array('collectionId' => 'addressbookFolderId'), 'contact1');
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
     */
    public function testAddContact()
    {
        $device = $this->_deviceBackend->create(
            Syncope_Backend_DeviceTests::getTestDevice(Syncope_Model_Device::TYPE_ANDROID)
        );
        $dataController = Syncope_Data_Factory::factory(Syncope_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
        
        $xml = new SimpleXMLElement($this->_xmlContactBirthdayWithoutTimeAndroid);
        $id = $dataController->createEntry('addressbookFolderId', $xml->Collections->Collection->Commands->Add->ApplicationData);
        
        $entry = Syncope_Data_AData::$entries['Syncope_Data_Contacts']['addressbookFolderId'][$id];
        
        #$userTimezone = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        #$bday = new Tinebase_DateTime('1969-12-31', $userTimezone);
        #$bday->setTimezone('UTC');        
        
        #$this->assertEquals($bday->toString(), $result->bday->toString());
        $this->assertEquals('Fritzchen', $entry['FirstName']);
        $this->assertEquals('Meinen',    $entry['LastName']);
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
        
        $collections    = $testDoc->documentElement->appendChild($testDoc->createElementNS('uri:AirSync', 'Collections'));
        $collection     = $collections->appendChild($testDoc->createElementNS('uri:AirSync', 'Collection'));
        $commands       = $collection->appendChild($testDoc->createElementNS('uri:AirSync', 'Commands'));
        $add            = $commands->appendChild($testDoc->createElementNS('uri:AirSync', 'Add'));
        $appData        = $add->appendChild($testDoc->createElementNS('uri:AirSync', 'ApplicationData'));
        
        
        $device = $this->_deviceBackend->create(
            Syncope_Backend_DeviceTests::getTestDevice(Syncope_Model_Device::TYPE_IPHONE)
        );
        $dataController = Syncope_Data_Factory::factory(Syncope_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
                
        $dataController->appendXML($appData, array('collectionId' => 'addressbookFolderId'), 'contact1');
        
        // no offset and namespace === uri:Contacts
        #$this->assertEquals('1975-01-02T03:00:00.000Z', @$testDoc->getElementsByTagNameNS('uri:Contacts', 'Birthday')->item(0)->nodeValue, $testDoc->saveXML());
        
        #echo $testDoc->saveXML();

        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Wbxml_Encoder($outputStream, 'UTF-8', 3);
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
        $device = $this->_deviceBackend->create(
            Syncope_Backend_DeviceTests::getTestDevice(Syncope_Model_Device::TYPE_IPHONE)
        );
        $dataController = Syncope_Data_Factory::factory(Syncope_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
        
    	$entries = $dataController->getServerEntries('addressbookFolderId', null);
    	
    	$this->assertContains('contact1', $entries);
    }
    
    /**
     * test getChanged entries
     */
    public function testGetChanged()
    {
        $device = $this->_deviceBackend->create(
            Syncope_Backend_DeviceTests::getTestDevice(Syncope_Model_Device::TYPE_IPHONE)
        );
        $dataController = Syncope_Data_Factory::factory(Syncope_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
        
        Syncope_Data_AData::$changedEntries['Syncope_Data_Contacts'][] = 'contact1';
        
        $entries = $dataController->getChangedEntries('addressbook-root', new DateTime(null, new DateTimeZone('UTC')));
        #var_dump($entries);
        $this->assertContains('contact1', $entries);
        $this->assertNotContains('contact2', $entries);
    }
    
    public function testDeleteEntry()
    {
        $device = $this->_deviceBackend->create(
            Syncope_Backend_DeviceTests::getTestDevice(Syncope_Model_Device::TYPE_IPHONE)
        );
        $dataController = Syncope_Data_Factory::factory(Syncope_Data_Factory::CLASS_CONTACTS, $device, new DateTime(null, new DateTimeZone('UTC')));
        
        Syncope_Data_AData::$entries['Syncope_Data_Contacts']['addressbookFolderId']['foobar'] = array();
        
        $this->assertArrayHasKey('foobar', Syncope_Data_Contacts::$entries['Syncope_Data_Contacts']['addressbookFolderId']);
        
        $dataController->deleteEntry('addressbookFolderId', 'foobar', array());
        
        $this->assertArrayNotHasKey('foobar', Syncope_Data_Contacts::$entries['Syncope_Data_Contacts']['addressbookFolderId']);
    }
}
