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
 * class to handle ActiveSync Search command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_Search extends Syncroton_Command_Wbxml
{
    const STATUS_SUCCESS      = 1;
    const STATUS_SERVER_ERROR = 3;

    protected $_defaultNameSpace    = 'uri:Search';
    protected $_documentElement     = 'Search';

    /**
     * store data
     *
     * @var Syncroton_Model_StoreRequest
     */
    protected $_store;

    /**
     * parse search command request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_requestBody);

        $this->_store = new Syncroton_Model_StoreRequest($xml->Store);

        if ($this->_logger instanceof Zend_Log)
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " stores: " . print_r($this->_store, true));
    }

    /**
     * generate search command response
     *
     */
    public function getResponse()
    {
        $dataController = Syncroton_Data_Factory::factory($this->_store->name, $this->_device, new DateTime());
        
        if (! $dataController instanceof Syncroton_Data_IDataSearch) {
            throw new RuntimeException('class must be instanceof Syncroton_Data_IDataSearch');
        }
        
        try {
            // Search
            $storeResponse = $dataController->search($this->_store);
            $storeResponse->status = self::STATUS_SUCCESS;
        } catch (Exception $e) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " search exception: " . $e->getMessage());
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " saerch exception trace : " . $e->getTraceAsString());
            
            $storeResponse = new Syncroton_Model_StoreResponse(array(
               'status' => self::STATUS_SERVER_ERROR
            ));
        }

        $search = $this->_outputDom->documentElement;

        $search->appendChild($this->_outputDom->createElementNS($this->_defaultNameSpace, 'Status', self::STATUS_SUCCESS));

        $response = $search->appendChild($this->_outputDom->createElementNS($this->_defaultNameSpace, 'Response'));
        $store    = $response->appendChild($this->_outputDom->createElementNS($this->_defaultNameSpace, 'Store'));

        $storeResponse->appendXML($store, $this->_device);

        return $this->_outputDom;
    }
}
