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
 
class ActiveSync_Command_Ping extends ActiveSync_Command_Wbxml 
{
    const STATUS_NO_CHANGES_FOUND = 1;
    const STATUS_CHANGES_FOUND = 2;
    const STATUS_MISSING_PARAMETERS = 3;
    const STATUS_REQUEST_FORMAT_ERROR = 4;
    const STATUS_INTERVAL_TO_GREAT_OR_SMALL = 5;
    const STATUS_TO_MUCH_FOLDERS = 6;
    const STATUS_FOLDER_NOT_FOUND = 7;
    const STATUS_GENERAL_ERROR = 8;
    
    /**
     * folders to monitor
     *
     * @var array
     */
    #protected $_folders = array();
    
    protected $_changesDetected = false;
    
    const PING_TIMEOUT = 5;
    
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
        
        if($this->_inputDom instanceof DOMDocument) {
            #$syncStateClass = new ActiveSync_SyncState();
            #$xml = simplexml_load_string($this->_inputDom->saveXML());
            $xml = new SimpleXMLElement($this->_inputDom->saveXML(), LIBXML_NOWARNING);
            $xml->registerXPathNamespace('Ping', 'Ping');    

            if(isset($xml->HeartBeatInterval)) {
                $this->_device->pinglifetime = $xml->HeartBeatInterval;
            }
            
            if(isset($xml->Folders->Folder)) {
                foreach ($xml->Folders->Folder as $folderXml) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " folderType: " . print_r($folderXml, true));
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
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $e->getMessage());
                        $status = self::STATUS_FOLDER_NOT_FOUND;
                        break;
                    }
                }
                $this->_device->pingfolder = serialize($folders);
            }
            $this->_device = $controller->updateDevice($this->_device);
        }
        
        $lifeTime = $this->_device->pinglifetime;
        if(ini_get('max_execution_time') < $lifeTime) { 
            if((bool)ini_get('safe_mode') === true) {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' max_execution_time(' . ini_get('max_execution_time') . ') is to low. Can\'t set limit to ' . $lifeTime . ' because of safe mode restrictions.');
            } else { 
                set_time_limit($lifetime);
            }
        }
        
        $intervalEnd = $intervalStart + $lifeTime;
        $folders = unserialize($this->_device->pingfolder);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Folders to monitor($lifeTime / $intervalStart / $intervalEnd / $status): " . print_r($folders, true));
        
        if($status === self::STATUS_NO_CHANGES_FOUND) {
            #if(empty($folders)) {
            #    // the client did not sent a list of folders to monitor
            #    // let's monitor all root folders of all applications
            #    foreach($this->_backend->getSupportedClasses() as $className) {
            #                            
            #        $folderClass = $this->_backend->factory($className);
            #        foreach($folderClass->getFolders() as $folder) {
            #            $folder = array(
            #                'serverEntryId' => $folder['serverId'],
            #                'folderType'    => $className
            #            );
            #            $folders[] = $folder;                
            #        }
            #    }
            #}
                
            #$syncStateClass = new ActiveSync_SyncState();
            $folderWithChanges = array();
            
            do {
                foreach($folders as $folder) {
                    $dataController = ActiveSync_Controller::dataFactory($folder['folderType'], $this->_syncTimeStamp);
                    #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . print_r($folder, true));
                    try {
                        $syncState = $controller->getSyncState($this->_device, $folder['folderType'] . '-' . $folder['serverEntryId']);
                        $count = $dataController->getItemEstimate($syncState->lastsync);
                        
                        // get the count of deleted entries or entries available becuase of change permissions
                        $contentStateBackend  = new ActiveSync_Backend_ContentState();
                        $allClientEntries = $contentStateBackend->getClientState($this->_device, $folder['folderType']);
                        $allServerEntries = $dataController->getServerEntries();

                        $count += abs(count($allClientEntries) - count($allServerEntries));
                        
                        if($count > 0) {
                            $folderWithChanges[] = array(
                                'serverEntryId' => $folder['serverEntryId'],
                                'folderType'    => $folder['folderType']
                            );
                            $status = self::STATUS_CHANGES_FOUND;
                        }
                    } catch (ActiveSync_Exception_SyncStateNotFound $e) {
                        // folder got never synchronized to client
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $e->getMessage());
                    }
                }
                
                if($status === self::STATUS_CHANGES_FOUND) {
                    break;
                }
                
                sleep(self::PING_TIMEOUT);
                $secondsLeft = $intervalEnd - mktime();
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_device->deviceid . " seconds left: " . $secondsLeft);
            } while($secondsLeft > 0);
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_device->deviceid . " Lifetime: $lifeTime SecondsLeft: $secondsLeft  Status: $status)");
        
        $ping = $this->_outputDom->documentElement;
        $ping->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Status', $status));
        if($status === self::STATUS_CHANGES_FOUND) {
            $folders = $ping->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Folders'));
            foreach($folderWithChanges as $changedFolder) {
                $folder = $folders->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Folder', $changedFolder['serverEntryId']));
                #$folder->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Id', $changedFolder['serverEntryId']));
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_deviceId . " changes in folder: " . $changedFolder['serverEntryId']);
            }
        }
                
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $this->_outputDom->saveXML());
        
        #$outputStream = fopen("php://temp", 'r+');
        #$encoder = new Wbxml_Encoder($outputStream, 'UTF-8', 3);
        #$encoder->encode($this->_outputDom);
        
        #return $outputStream;
    }    
}