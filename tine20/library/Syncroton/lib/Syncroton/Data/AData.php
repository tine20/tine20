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
    const LONGID_DELIMITER = "\xe2\x87\x94"; # UTF8 ⇔
    
    /**
     * used by unit tests only to simulated added folders
     */
    public static $changedEntries = array();
    
    public function __construct(Syncroton_Model_IDevice $_device, DateTime $_timeStamp)
    {
        $this->_device      = $_device;
        $this->_timestamp   = $_timeStamp;
        $this->_db          = Syncroton_Registry::getDatabase();
        $this->_tablePrefix = 'Syncroton_';
        $this->_ownerId     = '1234';
    }
    
    public function getFolder($id)
    {
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'data_folder')
            ->where('owner_id = ?', $this->_ownerId)
            ->where('id = ?', $id);
        
        $stmt   = $this->_db->query($select);
        $folder = $stmt->fetch();
        $stmt = null; # see https://bugs.php.net/bug.php?id=44081
        
        if ($folder === false) {
            throw new Syncroton_Exception_NotFound("folder $id not found");
        }
        
        return new Syncroton_Model_Folder(array(
            'serverId'    => $folder['id'],
            'displayName' => $folder['name'],
            'type'        => $folder['type'],
            'parentId'    => !empty($folder['parent_id']) ? $folder['parent_id'] : null
        ));
    }
    
    public function createFolder(Syncroton_Model_IFolder $folder)
    {
        if (!in_array($folder->type, $this->_supportedFolderTypes)) {
            throw new Syncroton_Exception_UnexpectedValue();
        }
        
        $id = !empty($folder->serverId) ? $folder->serverId : sha1(mt_rand(). microtime());
        
        $this->_db->insert($this->_tablePrefix . 'data_folder', array(
            'id'        => $id,
            'type'      => $folder->type,
            'name'      => $folder->displayName,
            'owner_id'  => $this->_ownerId,
            'parent_id' => $folder->parentId
        ));
        
        return $this->getFolder($id);
    }
    
    public function createEntry($_folderId, Syncroton_Model_IEntry $_entry)
    {
        $id = sha1(mt_rand(). microtime());
    
        $this->_db->insert($this->_tablePrefix . 'data', array(
            'id'        => $id,
            'class'     => get_class($_entry),
            'folder_id' => $_folderId,
            'data'      => serialize($_entry)
        ));
    
        return $id;
    }
    
    public function deleteEntry($_folderId, $_serverId, $_collectionData)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->serverId : $_folderId;
        
        $result = $this->_db->delete($this->_tablePrefix . 'data', array('id = ?' => $_serverId));
        
        return (bool) $result;
    }
    
    public function deleteFolder($_folderId)
    {
        $folderId = $_folderId instanceof Syncroton_Model_IFolder ? $_folderId->serverId : $_folderId;
        
        $result = $this->_db->delete($this->_tablePrefix . 'data', array('folder_id = ?' => $folderId));
        $result = $this->_db->delete($this->_tablePrefix . 'data_folder', array('id = ?' => $folderId));
        
        return (bool) $result;
    }
    
    public function getAllFolders()
    {
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'data_folder')
            ->where('type IN (?)', $this->_supportedFolderTypes)
            ->where('owner_id = ?', $this->_ownerId);
        
        $stmt    = $this->_db->query($select);
        $folders = $stmt->fetchAll();
        $stmt = null; # see https://bugs.php.net/bug.php?id=44081
        
        $result = array();
        
        foreach ((array) $folders as $folder) {
            $result[$folder['id']] =  new Syncroton_Model_Folder(array(
                'serverId'    => $folder['id'],
                'displayName' => $folder['name'],
                'type'        => $folder['type'],
                'parentId'    => $folder['parent_id']
            ));
        }
        
        return $result;
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
            'folder_id' => $_folderId,
            'data'      => serialize($_entry)
        ), array(
            'id = ?' => $_serverId
        ));
    }
    
    public function updateFolder(Syncroton_Model_IFolder $folder)
    {
        $this->_db->update($this->_tablePrefix . 'data_folder', array(
            'name'      => $folder->displayName,
            'parent_id' => $folder->parentId
        ), array(
            'id = ?' => $folder->serverId
        ));
    }
}

