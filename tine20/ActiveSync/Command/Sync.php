<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     ActiveSync
 */
 
class ActiveSync_Command_Sync extends ActiveSync_Command_Wbxml 
{
    const STATUS_SUCCESS                                = 1;
    const STATUS_PROTOCOL_VERSION_MISMATCH              = 2;
    const STATUS_INVALID_SYNC_KEY                       = 3;
    const STATUS_PROTOCOL_ERROR                         = 4;
    const STATUS_SERVER_ERROR                           = 5;
    const STATUS_ERROR_IN_CLIENT_SERVER_CONVERSION      = 6;
    const STATUS_CONFLICT_MATCHING_THE_CLIENT_AND_SERVER_OBJECT = 7;
    const STATUS_OBJECT_NOT_FOUND                       = 8;
    const STATUS_USER_ACCOUNT_MAYBE_OUT_OF_DISK_SPACE   = 9;
    
    const CONFLICT_OVERWRITE_SERVER                     = 0;
    const CONFLICT_OVERWRITE_PIM                        = 1;

    /**
     * An error occurred while setting the notification GUID. = 10
     * Device has not been provisioned for notifications yet. = 11
     */
    
    protected $_defaultNameSpace    = 'uri:AirSync';
    protected $_documentElement     = 'Sync';
    
    /**
     * list of collections
     *
     * @var array
     */
    protected $_collections = array();

    /**
     * the contentState sql backend
     *
     * @var ActiveSync_Backend_ContentState
     */
    protected $_contentStateBackend;
    
    /**
     * total count of items in all collections
     *
     * @var integer
     */
    protected $_totalCount;
    
    /**
     * there are more entries than WindowSize available
     * the MoreAvailable tag hot added to the xml output
     *
     * @var boolean
     */
    protected $_moreAvailable = false;
    
    /**
     * instance of ActiveSync_Controller
     *
     * @var ActiveSync_Controller
     */
    protected $_controller;
    
    protected $_session;
    
    /**
     * the constructor
     *
     * @param ActiveSync_Model_Device $_device
     */
    public function __construct(ActiveSync_Model_Device $_device)
    {
        parent::__construct($_device);
        
        $this->_contentStateBackend  = new ActiveSync_Backend_ContentState();
        $this->_session              = new Zend_Session_Namespace('moreData');
        $this->_controller           = ActiveSync_Controller::getInstance();
        // continue sync / MoreAvailable sent in previous repsonse
        if(isset($this->_session->syncTimeStamp)) {
            $this->_syncTimeStamp = $this->_session->syncTimeStamp;
        }
    }
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @return resource
     */
    public function handle()
    {
        // input xml
        $xml = new SimpleXMLElement($this->_inputDom->saveXML());
        #$xml = simplexml_import_dom($this->_inputDom);
                
        foreach ($xml->Collections->Collection as $xmlCollection) {
            $clientSyncKey  = (int)$xmlCollection->SyncKey;
            $class          = (string)$xmlCollection->Class;
            $collectionId   = (string)$xmlCollection->CollectionId;
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " SyncKey is $clientSyncKey Class: $class CollectionId: $collectionId");
            
            $collectionData = array(
                'syncKey'       => $clientSyncKey,
                'syncKeyValid'  => true,
                'class'         => $class,
                'collectionId'  => $collectionId,
                'windowSize'    => isset($xmlCollection->WindowSize) ? (int)$xmlCollection->WindowSize : 100,
                'getChanges'    => isset($xmlCollection->GetChanges) ? true : false,
                'added'         => array(),
                'changed'       => array(),
                'deleted'       => array(),
                'forceAdd'      => array(),
                'forceChange'   => array()
            );
            $this->_collections[$class] = $collectionData;
            
            if($clientSyncKey === 0 || $this->_controller->validateSyncKey($this->_device, $clientSyncKey, $class . '-' . $collectionId) !== true) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey $clientSyncKey provided");
                $this->_collections[$class]['syncKeyValid'] = false;
                continue;
            }
            
            $dataController = ActiveSync_Controller::dataFactory($class, $this->_syncTimeStamp);
            
            // handle incoming data
            
