<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Provision_Controller_Event
 * 
 * @package     ActiveSync
 */
class Syncope_Command_ProvisionTests extends Syncope_Command_ATestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Provision Command Tests');
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
        
        $provision = new Syncope_Command_Provision($doc, $this->_device, $this->_device->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:PolicyType');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_Provision::POLICYTYPE_WBXML, $nodes->item(0)->nodeValue, $responseDoc->saveXML());

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
        
        $provision = new Syncope_Command_Provision($doc, $this->_device, $this->_device->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:PolicyType');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_Provision::POLICYTYPE_WBXML, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
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
        
        $this->_device->remotewipe = Syncope_Command_Provision::REMOTEWIPE_REQUESTED;
        $this->_device = $this->_deviceBackend->update($this->_device);
        
        $provision = new Syncope_Command_Provision($doc, $this->_device, $this->_device->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
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
        
        $provision = new Syncope_Command_Provision($doc, $this->_device, $this->_device->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $this->_device = $this->_deviceBackend->update($this->_device);
        $this->assertEquals(Syncope_Command_Provision::REMOTEWIPE_CONFIRMED, $this->_device->remotewipe);
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncope_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:RemoteWipe');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
    }
}
