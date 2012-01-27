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
class Syncope_Command_ItemOperationsTests extends Syncope_Command_ATestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync ItemOperations command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     */
    public function testFetch()
    {
        // do initial sync first
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:ItemOperations"><SyncKey>0</SyncKey></FolderSync>'
        );
        
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, null);
        
        $folderSync->handle();
        
        $responseDoc = $folderSync->getResponse();
        
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <ItemOperations xmlns="uri:ItemOperations" xmlns:AirSync="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
            <Fetch><Store>Mailbox</Store><AirSync:CollectionId>emailInboxFolderId</AirSync:CollectionId><AirSync:ServerId>email1</AirSync:ServerId></Fetch>
    		</ItemOperations>'
        );
        
        $itemOperations = new Syncope_Command_ItemOperations($doc, $this->_device, null);
        
        $itemOperations->handle();
        
        $responseDoc = $itemOperations->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ItemOperations', 'uri:ItemOperations');
        $xpath->registerNamespace('Email', 'uri:Email');
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_ItemOperations::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_ItemOperations::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Properties/Email:Subject');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals('Subject of the email', $nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $this->assertEquals("uri:Email", $responseDoc->lookupNamespaceURI('Email'), $responseDoc->saveXML());
    }    

    /**
     */
    public function testFileReference()
    {
        // do initial sync first
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:ItemOperations"><SyncKey>0</SyncKey></FolderSync>'
        );
        
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, null);
        
        $folderSync->handle();
        
        $responseDoc = $folderSync->getResponse();
        
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <ItemOperations xmlns="uri:ItemOperations" xmlns:AirSync="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
            <Fetch><Store>Mailbox</Store><AirSyncBase:FileReference>email1-10</AirSyncBase:FileReference></Fetch>
    		</ItemOperations>'
        );
        
        $itemOperations = new Syncope_Command_ItemOperations($doc, $this->_device, null);
        
        $itemOperations->handle();
        
        $responseDoc = $itemOperations->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ItemOperations', 'uri:ItemOperations');
        $xpath->registerNamespace('Email', 'uri:Email');
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_ItemOperations::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_ItemOperations::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Properties/AirSyncBase:ContentType');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals('text/plain', $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }    
}
