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

class Syncroton_Data_Contacts extends Syncroton_Data_AData implements Syncroton_Data_IDataSearch
{
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataSearch::getSearchEntry()
     */
    public function getSearchEntry($longId, $options)
    {
        list($collectionId, $serverId) = explode('-', $longId, 2);
        
        $contact = $this->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $collectionId)), $serverId);
        
        return new Syncroton_Model_GAL(array(
            'firstName' => $contact->firstName,
            'lastName'  => $contact->lastName
        ));
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataSearch::search()
     */
    public function search($query, $options)
    {
        $found = array();
        
        $serverIds = $this->getServerEntries('addressbookFolderId', Syncroton_Command_Sync::FILTER_NOTHING);
        
        foreach ($serverIds as $serverId) {
            $contact = $this->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => 'addressbookFolderId')), $serverId);
            
            if ($contact->firstName == $query) {
                $found[] = new Syncroton_Model_StoreResponseResult(array(
                    'LongId' => 'addressbookFolderId-' .  $serverId,
                    'Properties' => $this->getSearchEntry('addressbookFolderId-' .  $serverId, $options)
                ));
            }
        }
        
        return $found;
    }
    
    protected function _initData()
    {
        /**
        * used by unit tests only to simulated added folders
        */
        if (!isset(Syncroton_Data_AData::$folders[get_class($this)])) {
            Syncroton_Data_AData::$folders[get_class($this)] = array(
                'addressbookFolderId' => new Syncroton_Model_Folder(array(
                    'id'          => sha1(mt_rand(). microtime()),
                    'serverId'    => 'addressbookFolderId',
                    'parentId'    => 0,
                    'displayName' => 'Default Contacts Folder',
                    'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT
                )),
                'anotherAddressbookFolderId' => new Syncroton_Model_Folder(array(
                    'id'          => sha1(mt_rand(). microtime()),
                    'serverId'    => 'anotherAddressbookFolderId',
                    'parentId'    => 0,
                    'displayName' => 'Another Contacts Folder',
                    'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED
                ))
            );
        }
        
        /**
         * used by unit tests only to simulated added folders
         */
        $entries = $this->getServerEntries('addressbookFolderId', 1);
        
        if (count($entries) == 0) {
            $testData = array(
                'addressbookFolderId' => array(
                    'contact1' => new Syncroton_Model_Contact(array(
                        'firstName' => 'Lars', 
                        'lastName'  => 'Kneschke'
                    )),
                    'contact2' => new Syncroton_Model_Contact(array(
                        'firstName' => 'Cornelius', 
                        'lastName'  => 'Weiß'
                    )),
                    'contact3' => new Syncroton_Model_Contact(array(
                        'firstName' => 'Lars', 
                        'lastName'  => 'Kneschke'
                    )),
                    'contact4' => new Syncroton_Model_Contact(array(
                        'firstName' => 'Cornelius', 
                        'lastName'  => 'Weiß'
                    )),
                    'contact5' => new Syncroton_Model_Contact(array(
                        'firstName' => 'Lars', 
                        'lastName'  => 'Kneschke'
                    )),
                    'contact6' => new Syncroton_Model_Contact(array(
                        'firstName' => 'Cornelius', 
                        'lastName'  => 'Weiß'
                    )),
                    'contact7' => new Syncroton_Model_Contact(array(
                        'firstName' => 'Lars', 
                        'lastName'  => 'Kneschke'
                    )),
                    'contact8' => new Syncroton_Model_Contact(array(
                        'firstName' => 'Cornelius', 
                        'lastName'  => 'Weiß'
                    )),
                    'contact9' => new Syncroton_Model_Contact(array(
                        'firstName' => 'Lars', 
                        'lastName'  => 'Kneschke'
                    )),
                    'contact10' => new Syncroton_Model_Contact(array(
                        'firstName' => 'Cornelius', 
                        'lastName'  => 'Weiß'
                    ))
                )
            );
            
            foreach ($testData['addressbookFolderId'] as $data) {
                $this->createEntry('addressbookFolderId', $data);
            }
        }
    }
}

