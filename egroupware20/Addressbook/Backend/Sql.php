<?php
/**
 * the class needed to access the contacts table
 *
 * @see Addressbook_Backend_Sql_Contacts
 */
require_once 'Addressbook/Backend/Sql/Contacts.php';

/**
 * the class needed to access the lists table
 *
 * @see Addressbook_Backend_Sql_Lists
 */
require_once 'Addressbook/Backend/Sql/Lists.php';

/**
 * interface for contacs class
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Addressbook_Backend_Sql implements Addressbook_Backend_Interface
{
    /**
     * Instance of Addressbook_Backend_Sql_Contacts
     *
     * @var Addressbook_Backend_Sql_Contacts
     */
    protected $contactsTable;

    /**
     * Instance of Addressbook_Backend_Sql_Lists
     *
     * @var Addressbook_Backend_Sql_Lists
     */
    protected $listsTable;

    /**
     * Instance of the Egwbase_Acl class
     *
     * @var unknown_type
     */
    protected $egwbaseAcl;

    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->contactsTable = new Addressbook_Backend_Sql_Contacts();
        $this->listsTable = new Addressbook_Backend_Sql_Lists();
        $this->listsMapping = new Addressbook_Backend_Sql_ListMapping();
        //$this->egwbaseAcl = Egwbase_Acl::getInstance();
    }

    /**
     * add or updates a contact
     *
     * @param int $_contactOwner the owner of the addressbook entry
     * @param Addressbook_Contact $_contactData the contactdata
     * @param int $_contactId the contact to update, if NULL the contact gets added
     * @todo check acl when adding contact
     * @return unknown
     */
    public function saveContact(Addressbook_Contact $_contactData)
    {
        if(empty($_contactData->contact_owner)) {
            throw new UnderflowException('contact_owner can not be empty');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_contactData->contact_owner, Egwbase_Container::GRANT_EDIT)) {
            throw new Exception('write access to new addressbook denied');
        }
        
        $accountId   = Zend_Registry::get('currentAccount')->account_id;
        $currentAccount = Zend_Registry::get('currentAccount');

        $contactData = $_contactData->toArray();
        $contactData['contact_tid'] = 'n';
        unset($contactData['contact_id']);
        

        if($_contactData->contact_id === NULL) {
            $_contactData->contact_id = $this->contactsTable->insert($contactData);
        } else {
            $oldContactData = $this->getContactById($_contactData->contact_id);
            if(!Zend_Registry::get('currentAccount')->hasGrant($oldContactData->contact_owner, Egwbase_Container::GRANT_EDIT)) {
                throw new Exception('write access to old addressbook denied');
            }
            
            $where  = array(
                $this->contactsTable->getAdapter()->quoteInto('contact_id = ?', $_contactData->contact_id)
            );

            $result = $this->contactsTable->update($contactData, $where);
        }

        return $_contactData;
    }

    /**
     * add or updates a list
     *
     * @param int $_listOwner the owner of the addressbook entry
     * @param Addressbook_List $_listData the listdata
     * @param int $_listId the list to update, if NULL the list gets added
     * @todo check acl when adding list
     * @return unknown
     */
