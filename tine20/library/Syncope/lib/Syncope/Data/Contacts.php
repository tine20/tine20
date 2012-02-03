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

class Syncope_Data_Contacts extends Syncope_Data_AData
{
    public function appendXML(DOMElement $_domParrent, $_collectionData, $_serverId)
    {
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Contacts', 'uri:Contacts');
        
        foreach (Syncope_Data_AData::$entries[get_class($this)][$_collectionData["collectionId"]][$_serverId] as $key => $value) {
            // create a new DOMElement ...
            $node = new DOMElement($key, null, 'uri:Contacts');
            
            // ... append it to parent node aka append it to the document ...
            $_domParrent->appendChild($node);
            
            // ... and now add the content (DomText takes care of special chars)
            $node->appendChild(new DOMText($value));
        }
        
    }
    
    public function createEntry($_folderId, SimpleXMLElement $_entry)
    {
        $xmlData = $_entry->children('uri:Contacts');
        
        $id = sha1(mt_rand(). microtime());
        
        Syncope_Data_AData::$entries[get_class($this)][$_folderId][$id] = array(
        	'FirstName' => (string)$xmlData->FirstName, 
        	'LastName'  => (string)$xmlData->LastName
        );
        
        return $id;
    }
    
    public function updateEntry($_folderId, $_serverId, SimpleXMLElement $_entry)
    {
        $xmlData = $_entry->children('uri:Contacts');
        
        Syncope_Data_AData::$entries[get_class($this)][$_folderId][$_serverId] = array(
        	'FirstName' => (string)$xmlData->FirstName, 
        	'LastName'  => (string)$xmlData->LastName
        );
    }        
    
    protected function _initData()
    {
        /**
        * used by unit tests only to simulated added folders
        */
        if (!isset(Syncope_Data_AData::$folders[get_class($this)])) {
            Syncope_Data_AData::$folders[get_class($this)] = array(
            	'addressbookFolderId' => array(
                    'folderId'    => 'addressbookFolderId',
                    'parentId'    => null,
                    'displayName' => 'Default Contacts Folder',
                    'type'        => Syncope_Command_FolderSync::FOLDERTYPE_CONTACT
                ),
            	'anotherAddressbookFolderId' => array(
                    'folderId'    => 'anotherAddressbookFolderId',
                    'parentId'    => null,
                    'displayName' => 'Another Contacts Folder',
                    'type'        => Syncope_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED
                )
            );
        }
        
        /**
         * used by unit tests only to simulated added folders
         */
        if (!isset(Syncope_Data_AData::$entries[get_class($this)])) {
            Syncope_Data_AData::$entries[get_class($this)] = array(
            		'addressbookFolderId' => array(
                        'contact1' => array(
                            	'FirstName' => 'Lars', 
                            	'LastName'  => 'Kneschke'
            	        ),
                        'contact2' => array(
                        	'FirstName' => 'Cornelius', 
                        	'LastName'  => 'Weiß'
                	    )
            	    )
            );
        }
    }
}

