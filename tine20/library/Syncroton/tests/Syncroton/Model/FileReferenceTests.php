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
class Syncroton_Model_FileReferenceTests extends Syncroton_Model_ATestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton email model tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test xml generation for IPhone
     * 
     * birthday must have 12 hours added
     */
    public function testAppendXmlData()
    {
        $imp                   = new DOMImplementation();
        
        $dtd                   = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
        $testDoc               = $imp->createDocument('uri:AirSync', 'Sync', $dtd);
        $testDoc->formatOutput = true;
        $testDoc->encoding     = 'utf-8';
        
        $appData = $testDoc->documentElement->appendChild($testDoc->createElementNS('uri:AirSync', 'ApplicationData'));
        
        $email = new Syncroton_Model_FileReference(array(
            'contentType' => 'text/plain',
            'data'        => 'Lars'
        ));
        
        $email->appendXML($appData, $this->_testDevice);
        
        #echo $testDoc->saveXML(); 
        
        $xpath = new DomXPath($testDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        $xpath->registerNamespace('AirSyncBase', 'uri:AirSyncBase');
        $xpath->registerNamespace('Email', 'uri:Email');
        $xpath->registerNamespace('Email2', 'uri:Email2');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/AirSyncBase:ContentType');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('text/plain', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/ItemOperations:Data');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('TGFycw==', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDoc);
    }
}
