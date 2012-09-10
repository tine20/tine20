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
     * 
     * @var Syncroton_Model_Policy
     */
    protected $_policy;
    
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
    
    protected function setUp()
    {
        parent::setUp();
        
        $this->_policy = Syncroton_Registry::getPolicyBackend()->create(new Syncroton_Model_Policy(array(
            'description'           => 'description',
            'policyKey'             => '183384384',
            'name'                  => 'PHPUNIT policy',
            'allowBluetooth'        => null,
            'allowSMIMEEncryptionAlgorithmNegotiation' => 0,
            'devicePasswordEnabled' => 1,
        )));
        
        $this->_device->policyId = $this->_policy->id;
    }
    
    public function testProvisioning()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
                <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
                <FolderSync xmlns="uri:FolderHierarchy"><SyncKey>0</SyncKey></FolderSync>'
        );
    
        try {
            $folderSync = new Syncroton_Command_FolderSync($doc, $this->_device, array('policyKey' => 5));
        } catch (Syncroton_Exception_ProvisioningNeeded $sepn) {
            $responseDoc = $sepn->domDocument;
        }
    
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
    
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('FolderHierarchy', 'uri:FolderHierarchy');
    
        $nodes = $xpath->query('//FolderHierarchy:FolderSync/FolderHierarchy:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(142, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * test get empty policy
     */
    public function testGetEmptyPolicy()
    {
        $this->_device->policyId = null;
        
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Provision xmlns="uri:Provision"  xmlns:Settings="uri:Settings">
                 <Settings:DeviceInformation>
                    <Settings:Set>
                        <Settings:Model>...</Settings:Model>
                        <Settings:IMEI>...</Settings:IMEI>
                        <Settings:PhoneNumber>...</Settings:PhoneNumber>
                        <Settings:OS>...</Settings:OS>
                        <Settings:OSLanguage>...</Settings:OSLanguage>
                        <Settings:FriendlyName>...</Settings:FriendlyName>
                        <Settings:MobileOperator>...</Settings:MobileOperator>
                        <Settings:UserAgent>...</Settings:UserAgent>
                    </Settings:Set>
                </Settings:DeviceInformation>
                <Policies>
                    <Policy><PolicyType>MS-EAS-Provisioning-WBXML</PolicyType></Policy>
                </Policies>
            </Provision>'
        );
        
        $provision = new Syncroton_Command_Provision($doc, $this->_device, $this->_device->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        $xpath->registerNamespace('Settings', 'uri:Settings');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Settings:DeviceInformation/Settings:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Provision::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:PolicyType');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Provision::POLICYTYPE_WBXML, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Provision::STATUS_POLICY_NOPOLICY, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * test get policy
     */
    public function testGetPolicy()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Provision xmlns="uri:Provision"  xmlns:Settings="uri:Settings">
                 <Settings:DeviceInformation>
                    <Settings:Set>
                        <Settings:Model>...</Settings:Model>
                        <Settings:IMEI>086465r87697</Settings:IMEI>
                        <Settings:PhoneNumber>...</Settings:PhoneNumber>
                        <Settings:OS>...</Settings:OS>
                        <Settings:OSLanguage>...</Settings:OSLanguage>
                        <Settings:FriendlyName>...</Settings:FriendlyName>
                        <Settings:MobileOperator>...</Settings:MobileOperator>
                        <Settings:UserAgent>...</Settings:UserAgent>
                    </Settings:Set>
                </Settings:DeviceInformation>
                <Policies>
                    <Policy><PolicyType>MS-EAS-Provisioning-WBXML</PolicyType></Policy>
                </Policies>
            </Provision>'
        );
        
        $provision = new Syncroton_Command_Provision($doc, $this->_device, $this->_device->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        $xpath->registerNamespace('Settings', 'uri:Settings');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Settings:DeviceInformation/Settings:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Provision::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:PolicyType');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Command_Provision::POLICYTYPE_WBXML, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:PolicyKey');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertTrue(!empty($nodes->item(0)->nodeValue), $responseDoc->saveXML());

        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:Data/Provision:EASProvisionDoc/Provision:DevicePasswordEnabled');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(1, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $updatedDevice = Syncroton_Registry::getDeviceBackend()->get($this->_device->id);
        
        $this->assertEquals('086465r87697', $updatedDevice->imei);
    }
    
    /**
     * test get policy with setting device information
     */
    public function testGetPolicyWithSeetings()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <Provision xmlns="uri:Provision" xmlns:Settings="uri:Settings">
                <DeviceInformation xmlns="uri:Settings"><Set><Model>sdk</Model><OS>Android 4.1</OS><UserAgent>Android/4.1-EAS-1.3</UserAgent></Set></DeviceInformation>
                <Policies><Policy><PolicyType>MS-EAS-Provisioning-WBXML</PolicyType></Policy></Policies>
            </Provision>'
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
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:PolicyKey');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertTrue(!empty($nodes->item(0)->nodeValue), $responseDoc->saveXML());

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
            <Provision xmlns="uri:Provision"><Policies><Policy><PolicyType>MS-EAS-Provisioning-WBXML</PolicyType><PolicyKey>' . $this->_device->policykey . '</PolicyKey><Status>1</Status></Policy></Policies></Provision>'
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
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:PolicyKey');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals($this->_policy->policyKey, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
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
        #$this->_device = Syncroton_Registry::getDeviceBackend()->update($this->_device);
        
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
        
        $this->_device = Syncroton_Registry::getDeviceBackend()->get($this->_device);
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
