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

class Syncope_Data_Calendar extends Syncope_Data_AData
{
    public function appendXML(DOMElement $_domParrent, $_collectionData, $_serverId)
    {
        // @todo is this correct?
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Calendar', 'uri:Calendar');
    
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
    
    protected function _initData()
    {
        /**
        * used by unit tests only to simulated added folders
        */
        Syncope_Data_AData::$folders[get_class($this)] = array(
            	'calendarFolderId' => array(
                    'folderId'    => 'calendarFolderId',
                    'parentId'    => null,
                    'displayName' => 'Default Contacts Folder',
                    'type'        => Syncope_Command_FolderSync::FOLDERTYPE_CALENDAR
                )
        );
        
        /**
         * used by unit tests only to simulated added folders
         */
        Syncope_Data_AData::$entries[get_class($this)] = array(
        		'calendarFolderId' => array(
        	    )
        );
    }
}

