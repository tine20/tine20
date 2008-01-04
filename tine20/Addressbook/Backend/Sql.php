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
     * the constructor
     *
     */
    public function __construct()
    {
        $this->contactsTable = new Egwbase_Db_Table(array('name' => 'egw_addressbook'));
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
        
        $db = $this->contactsTable->getAdapter();
        
        try {
            $db->beginTransaction();
            if($_contactData->contact_id === NULL) {
                $_contactData->contact_id = $this->contactsTable->insert($contactData);
            } else {
                $oldContactData = $this->getContactById($_contactData->contact_id);
                if(!Zend_Registry::get('currentAccount')->hasGrant($oldContactData->contact_owner, Egwbase_Container::GRANT_EDIT)) {
                    throw new Exception('write access to old addressbook denied');
                }
                if($oldContactData->contact_modified != $_contactData->contact_modified) {
                    throw new Exception('concurrency conflict!');
                }
                
                $now = new Zend_Date();
                $contactData['contact_modified'] = $now->getTimestamp();
                
                $where  = array(
                    $this->contactsTable->getAdapter()->quoteInto('contact_id = ?', $_contactData->contact_id),
                );
    
                $result = $this->contactsTable->update($contactData, $where);
                
                $db->commit();
            }
        } catch (Exception $e) {
            $db->rollBack();
            throw($e);
        }

        return $_contactData;
    }

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
        $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('addressbook', Egwbase_Container::GRANT_READ);
        
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
    public function getCountOfAllContacts($_filter)
    {
        $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('addressbook', Egwbase_Container::GRANT_READ);
        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', $containerIds)
        );
        
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->contactsTable->getTotalCount($where);

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

    public function getContactsByOwner($_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $owner = (int)$_owner;
        if($owner != $_owner) {
            throw new InvalidArgumentException('$_owner must be integer');
        }
        $ownerContainer = Egwbase_Container::getInstance()->getPersonalContainer('addressbook', $owner);
        
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
    
    public function getCountByOwner($_owner, $_filter)
    {
        $owner = (int)$_owner;
        if($owner != $_owner) {
            throw new InvalidArgumentException('$_owner must be integer');
        }
        $ownerContainer = Egwbase_Container::getInstance()->getPersonalContainer('addressbook', $owner);
        
        $containerIds = array();
        
        foreach($ownerContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_owner IN (?)', $containerIds)
        );
        
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->contactsTable->getTotalCount($where);

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
    
    public function getCountByAddressbookId($_addressbookId, $_filter)
    {
        $addressbookId = (int)$_addressbookId;
        if($addressbookId != $_addressbookId) {
            throw new InvalidArgumentException('$_addressbookId must be integer');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($addressbookId, Egwbase_Container::GRANT_READ)) {
            throw new Exception('read access denied to addressbook');
        }
        
        $where = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_owner = ?', $addressbookId)
        );
                
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->contactsTable->getTotalCount($where);

        return $result;
    }
    
    public function getSharedAddressbooks() {
        $sharedAddressbooks = Egwbase_Container::getInstance()->getSharedContainer('addressbook');
                
        return $sharedAddressbooks;
    }
    
    public function getOtherUsers() 
    {
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
        
    public function getAddressbooksByOwner($_owner) 
    {
        $personalAddressbooks = Egwbase_Container::getInstance()->getPersonalContainer('addressbook', $_owner);
                
        return $personalAddressbooks;
    }
    
    protected function _addQuickSearchFilter($_where, $_filter)
    {
        if(!empty($_filter)) {
            $_where[] = $this->contactsTable->getAdapter()->quoteInto('(n_family LIKE ? OR n_given LIKE ? OR org_name LIKE ? or contact_email LIKE ?)', '%' . $_filter . '%');
        }
        
        return $_where;
    }

    protected function _getContactsFromTable(array $_where, $_filter, $_sort, $_dir, $_limit, $_start)
    {
        $where = $this->_addQuickSearchFilter($_where, $_filter);

        $result = $this->contactsTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);
         
        return $result;
    }

    public function getAddressbookSettings($_addressbookId)
    {
        $result = array(
            'name'      => 'addressbook name',
            'grants'    => array(
                array(
                    'accountId'     => 7,
                    'accountName'   => 'Lars Kneschke',
                    'grants'        => array (1,2,4,8)
                ),
                array(
                    'accountId'     => NULL,
                    'accountName'   => 'Anyone',
                    'grants'        => array (1)
                )
            )
        );
        
        return $result;
    }
}
