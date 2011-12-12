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
        $this->_domDocument = $this->_getOutputDOMDocument();        
        
        $this->_emailTestClass = new Felamimail_Controller_MessageTest();
        $this->_emailTestClass->setup();
        
        $this->objects['devices'] = array();
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
        
        foreach($this->objects['devices'] as $device) {
            ActiveSync_Controller_Device::getInstance()->delete($device);
        }
    }
    
    /**
     * validate fetching email by filereference(hashid-partid)
     */
    public function testAppendFileReference()
    {
    	$controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM)); 
    	
    	$message = $this->_emailTestClass->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
    	
    	$fileReference = $message->getId() . '-2';
    	
    	$properties = $this->_domDocument->createElementNS('uri:ItemOperations', 'Properties');
        $controller->appendFileReference($properties, $fileReference);
        $this->_domDocument->documentElement->appendChild($properties);
        
    	#$this->_domDocument->formatOutput = true;
    	#echo $this->_domDocument->saveXML();

        $this->assertEquals('text/plain', @$this->_domDocument->getElementsByTagNameNS('uri:AirSyncBase', 'ContentType')->item(0)->nodeValue, $this->_domDocument->saveXML());
        $this->assertTrue(3000 < strlen($this->_domDocument->getElementsByTagNameNS('uri:ItemOperations', 'Data')->item(0)->nodeValue), $this->_domDocument->saveXML());
    }
    
    /**
     * test invalid chars
     */
    public function testInvalidBodyChars()
    {
        //invalid_body_chars.eml
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM)); 
    	
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
    public function testAppendXML()
    {
        $controller = $this->_getController($this->_getDevice(ActiveSync_Backend_Device::TYPE_PALM)); 
        
        $message = $this->_emailTestClass->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        
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
     * validate getSupportedFolders
     */
    public function testGetSupportedFolders()
    {
        $controller = ActiveSync_Controller::dataFactory(ActiveSync_Controller::CLASS_EMAIL, $this->_getDevice(ActiveSync_Backend_Device::TYPE_IPHONE), new Tinebase_DateTime(null, null, 'de_DE'));
        
        $folders = $controller->getSupportedFolders();
        
        $this->assertGreaterThanOrEqual(1, count($folders));
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
        
        switch ($_deviceType) {
            case ActiveSync_Backend_Device::TYPE_IPHONE:
                $device = ActiveSync_Backend_DeviceTests::getTestDevice();
                $device->devicetype   = $_deviceType;
                $device->owner_id     = $this->_testUser->getId();
                #$palm->contactsfilter_id = $this->objects['filter']->getId();
                
                break;
                
            case ActiveSync_Backend_Device::TYPE_PALM:
                $device = ActiveSync_Backend_DeviceTests::getTestDevice();
                $device->devicetype   = $_deviceType;
                $device->owner_id     = $this->_testUser->getId();
                $device->acsversion   = '12.0';
                #$palm->contactsfilter_id = $this->objects['filter']->getId();
                
                break;
                
            default:
                throw new Exception('unsupported device: ' , $_deviceType);
        }
        
        $this->objects['devices'][$_deviceType] = ActiveSync_Controller_Device::getInstance()->create($device);

        return $this->objects['devices'][$_deviceType];
    }
    
    /**
     * get application activesync controller
     * 
     * @param ActiveSync_Model_Device $_device
     */
    protected function _getController(ActiveSync_Model_Device $_device)
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
    protected function _getOutputDOMDocument()
    {
    	$dom = new DOMDocument();
        $dom->formatOutput = false;
        $dom->encoding     = 'utf-8';
        $dom->loadXML($this->_testXMLOutput);
        #$dom->formatOutput = true; echo $dom->saveXML(); $dom->formatOutput = false;
        
        return $dom;
    }
}
