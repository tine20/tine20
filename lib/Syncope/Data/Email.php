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

class Syncope_Data_Email extends Syncope_Data_AData
{
    /**
     * used by unit tests only to simulated added folders
     */
    public static $folders = array(
        'emailInboxFolderId' => array(
            'folderId'    => 'emailInboxFolderId',
            'parentId'    => null,
            'displayName' => 'Inbox',
            'type'        => Syncope_Command_FolderSync::FOLDERTYPE_INBOX
        ),
        'emailSentFolderId' => array(
            'folderId'    => 'emailSentFolderId',
            'parentId'    => null,
            'displayName' => 'Sent',
            'type'        => Syncope_Command_FolderSync::FOLDERTYPE_SENTMAIL
        )
    );
    
    /**
     * used by unit tests only to simulated added folders
     */
    public static $entries = array(
        'email1' => array(
        	'FirstName' => 'Lars', 
        	'LastName'  => 'Kneschke'
    	),
        'email2' => array(
        	'FirstName' => 'Cornelius', 
        	'LastName'  => 'Weiß'
        )
    );
    
    /**
     * append email data to xml element
     *
     * @param DOMElement  $_domParrent   the parrent xml node
     * @param string      $_folderId  the local folder id
     */
    public function appendFileReference(DOMElement $_domParrent, $_fileReference)
    {
        list($messageId, $partId) = explode('-', $_fileReference, 2);
    
        $_domParrent->appendChild(new DOMElement('ContentType', 'text/plain', 'uri:AirSyncBase'));
        $_domParrent->appendChild(new DOMElement('Data', base64_encode('TestData'), 'uri:ItemOperations'));
    }
    
    public function appendXML(DOMElement $_domParrent, $_collectionData, $_serverId)
    {
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Email', 'uri:Email');
        
        $node = $_domParrent->appendChild(new DOMElement('Subject', 'Subject of the email', 'uri:Email'));
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
        return self::$folders;
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
        // not used by email
    }
}

