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
    
    protected $_added;
    protected $_changed;
    protected $_deleted;
    
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
        $controller = ActiveSync_Controller::getInstance();
        $this->_session = new Zend_Session_Namespace('moreData');
        
        $xml = new SimpleXMLElement($this->_inputDom->saveXML(), LIBXML_NOWARNING);
        $xml->registerXPathNamespace('AirSync', 'AirSync');
        
        $sync = $this->_outputDom->appendChild($this->_outputDom->createElementNS('AirSync', 'Sync'));
        $collections = $sync->appendChild($this->_outputDom->createElementNS('AirSync', 'Collections'));
        
        foreach ($xml->Collections->Collection as $xmlCollection) {
            $clientSyncKey  = (int)$xmlCollection->SyncKey;
            $class          = (string)$xmlCollection->Class;
            $collectionId   = (string)$xmlCollection->CollectionId;
            $windowSize     = isset($xmlCollection->WindowSize) ? (int)$xmlCollection->WindowSize : 100;
            $itemsInCollection = 0;
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " SyncKey is $clientSyncKey Class: $class CollectionId: $collectionId WindowSize: $windowSize");
            
            $collectionData = array(
                'syncKey'       => $clientSyncKey,
                'class'         => $class,
                'collectionId'  => $collectionId,
                'windowSize'    => $windowSize,
                'added'         => array(),
                'changed'       => array(),
                'deleted'       => array()
            );
            
            $this->_dataController = ActiveSync_Controller::dataFactory($class, $this->_syncTimeStamp);
            $this->_added   = array();
            $this->_changed = array();
            $this->_deleted = array();
            
            if($clientSyncKey === 0) {
                $this->_handleSync0($collections, $class, $collectionId);
            } elseif($controller->validateSyncKey($this->_device, $clientSyncKey, $class . '-' . $collectionId) === true) {
                if($clientSyncKey === 1) {
                    $this->_handleInitialSync($xml, $collections, $collectionId);
                } else {
                    $this->_handleIncrementalSync($xml, $collections, $collectionId);
                }
                
                // create response
                $newSyncKey = $clientSyncKey + 1;
                
                $collection = $collections->appendChild($this->_outputDom->createElementNS('AirSync', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Class', $class));
                $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'SyncKey', $newSyncKey));
                $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'CollectionId', $collectionId));
                $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Status', self::STATUS_SUCCESS));
    
                if(!empty($this->_added)) {
                    $response = $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Responses'));
                    foreach($this->_added as $serverId => $clientId) {
                        $add = $response->appendChild($this->_outputDom->createElementNS('AirSync', 'Add'));
                        $add->appendChild($this->_outputDom->createElementNS('AirSync', 'ClientId', $clientId));
                        $add->appendChild($this->_outputDom->createElementNS('AirSync', 'ServerId', $serverId));
                        $add->appendChild($this->_outputDom->createElementNS('AirSync', 'Status', self::STATUS_SUCCESS));
                    }
                }
                
                if(isset($xmlCollection->GetChanges)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " client requested changes");
                    $syncState = $controller->getSyncState($this->_device, $class . '-' . $collectionId, $clientSyncKey);
                    
                    if($clientSyncKey === 1) {
                        $serverAdds = $this->_dataController->getSince('added', '0000-00-00 00:00:00', $this->_syncTimeStamp);
                        
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($serverAdds) . ' for first sync');

                        if(count($serverAdds) > 0) {
                            $commands = NULL;
                            foreach($serverAdds as $serverAdd) {
                                // skip entry, was added by client
                                if(isset($this->_added[$serverAdd->getId()])) {
                                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " skipped added entry: " . $serverAdd->getId());
                                    continue;
                                }
                                
                                // create commands node only when needed
                                if($commands === NULL) {
                                    $commands = $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Commands'));
                                }
                                
                                $add = $commands->appendChild($this->_outputDom->createElementNS('AirSync', 'Add'));
                                $add->appendChild($this->_outputDom->createElementNS('AirSync', 'ServerId', $serverAdd->getId()));
                                $applicationData = $add->appendChild($this->_outputDom->createElementNS('AirSync', 'ApplicationData'));
                                $this->_dataController->appendXML($this->_outputDom, $applicationData, $serverAdd);
                                
                                $itemsInCollection++;
                            }
                        }
                        #error_log(__METHOD__ . '::' . __LINE__ . " " . print_r($this->_outputDom->saveXML(), true));
                    } elseif($this->_dataController->getItemEstimate($syncState->lastsync, $this->_syncTimeStamp) > 0 ) {
                        $commands = NULL;
                        
                        /**
                         * process added entries
                         */
                        if($itemsInCollection < $windowSize) {
                            if(isset($this->_session->serverAdds)) {
                                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' use cache');
                                $serverAdds = $this->_session->serverAdds;
                                unset($this->_session->serverAdds);
                            } else {
                                $serverAdds = $this->_dataController->getSince('added', $syncState->lastsync, $this->_syncTimeStamp);
                            }
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($serverAdds) . ' added entries');
                            foreach($serverAdds as $id => $serverAdd) {
                                if($itemsInCollection === $windowSize) {
                                    $this->_session->serverAdds = $serverAdds;
                                    $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'MoreAvailable'));
                                    break;
                                }
                                
                                unset($serverAdds[$id]);
                                
                                // skip entry, was added by client
                                if(isset($this->_added[$serverAdd->getId()])) {
                                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " skipped added entry: " . $serverAdd->getId());
                                    continue;
                                }
                                
                                // create commands node only when needed
                                if($commands === NULL) {
                                    $commands = $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Commands'));
                                }
                                
                                $add = $commands->appendChild($this->_outputDom->createElementNS('AirSync', 'Add'));
                                $add->appendChild($this->_outputDom->createElementNS('AirSync', 'ServerId', $serverAdd->getId()));
                                $applicationData = $add->appendChild($this->_outputDom->createElementNS('AirSync', 'ApplicationData'));
                                $this->_dataController->appendXML($this->_outputDom, $applicationData, $serverAdd);
    
                                $itemsInCollection++;                                
                            }
                        }

                        /**
                         * process changed entries
                         */
                        if($itemsInCollection < $windowSize) {
                            if(isset($this->_session->serverChanges)) {
                                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' use cache');
                                $serverChanges = $this->_session->serverChanges;
                                unset($this->_session->serverChanges);
                            } else {
                                $serverChanges = $this->_dataController->getSince('changed', $syncState->lastsync, $this->_syncTimeStamp);
                            }
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($serverChanges) . ' changed entries');
                            foreach($serverChanges as $id => $serverChange) {
                                if($itemsInCollection === $windowSize) {
                                    $this->_session->serverChanges = $serverChanges;
                                    $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'MoreAvailable'));
                                    break;
                                }
                                
                                unset($serverChanges[$id]);
                                
                                // skip entry, was changed by client
                                if(isset($this->_changed[$serverChange->getId()])) {
                                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " skipped changed entry: " . $serverChange->getId());
                                    continue;
                                }
                                
                                // create commands node only when needed
                                if($commands === NULL) {
                                    $commands = $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Commands'));
                                }
                                
                                $change = $commands->appendChild($this->_outputDom->createElementNS('AirSync', 'Change'));
                                $change->appendChild($this->_outputDom->createElementNS('AirSync', 'ServerId', $serverChange->getId()));
                                $applicationData = $change->appendChild($this->_outputDom->createElementNS('AirSync', 'ApplicationData'));
                                $this->_dataController->appendXML($this->_outputDom, $applicationData, $serverChange);
    
                                $itemsInCollection++;
                            }
                        }
                        
                        /**
                         * process deleted entries
                         */
                        if(isset($this->_session->serverDeletes)) {
                            $serverDeletes = $this->_session->serverDeletes;
                        } else {
                            $serverDeletes = $this->_dataController->getSince('deleted', $syncState->lastsync, $this->_syncTimeStamp);
                        }
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($serverDeletes) . ' deleted entries');
                        foreach($serverDeletes as $delete) {
                        #    $this->_serverDelete($commands, $delete);

                            $itemsInCollection++;
                        }
                    }
                }
                /**
                  <MoreAvailable/>
                 */
                
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " new synckey is $newSyncKey");
                
                // increment sync timestamp by 1 second
                $this->_syncTimeStamp->add('1', Zend_Date::SECOND);
                $controller->updateSyncKey($this->_device, $newSyncKey, $class . '-' . $collectionId, $this->_syncTimeStamp);
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey $clientSyncKey provided");
                
                $collection = $collections->appendChild($this->_outputDom->createElementNS('AirSync', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Class', $class));
                $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'SyncKey', 1));
                $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'CollectionId', $collectionId));
                $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Status', self::STATUS_INVALID_SYNC_KEY));
                
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " new synckey is 1");
                $controller->updateSyncKey($this->_device, 1, $class . '-' . $collectionId, $this->_syncTimeStamp);
                
                #$sync = $this->_outputDom->appendChild($this->_outputDom->createElementNS('AirSync', 'Sync'));
                #$sync->appendChild($this->_outputDom->createElementNS('AirSync', 'Status', self::STATUS_INVALID_SYNC_KEY));
                
                #$sync = $this->_outputDom->appendChild($this->_outputDom->createElementNS('AirSync', 'Sync'));
                #$collections = $sync->appendChild($this->_outputDom->createElementNS('AirSync', 'Collections'));
                #$collection = $collections->appendChild($this->_outputDom->createElementNS('AirSync', 'Collection'));
                #$collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Class', $class));
                #$collection->appendChild($this->_outputDom->createElementNS('AirSync', 'CollectionId', $collectionId));
                #$collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Status', self::STATUS_INVALID_SYNC_KEY));
            }        
        }
        
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $this->_outputDom->saveXML());
        
        #$outputStream = fopen("php://temp", 'r+');
        #$encoder = new Wbxml_Encoder($outputStream, 'UTF-8', 3);
        #$encoder->encode($this->_outputDom);
        
        #return $outputStream;
    }    
    
    protected function _handleSync0($_collections, $_class, $_collectionId)
    {
        // Sync 0
        // send back a new SyncKey only
        $collection = $_collections->appendChild($this->_outputDom->createElementNS('AirSync', 'Collection'));
        $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Class', $_class));
        $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'SyncKey', 1));
        $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'CollectionId', $_collectionId));
        $collection->appendChild($this->_outputDom->createElementNS('AirSync', 'Status', self::STATUS_SUCCESS));
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " new synckey is 1");
        
        // store new synckey
        ActiveSync_Controller::getInstance()->updateSyncKey($this->_device, 1, $_class . '-' . $_collectionId, NULL);        
    }
    
    protected function _handleInitialSync($_xml, $_collections, $_collectionId)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " doing intial sync");

        // handle incoming data
        $adds = $_xml->xpath('//AirSync:Add');
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($adds) . " client adds");
        
        foreach ($adds as $add) {
            // search for existing entries
            $existing = $this->_dataController->search($_collectionId, $add->ApplicationData);
            
            if(count($existing) === 0) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " entry not found. adding as new");
                $added = $this->_dataController->add($_collectionId, $add->ApplicationData);
            } else {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found matching entry. reuse existing entry");
                // use the first found entry
                $added = $existing[0];
            }
            $this->_added[$added->getId()] = (string)$add->ClientId;
        }
    }
    
    protected function _handleIncrementalSync($_xml, $_collections, $_collectionId)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " doing incremental sync");
        // handle incoming data
        $adds = $_xml->xpath('//AirSync:Add');
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($adds) . " client adds");
        foreach ($adds as $add) {
            $added = $this->_dataController->add($_collectionId, $add->ApplicationData);
            $this->_added[$added->getId()] = (string)$add->ClientId;
        }
        
        $changes = $_xml->xpath('//AirSync:Change');
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($changes) . " client changes");
        foreach ($changes as $change) {
            $changed = $this->_dataController->change($_collectionId, (string)$change->ServerId, $change->ApplicationData);
            $this->_changed[$changed->getId()] = (string)$change->ServerId;
        }
        
        $deletes = $_xml->xpath('//AirSync:Delete');
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " found " . count($deletes) . " client deletes");
        foreach ($deletes as $delete) {
            try {
                $this->_dataController->delete($_collectionId, (string)$delete->ServerId);
            } catch(Tinebase_Exception_NotFound $e) {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' tried to delete entry ' . (string)$delete->ServerId . ' but entry was not found');
            }
            $this->_deleted[(string)$delete->ServerId] = (string)$delete->ServerId;
        }
    }
}