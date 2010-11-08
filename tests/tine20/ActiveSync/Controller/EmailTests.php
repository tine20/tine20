<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ActiveSync_Controller_EmailTests::main');
}

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
     * 
     * @var Felamimail_Controller_MessageTest
     */
    protected $_emailTestClass;
    
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
    
    
    protected function setUp()
    {   	
        $imp = new DOMImplementation;
        
        $doctype = $imp->createDocumentType("phpunit", "-//W3C//DTD HTML 4.01//EN", "http://www.w3.org/TR/html4/strict.dtd"); 
        $this->_domDocument = $imp->createDocument(null, 'phpunit', $doctype);
        
        $this->_domDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Contacts'    , 'uri:Contacts');
        $this->_domDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Tasks'       , 'uri:Tasks');
        $this->_domDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Email'       , 'uri:Email');
        $this->_domDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Calendar'    , 'uri:Calendar');
        $this->_domDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:AirSyncBase' , 'uri:AirSyncBase');
        $this->_domDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:AirSync'     , 'uri:AirSync');
        $this->_domDocument->formatOutput = false;
        $this->_domDocument->encoding     = 'utf-8';
        
        $this->_emailTestClass = new Felamimail_Controller_MessageTest();
        $this->_emailTestClass->setup();
        
        ########### define test devices
        $palm = ActiveSync_Backend_DeviceTests::getTestDevice();
        $palm->devicetype = 'palm';
        $palm->acsversion = '12.0';
        $this->objects['devicePalm']   = ActiveSync_Controller_Device::getInstance()->create($palm);
        
        $iphone = ActiveSync_Backend_DeviceTests::getTestDevice();
        $iphone->devicetype = 'iphone';
        $iphone->acsversion = '2.5';
        $this->objects['deviceIPhone'] = ActiveSync_Controller_Device::getInstance()->create($iphone);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $this->_emailTestClass->tearDown();
        
        ActiveSync_Controller_Device::getInstance()->delete($this->objects['devicePalm']);
        ActiveSync_Controller_Device::getInstance()->delete($this->objects['deviceIPhone']);
    }
    
    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testAppendFileReference()
    {
    	$controller = new ActiveSync_Controller_Email($this->objects['devicePalm'], Tinebase_DateTime::now());
    	
    	$message = $this->_emailTestClass->createCachedTestMessage('multipart_mixed.eml', 'multipart/mixed');
    	
    	$fileReference = $message->getId() . '-2';
    	
    	$properties = $this->_domDocument->createElementNS('uri:ItemOperations', 'Properties');
        $controller->appendFileReference($properties, $fileReference);
        $this->_domDocument->documentElement->appendChild($properties);
        
    	#$this->_domDocument->formatOutput = true;
    	#echo $this->_domDocument->saveXML();

        $this->assertEquals('text/plain', @$this->_domDocument->getElementsByTagNameNS('uri:AirSyncBase', 'ContentType')->item(0)->nodeValue, $this->_domDocument->saveXML());
        $this->assertEquals(2787, strlen($this->_domDocument->getElementsByTagNameNS('uri:ItemOperations', 'Data')->item(0)->nodeValue), $this->_domDocument->saveXML());
    }
    
    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testAppendXML()
    {
        $controller = new ActiveSync_Controller_Email($this->objects['devicePalm'], Tinebase_DateTime::now());
        
        $message = $this->_emailTestClass->createCachedTestMessage('multipart_mixed.eml', 'multipart/mixed');
        
        $options = array();
        $properties = $this->_domDocument->createElementNS('uri:ItemOperations', 'Properties');
        $controller->appendXML($properties, $message->folder_id, $message->getId(), $options);
        $this->_domDocument->documentElement->appendChild($properties);
        
        #$this->_domDocument->formatOutput = true;
        #echo $this->_domDocument->saveXML();

        $this->assertEquals('[gentoo-dev] Automated Package Removal and Addition Tracker, for the week ending 2009-04-12 23h59 UTC', @$this->_domDocument->getElementsByTagNameNS('uri:Email', 'Subject')->item(0)->nodeValue, $this->_domDocument->saveXML());
        // size of the attachment
        $this->assertEquals(2787, @$this->_domDocument->getElementsByTagNameNS('uri:AirSyncBase', 'EstimatedDataSize')->item(0)->nodeValue, $this->_domDocument->saveXML());
        // size of the body
        $this->assertEquals(9606, @$this->_domDocument->getElementsByTagNameNS('uri:AirSyncBase', 'EstimatedDataSize')->item(1)->nodeValue, $this->_domDocument->saveXML());
    }
    
    /**
     * append message (from given filename) to cache
     *
     * @param string $_filename
     * @param string $_folder
     */
    protected function _appendMessage($_filename, $_folder)
    {
        $message = fopen(dirname(dirname(__FILE__)) . '/files/' . $_filename, 'r');
        $this->_controller->appendMessage($_folder, $message);
    }
    
}
    
if (PHPUnit_MAIN_METHOD == 'ActiveSync_Controller_EmailTests::main') {
    ActiveSync_Controller_EmailTests::main();
}
