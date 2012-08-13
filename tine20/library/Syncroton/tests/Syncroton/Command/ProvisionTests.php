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
class Syncroton_Command_ProvisionTests extends Syncroton_Command_ATestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Syncroton Provision command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test xml generation for IPhone
     */
    public function testGetPolicy()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Provision xmlns="uri:Provision"><Policies><Policy><PolicyType>MS-EAS-Provisioning-WBXML</PolicyType></Policy></Policies></Provision>'
        );
        
        $provision = new Syncroton_Command_Provision($doc, $this->_device, $this->_device->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:PolicyType');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Provision::POLICYTYPE_WBXML, $nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:Data/Provision:EASProvisionDoc/Provision:DevicePasswordEnabled');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(1, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * test xml generation for IPhone
     * @todo validate new PolicyKey
     */
    public function testAcknowledgePolicy()
    {
        $this->testGetPolicy();
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Provision xmlns="uri:Provision"><Policies><Policy><PolicyType>MS-EAS-Provisioning-WBXML</PolicyType><PolicyKey>1307199584</PolicyKey><Status>1</Status></Policy></Policies></Provision>'
        );
        
        $provision = new Syncroton_Command_Provision($doc, $this->_device, $this->_device->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:PolicyType');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Provision::POLICYTYPE_WBXML, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * test xml generation for IPhone
     */
    public function testRemoteWipeStep1()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Provision xmlns="uri:Provision"></Provision>'
        );
        
        $this->_device->remotewipe = Syncroton_Command_Provision::REMOTEWIPE_REQUESTED;
        $this->_device = Syncroton_Registry::getDeviceBackend()->update($this->_device);
        
        $provision = new Syncroton_Command_Provision($doc, $this->_device, $this->_device->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:RemoteWipe');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
    }
    
    /**
     * test xml generation for IPhone
     */
    public function testRemoteWipeStep2()
    {
        $this->testRemoteWipeStep1();
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Provision xmlns="uri:Provision"><RemoteWipe><Status>1</Status></RemoteWipe></Provision>'
        );
        
        $provision = new Syncroton_Command_Provision($doc, $this->_device, $this->_device->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $this->_device = Syncroton_Registry::getDeviceBackend()->update($this->_device);
        $this->assertEquals(Syncroton_Command_Provision::REMOTEWIPE_CONFIRMED, $this->_device->remotewipe);
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:RemoteWipe');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
    }
}
