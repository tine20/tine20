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
 * abstract class for all commands using wbxml encoded content
 *
 * @package     Syncroton
 * @subpackage  Command
 */
 
abstract class Syncroton_Command_Wbxml implements Syncroton_Command_ICommand
{
    /**
     * informations about the currently device
     *
     * @var Syncroton_Model_Device
     */
    protected $_device;
    
    /**
     * informations about the currently device
     *
     * @var Syncroton_Backend_IDevice
     */
    protected $_deviceBackend;
    
    /**
     * informations about the currently device
     *
     * @var Syncroton_Backend_IFolder
     */
    protected $_folderBackend;
    
    /**
     * @var Syncroton_Backend_ISyncState
     */
    protected $_syncStateBackend;
    
    /**
     * @var Syncroton_Backend_IContent
     */
    protected $_contentStateBackend;
    
    /**
     * 
     * @var Syncroton_Backend_IPolicy
     */
    protected $_policyBackend;
    
    /**
     * the domDocument containing the xml response from the server
     *
     * @var DOMDocument
     */
    protected $_outputDom;

    /**
     * the domDocucment containing the xml request from the client
     *
     * @var DOMDocument
     */
    protected $_requestBody;
        
    /**
     * the default namespace
     *
     * @var string
     */
    protected $_defaultNameSpace;
    
    /**
     * the main xml tag
     *
     * @var string
     */
    protected $_documentElement;
    
    /**
     * @var array
     */
    protected $_requestParameters;
    
    /**
     * @var Syncroton_Model_SyncState
     */
    protected $_syncState;
    
    protected $_skipValidatePolicyKey = false;
    
    /**
     * timestamp to use for all sync requests
     *
     * @var DateTime
     */
    protected $_syncTimeStamp;
    
    /**
     * @var string
     */
    protected $_transactionId;
    
    /**
     * @var string
     */
    protected $_policyKey;
    
    /**
     * @var Zend_Log
     */
    protected $_logger;
    
    /**
     * list of part streams
     * 
     * @var array
     */
    protected $_parts = array();
    
    /**
     * list of headers
     * 
     * @var array
     */
    protected $_headers = array();
    
    /**
     * the constructor
     *
     * @param  mixed                   $requestBody
     * @param  Syncroton_Model_Device  $device
     * @param  array                   $requestParameters
     */
    public function __construct($requestBody, Syncroton_Model_IDevice $device, $requestParameters)
    {
        $this->_requestBody       = $requestBody;
        $this->_device            = $device;
        $this->_requestParameters = $requestParameters;
        $this->_policyKey         = $requestParameters['policyKey'];
        
        $this->_deviceBackend       = Syncroton_Registry::getDeviceBackend();
        $this->_folderBackend       = Syncroton_Registry::getFolderBackend();
        $this->_syncStateBackend    = Syncroton_Registry::getSyncStateBackend();
        $this->_contentStateBackend = Syncroton_Registry::getContentStateBackend();
        $this->_policyBackend       = Syncroton_Registry::getPolicyBackend();
        if (Syncroton_Registry::isRegistered('loggerBackend')) {
            $this->_logger          = Syncroton_Registry::get('loggerBackend');
        }
        
        $this->_syncTimeStamp = new DateTime(null, new DateTimeZone('UTC'));
        
        // set default content type
        $this->_headers['Content-Type'] = 'application/vnd.ms-sync.wbxml';
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " sync timestamp: " . $this->_syncTimeStamp->format('Y-m-d H:i:s'));
        
        if (isset($this->_defaultNameSpace) && isset($this->_documentElement)) {
            // Creates an instance of the DOMImplementation class
            $imp = new DOMImplementation();
            
            // Creates a DOMDocumentType instance
            $dtd = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");
            
            // Creates a DOMDocument instance
            $this->_outputDom = $imp->createDocument($this->_defaultNameSpace, $this->_documentElement, $dtd);
            $this->_outputDom->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Syncroton', 'uri:Syncroton');
            $this->_outputDom->formatOutput = false;
            $this->_outputDom->encoding     = 'utf-8';
        }
        
        if ($this->_skipValidatePolicyKey != true) {
            if (!empty($this->_device->policyId)) {
                $policy = $this->_policyBackend->get($this->_device->policyId);
                
                if($policy->policyKey != $this->_policyKey) {
                    $this->_outputDom->documentElement->appendChild($this->_outputDom->createElementNS($this->_defaultNameSpace, 'Status', 142));
                    
                    $sepn = new Syncroton_Exception_ProvisioningNeeded();
                    $sepn->domDocument = $this->_outputDom;
                    
                    throw $sepn;
                }
                
                // should we wipe the mobile phone?
                if ($this->_device->remotewipe >= Syncroton_Command_Provision::REMOTEWIPE_REQUESTED) {
                    $this->_outputDom->documentElement->appendChild($this->_outputDom->createElementNS($this->_defaultNameSpace, 'Status', 140));
                    
                    $sepn = new Syncroton_Exception_ProvisioningNeeded();
                    $sepn->domDocument = $this->_outputDom;
                    
                    throw $sepn;
                }
            }
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Command_ICommand::getHeaders()
     */
    public function getHeaders()
    {
        return $this->_headers;
    }
    /**
     * return array of part streams
     * 
     * @return array
     */
    public function getParts()
    {
        return $this->_parts;
    }
}
