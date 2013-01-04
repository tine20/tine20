<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Ping command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_Ping extends Syncroton_Command_Wbxml 
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
    
    /**
     * @var Syncroton_Backend_StandAlone_Abstract
     */
    protected $_dataBackend;

    protected $_defaultNameSpace = 'uri:Ping';
    protected $_documentElement  = 'Ping';
    
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
        if($this->_requestBody instanceof DOMDocument) {
            $xml = simplexml_import_dom($this->_requestBody);
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
                        
                        $folders[$folder->id] = $folder;
                    } catch (Syncroton_Exception_NotFound $senf) {
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " " . $senf->getMessage());
                        $status = self::STATUS_FOLDER_NOT_FOUND;
                        break;
                    }
                }
                $this->_device->pingfolder = serialize(array_keys($folders));
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
        
        if ($status === self::STATUS_NO_CHANGES_FOUND) {

            $folderWithChanges = array();
            
            do {
                // take a break to save battery lifetime
                sleep(Syncroton_Registry::getPingTimeout());
                
                $now = new DateTime('now', new DateTimeZone('utc'));
                
                foreach ((array) $folders as $folderId) {
                    try {
                        $folder         = $this->_folderBackend->get($folderId);
                        $dataController = Syncroton_Data_Factory::factory($folder->class, $this->_device, $this->_syncTimeStamp);
                    } catch (Syncroton_Exception_NotFound $e) {
                        if ($this->_logger instanceof Zend_Log)
                            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " " . $e->getMessage());
                        $status = self::STATUS_FOLDER_NOT_FOUND;
                        break;
                    } catch (Exception $e) {
                        if ($this->_logger instanceof Zend_Log)
                            $this->_logger->err(__METHOD__ . '::' . __LINE__ . " " . $e->getMessage());
                        // do nothing, maybe temporal issue, should we stop?
                        continue;
                    }

                    try {
                        $syncState = $this->_syncStateBackend->getSyncState($this->_device, $folder);
                        
                        // another process synchronized data of this folder already. let's skip it
                        if ($syncState->lastsync > $this->_syncTimeStamp) {
                            continue;
                        }
                        
                        // safe battery time by skipping folders which got synchronied less than Syncroton_Registry::getQuietTime() seconds ago
                        if (($now->getTimestamp() - $syncState->lastsync->getTimestamp()) < Syncroton_Registry::getQuietTime()) {
                            continue;
                        }
                        
                        $foundChanges = !!$dataController->getCountOfChanges($this->_contentStateBackend, $folder, $syncState);
                        
                    } catch (Syncroton_Exception_NotFound $e) {
                        // folder got never synchronized to client
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " " . $e->getMessage());
                        if ($this->_logger instanceof Zend_Log) 
                            $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' syncstate not found. enforce sync for folder: ' . $folder->serverId);
                        
                        $foundChanges = true;
                    }
                    
                    if ($foundChanges == true) {
                        $this->_foldersWithChanges[] = $folder;
                        $status = self::STATUS_CHANGES_FOUND;
                    }
                }
                
                if ($status != self::STATUS_NO_CHANGES_FOUND) {
                    break;
                }
                
                $secondsLeft = $intervalEnd - time();
                
                if ($this->_logger instanceof Zend_Log)
                    $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_device->deviceid . " seconds left: " . $secondsLeft);
            
            // See: http://www.tine20.org/forum/viewtopic.php?f=12&t=12146
            //
            // break if there are less than PingTimeout + 10 seconds left for the next loop
            // otherwise the response will be returned after the client has finished his Ping
            // request already maybe
            } while ($secondsLeft > (Syncroton_Registry::getPingTimeout() + 10));
        }
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->info(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_device->deviceid . " Lifetime: $lifeTime SecondsLeft: $secondsLeft Status: $status)");
        
        $ping = $this->_outputDom->documentElement;
        $ping->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Status', $status));
        if($status === self::STATUS_CHANGES_FOUND) {
            $folders = $ping->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Folders'));
            
            foreach($this->_foldersWithChanges as $changedFolder) {
                $folder = $folders->appendChild($this->_outputDom->createElementNS('uri:Ping', 'Folder', $changedFolder->serverId));
                if ($this->_logger instanceof Zend_Log) 
                    $this->_logger->info(__METHOD__ . '::' . __LINE__ . " DeviceId: " . $this->_device->deviceid . " changes in folder: " . $changedFolder->serverId);
            }
        }
    }
        
    /**
     * generate ping command response
     *
     */
    public function getResponse()
    {
        return $this->_outputDom;
    }
}