/*    public function saveList(Addressbook_List $_listData)
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $listData = array();
        $listData['list_name']		  = $_listData->list_name;
        //$listData['list_description'] = $_listData->list_description;
        $listData['list_owner']	      = $_listData->list_owner;

        if($_listData->list_id === NULL) {
            $result = $this->listsTable->insert($listData);
            $_listData->list_id = $this->listsTable->getAdapter()->lastInsertId();
        } else {

            $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::EDIT);

            // update the requested contact_id only if the contact_owner matches the current users acl
            $where  = array(
                $this->listsTable->getAdapter()->quoteInto('list_id = (?)', $_listData->list_id),
                $this->listsTable->getAdapter()->quoteInto('list_owner IN (?)', array_keys($acl))
            );

            $result = $this->listsTable->update($listData, $where);
        }


        $where = $this->listsMapping->getAdapter()->quoteInto('list_id = ?', $_listData->list_id);
        $this->listsMapping->delete($where);

        //error_log(print_r($_listData->list_members, true));
        $listMembers = array();
        foreach($_listData->list_members as $contact) {
            if($contact->contact_id === NULL) {
                $contact->contact_owner = $_listData->list_owner;
                $contact = $this->saveContact($contact);
            }
            $listMembers[$contact->contact_id] = $contact->contact_id;
        }

        foreach($listMembers as $listMember) {
            $listMemberData = array();
            $listMemberData['list_id']			= $_listData->list_id;
            $listMemberData['contact_id']		= $listMember;
            $listMemberData['list_added_by']	= $currentAccount->account_id;
             
            $this->listsMapping->insert($listMemberData);
        }
        
        return $_listData;
    }*/

    /**
     * delete contact identified by contact id
     *
     * @param int $_contacts contact ids
     * @return int the number of rows deleted
     */
    public function deleteContactById($_contactId)
    {
        $contactId = (int)$_contactId;
        if($contactId != $_contactId) {
            throw new InvalidArgumentException('$_contactId must be integer');
        }

        $oldContactData = $this->getContactById($_contactId);

        if(!Zend_Registry::get('currentAccount')->hasGrant($oldContactData->contact_owner, Egwbase_Container::GRANT_DELETE)) {
            throw new Exception('delete access to addressbook denied');
        }
        
        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_id = ?', $contactId),
        );
         
        $result = $this->contactsTable->delete($where);

        return $result;
    }

    /**
     * delete lists identified by list id
     *
     * @param array $_lists list of list ids
     * @return int the number of rows deleted
     */
    /*public function deleteListsById(array $_lists)
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::DELETE);

        if(empty($acl)) {
            return false;
        }
        
        foreach($_lists as $listId) {
            if((int)$listId === 0) {
                throw new Exception('$listId must be a integer and bigger 0');
            }
            // delete the requested list_id only if the list_owner matches the current users acl
            $where  = array(
                $this->listsTable->getAdapter()->quoteInto('list_id = ?', $listId),
                $this->listsTable->getAdapter()->quoteInto('list_owner IN (?)', array_keys($acl))
            );
             
            $result = $this->listsTable->delete($where);
            
            // delete was successfull, now also delete the listmembers
            if($result === 1) {
                $where  = array(
                    $this->listsTable->getAdapter()->quoteInto('list_id = ?', $listId)
                );
                
                $this->listsMapping->delete($where);
            }
        }

        return $result;
    } */
    
    public function addAddressbook($_name, $_type) 
    {
        $egwbaseContainer = Egwbase_Container::getInstance();
        $accountId   = Zend_Registry::get('currentAccount')->account_id;
        $allGrants = array(
            Egwbase_Container::GRANT_ADD,
            Egwbase_Container::GRANT_ADMIN,
            Egwbase_Container::GRANT_DELETE,
            Egwbase_Container::GRANT_EDIT,
            Egwbase_Container::GRANT_READ
        );
        
        if($_type == Egwbase_Container::TYPE_SHARED) {
            $addressbookId = $egwbaseContainer->addContainer('addressbook', $_name, Egwbase_Container::TYPE_SHARED, Addressbook_Backend::SQL);

            // add admin grants to creator
            $egwbaseContainer->addGrants($addressbookId, $accountId, $allGrants);
            // add read grants to any other user
            $egwbaseContainer->addGrants($addressbookId, NULL, array(Egwbase_Container::GRANT_READ));
        } else {
            $addressbookId = $egwbaseContainer->addContainer('addressbook', $_name, Egwbase_Container::TYPE_PERSONAL, Addressbook_Backend::SQL);
        
            // add admin grants to creator
            $egwbaseContainer->addGrants($addressbookId, $accountId, $allGrants);
        }
        
        return $addressbookId;
    }
    
    public function deleteAddressbook($_addressbookId)
    {
        $egwbaseContainer = Egwbase_Container::getInstance();
        
        $egwbaseContainer->deleteContainer($_addressbookId);
        
        $where = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_owner = ?', (int)$_addressbookId)
        );
        
        //$this->contactsTable->delete($where);
        
        return true;
    }
    
    public function renameAddressbook($_addressbookId, $_name)
    {
        $egwbaseContainer = Egwbase_Container::getInstance();
        
        $egwbaseContainer->renameContainer($_addressbookId, $_name);
                
        return true;
    }
    
    public function getOtherPeopleContacts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL) 
    {
        $otherPeoplesContainer = Egwbase_Container::getInstance()->getOtherUsersContainer('addressbook');
        
        $containerIds = array();
        
        foreach($otherPeoplesContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', $containerIds)
        );

        $result = $this->_getContactsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start);
         
        return $result;
    }
    
    /**
     * get total count of all other users contacts
     *
     * @return int count of all other users contacts
     */
    public function getCountOfOtherPeopleContacts()
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ACCOUNT_GRANTS);

        if(empty($acl)) {
            return false;
        }

        $groupIds = array_keys($acl);

        $result = $this->contactsTable->getCountByAcl($groupIds);

        return $result;
    }

    /**
     * get list of contacts from all shared addressbooks the current user has access to
     *
     * @param string $_filter string to search for in contacts
     * @param array $_contactType filter by type (list or contact currently)
     * @param unknown_type $_sort fieldname to sort by
     * @param unknown_type $_dir sort ascending or descending (ASC | DESC)
     * @param unknown_type $_limit how many contacts to display
     * @param unknown_type $_start how many contaxts to skip
     * @return unknown The row results per the Zend_Db_Adapter fetch mode.
     */
    public function getAllContacts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $allContainer = Egwbase_Container::getInstance()->getContainerByACL('addressbook', Egwbase_Container::GRANT_READ);
        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', $containerIds)
        );

        $result = $this->_getContactsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start);
         
        return $result;
    }

    /**
     * get total count of all contacts from shared addressbooks
     *
     * @todo return the correct count (the accounts are missing)
     *
     * @return int count of all other users contacts
     */
    public function getCountOfAllContacts()
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ANY_GRANTS);

        $groupIds = array_keys($acl);

        $result = $this->contactsTable->getCountByAcl($groupIds);

        return $result;
    }

    public function getSharedContacts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL) 
    {
        $sharedContainer = Egwbase_Container::getInstance()->getSharedContainer('addressbook');
        
        $containerIds = array();
        
        foreach($sharedContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', $containerIds)
        );

        $result = $this->_getContactsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start);
         
        return $result;
    }
    
    
    /**
     * get total count of all contacts from shared addressbooks
     *
     * @return int count of all other users contacts
     */
    public function getCountOfSharedContacts()
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::GROUP_GRANTS);

        if(empty($acl)) {
            return false;
        }

        $groupIds = array_keys($acl);

        $result = $this->contactsTable->getCountByAcl($groupIds);

        return $result;
    }

    /**
     * fetch one contact identified by contactid
     *
     * @param int $_contactId
     * @return The row results per the Zend_Db_Adapter fetch mode, or null if no row found.
     */
    public function getContactById($_contactId)
    {
        $contactId = (int)$_contactId;
        if($contactId != $_contactId) {
            throw new InvalidArgumentException('$_contactId must be integer');
        }
        
        $accountId = Zend_Registry::get('currentAccount')->account_id;

        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_id = ?', $contactId)
        );

        $result = $this->contactsTable->fetchRow($where);
        
        if($result === NULL) {
            throw new UnderFlowExecption('contact not found');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($result->contact_owner, Egwbase_Container::GRANT_READ)) {
            throw new Exception('permission to contact denied');
        }

        return $result;
    }

    /**
     * fetch one contact identified by contactid
     *
     * @param array $_contacts
     * @return The row results per the Zend_Db_Adapter fetch mode, or null if no row found.
     */
