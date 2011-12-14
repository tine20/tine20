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
            
            // fetch values from a different namespace
            $airSyncValues  = $xmlCollection->children('uri:AirSync');
            
            $collectionData = array(
                'syncKey'       => (int)$airSyncValues->SyncKey,
                'syncKeyValid'  => true,
                'class'         => (string) $xmlCollection->Class,
                'collectionId'  => (string) $xmlCollection->CollectionId,
                'filterType'    => isset($airSyncValues->FilterType) ? (int)$airSyncValues->FilterType : 0
            );
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " synckey is {$collectionData['syncKey']} class: {$collectionData['class']} collectionid: {$collectionData['collectionId']} filtertype: {$collectionData['filterType']}");
            
            if($collectionData['syncKey'] === 0) {
                $collectionData['syncState']    = new ActiveSync_Model_SyncState(array(
                    'device_id' => $this->_device->getId(),
                    'counter'   => 0,
                    'type'      => $collectionData['class'] . '-' . $collectionData['collectionId'],
                    'lastsync'  => $this->_syncTimeStamp
                ));
            }
            
            if(($collectionData['syncState'] = $controller->validateSyncKey($this->_device, $collectionData['syncKey'], $collectionData['class'], $collectionData['collectionId'])) === false) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey {$collectionData['syncKey']} provided");
                $collectionData['syncKeyValid'] = false;
                
                $collectionData['syncState']    = new ActiveSync_Model_SyncState(array(
                    'device_id' => $this->_device->getId(),
                    'counter'   => 0,
                    'type'      => $collectionData['class'] . '-' . $collectionData['collectionId'],
                    'lastsync'  => $this->_syncTimeStamp
                ));
            }
            
            $this->_collections[$collectionData['class']][$collectionData['collectionId']] = $collectionData;
        }
    }    
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Command_Wbxml::getResponse()
     */
    public function getResponse()
    {
        $controller = ActiveSync_Controller::getInstance();
        
        $itemEstimate = $this->_outputDom->documentElement;
        
        foreach($this->_collections as $collections) {
            foreach($collections as $collectionData) {
                $response = $itemEstimate->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Response'));
                
                $dataController = ActiveSync_Controller::dataFactory($collectionData['class'], $this->_device, $this->_syncTimeStamp);
                
                try {
                    // does the folder exist?
                    $dataController->getFolder($collectionData['collectionId']);
                    $folderExists = true;
                } catch (ActiveSync_Exception_FolderNotFound $asefnf) {
                    $folderExists = false;
                }
                
                if ($folderExists !== true) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " folder does not exist");
                    $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_INVALID_COLLECTION));
                    $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 0));
                    
                } elseif ($collectionData['syncKeyValid'] !== true) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey ${collectionData['syncKey']} provided");
                    /*
                     * Android phones (and maybe others) don't take care about status 4(INVALID_SYNC_KEY)
                     * To solve the problem we always return status 1(SUCCESS) even the sync key is invalid with Estimate set to 1.
                     * This way the phone gets forced to sync. Handling invalid synckeys during sync command works without any problems.
                     * 
                        $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_INVALID_SYNC_KEY));
                        $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                        $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                        $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));  
                        $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 0));
                     */
                                                                  
                    $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_SUCCESS));
                    $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));  
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 1));                                              
                } else {
                    
                    $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_SUCCESS));
                    $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));
                    if($collectionData['syncKey'] <= 1) {
                        // this is the first sync. in most cases there are data on the server.
                        $count = count($dataController->getServerEntries($collectionData['collectionId'], $collectionData['filterType']));
                    } else {
                        $count = $this->_getItemEstimate($dataController, $collectionData);
                    }
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', $count));
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
                
                // folderState can be NULL in case of not existing folder
                if ($folderState instanceof ActiveSync_Model_FolderState) {
                    $folderState->lastfiltertype = $collectionData['filterType'];
                    $this->_folderStateBackend->update($folderState);
                }
            }
        }
                
        return $this->_outputDom;
    }

    /**
     * return number of chnaged entries
     * 
     * @param $_dataController
     * @param array $_collectionData
     * @param $_lastSyncTimeStamp
     * @return int number of changed entries
     */
    protected function _getItemEstimate($_dataController, $_collectionData)
    {
        $contentStateBackend  = new ActiveSync_Backend_ContentState();
        
        $allClientEntries   = $contentStateBackend->getClientState($this->_device, $_collectionData['class'], $_collectionData['collectionId']);
        
        $_dataController->updateCache($_collectionData['collectionId']);
        $allServerEntries   = $_dataController->getServerEntries($_collectionData['collectionId'], $_collectionData['filterType']);    
        
        $addedEntries       = array_diff($allServerEntries, $allClientEntries);
        $deletedEntries     = array_diff($allClientEntries, $allServerEntries);
        $changedEntries     = $_dataController->getChanged($_collectionData['collectionId'], $_collectionData['syncState']->lastsync, $this->_syncTimeStamp);
        
        return count($addedEntries) + count($deletedEntries) + count($changedEntries);
    }
}
