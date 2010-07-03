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
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 */
class ActiveSync_Command_ItemOperations extends ActiveSync_Command_Wbxml 
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
        $xml = new SimpleXMLElement($this->_inputDom->saveXML());
        #$xml = simplexml_import_dom($this->_inputDom);
        
        if (isset($xml->Fetch)) {
            foreach ($xml->Fetch as $fetch) {
                $fetchArray = array(
                    'store' => (string)$fetch->Store
                );
                
                // try to fetch element from namespace AirSync
                $airSync = $fetch->children('uri:AirSync');
                $fetchArray['collectionId'] = (string)$airSync->CollectionId;
                $fetchArray['serverId']     = (string)$airSync->ServerId;
                
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " fetches: " . print_r($this->_fetches, true));        
    }
    
    /**
     * generate ItemOperations response
     * 
     */
    public function getResponse()
    {
        $folderStateBackend   = new ActiveSync_Backend_FolderState();
        
        $itemOperations = $this->_outputDom->documentElement;
        
        $itemOperations->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', ActiveSync_Command_ItemOperations::STATUS_SUCCESS));
        
        $response = $itemOperations->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Response'));
        
        foreach ($this->_fetches as $fetch) {
            $fetchTag = $response->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Fetch'));
            
            try {
                $folder         = $folderStateBackend->getByProperty($fetch['collectionId'], 'folderid');
                $dataController = ActiveSync_Controller::dataFactory($folder->class, $this->_device, $this->_syncTimeStamp);
                
                $fetchTag->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', ActiveSync_Command_ItemOperations::STATUS_SUCCESS));
                $fetchTag->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $fetch['collectionId']));
                $fetchTag->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId',     $fetch['serverId']));
                
                $properties = $fetchTag->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Properties'));
                $dataController->appendXML($properties, $fetch['collectionId'], $fetch['serverId'], $fetch, true);
                
            } catch (Tinebase_Exception_NotFound $e) {
                $response->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', ActiveSync_Command_ItemOperations::STATUS_ITEM_FAILED_CONVERSION));
            } catch (Exception $e) {
                $response->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', ActiveSync_Command_ItemOperations::STATUS_SERVER_ERROR));
            }
        }
        
        parent::getResponse();
    }
}