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
class Syncroton_Command_ItemOperationsTests extends Syncroton_Command_ATestCase
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
        
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, null);
        
        $folderSync->handle();
        
        $responseDoc = $folderSync->getResponse();
        
        
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_EMAIL, $this->_device, new DateTime(null, new DateTimeZone('UTC')));
        
        $entries = $dataController->getServerEntries('emailInboxFolderId', null);
        
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <ItemOperations xmlns="uri:ItemOperations" xmlns:AirSync="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
                <Fetch>
                    <Store>Mailbox</Store>
                    <AirSync:CollectionId>emailInboxFolderId</AirSync:CollectionId><AirSync:ServerId>' . $entries[0] . '</AirSync:ServerId>
                    <Options><BodyPreference xmlns="uri:AirSyncBase"><Type>1</Type><TruncationSize>819200</TruncationSize><AllOrNone>0</AllOrNone></BodyPreference></Options>
                </Fetch>
            </ItemOperations>'
        );
        
        $itemOperations = new Syncroton_Command_ItemOperations($doc, $this->_device, null);
        
        $itemOperations->handle();
        
        $responseDoc = $itemOperations->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ItemOperations', 'uri:ItemOperations');
        $xpath->registerNamespace('Email', 'uri:Email');
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_ItemOperations::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_ItemOperations::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Properties/Email:Subject');
        $this->assertEquals(1, $nodes->length, 'email subject missing');
        $this->assertEquals('Test Subject', $nodes->item(0)->nodeValue, $responseDoc->saveXML());

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
        
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, null);
        
        $folderSync->handle();
        
        $responseDoc = $folderSync->getResponse();
        
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <ItemOperations xmlns="uri:ItemOperations" xmlns:AirSync="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
            <Fetch><Store>Mailbox</Store><AirSyncBase:FileReference>emailInboxFolderId' . Syncroton_Data_AData::LONGID_DELIMITER . 'email1</AirSyncBase:FileReference></Fetch>
            </ItemOperations>'
        );
        
        $itemOperations = new Syncroton_Command_ItemOperations($doc, $this->_device, null);
        
        $itemOperations->handle();
        
        $responseDoc = $itemOperations->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ItemOperations', 'uri:ItemOperations');
        $xpath->registerNamespace('Email', 'uri:Email');
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Status');
        $this->assertEquals(1, $nodes->length, 'ItemOperations:Status missing');
        $this->assertEquals(Syncroton_Command_ItemOperations::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Status');
        $this->assertEquals(1, $nodes->length, 'ItemOperations:Status missing');
        $this->assertEquals(Syncroton_Command_ItemOperations::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Properties/AirSyncBase:ContentType');
        $this->assertEquals(1, $nodes->length, 'AirSyncBase:ContentType missing');
        $this->assertEquals('text/plain', $nodes->item(0)->nodeValue, 'contenttype mismatch');
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Properties/ItemOperations:Data');
        $this->assertEquals(1, $nodes->length, 'ItemOperations:Data missing');
        $this->assertEquals('TGFycw==', $nodes->item(0)->nodeValue, 'data mismatch');
    }
    
    /**
     * test fetching multipart
     */
    public function testFileReferenceMultiPart()
    {
        // do initial sync first
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:ItemOperations"><SyncKey>0</SyncKey></FolderSync>'
        );
        
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, null);
        
        $folderSync->handle();
        
        $responseDoc = $folderSync->getResponse();
        
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <ItemOperations xmlns="uri:ItemOperations" xmlns:AirSync="uri:AirSync" xmlns:AirSyncBase="uri:AirSyncBase">
            <Fetch><Store>Mailbox</Store><AirSyncBase:FileReference>emailInboxFolderId' . Syncroton_Data_AData::LONGID_DELIMITER . 'email1</AirSyncBase:FileReference></Fetch>
            </ItemOperations>'
        );
        
        $itemOperations = new Syncroton_Command_ItemOperations($doc, $this->_device, array('acceptMultipart' => true, 'policyKey' => 0));
        
        $itemOperations->handle();
        
        $responseDoc = $itemOperations->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ItemOperations', 'uri:ItemOperations');
        $xpath->registerNamespace('Email', 'uri:Email');
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Status');
        $this->assertEquals(1, $nodes->length, 'ItemOperations:Status missing');
        $this->assertEquals(Syncroton_Command_ItemOperations::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Status');
        $this->assertEquals(1, $nodes->length, 'ItemOperations:Status missing');
        $this->assertEquals(Syncroton_Command_ItemOperations::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Properties/AirSyncBase:ContentType');
        $this->assertEquals(1, $nodes->length, 'AirSyncBase:ContentType missing');
        $this->assertEquals('text/plain', $nodes->item(0)->nodeValue, 'contenttype mismatch');
        
        $nodes = $xpath->query('//ItemOperations:ItemOperations/ItemOperations:Response/ItemOperations:Fetch/ItemOperations:Properties/ItemOperations:Part');
        $this->assertEquals(1, $nodes->length, 'ItemOperations:Part missing');
        $this->assertEquals('1', $nodes->item(0)->nodeValue, 'part mismatch');
        
        $this->assertEquals(1, count($itemOperations->getParts()));
        
        $parts = $itemOperations->getParts();
        $fstat = fstat($parts[0]);
        $this->assertEquals(4, $fstat['size']);
    } 
}