            if(isset($xmlCollection->Commands->Add)) {
                $adds = $xmlCollection->Commands->Add;
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($adds) . " entries to be added to server");
                
                foreach ($adds as $add) {
                    // search for existing entries if first sync
                    if($clientSyncKey == 1) {
                        $existing = $dataController->search($_collectionId, $add->ApplicationData);
                    } else {
                        $existing = array(); // count() == 0
                    }
                    
                    if(count($existing) === 0) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " entry not found. adding as new");
                        $added = $dataController->add($_collectionId, $add->ApplicationData);
                    } else {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found matching entry. reuse existing entry");
                        // use the first found entry
                        $added = $existing[0];
                    }
                    $this->_collections[$class]['added'][$added->getId()] = (string)$add->ClientId;
                    $this->_addContentState($collectionData['class'], $added->getId());
                }
            }
        
            // handle changes, but only if not first sync
            if($clientSyncKey > 1 && isset($xmlCollection->Commands->Change)) {
                $changes = $xmlCollection->Commands->Change;
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($changes) . " entries to be updated on server");
                foreach ($changes as $change) {
                    $serverId = (string)$change->ServerId;
                    try {
                        $changed = $dataController->change($_collectionId, $serverId, $change->ApplicationData);
                        $this->_collections[$class]['changed'][$serverId] = self::STATUS_SUCCESS;
                    } catch (Tinebase_Exception_AccessDenied $e) {
                        $this->_collections[$class]['changed'][$serverId] = self::STATUS_CONFLICT_MATCHING_THE_CLIENT_AND_SERVER_OBJECT;
                        $this->_collections[$class]['forceChange'][$serverId] = $serverId;
                    }
                }
            }
        
            // handle deletes, but only if not first sync
            if($clientSyncKey > 1 && isset($xmlCollection->Commands->Delete)) {
                $deletes = $xmlCollection->Commands->Delete;
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($deletes) . " entries to be deleted on server");
                foreach ($deletes as $delete) {
                    $serverId = (string)$delete->ServerId;
                    try {
                        $dataController->delete($_collectionId, $serverId);
                    } catch(Tinebase_Exception_NotFound $e) {
                        Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' tried to delete entry ' . $serverId . ' but entry was not found');
                    } catch (Tinebase_Exception $e) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' tried to delete entry ' . $serverId . ' but permission was denied');
                        $this->_collections[$class]['forceAdd'][$serverId] = $serverId;
                    }
                    $this->_collections[$class]['deleted'][$serverId] = self::STATUS_SUCCESS;
                    $this->_deleteContentState($collectionData['class'], $serverId);
                }
            }            
        }        
    }    
    
    public function getResponse()
    {
        // add aditional namespaces for contacts and tasks
        $this->_outputDom->documentElement->setAttribute('xmlns:' . 'Contacts', 'uri:Contacts');
        $this->_outputDom->documentElement->setAttribute('xmlns:' . 'Tasks', 'uri:Tasks');
        
        $sync = $this->_outputDom->documentElement;
        
        $collections = $sync->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collections'));
        
        foreach($this->_collections as $class => $collectionData) {
            if($collectionData['syncKeyValid'] !== true) {
                $newSyncKey = 1;
                $status = $collectionData['syncKey'] == 0 ? self::STATUS_SUCCESS : self::STATUS_INVALID_SYNC_KEY;

                // Sync 0
                // send back a new SyncKey only
                $collection = $collections->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Class', $collectionData['class']));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'SyncKey', $newSyncKey));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $collectionData['collectionId']));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', $status));
                
                $this->_contentStateBackend->resetState($this->_device, $collectionData['class']);
                
                                
            } else {
                $newSyncKey = $collectionData['syncKey'] + 1;
                
                // collection header
                $collection = $collections->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Class', $collectionData['class']));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'SyncKey', $newSyncKey));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $collectionData['collectionId']));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_SUCCESS));
                
                $responses = NULL;
                // sent reponse for newly added entries
                if(!empty($collectionData['added'])) {
                    if($responses === NULL) {
                        $responses = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Responses'));
                    }
                    foreach($collectionData['added'] as $serverId => $clientId) {
                        $add = $responses->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Add'));
                        $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ClientId', $clientId));
                        $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                        $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_SUCCESS));
                    }
                }
                
                // sent reponse for changed entries
                if(!empty($collectionData['changed'])) {
                    if($responses === NULL) {
                        $responses = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Responses'));
                    }
                    foreach($collectionData['changed'] as $serverId => $status) {
                        $change = $responses->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Change'));
                        $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                        $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', $status));
                    }
                }
                
                if($collectionData['getChanges'] === true) {
                    $dataController = ActiveSync_Controller::dataFactory($collectionData['class'], $this->_syncTimeStamp);
                    
                    if($collectionData['syncKey'] === 1) {
                        // all entries available
                        $serverAdds = $dataController->getSince('added', '0000-00-00 00:00:00', $this->_syncTimeStamp);
                    } else {
                        if(isset($this->_session->serverAdds[$collectionData['class']])) {
                            $serverAdds = $this->_session->serverAdds[$collectionData['class']];
                        } else {
                            // all entries added since last sync
                            $syncState  = $this->_controller->getSyncState($this->_device, $collectionData['class'] . '-' . $collectionData['collectionId'], $collectionData['syncKey']);
                            $serverAdds = $dataController->getSince('added', $syncState->lastsync, $this->_syncTimeStamp);
                            // add entries which produced problems during delete from client
                            $serverAdds = array_merge($serverAdds, $this->_collections[$class]['forceAdd']);
                            // add entries which got available because of new permissions
                            $allClientEntries = $this->_contentStateBackend->getClientState($this->_device, $collectionData['class']);
                            $allServerEntries = $dataController->getServerEntries();
                            $serverDiff = array_diff($allServerEntries, $allClientEntries);
                            $serverAdds = array_merge($serverAdds, $serverDiff);
                        }
                    }
                    
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($serverAdds) . ' entry for sync from server to client' . print_r($serverAdds, true));

                    $commands = NULL;
                    
                    if(count($serverAdds) > 0) {
                        if(count($serverAdds) > $collectionData['windowSize']) {
                            $this->_moreAvailable = true;
                            $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'MoreAvailable'));
                        }
                        
                        foreach($serverAdds as $id => $serverId) {
                            if($this->_totalCount === $collectionData['windowSize']) {
                                break;
                            }
                            
                            // skip entry, was added by client
                            if(isset($collectionData['added'][$serverId]) && !isset($this->_collections[$class]['forceAdd'][$serverId])) {
                                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " skipped added entry: " . $serverId);
                                continue;
                            }
                            
                            // create commands node only when needed
                            if($commands === NULL) {
                                $commands = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Commands'));
                            }
                            
                            $add = $commands->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Add'));
                            $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                            $applicationData = $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ApplicationData'));
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " add ID " . $serverId);
                            $dataController->appendXML($this->_outputDom, $applicationData, $serverId);

                            $this->_addContentState($collectionData['class'], $serverId);
                            
                            $this->_totalCount++;                                
                            unset($serverAdds[$id]);    
                        }
                    }

                    if($collectionData['syncKey'] > 1) {
                        if(isset($this->_session->serverChanges[$collectionData['class']])) {
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " add ID ");
                            $serverChanges = $this->_session->serverChanges[$collectionData['class']];
                        } else {
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " add ID ");
                            $serverChanges = $dataController->getSince('changed', $syncState->lastsync, $this->_syncTimeStamp);
                            $serverChanges = array_merge($serverChanges + $this->_collections[$class]['forceChange']);
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " add ID ");
                        }
                        
                        if(count($serverChanges) > $collectionData['windowSize']) {
                            $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'MoreAvailable'));
                            $this->_moreAvailable = true;
                        }
                        
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($serverChanges) . ' changed entries' . print_r($serverChanges, true));
                        foreach($serverChanges as $id => $serverId) {
                            if($this->_totalCount === $collectionData['windowSize']) {
                                break;
                            }
                                                        
                            // skip entry, was changed by client
                            if(isset($collectionData['changed'][$serverId]) && !isset($this->_collections[$class]['forceChange'][$serverId])) {
                                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " skipped changed entry: " . $serverId);
                                continue;
                            }
                            
                            // create commands node only when needed
                            if($commands === NULL) {
                                $commands = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Commands'));
                            }
                            
                            $change = $commands->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Change'));
                            $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                            $applicationData = $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ApplicationData'));
                            $dataController->appendXML($this->_outputDom, $applicationData, $serverId);

                            $this->_totalCount++;
                            unset($serverChanges[$id]);    
                        }
                        
                        /**
                         * process deleted entries
                         */
                        if(isset($this->_session->serverDeletes[$collectionData['class']])) {
                            $serverDeletes = $this->_session->serverDeletes[$collectionData['class']];
                        } else {
                            $allClientEntries = $this->_contentStateBackend->getClientState($this->_device, $collectionData['class']);
                            $allServerEntries = $dataController->getServerEntries();
                            $serverDeletes = array_diff($allClientEntries, $allServerEntries);
                        }
                        foreach($serverDeletes as $id => $serverId) {
                            if($this->_totalCount === $collectionData['windowSize']) {
                                $this->_moreAvailable = true;
                                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'MoreAvailable'));
                                break;
                            }
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " need to delete entry " . $serverId);
                            
                            if($commands === NULL) {
                                $commands = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Commands'));
                            }
                            
                            $change = $commands->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Delete'));
                            $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                            
                            $this->_deleteContentState($collectionData['class'], $serverId);

                            $this->_totalCount++;
                            unset($serverDeletes[$id]);    
                        }
                    }
                }
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " new synckey is $newSyncKey");                
            }
            
            // save data to session if more data available
            if($this->_moreAvailable === true) {
                $this->_session->syncTimeStamp = $this->_syncTimeStamp;
                $this->_session->serverAdds[$collectionData['class']]    = (array)$serverAdds;
                $this->_session->serverChanges[$collectionData['class']] = (array)$serverChanges;
                $this->_session->serverDeletes[$collectionData['class']] = (array)$serverDeletes;
            }
            
            // increment sync timestamp by 1 second
            $this->_syncTimeStamp->add('1', Zend_Date::SECOND);
            $this->_controller->updateSyncKey($this->_device, $newSyncKey, $collectionData['class'] . '-' . $collectionData['collectionId'], $this->_syncTimeStamp);
        }
        
        parent::getResponse($this->_moreAvailable);
    }

    /**
     * save contentstate (aka: remember that we have sent the entry to the client)
     *
     * @param string $_class the class from the xml
     * @param string $_contentId the Tine 2.0 id of the entry
     */
    protected function _addContentState($_class, $_contentId)
    {
        $contentState = new ActiveSync_Model_ContentState(array(
            'device_id'     => $this->_device->getId(),
            'class'         => $_class,
            'contentid'     => $_contentId,
            'creation_time' => $this->_syncTimeStamp
        ));
        
        /**
         * if the entry got added earlier, and there was an error, the entry gets added again
         * @todo it's better to wrap the whole process into a transation
         */
        try {
            $this->_contentStateBackend->create($contentState);
        } catch (Zend_Db_Statement_Exception $e) {
            $this->_deleteContentState($_class, $_contentId);
            $this->_contentStateBackend->create($contentState);
        }
    }
    
    /**
     * delete contentstate (aka: forget that we have sent the entry to the client)
     *
     * @param string $_class the class from the xml
     * @param string $_contentId the Tine 2.0 id of the entry
     */
    protected function _deleteContentState($_class, $_contentId)
    {
        $contentStateFilter = new ActiveSync_Model_ContentStateFilter(array(
            array(
                    'field'     => 'device_id',
                    'operator'  => 'equals',
                    'value'     => $this->_device->getId()
            ),
            array(
                    'field'     => 'class',
                    'operator'  => 'equals',
                    'value'     => $_class
            ),
            array(
                    'field'     => 'contentid',
                    'operator'  => 'equals',
                    'value'     => $_contentId
            )
        ));
        $state = $this->_contentStateBackend->search($contentStateFilter, NULL, true);
        
        if(count($state) > 0) {
            $this->_contentStateBackend->delete($state[0]);
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " no contentstate found for " . print_r($contentStateFilter->toArray(), true));
        }
    }    
}