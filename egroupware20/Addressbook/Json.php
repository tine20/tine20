<?php
/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the addressbook application
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Addressbook_Json extends Egwbase_Application_Json_Abstract
{
    protected $_appname = 'Addressbook';
    
    /**
     * delete a array of contacts
     *
     * @param array $_contactIDs
     * @return array
     */
    public function deleteContacts($_contactIds)
    {
        $contactIds = Zend_Json::decode($_contactIds);
        if(is_array($contactIds)) {
            $contacts = Addressbook_Backend::factory(Addressbook_Backend::SQL);
            $contacts->deleteContactsById($contactIds);

            $result = array('success'   => TRUE, 'ids' => $contactIds);
        } else {
            $result = array('success'   => FALSE);
        }

        return $result;
    }
     
    /**
     * delete a array of lists
     *
     * @param array $listIDs
     * @return array
     */
/*    public function deleteLists($listIds)
    {
        $listIds = Zend_Json::decode($listIds);
        if(is_array($listIds)) {
            $contacts = Addressbook_Backend::factory(Addressbook_Backend::SQL);
            $contacts->deleteListsById($listIds);

            $result = array('success'   => TRUE, 'ids' => $listIds);
        } else {
            $result = array('success'   => FALSE);
        }

        return $result;
    } */
     
    /**
     * save one contact
     *
     * if $_contactId is 0 the contact gets added, otherwise it gets updated
     *
     * @return array
     */
    public function saveContact()
    {
        // convert birthday back to yyyy-mm-dd
        if(isset($_POST['contact_bday'])) {
            $locale = Zend_Registry::get('locale');
            $dateFormat = $locale->getTranslationList('Dateformat');
            try {
                $date = new Zend_Date($_POST['contact_bday'], $dateFormat['long'], 'en');
                $_POST['contact_bday'] = $date->toString('yyyy-MM-dd');
            } catch (Exception $e) {
                unset($_POST['contact_bday']);
            }
        }

        if(empty($_POST['contact_id'])) {
            unset($_POST['contact_id']);
        }

        $contact = new Addressbook_Contact();
        try {
            $contact->setFromUserData($_POST);
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errors'            => $contact->getValidationErrors(),
                            'errorMessage'      => 'filter NOT ok');

            return $result;
        }

        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
         
        try {
            $backend->saveContact($contact);
            $result = array('success'           => true,
                            'welcomeMessage'    => 'Entry updated');
        } catch (Exception $e) {
            $result = array('success'           => false,
        					'errorMessage'      => $e->getMessage());
        }

        return $result;
         
    }

    /**
     * save one list
     *
     * if $_listID is NULL the contact gets added, otherwise it gets updated
     *
     * @param int $_listId the id of the list to update, set to 0 for new lists
     * @param int $_listOwner the id the list owner
     * @return array
     */
