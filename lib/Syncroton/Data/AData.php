<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 */

abstract class Syncroton_Data_AData implements Syncroton_Data_IData
{
    /**
     * used by unit tests only to simulated added folders
     */
    public static $folders = array();
    
    /**
     * used by unit tests only to simulated added folders
     */
    public static $entries = array();
    
    /**
    * used by unit tests only to simulated added folders
    */
    public static $changedEntries = array();
    
    public function __construct(Syncroton_Model_IDevice $_device, DateTime $_timeStamp)
    {
        $this->_device = $_device;
        $this->_timestamp = $_timeStamp;
        
        $this->_initData();
    }
    
    public function createFolder($_parentId, $_displayName, $_type)
    {
        $id = sha1(mt_rand(). microtime());
    
        Syncroton_Data_AData::$folders[get_class($this)][$id] = array(
            'folderId'    => $id,
            'parentId'    => $_parentId,
            'displayName' => $_displayName,
            'type'        => $_type
        );
    
        return Syncroton_Data_AData::$folders[get_class($this)][$id];
    }
    
    public function deleteEntry($_folderId, $_serverId, $_collectionData)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->folderid : $_folderId;
        
        unset(Syncroton_Data_AData::$entries[get_class($this)][$folderId][$_serverId]);
    }
    
    public function deleteFolder($_folderId)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->folderid : $_folderId;
    
        unset(Syncroton_Data_AData::$folders[get_class($this)][$folderId]);
        unset(Syncroton_Data_AData::$entries[get_class($this)][$folderId]);
    }
    
    public function getAllFolders()
    {
        return Syncroton_Data_AData::$folders[get_class($this)];
    }
    
    public function getChangedEntries($_folderId, DateTime $_startTimeStamp, DateTime $_endTimeStamp = NULL)
    {
        if (!isset(Syncroton_Data_AData::$changedEntries[get_class($this)])) {
            return array();
        } else {
            return Syncroton_Data_AData::$changedEntries[get_class($this)];
        }
    }
    
    /**
     * @param  Syncroton_Model_IFolder|string  $_folderId
     * @param  string                        $_filter
     * @return array
     */
    public function getServerEntries($_folderId, $_filter)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->id : $_folderId;
    
        return array_keys(Syncroton_Data_AData::$entries[get_class($this)][$folderId]);
    }
    
    public function getCountOfChanges(Syncroton_Backend_IContent $contentBackend, Syncroton_Model_IFolder $folder, Syncroton_Model_ISyncState $syncState)
    {
        $allClientEntries = $contentBackend->getFolderState($this->_device, $folder);
        $allServerEntries = $this->getServerEntries($folder->folderid, $folder->lastfiltertype);
        
        $addedEntries       = array_diff($allServerEntries, $allClientEntries);
        $deletedEntries     = array_diff($allClientEntries, $allServerEntries);
        $changedEntries     = $this->getChangedEntries($folder->folderid, $syncState->lastsync);
        
        return count($addedEntries) + count($deletedEntries) + count($changedEntries);
    }
    
    public function moveItem($_srcFolderId, $_serverId, $_dstFolderId)
    {
        Syncroton_Data_AData::$entries[get_class($this)][$_dstFolderId][$_serverId] = Syncroton_Data_AData::$entries[get_class($this)][$_srcFolderId][$_serverId];
        unset(Syncroton_Data_AData::$entries[get_class($this)][$_srcFolderId][$_serverId]);
        
        return $_serverId;
    }
    
    public function updateEntry($_folderId, $_serverId, SimpleXMLElement $_entry)
    {
    }
    
    abstract protected function _initData();
}

