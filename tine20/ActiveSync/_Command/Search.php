<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * search command
 * 
 * does nothing at the moment
 *
 * @package     ActiveSync
 */
class ActiveSync_Command_Search extends ActiveSync_Command_Wbxml 
{        
    const STATUS_SUCCESS      = 1;
    const STATUS_SERVER_ERROR = 3;
    
    protected $_defaultNameSpace    = 'uri:Search';
    protected $_documentElement     = 'Search';
    
    /**
     * store data
     * 
     * @var array
     */
    protected $_store = array();
    
    /**
     * parse search command request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_inputDom);
        
        $this->_store = array(
            'name' => (string) $xml->Store->Name
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " stores: " . print_r($this->_store, true));        
    }
    
    /**
     * generate search command response
     * 
     */
    public function getResponse()
    {
        $search = $this->_outputDom->documentElement;
        $search->appendChild($this->_outputDom->createElementNS($this->_defaultNameSpace, 'Status', self::STATUS_SUCCESS));
        
        $response = $search->appendChild($this->_outputDom->createElementNS($this->_defaultNameSpace, 'Response'));
        $store    = $response->appendChild($this->_outputDom->createElementNS($this->_defaultNameSpace, 'Store'));

        $store->appendChild($this->_outputDom->createElementNS($this->_defaultNameSpace, 'Status', self::STATUS_SUCCESS));
        $store->appendChild($this->_outputDom->createElementNS($this->_defaultNameSpace, 'Total', 0));
        
        $result = $store->appendChild($this->_outputDom->createElementNS($this->_defaultNameSpace, 'Result', 0));
        
        return $this->_outputDom;
    }
}