/*    public function getListById($_listId)
    {
        $listId = (int)$_listId;
        if($listId < 1) {
            throw new Exception('$_listId must be integer and greater than 0');
        }
        $currentAccount = Zend_Registry::get('currentAccount');

        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ);

        // return the requested list_id only if the contact_owner matches the current users acl
        $where  = array(
            $this->listsTable->getAdapter()->quoteInto('list_id = ?', $listId),
            $this->listsTable->getAdapter()->quoteInto('list_owner IN (?)', array_keys($acl))
        );

        $listData = $this->listsTable->fetchRow($where);
        $listMembers = $this->getContactsByListId($listId, $currentAccount->account_id, NULL, 'n_family', 'ASC');

        $result = new Addressbook_List();

        $result->list_id = $listData->list_id;
        $result->list_name = $listData->list_name;
        //$result->list_description = $listData->list_description;
        $result->list_owner = $listData->list_owner;
        $result->list_members = $listMembers;

        return $result;
    }*/

    public function getContactsByOwner($_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $ownerContainer = Egwbase_Container::getInstance()->getPersonalContainer('addressbook', $_owner);
        
        $containerIds = array();
        
        foreach($ownerContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', $containerIds)
        );

        $result = $this->_getContactsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start);
         
        return $result;
    }

    public function getContactsByAddressbookId($_addressbookId, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        // convert to int
        $addressbookId = (int)$_addressbookId;
        if($addressbookId != $_addressbookId) {
            throw new InvalidArgumentException('$_addressbookId must be integer');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_addressbookId, Egwbase_Container::GRANT_READ)) {
            throw new Exception('read access denied to addressbook');
        }
        
        $where = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_owner = ?', $addressbookId)
        );

        $result = $this->_getContactsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start);
         
        return $result;
    }
    
    public function getCountByOwner($_owner)
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        if($_owner != $currentAccount->account_id && !$this->egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
            throw new Exception("access to addressbook $_owner by $currentAccount->account_id denied.");
        }

        $acl = array($_owner);

        $result = $this->contactsTable->getCountByAcl($acl);

        return $result;
    }

    /**
     * return entries from a personal list
     *
     * @param unknown_type $_list
     * @param unknown_type $_owner
     * @param unknown_type $_filter
     * @param unknown_type $_sort
     * @param unknown_type $_dir
     * @param unknown_type $_limit
     * @param unknown_type $_start
     * @return Addressbook_ContactSet
     */
