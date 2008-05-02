<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
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
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->contactsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'addressbook'));
        
        // removed current account object (isn't used at the moment in this class)
        //$this->_currentAccount = Zend_Registry::get('currentAccount');
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Addressbook_Backend_Sql
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Backend_Sql
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Backend_Sql;
        }
        
        return self::$_instance;
    }
    
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
    protected $_currentAccount = NULL;

    /**
     * add or updates a contact
     *
     * This functions gets removed, when Cornelius move the history stuff to it's final location
     * 
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @deprecated
     * @return Addressbook_Model_Contact
     */
/*    private function saveContact(Addressbook_Model_Contact $_contactData)
    {
        if((int)$_contactData->owner < 0) {
            throw new UnderflowException('owner can not be empty');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_contactData->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('write access to new addressbook denied');
        }
        
        $accountId   = Zend_Registry::get('currentAccount')->getId();
        $currentAccount = Zend_Registry::get('currentAccount');

        $contactData = $_contactData->toArray();
        $contactData['tid'] = 'n';
        unset($contactData['id']);
        
        $db = $this->contactsTable->getAdapter();
        
        try {
            $db->beginTransaction();
            
            if($_contactData->id === NULL) {
                // create new contact
                $_contactData->id = $this->contactsTable->insert($contactData);
            } else {
                // update existing contact
                $oldContactData = $this->getContactById($_contactData->id);
                if(!Zend_Registry::get('currentAccount')->hasGrant($oldContactData->owner, Tinebase_Container::GRANT_EDIT)) {
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
                    $logedMods = $modLog->getModifications('Addressbook', $_contactData->id,
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
                    $this->contactsTable->getAdapter()->quoteInto('id = ?', $_contactData->id),
                );
                $result = $this->contactsTable->update($contactData, $where);
                
                // modification logging
                $modLogEntry = new Tinebase_Timemachine_Model_ModificationLog(array(
                    'application'          => 'Addressbook',
                    'record_identifier'    => $_contactData->id,
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

        return $this->getContactById($_contactData->id);
    }*/

    /**
     * get list of contacts from given addressbooks
     *
     * @param  Tinebase_Record_RecordSet $_container  container id's to read the contacts from
     * @param  Addressbook_Model_Filter  $_filter     string to search for in contacts
     * @param  Tinebase_Model_Pagination $_pagination 
     * @return Tinebase_Record_RecordSet subtype Addressbook_Model_Contact
     */
    public function getContacts(Tinebase_Record_RecordSet $_container, Addressbook_Model_Filter $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL)
    {
        if(count($_container) === 0) {
            throw new Exception('$_container can not be empty');
        }        
        $select = $this->contactsTable->select();
        $select->where($this->contactsTable->getAdapter()->quoteInto('owner IN (?)', 
            $_container->getArrayOfIds()));

        $result = $this->_getContactsFromTable($select, $_filter, $_pagination);
         
        return $result;
    }

    /**
     * get total count of contacts from given addressbooks
     *
     * @param  Tinebase_Record_RecordSet $_container container id's to read the contacts from
     * @param  Addressbook_Model_Filter  $_filter the search filter
     * @return int                       count of all other users contacts
     */
    public function getCountOfContacts(Tinebase_Record_RecordSet $_container, Addressbook_Model_Filter $_filter)
    {
        if(count($_container) === 0) {
            throw new Exception('$_container can not be empty');
        }        
        
        $select = $this->contactsTable->select();
        $select->where($this->contactsTable->getAdapter()->quoteInto('owner IN (?)', 
            $_container->getArrayOfIds()));
        
        $this->_addFilter($select, $_filter);
        
        $result = $this->contactsTable->getTotalCount($select);

        return $result;
    }

    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select           $_select current where filter
     * @param  Addressbook_Model_Filter $_filter the string to search for
     * @return void
     */
    protected function _addFilter(Zend_Db_Select $_select, Addressbook_Model_Filter $_filter)
    {
        $_select->where($this->contactsTable->getAdapter()->quoteInto('(n_family LIKE ? OR n_given LIKE ? OR org_name LIKE ? or email LIKE ?)', '%' . trim($_filter->query) . '%'));
    }

    /**
     * internal function to read the contacts from the database
     *
     * @param  Zend_Db_Select                     $_where where filter
     * @param  Addressbook_Model_Filter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet subtype Addressbook_Model_Contact
     */
    protected function _getContactsFromTable(Zend_Db_Select $_select, Addressbook_Model_Filter $_filter, Tinebase_Model_Pagination $_pagination)
    {
        $this->_addFilter($_select, $_filter);

        $rows = $this->contactsTable->fetchAll($_select, $_pagination->sort, $_pagination->dir, $_pagination->limit, $_pagination->start);

        $result = new Tinebase_Record_RecordSet('Addressbook_Model_Contact', $rows->toArray(),  true);
        
        return $result;
    }
    
    /**
     * add a contact
     *
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @return Addressbook_Model_Contact
     */
    public function addContact(Addressbook_Model_Contact $_contactData)
    {
        if(!$_contactData->isValid()) {
            throw new Exception('invalid contact');
        }
        
        $contactData = $_contactData->toArray();
        
        if(empty($_contactData->id)) {
            unset($contactData['id']);
        }
        
        // tags are not property of this backend
        unset($contactData['tags']);
        
        $id = $this->contactsTable->insert($contactData);

        // if we insert a contact without an id, we need to get back one
        if(empty($_contactData->id) && $id == 0) {
            throw new Exception("returned contact id is 0");
        }
        
        // if the account had no accountId set, set the id now
        if(empty($_contactData->id)) {
            $_contactData->id = $id;
        }
        
        return $this->getContact($_contactData->id);
    }
    
    /**
     * update an existing contact
     *
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @return Addressbook_Model_Contact
     */
    public function updateContact(Addressbook_Model_Contact $_contactData)
    {
        if(!$_contactData->isValid()) {
            throw new Exception('invalid contact');
        }
        
        $contactId = Addressbook_Model_Contact::convertContactIdToInt($_contactData);
        
        $contactData = $_contactData->toArray();
        unset($contactData['id']);
        // tags are not property of this backend
        unset($contactData['tags']);
        
        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('id = ?', $contactId),
        );
        
        $id = $this->contactsTable->update($contactData, $where);
        
        return $this->getContact($contactId);
    }
    
    /**
     * delete contact identified by contact id
     *
     * @param int $_contactId contact ids
     * @return int the number of rows deleted
     */
    public function deleteContact($_contactId)
    {
        $contactId = Addressbook_Model_Contact::convertContactIdToInt($_contactId);

        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('id = ?', $contactId),
        );
         
        $result = $this->contactsTable->delete($where);

        return $result;
    }
    
    /**
     * fetch one contact identified by contactid
     *
     * @param int $_contactId
     * @return Addressbook_Model_Contact 
     */
    public function getContact($_contactId)
    {
        $contactId = Addressbook_Model_Contact::convertContactIdToInt($_contactId);
        
        $where  = array(
            $this->contactsTable->getAdapter()->quoteInto('id = ?', $contactId)
        );

        $row = $this->contactsTable->fetchRow($where);
        
        if($row === NULL) {
            throw new UnderflowException('contact not found');
        }
        
        $result = new Addressbook_Model_Contact($row->toArray());

        return $result;
    }
    
}
