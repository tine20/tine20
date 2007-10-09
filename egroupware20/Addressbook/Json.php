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
class Addressbook_Json
{
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
    public function saveList($_listId, $_listOwner, $_listMembers, $list_description, $list_name)
    {
        $list = new Addressbook_List();
        try {
            $userData['list_owner'] = $_listOwner;
            $userData['list_members'] = Zend_Json::decode($_listMembers);
            $userData['list_description'] = $list_description;
            $userData['list_name'] = $list_name;
            if(!empty($_listId)) {
                $userData['list_id'] = $_listId;
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
    }

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
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getContacts($query, $datatype, $owner, $start, $sort, $dir, $limit, $options)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        if(empty($query)) {
            $query = NULL;
        }

        switch($datatype) {
            case 'accounts':
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                if($rows = $backend->getAccounts($query, $sort, $dir, $limit, $start)) {
                    $result['results']    = $rows->toArray();
                    $result['totalcount'] = $backend->getCountOfAccounts();
                }

                break;

            case 'contacts':
                $options = Zend_Json::decode($options);
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                if($rows = $backend->getContactsByOwner($owner, $query, $options, $sort, $dir, $limit, $start)) {
                    $result['results']    = $rows->toArray();
                    $result['totalcount'] = $backend->getCountByOwner($owner);
                }

                break;

            case 'otherpeople':
                $options = Zend_Json::decode($options);
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);

                if($rows = $backend->getAllOtherPeopleContacts($query, $options, $sort, $dir, $limit, $start)) {
                    $result['results']    = $rows->toArray();
                    $result['totalcount'] = $backend->getCountOfAllOtherPeopleContacts();
                }

                break;

            case 'sharedaddressbooks':
                $options = Zend_Json::decode($options);
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                if($rows = $backend->getAllSharedContacts($query, $options, $sort, $dir, $limit, $start)) {
                    $result['results']    = $rows->toArray();
                    $result['totalcount'] = $backend->getCountOfAllSharedContacts();
                }

                break;

        }

        return $result;
    }

