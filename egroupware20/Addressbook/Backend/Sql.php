<?php

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
	protected $contactsTable;
	
	public function __construct()
	{
		$this->contactsTable = new Addressbook_Backend_Sql_Contacts();
	}
	
    public function deleteContactsById(array $_contacts)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        $egwbaseAcl = Egwbase_Acl::getInstance();
        
        $acl = $egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::DELETE);
        
        // delete the requested contact_id only if the contact_owner matches the current users acl
        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_id = (?)', $_contacts),
            $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', array_keys($acl))
        );
       
        $result = parent::delete($where);
        
        return $result;
    }
    
    public function getAllOtherPeopleContacts($_filter, array $_contactType, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        $egwbaseAcl = Egwbase_Acl::getInstance();
        
        $acl = $egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ACCOUNT_GRANTS);
        $groupIds = array_keys($acl);
        
        $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', $groupIds);

        $requestedContactType = array();
        if($_contactType['displayContacts'] == TRUE) {
            $requestedContactTypes[]  = 'n';
        }
        if($_contactType['displayLists'] == TRUE) {
            $requestedContactTypes[]  = 'l';
        }
        $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_tid IN (?)', $requestedContactTypes);

        #error_log(print_r($where, true));

        $result = $this->contactsTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);
        
        return $result;
    }

    public function getCountOfAllOtherPeopleContacts()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $egwbaseAcl = Egwbase_Acl::getInstance();
        
        $acl = $egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ACCOUNT_GRANTS);
        $groupIds = array_keys($acl);
        
        $result = $this->contactsTable->getCountByAcl($groupIds);

        return $result;
    }

    public function getAllSharedContacts($_filter, array $_contactType, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        $egwbaseAcl = Egwbase_Acl::getInstance();
        
        $acl = $egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::GROUP_GRANTS);
        $groupIds = array_keys($acl);
        
        $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', $groupIds);

        $requestedContactType = array();
        if($_contactType['displayContacts'] == TRUE) {
            $requestedContactTypes[]  = 'n';
        }
        if($_contactType['displayLists'] == TRUE) {
            $requestedContactTypes[]  = 'l';
        }
        $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_tid IN (?)', $requestedContactTypes);

        #error_log(print_r($where, true));

        $result = $this->contactsTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);
        
        return $result;
    }

    public function getCountOfAllSharedContacts()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $egwbaseAcl = Egwbase_Acl::getInstance();
        
        $acl = $egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::GROUP_GRANTS);
        $groupIds = array_keys($acl);
        
        $result = $this->contactsTable->getCountByAcl($groupIds);

        return $result;
    }
    
    public function getContactsById(array $_contacts)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        $egwbaseAcl = Egwbase_Acl::getInstance();
        
        $acl = $egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ);
        
        // return the requested contact_id only if the contact_owner matches the current users acl
        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_id = ?', $_contacts),
            $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', array_keys($acl))
        );
        
        $result = $this->contactsTable->fetchall($where, NULL, NULL, NULL, NULL);
        
        return $result;
    }
    
    public function getContactsByOwner($_owner, $_filter, array $_contactType, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        error_log("getContactsByOwner :: $_owner");
        $currentAccount = Zend_Registry::get('currentAccount');
        $where = array();
        
        $egwbaseAcl = Egwbase_Acl::getInstance();
        if($_owner == $currentAccount->account_id || $egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
            $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_owner = ?', $_owner);
        } else {
            throw new Exception("access to addressbook $_owner by $currentAccount->account_id denied.");
        }
        
        $requestedContactType = array();
        if($_contactType['displayContacts'] == TRUE) {
            $requestedContactTypes[]  = 'n';
        }
        if($_contactType['displayLists'] == TRUE) {
            $requestedContactTypes[]  = 'l';
        }
        $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_tid IN (?)', $requestedContactTypes);
        error_log(print_r($where, true));
        $result = $this->contactsTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);
        
        return $result;
    }

    public function getCountByOwner($_owner)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $egwbaseAcl = Egwbase_Acl::getInstance();
        if($_owner != $currentAccount->account_id && !$egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
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
     * @return unknown
     */
    public function getContactsByList($_list, $_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $egwbaseAcl = Egwbase_Acl::getInstance();
        if($_owner != $currentAccount->account_id && !$egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
            throw new Exception("access to addressbook $_owner by $currentAccount->account_id denied.");
        }

        $acl = $egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ);

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
        
        //error_log($select->__toString());
            
        $stmt = $db->query($select);
        
        $config = array(
            'table'     => 'egw_addressbook',
            'data'      => $stmt->fetchAll(Zend_Db::FETCH_ASSOC),
            'rowClass'  => 'Zend_Db_Table_Row',
            'stored'    => true
        );
        
        $rowset = new Zend_Db_Table_Rowset($config);
        
        return $rowset;
        
        /*
         * you could also use this piece of code, to fetch the joined data, but this way you can NOT define
         * any limit, where or order statements
        $lists = new Addressbook_Backend_Sql_Lists();
        
        $listRowset = $lists->find($list);
        
        $currentList = $listRowset->current();
        
        $result = $currentList->findManyToManyRowset('Addressbook_Backend_Sql_Contacts', 'Addressbook_Backend_Sql_ListMapping');
        
        return $result;
        
        */
    }
    
    public function getListsByOwner($_owner)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        $lists = Addressbook_Backend_Sql_Lists::getInstance();
        
        $egwbaseAcl = Egwbase_Acl::getInstance();
        if($_owner == $currentAccount->account_id || $egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
            $where[] = $lists->getAdapter()->quoteInto('list_owner = ?', $_owner);
        } else {
            throw new Exception("access to addressbook $_owner by $currentAccount->account_id denied.");
        }
        
        $result = $lists->fetchAll($where, 'list_name', 'ASC');
        
        return $result;
    }

    public function getAccounts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $where = 'account_id IS NOT NULL';
        
        $result = $this->contactsTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);
        
        return $result;
    }

    public function getCountOfAccounts()
    {
        $result = $this->contactsTable->getAdapter()->fetchOne('SELECT count(*) FROM egw_addressbook WHERE account_id IS NOT NULL');
        
        return $result;
    }
    
    public function getSharedAddressbooks()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        $egwbaseAcl = Egwbase_Acl::getInstance();
        
        $acl = $egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::GROUP_GRANTS);
        
        $result = array();
        
        foreach($acl as $groupId => $rights) {
            $groupInfo = new stdClass();
            $groupInfo->id = $groupId;
            $groupInfo->rights = $rights;
            $groupInfo->title = 'Group ' . $groupId;
            
            $result[$groupId] = $groupInfo;
        }
        
        return $result;
    }

    public function getOtherAddressbooks()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        $egwbaseAcl = Egwbase_Acl::getInstance();
        
        $acl = $egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ACCOUNT_GRANTS);
        
        $result = array();
        
        foreach($acl as $groupId => $rights) {
            $groupInfo = new stdClass();
            $groupInfo->id = $groupId;
            $groupInfo->rights = $rights;
            $groupInfo->title = 'Account ' . $groupId;
            
            $result[$groupId] = $groupInfo;
        }
        
        return $result;
    }

}
