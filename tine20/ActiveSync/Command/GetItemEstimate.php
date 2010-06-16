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
    const STATUS_SUCCESS                = 1;
    /**
     * A collection was invalid or one of the specified collection IDs was invalid.
     *
     */
    const STATUS_INVALID_COLLECTION     = 2;
    /**
     * Sync state has not been primed yet. The Sync command must be performed first.
     *
     */
    const STATUS_SYNC_STATE_NOT_PRIMED  = 3;
    const STATUS_INVALID_SYNC_KEY       = 4;
    
    protected $_defaultNameSpace    = 'uri:ItemEstimate';
    protected $_documentElement     = 'GetItemEstimate';
    
    /**
     * list of collections
     *
     * @var array
     */
    protected $_collections = array();
    
    /**
     * the folderState sql backend
     *
     * @var ActiveSync_Backend_FolderState
     */
    protected $_folderStateBackend;
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @return resource
     */
    public function handle()
    {
        $controller = ActiveSync_Controller::getInstance();
        $this->_folderStateBackend   = new ActiveSync_Backend_FolderState();
        
        $xml = new SimpleXMLElement($this->_inputDom->saveXML(), LIBXML_NOWARNING);
        #$xml = simplexml_import_dom($this->_inputDom);
        
        foreach ($xml->Collections->Collection as $xmlCollection) {
            $class          = (string)$xmlCollection->Class;
            $collectionId   = (string)$xmlCollection->CollectionId;
            
            // fetch values from a different namespace
            $airSyncValues  = $xmlCollection->children('uri:AirSync');
            $clientSyncKey  = (string)$airSyncValues->SyncKey;
            $filterType     = isset($airSyncValues->FilterType) ? (int)$airSyncValues->FilterType : 0;
            
            $collectionData = array(
                'syncKey'       => $clientSyncKey,
                'syncKeyValid'  => true,
                'class'         => $class,
                'collectionId'  => $collectionId,
                'filterType'    => $filterType
            );
            $this->_collections[$class][$collectionId] = $collectionData;
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " synckey is $clientSyncKey class: $class collectionid: $collectionId filtertype: $filterType");
            
            if($clientSyncKey === 0 || $controller->validateSyncKey($this->_device, $clientSyncKey, $class, $collectionId) !== true) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey $clientSyncKey provided");
                $this->_collections[$class][$collectionId]['syncKeyValid'] = false;
            }
        }
    }    
    
    public function getResponse()
    {
        $controller = ActiveSync_Controller::getInstance();
        
        $itemEstimate = $this->_outputDom->documentElement;
        
        foreach($this->_collections as $class => $collections) {
            foreach($collections as $collectionId => $collectionData) {
                $response = $itemEstimate->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Response'));
                
                if($collectionData['syncKeyValid'] !== true) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey ${collectionData['syncKey']} provided");
                    $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_INVALID_SYNC_KEY));
                    $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));  
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 0));                                              
                } else {
                    $dataController = ActiveSync_Controller::dataFactory($collectionData['class'], $this->_device, $this->_syncTimeStamp);
                    
                    try {
                        // does the folder exist?
                        $dataController->getFolder($collectionData['collectionId']);
                        
                        $syncState = $controller->getSyncState($this->_device, $collectionData['class'], $collectionData['collectionId'], $collectionData['syncKey']);
                    
                        if($controller->validateSyncKey($this->_device, $collectionData['syncKey'], $collectionData['class'], $collectionData['collectionId'])) {
                            $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_SUCCESS));
                            $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                            $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                            $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));
                            if($collectionData['syncKey'] == 1) {
                                // this is the first sync. in most cases there are data on the server.
                                $count = count($dataController->getServerEntries($collectionData['collectionId'], $collectionData['filterType']));
                            } else {
                                $count = $this->_getItemEstimate(
                                    $dataController,
                                    $collectionData,
                                    $syncState->lastsync
                                );
                            }
                            $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', $count));
                        } else {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey ${collectionData['syncKey']} provided");
                            $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_INVALID_SYNC_KEY));
                            $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                            $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                            $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));
                            $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 0));              
                        }
                    } catch (ActiveSync_Exception_FolderNotFound $e) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " folder not found");
                        $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_INVALID_COLLECTION));
                        $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                        $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                        $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));                
                        $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 0));              
                    }
                    
                }
                
                // store current filter type
                $filter = new ActiveSync_Model_FolderStateFilter(array(
                    array(
                        'field'     => 'device_id',
                        'operator'  => 'equals',
                        'value'     => $this->_device->getId(),
                    ),
                    array(
                        'field'     => 'class',
                        'operator'  => 'equals',
                        'value'     => $collectionData['class'],
                    ),
                    array(
                        'field'     => 'folderid',
                        'operator'  => 'equals',
                        'value'     => $collectionData['collectionId']
                    )
                ));
                $folderState = $this->_folderStateBackend->search($filter)->getFirstRecord();
                $folderState->lastfiltertype = $collectionData['filterType'];
                $this->_folderStateBackend->update($folderState);
            }
        }
                
        parent::getResponse();
    }

    /**
     * return number of chnaged entries
     * 
     * @param $_dataController
     * @param array $_collectionData
     * @param $_lastSyncTimeStamp
     * @return int number of changed entries
     */
    private function _getItemEstimate($_dataController, $_collectionData, $_lastSyncTimeStamp)
    {
        $contentStateBackend  = new ActiveSync_Backend_ContentState();
        
        $allClientEntries   = $contentStateBackend->getClientState($this->_device, $_collectionData['class'], $_collectionData['collectionId']);
        
        $_dataController->updateCache($_collectionData['collectionId']);
        $allServerEntries   = $_dataController->getServerEntries($_collectionData['collectionId'], $_collectionData['filterType']);    
        
        $addedEntries       = array_diff($allServerEntries, $allClientEntries);
        $deletedEntries     = array_diff($allClientEntries, $allServerEntries);
        $changedEntries     = $_dataController->getChanged($_collectionData['collectionId'], $_lastSyncTimeStamp, $this->_syncTimeStamp);
        
        return count($addedEntries) + count($deletedEntries) + count($changedEntries);
    }
}