/*    public function getContactsByListId($_list, $_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        if($_owner != $currentAccount->account_id && !$this->egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
            throw new Exception("access to addressbook $_owner by $currentAccount->account_id denied.");
        }

        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ);

        if(empty($acl)) {
            return false;
        }
        
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
        ->from('egw_addressbook2list', array())
        ->order($_sort . ' ' . $_dir)
        ->join('egw_addressbook','egw_addressbook.contact_id = egw_addressbook2list.contact_id')
        ->join('egw_addressbook_lists','egw_addressbook_lists.list_id = egw_addressbook2list.list_id')
        ->where('egw_addressbook2list.list_id = ?', $_list)
        ->where('egw_addressbook_lists.list_owner = ?', $_owner)
        ->where('egw_addressbook.contact_owner IN (?)', array_keys($acl))
        ->limit($limit, $start);

        //error_log("getContactsByListQuery:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = new Addressbook_ContactSet($stmt->fetchAll(Zend_Db::FETCH_ASSOC));

        return $result;
    }*/

    /**
     * return entries from a personal list
     *
     * @param unknown_type $_list
     * @param unknown_type $_owner
     * @param unknown_type $_filter
     * @param unknown_type $_sort
     * @param unknown_type $_dir
     * @param unknown_type $_limit
     * @param unknown_type $_start
     * @return Addressbook_ContactSet
     */
//    public function getContactsByListOwner($_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
//    {
//        $currentAccount = Zend_Registry::get('currentAccount');
//
//        switch($_owner) {
//            case 'alllists':
//                $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ANY_GRANTS);
//
//                if(empty($acl)) {
//                    return false;
//                }
//
//                $listOwner = array_keys($acl);
//
//                break;
//
//            case 'sharedlists':
//                $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::GROUP_GRANTS);
//
//                if(empty($acl)) {
//                    return false;
//                }
//
//                $listOwner = array_keys($acl);
//
//                break;
//
//            case 'otherlists':
//                $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ACCOUNT_GRANTS);
//
//                if(empty($acl)) {
//                    return false;
//                }
//
//                $listOwner = array_keys($acl);
//
//                break;
//
//            default:
//                if($_owner != $currentAccount->account_id && !$this->egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
//                    throw new Exception("access to addressbook $_owner by $currentAccount->account_id denied.");
//                }
//
//                $listOwner = $_owner;
//
//                break;
//        }
//
//        $contactOwner = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ);
//
//        $db = Zend_Registry::get('dbAdapter');
//
//        $select = $db->select()
//        ->from('egw_addressbook2list', array())
//        ->order($_sort . ' ' . $_dir)
//        ->join('egw_addressbook','egw_addressbook.contact_id = egw_addressbook2list.contact_id')
//        ->join('egw_addressbook_lists','egw_addressbook_lists.list_id = egw_addressbook2list.list_id')
//        ->where('egw_addressbook_lists.list_owner IN (?)', $listOwner)
//        ->where('egw_addressbook.contact_owner IN (?)', array_keys($contactOwner))
//        ->limit($limit, $start);
//        /*
//         $select = $db->select()
//         ->from('egw_addressbook2list', array())
//         ->order($_sort . ' ' . $_dir)
//         ->join(array('contact_data' => 'egw_addressbook'),'contact_data.contact_id = egw_addressbook2list.contact_id')
//         ->join(array('list_data' => 'egw_addressbook'),'list_data.contact_id = egw_addressbook2list.list_id', array())
//         ->where('list_data.contact_id = ?', $_list)
//         ->where('list_data.contact_owner IN (?)', array_keys($acl))
//         ->where('contact_data.contact_owner IN (?)', array_keys($acl))
//         ->limit($limit, $start);
//         */
//        //error_log("getContactsByListQuery:: " . $select->__toString());
//
//        $stmt = $db->query($select);
//
//        $result = new Addressbook_ContactSet($stmt->fetchAll(Zend_Db::FETCH_ASSOC));
//
//        return $result;
//    }

