<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for FolderSync_Controller_Event
 * 
 * @package     Tests
 */
class Syncope_Command_SettingsTests extends Syncope_Command_ATestCase
{
    #protected $_logPriority = Zend_Log::DEBUG;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync Settings command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test xml generation for IPhone
     */
    public function testSetDeviceInformation()
    {
        // delete folder created above
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Settings xmlns="uri:Settings"><DeviceInformation><Set><Model>iPhone</Model></Set></DeviceInformation></Settings>'
        );
        $folderDelete = new Syncope_Command_Settings($doc, $this->_device, null);
        $folderDelete->handle();
        $responseDoc = $folderDelete->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Settings', 'uri:Settings');
        
        $nodes = $xpath->query('//Settings:Settings/Settings:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_Settings::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        #$nodes = $xpath->query('//Settings:Settings/Settings:SyncKey');
        #$this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        #$this->assertEquals(3, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }    
}
