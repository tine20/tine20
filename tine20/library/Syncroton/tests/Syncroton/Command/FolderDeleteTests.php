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
     * test xml generation for IPhone
     */
    public function testDeleteFolder()
    {
        // do initial sync first
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
                    <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
                    <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
        
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, null);
        $folderSync->handle();
        $responseDoc = $folderSync->getResponse();
        
        
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
        
        $nodes = $xpath->query('//FolderHierarchy:FolderCreate/FolderHierarchy:ServerId');
        $newFolderId = $nodes->item(0)->nodeValue;
        
        
        // delete folder created above
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderDelete xmlns="uri:FolderHierarchy"><SyncKey>2</SyncKey><ServerId>' . $newFolderId . '</ServerId></FolderDelete>'
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
        
        $this->assertArrayNotHasKey($newFolderId, Syncroton_Data_Contacts::$folders);
    }    
}