/*    public function getListsByOwner($_owner, $_filter, $_sort, $_dir, $_limit, $_start)
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        switch($_owner) {
            case 'alllists':
                $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ANY_GRANTS);

                if(empty($acl)) {
                    return false;
                }

                $listOwner = array_keys($acl);

                break;

            case 'sharedlists':
                $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::GROUP_GRANTS);

                if(empty($acl)) {
                    return false;
                }

                $listOwner = array_keys($acl);

                break;

            case 'otherlists':
                $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ACCOUNT_GRANTS);

                if(empty($acl)) {
                    return false;
                }

                $listOwner = array_keys($acl);

                break;

            default:
                if($_owner != $currentAccount->account_id && !$this->egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
                    throw new Exception("access to addressbook $_owner by $currentAccount->account_id denied.");
                }

                $listOwner = $_owner;

                break;
        }
        
        $where  = array(
            $this->listsTable->getAdapter()->quoteInto('list_owner IN (?)', $listOwner)
        );
        
        if($_filter !== NULL) {
            $where[] = $this->listsTable->getAdapter()->quoteInto('(list_name LIKE ?)', '%' . $_filter . '%');
        }

        $result = $this->listsTable->fetchAll($where, "$_sort $_dir", $_limit, $_start);

        return $result;
    }*/

    public function getAccounts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $egwbaseContainer = Egwbase_Container::getInstance();
        
        $internalContainer = $egwbaseContainer->getInternalContainer('addressbook');
        
        $result = $this->getContactsByAddressbookId($internalContainer->container_id, $_filter, $_sort, $_dir, $_limit, $_start);
        
        return $result;
    }

    public function getCountOfAccounts()
    {
        $result = $this->contactsTable->getAdapter()->fetchOne('SELECT count(*) FROM egw_addressbook WHERE account_id IS NOT NULL');

        return $result;
    }
    
    public function getSharedAddressbooks() {
        $sharedAddressbooks = Egwbase_Container::getInstance()->getSharedContainer('addressbook');
                
        return $sharedAddressbooks;
    }

    /**
     * get all shared addressbooks
     *
     * @return unknown
     */
/*  public function getSharedAddressbooks_14()
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::GROUP_GRANTS);

        $result = array();

        foreach($acl as $groupId => $rights) {
            $groupInfo = new stdClass();
            $groupInfo->id = $groupId;
            $groupInfo->rights = $rights;
            $groupInfo->name = 'Group ' . $groupId;

            $result[$groupId] = $groupInfo;
        }

        return $result;
    }*/
    
    public function getOtherUsers() {
        $rows = Egwbase_Container::getInstance()->getOtherUsers('addressbook');

        $accountData = array();

        foreach($rows as $account) {
            $accountData[] = array(
                'account_id'      => $account['account_id'],
                'account_loginid' => 'loginid',
                'account_name'    => 'Account ' . $account['account_id']
            );
        }

        $result = new Egwbase_Record_RecordSet($accountData, 'Egwbase_Record_Account');
        
        return $result;
    }
    
/*    public function getOtherUsers_14()
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ACCOUNT_GRANTS);

        $result = array();

        foreach($acl as $groupId => $rights) {
            $groupInfo = new stdClass();
            $groupInfo->id = $groupId;
            $groupInfo->rights = $rights;
            $groupInfo->name = 'Account ' . $groupId;

            $result[$groupId] = $groupInfo;
        }

        return $result;
    } */
    
    public function getAddressbooksByOwner($_owner) {
        $personalAddressbooks = Egwbase_Container::getInstance()->getPersonalContainer('addressbook', $_owner);
                
        return $personalAddressbooks;
    }

    protected function _getContactsFromTable(array $_where, $_filter, $_sort, $_dir, $_limit, $_start)
    {
        $where = $_where;

        if($_filter !== NULL) {
            $where[] = $this->contactsTable->getAdapter()->quoteInto('(n_family LIKE ? OR n_given LIKE ? OR org_name LIKE ? or contact_email LIKE ?)', '%' . $_filter . '%');
        }

        $result = $this->contactsTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);
         
        return $result;
    }


}
