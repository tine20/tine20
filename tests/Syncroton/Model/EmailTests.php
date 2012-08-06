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
class Syncroton_Model_EmailTests extends PHPUnit_Framework_TestCase
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
        
        $email = new Syncroton_Model_Email(array(
            'AccountId'    => 'FooBar',
            'Attachments'  => array(
                new Syncroton_Model_EmailAttachment(array(
                    'FileReference' => '12345abcd',
                    'UmAttOrder'    => 1
                ))
            ),
            'Categories'   => array('123', '456'),
            'Cc'           => 'l.kneschke@metaways.de',
            'DateReceived' => new DateTime('2012-03-21 14:00:00', new DateTimeZone('UTC')), 
            'From'         => 'k.kneschke@metaways.de',
            'Subject'      => 'Test Subject',
            'To'           => 'j.kneschke@metaways.de',
            'Read'         => 1,
            'Body'         => new Syncroton_Model_EmailBody(array(
                'Type'         => Syncroton_Model_EmailBody::TYPE_HTML,
                'Data'         => 'Hallo <br>',
                'EstimatedDataSize' => 1234,
                'Truncated'    => 1
            ))
        ));
        
        $email->appendXML($appData);
        
        #echo $testDoc->saveXML(); 
        
        $xpath = new DomXPath($testDoc);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        $xpath->registerNamespace('AirSyncBase', 'uri:AirSyncBase');
        $xpath->registerNamespace('Email', 'uri:Email');
        $xpath->registerNamespace('Email2', 'uri:Email2');
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Email2:AccountId');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('FooBar', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Email:DateReceived');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('2012-03-21T14:00:00Z', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/AirSyncBase:Attachments/AirSyncBase:Attachment/AirSyncBase:FileReference');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('12345abcd', $nodes->item(0)->nodeValue, $testDoc->saveXML());

        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/AirSyncBase:Attachments/AirSyncBase:Attachment/Email2:UmAttOrder');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('1', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        $nodes = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/AirSyncBase:Body/AirSyncBase:Type');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals(Syncroton_Model_EmailBody::TYPE_HTML, $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDoc);
    }
}
