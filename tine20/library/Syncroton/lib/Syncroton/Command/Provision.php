<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Provision command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_Provision extends Syncroton_Command_Wbxml
{
    protected $_defaultNameSpace = 'uri:Provision';
    protected $_documentElement  = 'Provision';
    
    const POLICYTYPE_WBXML = 'MS-EAS-Provisioning-WBXML';
    
    const STATUS_SUCCESS                   = 1;
    const STATUS_PROTOCOL_ERROR            = 2;
    const STATUS_GENERAL_SERVER_ERROR      = 3;
    const STATUS_DEVICE_MANAGED_EXTERNALLY = 4;
    
    const STATUS_POLICY_SUCCESS        = 1;
    const STATUS_POLICY_NOPOLICY       = 2;
    const STATUS_POLICY_UNKNOWNTYPE    = 3;
    const STATUS_POLICY_CORRUPTED      = 4;
    const STATUS_POLICY_WRONGPOLICYKEY = 5;
    
    const REMOTEWIPE_REQUESTED = 1;
    const REMOTEWIPE_CONFIRMED = 2;
    
    protected $_skipValidatePolicyKey = true;
    
    protected $_policyType;
    protected $_sendPolicyKey;
    
    /**
     * @var Syncroton_Model_DeviceInformation
     */
    protected $_deviceInformation;
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @return resource
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_requestBody);
        
        $this->_policyType     = isset($xml->Policies->Policy->PolicyType) ? (string) $xml->Policies->Policy->PolicyType : null;
        $this->_sendPolicyKey  = isset($xml->Policies->Policy->PolicyKey)  ? (int) $xml->Policies->Policy->PolicyKey     : null;
        
        if ($this->_device->remotewipe == self::REMOTEWIPE_REQUESTED && isset($xml->RemoteWipe->Status) && (int)$xml->RemoteWipe->Status == self::STATUS_SUCCESS) {
            $this->_device->remotewipe = self::REMOTEWIPE_CONFIRMED;
            $this->_device = $this->_deviceBackend->update($this->_device);
        }
        
        // try to fetch element from Settings namespace
        $settings = $xml->children('uri:Settings');
        if (isset($settings->DeviceInformation) && isset($settings->DeviceInformation->Set)) {
            $this->_deviceInformation = new Syncroton_Model_DeviceInformation($settings->DeviceInformation->Set);
            
            $this->_device->model           = $this->_deviceInformation->model;
            $this->_device->imei            = $this->_deviceInformation->iMEI;
            $this->_device->friendlyname    = $this->_deviceInformation->friendlyName;
            $this->_device->os              = $this->_deviceInformation->oS;
            $this->_device->oslanguage      = $this->_deviceInformation->oSLanguage;
            $this->_device->phonenumber     = $this->_deviceInformation->phoneNumber;
            
            $this->_device = $this->_deviceBackend->update($this->_device);
            
        }
    }
    
    /**
     * generate search command response
     *
     */
    public function getResponse()
    {
        $this->_outputDom->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Settings', 'uri:Settings');
        
        // should we wipe the device
        if ($this->_device->remotewipe >= self::REMOTEWIPE_REQUESTED) {
            $this->_sendRemoteWipe();
        } else {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' PolicyType: ' . $this->_policyType . ' PolicyKey: ' . $this->_sendPolicyKey);
            
            if($this->_sendPolicyKey === NULL) {
                $this->_sendPolicy();
            } elseif ($this->_sendPolicyKey == $this->_device->policykey) {
                $this->_acknowledgePolicy();
            }       
        } 
            
        return $this->_outputDom;
    }
    
    /**
     * function the send policy to client
     * 
     * 4131 (Enforce password on device) 0: enabled 1: disabled
     * 4133 (Unlock from computer) 0: disabled 1: enabled
     * AEFrequencyType 0: no inactivity time 1: inactivity time is set
     * AEFrequencyValue inactivity time in minutes
     * DeviceWipeThreshold after how many worng password to device should get wiped
     * CodewordFrequency validate every 3 wrong passwords, that a person is using the device which is able to read and write. should be half of DeviceWipeThreshold
     * MinimumPasswordLength minimum password length
     * PasswordComplexity 0: Require alphanumeric 1: Require only numeric, 2: anything goes
     *
     */
    protected function _sendPolicy()
    {
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' send policy to device');
        
        $provision = $sync = $this->_outputDom->documentElement;
        $provision->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Status', 1));

        // settings
        if ($this->_deviceInformation instanceof Syncroton_Model_DeviceInformation) {
            $deviceInformation = $provision->appendChild($this->_outputDom->createElementNS('uri:Settings', 'DeviceInformation'));
            $deviceInformation->appendChild($this->_outputDom->createElementNS('uri:Settings', 'Status', 1));
        }
        
        // policies
        $policies = $provision->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Policies'));
        $policy = $policies->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Policy'));
        $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'PolicyType', $this->_policyType));
        
        if ($this->_policyType != self::POLICYTYPE_WBXML) {
            $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Status', self::STATUS_POLICY_UNKNOWNTYPE));
        } elseif (empty($this->_device->policyId)) {
            $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Status', self::STATUS_POLICY_NOPOLICY));
        } else {
            $this->_device->policykey = $this->generatePolicyKey();
            
            $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Status', self::STATUS_POLICY_SUCCESS));
            $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'PolicyKey', $this->_device->policykey));
            
            $data = $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Data'));
            $easProvisionDoc = $data->appendChild($this->_outputDom->createElementNS('uri:Provision', 'EASProvisionDoc'));
            $this->_policyBackend
                ->get($this->_device->policyId)
                ->appendXML($easProvisionDoc, $this->_device);
            
            $this->_deviceBackend->update($this->_device);
        }
    }
    
    /**
     * function the send remote wipe command
     */
    protected function _sendRemoteWipe()
    {
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->warn(__METHOD__ . '::' . __LINE__ . ' send remote wipe to device');
        
        $provision = $sync = $this->_outputDom->documentElement;
        $provision->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Status', 1));
        $provision->appendChild($this->_outputDom->createElementNS('uri:Provision', 'RemoteWipe'));
    }
    
    protected function _acknowledgePolicy()
    {
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' acknowledge policy');
        
        $policykey = $this->_policyBackend->get($this->_device->policyId)->policyKey;
        
        $provision = $sync = $this->_outputDom->documentElement;
        $provision->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Status', 1));
        $policies = $provision->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Policies'));
        $policy = $policies->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Policy'));
        $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'PolicyType', $this->_policyType));
        $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Status', 1));
        $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'PolicyKey', $policykey));

        $this->_device->policykey = $policykey;
        $this->_deviceBackend->update($this->_device);
    }

    /**
     * generate a random string used as PolicyKey
     */
    public static function generatePolicyKey()
    {
        return sha1(mt_rand(). microtime());
    }
}
