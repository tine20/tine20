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

class Syncope_Data_Contacts extends Syncope_Data_AData
{
    protected $_specialFolderName = 'addressbook-root';
    
    /**
     * used by unit tests only to simulated added folders
     */
    public static $folders = array(
    	'addressbookFolderId' => array(
            'folderId'    => 'addressbookFolderId',
            'parentId'    => null,
            'displayName' => 'Default Contacts Folder',
            'type'        => Syncope_Command_FolderSync::FOLDERTYPE_CONTACT
        )
    );
    
    /**
     * used by unit tests only to simulated added folders
     */
    public static $entries = array(
        'contact1' => array(
        	'FirstName' => 'Lars', 
        	'LastName'  => 'Kneschke'
    	),
        'contact2' => array(
        	'FirstName' => 'Cornelius', 
        	'LastName'  => 'Weiß'
        )
    );
    
    /**
     * used by unit tests only to simulated added folders
     */
    public static $changedEntries = array(
    );
    
    public function __construct(Syncope_Model_IDevice $_device, DateTime $_timeStamp)
    {
        $this->_device = $_device;
        $this->_timestamp = $_timeStamp;
    }
    
    public function createEntry($_folderId, SimpleXMLElement $_entry)
    {
        $xmlData = $_entry->children('uri:Contacts');
        
        $id = sha1(mt_rand(). microtime());
        
        self::$entries[$id] = array(
        	'FirstName' => (string)$xmlData->FirstName, 
        	'LastName'  => (string)$xmlData->LastName
        );
        
        return $id;
    }
    
    public function deleteEntry($_folderId, $_serverId)
    {
        unset(self::$entries[$_serverId]);
    }
    
    public function updateEntry($_folderId, $_serverId, SimpleXMLElement $_entry)
    {
        $xmlData = $_entry->children('uri:Contacts');
        
        self::$entries[$_serverId] = array(
        	'FirstName' => (string)$xmlData->FirstName, 
        	'LastName'  => (string)$xmlData->LastName
        );
    }
    
    public function appendXML(DOMElement $_domParrent, $_collectionData, $_serverId)
    {
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Contacts', 'uri:Contacts');
        
        foreach (self::$entries[$_serverId] as $key => $value) {
            // create a new DOMElement ...
            $node = new DOMElement($key, null, 'uri:Contacts');
            
            // ... append it to parent node aka append it to the document ...
            $_domParrent->appendChild($node);
            
            // ... and now add the content (DomText takes care of special chars)
            $node->appendChild(new DOMText($value));
        }
        
    }
    
    public function getAllFolders()
    {
        if (!$this->_supportsMultipleFolders()) {
            return array(
                $this->_specialFolderName => array(
                    'folderId'    => $this->_specialFolderName,
                    'parentId'    => null,
                    'displayName' => 'Default Contacts Folder',
                    'type'        => Syncope_Command_FolderSync::FOLDERTYPE_CONTACT
                )
            );
        }
        
        return self::$folders;
    }
    
    public function createFolder($_parentId, $_displayName, $_type)
    {
        $id = sha1(mt_rand(). microtime());
        
        self::$folders[$id] = array(
            'folderId'    => $id,
            'parentId'    => $_parentId,
            'displayName' => $_displayName,
            'type'        => $_type
        );
        
        return self::$folders[$id];
    }
    
    public function deleteFolder($_folderId)
    {
        $folderId = $_folderId instanceof Syncope_Model_IFolder ? $_folderId->folderid : $_folderId;
        
        unset(self::$folders[$folderId]);
    }
    
    /**
     * @param  Syncope_Model_IFolder|string  $_folderId
     * @param  string                        $_filter
     * @return array
     */
    public function getServerEntries($_folderId, $_filter)
    {
        $folderId = $_folderId instanceof Syncope_Model_IFolder ? $_folderId->id : $_folderId;
        
        return array_keys(self::$entries);
    }
    
    public function getChangedEntries($_folderId, DateTime $_startTimeStamp, DateTime $_endTimeStamp = NULL)
    {
        return self::$changedEntries;
    }
    
    public function hasChanges(Syncope_Backend_IContent $contentBackend, Syncope_Model_IFolder $folder, Syncope_Model_ISyncState $syncState)
    {
        return true;
    }
    
    #public function getMultiple()
    #{
    #    return array();
    #}
    
    protected function _supportsMultipleFolders()
    {
        if (in_array($this->_device->devicetype, array(Syncope_Model_Device::TYPE_ANDROID, Syncope_Model_Device::TYPE_WEBOS))) {
            return false;
        }
        
        return true;
    }
    
}

