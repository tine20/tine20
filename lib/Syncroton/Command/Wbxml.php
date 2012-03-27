<?php
/**
 * Tine 2.0
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
 
abstract class Syncroton_Command_Wbxml implements Syncroton_Command_Interface
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
    protected $_inputDom;
        
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
     * @var string
     */
    protected $_policyKey;
    
    /**
     * @var Syncroton_Model_SyncState
     */
    protected $_syncState;
    
    protected $_skipValidatePolicyKey = false;
    
    /**
     * timestamp to use for all sync requests
     *
     * @var Tinebase_DateTime
     */
    protected $_syncTimeStamp;
    
    /**
     * @var string
     */
    protected $_transactionId;
    /**
     * @var Zend_Log
     */
    protected $_logger;
        
    const FILTERTYPE_ALL            = 0;
    const FILTERTYPE_1DAY           = 1;
    const FILTERTYPE_3DAYS          = 2;
    const FILTERTYPE_1WEEK          = 3;
    const FILTERTYPE_2WEEKS         = 4;
    const FILTERTYPE_1MONTH         = 5;
    const FILTERTYPE_3MONTHS        = 6;
    const FILTERTYPE_6MONTHS        = 7;

    const TRUNCATION_HEADERS        = 0;
    const TRUNCATION_512B           = 1;
    const TRUNCATION_1K             = 2;
    const TRUNCATION_5K             = 4;
    const TRUNCATION_ALL            = 9;
    
    /**
     * the constructor
     *
     * @param  mixed                    $_requestBody
     * @param  Syncroton_Model_Device  $_device
     * @param  string                   $_policyKey
     */
    public function __construct($_requestBody, Syncroton_Model_IDevice $_device, $_policyKey)
    {
        $this->_policyKey = $_policyKey;
        $this->_device    = $_device;
        
        $this->_deviceBackend       = Syncroton_Registry::get('deviceBackend');
        $this->_folderBackend       = Syncroton_Registry::get('folderStateBackend');
        $this->_syncStateBackend    = Syncroton_Registry::get('syncStateBackend');
        $this->_contentStateBackend = Syncroton_Registry::get('contentStateBackend');
        if (Syncroton_Registry::isRegistered('loggerBackend')) {
            $this->_logger          = Syncroton_Registry::get('loggerBackend');
        }
        
        if ($this->_skipValidatePolicyKey !== true && $this->_policyKey === null) {
            #throw new Syncroton_Exception_PolicyKeyMissing();
        }
        
        if ($this->_skipValidatePolicyKey !== true && ($this->_policyKey === 0 || $this->_device->policykey != $this->_policyKey)) {
            #throw new Syncroton_Exception_ProvisioningNeeded();
        }
        
        // should we wipe the mobile phone?
        if ($this->_skipValidatePolicyKey !== true && !empty($this->_policyKey) && $this->_device->remotewipe >= Syncroton_Command_Provision::REMOTEWIPE_REQUESTED) {
            throw new Syncroton_Exception_ProvisioningNeeded();
        }
        
        $this->_inputDom = $_requestBody;
        
        $this->_syncTimeStamp = new DateTime(null, new DateTimeZone('UTC'));
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " sync timestamp: " . $this->_syncTimeStamp->format('Y-m-d H:i:s'));
        
        // Creates an instance of the DOMImplementation class
        $imp = new DOMImplementation();
        
        // Creates a DOMDocumentType instance
        $dtd = $imp->createDocumentType('AirSync', "-//AIRSYNC//DTD AirSync//EN", "http://www.microsoft.com/");

        // Creates a DOMDocument instance
        $this->_outputDom = $imp->createDocument($this->_defaultNameSpace, $this->_documentElement, $dtd);
        $this->_outputDom->formatOutput = false;
        $this->_outputDom->encoding     = 'utf-8';
        
    }    
}
