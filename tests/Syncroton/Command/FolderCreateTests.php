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
class Syncroton_Command_FolderCreateTests extends Syncroton_Command_ATestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync FolderCreate command tests');
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
    }
    
    /**
     * test creation of claendar folder
     */
    public function testCreateCalendarFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderCreate xmlns="uri:FolderHierarchy"><SyncKey>1</SyncKey><ParentId/><DisplayName>Test Folder</DisplayName><Type>13</Type></FolderCreate>'
        );
        
        $folderCreate = new Syncroton_Command_FolderCreate($doc, $this->_device, null);
        
        $folderCreate->handle();
        
        $responseDoc = $folderCreate->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderCreate/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//FolderHierarchy:FolderCreate/FolderHierarchy:SyncKey');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(2, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $allFolders = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CALENDAR, $this->_device, new DateTime('now'))->getAllFolders();
        
        $nodes = $xpath->query('//FolderHierarchy:FolderCreate/FolderHierarchy:ServerId');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertFalse(empty($nodes->item(0)->nodeValue), $responseDoc->saveXML());
        $this->assertArrayHasKey($nodes->item(0)->nodeValue, $allFolders);
    }
    
    /**
     * test creation of contacts folder
     */
    public function testCreateContactsFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderCreate xmlns="uri:FolderHierarchy"><SyncKey>1</SyncKey><ParentId/><DisplayName>Test Folder</DisplayName><Type>14</Type></FolderCreate>'
        );
        
        $folderCreate = new Syncroton_Command_FolderCreate($doc, $this->_device, null);
        
        $folderCreate->handle();
        
        $responseDoc = $folderCreate->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $allFolders = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CONTACTS, $this->_device, new DateTime('now'))->getAllFolders();
        
        $nodes = $xpath->query('//FolderHierarchy:FolderCreate/FolderHierarchy:ServerId');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertFalse(empty($nodes->item(0)->nodeValue), $responseDoc->saveXML());
        $this->assertArrayHasKey($nodes->item(0)->nodeValue, $allFolders);
    }
    
    /**
     * test creation of email folder
     */
    public function testCreateEmailFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderCreate xmlns="uri:FolderHierarchy"><SyncKey>1</SyncKey><ParentId/><DisplayName>Test Folder</DisplayName><Type>12</Type></FolderCreate>'
        );
        
        $folderCreate = new Syncroton_Command_FolderCreate($doc, $this->_device, null);
        
        $folderCreate->handle();
        
        $responseDoc = $folderCreate->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $allFolders = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_EMAIL, $this->_device, new DateTime('now'))->getAllFolders();
        
        $nodes = $xpath->query('//FolderHierarchy:FolderCreate/FolderHierarchy:ServerId');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertFalse(empty($nodes->item(0)->nodeValue), $responseDoc->saveXML());
        $this->assertArrayHasKey($nodes->item(0)->nodeValue, $allFolders);
    }
    
    /**
     * test creation of tasks folder
     */
    public function testCreateTasksFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderCreate xmlns="uri:FolderHierarchy"><SyncKey>1</SyncKey><ParentId/><DisplayName>Test Folder</DisplayName><Type>15</Type></FolderCreate>'
        );
        
        $folderCreate = new Syncroton_Command_FolderCreate($doc, $this->_device, null);
        
        $folderCreate->handle();
        
        $responseDoc = $folderCreate->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $allFolders = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_TASKS, $this->_device, new DateTime('now'))->getAllFolders();
        
        $nodes = $xpath->query('//FolderHierarchy:FolderCreate/FolderHierarchy:ServerId');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertFalse(empty($nodes->item(0)->nodeValue), $responseDoc->saveXML());
        $this->assertArrayHasKey($nodes->item(0)->nodeValue, $allFolders);
    }
    
    /**
     * test handling of invalid SyncKey
     */
    public function testInvalidSyncKey()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderCreate xmlns="uri:FolderHierarchy"><SyncKey>11</SyncKey><ParentId/><DisplayName>Test Folder</DisplayName><Type>15</Type></FolderCreate>'
        );
        
        $folderCreate = new Syncroton_Command_FolderCreate($doc, $this->_device, null);
        
        $folderCreate->handle();
        
        $responseDoc = $folderCreate->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderCreate/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_INVALID_SYNC_KEY, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * test handling of invalid folder type
     */
    public function testCreateInvalidFolderType()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderCreate xmlns="uri:FolderHierarchy"><SyncKey>1</SyncKey><ParentId/><DisplayName>Test Folder</DisplayName><Type>1000</Type></FolderCreate>'
        );
        
        $folderCreate = new Syncroton_Command_FolderCreate($doc, $this->_device, null);
        
        $this->setExpectedException('Syncroton_Exception_UnexpectedValue');
        
        $folderCreate->handle();
    }
}
