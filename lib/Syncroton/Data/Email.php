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

class Syncroton_Data_Email extends Syncroton_Data_AData
{
    /**
     * used by unit tests only to simulated added folders
     */
    public static $eentries = array(
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
    
    public function updateEntry($_folderId, $_serverId, Syncroton_Model_IEntry $_entry)
    {
        // not used by email
    }

    protected function _initData()
    {
        /**
        * used by unit tests only to simulated added folders
        */
        Syncroton_Data_AData::$folders[get_class($this)] = array(
                'emailInboxFolderId' => array(
                    'folderId'    => 'emailInboxFolderId',
                    'parentId'    => null,
                    'displayName' => 'Inbox',
                    'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_INBOX
                ),
                'emailSentFolderId' => array(
                    'folderId'    => 'emailSentFolderId',
                    'parentId'    => null,
                    'displayName' => 'Sent',
                    'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_SENTMAIL
                )
        );
        
        /**
         * used by unit tests only to simulated added folders
         */
        Syncroton_Data_AData::$entries[get_class($this)] = array(
        		'emailInboxFolderId' => array(
                    'email1' => array(
                    	'FirstName' => 'Lars', 
                    	'LastName'  => 'Kneschke'
                	),
                    'email2' => array(
                    	'FirstName' => 'Cornelius', 
                    	'LastName'  => 'Weiß'
                    )
                )
        );
    }
}

