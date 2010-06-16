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
 
class ActiveSync_Command_Ping extends ActiveSync_Command_Wbxml 
{
    const STATUS_NO_CHANGES_FOUND           = 1;
    const STATUS_CHANGES_FOUND              = 2;
    const STATUS_MISSING_PARAMETERS         = 3;
    const STATUS_REQUEST_FORMAT_ERROR       = 4;
    const STATUS_INTERVAL_TO_GREAT_OR_SMALL = 5;
    const STATUS_TO_MUCH_FOLDERS            = 6;
    const STATUS_FOLDER_NOT_FOUND           = 7;
    const STATUS_GENERAL_ERROR              = 8;
    
    /**
     * folders to monitor
     *
     * @var array
     */
    #protected $_folders = array();
    
    protected $_changesDetected = false;
    
    const PING_TIMEOUT = 10;
    
    /**
     * Enter description here...
     *
     * @var ActiveSync_Backend_StandAlone_Abstract
     */
    protected $_dataBackend;

    protected $_defaultNameSpace = 'uri:Ping';
    protected $_documentElement = 'Ping';
    
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @todo we need to stored the initial data for folders and lifetime as the phone is sending them only when they change
     * @return resource
     */
    public function handle()
    {
        $controller = ActiveSync_Controller::getInstance();
        
        $intervalStart = mktime();
        $status = self::STATUS_NO_CHANGES_FOUND;

        // the client does not send a wbxml document, if the Ping parameters did not change compared with the last request
        if($this->_inputDom instanceof DOMDocument) {
            #$xml = simplexml_load_string($this->_inputDom->saveXML());
            $xml = new SimpleXMLElement($this->_inputDom->saveXML(), LIBXML_NOWARNING);
            $xml->registerXPathNamespace('Ping', 'Ping');    

            if(isset($xml->HeartBeatInterval)) {
                $this->_device->pinglifetime = $xml->HeartBeatInterval;
            }
            
            if(isset($xml->Folders->Folder)) {
                foreach ($xml->Folders->Folder as $folderXml) {
                    #Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " folderType: " . print_r($folderXml, true));
                    #$folderBackend = $this->_backend->factory((string)$folderXml->Class);
                    try {
                        // does the folder exist?
            #            $folderBackend->getFolder($folderXml->Id);
                        
                        $folder = array(
                            'serverEntryId' => (string)$folderXml->Id,
                            'folderType'    => (string)$folderXml->Class
                        );
                        
                        $folders[] = $folder;                
                    } catch (Exception $e) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $e->getMessage());
                        $status = self::STATUS_FOLDER_NOT_FOUND;
                        break;
                    }
                }
                $this->_device->pingfolder = serialize($folders);
            }
            $this->_device = $controller->updateDevice($this->_device);
        }
        
        $lifeTime = $this->_device->pinglifetime;
        Tinebase_Core::setExecutionLifeTime($lifeTime);
        
        $intervalEnd = $intervalStart + $lifeTime;
        $secondsLeft = $intervalEnd;
        $folders = unserialize($this->_device->pingfolder);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Folders to monitor($lifeTime / $intervalStart / $intervalEnd / $status): " . print_r($folders, true));
        
        if($status === self::STATUS_NO_CHANGES_FOUND) {

            $folderWithChanges = array();
            
            do {
                foreach((array) $folders as $folder) {
                    $dataController = ActiveSync_Controller::dataFactory($folder['folderType'], $this->_device, $this->_syncTimeStamp);
                    #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . print_r($folder, true));
                    try {
                        $syncState = $controller->getSyncState($this->_device, $folder['folderType'], $folder['serverEntryId']);
                        //$count = $dataController->getItemEstimate($syncState->lastsync);
                        $count = $this->_getItemEstimate(
                            $dataController,
                            $folder,
                            $syncState->lastsync
                        );
                                                
                        if($count > 0) {
                            $folderWithChanges[] = array(
                                'serverEntryId' => $folder['serverEntryId'],
                                'folderType'    => $folder['folderType']
                            );
                            $status = self::STATUS_CHANGES_FOUND;
                        }
                    } catch (ActiveSync_Exception_SyncStateNotFound $e) {
                        // folder got never synchronized to client
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $e->getMessage());
                    }
                }
                
                if($status === self::STATUS_CHANGES_FOUND) {
                    break;
                }
                
                // another process synchronized data already
                if(isset($syncState) && $syncState->lastsync > $this->_syncTimeStamp) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " terminate ping process. Some other process updated data already.");
                    break;
                }
                
                sleep(self::PING_TIMEOUT);
                $secondsLeft = $intervalEnd - mktime();
                //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_device->deviceid . " seconds left: " . $secondsLeft);
            } while($secondsLeft > 0);
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_device->deviceid . " Lifetime: $lifeTime SecondsLeft: $secondsLeft Status: $status)");
        
        $ping = $this->_outputDom->documentElement;
        $ping->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Status', $status));
        if($status === self::STATUS_CHANGES_FOUND) {
            $folders = $ping->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Folders'));
            foreach($folderWithChanges as $changedFolder) {
                $folder = $folders->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Folder', $changedFolder['serverEntryId']));
                #$folder->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Id', $changedFolder['serverEntryId']));
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_device->deviceid . " changes in folder: " . $changedFolder['serverEntryId']);
            }
        }                
    }    
    
    /**
     * this function generates the response for the client
     */
    public function getResponse()
    {
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
        // hack to have the same variable names all over the place
        $_collectionData['class']        = $_collectionData['folderType'];
        $_collectionData['collectionId'] = $_collectionData['serverEntryId'];
        
        $contentStateBackend  = new ActiveSync_Backend_ContentState();
        $folderStateBackend   = new ActiveSync_Backend_FolderState();
        // get current filterType
        $filter = new ActiveSync_Model_FolderStateFilter(array(
            array(
                'field'     => 'device_id',
                'operator'  => 'equals',
                'value'     => $this->_device->getId(),
            ),
            array(
                'field'     => 'class',
                'operator'  => 'equals',
                'value'     => $_collectionData['class'],
            ),
            array(
                'field'     => 'folderid',
                'operator'  => 'equals',
                'value'     => $_collectionData['collectionId']
            )
        ));
        $folderState = $folderStateBackend->search($filter)->getFirstRecord();
        
        if($folderState instanceof ActiveSync_Model_FolderState) {
            $filterType = $folderState->lastfiltertype;
        } else {
            $filterType = 0;
        }

        $allClientEntries   = $contentStateBackend->getClientState($this->_device, $_collectionData['class'], $_collectionData['collectionId']);
        
        $_dataController->updateCache($_collectionData['collectionId']);
        $allServerEntries   = $_dataController->getServerEntries($_collectionData['collectionId'], $filterType);
            
        $addedEntries       = array_diff($allServerEntries, $allClientEntries);
        $deletedEntries     = array_diff($allClientEntries, $allServerEntries);
        $changedEntries     = $_dataController->getChanged($_collectionData['collectionId'], $_lastSyncTimeStamp);
        
        return count($addedEntries) + count($deletedEntries) + count($changedEntries);
    }
    
}