/*    public function saveList($list_id, $list_owner, $listMembers, $list_description, $list_name)
    {
        $listMembers = Zend_Json::decode($listMembers);
        
        $list = new Addressbook_List();
        try {
            $userData['list_owner'] = $list_owner;
            $userData['list_description'] = $list_description;
            $userData['list_name'] = $list_name;
            if(!empty($list_id)) {
                $userData['list_id'] = $list_id;
            }
            if(is_array($listMembers)) {
                $userData['list_members'] = $listMembers;
            }
             
            $list->setFromUserData($userData);

        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errors'            => $list->getValidationErrors(),
                            'errorMessage'      => 'filter NOT ok');

            return $result;
        }

        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);

        try {
            $backend->saveList($list);
            $result = array('success'           => true,
            				'listId'			=> $list->list_id,
                            'welcomeMessage'    => 'Entry updated');
        } catch (Exception $e) {
            $result = array('success'           => false,
        					'errorMessage'      => $e->getMessage());
        }

        return $result;
    } */

    /**
     * get data for overview
     *
     * returns the data to be displayed in a ExtJS grid
     *
     * @todo implement correc total count for lists
     * @param string $nodeid
     * @param string $_datatype
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return array
     */
    public function getContactsByOwner($filter, $owner, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );

        if(empty($filter)) {
            $filter = NULL;
        }
        
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if($rows = $backend->getContactsByOwner($owner, $filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            //$result['totalcount'] = $backend->getCountByOwner($owner);
        }

        return $result;
    }

    public function getAccounts($filter, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        if(empty($filter)) {
            $filter = NULL;
        }
         
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if($rows = $backend->getAccounts($filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            $result['totalcount'] = $backend->getCountOfAccounts();
        }
         
        return $result;
    }

    
    
/*    public function getContactsByListId($listId, $filter, $owner, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        if(empty($filter)) {
            $filter = NULL;
        }
         
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if($rows = $backend->getContactsByListId($listId, $owner, $filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            $result['totalcount'] = $backend->getCountByOwner($owner);
        }
         
        return $result;
    } */
    
    public function getContactsByAddressbookId($addressbookId, $filter, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        if(empty($filter)) {
            $filter = NULL;
        }
                
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if(Zend_Registry::get('dbConfig')->get('egw14compat') == 1) {
            if($rows = $backend->getContactsByOwner($addressbookId, $filter, $sort, $dir, $limit = NULL, $start)) {
                $result['results']    = $rows->toArray();
                $result['totalcount'] = $backend->getCountByOwner($addressbookId);
            }
        } else {
            if($rows = $backend->getContactsByOwner($addressbookId, $filter, $sort, $dir, $limit = NULL, $start)) {
                $result['results']    = $rows->toArray();
                $result['totalcount'] = $backend->getCountByOwner($addressbookId);
            }
        }
        
        return $result;
    }

    
    /*public function getListMemberByOwner($query, $owner, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if(empty($query)) {
            $query = NULL;
        }

        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if($rows = $backend->getContactsByListOwner($owner, $query, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            //$result['totalcount'] = $backend->getCountByOwner($owner);
        }
         
        return $result;
    } */

/*    public function getListsByOwner($owner, $filter, $sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if(empty($filter)) {
            $filter = NULL;
        }

        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if($rows = $backend->getListsByOwner($owner, $filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            //$result['totalcount'] = $backend->getCountByOwner($owner);
        }
         
        return $result;
    } */
        
    public function getAddressbooksByOwner($owner)
    {
        $treeNodes = array();
        
        if(Zend_Registry::get('dbConfig')->get('egw14compat') == 1) {
            // eGW 1.4 does not support multiple addressbooks per user

            // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
            return NULL;
        }
        
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if($rows = $backend->getAddressbooksByOwner($owner)) {
            foreach($rows as $addressbookData) {
                $childNode = new Egwbase_Ext_Treenode('Addressbook', 'contacts', 'addressbook-' . $addressbookData->container_id, $addressbookData->container_name, TRUE);
                $childNode->addressbookId = $addressbookData->container_id;
                $childNode->nodeType = 'singleAddressbook';
                $treeNodes[] = $childNode;
            }
        }
        
        echo Zend_Json::encode($treeNodes);

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }    

    public function getSharedAddressbooks()
    {
        $treeNodes = array();
        
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if(Zend_Registry::get('dbConfig')->get('egw14compat') == 1) {
            $rows = $backend->getSharedAddressbooks_14();
        } else {
            $rows = $backend->getSharedAddressbooks();
        }
        
        if(is_array($rows)) {
            foreach($rows as $addressbookData) {
                $childNode = new Egwbase_Ext_Treenode('Addressbook', 'contacts', 'shared-' . $addressbookData->id, $addressbookData->name, TRUE);
                $childNode->addressbookId = $addressbookData->id;
                $childNode->nodeType = 'singleAddressbook';
                $treeNodes[] = $childNode;
            }
        }
        
        echo Zend_Json::encode($treeNodes);

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }    
    
    public function getOtherUsers()
    {
        $treeNodes = array();
        
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if(Zend_Registry::get('dbConfig')->get('egw14compat') == 1) {
            $rows = $backend->getOtherUsers_14();
        } else {
            $rows = $backend->getOtherUsers();
        }
        
        if(is_array($rows)) {
            foreach($rows as $addressbookData) {
                $treeNode = new Egwbase_Ext_Treenode(
                                    'Addressbook',
                                    'contacts',
                                    'otheraddressbook_'. $addressbookData->id, 
                                    $addressbookData->name,
                                    Zend_Registry::get('dbConfig')->get('egw14compat') == 1
                );
                $treeNode->owner  = $addressbookData->id;
                $treeNode->nodeType = 'userAddressbooks';
                $treeNodes[] = $treeNode;
            }
        }
        
        echo Zend_Json::encode($treeNodes);

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }    

    /**
     * get data for the overview
     *
     * returns the data to be displayed in a ExtJS grid
     *
     * @todo implement correc total count for lists
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getAllContacts($filter, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        if(empty($filter)) {
            $filter = NULL;
        }
                
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);

        if($rows = $backend->getAllContacts($filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            $result['totalcount'] = $backend->getCountOfAllContacts();
        }

        return $result;
    }

    /**
     * get data for the overview
     *
     * returns the data to be displayed in a ExtJS grid
     *
     * @todo implement correc total count for lists
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getSharedContacts($filter, $sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        if(empty($filter)) {
            $filter = NULL;
        }
                
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if(Zend_Registry::get('dbConfig')->get('egw14compat') == 1) {
            $rows = $backend->getSharedContacts_14($filter, $sort, $dir, $limit, $start);
        } else {
            $rows = $backend->getSharedContacts($filter, $sort, $dir, $limit, $start);
        }
        
        if($rows !== false) {
            $result['results']    = $rows->toArray();
            $result['totalcount'] = $backend->getCountOfSharedContacts();
        }

        return $result;
    }

    /**
     * get data for the overview
     *
     * returns the data to be displayed in a ExtJS grid
     *
     * @todo implement correc total count for lists
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getOtherPeopleContacts($filter, $sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        if(empty($filter)) {
            $filter = NULL;
        }
                
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if(Zend_Registry::get('dbConfig')->get('egw14compat') == 1) {
            $rows = $backend->getOtherPeopleContacts_14($filter, $sort, $dir, $limit, $start);
        } else {
            $rows = $backend->getOtherPeopleContacts($filter, $sort, $dir, $limit, $start);
        }
        
        if($rows !== false) {
            $result['results']    = $rows->toArray();
            $result['totalcount'] = $backend->getCountOfOtherPeopleContacts();
        }

        return $result;
    }
    
    
}