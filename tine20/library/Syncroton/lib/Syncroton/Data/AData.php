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
        
        $this->_db          = Syncroton_Registry::getDatabase();
        $this->_tablePrefix = 'Syncroton_';
        
        $this->_initData();
    }
    
    public function createFolder(Syncroton_Model_IFolder $folder)
    {
        $folder->id = sha1(mt_rand(). microtime());
        
        // normaly generated on server backend
        $folder->serverId = sha1(mt_rand(). microtime());
    
        Syncroton_Data_AData::$folders[get_class($this)][$folder->serverId] = $folder;
    
        return Syncroton_Data_AData::$folders[get_class($this)][$folder->serverId];
    }
    
    public function createEntry($_folderId, Syncroton_Model_IEntry $_entry)
    {
        $id = sha1(mt_rand(). microtime());
    
        #Syncroton_Data_AData::$entries[get_class($this)][$_folderId][$id] = $_entry;
        
        $this->_db->insert($this->_tablePrefix . 'data', array(
            'id'        => $id,
            'type'      => get_class($this),
            'folder_id' => $_folderId,
            'data'      => serialize($_entry)
        ));
    
        return $id;
    }
    
    public function deleteEntry($_folderId, $_serverId, $_collectionData)
    {
        #$folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->serverId : $_folderId;
        
        $result = $this->_db->delete($this->_tablePrefix . 'data', array('id = ?' => $_serverId));
        
        return (bool) $result;
        
        #unset(Syncroton_Data_AData::$entries[get_class($this)][$folderId][$_serverId]);
    }
    
    public function deleteFolder($_folderId)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->serverId : $_folderId;
    
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
    
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'data', array('id'))
            ->where('folder_id = ?', $_folderId);
        
        $ids = array();
        
        $stmt = $this->_db->query($select);
        while ($id = $stmt->fetchColumn()) {
            $ids[] = $id;
        }
        
        return $ids;
    }
    
    public function getCountOfChanges(Syncroton_Backend_IContent $contentBackend, Syncroton_Model_IFolder $folder, Syncroton_Model_ISyncState $syncState)
    {
        $allClientEntries = $contentBackend->getFolderState($this->_device, $folder);
        $allServerEntries = $this->getServerEntries($folder->serverId, $folder->lastfiltertype);
        
        $addedEntries       = array_diff($allServerEntries, $allClientEntries);
        $deletedEntries     = array_diff($allClientEntries, $allServerEntries);
        $changedEntries     = $this->getChangedEntries($folder->serverId, $syncState->lastsync);
        
        return count($addedEntries) + count($deletedEntries) + count($changedEntries);
    }
    
    public function getFileReference($fileReference)
    {
        throw new Syncroton_Exception_NotFound('filereference not found');
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::getEntry()
     */
    public function getEntry(Syncroton_Model_SyncCollection $collection, $serverId)
    {
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'data', array('data'))
            ->where('id = ?', $serverId);
        
        $stmt = $this->_db->query($select);
        $entry = $stmt->fetchColumn();

        if ($entry === false) {
            throw new Syncroton_Exception_NotFound("entry $serverId not found in folder {$collection->collectionId}");
        }
        
        return unserialize($entry);
    }
    
    public function moveItem($_srcFolderId, $_serverId, $_dstFolderId)
    {
        $this->_db->update($this->_tablePrefix . 'data', array(
            'folder_id' => $_dstFolderId,
        ), array(
            'id = ?' => $_serverId
        ));
        
        return $_serverId;
    }
    
    public function updateEntry($_folderId, $_serverId, Syncroton_Model_IEntry $_entry)
    {
        $this->_db->update($this->_tablePrefix . 'data', array(
            'type'      => get_class($this),
            'folder_id' => $_folderId,
            'data'      => serialize($_entry)
        ), array(
            'id = ?' => $_serverId
        ));
    }
    
    public function updateFolder(Syncroton_Model_IFolder $folder)
    {
        Syncroton_Data_AData::$folders[get_class($this)][$folder->serverId] = $folder;
    }
    
    
    abstract protected function _initData();
}

