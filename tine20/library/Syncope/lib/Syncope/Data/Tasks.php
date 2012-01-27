<?php

/**
 * Syncope
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL),
 *              Version 1, the distribution of the Tine 2.0 Syncope module in or to the
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 */

class Syncope_Data_Tasks extends Syncope_Data_AData
{
    public function appendXML(DOMElement $_domParrent, $_collectionData, $_serverId)
    {
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Tasks', 'uri:Tasks');
    }
    
    public function createEntry($_folderId, SimpleXMLElement $_entry)
    {
        #$xmlData = $_entry->children('uri:Contacts');
    
        $id = sha1(mt_rand(). microtime());
    
        self::$entries[$id] = array(
        #    	'FirstName' => (string)$xmlData->FirstName, 
        #    	'LastName'  => (string)$xmlData->LastName
        );
    
        return $id;
    }
    
    public function deleteEntry($_folderId, $_serverId)
    {
        unset(self::$entries[$_serverId]);
    }
    
    public function getAllFolders()
    {
        return array(
            'tasksFolderId' => array(
                'folderId'    => 'tasksFolderId',
                'parentId'    => null,
                'displayName' => 'Default Tasks Folder',
                'type'        => Syncope_Command_FolderSync::FOLDERTYPE_TASK
            )
        );
    }
    
    public function getChangedEntries($_folderId, DateTime $_startTimeStamp, DateTime $_endTimeStamp = NULL)
    {
        return self::$changedEntries;
    }
    
    public function hasChanges(Syncope_Backend_IContent $contentBackend, Syncope_Model_IFolder $folder, Syncope_Model_ISyncState $syncState)
    {
        return true;
    }
    
    public function updateEntry($_folderId, $_serverId, SimpleXMLElement $_entry)
    {
    }
}

