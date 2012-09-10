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
 * class to handle ActiveSync Settings command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_Settings extends Syncroton_Command_Wbxml 
{
    const STATUS_SUCCESS                = 1;
    const STATUS_PROTOCOL_ERROR         = 2;
    const STATUS_ACCESS_DENIED          = 3;
    const STATUS_SERVICE_UNAVAILABLE    = 4;
    const STATUS_INVALID_ARGUMENTS      = 5;
    const STATUS_CONFLICTING_ARGUMENTS  = 6;
    
    const STATUS_DEVICEPASSWORD_TO_LONG = 5;
    const STATUS_DEVICEPASSWORD_PASSWORD_RECOVERY_DISABLED = 7;
    
    protected $_defaultNameSpace = 'uri:Settings';
    protected $_documentElement  = 'Settings';
    
    /**
     * @var Syncroton_Model_DeviceInformation
     */
    protected $_deviceInformation;
    protected $_userInformationRequested = false;
    
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_requestBody);
        
        if(isset($xml->DeviceInformation->Set)) {
            $this->_deviceInformation = new Syncroton_Model_DeviceInformation($xml->DeviceInformation->Set);
            
            $this->_device->model           = $this->_deviceInformation->model;
            $this->_device->imei            = $this->_deviceInformation->iMEI;
            $this->_device->friendlyname    = $this->_deviceInformation->friendlyName;
            $this->_device->os              = $this->_deviceInformation->oS;
            $this->_device->oslanguage      = $this->_deviceInformation->oSLanguage;
            $this->_device->phonenumber     = $this->_deviceInformation->phoneNumber;
                        
            $this->_device = $this->_deviceBackend->update($this->_device);
        }
        
        if(isset($xml->UserInformation->Get)) {
            $this->_userInformationRequested = true;
        }
        
    }    
    
    /**
     * this function generates the response for the client
     * 
     */
    public function getResponse()
    {
        $settings = $this->_outputDom->documentElement;
        
        $settings->appendChild($this->_outputDom->createElementNS('uri:Settings', 'Status', self::STATUS_SUCCESS));
        
        if ($this->_deviceInformation instanceof Syncroton_Model_DeviceInformation) {
            $deviceInformation = $settings->appendChild($this->_outputDom->createElementNS('uri:Settings', 'DeviceInformation'));
            $set = $deviceInformation->appendChild($this->_outputDom->createElementNS('uri:Settings', 'Set'));
            $set->appendChild($this->_outputDom->createElementNS('uri:Settings', 'Status', self::STATUS_SUCCESS));
        }
        
        if($this->_userInformationRequested === true) {
            $smtpAddresses = array();
            
            $userInformation = $settings->appendChild($this->_outputDom->createElementNS('uri:Settings', 'UserInformation'));
            $userInformation->appendChild($this->_outputDom->createElementNS('uri:Settings', 'Status', self::STATUS_SUCCESS));
            $get = $userInformation->appendChild($this->_outputDom->createElementNS('uri:Settings', 'Get'));
            if(!empty($smtpAddresses)) {
                $emailAddresses = $get->appendChild($this->_outputDom->createElementNS('uri:Settings', 'EmailAddresses'));
                foreach($smtpAddresses as $smtpAddress) {
                    $emailAddresses->appendChild($this->_outputDom->createElementNS('uri:Settings', 'SMTPAddress', $smtpAddress));
                }
            }
        }

        return $this->_outputDom;
    }
}