    public function getList($listId, $query, $owner, $start, $sort, $dir, $limit)
    {
        $result = array();
        if(empty($query)) {
            $query = NULL;
        }
         
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
        if($rows = $backend->getContactsByList($listId, $owner, $query, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            $result['totalcount'] = $backend->getCountByOwner($owner);
        }
         
        return $result;
    }

    /**
     * Returns the structure of the initial tree for this application.
     *
     * This function returns the needed structure, to display the initial tree, after the the logoin.
     * Additional tree items get loaded on demand.
     *
     * @return array
     */
    public function getInitialTree($_location)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
         
        switch($_location) {
            case 'mainTree':
                $treeNodes = array();
                
                $treeNode = new Egwbase_Ext_Treenode('Addressbook', 'overview', 'addressbook', 'All Contacts', FALSE);
                $treeNode->setIcon('apps/kaddressbook.png');
                $treeNode->cls = 'treemain';

                $childNode = new Egwbase_Ext_Treenode('Addressbook', 'contacts', 'mycontacts', 'My Contacts', TRUE);
                $childNode->owner = $currentAccount->account_id;
                $treeNode->addChildren($childNode);

                $childNode = new Egwbase_Ext_Treenode('Addressbook', 'accounts', 'accounts', 'All Users', TRUE);
                $childNode->owner = 0;
                $treeNode->addChildren($childNode);
                
                $childNode = new Egwbase_Ext_Treenode('Addressbook', 'otherpeople', 'otherpeople', 'Other Users Contacts', FALSE);
                $childNode->owner = 0;
                $treeNode->addChildren($childNode);
                                 
                $childNode = new Egwbase_Ext_Treenode('Addressbook', 'sharedaddressbooks', 'sharedaddressbooks', 'Shared Contacts', FALSE);
                $childNode->owner = 0;
                $treeNode->addChildren($childNode);

                $treeNodes[] = $treeNode;
                
                $treeNode = new Egwbase_Ext_Treenode('Lists', 'alllists', 'alllists', 'All Lists', FALSE);
                $treeNode->setIcon('apps/kaddressbook.png');
                $treeNode->cls = 'treemain';

                $childNode = new Egwbase_Ext_Treenode('Lists', 'mylists', 'mylists', 'My Lists', FALSE);
                $childNode->owner = $currentAccount->account_id;
                $treeNode->addChildren($childNode);

                $childNode = new Egwbase_Ext_Treenode('Lists', 'otherlists', 'otherlists', 'Other Users Lists', FALSE);
                $childNode->owner = 0;
                $treeNode->addChildren($childNode);
                                 
                $childNode = new Egwbase_Ext_Treenode('Lists', 'sharedlists', 'sharedlists', 'Shared Lists', FALSE);
                $childNode->owner = 0;
                $treeNode->addChildren($childNode);
                
                $treeNodes[] = $treeNode;
                
                return $treeNodes;
                 
                break;
                 
            case 'selectFolder':
                $treeNode = array();

                $childNode = new Egwbase_Ext_Treenode('Addressbook', 'contacts', 'mycontacts', 'My Contacts', TRUE);
                $childNode->owner = $currentAccount->account_id;
                $treeNode[] = $childNode;

                $childNode = new Egwbase_Ext_Treenode('Addressbook', 'otherpeople', 'otherpeople', 'Other Users Contacts', FALSE);
                $childNode->owner = 0;
                $treeNode[] = $childNode;

                $childNode = new Egwbase_Ext_Treenode('Addressbook', 'sharedaddressbooks', 'sharedaddressbooks', 'Shared Contacts', FALSE);
                $childNode->owner = 0;
                $treeNode[] = $childNode;
                 
                return $treeNode;
                 
                break;
        }

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
    public function getOverview($query, $start, $sort, $dir, $limit, $options)
    {
        if(empty($query)) {
            $query = NULL;
        }

        $options = Zend_Json::decode($options);
        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);

        $result = array();
        if($rows = $backend->getAllContacts($query, $options, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            $result['totalcount'] = $backend->getCountOfAllContacts();
        }

        return $result;
    }


    /**
     * returns the nodes for the dynamic tree
     *
     * @param string $node which node got selected in the UI
     * @param string $datatype what kind of data to search
     * @return string json encoded array
     */
    public function getSubTree($_node, $_owner, $_datatype, $_location)
    {
        $nodes = array();

        switch($_location) {
            case 'mainTree':
                switch($_datatype) {
                    case 'mylists':
                        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                        $lists = $backend->getListsByOwner($_owner);

                        foreach($lists as $listObject) {
                            $treeNode = new Egwbase_Ext_Treenode(
                        		'Addressbook',
                        		'list',
                        		'list-'. $listObject->contact_id, 
                                $listObject->n_family,
                                TRUE
                            );
                            $treeNode->contextMenuClass = 'ctxMenuList';
                            $treeNode->listId = $listObject->contact_id;
                            $treeNode->owner  = $_owner;
                            $nodes[] = $treeNode;
                        }

                        break;

                    case 'otherlists':
                        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                        $lists = $backend->getOtherAddressbooks();
                        foreach($lists as $listObject) {
                            $treeNode = new Egwbase_Ext_Treenode(
                        		'Addressbook',
                        		'contacts',
                        		'other_'. $listObject->id, 
                                $listObject->title,
                                FALSE
                            );
                            $treeNode->contextMenuClass = 'ctxMenuContacts';
                            $treeNode->owner  = $listObject->id;
                            $nodes[] = $treeNode;
                        }

                        break;

                    case 'otherpeople':
                        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                        $lists = $backend->getOtherAddressbooks();
                        foreach($lists as $listObject) {
                            $treeNode = new Egwbase_Ext_Treenode(
                        		'Addressbook',
                        		'contacts',
                        		'other_'. $listObject->id, 
                                $listObject->title,
                                TRUE
                            );
                            $treeNode->contextMenuClass = 'ctxMenuContacts';
                            $treeNode->owner  = $listObject->id;
                            $nodes[] = $treeNode;
                        }

                        break;

                    case 'sharedlists':
                        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                        $lists = $backend->getSharedAddressbooks();
                        foreach($lists as $listObject) {
                            $treeNode = new Egwbase_Ext_Treenode(
                        		'Addressbook',
                        		'contacts',
                        		'shared_'. $listObject->id, 
                                $listObject->title,
                                FALSE
                            );
                            $treeNode->contextMenuClass = 'ctxMenuContacts';
                            $treeNode->owner  = $listObject->id;
                            $nodes[] = $treeNode;
                        }

                        break;
                        
                    case 'sharedaddressbooks':
                        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                        $lists = $backend->getSharedAddressbooks();
                        foreach($lists as $listObject) {
                            $treeNode = new Egwbase_Ext_Treenode(
                        		'Addressbook',
                        		'contacts',
                        		'shared_'. $listObject->id, 
                                $listObject->title,
                                TRUE
                            );
                            $treeNode->contextMenuClass = 'ctxMenuContacts';
                            $treeNode->owner  = $listObject->id;
                            $nodes[] = $treeNode;
                        }

                    break;
                        
                }
                
                break;
                
            case 'selectFolder':
                switch($_datatype) {
                    case 'otherpeople':
                        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                        $lists = $backend->getOtherAddressbooks();
                        foreach($lists as $listObject) {
                            $treeNode = new Egwbase_Ext_Treenode(
                        		'Addressbook',
                        		'contacts',
                        		'other_'. $listObject->id, 
                                $listObject->title,
                                TRUE
                            );
                            $treeNode->contextMenuClass = 'ctxMenuContacts';
                            $treeNode->owner  = $listObject->id;
                            $nodes[] = $treeNode;
                        }

                        break;

                    case 'sharedaddressbooks':
                        $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                        $lists = $backend->getSharedAddressbooks();
                        foreach($lists as $listObject) {
                            $treeNode = new Egwbase_Ext_Treenode(
                        		'Addressbook',
                        		'contacts',
                        		'shared_'. $listObject->id, 
                                $listObject->title,
                                TRUE
                            );
                            $treeNode->contextMenuClass = 'ctxMenuContacts';
                            $treeNode->owner  = $listObject->id;
                            $nodes[] = $treeNode;
                        }

                        break;
                }
                
                break;
                
        }

        echo Zend_Json::encode($nodes);

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }
}