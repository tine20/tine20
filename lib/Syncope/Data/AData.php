<?php
/**
 * Syncope
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

abstract class Syncope_Data_AData implements Syncope_Data_IData
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
    
    public function __construct(Syncope_Model_IDevice $_device, DateTime $_timeStamp)
    {
        $this->_device = $_device;
        $this->_timestamp = $_timeStamp;
        
        $this->_initData();
    }
    
    public function createFolder($_parentId, $_displayName, $_type)
    {
        $id = sha1(mt_rand(). microtime());
    
        Syncope_Data_AData::$folders[get_class($this)][$id] = array(
            'folderId'    => $id,
            'parentId'    => $_parentId,
            'displayName' => $_displayName,
            'type'        => $_type
        );
    
        return Syncope_Data_AData::$folders[get_class($this)][$id];
    }
    
    public function deleteEntry($_folderId, $_serverId, $_collectionData)
    {
        $folderId = $_folderId instanceof Syncope_Model_IFolder ? $_folderId->folderid : $_folderId;
        
        unset(Syncope_Data_AData::$entries[get_class($this)][$folderId][$_serverId]);
    }
    
    public function deleteFolder($_folderId)
    {
        $folderId = $_folderId instanceof Syncope_Model_IFolder ? $_folderId->folderid : $_folderId;
    
        unset(Syncope_Data_AData::$folders[get_class($this)][$folderId]);
        unset(Syncope_Data_AData::$entries[get_class($this)][$folderId]);
    }
    
    public function getAllFolders()
    {
        return Syncope_Data_AData::$folders[get_class($this)];
    }
    
    public function getChangedEntries($_folderId, DateTime $_startTimeStamp, DateTime $_endTimeStamp = NULL)
    {
        if (!isset(Syncope_Data_AData::$changedEntries[get_class($this)])) {
            return array();
        } else {
            return Syncope_Data_AData::$changedEntries[get_class($this)];
        }
    }
    
    /**
     * @param  Syncope_Model_IFolder|string  $_folderId
     * @param  string                        $_filter
     * @return array
     */
    public function getServerEntries($_folderId, $_filter)
    {
        $folderId = $_folderId instanceof Syncope_Model_IFolder ? $_folderId->id : $_folderId;
    
        return array_keys(Syncope_Data_AData::$entries[get_class($this)][$folderId]);
    }
    
    public function hasChanges(Syncope_Backend_IContent $contentBackend, Syncope_Model_IFolder $folder, Syncope_Model_ISyncState $syncState)
    {
        return true;
    }
    
    public function moveItem($_srcFolderId, $_serverId, $_dstFolderId)
    {
        Syncope_Data_AData::$entries[get_class($this)][$_dstFolderId][$_serverId] = Syncope_Data_AData::$entries[get_class($this)][$_srcFolderId][$_serverId];
        unset(Syncope_Data_AData::$entries[get_class($this)][$_srcFolderId][$_serverId]);
        
        return $_serverId;
    }
    
    public function updateEntry($_folderId, $_serverId, SimpleXMLElement $_entry)
    {
    }
    
    abstract protected function _initData();
}

