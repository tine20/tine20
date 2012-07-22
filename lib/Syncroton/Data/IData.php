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

interface Syncroton_Data_IData
{
    //public function appendXML(DOMElement $_domParrent, $_collectionData, $_serverId);
    
    public function createEntry($_folderId, Syncroton_Model_IEntry $_entry);
    
    public function deleteEntry($_folderId, $_serverId, $_collectionData);
    
    public function getAllFolders();
    
    public function getChangedEntries($_folderId, DateTime $_startTimeStamp, DateTime $_endTimeStamp = NULL);
    
    public function getCountOfChanges(Syncroton_Backend_IContent $contentBackend, Syncroton_Model_IFolder $folder, Syncroton_Model_ISyncState $syncState);
    
    /**
     * 
     * @param Syncroton_Model_SyncCollection $collection
     * @param string $serverId
     * @return Syncroton_Model_IEntry
     */
    public function getEntry(Syncroton_Model_SyncCollection $collection, $serverId);
    
    public function moveItem($_srcFolderId, $_serverId, $_dstFolderId);
    
    public function updateEntry($_folderId, $_serverId, Syncroton_Model_IEntry $_entry);
}

