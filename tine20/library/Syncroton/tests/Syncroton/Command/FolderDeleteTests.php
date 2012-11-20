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
class Syncroton_Command_FolderDeleteTests extends Syncroton_Command_ATestCase
{
    #protected $_logPriority = Zend_Log::DEBUG;
    
    /**
     * id of the test folder
     * 
     * @var string
     */
    protected $_testFolderId;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync FolderDelete command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton/Syncroton_TestCase::setUp()
     */
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
        
        // create a test folder
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderCreate xmlns="uri:FolderHierarchy"><SyncKey>1</SyncKey><ParentId/><DisplayName>Test Folder</DisplayName><Type>14</Type></FolderCreate>'
        );
        
        $folderCreate = new Syncroton_Command_FolderCreate($doc, $this->_device, null);
        $folderCreate->handle();
        $responseDoc = $folderCreate->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        // parse id of created folder
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderCreate/FolderHierarchy:ServerId');
        $this->_testFolderId = $nodes->item(0)->nodeValue;
    }
    
    /**
     * test delete with invalid synckey
     */
    public function testDeleteWithInvalidSyncKey()
    {
        // delete folder created above
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderDelete xmlns="uri:FolderHierarchy">
                <SyncKey>20</SyncKey>
                <ServerId>' . $this->_testFolderId . '</ServerId>
            </FolderDelete>'
        );
        $folderDelete = new Syncroton_Command_FolderDelete($doc, $this->_device, null);
        $folderDelete->handle();
        $responseDoc = $folderDelete->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderDelete/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_INVALID_SYNC_KEY, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * test deleting existing folder
     */
    public function testDeleteExsistingFolder()
    {
        // delete folder created above
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderDelete xmlns="uri:FolderHierarchy">
                <SyncKey>2</SyncKey>
                <ServerId>' . $this->_testFolderId . '</ServerId>
            </FolderDelete>'
        );
        $folderDelete = new Syncroton_Command_FolderDelete($doc, $this->_device, null);
        $folderDelete->handle();
        $responseDoc = $folderDelete->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderDelete/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//FolderHierarchy:FolderDelete/FolderHierarchy:SyncKey');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(3, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $allFolders = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CALENDAR, $this->_device, new DateTime('now'))->getAllFolders();
        
        $this->assertArrayNotHasKey($this->_testFolderId, $allFolders);
    }
    
    /**
     * test deleting existing folder
     */
    public function testDeleteNonExsistingFolder()
    {
        // delete folder created above
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderDelete xmlns="uri:FolderHierarchy">
                <SyncKey>2</SyncKey>
                <ServerId>invalidFolderId</ServerId>
            </FolderDelete>'
        );
        $folderDelete = new Syncroton_Command_FolderDelete($doc, $this->_device, null);
        $folderDelete->handle();
        $responseDoc = $folderDelete->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
        
        $nodes = $xpath->query('//FolderHierarchy:FolderDelete/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_FOLDER_NOT_FOUND, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
}
