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
class Syncroton_Model_PolicyTests extends Syncroton_Model_ATestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton Policy model tests');
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
        $testDoc               = $imp->createDocument('uri:Provision', 'Provision', $dtd);
        $testDoc->formatOutput = true;
        $testDoc->encoding     = 'utf-8';
        
        $provDoc = $testDoc->documentElement->appendChild($testDoc->createElementNS('uri:Provision', 'EASProvisionDoc'));
        
        $provData = new Syncroton_Model_Policy(array(
            'id'                    => 'skdjhfkjsfd',
            'devicePasswordEnabled' => 1,
            #'approvedApplicationList' => array('12werwerwer', 'sdafasdfas')
        ));
        
        $provData->appendXML($provDoc, $this->_testDevice);
        
        #echo $testDoc->saveXML(); 
        
        $xpath = new DomXPath($testDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:EASProvisionDoc/Provision:DevicePasswordEnabled');
        $this->assertEquals(1, $nodes->length, $testDoc->saveXML());
        $this->assertEquals('1', $nodes->item(0)->nodeValue, $testDoc->saveXML());
        
        // try to encode XML until we have wbxml tests
        $outputStream = fopen("php://temp", 'r+');
        $encoder = new Syncroton_Wbxml_Encoder($outputStream, 'UTF-8', 3);
        $encoder->encode($testDoc);
    }
    
}
