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
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Provision_Controller_Event
 * 
 * @package     ActiveSync
 */
class ActiveSync_Command_ProvisionTests extends PHPUnit_Framework_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Commands Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::setUp()
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $testDevice = ActiveSync_Backend_DeviceTests::getTestDevice();
        
        $this->objects['device'] = ActiveSync_Controller_Device::getInstance()->create($testDevice);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
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
        
        $provision = new ActiveSync_Command_Provision($doc, $this->objects['device'], $this->objects['device']->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(ActiveSync_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:PolicyType');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(ActiveSync_Command_Provision::POLICYTYPE_WBXML, $nodes->item(0)->nodeValue, $responseDoc->saveXML());

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
        
        $provision = new ActiveSync_Command_Provision($doc, $this->objects['device'], $this->objects['device']->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(ActiveSync_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Policies/Provision:Policy/Provision:PolicyType');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(ActiveSync_Command_Provision::POLICYTYPE_WBXML, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
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
        
        $this->objects['device']->remotewipe = ActiveSync_Command_Provision::REMOTEWIPE_REQUESTED;
        $this->objects['device'] = ActiveSync_Controller_Device::getInstance()->update($this->objects['device']);
        
        $provision = new ActiveSync_Command_Provision($doc, $this->objects['device'], $this->objects['device']->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(ActiveSync_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
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
        
        $provision = new ActiveSync_Command_Provision($doc, $this->objects['device'], $this->objects['device']->policykey);
        
        $provision->handle();
        
        $responseDoc = $provision->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $this->objects['device'] = ActiveSync_Controller_Device::getInstance()->get($this->objects['device']);
        $this->assertEquals(ActiveSync_Command_Provision::REMOTEWIPE_CONFIRMED, $this->objects['device']->remotewipe);
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('Provision', 'uri:Provision');
        
        $nodes = $xpath->query('//Provision:Provision/Provision:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(ActiveSync_Command_FolderSync::STATUS_SUCCESS, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//Provision:Provision/Provision:RemoteWipe');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
    }
}
