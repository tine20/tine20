<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
class ActiveSync_Command_GetItemEstimate extends ActiveSync_Command_Wbxml 
{
    const STATUS_SUCCESS = 1;
    /**
     * A collection was invalid or one of the specified collection IDs was invalid.
     *
     */
    const STATUS_INVALID_COLLECTION = 2;
    /**
     * Sync state has not been primed yet. The Sync command must be performed first.
     *
     */
    const STATUS_SYNC_STATE_NOT_PRIMED = 3;
    const STATUS_INVALID_SYNC_KEY = 4;
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @return resource
     */
    public function handle()
    {
        # remove
        #$syncStateClass = new ActiveSync_SyncState();
        $controller = ActiveSync_Controller::getInstance();
        
        #$xml = simplexml_load_string($this->_inputDom->saveXML());
        $xml = new SimpleXMLElement($this->_inputDom->saveXML(), LIBXML_NOWARNING);
        $xml->registerXPathNamespace('GetItemEstimate', 'GetItemEstimate');
        
        $itemEstimate = $this->_outputDom->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'GetItemEstimate'));
        
        foreach ($xml->Collections->Collection as $xmlCollection) {
            $clientSyncKey = $xmlCollection->SyncKey;
            $class = $xmlCollection->Class;
            $collectionId = $xmlCollection->CollectionId;
            $filterType = $xmlCollection->FilterType;
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " synckey is $clientSyncKey class: $class collectionid: $collectionId filtertype: $filterType");
            
            # remove
            #$dataBackend = $this->_backend->factory($class);
            $dataController = ActiveSync_Controller::dataFactory($class, $this->_syncTimeStamp);
            
            $response = $itemEstimate->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Response'));
            
            try {
                // does the folder exist?
                $dataController->getFolder($collectionId);
                
                $syncState = $controller->getSyncState($this->_device, $class . '-' . $collectionId, $clientSyncKey);
            
                if($controller->validateSyncKey($this->_device, $clientSyncKey, $class . '-' . $collectionId)) {
                    $response->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Status', self::STATUS_SUCCESS));
                    $collection = $response->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Collection'));
                    $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Class', $class));
                    $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'CollectionId', $collectionId));
                    if($clientSyncKey == 1) {
                        // this is the first sync. in most cases there are data on the server.
                        $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Estimate', $dataController->getItemEstimate()));
                    } else {
                        $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Estimate', $dataController->getItemEstimate($syncState->lastsync, $this->_syncTimeStamp)));
                    }
                } else {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey $clientSyncKey provided");
                    $response->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Status', self::STATUS_INVALID_SYNC_KEY));
                    $collection = $response->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Collection'));
                    $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Class', $class));
                    $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'CollectionId', $collectionId));
                    $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Estimate', 0));              
                }
            } catch (ActiveSync_Exception_FolderNotFound $e) {
                error_log(__METHOD__ . '::' . __LINE__ . " folder not found");
                $response->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Status', self::STATUS_INVALID_COLLECTION));
                $collection = $response->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Class', $class));
                $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'CollectionId', $collectionId));                
                $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Estimate', 0));              
            } catch (ActiveSync_Exception_SyncStateNotFound $e) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey $clientSyncKey provided (syncstate not found)");
                $response->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Status', self::STATUS_INVALID_SYNC_KEY));
                $collection = $response->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Class', $class));
                $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'CollectionId', $collectionId));  
                $collection->appendChild($this->_outputDom->createElementNS('GetItemEstimate', 'Estimate', 0));              
            }
        }
    }    
}