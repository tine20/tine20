<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * sql backend class for the addressbook
 *
 * @package     Addressbook
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
     * Holds instance of current account
     *
     * @var Tinebase_Account_Model_Account
     */
    protected $_currentAccount;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->contactsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'addressbook'));
        $this->_currentAccount = Zend_Registry::get('currentAccount');
    }

    /**
     * add or updates a contact
     *
     * @param int $_contactOwner the owner of the addressbook entry
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @param int $_contactId the contact to update, if NULL the contact gets added
     * @todo check acl when adding contact
     * @return Addressbook_Model_Contact
     */
    public function saveContact(Addressbook_Model_Contact $_contactData)
    {
        if((int)$_contactData->contact_owner < 0) {
            throw new UnderflowException('contact_owner can not be empty');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_contactData->contact_owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('write access to new addressbook denied');
        }
        
        $accountId   = Zend_Registry::get('currentAccount')->accountId;
        $currentAccount = Zend_Registry::get('currentAccount');

        $contactData = $_contactData->toArray();
        $contactData['contact_tid'] = 'n';
        unset($contactData['contact_id']);
        
        $db = $this->contactsTable->getAdapter();
        
        try {
            $db->beginTransaction();
            
            if($_contactData->contact_id === NULL) {
                // create new contact
                $_contactData->contact_id = $this->contactsTable->insert($contactData);
            } else {
                // update existing contact
                $oldContactData = $this->getContactById($_contactData->contact_id);
                if(!Zend_Registry::get('currentAccount')->hasGrant($oldContactData->contact_owner, Tinebase_Container::GRANT_EDIT)) {
                    throw new Exception('write access to old addressbook denied');
                }
                
                // concurrency management
                $dbMods = array_diff_assoc($_contactData->toArray(), $oldContactData->toArray());
                error_log('$dbMods = ' . print_r($dbMods,true));
                
                $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
                
                if (empty($dbMods)) {
                    // nothing canged!
                    $db->rollBack();
                    return $_contactData;
                }
                
                if(!empty($dbMods['contact_modified'])) {
                    // concurrency problem. The following situations could occour:
                    // - current user did not change value, but other(s)
                    //   [$dbMod & $logedMods but equal values] -> resolvable 
                    // - current user changed value, but other(s) not
                    //   [$dbMod only] ->resolvable 
                    // - current user changed value and other(s) also
                    //   [$dbMod & $logedMods, values not equal] -> not resolvable -> real conflict
                    $from  = new Zend_Date($_contactData->contact_modified, Zend_Date::TIMESTAMP);
                    $until = new Zend_Date($oldContactData->contact_modified, Zend_Date::TIMESTAMP);
                    $logedMods = $modLog->getModifications('Addressbook', $_contactData->contact_id,
                            'Addressbook_Model_Contact', Addressbook_Backend_Factory::SQL, $from, $until);
                    $diffs = $modLog->computeDiff($logedMods);
                            
                    foreach ($diffs as $diff) {
                        $modified_attribute = $diff->modified_attribute;
                        if (!array_key_exists($modified_attribute, $dbMods)) {
                            // useres updated to same value, nothing to do.
                        } elseif ($diff->modified_from == $contactData[$modified_attribute]) {
                            unset($dbMods[$modified_attribute]);
                            // merge diff into current contact, as it was not changed in current update request.
                            $contactData[$modified_attribute] = $diff->modified_to;
                        } else {
                            // non resolvable conflict!
                            throw new Exception('concurrency confilict!');
                        }
                    }
                    unset($dbMods['contact_modified']);
                }
                
                // update database
                $now = new Zend_Date();
                $contactData['contact_modified'] = $now->getTimestamp();
                $contactData['contact_modifier'] = $this->_currentAccount->getId();
                
                $where  = array(
                    $this->contactsTable->getAdapter()->quoteInto('contact_id = ?', $_contactData->contact_id),
                );
                $result = $this->contactsTable->update($contactData, $where);
                
                // modification logging
                $modLogEntry = new Tinebase_Timemachine_Model_ModificationLog(array(
                    'application'          => 'Addressbook',
                    'record_identifier'    => $_contactData->contact_id,
                    'record_type'          => 'Addressbook_Model_Contact',
                    'record_backend'       => Addressbook_Backend_Factory::SQL,
                    'modification_time'    => $now,
                    'modification_account' => $this->_currentAccount->getId()
                ),true);
                foreach ($dbMods as $modified_attribute => $modified_to) {
                    $modLogEntry->modified_attribute = $modified_attribute;
                    $modLogEntry->modified_from      = $oldContactData->$modified_attribute;
                    $modLogEntry->modified_to        = $modified_to;
                    $modLog->setModification($modLogEntry);
                }
            
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw($e);
        }

        return $this->getContactById($_contactData->contact_id);
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

        if(!Zend_Registry::get('currentAccount')->hasGrant($oldContactData->contact_owner, Tinebase_Container::GRANT_DELETE)) {
            throw new Exception('delete access to addressbook denied');
        }
        
        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_id = ?', $contactId),
        );
         
        $result = $this->contactsTable->delete($where);

        return $result;
    }
    
    /**
     * add a new addressbook
     *
     * @param string $_name the name of the addressbook
     * @param int $_type
     * @return int the id of the new addressbook
     */
    public function addAddressbook($_name, $_type) 
    {
        $tinebaseContainer = Tinebase_Container::getInstance();
        $accountId   = Zend_Registry::get('currentAccount')->accountId;
        $allGrants = array(
            Tinebase_Container::GRANT_ADD,
            Tinebase_Container::GRANT_ADMIN,
            Tinebase_Container::GRANT_DELETE,
            Tinebase_Container::GRANT_EDIT,
            Tinebase_Container::GRANT_READ
        );
        
        if($_type == Tinebase_Container::TYPE_SHARED) {
            $addressbookId = $tinebaseContainer->addContainer('addressbook', $_name, Tinebase_Container::TYPE_SHARED, Addressbook_Backend_Factory::SQL);

            // add admin grants to creator
            $tinebaseContainer->addGrants($addressbookId, $accountId, $allGrants);
            // add read grants to any other user
            $tinebaseContainer->addGrants($addressbookId, NULL, array(Tinebase_Container::GRANT_READ));
        } else {
            $addressbookId = $tinebaseContainer->addContainer('addressbook', $_name, Tinebase_Container::TYPE_PERSONAL, Addressbook_Backend_Factory::SQL);
        
            // add admin grants to creator
            $tinebaseContainer->addGrants($addressbookId, $accountId, $allGrants);
        }
        
        return $addressbookId;
    }
    
    /**
     * delete an addressbook
     *
     * @param int $_addressbookId id of the addressbook
     * @return unknown
     */
    public function deleteAddressbook($_addressbookId)
    {
        $tinebaseContainer = Tinebase_Container::getInstance();
        
        $tinebaseContainer->deleteContainer($_addressbookId);
        
        $where = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_owner = ?', (int)$_addressbookId)
        );
        
        //$this->contactsTable->delete($where);
        
        return true;
    }
    
    /**
     * get contacts of other people, takes acl of current owner into account
     *
     * @param string $_filter the search filter
     * @param string $_sort the columnname to sort after
     * @param string $_dir the direction to sort after
     * @param int $_limit
     * @param int $_start
     * @return Zend_Db_Table_Rowset
     */
    public function getOtherPeopleContacts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL) 
    {
        $otherPeoplesContainer = Zend_Registry::get('currentAccount')->getOtherUsersContainer('addressbook', Tinebase_Container::GRANT_READ);
        
        if(count($otherPeoplesContainer) === 0) {
            return new Tinebase_Record_RecordSet(array(), 'Addressbook_Model_Contact');
        }
        
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

        $acl = $this->tinebaseAcl->getGrants($currentAccount->accountId, 'addressbook', Tinebase_Acl::READ, Tinebase_Acl::ACCOUNT_GRANTS);

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
        $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('addressbook', Tinebase_Container::GRANT_READ);
        
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
     * get total count of contacts from all addressbooks
     *
     * @param string $_filter the search filter
     * @return int count of all other users contacts
     */
    public function getCountOfAllContacts($_filter)
    {
        $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('addressbook', Tinebase_Container::GRANT_READ);
        
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

    /**
     * get list of shared contacts
     *
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return Zend_Db_Table_Rowset returns false if user has no access to shared addressbooks
     */
    public function getSharedContacts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL) 
    {
        $sharedContainer = Zend_Registry::get('currentAccount')->getSharedContainer('addressbook', Tinebase_Container::GRANT_READ);
        
        if(count($sharedContainer) === 0) {
            return new Tinebase_Record_RecordSet(array(), 'Addressbook_Model_Contact');
        }
        
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
     * @todo rework this function
     * @return int count of all other users contacts
     */
    public function getCountOfSharedContacts()
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $acl = $this->tinebaseAcl->getGrants($currentAccount->accountId, 'addressbook', Tinebase_Acl::READ, Tinebase_Acl::GROUP_GRANTS);

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
        
        $accountId = Zend_Registry::get('currentAccount')->accountId;

        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_id = ?', $contactId)
        );

        $row = $this->contactsTable->fetchRow($where);
        
        if($row === NULL) {
            throw new UnderflowException('contact not found');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($row->contact_owner, Tinebase_Container::GRANT_READ)) {
            throw new Exception('permission to contact denied');
        }
        
        $result = new Addressbook_Model_Contact($row->toArray());

        return $result;
    }

    /**
     * get contacts of user identified by account id
     *
     * @param int $_owner account id
     * @param string $_filter search filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_limit
     * @param int $_start
     * @return Zend_Db_Table_Rowset
     */
    public function getContactsByOwner($_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        $owner = (int)$_owner;
        if($owner != $_owner) {
            throw new InvalidArgumentException('$_owner must be integer');
        }
        $ownerContainer = Zend_Registry::get('currentAccount')->getPersonalContainer('addressbook', $owner, Tinebase_Container::GRANT_READ);
        
        if(count($ownerContainer) === 0) {
            return new Tinebase_Record_RecordSet(array(), 'Addressbook_Model_Contact');
        }
        
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
    
    /**
     * get count of messages readable be current account of other owners
     *
     * @param int $_owner
     * @param strin $_filter
     * @return int the total count
     */
    public function getCountByOwner($_owner, $_filter)
    {
        $owner = (int)$_owner;
        if($owner != $_owner) {
            throw new InvalidArgumentException('$_owner must be integer');
        }
        $ownerContainer = Zend_Registry::get('currentAccount')->getPersonalContainer('addressbook', $owner, Tinebase_Container::GRANT_READ);
        
        if(count($ownerContainer) === 0) {
            return 0;
        }
        
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
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_addressbookId, Tinebase_Container::GRANT_READ)) {
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
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($addressbookId, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access denied to addressbook');
        }
        
        $where = array(
            $this->contactsTable->getAdapter()->quoteInto('contact_owner = ?', $addressbookId)
        );
                
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->contactsTable->getTotalCount($where);

        return $result;
    }
    
    protected function _addQuickSearchFilter($_where, $_filter)
    {
        if(!empty($_filter)) {
            $_where[] = $this->contactsTable->getAdapter()->quoteInto('(n_family LIKE ? OR n_given LIKE ? OR org_name LIKE ? or contact_email LIKE ?)', '%' . trim($_filter) . '%');
        }
        
        return $_where;
    }

    protected function _getContactsFromTable(array $_where, $_filter, $_sort, $_dir, $_limit, $_start)
    {
        $where = $this->_addQuickSearchFilter($_where, $_filter);

        $rows = $this->contactsTable->fetchAll($where, $_sort, $_dir, $_limit, $_start);

        $result = new Tinebase_Record_RecordSet($rows->toArray(), 'Addressbook_Model_Contact', true);
        
        return $result;
    }
}
