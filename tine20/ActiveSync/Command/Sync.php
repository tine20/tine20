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
 
class ActiveSync_Command_Sync extends ActiveSync_Command_Wbxml 
{
    const STATUS_SUCCESS                              = 1;
    const STATUS_PROTOCOL_VERSION_MISMATCH            = 2;
    const STATUS_INVALID_SYNC_KEY                     = 3;
    const STATUS_PROTOCOL_ERROR                       = 4;
    const STATUS_SERVER_ERROR                         = 5;
    const STATUS_ERROR_IN_CLIENT_SERVER_CONVERSION    = 6;
    const STATUS_CONFLICT_MATCHING_THE_CLIENT_AND_SERVER_OBJECT = 7;
    const STATUS_OBJECT_NOT_FOUND                     = 8;
    const STATUS_USER_ACCOUNT_MAYBE_OUT_OF_DISK_SPACE = 9;
    
    const CONFLICT_OVERWRITE_SERVER = 0;
    const CONFLICT_OVERWRITE_PIM    = 1;

    /**
     * An error occurred while setting the notification GUID. = 10
     * Device has not been provisioned for notifications yet. = 11
     */
    
    protected $_defaultNameSpace = 'uri:AirSync';
    protected $_documentElement = 'Sync';
    
    #protected $_added;
    #protected $_changed;
    #protected $_deleted;
    
    /**
     * list of collections
     *
     * @var array
     */
    protected $_collections = array();
    
    /**
     * controller to handle contacts, task, calendar or email
     *
     * @var ActiveSync_Controller_Abstract
     */
    protected $_dataController;
    
    protected $_session;
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @return resource
     */
    public function handle()
    {
        $controller             = ActiveSync_Controller::getInstance();
        $contentStateBackend    = new ActiveSync_Backend_ContentState();
        $this->_session         = new Zend_Session_Namespace('moreData');
        
        // input xml
        $xml = new SimpleXMLElement($this->_inputDom->saveXML());
                
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
                'deleted'       => array()
            );
            $this->_collections[$class] = $collectionData;
            
