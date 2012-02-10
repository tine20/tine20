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
class Syncope_Command_FolderSyncTests extends Syncope_Command_ATestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync FolderSync command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test reponse with synckey 0
     */
    public function testGetFoldersSyncKey0()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, null);
        
        $folderSync->handle();
        
        $responseDoc = $folderSync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:SyncKey');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(1, $nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:Changes/FolderHierarchy:Add');
        $this->assertGreaterThanOrEqual(1, $nodes->length, $responseDoc->saveXML());
    }
    
    /**
     * test reponse with synckey 1
     */
    public function testGetFoldersSyncKey1()
    {
        $this->testGetFoldersSyncKey0();
        
        Syncope_Data_AData::$folders['Syncope_Data_Contacts']['addressbookFolderId2'] = array(
            'folderId'    => 'addressbookFolderId2',
            'parentId'    => null,
            'displayName' => 'User created Contacts Folder',
            'type'        => Syncope_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED
        );
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>1</SyncKey></FolderSync>'
        );
    
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
    
        $folderSync->handle();
    
        $responseDoc = $folderSync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:SyncKey');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:Changes/FolderHierarchy:Add');
        $this->assertGreaterThanOrEqual(1, $nodes->length, $responseDoc->saveXML());
    }
    
    /**
     * test reponse with invalid synckey
     */
    public function testGetFoldersInvalidSyncKey()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>99</SyncKey></FolderSync>'
        );
        
        $folderSync = new Syncope_Command_FolderSync($doc, $this->_device, $this->_device->policykey);
        
        $folderSync->handle();
        
        $responseDoc = $folderSync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_FolderSync::STATUS_INVALID_SYNC_KEY, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//FolderHierarchy:FolderSync');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());

    }
    
    /**
     * this test should throw no error if the foldersync got restarted after an invalid synckey
     */
    public function testFolderSyncAfterInvalidSyncKey()
    {
        $this->testGetFoldersSyncKey0();
        $clientFolders1 = $this->_folderBackend->getFolderState($this->_device, 'Contacts');
        
        $this->testGetFoldersInvalidSyncKey();
        
        $this->testGetFoldersSyncKey0();
        $clientFolders2 = $this->_folderBackend->getFolderState($this->_device, 'Contacts');
        
        $this->assertEquals($clientFolders1["addressbookFolderId"], $clientFolders2["addressbookFolderId"]);
    }
}
