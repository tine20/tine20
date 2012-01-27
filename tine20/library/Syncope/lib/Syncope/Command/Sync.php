<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 Syncope module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Syncope
 * @subpackage  Command
 */
class Syncope_Command_Sync extends Syncope_Command_Wbxml 
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
     * @var Syncope_Model_SyncState
     */
    protected $_syncState;
    
    /**
     * process the XML file and add, change, delete or fetches data 
     */
    public function handle()
    {
        // input xml
        $xml = simplexml_import_dom($this->_inputDom);
        
        foreach ($xml->Collections->Collection as $xmlCollection) {
            $collectionData = array(
                'syncKey'         => (int)$xmlCollection->SyncKey,
                'class'           => isset($xmlCollection->Class) ? (string)$xmlCollection->Class : null,
                'collectionId'    => (string)$xmlCollection->CollectionId,
                'windowSize'      => isset($xmlCollection->WindowSize) ? (int)$xmlCollection->WindowSize : 100,
                'deletesAsMoves'  => isset($xmlCollection->DeletesAsMoves) ? true : false,
                'getChanges'      => isset($xmlCollection->GetChanges) ? true : false,
                'added'           => array(),
                'changed'         => array(),
                'deleted'         => array(),
                'forceAdd'        => array(),
                'forceChange'     => array(),
                'toBeFetched'     => array(),
                'filterType'      => 0,
                'mimeSupport'     => self::MIMESUPPORT_DONT_SEND_MIME,
                'mimeTruncation'  => Syncope_Command_Sync::TRUNCATE_NOTHING,
                'bodyPreferences' => array(),
            );
            
            // process options
            if (isset($xmlCollection->Options)) {
                // optional parameters
                if (isset($xmlCollection->Options->FilterType)) {
                    $collectionData['filterType'] = (int)$xmlCollection->Options->FilterType;
                }
                if (isset($xmlCollection->Options->MIMESupport)) {
                    $collectionData['mimeSupport'] = (int)$xmlCollection->Options->MIMESupport;
                }
                if (isset($xmlCollection->Options->MIMETruncation)) {
                    $collectionData['mimeTruncation'] = (int)$xmlCollection->Options->MIMETruncation;
                }
                
                // try to fetch element from AirSyncBase:BodyPreference
                $airSyncBase = $xmlCollection->Options->children('uri:AirSyncBase');
                
                if (isset($airSyncBase->BodyPreference)) {
                    
                    foreach ($airSyncBase->BodyPreference as $bodyPreference) {
                        $type = (int) $bodyPreference->Type;
                        $collectionData['bodyPreferences'][$type] = array(
                            'type' => $type
                        );
                        
                        // optional
                        if (isset($bodyPreference->TruncationSize)) {
                            $collectionData['bodyPreferences'][$type]['truncationSize'] = (int) $bodyPreference->TruncationSize;
                        }
                    }
                }
            }
            
            // got the folder synchronized to the device already
            try {
                $collectionData['folder'] = $this->_folderBackend->getFolder($this->_device, $collectionData['collectionId']);
                
            } catch (Syncope_Exception_NotFound $senf) {
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " folder {$collectionData['collectionId']} not found");
                
                $this->_collections['invalidFolderId'][$collectionData['collectionId']] = $collectionData;
                continue;
            }
            
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " SyncKey is {$collectionData['syncKey']} Class: {$collectionData['class']} CollectionId: {$collectionData['collectionId']}");
            
            // initial synckey
            if($collectionData['syncKey'] === 0) {
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " initial client synckey 0 provided");
                
                // reset sync state for this folder
                $this->_syncStateBackend->resetState($this->_device, $collectionData['folder']);
                $this->_contentStateBackend->resetState($this->_device, $collectionData['folder']);
            
                $collectionData['syncState']    = new Syncope_Model_SyncState(array(
                    'device_id' => $this->_device,
                    'counter'   => 0,
                    'type'      => $collectionData['folder'],
                    'lastsync'  => $this->_syncTimeStamp
                ));
                
                $this->_collections[$collectionData['folder']->class][$collectionData['collectionId']] = $collectionData;
                
                continue;
            }
            
            // check for invalid sycnkey
            if(($collectionData['syncState'] = $this->_syncStateBackend->validate($this->_device, $collectionData['folder'], $collectionData['syncKey'])) === false) {
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey {$collectionData['syncKey']} provided");
                
                // reset sync state for this folder
                $this->_syncStateBackend->resetState($this->_device, $collectionData['folder']);
                $this->_contentStateBackend->resetState($this->_device, $collectionData['folder']);
                
                $this->_collections[$collectionData['folder']->class][$collectionData['collectionId']] = $collectionData;
                
                continue;
            }
            
            $dataController = Syncope_Data_Factory::factory($collectionData['folder']->class, $this->_device, $this->_syncTimeStamp);
            
            // handle incoming data
            if(isset($xmlCollection->Commands->Add)) {
                $adds = $xmlCollection->Commands->Add;
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " found " . count($adds) . " entries to be added to server");
                
                foreach ($adds as $add) {
                	if ($this->_logger instanceof Zend_Log) 
                	    $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " add entry with clientId " . (string) $add->ClientId);

                    try {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->info(__METHOD__ . '::' . __LINE__ . " adding entry as new");
                        
                        $serverId = $dataController->createEntry($collectionData['collectionId'], $add->ApplicationData);
                        
                        $collectionData['added'][$serverId] = array(
                            'clientId' => (string)$add->ClientId,
                            'serverId' => $serverId,
                            'status'   => self::STATUS_SUCCESS
                        );
                        
                        $this->_contentStateBackend->create(new Syncope_Model_Content(array(
                        	'device_id'     => $this->_device,
                            'folder_id'     => $collectionData['folder'],
                            'contentid'     => $serverId,
                            'creation_time' => $this->_syncTimeStamp
                        
                        )));
                    } catch (Exception $e) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " failed to add entry " . $e->getMessage());
                        $collectionData['added'][] = array(
                            'clientId' => (string)$add->ClientId,
                            'status'   => self::STATUS_SERVER_ERROR
                        );
                    }
                }
            }
        
            // handle changes, but only if not first sync
            if($collectionData['syncKey'] > 1 && isset($xmlCollection->Commands->Change)) {
                $changes = $xmlCollection->Commands->Change;
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " found " . count($changes) . " entries to be updated on server");
                
                foreach ($changes as $change) {
                    $serverId = (string)$change->ServerId;
                    
                    try {
                        $dataController->updateEntry($collectionData['collectionId'], $serverId, $change->ApplicationData);
                        $collectionData['changed'][$serverId] = self::STATUS_SUCCESS;
                    } catch (Syncope_Exception_AccessDenied $e) {
                        $collectionData['changed'][$serverId] = self::STATUS_CONFLICT_MATCHING_THE_CLIENT_AND_SERVER_OBJECT;
                        $collectionData['forceChange'][$serverId] = $serverId;
                    } catch (Syncope_Exception_NotFound $e) {
                        // entry does not exist anymore, will get deleted automaticaly
                        $collectionData['changed'][$serverId] = self::STATUS_OBJECT_NOT_FOUND;
                    } catch (Exception $e) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " failed to update entry " . $e);
                        // something went wrong while trying to update the entry
                        $collectionData['changed'][$serverId] = self::STATUS_SERVER_ERROR;
                    }
                }
            }
        
            // handle deletes, but only if not first sync
            if(isset($xmlCollection->Commands->Delete)) {
                $deletes = $xmlCollection->Commands->Delete;
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " found " . count($deletes) . " entries to be deleted on server");
                
                foreach ($deletes as $delete) {
                    $serverId = (string)$delete->ServerId;
                    
                    try {
                        // check if we have sent this entry to the phone
                        $state = $this->_contentStateBackend->getContentState($this->_device, $collectionData['folder'], $serverId);
                        
                        try {
                            $dataController->deleteEntry($collectionData['collectionId'], $serverId, $collectionData);
                        } catch(Syncope_Exception_NotFound $e) {
                            if ($this->_logger instanceof Zend_Log) 
                                $this->_logger->crit(__METHOD__ . '::' . __LINE__ . ' tried to delete entry ' . $serverId . ' but entry was not found');
                        } catch (Syncope_Exception $e) {
                            if ($this->_logger instanceof Zend_Log) 
                                $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' tried to delete entry ' . $serverId . ' but a error occured: ' . $e->getMessage());
                            $collectionData['forceAdd'][$serverId] = $serverId;
                        }
                        $this->_contentStateBackend->delete($state);
                        
                    } catch (Syncope_Exception_NotFound $senf) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' ' . $serverId . ' should have been removed from client already');
                        // should we send a special status???
                        //$collectionData['deleted'][$serverId] = self::STATUS_SUCCESS;
                    }
                    
                    $collectionData['deleted'][$serverId] = self::STATUS_SUCCESS;
                }
            }
                        
            // handle fetches, but only if not first sync
            if($collectionData['syncKey'] > 1 && isset($xmlCollection->Commands->Fetch)) {
                // the default value for GetChanges is 1. If the phone don't want the changes it must set GetChanges to 0
                // unfortunately the iPhone dont set GetChanges to 0 when fetching email body, but is confused when we send
                // changes
                if (! isset($xmlCollection->GetChanges)) {
                    $collectionData['getChanges'] = false;
                }
                
                $fetches = $xmlCollection->Commands->Fetch;
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " found " . count($fetches) . " entries to be fetched from server");
                foreach ($fetches as $fetch) {
                    $serverId = (string)$fetch->ServerId;
                    
                    $collectionData['toBeFetched'][$serverId] = $serverId;
                }
            }            
            
            $this->_collections[$collectionData['folder']->class][$collectionData['collectionId']] = $collectionData;
        }  
    }    
    
    /**
     * (non-PHPdoc)
     * @see Syncope_Command_Wbxml::getResponse()
     */
    public function getResponse()
    {
        $this->_outputDom->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:AirSyncBase' , 'uri:AirSyncBase');
        
        $sync = $this->_outputDom->documentElement;
        
        $collections = $sync->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collections'));

        foreach($this->_collections as $classCollections) {
            foreach($classCollections as $collectionId => $collectionData) {
                
                // invalid collectionid provided
                if (empty($collectionData['folder'])) {
                    $collection = $collections->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collection'));
                    if (!empty($collectionData['class'])) {
                        $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Class', $collectionData['class']));
                    }
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'SyncKey', 0));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $collectionData['collectionId']));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_FOLDER_HIERARCHY_HAS_CHANGED));


                // invalid synckey provided
                } elseif (! ($collectionData['syncState'] instanceof Syncope_Model_ISyncState)) {
                    // set synckey to 0
                    $collection = $collections->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collection'));
                    if (!empty($collectionData['class'])) {
                        $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Class', $collectionData['class']));
                    }
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'SyncKey', 0));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $collectionData['collectionId']));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_INVALID_SYNC_KEY));
                    

                // initial sync
                } elseif ($collectionData['syncState']->counter === 0) {
                    $collectionData['syncState']->counter++;
    
                    // initial sync
                    // send back a new SyncKey only
                    $collection = $collections->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collection'));
                    if (!empty($collectionData['class'])) {
                        $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Class', $collectionData['class']));
                    }
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'SyncKey', $collectionData['syncState']->counter));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $collectionData['collectionId']));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_SUCCESS));
                    
                } else {
                    if (empty($collectionData['added']) && empty($collectionData['changed']) && empty($collectionData['deleted']) && $collectionData['getChanges'] === false) {
                        // keep synckey during fetch requests
                    } else {
                        $collectionData['syncState']->counter++;
                    }
                    
                    // collection header
                    $collection = $collections->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collection'));
                    if (!empty($collectionData['class'])) {
                        $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Class', $collectionData['class']));
                    }
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'SyncKey', $collectionData['syncState']->counter));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $collectionData['collectionId']));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_SUCCESS));
                    
                    $responses = NULL;
                    // sent reponse for newly added entries
                    if(!empty($collectionData['added'])) {
                        if($responses === NULL) {
                            $responses = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Responses'));
                        }
                        foreach($collectionData['added'] as $entryData) {
                            $add = $responses->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Add'));
                            $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ClientId', $entryData['clientId']));
                            if(isset($entryData['serverId'])) {
                                $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $entryData['serverId']));
                            }
                            $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', $entryData['status']));
                        }
                    }
                    
                    // sent reponse for changed entries
                    // not really needed
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
                    
                    $dataController = Syncope_Data_Factory::factory($collectionData['folder']->class , $this->_device, $this->_syncTimeStamp);
                    
                    // sent response for to be fetched entries
                    if(!empty($collectionData['toBeFetched'])) {
                        if($responses === NULL) {
                            $responses = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Responses'));
                        }
                        foreach($collectionData['toBeFetched'] as $serverId) {
                            $fetch = $responses->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Fetch'));
                            $fetch->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));

                            
                            try {
                                $applicationData = $this->_outputDom->createElementNS('uri:AirSync', 'ApplicationData');
                                $dataController->appendXML($applicationData, $collectionData, $serverId);
                                
                                $fetch->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_SUCCESS));
                                
                                $fetch->appendChild($applicationData);
                            } catch (Exception $e) {
                                if ($this->_logger instanceof Zend_Log) 
                                    $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " unable to convert entry to xml: " . $e->getMessage());
                                $fetch->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_OBJECT_NOT_FOUND));
                            }
                        }
                    }
                    
                    if($collectionData['getChanges'] === true) {
                        if($collectionData['syncKey'] === 1) {
                            // all entries available
                            $serverAdds    = $dataController->getServerEntries($collectionData['collectionId'], $collectionData['filterType']);
                            $serverChanges = array();
                            $serverDeletes = array();
                        } else {
                            // continue sync session
                            if(is_array($collectionData['syncState']->pendingdata)) {
                                if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " restored from sync state ");
                                $serverAdds    = $collectionData['syncState']->pendingdata['serverAdds'];
                                $serverChanges = $collectionData['syncState']->pendingdata['serverChanges'];
                                $serverDeletes = $collectionData['syncState']->pendingdata['serverDeletes'];
                            } else {
                                // fetch entries added since last sync
                                $allClientEntries = $this->_contentStateBackend->getFolderState($this->_device, $collectionData['folder']);
                                $allServerEntries = $dataController->getServerEntries($collectionData['collectionId'], $collectionData['filterType']);
                                
                                // add entries
                                $serverDiff = array_diff($allServerEntries, $allClientEntries);
                                // add entries which produced problems during delete from client
                                $serverAdds = $collectionData['forceAdd'];
                                // add entries not yet sent to client
                                $serverAdds = array_unique(array_merge($serverAdds, $serverDiff));

                                # @todo still needed?
                                foreach($serverAdds as $id => $serverId) {
                                    // skip entries added by client during this sync session
                                    if(isset($collectionData['added'][$serverId]) && !isset($collectionData['forceAdd'][$serverId])) {
                                        if ($this->_logger instanceof Zend_Log) 
                                            $this->_logger->info(__METHOD__ . '::' . __LINE__ . " skipped added entry: " . $serverId);
                                        unset($serverAdds[$id]);
                                    }
                                }
                                
                                // entries to be deleted
                                $serverDeletes = array_diff($allClientEntries, $allServerEntries);
                                
                                // fetch entries changed since last sync
                                $serverChanges = $dataController->getChangedEntries($collectionData['collectionId'], $collectionData['syncState']->lastsync, $this->_syncTimeStamp);
                                $serverChanges = array_merge($serverChanges, $collectionData['forceChange']);

                                foreach($serverChanges as $id => $serverId) {
                                    // skip entry, if it got changed by client during current sync
                                    if(isset($collectionData['changed'][$serverId]) && !isset($collectionData['forceChange'][$serverId])) {
                                        if ($this->_logger instanceof Zend_Log) 
                                            $this->_logger->info(__METHOD__ . '::' . __LINE__ . " skipped changed entry: " . $serverId);
                                        unset($serverChanges[$id]);
                                    }
                                }
                                
                                // entries comeing in scope are already in $serverAdds and do not need to
                                // be send with $serverCanges
                                $serverChanges = array_diff($serverChanges, $serverAdds);
                            }                        
                        }
                        
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->info(__METHOD__ . '::' . __LINE__ . " found (added/changed/deleted) " . count($serverAdds) . '/' . count($serverChanges) . '/' . count($serverDeletes)  . ' entries for sync from server to client');
    
                        if ((count($serverAdds) + count($serverChanges) + count($serverDeletes)) > $collectionData['windowSize'] ) {
                            $this->_moreAvailable = true;
                            $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'MoreAvailable'));
                        }
                        
                        if (count($serverAdds) > 0 || count($serverChanges) > 0 || count($serverDeletes) > 0) {
                            $commands = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Commands'));
                        }
                        
                        /**
                         * process added entries
                         */
                        foreach($serverAdds as $id => $serverId) {
                            if($this->_totalCount === $collectionData['windowSize']) {
                                break;
                            }
                            
                            try {
                                $add = $this->_outputDom->createElementNS('uri:AirSync', 'Add');
                                $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                                
                                $applicationData = $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ApplicationData'));
                                $dataController->appendXML($applicationData, $collectionData, $serverId);
        
                                $commands->appendChild($add);
                                
                                $this->_totalCount++;
                            } catch (Exception $e) {
                                if ($this->_logger instanceof Zend_Log) 
                                    $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " unable to convert entry to xml: " . $e->getMessage());
                            }
                            
                            // mark as send to the client, even the conversion to xml might have failed 
                            $contentState = $this->_contentStateBackend->create(new Syncope_Model_Content(array(
                                'device_id'     => $this->_device,
                                'folder_id'     => $collectionData['folder'],
                                'contentid'     => $serverId,
                                'creation_time' => $this->_syncTimeStamp
                            )));
                            
                            unset($serverAdds[$id]);    
                        }
    
                        /**
                         * process changed entries
                         */
                        foreach($serverChanges as $id => $serverId) {
                            if($this->_totalCount === $collectionData['windowSize']) {
                                break;
                            }

                            try {
                                $change = $this->_outputDom->createElementNS('uri:AirSync', 'Change');
                                $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                                
                                $applicationData = $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ApplicationData'));
                                $dataController->appendXML($applicationData, $collectionData, $serverId);
        
                                $commands->appendChild($change);
                                
                                $this->_totalCount++;
                            } catch (Exception $e) {
                                if ($this->_logger instanceof Zend_Log) 
                                    $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " unable to convert entry to xml: " . $e->getMessage());
                            }

                            unset($serverChanges[$id]);    
                        }
    
                        /**
                         * process deleted entries
                         */
                        foreach($serverDeletes as $id => $serverId) {
                            if($this->_totalCount === $collectionData['windowSize']) {
                                break;
                            }
                                                        
                            try {
                                // check if we have sent this entry to the phone
                                $state = $this->_contentStateBackend->getContentState($this->_device, $collectionData['folder'], $serverId);
                                
                                $delete = $this->_outputDom->createElementNS('uri:AirSync', 'Delete');
                                $delete->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                                
                                $this->_contentStateBackend->delete($state);
                                
                                $commands->appendChild($delete);
                                
                                $this->_totalCount++;
                            } catch (Exception $e) {
                                if ($this->_logger instanceof Zend_Log) 
                                    $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " unable to convert entry to xml: " . $e->getMessage());
                            }
                            
                            unset($serverDeletes[$id]);    
                        }
                    }
                    if ($this->_logger instanceof Zend_Log) 
                        $this->_logger->info(__METHOD__ . '::' . __LINE__ . " new synckey is ". $collectionData['syncState']->counter);                
                }
                
                if (isset($collectionData['syncState']) && $collectionData['syncState'] instanceof Syncope_Model_ISyncState && 
                    $collectionData['syncState']->counter != $collectionData['syncKey']) {
                    // increment sync timestamp by 1 second
                    $this->_syncTimeStamp->modify('+1 sec');
                    
                    // store pening data in sync state when needed
                    if($this->_moreAvailable === true) {
                        $collectionData['syncState']->pendingdata = array(
                            'serverAdds'    => (array)$serverAdds,
                            'serverChanges' => (array)$serverChanges,
                            'serverDeletes' => (array)$serverDeletes
                        );
                    } else {
                        $collectionData['syncState']->pendingdata = null;
                    }
                    
                    
                    if (!empty($collectionData['added'])) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->info(__METHOD__ . '::' . __LINE__ . " remove previous synckey as client added new entries");
                        $keepPreviousSyncKey = false;
                    } else {
                        $keepPreviousSyncKey = true;
                    }
                    
                    $collectionData['syncState']->lastsync = $this->_syncTimeStamp;
                    
                    $this->_syncStateBackend->create($collectionData['syncState'], $keepPreviousSyncKey);
                    
                    // store current filter type
                    try {
                        $folderState = $this->_folderBackend->getFolder($this->_device, $collectionData['collectionId']);
                        $folderState->lastfiltertype = $collectionData['filterType'];
                        $this->_folderBackend->update($folderState);
                    } catch (Syncope_Exception_NotFound $senf) {
                        // failed to get folderstate => should not happen but is also no problem in this state
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->crit(__METHOD__ . '::' . __LINE__ . ' failed to get content state for: ' . $collectionData['collectionId']);
                    }
                }
            }
        }
        
        return $this->_outputDom;
    }

    /**
     * delete contentstate (aka: forget that we have sent the entry to the client)
     *
     * @param string $_class the class from the xml
     * @param string $_collectionId the collection id from the xml
     * @param string $_contentId the Tine 2.0 id of the entry
     */
    protected function __markContentAsDeleted($_class, $_collectionId, $_contentId)
    {
        $contentState = new Syncope_Model_Content(array(
                'device_id'     => $this->_device->getId(),
                'class'         => $_class,
                'collectionid'  => $_collectionId,
                'contentid'     => $_contentId,
                'creation_time' => $this->_syncTimeStamp
        ));
    
        $this->_controller->markContentAsDeleted($contentState);
    }    
}
