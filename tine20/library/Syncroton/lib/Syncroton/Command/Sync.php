<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_Sync extends Syncroton_Command_Wbxml 
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
    const STATUS_ERROR_SETTING_NOTIFICATION_GUID        = 10;
    const STATUS_DEVICE_NOT_PROVISIONED_FOR_NOTIFICATIONS = 11;
    const STATUS_FOLDER_HIERARCHY_HAS_CHANGED           = 12;
    const STATUS_RESEND_FULL_XML                        = 13;
    const STATUS_WAIT_INTERVAL_OUT_OF_RANGE             = 14;
    
    const CONFLICT_OVERWRITE_SERVER                     = 0;
    const CONFLICT_OVERWRITE_PIM                        = 1;
    
    const MIMESUPPORT_DONT_SEND_MIME                    = 0;
    const MIMESUPPORT_SMIME_ONLY                        = 1;
    const MIMESUPPORT_SEND_MIME                         = 2;
    
    const BODY_TYPE_PLAIN_TEXT                          = 1;
    const BODY_TYPE_HTML                                = 2;
    const BODY_TYPE_RTF                                 = 3;
    const BODY_TYPE_MIME                                = 4;
    
    /**
     * truncate types
     */
    const TRUNCATE_ALL                                  = 0;
    const TRUNCATE_4096                                 = 1;
    const TRUNCATE_5120                                 = 2;
    const TRUNCATE_7168                                 = 3;
    const TRUNCATE_10240                                = 4;
    const TRUNCATE_20480                                = 5;
    const TRUNCATE_51200                                = 6;
    const TRUNCATE_102400                               = 7;
    const TRUNCATE_NOTHING                              = 8;

    /**
     * filter types
     */
    const FILTER_NOTHING        = 0;
    const FILTER_1_DAY_BACK     = 1;
    const FILTER_3_DAYS_BACK    = 2;
    const FILTER_1_WEEK_BACK    = 3;
    const FILTER_2_WEEKS_BACK   = 4;
    const FILTER_1_MONTH_BACK   = 5;
    const FILTER_3_MONTHS_BACK  = 6;
    const FILTER_6_MONTHS_BACK  = 7;
    const FILTER_INCOMPLETE     = 8;
    

    protected $_defaultNameSpace    = 'uri:AirSync';
    protected $_documentElement     = 'Sync';
    
    /**
     * list of collections
     *
     * @var array
     */
    protected $_collections = array();
    
    protected $_modifications = array();

    /**
     * the global WindowSize
     *
     * @var integer
     */
    protected $_globalWindowSize;
    
    /**
     * there are more entries than WindowSize available
     * the MoreAvailable tag hot added to the xml output
     *
     * @var boolean
     */
    protected $_moreAvailable = false;
    
    /**
     * @var Syncroton_Model_SyncState
     */
    protected $_syncState;
    
    protected $_maxWindowSize = 100;
    
    protected $_heartbeatInterval = null;
    
    /**
     * process the XML file and add, change, delete or fetches data 
     */
    public function handle()
    {
        // input xml
        $xml = simplexml_import_dom($this->_requestBody);
        
        if (isset($xml->HeartbeatInterval)) {
            $this->_heartbeatInterval = (int)$xml->HeartbeatInterval;
        } elseif (isset($xml->Wait)) {
            $this->_heartbeatInterval = (int)$xml->Wait * 60;
        }
        
        $this->_globalWindowSize = isset($xml->WindowSize) ? (int)$xml->WindowSize : 100;
        
        
        if ($this->_globalWindowSize > $this->_maxWindowSize) {
            $this->_globalWindowSize = $this->_maxWindowSize;
        }
        
        $collections = array();
        $isPartialRequest = isset($xml->Partial);
        
        // try to restore collections from previous request
        if ($isPartialRequest) {
            $decodedCollections = Zend_Json::decode($this->_device->lastsynccollection);
            
            if (is_array($decodedCollections)) {
                foreach ($decodedCollections as $collection) {
                    $collections[$collection['collectionId']] = new Syncroton_Model_SyncCollection($collection);
                }
            }
        }
        
        // Collections element is optional when Partial element is sent
        if (isset($xml->Collections)) {
            foreach ($xml->Collections->Collection as $xmlCollection) {
                $collectionId = (string)$xmlCollection->CollectionId;
                
                // do we have to update a collection sent in previous sync request?
                if ($isPartialRequest && isset($collections[$collectionId])) {
                    $collections[$collectionId]->setFromSimpleXMLElement($xmlCollection);
                } else {
                    $collections[$collectionId] = new Syncroton_Model_SyncCollection($xmlCollection);
                }
            }
            
            // store current value of $collections for next Sync command request
            $collectionsToSave = array();
        
            foreach ($collections as $collection) {
                $collectionsToSave[$collection->collectionId] = $collection->toArray();
            }
        
            $this->_device->lastsynccollection = Zend_Json::encode($collectionsToSave);
        
            Syncroton_Registry::getDeviceBackend()->update($this->_device);
        }
        
        foreach ($collections as $collectionData) {
            // has the folder been synchronised to the device already
            try {
                $collectionData->folder = $this->_folderBackend->getFolder($this->_device, $collectionData->collectionId);
                
            } catch (Syncroton_Exception_NotFound $senf) {
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " folder {$collectionData->collectionId} not found");
                
                // trigger INVALID_SYNCKEY instead of OBJECT_NOTFOUND when synckey is higher than 0
                // to avoid a syncloop for the iPhone
                if ($collectionData->syncKey > 0) {
                    $collectionData->folder    = new Syncroton_Model_Folder(array(
                        'deviceId' => $this->_device,
                        'serverId' => $collectionData->collectionId
                    ));
                }
                
                $this->_collections[$collectionData->collectionId] = $collectionData;
                
                continue;
            }
            
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " SyncKey is {$collectionData->syncKey} Class: {$collectionData->folder->class} CollectionId: {$collectionData->collectionId}");
            
            // initial synckey
            if($collectionData->syncKey === 0) {
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " initial client synckey 0 provided");
                
                // reset sync state for this folder
                $this->_syncStateBackend->resetState($this->_device, $collectionData->folder);
                $this->_contentStateBackend->resetState($this->_device, $collectionData->folder);
            
                $collectionData->syncState    = new Syncroton_Model_SyncState(array(
                    'device_id' => $this->_device,
                    'counter'   => 0,
                    'type'      => $collectionData->folder,
                    'lastsync'  => $this->_syncTimeStamp
                ));
                
                $this->_collections[$collectionData->collectionId] = $collectionData;
                
                continue;
            }
            
            // check for invalid sycnkey
            if(($collectionData->syncState = $this->_syncStateBackend->validate($this->_device, $collectionData->folder, $collectionData->syncKey)) === false) {
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey {$collectionData->syncKey} provided");
                
                // reset sync state for this folder
                $this->_syncStateBackend->resetState($this->_device, $collectionData->folder);
                $this->_contentStateBackend->resetState($this->_device, $collectionData->folder);
                
                $this->_collections[$collectionData->collectionId] = $collectionData;
                
                continue;
            }
            
            $dataController = Syncroton_Data_Factory::factory($collectionData->folder->class, $this->_device, $this->_syncTimeStamp);
            
            switch($collectionData->folder->class) {
                case Syncroton_Data_Factory::CLASS_CALENDAR:
                    $dataClass = 'Syncroton_Model_Event';
                    
                    break;
                    
                case Syncroton_Data_Factory::CLASS_CONTACTS:
                    $dataClass = 'Syncroton_Model_Contact';
                    
                    break;
                    
                case Syncroton_Data_Factory::CLASS_EMAIL:
                    $dataClass = 'Syncroton_Model_Email';
                    
                    break;
                    
                case Syncroton_Data_Factory::CLASS_TASKS:
                    $dataClass = 'Syncroton_Model_Task';
                    
                    break;
                    
                default:
                    throw new Syncroton_Exception_UnexpectedValue('invalid class provided');
                    
                    break;
            }
            
            $clientModifications = array(
                'added'            => array(),
                'changed'          => array(),
                'deleted'          => array(),
                'forceAdd'         => array(),
                'forceChange'      => array(),
                'toBeFetched'      => array(),
            );
            
            // handle incoming data
            if($collectionData->hasClientAdds()) {
                $adds = $collectionData->getClientAdds();
                
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " found " . count($adds) . " entries to be added to server");
                
                foreach ($adds as $add) {
                    if ($this->_logger instanceof Zend_Log) 
                        $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " add entry with clientId " . (string) $add->ClientId);

                    try {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->info(__METHOD__ . '::' . __LINE__ . " adding entry as new");
                        
                        $serverId = $dataController->createEntry($collectionData->collectionId, new $dataClass($add->ApplicationData));
                        
                        $clientModifications['added'][$serverId] = array(
                            'clientId'     => (string)$add->ClientId,
                            'serverId'     => $serverId,
                            'status'       => self::STATUS_SUCCESS,
                            'contentState' => $this->_contentStateBackend->create(new Syncroton_Model_Content(array(
                                'device_id'        => $this->_device,
                                'folder_id'        => $collectionData->folder,
                                'contentid'        => $serverId,
                                'creation_time'    => $this->_syncTimeStamp,
                                'creation_synckey' => $collectionData->syncKey + 1
                            )))
                        );
                        
                    } catch (Exception $e) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " failed to add entry " . $e->getMessage());
                        $clientModifications['added'][] = array(
                            'clientId' => (string)$add->ClientId,
                            'status'   => self::STATUS_SERVER_ERROR
                        );
                    }
                }
            }
            
            // handle changes, but only if not first sync
            if($collectionData->syncKey > 1 && $collectionData->hasClientChanges()) {
                $changes = $collectionData->getClientChanges();
                
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " found " . count($changes) . " entries to be updated on server");
                
                foreach ($changes as $change) {
                    $serverId = (string)$change->ServerId;
                    
                    try {
                        $dataController->updateEntry($collectionData->collectionId, $serverId, new $dataClass($change->ApplicationData));
                        $clientModifications['changed'][$serverId] = self::STATUS_SUCCESS;
                        
                    } catch (Syncroton_Exception_AccessDenied $e) {
                        $clientModifications['changed'][$serverId] = self::STATUS_CONFLICT_MATCHING_THE_CLIENT_AND_SERVER_OBJECT;
                        $clientModifications['forceChange'][$serverId] = $serverId;
                        
                    } catch (Syncroton_Exception_NotFound $e) {
                        // entry does not exist anymore, will get deleted automaticaly
                        $clientModifications['changed'][$serverId] = self::STATUS_OBJECT_NOT_FOUND;
                        
                    } catch (Exception $e) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " failed to update entry " . $e);
                        // something went wrong while trying to update the entry
                        $clientModifications['changed'][$serverId] = self::STATUS_SERVER_ERROR;
                    }
                }
            }
            
            // handle deletes, but only if not first sync
            if($collectionData->hasClientDeletes()) {
                $deletes = $collectionData->getClientDeletes();
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " found " . count($deletes) . " entries to be deleted on server");
                
                foreach ($deletes as $delete) {
                    $serverId = (string)$delete->ServerId;
                    
                    try {
                        // check if we have sent this entry to the phone
                        $state = $this->_contentStateBackend->getContentState($this->_device, $collectionData->folder, $serverId);
                        
                        try {
                            $dataController->deleteEntry($collectionData->collectionId, $serverId, $collectionData);
                            
                        } catch(Syncroton_Exception_NotFound $e) {
                            if ($this->_logger instanceof Zend_Log) 
                                $this->_logger->crit(__METHOD__ . '::' . __LINE__ . ' tried to delete entry ' . $serverId . ' but entry was not found');
                            
                        } catch (Syncroton_Exception $e) {
                            if ($this->_logger instanceof Zend_Log) 
                                $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' tried to delete entry ' . $serverId . ' but a error occured: ' . $e->getMessage());
                            $clientModifications['forceAdd'][$serverId] = $serverId;
                        }
                        $this->_contentStateBackend->delete($state);
                        
                    } catch (Syncroton_Exception_NotFound $senf) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' ' . $serverId . ' should have been removed from client already');
                        // should we send a special status???
                        //$collectionData->deleted[$serverId] = self::STATUS_SUCCESS;
                    }
                    
                    $clientModifications['deleted'][$serverId] = self::STATUS_SUCCESS;
                }
            }
            
            // handle fetches, but only if not first sync
            if($collectionData->syncKey > 1 && $collectionData->hasClientFetches()) {
                // the default value for GetChanges is 1. If the phone don't want the changes it must set GetChanges to 0
                // some prevoius versions of iOS did not set GetChanges to 0 for fetches. Let's enforce getChanges to false here.
                $collectionData->getChanges = false;
                
                $fetches = $collectionData->getClientFetches();
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " found " . count($fetches) . " entries to be fetched from server");
                
                $toBeFecthed = array();
                
                foreach ($fetches as $fetch) {
                    $serverId = (string)$fetch->ServerId;
                    
                    $toBeFetched[$serverId] = $serverId;
                }
                
                $collectionData->toBeFetched = $toBeFetched;
            }
            
            $this->_collections[$collectionData->collectionId] = $collectionData;
            $this->_modifications[$collectionData->collectionId] = $clientModifications;
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Command_Wbxml::getResponse()
     */
    public function getResponse()
    {
        $sync = $this->_outputDom->documentElement;
        
        if (count($this->_collections) == 0) {
            $sync->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_RESEND_FULL_XML));
            
            return $this->_outputDom;
        }
        
        $collections = $sync->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collections'));

        $totalChanges = 0;
        
        // continue only if there are changes or no time is left
        if ($this->_heartbeatInterval > 0) {
            $intervalStart = time();
            
            do {
                // take a break to save battery lifetime
                sleep(Syncroton_Registry::getPingTimeout());
                
                $now = new DateTime(null, new DateTimeZone('utc'));

                foreach($this->_collections as $collectionData) {
                    // countinue immediately if folder does not exist 
                    if (! ($collectionData->folder instanceof Syncroton_Model_IFolder)) {
                        break 2;
                        
                    // countinue immediately if syncstate is invalid
                    } elseif (! ($collectionData->syncState instanceof Syncroton_Model_ISyncState)) {
                        break 2;
                        
                    } else {
                        if ($collectionData->getChanges !== true) {
                            continue;
                        }
                        
                        try {
                            // just check if the folder still exists
                            $this->_folderBackend->get($collectionData->folder);
                        } catch (Syncroton_Exception_NotFound $senf) {
                            if ($this->_logger instanceof Zend_Log)
                                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " collection does not exist anymore: " . $collectionData->collectionId);
                            
                            $collectionData->getChanges = false;
                            
                            // make sure this is the last while loop
                            // no break 2 here, as we like to check the other folders too
                            $intervalStart -= $this->_heartbeatInterval;
                        }
                        
                        // check that the syncstate still exists and is still valid
                        try {
                            $syncState = $this->_syncStateBackend->getSyncState($this->_device, $collectionData->folder);
                            
                            // another process synchronized data of this folder already. let's skip it
                            if ($syncState->id !== $collectionData->syncState->id) {
                                if ($this->_logger instanceof Zend_Log)
                                    $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " syncstate changed during heartbeat interval for collection: " . $collectionData->folder->serverId);
                                
                                $collectionData->getChanges = false;
                                
                                // make sure this is the last while loop
                                // no break 2 here, as we like to check the other folders too
                                $intervalStart -= $this->_heartbeatInterval;
                            }
                        } catch (Syncroton_Exception_NotFound $senf) {
                            if ($this->_logger instanceof Zend_Log)
                                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " no syncstate found anymore for collection: " . $collectionData->folder->serverId);
                            
                            $collectionData->syncState = null;
                            
                            // make sure this is the last while loop
                            // no break 2 here, as we like to check the other folders too
                            $intervalStart -= $this->_heartbeatInterval;
                        }
                        
                        
                        // safe battery time by skipping folders which got synchronied less than Syncroton_Command_Ping::$quietTime seconds ago
                        if (($now->getTimestamp() - $collectionData->syncState->lastsync->getTimestamp()) < Syncroton_Registry::getQuietTime()) {
                            continue;
                        }
                                                
                        $dataController = Syncroton_Data_Factory::factory($collectionData->folder->class , $this->_device, $this->_syncTimeStamp);
                        
                        $estimate = $dataController->getCountOfChanges($this->_contentStateBackend, $collectionData->folder, $collectionData->syncState);
                        
                        // countinue immediately if there are changes available
                        if ($estimate > 0) {
                            break 2;
                        }
                    }
                }
                
                $this->_syncTimeStamp = clone $now;
                
            // See: http://www.tine20.org/forum/viewtopic.php?f=12&t=12146
            //
            // break if there are less than PingTimeout + 10 seconds left for the next loop
            // otherwise the response will be returned after the client has finished his Ping
            // request already maybe
            } while (time() - $intervalStart < $this->_heartbeatInterval - (Syncroton_Registry::getPingTimeout() + 10));
        }
        
        foreach($this->_collections as $collectionData) {
            $collectionChanges = 0;
            
            /**
             * keep track of entries added on server side
             */
            $newContentStates = array();
            
            /**
             * keep track of entries deleted on server side
             */
            $deletedContentStates = array();
            
            // invalid collectionid provided
            if (! ($collectionData->folder instanceof Syncroton_Model_IFolder)) {
                $collection = $collections->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'SyncKey', 0));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $collectionData->collectionId));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_FOLDER_HIERARCHY_HAS_CHANGED));

            // invalid synckey provided
            } elseif (! ($collectionData->syncState instanceof Syncroton_Model_ISyncState)) {
                // set synckey to 0
                $collection = $collections->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'SyncKey', 0));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $collectionData->collectionId));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_INVALID_SYNC_KEY));
                
            // initial sync
            } elseif ($collectionData->syncState->counter === 0) {
                $collectionData->syncState->counter++;

                // initial sync
                // send back a new SyncKey only
                $collection = $collections->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collection'));
                if (!empty($collectionData->folder->class)) {
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Class', $collectionData->folder->class));
                }
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'SyncKey', $collectionData->syncState->counter));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $collectionData->collectionId));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_SUCCESS));
                
            } else {

                $dataController = Syncroton_Data_Factory::factory($collectionData->folder->class , $this->_device, $this->_syncTimeStamp);
                
                $clientModifications = $this->_modifications[$collectionData->collectionId];
                $serverModifications = array(
                    'added'   => array(),
                    'changed' => array(),
                    'deleted' => array(),
                );
                
                if($collectionData->getChanges === true) {
                    // continue sync session?
                    if(is_array($collectionData->syncState->pendingdata)) {
                        if ($this->_logger instanceof Zend_Log)
                            $this->_logger->info(__METHOD__ . '::' . __LINE__ . " restored from sync state ");
                        
                        $serverModifications = $collectionData->syncState->pendingdata;
                        
                    } else {
                        try {
                            // fetch entries added since last sync
                            $allClientEntries = $this->_contentStateBackend->getFolderState($this->_device, $collectionData->folder);
                            $allServerEntries = $dataController->getServerEntries($collectionData->collectionId, $collectionData->options['filterType']);

                            // add entries
                            $serverDiff = array_diff($allServerEntries, $allClientEntries);
                            // add entries which produced problems during delete from client
                            $serverModifications['added'] = $clientModifications['forceAdd'];
                            // add entries not yet sent to client
                            $serverModifications['added'] = array_unique(array_merge($serverModifications['added'], $serverDiff));

                            // @todo still needed?
                            foreach($serverModifications['added'] as $id => $serverId) {
                                // skip entries added by client during this sync session
                                if(isset($clientModifications['added'][$serverId]) && !isset($clientModifications['forceAdd'][$serverId])) {
                                    if ($this->_logger instanceof Zend_Log)
                                        $this->_logger->info(__METHOD__ . '::' . __LINE__ . " skipped added entry: " . $serverId);
                                    unset($serverModifications['added'][$id]);
                                }
                            }

                            // entries to be deleted
                            $serverModifications['deleted'] = array_diff($allClientEntries, $allServerEntries);
                            
                            // fetch entries changed since last sync
                            $serverModifications['changed'] = $dataController->getChangedEntries($collectionData->collectionId, $collectionData->syncState->lastsync, $this->_syncTimeStamp);
                            $serverModifications['changed'] = array_merge($serverModifications['changed'], $clientModifications['forceChange']);

                            foreach($serverModifications['changed'] as $id => $serverId) {
                                // skip entry, if it got changed by client during current sync
                                if(isset($clientModifications['changed'][$serverId]) && !isset($clientModifications['forceChange'][$serverId])) {
                                    if ($this->_logger instanceof Zend_Log)
                                        $this->_logger->info(__METHOD__ . '::' . __LINE__ . " skipped changed entry: " . $serverId);
                                    unset($serverModifications['changed'][$id]);
                                }
                                // skip entry, make sure we don't sent entries already added by client in this request
                                else if (isset($clientModifications['added'][$serverId]) && !isset($clientModifications['forceAdd'][$serverId])) {
                                    if ($this->_logger instanceof Zend_Log)
                                        $this->_logger->info(__METHOD__ . '::' . __LINE__ . " skipped change for added entry: " . $serverId);
                                    unset($serverModifications['changed'][$id]);
                                }
                            }

                            // entries comeing in scope are already in $serverModifications['added'] and do not need to
                            // be send with $serverCanges
                            $serverModifications['changed'] = array_diff($serverModifications['changed'], $serverModifications['added']);
                        } catch (Exception $e) {
                            if ($this->_logger instanceof Zend_Log)
                                $this->_logger->crit(__METHOD__ . '::' . __LINE__ . " Folder state checking failed: " . $e->getMessage());
                            if ($this->_logger instanceof Zend_Log)
                                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " Folder state checking failed: " . $e->getTraceAsString());
                            
                            // Prevent from removing client entries when getServerEntries() fails
                            // @todo: should we set Status and break the loop here?
                            $serverModifications = array(
                                'added'   => array(),
                                'changed' => array(),
                                'deleted' => array(),
                            );
                        }
                    }

                    if ($this->_logger instanceof Zend_Log)
                        $this->_logger->info(__METHOD__ . '::' . __LINE__ . " found (added/changed/deleted) " . count($serverModifications['added']) . '/' . count($serverModifications['changed']) . '/' . count($serverModifications['deleted'])  . ' entries for sync from server to client');
                }

                // collection header
                $collection = $collections->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collection'));
                if (!empty($collectionData->folder->class)) {
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Class', $collectionData->folder->class));
                }
                
                $syncKeyElement = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'SyncKey'));
                
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $collectionData->collectionId));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_SUCCESS));
                
                $responses = $this->_outputDom->createElementNS('uri:AirSync', 'Responses');
                
                // send reponse for newly added entries
                if(!empty($clientModifications['added'])) {
                    foreach($clientModifications['added'] as $entryData) {
                        $add = $responses->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Add'));
                        $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ClientId', $entryData['clientId']));
                        // we have no serverId is the add failed
                        if(isset($entryData['serverId'])) {
                            $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $entryData['serverId']));
                        }
                        $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', $entryData['status']));
                    }
                }
                
                // send reponse for changed entries
                if(!empty($clientModifications['changed'])) {
                    foreach($clientModifications['changed'] as $serverId => $status) {
                        if ($status !== Syncroton_Command_Sync::STATUS_SUCCESS) {
                            $change = $responses->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Change'));
                            $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                            $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', $status));
                        }
                    }
                }
                
                // send response for to be fetched entries
                if(!empty($collectionData->toBeFetched)) {
                    foreach($collectionData->toBeFetched as $serverId) {
                        $fetch = $responses->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Fetch'));
                        $fetch->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                        
                        try {
                            $applicationData = $this->_outputDom->createElementNS('uri:AirSync', 'ApplicationData');
                            
                            $dataController
                                ->getEntry($collectionData, $serverId)
                                ->appendXML($applicationData, $this->_device);
                            
                            $fetch->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_SUCCESS));
                            
                            $fetch->appendChild($applicationData);
                        } catch (Exception $e) {
                            if ($this->_logger instanceof Zend_Log) 
                                $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " unable to convert entry to xml: " . $e->getMessage());
                            if ($this->_logger instanceof Zend_Log) 
                                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " unable to convert entry to xml: " . $e->getTraceAsString());
                            $fetch->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_OBJECT_NOT_FOUND));
                        }
                    }
                }
                
                if ($responses->hasChildNodes() === true) {
                    $collection->appendChild($responses);
                }
                
                $commands = $this->_outputDom->createElementNS('uri:AirSync', 'Commands');
                
                foreach($serverModifications['added'] as $id => $serverId) {
                    if($collectionChanges == $collectionData->windowSize || $totalChanges + $collectionChanges >= $this->_globalWindowSize) {
                        break;
                    }
                    
                    #/**
                    # * somewhere is a problem in the logic for handling moreAvailable
                    # * 
                    # * it can happen, that we have a contentstate (which means we sent the entry to the client
                    # * and that this entry is yet in $collectionData->syncState->pendingdata['serverAdds']
                    # * I have no idea how this can happen, but the next lines of code work around this problem
                    # */
                    #try {
                    #    $this->_contentStateBackend->getContentState($this->_device, $collectionData->folder, $serverId);
                    # 
                    #    if ($this->_logger instanceof Zend_Log) 
                    #        $this->_logger->info(__METHOD__ . '::' . __LINE__ . " skipped an entry($serverId) which is already on the client");
                    #    
                    #    unset($serverModifications['added'][$id]);
                    #    continue;
                    #    
                    #} catch (Syncroton_Exception_NotFound $senf) {
                    #    // do nothing => content state should not exist yet
                    #}
                    
                    try {
                        $add = $this->_outputDom->createElementNS('uri:AirSync', 'Add');
                        $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                        
                        $applicationData = $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ApplicationData'));

                        $dataController
                            ->getEntry($collectionData, $serverId)
                            ->appendXML($applicationData, $this->_device);
                        
                        $commands->appendChild($add);
                        
                        $collectionChanges++;
                    } catch (Exception $e) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " unable to convert entry to xml: " . $e->getMessage());
                    }
                    
                    // mark as send to the client, even the conversion to xml might have failed 
                    $newContentStates[] = new Syncroton_Model_Content(array(
                        'device_id'        => $this->_device,
                        'folder_id'        => $collectionData->folder,
                        'contentid'        => $serverId,
                        'creation_time'    => $this->_syncTimeStamp,
                        'creation_synckey' => $collectionData->syncState->counter + 1
                    ));
                    unset($serverModifications['added'][$id]);
                }

                /**
                 * process entries changed on server side
                 */
                foreach($serverModifications['changed'] as $id => $serverId) {
                    if($collectionChanges == $collectionData->windowSize || $totalChanges + $collectionChanges >= $this->_globalWindowSize) {
                        break;
                    }
                    
                    try {
                        $change = $this->_outputDom->createElementNS('uri:AirSync', 'Change');
                        $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                        
                        $applicationData = $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ApplicationData'));
                        
                        $dataController
                            ->getEntry($collectionData, $serverId)
                            ->appendXML($applicationData, $this->_device);
                        

                        $commands->appendChild($change);
                        
                        $collectionChanges++;
                    } catch (Exception $e) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " unable to convert entry to xml: " . $e->getMessage());
                    }

                    unset($serverModifications['changed'][$id]);    
                }

                foreach($serverModifications['deleted'] as $id => $serverId) {
                    if($collectionChanges == $collectionData->windowSize || $totalChanges + $collectionChanges >= $this->_globalWindowSize) {
                        break;
                    }
                    
                    try {
                        // check if we have sent this entry to the phone
                        $state = $this->_contentStateBackend->getContentState($this->_device, $collectionData->folder, $serverId);
                        
                        $delete = $this->_outputDom->createElementNS('uri:AirSync', 'Delete');
                        $delete->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                        
                        $deletedContentStates[] = $state;
                        
                        $commands->appendChild($delete);
                        
                        $collectionChanges++;
                    } catch (Exception $e) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " unable to convert entry to xml: " . $e->getMessage());
                    }
                    
                    unset($serverModifications['deleted'][$id]);
                }
                
                $countOfPendingChanges = (count($serverModifications['added']) + count($serverModifications['changed']) + count($serverModifications['deleted'])); 
                if ($countOfPendingChanges > 0) {
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'MoreAvailable'));
                } else {
                    $serverModifications = null;
                }
                    
                if ($commands->hasChildNodes() === true) {
                    $collection->appendChild($commands);
                }
                
                $totalChanges += $collectionChanges;
                
                // increase SyncKey if needed
                if ((
                        // sent the clients updates?
                        !empty($clientModifications['added']) ||
                        !empty($clientModifications['changed']) ||
                        !empty($clientModifications['deleted'])
                    ) || (
                        // sends the server updates to the client?
                        $commands->hasChildNodes() === true
                    ) || (
                        // changed the pending data?
                        $collectionData->syncState->pendingdata != $serverModifications
                    )
                ) {
                    // then increase SyncKey
                    $collectionData->syncState->counter++;
                }
                $syncKeyElement->appendChild($this->_outputDom->createTextNode($collectionData->syncState->counter));
                
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " new synckey is ". $collectionData->syncState->counter);
            }
            
            if (isset($collectionData->syncState) && $collectionData->syncState instanceof Syncroton_Model_ISyncState && 
                $collectionData->syncState->counter != $collectionData->syncKey) {
                
                if ($this->_logger instanceof Zend_Log)
                    $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " update syncState for collection: " . $collectionData->collectionId);
                
                // store pending data in sync state when needed
                if(isset($countOfPendingChanges) && $countOfPendingChanges > 0) {
                    $collectionData->syncState->pendingdata = array(
                        'added'   => (array)$serverModifications['added'],
                        'changed' => (array)$serverModifications['changed'],
                        'deleted' => (array)$serverModifications['deleted']
                    );
                } else {
                    $collectionData->syncState->pendingdata = null;
                }
                
                
                if (!empty($clientModifications['added'])) {
                    if ($this->_logger instanceof Zend_Log) 
                        $this->_logger->info(__METHOD__ . '::' . __LINE__ . " remove previous synckey as client added new entries");
                    $keepPreviousSyncKey = false;
                } else {
                    $keepPreviousSyncKey = true;
                }
                
                $collectionData->syncState->lastsync = clone $this->_syncTimeStamp;
                // increment sync timestamp by 1 second
                $collectionData->syncState->lastsync->modify('+1 sec');
                
                try {
                    $transactionId = Syncroton_Registry::getTransactionManager()->startTransaction(Syncroton_Registry::getDatabase());
                    
                    // store new synckey
                    $this->_syncStateBackend->create($collectionData->syncState, $keepPreviousSyncKey);
                    
                    // store contentstates for new entries added to client
                    foreach($newContentStates as $state) {
                        $this->_contentStateBackend->create($state);
                    }
                    
                    // remove contentstates for entries to be deleted on client
                    foreach($deletedContentStates as $state) {
                        $this->_contentStateBackend->delete($state);
                    }
                    
                    Syncroton_Registry::getTransactionManager()->commitTransaction($transactionId);
                } catch (Zend_Db_Statement_Exception $zdse) {
                    // something went wrong
                    // maybe another parallel request added a new synckey
                    // we must remove data added from client
                    if (!empty($clientModifications['added'])) {
                        foreach ($clientModifications['added'] as $added) {
                            $this->_contentStateBackend->delete($added['contentState']);
                            $dataController->deleteEntry($collectionData->collectionId, $added['serverId'], array());
                        }
                    }
                    
                    Syncroton_Registry::getTransactionManager()->rollBack();
                    
                    throw $zdse;
                }
                    
                
                // store current filter type
                try {
                    $folderState = $this->_folderBackend->getFolder($this->_device, $collectionData->collectionId);
                    $folderState->lastfiltertype = $collectionData->options['filterType'];
                    $this->_folderBackend->update($folderState);
                } catch (Syncroton_Exception_NotFound $senf) {
                    // failed to get folderstate => should not happen but is also no problem in this state
                    if ($this->_logger instanceof Zend_Log) 
                        $this->_logger->crit(__METHOD__ . '::' . __LINE__ . ' failed to get content state for: ' . $collectionData->collectionId);
                }
            }
        }
        
        return $this->_outputDom;
    }
}
