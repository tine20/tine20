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

class Syncroton_Data_Contacts extends Syncroton_Data_AData
{
    const MODEL = 'Syncroton_Model_Contact';
    
    protected function _initData()
    {
        /**
        * used by unit tests only to simulated added folders
        */
        if (!isset(Syncroton_Data_AData::$folders[get_class($this)])) {
            Syncroton_Data_AData::$folders[get_class($this)] = array(
                'addressbookFolderId' => array(
                    'folderId'    => 'addressbookFolderId',
                    'parentId'    => null,
                    'displayName' => 'Default Contacts Folder',
                    'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT
                ),
                'anotherAddressbookFolderId' => array(
                    'folderId'    => 'anotherAddressbookFolderId',
                    'parentId'    => null,
                    'displayName' => 'Another Contacts Folder',
                    'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED
                )
            );
        }
        
        /**
         * used by unit tests only to simulated added folders
         */
        if (!isset(Syncroton_Data_AData::$entries[get_class($this)])) {
            Syncroton_Data_AData::$entries[get_class($this)] = array(
                'addressbookFolderId' => array(
                    'contact1' => new Syncroton_Model_Contact(array(
                        'FirstName' => 'Lars', 
                        'LastName'  => 'Kneschke'
                    )),
                    'contact2' => new Syncroton_Model_Contact(array(
                        'FirstName' => 'Cornelius', 
                        'LastName'  => 'Weiß'
                    )),
                    'contact3' => new Syncroton_Model_Contact(array(
                        'FirstName' => 'Lars', 
                        'LastName'  => 'Kneschke'
                    )),
                    'contact4' => new Syncroton_Model_Contact(array(
                        'FirstName' => 'Cornelius', 
                        'LastName'  => 'Weiß'
                    )),
                    'contact5' => new Syncroton_Model_Contact(array(
                        'FirstName' => 'Lars', 
                        'LastName'  => 'Kneschke'
                    )),
                    'contact6' => new Syncroton_Model_Contact(array(
                        'FirstName' => 'Cornelius', 
                        'LastName'  => 'Weiß'
                    )),
                    'contact7' => new Syncroton_Model_Contact(array(
                        'FirstName' => 'Lars', 
                        'LastName'  => 'Kneschke'
                    )),
                    'contact8' => new Syncroton_Model_Contact(array(
                        'FirstName' => 'Cornelius', 
                        'LastName'  => 'Weiß'
                    )),
                    'contact9' => new Syncroton_Model_Contact(array(
                        'FirstName' => 'Lars', 
                        'LastName'  => 'Kneschke'
                    )),
                    'contact10' => new Syncroton_Model_Contact(array(
                        'FirstName' => 'Cornelius', 
                        'LastName'  => 'Weiß'
                    ))
                )
            );
        }
    }
}

