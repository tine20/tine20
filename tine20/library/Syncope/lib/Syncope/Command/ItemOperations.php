<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync ItemOperations command
 *
 * @package     Syncope
 * @subpackage  Command
 */
class Syncope_Command_ItemOperations extends Syncope_Command_Wbxml 
{        
    const STATUS_SUCCESS        = 1;
    const STATUS_PROTOCOL_ERROR = 2;
    const STATUS_SERVER_ERROR   = 3;
    
    const STATUS_ITEM_FAILED_CONVERSION = 14;
    
    protected $_defaultNameSpace    = 'uri:ItemOperations';
    protected $_documentElement     = 'ItemOperations';
    
    /**
     * list of items to move
     * 
     * @var array
     */
    protected $_fetches = array();
    
    /**
     * parse MoveItems request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_inputDom);
        
        if (isset($xml->Fetch)) {
            foreach ($xml->Fetch as $fetch) {
                $fetchArray = array(
                    'store' => (string)$fetch->Store
                );
                
                // try to fetch element from namespace AirSync
                $airSync = $fetch->children('uri:AirSync');
                
                if (isset($airSync->CollectionId)) {
                    $fetchArray['collectionId'] = (string)$airSync->CollectionId;
                    $fetchArray['serverId']     = (string)$airSync->ServerId;
                }
                
                // try to fetch element from namespace AirSyncBase
                $airSyncBase = $fetch->children('uri:AirSyncBase');
                
                if (isset($airSyncBase->FileReference)) {
                    $fetchArray['fileReference'] = (string)$airSyncBase->FileReference;
                }
                
                if (isset($fetch->Options)) {
                    // try to fetch element from namespace AirSyncBase
                    $airSyncBase = $fetch->Options->children('uri:AirSyncBase');
                    
                    if (isset($airSyncBase->BodyPreference)) {
                        // required
                        $fetchArray['bodyPreferenceType'] = (int) $airSyncBase->BodyPreference->Type;
                        
                        // optional
                        if (isset($airSyncBase->BodyPreference->TruncationSize)) {
                            $fetchArray['truncationSize'] = (int) $airSyncBase->BodyPreference->TruncationSize;
                        }
                    }
                }
                $this->_fetches[] = $fetchArray;
            }
        }
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " fetches: " . print_r($this->_fetches, true));        
    }
    
    /**
     * generate ItemOperations response
     */
    public function getResponse()
    {
        // add aditional namespaces
        $this->_outputDom->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:AirSyncBase' , 'uri:AirSyncBase');
        $this->_outputDom->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:AirSync'     , 'uri:AirSync');
        
        $itemOperations = $this->_outputDom->documentElement;
        
        $itemOperations->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', Syncope_Command_ItemOperations::STATUS_SUCCESS));
        
        $response = $itemOperations->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Response'));
        
        foreach ($this->_fetches as $fetch) {
            $fetchTag = $response->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Fetch'));
            
            try {
                $dataController = Syncope_Data_Factory::factory($fetch['store'], $this->_device, $this->_syncTimeStamp);
                
                if (isset($fetch['collectionId'])) {
                    $properties = $this->_outputDom->createElementNS('uri:ItemOperations', 'Properties');
                    $dataController->appendXML($properties, $fetch, $fetch['serverId']);
                    
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', Syncope_Command_ItemOperations::STATUS_SUCCESS));
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $fetch['collectionId']));
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId',     $fetch['serverId']));
                    $fetchTag->appendChild($properties);
                    
                } elseif (isset($fetch['fileReference'])) {
                    $properties = $this->_outputDom->createElementNS('uri:ItemOperations', 'Properties');
                    $dataController->appendFileReference($properties, $fetch['fileReference']);
                    
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', Syncope_Command_ItemOperations::STATUS_SUCCESS));
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:AirSyncBase', 'FileReference', $fetch['fileReference']));
                    $fetchTag->appendChild($properties);
                }
            } catch (Tinebase_Exception_NotFound $e) {
                $response->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', Syncope_Command_ItemOperations::STATUS_ITEM_FAILED_CONVERSION));
            } catch (Exception $e) {
                $response->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', Syncope_Command_ItemOperations::STATUS_SERVER_ERROR));
            }
        }
        
        return $this->_outputDom;
    }
}
