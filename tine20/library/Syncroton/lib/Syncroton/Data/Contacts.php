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
    protected $_supportedFolderTypes = array(
        Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT,
        Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED
    );
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataSearch::getSearchEntry()
     */
    public function getSearchEntry($longId, $options)
    {
        list($collectionId, $serverId) = explode(Syncroton_Data_AData::LONGID_DELIMITER, $longId, 2);
        
        $contact = $this->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $collectionId)), $serverId);
        
        return new Syncroton_Model_GAL(array(
            'firstName' => $contact->firstName,
            'lastName'  => $contact->lastName,
            'picture'   => new Syncroton_Model_GALPicture(array('status' => 1, 'data' => 'abc'))
        ));
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataSearch::search()
     */
    public function search(Syncroton_Model_StoreRequest $store)
    {
        $storeResponse = new Syncroton_Model_StoreResponse();
        
        $serverIds = $this->getServerEntries('addressbookFolderId', Syncroton_Command_Sync::FILTER_NOTHING);
        
        $total = 0;
        $found = array();
        
        foreach ($serverIds as $serverId) {
            $contact = $this->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => 'addressbookFolderId')), $serverId);
            
            if ($contact->firstName == $store->query) {
                $total++;
                
                if (count($found) == $store->options['range'][1]+1) {
                    continue;
                }
                $found[] = new Syncroton_Model_StoreResponseResult(array(
                    'longId' => 'addressbookFolderId' . Syncroton_Data_AData::LONGID_DELIMITER .  $serverId,
                    'properties' => $this->getSearchEntry('addressbookFolderId' . Syncroton_Data_AData::LONGID_DELIMITER .  $serverId, $store->options)
                ));
            }
        }
        
        if (count($found) > 0) {
            $storeResponse->result = $found;
            $storeResponse->range = array(0, count($found) - 1);
            $storeResponse->total = $total;
        } else {
            $storeResponse->total = $total;
        }
        
        return $storeResponse;
    }
}

