<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync FolderSync command
 *
 * @package     Syncope
 * @subpackage  Command
 */
class Syncope_Command_Provision extends Syncope_Command_Wbxml
{
    protected $_defaultNameSpace = 'uri:Provision';
    protected $_documentElement  = 'Provision';
    
    const POLICYTYPE_XML   = 'MS-WAP-Provisioning-XML';
    const POLICYTYPE_WBXML = 'MS-EAS-Provisioning-WBXML';
    
    const STATUS_SUCCESS                   = 1;
    const STATUS_PROTOCOL_ERROR            = 2;
    const STATUS_GENERAL_SERVER_ERROR      = 3;
    const STATUS_DEVICE_MANAGED_EXTERNALLY = 4;
    
    const REMOTEWIPE_REQUESTED = 1;
    const REMOTEWIPE_CONFIRMED = 2;
    
    protected $_skipValidatePolicyKey = true;
    
    protected $_policyType;
    protected $_sendPolicyKey;
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @return resource
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_inputDom);
        
        $this->_policyType     = isset($xml->Policies->Policy->PolicyType) ? (string) $xml->Policies->Policy->PolicyType : null;
        $this->_sendPolicyKey  = isset($xml->Policies->Policy->PolicyKey)  ? (int) $xml->Policies->Policy->PolicyKey     : null;
        
        if ($this->_device->remotewipe == self::REMOTEWIPE_REQUESTED && isset($xml->RemoteWipe->Status) && (int)$xml->RemoteWipe->Status == self::STATUS_SUCCESS) {
            $this->_device->remotewipe = self::REMOTEWIPE_CONFIRMED;
            $this->_device = $this->_deviceBackend->update($this->_device);
        }
        
    }
    
    /**
     * generate search command response
     *
     */
    public function getResponse()
    {
        // should we wipe the device
        if ($this->_device->remotewipe >= self::REMOTEWIPE_REQUESTED) {
            $this->_sendRemoteWipe();
        } else {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' PolicyType: ' . $this->_policyType . ' PolicyKey: ' . $this->_sendPolicyKey);
            
            if($this->_sendPolicyKey === NULL) {
                $this->_sendPolicy();
            } else {
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
        
        $policyData = '<wap-provisioningdoc>
            <characteristic type="SecurityPolicy">
                <parm name="4131" value="0"/>
                <parm name="4133" value="0"/>
            </characteristic>
            <characteristic type="Registry">
                <characteristic type="HKLM\Comm\Security\Policy\LASSD\AE\{50C13377-C66D-400C-889E-C316FC4AB374}">
                    <parm name="AEFrequencyType" value="1"/>
                    <parm name="AEFrequencyValue" value="3"/>
                </characteristic>
                <characteristic type="HKLM\Comm\Security\Policy\LASSD">
                    <parm name="DeviceWipeThreshold" value="6"/>
                </characteristic>
                <characteristic type="HKLM\Comm\Security\Policy\LASSD">
                    <parm name="CodewordFrequency" value="3"/>
                </characteristic>
                <characteristic type="HKLM\Comm\Security\Policy\LASSD\LAP\lap_pw">
                    <parm name="MinimumPasswordLength" value="5"/>
                </characteristic>
                <characteristic type="HKLM\Comm\Security\Policy\LASSD\LAP\lap_pw">
                    <parm name="PasswordComplexity" value="2"/>
                </characteristic>
            </characteristic>
        </wap-provisioningdoc>';
        
        $this->_device->policykey = $this->generatePolicyKey();
                
        $provision = $sync = $this->_outputDom->documentElement;
        $provision->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Status', 1));
        $policies = $provision->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Policies'));
        $policy = $policies->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Policy'));
        $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'PolicyType', $this->_policyType));
        $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Status', 1));
        $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'PolicyKey', $this->_device->policykey));
        if ($this->_policyType == self::POLICYTYPE_XML) {
            $data = $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Data', $policyData));
        } else {
            $data = $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Data'));
            $easProvisionDoc = $data->appendChild($this->_outputDom->createElementNS('uri:Provision', 'EASProvisionDoc'));
            $easProvisionDoc->appendChild($this->_outputDom->createElementNS('uri:Provision', 'DevicePasswordEnabled', 1));
            #$easProvisionDoc->appendChild($this->_outputDom->createElementNS('uri:Provision', 'MinDevicePasswordLength', 4));
            #$easProvisionDoc->appendChild($this->_outputDom->createElementNS('uri:Provision', 'MaxDevicePasswordFailedAttempts', 4));
            #$easProvisionDoc->appendChild($this->_outputDom->createElementNS('uri:Provision', 'MaxInactivityTimeDeviceLock', 60));
        }
        
        $this->_deviceBackend->update($this->_device);
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
        
        $this->_device->policykey = $this->generatePolicyKey();
        
        $provision = $sync = $this->_outputDom->documentElement;
        $provision->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Status', 1));
        $policies = $provision->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Policies'));
        $policy = $policies->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Policy'));
        $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'PolicyType', $this->_policyType));
        $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'Status', 1));
        $policy->appendChild($this->_outputDom->createElementNS('uri:Provision', 'PolicyKey', $this->_device->policykey));

        $this->_deviceBackend->update($this->_device);
    }
    
    public static function generatePolicyKey()
    {
        $policyKey = mt_rand(1, mt_getrandmax());
        
        return $policyKey;
    }
}
