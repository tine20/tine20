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
class Syncroton_Command_MoveItemsTests extends Syncroton_Command_ATestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync MoveItems command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    protected function setUp()
    {
        parent::setUp();
        
        // do initial sync first
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <FolderSync xmlns="uri:ItemOperations"><SyncKey>0</SyncKey></FolderSync>'
        );
        
        $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, null);
        $folderSync->handle();
        $responseDoc = $folderSync->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
    }
    
    /**
     */
    public function testMoveInvalidSrcFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Moves xmlns="uri:Move"><Move><SrcMsgId>2246b0b87ee914e283d6c53717cc36c68cacd187</SrcMsgId><SrcFldId>a130b7462fde72c7d6215ce32226e1794d631fa8</SrcFldId><DstFldId>cf11782725c1e132d05fec5a7cd9862694933003</DstFldId></Move></Moves>'
        );
        
        $moveItems = new Syncroton_Command_MoveItems($doc, $this->_device, null);
        $moveItems->handle();
        $responseDoc = $moveItems->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Move', 'uri:Move');
        
        $nodes = $xpath->query('//Move:Moves/Move:Response/Move:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Exception_Status_MoveItems::INVALID_SOURCE, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
        
    /**
     */
    public function testMoveInvalidDstFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Moves xmlns="uri:Move"><Move><SrcMsgId>2246b0b87ee914e283d6c53717cc36c68cacd187</SrcMsgId><SrcFldId>addressbookFolderId</SrcFldId><DstFldId>cf11782725c1e132d05fec5a7cd9862694933003</DstFldId></Move></Moves>'
        );
        
        $moveItems = new Syncroton_Command_MoveItems($doc, $this->_device, null);
        $moveItems->handle();
        $responseDoc = $moveItems->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Move', 'uri:Move');
        
        $nodes = $xpath->query('//Move:Moves/Move:Response/Move:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Exception_Status_MoveItems::INVALID_DESTINATION, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }

    /**
     */
    public function testMoveSameDstAndSrcFolder()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Moves xmlns="uri:Move"><Move><SrcMsgId>2246b0b87ee914e283d6c53717cc36c68cacd187</SrcMsgId><SrcFldId>addressbookFolderId</SrcFldId><DstFldId>addressbookFolderId</DstFldId></Move></Moves>'
        );
        
        $moveItems = new Syncroton_Command_MoveItems($doc, $this->_device, null);
        $moveItems->handle();
        $responseDoc = $moveItems->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Move', 'uri:Move');
        
        $nodes = $xpath->query('//Move:Moves/Move:Response/Move:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Exception_Status_MoveItems::SAME_FOLDER, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
        
    /**
     */
    public function testMove()
    {
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Moves xmlns="uri:Move"><Move><SrcMsgId>contact1</SrcMsgId><SrcFldId>addressbookFolderId</SrcFldId><DstFldId>anotherAddressbookFolderId</DstFldId></Move></Moves>'
        );
        
        $moveItems = new Syncroton_Command_MoveItems($doc, $this->_device, null);
        $moveItems->handle();
        $responseDoc = $moveItems->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Move', 'uri:Move');
        
        $nodes = $xpath->query('//Move:Moves/Move:Response/Move:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_MoveItems::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Move:Moves/Move:Response/Move:SrcMsgId');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals('contact1', $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Move:Moves/Move:Response/Move:DstMsgId');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals('contact1', $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }    
}
