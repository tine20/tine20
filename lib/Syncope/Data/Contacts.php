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

class Syncope_Data_Contacts implements Syncope_Data_IData
{
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
    
    public function appendXML(DOMElement $_domParrent, $_collectionData, $_serverId)
    {
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Contacts', 'uri:Contacts');
        
    }
    
    public function getAllFolders()
    {
        return self::$folders;
    }
    
    public function getServerEntries()
    {
        return array('serverContactId1', 'serverContactId2');
    }
    
    public function getChanged()
    {
        return array();
    }
    
    public function getMultiple()
    {
        return array();
    }
}

