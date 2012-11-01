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
class Syncroton_Command_GetItemEstimateTests extends Syncroton_Command_ATestCase
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
    public function testGetItemEstimateWithInvalidFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <GetItemEstimate xmlns="uri:ItemEstimate" xmlns:AirSync="uri:AirSync"><Collections><Collection><Options xmlns="uri:AirSync"><AirSync:FilterType>4</AirSync:FilterType></Options><AirSync:SyncKey>0</AirSync:SyncKey><Class>Contacts</Class><CollectionId>1212</CollectionId></Collection></Collections></GetItemEstimate>'
        );
        
        $search = new Syncroton_Command_GetItemEstimate($doc, $this->_device, null);
        
        $search->handle();
        
        $responseDoc = $search->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ItemEstimate', 'uri:ItemEstimate');
        
        $nodes = $xpath->query('//ItemEstimate:GetItemEstimate/ItemEstimate:Response/ItemEstimate:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_GetItemEstimate::STATUS_INVALID_COLLECTION, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * 
     */
    public function testGetItemEstimateWithInvalidSynckey()
    {
        // first do a foldersync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $folderSync->getResponse();
        
        // and now we can send the GetItemEstimate command
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <GetItemEstimate xmlns="uri:ItemEstimate" xmlns:AirSync="uri:AirSync"><Collections><Collection><Options xmlns="uri:AirSync"><AirSync:FilterType>0</AirSync:FilterType></Options><AirSync:SyncKey>10</AirSync:SyncKey><Class>Contacts</Class><CollectionId>addressbookFolderId</CollectionId></Collection></Collections></GetItemEstimate>'
        );
        
        $search = new Syncroton_Command_GetItemEstimate($doc, $this->_device, null);
        
        $search->handle();
        
        $responseDoc = $search->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ItemEstimate', 'uri:ItemEstimate');
        
        $nodes = $xpath->query('//ItemEstimate:GetItemEstimate/ItemEstimate:Response/ItemEstimate:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_GetItemEstimate::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
            
    /**
     * 
     */
    public function testGetItemEstimateWithSynckey0()
    {
        // first do a foldersync
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        $folderSync->handle();
        $folderSync->getResponse();
        
        // and now we can send the GetItemEstimate command
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <GetItemEstimate xmlns="uri:ItemEstimate" xmlns:AirSync="uri:AirSync"><Collections><Collection><Options xmlns="uri:AirSync"><AirSync:FilterType>0</AirSync:FilterType></Options><AirSync:SyncKey>0</AirSync:SyncKey><Class>Contacts</Class><CollectionId>addressbookFolderId</CollectionId></Collection></Collections></GetItemEstimate>'
        );
        
        $search = new Syncroton_Command_GetItemEstimate($doc, $this->_device, null);
        $search->handle();
        $responseDoc = $search->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ItemEstimate', 'uri:ItemEstimate');
        
        $nodes = $xpath->query('//ItemEstimate:GetItemEstimate/ItemEstimate:Response/ItemEstimate:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_GetItemEstimate::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemEstimate:GetItemEstimate/ItemEstimate:Response/ItemEstimate:Collection/ItemEstimate:Estimate');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(10, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
    }
    
    /**
     * 
     */
    public function testGetItemEstimateWithSynckey2()
    {
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
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>0</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>2</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        $sync->handle();
        $responseDoc = $sync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        
        // now do the first sync with a windowsize of 2
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Sync xmlns="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase"><Collections><Collection><Class>Contacts</Class><SyncKey>1</SyncKey><CollectionId>addressbookFolderId</CollectionId><DeletesAsMoves/><GetChanges/><WindowSize>2</WindowSize><Options><AirSyncBase:BodyPreference><AirSyncBase:Type>1</AirSyncBase:Type><AirSyncBase:TruncationSize>5120</AirSyncBase:TruncationSize></AirSyncBase:BodyPreference><Conflict>1</Conflict></Options></Collection></Collections></Sync>'
        );
        
        $sync = new Syncroton_Command_Sync($doc, $this->_device, $this->_device->policykey);
        $sync->handle();
        $responseDoc = $sync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        
        // and now we can send the GetItemEstimate command
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <GetItemEstimate xmlns="uri:ItemEstimate" xmlns:AirSync="uri:AirSync"><Collections><Collection><Options xmlns="uri:AirSync"><AirSync:FilterType>0</AirSync:FilterType></Options><AirSync:SyncKey>2</AirSync:SyncKey><Class>Contacts</Class><CollectionId>addressbookFolderId</CollectionId></Collection></Collections></GetItemEstimate>'
        );
        
        $search = new Syncroton_Command_GetItemEstimate($doc, $this->_device, null);
        $search->handle();
        $responseDoc = $search->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ItemEstimate', 'uri:ItemEstimate');
        
        $nodes = $xpath->query('//ItemEstimate:GetItemEstimate/ItemEstimate:Response/ItemEstimate:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_GetItemEstimate::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemEstimate:GetItemEstimate/ItemEstimate:Response/ItemEstimate:Collection/ItemEstimate:Estimate');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(8, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
    }
}
