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
class Syncope_Command_GetItemEstimateTests extends Syncope_Command_ATestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync GetItemEstimate command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * 
     */
    public function _testGetItemEstimateWithInvalidFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <GetItemEstimate xmlns="uri:ItemEstimate" xmlns:AirSync="uri:AirSync"><Collections><Collection><AirSync:FilterType>0</AirSync:FilterType><AirSync:SyncKey>0</AirSync:SyncKey><Class>Contacts</Class><CollectionId>1212</CollectionId></Collection></Collections></GetItemEstimate>'
        );
        
        $search = new Syncope_Command_GetItemEstimate($doc, $this->_device, null);
        
        $search->handle();
        
        $responseDoc = $search->getResponse();
        $responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ItemEstimate', 'uri:ItemEstimate');
        
        $nodes = $xpath->query('//ItemEstimate:GetItemEstimate/ItemEstimate:Response/ItemEstimate:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_GetItemEstimate::STATUS_INVALID_COLLECTION, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
        
    /**
     * 
     */
    public function testGetItemEstimate()
    {
        // first do a foldersync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $folderSync->getResponse();
        
        // and now we can start the ping request
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <GetItemEstimate xmlns="uri:ItemEstimate" xmlns:AirSync="uri:AirSync"><Collections><Collection><AirSync:FilterType>0</AirSync:FilterType><AirSync:SyncKey>0</AirSync:SyncKey><Class>Contacts</Class><CollectionId>addressbookFolderId</CollectionId></Collection></Collections></GetItemEstimate>'
        );
                
        $search = new Syncope_Command_GetItemEstimate($doc, $this->_device, null);
        $search->handle();
        $responseDoc = $search->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ItemEstimate', 'uri:ItemEstimate');
        
        $nodes = $xpath->query('//ItemEstimate:GetItemEstimate/ItemEstimate:Response/ItemEstimate:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_GetItemEstimate::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
                
        $nodes = $xpath->query('//ItemEstimate:GetItemEstimate/ItemEstimate:Response/ItemEstimate:Collection/ItemEstimate:Estimate');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(10, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
    }    
}
