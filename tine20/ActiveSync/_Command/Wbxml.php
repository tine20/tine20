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
 * abstract class for all commands using wbxml encoded content
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
abstract class ActiveSync_Command_Wbxml implements ActiveSync_Command_Interface
{
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
     * informations about the currently device
     *
     * @var ActiveSync_Model_Device
     */
    protected $_device;
    
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
    
    protected $_skipValidatePolicyKey = false;
    /**
     * timestamp to use for all sync requests
     *
     * @var Tinebase_DateTime
     */
    protected $_syncTimeStamp;
        
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
     * @param  ActiveSync_Model_Device  $_device
     * @param  string                   $_policyKey
     */
    public function __construct($_requestBody, ActiveSync_Model_Device $_device = null, $_policyKey = null)
    {
        if ($this->_skipValidatePolicyKey !== true && $_policyKey === null) {
            #throw new ActiveSync_Exception_PolicyKeyMissing();
        }
        
        if ($this->_skipValidatePolicyKey !== true && ($_policyKey === 0 || $_device->policykey != $_policyKey)) {
            #throw new ActiveSync_Exception_ProvisioningNeeded();
        }
        
        // should we wipe the mobile phone?
        if ($this->_skipValidatePolicyKey !== true && !empty($_policyKey) && $_device->remotewipe >= ActiveSync_Command_Provision::REMOTEWIPE_REQUESTED) {
            throw new ActiveSync_Exception_ProvisioningNeeded();
        }
        
        $this->_policyKey = $_policyKey;
        $this->_device    = $_device;
        
        $this->_inputDom = $_requestBody;
        
        $this->_syncTimeStamp = Tinebase_DateTime::now();
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " sync timestamp: " . $this->_syncTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG));
        
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
