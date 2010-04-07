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
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
class ActiveSync_Command_Settings extends ActiveSync_Command_Wbxml 
{
    const STATUS_SUCCESS                = 1;
    const STATUS_PROTOCOL_ERROR         = 2;
    const STATUS_ACCESS_DENIED          = 3;
    const STATUS_SERVICE_UNAVAILABLE    = 4;
    const STATUS_INVALID_ARGUMENTS      = 5;
    const STATUS_CONFLICTING_ARGUMENTS  = 6;
    
    const STATUS_DEVICEPASSWORD_TO_LONG = 5;
    const STATUS_DEVICEPASSWORD_PASSWORD_RECOVERY_DISABLED = 7;
    
    /**
     * Enter description here...
     *
     * @var ActiveSync_Backend_StandAlone_Abstract
     */
    protected $_dataBackend;

    protected $_defaultNameSpace = 'uri:Settings';
    protected $_documentElement  = 'Settings';
    
    protected $_deviceInformationSet     = false;
    protected $_userInformationRequested = false;
    
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @todo we need to stored the initial data for folders and lifetime as the phone is sending them only when they change
     * @return resource
     */
    public function handle()
    {
        $controller = ActiveSync_Controller::getInstance();
        
        $xml = simplexml_import_dom($this->_inputDom);
        
        if(isset($xml->DeviceInformation->Set)) {
            $this->_deviceInformationSet = true;
            
            $this->_device->model           = (string)$xml->DeviceInformation->Set->Model;
            $this->_device->imei            = (string)$xml->DeviceInformation->Set->IMEI;
            $this->_device->friendlyname    = (string)$xml->DeviceInformation->Set->FriendlyName;
            $this->_device->os              = (string)$xml->DeviceInformation->Set->OS;
            $this->_device->oslanguage      = (string)$xml->DeviceInformation->Set->OSLanguage;
            $this->_device->phonenumber     = (string)$xml->DeviceInformation->Set->PhoneNumber;
            
            $this->_device = $controller->updateDevice($this->_device);
        }
        
        if(isset($xml->UserInformation->Get)) {
            $this->_userInformationRequested = true;
        }
        
    }    
    
    /**
     * this function generates the response for the client
     */
    public function getResponse()
    {
        $settings = $this->_outputDom->documentElement;
        
        $settings->appendChild($this->_outputDom->createElementNS('uri:Settings', 'Status', self::STATUS_SUCCESS));
        
        if($this->_deviceInformationSet === true) {
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
        
        parent::getResponse();
    }
}