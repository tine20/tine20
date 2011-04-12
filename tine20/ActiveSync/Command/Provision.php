<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
class ActiveSync_Command_Provision extends ActiveSync_Command_Wbxml 
{
    const POLICYTYPE_XML = 'MS-WAP-Provisioning-XML';
    const POLICYTYPE_WBXML = 'MS-EAS-Provisioning-WBXML';
    
    protected $_policyType;
    protected $_policyKey;
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @return resource
     */
    public function handle()
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ');
        
        $controller = ActiveSync_Controller::getInstance();
        
        #$imp = new DOMImplementation;
        #$this->_outputDom = $imp->createDocument();
        #$this->_outputDom->formatOutput = false;
        
        #$xml = simplexml_load_string($this->_inputDom->saveXML());
        $xml = new SimpleXMLElement($this->_inputDom->saveXML(), LIBXML_NOWARNING);
        $xml->registerXPathNamespace('Provision:', 'Provision:');    

        $this->_policyType = $xml->Policies->Policy->PolicyType;
        $this->_policyKey = isset($xml->Policies->Policy->PolicyKey) ? (int)$xml->Policies->Policy->PolicyKey : null;
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' PolicyType: ' . $this->_policyType . ' PolicyKey: ' . $this->_policyKey);
        
        if($this->_policyKey === NULL) {
            $this->_sendPolicy();
        } else {
            $this->_acknowledgePolicy();
        }        
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
        
        $newPolicyKey = $this->generatePolicyKey();
                
        $provision = $this->_outputDom->appendChild($this->_outputDom->createElementNS('Provision:', 'Provision'));
        $provision->appendChild($this->_outputDom->createElementNS('Provision:', 'Status', 1));
        $policies = $provision->appendChild($this->_outputDom->createElementNS('Provision:', 'Policies'));
        $policy = $policies->appendChild($this->_outputDom->createElementNS('Provision:', 'Policy'));
        $policy->appendChild($this->_outputDom->createElementNS('Provision:', 'PolicyType', $this->_policyType));
        $policy->appendChild($this->_outputDom->createElementNS('Provision:', 'Status', 1));
        $policy->appendChild($this->_outputDom->createElementNS('Provision:', 'PolicyKey', $newPolicyKey));
        $data = $policy->appendChild($this->_outputDom->createElementNS('Provision:', 'Data', $policyData));
        #$provision->appendChild($this->_outputDom->createElementNS('Provision:', 'RemoteWipe'));
        
        $this->_device->policykey = $newPolicyKey;
        ActiveSync_Controller::getInstance()->updateDevice($this->_device);
    }
    
    protected function _acknowledgePolicy()
    {
        $newPolicyKey = $this->generatePolicyKey();
        
        $provision = $this->_outputDom->appendChild($this->_outputDom->createElementNS('Provision:', 'Provision'));
        $provision->appendChild($this->_outputDom->createElementNS('Provision:', 'Status', 1));
        $policies = $provision->appendChild($this->_outputDom->createElementNS('Provision:', 'Policies'));
        $policy = $policies->appendChild($this->_outputDom->createElementNS('Provision:', 'Policy'));
        $policy->appendChild($this->_outputDom->createElementNS('Provision:', 'PolicyType', $this->_policyType));
        $policy->appendChild($this->_outputDom->createElementNS('Provision:', 'Status', 1));
        $policy->appendChild($this->_outputDom->createElementNS('Provision:', 'PolicyKey', $newPolicyKey));

        $this->_device->policykey = $newPolicyKey;
        ActiveSync_Controller::getInstance()->updateDevice($this->_device);
    }
    
    public static function generatePolicyKey()
    {
        $policyKey = mt_rand(1, mt_getrandmax());
        
        return $policyKey;
    }
}
