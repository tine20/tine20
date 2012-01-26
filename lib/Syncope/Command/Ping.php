<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Ping command
 *
 * @package     Syncope
 * @subpackage  Command
 */
class Syncope_Command_Ping extends Syncope_Command_Wbxml 
{
    const STATUS_NO_CHANGES_FOUND           = 1;
    const STATUS_CHANGES_FOUND              = 2;
    const STATUS_MISSING_PARAMETERS         = 3;
    const STATUS_REQUEST_FORMAT_ERROR       = 4;
    const STATUS_INTERVAL_TO_GREAT_OR_SMALL = 5;
    const STATUS_TO_MUCH_FOLDERS            = 6;
    const STATUS_FOLDER_NOT_FOUND           = 7;
    const STATUS_GENERAL_ERROR              = 8;
    
    protected $_skipValidatePolicyKey = true;
    
    protected $_changesDetected = false;
    
    const PING_TIMEOUT = 60;
    
    /**
     * Enter description here...
     *
     * @var Syncope_Backend_StandAlone_Abstract
     */
    protected $_dataBackend;

    protected $_defaultNameSpace = 'uri:Ping';
    protected $_documentElement = 'Ping';
    
    protected $_foldersWithChanges = array();
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @todo we need to stored the initial data for folders and lifetime as the phone is sending them only when they change
     * @return resource
     */
    public function handle()
    {
        $intervalStart = time();
        $status = self::STATUS_NO_CHANGES_FOUND;

        // the client does not send a wbxml document, if the Ping parameters did not change compared with the last request
        if($this->_inputDom instanceof DOMDocument) {
            $xml = simplexml_import_dom($this->_inputDom);
            $xml->registerXPathNamespace('Ping', 'Ping');    

            if(isset($xml->HeartBeatInterval)) {
                $this->_device->pinglifetime = (int)$xml->HeartBeatInterval;
            }
            
            if(isset($xml->Folders->Folder)) {
                $folders = array();
                foreach ($xml->Folders->Folder as $folderXml) {
                    try {
                        // does the folder exist?
                        $folder = $this->_folderBackend->getFolder($this->_device, (string)$folderXml->Id);
                        
                        $folders[] = $folder;                
                    } catch (Syncope_Exception_NotFound $senf) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " " . $senf->getMessage());
                        $status = self::STATUS_FOLDER_NOT_FOUND;
                        break;
                    }
                }
                $this->_device->pingfolder = serialize($folders);
            }
            $this->_device = $this->_deviceBackend->update($this->_device);
        }
        
        $lifeTime = $this->_device->pinglifetime;
        #Tinebase_Core::setExecutionLifeTime($lifeTime);
        
        $intervalEnd = $intervalStart + $lifeTime;
        $secondsLeft = $intervalEnd;
        $folders = unserialize($this->_device->pingfolder);
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " Folders to monitor($lifeTime / $intervalStart / $intervalEnd / $status): " . print_r($folders, true));
        
        if($status === self::STATUS_NO_CHANGES_FOUND) {

            $folderWithChanges = array();
            
            do {
                foreach((array) $folders as $folder) {
                    $dataController = Syncope_Data_Factory::factory($folder->class, $this->_device, $this->_syncTimeStamp);
                    
                    try {
                        $syncState = $this->_syncStateBackend->getSyncState($this->_device, $folder);

                        $count = $dataController->hasChanges($folder, $syncState);
                        
                        $count = $this->_getItemEstimate(
                            $dataController,
                            $folder,
                            $syncState->lastsync
                        );
                    } catch (Syncope_Exception_NotFound $e) {
                        // folder got never synchronized to client
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " " . $e->getMessage());
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' syncstate not found. enforce sync for folder: ' . $folder['serverFolderId']);
                        
                        $count = 1;
                    }
                        
                    if($count > 0) {
                        $this->_foldersWithChanges[] = $folder;
                        $status = self::STATUS_CHANGES_FOUND;
                    }
                }
                
                if($status === self::STATUS_CHANGES_FOUND) {
                    break;
                }
                
                // another process synchronized data already
                if(isset($syncState) && $syncState->lastsync > $this->_syncTimeStamp) {
                    if ($this->_logger instanceof Zend_Log) 
                        $this->_logger->info(__METHOD__ . '::' . __LINE__ . " terminate ping process. Some other process updated data already.");
                    break;
                }
                
                sleep(self::PING_TIMEOUT);
                $secondsLeft = $intervalEnd - time();
                //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_device->deviceid . " seconds left: " . $secondsLeft);
            } while($secondsLeft > 0);
        }
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->info(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_device->deviceid . " Lifetime: $lifeTime SecondsLeft: $secondsLeft Status: $status)");
        
        $ping = $this->_outputDom->documentElement;
        $ping->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Status', $status));
        if($status === self::STATUS_CHANGES_FOUND) {
            $folders = $ping->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Folders'));
            
            foreach($this->_foldersWithChanges as $changedFolder) {
                $folder = $folders->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Folder', $changedFolder->folderid));
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_device->deviceid . " changes in folder: " . $changedFolder->folderid);
            }
        }                
    }
        
    /**
     * generate ping command response
     *
     */
    public function getResponse()
    {
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " " . $this->_outputDom->saveXML());
    
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
    protected function _getItemEstimate($_dataController, $_collectionData, $_lastSyncTimeStamp)
    {
        return 1;
        
        // hack to have the same variable names all over the place
        $_collectionData['class']        = $_collectionData['class'];
        $_collectionData['collectionId'] = $_collectionData['serverFolderId'];
        
        $contentStateBackend  = new Syncope_Backend_ContentState();
        $folderStateBackend   = new Syncope_Backend_FolderState();
        // get current filterType
        $filter = new Syncope_Model_FolderStateFilter(array(
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
        
        if($folderState instanceof Syncope_Model_FolderState) {
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
