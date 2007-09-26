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
		$this->egwbaseAcl = Egwbase_Acl::getInstance();
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
	public function saveContact($_contactOwner, Addressbook_Contact $_contactData, $_contactId = NULL)
	{
		$contactData = $_contactData->toArray();
		$contactData['contact_owner'] = $_contactOwner;
		$contactData['contact_tid'] = 'n';
		
		if($_contactId === NULL) {
			$result = $this->contactsTable->insert($contactData);
		} else {
			$currentAccount = Zend_Registry::get('currentAccount');
        
			$acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::EDIT);
        
			// update the requested contact_id only if the contact_owner matches the current users acl
			$where  = array(
				$this->contactsTable->getAdapter()->quoteInto('contact_id = (?)', $_contactId),
				$this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', array_keys($acl))
			);
        		
			$result = $this->contactsTable->update($contactData, $where);
		}
		
		return $result;
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
	public function saveList($_listOwner, Addressbook_List $_listData, $_listId = NULL)
	{
		$listData = $_listData->toArray();
		$listData['contact_owner'] = $_listOwner;
		$listData['contact_tid'] = 'l';
		
		if($_listId === NULL) {
			$result = $this->contactsTable->insert($listData);
		} else {
			$currentAccount = Zend_Registry::get('currentAccount');
        
			$acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::EDIT);
        
			// update the requested contact_id only if the contact_owner matches the current users acl
			$where  = array(
				$this->contactsTable->getAdapter()->quoteInto('contact_id = (?)', $_listId),
				$this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', array_keys($acl))
			);
        		
			$result = $this->contactsTable->update($listData, $where);
		}
		
		return $result;
	}
	
    /**
     * delete contacts identified by contact id
     *
     * @param array $_contacts list of contact ids
     * @return int the number of rows deleted
     */
    public function deleteContactsById(array $_contacts)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::DELETE);
        
        // delete the requested contact_id only if the contact_owner matches the current users acl
        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_id IN (?)', $_contacts),
            $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', array_keys($acl))
        );
       
        $result = $this->contactsTable->delete($where);
        
        return $result;
    }
    
    /**
     * get list of contacts from all other people the current user has access to
     *
     * @param string $_filter string to search for in contacts
     * @param array $_contactType filter by type (list or contact currently)
     * @param unknown_type $_sort fieldname to sort by
     * @param unknown_type $_dir sort ascending or descending (ASC | DESC)
     * @param unknown_type $_limit how many contacts to display
     * @param unknown_type $_start how many contaxts to skip
     * @return unknown The row results per the Zend_Db_Adapter fetch mode.
     */
    public function getAllOtherPeopleContacts($_filter, array $_contactType, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ACCOUNT_GRANTS);

        $groupIds = array_keys($acl);
        
        $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', $groupIds);
        
        $result = $this->_getContactsFromTable($where, $_filter, $_contactType, $_sort, $_dir, $_limit, $_start);
        
        return $result;
    }

    /**
     * get total count of all other users contacts
     *
     * @return int count of all other users contacts
     */
    public function getCountOfAllOtherPeopleContacts()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ACCOUNT_GRANTS);

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
    public function getAllContacts($_filter, array $_contactType, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ANY_GRANTS);

        $groupIds = array_keys($acl);
        
        $where[] = $this->contactsTable->getAdapter()->quoteInto('(contact_owner IN (?) OR account_id IS NOT NULL)', $groupIds);

        $result = $this->_getContactsFromTable($where, $_filter, $_contactType, $_sort, $_dir, $_limit, $_start);
        
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
    public function getAllSharedContacts($_filter, array $_contactType, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::GROUP_GRANTS);

        $groupIds = array_keys($acl);
        
        $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', $groupIds);

        $result = $this->_getContactsFromTable($where, $_filter, $_contactType, $_sort, $_dir, $_limit, $_start);
        
        return $result;
    }

    /**
     * get total count of all contacts from shared addressbooks
     *
     * @return int count of all other users contacts
     */
    public function getCountOfAllSharedContacts()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::GROUP_GRANTS);

        $groupIds = array_keys($acl);
        
        $result = $this->contactsTable->getCountByAcl($groupIds);

        return $result;
    }
    
    /**
     * fetch one contact identified by contactid
     *
     * @param array $_contacts
     * @return The row results per the Zend_Db_Adapter fetch mode, or null if no row found.
     */
    public function getContactById($_contactId)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ);
        
        // return the requested contact_id only if the contact_owner matches the current users acl
        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_id = ?', $_contactId),
            $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', array_keys($acl))
        );
        
        $result = $this->contactsTable->fetchRow($where);
        
        return $result;
    }
    
    /**
     * fetch one contact identified by contactid
     *
     * @param array $_contacts
     * @return The row results per the Zend_Db_Adapter fetch mode, or null if no row found.
     */
    public function getListById($_listId)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ);
        
        // return the requested list_id only if the contact_owner matches the current users acl
        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_id = ?', $_listId),
            $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', array_keys($acl))
        );
        
        $listData = $this->contactsTable->fetchRow($where);
        $listMembers = $this->getContactsByList($_listId, $currentAccount->account_id, NULL, 'n_family', 'ASC');
        //$result = $this->contactsTable->fetchRow($where);
        error_log(print_r($listData, true));
        //error_log(print_r($listMembers, true));
        $result = new Addressbook_List();
        
        $result->list_name = $listData->n_family;
        $result->list_description = $listData->contact_note;
        $result->list_owner = $listData->contact_owner;
        $result->list_members[] = array(
        	'contact_id'	=> '1', 
        	'n_family'		=> 'Kneschke', 
        	'contact_email' => 'lars@kneschke.de'
        );
        
        return $result;
    }
    
    public function getContactsByOwner($_owner, $_filter, array $_contactType, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        $where = array();
        
        if($_owner == $currentAccount->account_id || $this->egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
            $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_owner = ?', $_owner);
        } else {
            throw new Exception("access to addressbook $_owner by $currentAccount->account_id denied.");
        }
        
        $result = $this->_getContactsFromTable($where, $_filter, $_contactType, $_sort, $_dir, $_limit, $_start);
        	        
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
     * @return unknown
     */
    public function getContactsByList($_list, $_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        if($_owner != $currentAccount->account_id && !$this->egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
            throw new Exception("access to addressbook $_owner by $currentAccount->account_id denied.");
        }

        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ);

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
        
        if($_owner == $currentAccount->account_id || $this->egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
            $where[] = $this->listsTable->getAdapter()->quoteInto('list_owner = ?', $_owner);
        } else {
            throw new Exception("access to addressbook $_owner by $currentAccount->account_id denied.");
        }
        
        $result = $this->listsTable->fetchAll($where, 'list_name', 'ASC');
        
        return $result;
    }
	
    public function getNewListsByOwner($_owner)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        if($_owner == $currentAccount->account_id || $this->egwbaseAcl->checkPermissions($currentAccount->account_id, 'addressbook', $_owner, Egwbase_Acl::READ) ) {
            $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_owner = ?', $_owner);
        } else {
            throw new Exception("access to addressbook $_owner by $currentAccount->account_id denied.");
        }

	//	 $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_tid = ?', 'l');
		 
        $result = $this->contactsTable->fetchAll($wwhere, 'n_family', 'ASC');
        
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
        
        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::GROUP_GRANTS);
        
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
        
        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ, Egwbase_Acl::ACCOUNT_GRANTS);
        
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

    protected function _getContactsFromTable(array $_where, $_filter, $_contactType, $_sort, $_dir, $_limit, $_start)
    {
    	$where = $_where;
        $requestedContactType = array();
        
        if($_contactType['displayContacts'] == TRUE) {
            $requestedContactTypes[]  = 'n';
        }
        if($_contactType['displayLists'] == TRUE) {
            $requestedContactTypes[]  = 'l';
        }
        
        $where[] = $this->contactsTable->getAdapter()->quoteInto('contact_tid IN (?)', $requestedContactTypes);
        if($_filter !== NULL) {
        	$where[] = $this->contactsTable->getAdapter()->quoteInto('(n_family LIKE ? OR n_given LIKE ? OR org_name LIKE ? or contact_email LIKE ?)', '%' . $_filter . '%');
        }

        $result = $this->contactsTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);
    	
        return $result;
    }
    
    
}
