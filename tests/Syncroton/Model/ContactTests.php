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
class Syncroton_Model_ContactTests extends Syncroton_Model_ATestCase
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
<Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase" xmlns:Contacts="uri:Contacts" xmlns:Contacts2="uri:Contacts2"><Collections><Collection><Class>Contacts</Class><SyncKey>3</SyncKey><CollectionId>addressbook-root</CollectionId><DeletesAsMoves/><WindowSize>100</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options>
<Commands><Add><ClientId>4600</ClientId>
    <ApplicationData>
        <Contacts:FileAs>Fritzchen</Contacts:FileAs>
        <Contacts:FirstName>Fritzchen</Contacts:FirstName>
        <Contacts:LastName>Meinen</Contacts:LastName>
        <Contacts:Birthday>1969-12-31</Contacts:Birthday>
        <Contacts2:ManagerName>The Boss</Contacts2:ManagerName>
        <Contacts:Categories>
            <Contacts:Category>1234</Contacts:Category>
            <Contacts:Category>5678</Contacts:Category>
        </Contacts:Categories>
        <Contacts:Children>
            <Contacts:Child>1234</Contacts:Child>
            <Contacts:Child>5678</Contacts:Child>
        </Contacts:Children>
        <Contacts:Picture>cGljdHVyZQ==</Contacts:Picture>
        <AirSyncBase:Body><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:Data>Rewdfyyh fddg</AirSyncBase:Data></AirSyncBase:Body>
    </ApplicationData>
</Add></Commands></Collection></Collections></Sync>';
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton contact tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test add contact
     */
    public function testParseSimpleXMLElement()
    {
        $xml = new SimpleXMLElement($this->_xmlContactBirthdayWithoutTimeAndroid);
        $contact = new Syncroton_Model_Contact($xml->Collections->Collection->Commands->Add->ApplicationData);
        
        #foreach ($contact as $key => $value) {echo $key; var_dump($value);} 
        
        $this->assertEquals(9, count($contact));
        $this->assertEquals("Fritzchen",  $contact->fileAs);
        $this->assertEquals("Fritzchen",  $contact->firstName);
        $this->assertEquals("Meinen",     $contact->lastName);
        $this->assertEquals("1969-12-31", $contact->birthday->format('Y-m-d'));
        $this->assertEquals("The Boss",   $contact->managerName);
        $this->assertEquals("picture",    $contact->picture);
        $this->assertTrue($contact->body instanceof Syncroton_Model_EmailBody);
        $this->assertEquals(Syncroton_Model_EmailBody::TYPE_PLAINTEXT, $contact->body->type);
        $this->assertTrue(is_array($contact->children));
        $this->assertContains('1234', $contact->children);
        $this->assertTrue(is_array($contact->categories));
        $this->assertContains('5678', $contact->categories);
    }
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function testAppendXmlData()
    {
        $imp                   = new DOMImplementation();
        
        $dtd                   = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $testDoc               = $imp->createDocument('uri:AirSync', 'Sync', $dtd);
        $testDoc->formatOutput = true;
        $testDoc->encoding     = 'utf-8';
        
        $appData    = $testDoc->documentElement->appendChild($testDoc->createElementNS('uri:AirSync', 'ApplicationData'));
        
        $xml = new SimpleXMLElement($this->_xmlContactBirthdayWithoutTimeAndroid);
        $contact = new Syncroton_Model_Contact($xml->Collections->Collection->Commands->Add->ApplicationData);
        $contact->picture    = fopen(__DIR__ . '/../../files/test_image.jpeg', 'r');
        
        $contact->appendXML($appData, $this->_testDevice);
        
        #echo $testDoc->saveXML();
        
        $xpath = new DomXPath($testDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        $xpath->registerNamespace('Contacts', 'uri:Contacts');
        $xpath->registerNamespace('Contacts2', 'uri:Contacts2');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Contacts:FirstName');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('Fritzchen', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Contacts2:ManagerName');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('The Boss', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Contacts:Children/Contacts:Child');
        $this->assertEquals(2, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('1234', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/AirSyncBase:Body/AirSyncBase:Type');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals(Syncroton_Model_EmailBody::TYPE_PLAINTEXT, $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDoc);
    }
}
