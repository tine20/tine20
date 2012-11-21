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
class Syncroton_Command_FolderUpdateTests extends Syncroton_Command_ATestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync FolderUpdate command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    protected function setUp()
    {
        parent::setUp();
        
        // do initial sync first
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
                <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
                <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, null);
        $folderSync->handle();
        $responseDoc = $folderSync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
    }
    
    /**
     * test creation of claendar folder
     */
    public function testUpdateCalendarFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderUpdate xmlns="uri:FolderHierarchy"><SyncKey>1</SyncKey><ParentId/><ServerId>calendarFolderId</ServerId><DisplayName>Test Folder Update</DisplayName><Type>13</Type></FolderUpdate>'
        );
        
        $folderUpdate = new Syncroton_Command_FolderUpdate($doc, $this->_device, null);
        
        $folderUpdate->handle();
        
        $responseDoc = $folderUpdate->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderUpdate/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//FolderHierarchy:FolderUpdate/FolderHierarchy:SyncKey');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $allFolders = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CALENDAR, $this->_device, new DateTime('now'))->getAllFolders();
        
        $this->assertArrayHasKey('calendarFolderId', $allFolders);
        $this->assertEquals('Test Folder Update', $allFolders['calendarFolderId']->displayName);
        
    }
        
    /**
     * test creation of contacts folder
     */
    public function testUpdateContactsFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderUpdate xmlns="uri:FolderHierarchy"><SyncKey>1</SyncKey><ParentId/><ServerId>anotherAddressbookFolderId</ServerId><DisplayName>Test Folder Update</DisplayName><Type>14</Type></FolderUpdate>'
        );
        
        $folderUpdate = new Syncroton_Command_FolderUpdate($doc, $this->_device, null);
        
        $folderUpdate->handle();
        
        $responseDoc = $folderUpdate->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderUpdate/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//FolderHierarchy:FolderUpdate/FolderHierarchy:SyncKey');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $allFolders = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $this->_device, new DateTime('now'))->getAllFolders();
        
        $this->assertArrayHasKey('anotherAddressbookFolderId', $allFolders);
        $this->assertEquals('Test Folder Update', $allFolders['anotherAddressbookFolderId']->displayName);
    }
    
    /**
     * test update of invalid folder
     */
    public function testUpdateInvalidFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderUpdate xmlns="uri:FolderHierarchy">
                <SyncKey>1</SyncKey>
                <ParentId/>
                <ServerId>invalidFolderId</ServerId>
                <DisplayName>Test Folder Update</DisplayName>
                <Type>14</Type>
            </FolderUpdate>'
        );
        
        $folderUpdate = new Syncroton_Command_FolderUpdate($doc, $this->_device, null);
        
        $folderUpdate->handle();
        
        $responseDoc = $folderUpdate->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderUpdate/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_FOLDER_NOT_FOUND, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * test handling of invalid SyncKey
     */
    public function testInvalidSyncKey()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderUpdate xmlns="uri:FolderHierarchy"><SyncKey>11</SyncKey><ParentId/><DisplayName>Test Folder</DisplayName><Type>15</Type></FolderUpdate>'
        );
        
        $folderUpdate = new Syncroton_Command_FolderUpdate($doc, $this->_device, null);
        
        $folderUpdate->handle();
        
        $responseDoc = $folderUpdate->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderUpdate/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_INVALID_SYNC_KEY, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
}