            if($clientSyncKey === 0 || $controller->validateSyncKey($this->_device, $clientSyncKey, $class . '-' . $collectionId) !== true) {
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
                    $contentState = new ActiveSync_Model_ContentState(array(
                        'device_id'     => $this->_device->getId(),
                        'class'         => $collectionData['class'],
                        'contentid'     => $added->getId(),
                        'creation_time' => $this->_syncTimeStamp
                    ));
                    $contentStateBackend->create($contentState);
                }
            }
        
            // handle changes, but only when not first sync
            if($clientSyncKey > 1 && isset($xmlCollection->Commands->Change)) {
                $changes = $xmlCollection->Commands->Change;
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($changes) . " entries to be updated on server");
                foreach ($changes as $change) {
                    $changed = $dataController->change($_collectionId, (string)$change->ServerId, $change->ApplicationData);
                    $this->_collections[$class]['changed'][$changed->getId()] = (string)$change->ServerId;
                }
            }
        
            // handle deletes, but only when not first sync
            if($clientSyncKey > 1 && isset($xmlCollection->Commands->Delete)) {
                $deletes = $xmlCollection->Commands->Delete;
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($deletes) . " entries to be deleted on server");
                foreach ($deletes as $delete) {
                    try {
                        $dataController->delete($_collectionId, (string)$delete->ServerId);
                    } catch(Tinebase_Exception_NotFound $e) {
                        Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' tried to delete entry ' . (string)$delete->ServerId . ' but entry was not found');
                    }
                    $this->_collections[$class]['deleted'][(string)$delete->ServerId] = (string)$delete->ServerId;
                }
            }            
        }        
    }    
    
    public function getResponse()
    {
        $controller             = ActiveSync_Controller::getInstance();
        $contentStateBackend    = new ActiveSync_Backend_ContentState();
        
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
                                
            } else {
                $newSyncKey = $collectionData['syncKey'] + 1;
                
                // collection header
                $collection = $collections->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Class', $collectionData['class']));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'SyncKey', $newSyncKey));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $collectionData['collectionId']));
                $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_SUCCESS));

                // ids of newly added entries
                if(!empty($collectionData['added'])) {
                    $responses = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Responses'));
                    foreach($collectionData['added'] as $serverId => $clientId) {
                        $add = $responses->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Add'));
                        $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ClientId', $clientId));
                        $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverId));
                        $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Status', self::STATUS_SUCCESS));
                    }
                }
                
                if($collectionData['getChanges'] === true) {
                    $dataController = ActiveSync_Controller::dataFactory($collectionData['class'], $this->_syncTimeStamp);
                    
                    if($collectionData['syncKey'] === 1) {
                        // all entries available
                        $serverAdds = $dataController->getSince('added', '0000-00-00 00:00:00', $this->_syncTimeStamp);
                    } else {
                        // all entries added since last sync
                        $syncState  = $controller->getSyncState($this->_device, $collectionData['class'] . '-' . $collectionData['collectionId'], $collectionData['syncKey']);
                        $serverAdds = $dataController->getSince('added', $syncState->lastsync, $this->_syncTimeStamp);
                    }
                    
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($serverAdds) . ' for first sync');

                    if(count($serverAdds) > 0) {
                        $commands = NULL;
                        
                        foreach($serverAdds as $id => $serverAdd) {
                            #if($itemsInCollection === $windowSize) {
                            #    $this->_session->serverAdds = $serverAdds;
                            #    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'MoreAvailable'));
                            #    break;
                            #}
                            
                            #unset($serverAdds[$id]);
                            
                            // skip entry, was added by client
                            if(isset($collectionData['added'][$serverAdd->getId()])) {
                                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " skipped added entry: " . $serverAdd->getId());
                                continue;
                            }
                            
                            // create commands node only when needed
                            if($commands === NULL) {
                                $commands = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Commands'));
                            }
                            
                            $add = $commands->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Add'));
                            $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverAdd->getId()));
                            $applicationData = $add->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ApplicationData'));
                            $dataController->appendXML($this->_outputDom, $applicationData, $serverAdd);
                            $contentState = new ActiveSync_Model_ContentState(array(
                                'device_id'     => $this->_device->getId(),
                                'class'         => $collectionData['class'],
                                'contentid'     => $serverAdd->getId(),
                                'creation_time' => $this->_syncTimeStamp
                            ));
                            $contentStateBackend->create($contentState);
                            
                            #$itemsInCollection++;                                
                        }
                    }

                    if($collectionData['syncKey'] > 1) {
                        $serverChanges = $dataController->getSince('changed', $syncState->lastsync, $this->_syncTimeStamp);


                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($serverChanges) . ' changed entries');
                        foreach($serverChanges as $id => $serverChange) {
                            #if($itemsInCollection === $windowSize) {
                            #    $this->_session->serverChanges = $serverChanges;
                            #    $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'MoreAvailable'));
                            #    break;
                            #}
                            
                            #unset($serverChanges[$id]);
                            
                            // skip entry, was changed by client
                            if(isset($collectionData['changed'][$serverChange->getId()])) {
                                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " skipped changed entry: " . $serverChange->getId());
                                continue;
                            }
                            
                            // create commands node only when needed
                            if($commands === NULL) {
                                $commands = $collection->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Commands'));
                            }
                            
                            $change = $commands->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'Change'));
                            $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId', $serverChange->getId()));
                            $applicationData = $change->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ApplicationData'));
                            $dataController->appendXML($this->_outputDom, $applicationData, $serverChange);

                            $itemsInCollection++;
                        }
                        
                        /**
                         * process deleted entries
                         */
                        $serverDeletes = $dataController->getSince('deleted', $syncState->lastsync, $this->_syncTimeStamp);
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($serverDeletes) . ' deleted entries');
                        foreach($serverDeletes as $delete) {
                        #    $this->_serverDelete($commands, $delete);

                        #    $itemsInCollection++;
                        }
                    }
                }
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " new synckey is $newSyncKey");                
            }
            // increment sync timestamp by 1 second
            $this->_syncTimeStamp->add('1', Zend_Date::SECOND);
            $controller->updateSyncKey($this->_device, $newSyncKey, $collectionData['class'] . '-' . $collectionData['collectionId'], $this->_syncTimeStamp);
        }
        parent::getResponse();
    }
}