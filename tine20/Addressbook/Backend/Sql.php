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
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct ()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
    }
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone ()
    {
        
    }
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
    public static function getInstance ()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Backend_Sql();
        }
        return self::$_instance;
    }
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
                $oldContactData = $this->getById($_contactData->id);
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

        return $this->getById($_contactData->id);
    }*/
    /**
     * get list of contacts from given addressbooks
     *
     * @param  Tinebase_Record_RecordSet $_container  container id's to read the contacts from
     * @param  Addressbook_Model_Filter  $_filter     string to search for in contacts
     * @param  Tinebase_Model_Pagination $_pagination 
     * @return Tinebase_Record_RecordSet subtype Addressbook_Model_Contact
     */
    public function search(Addressbook_Model_Filter  $_filter, Tinebase_Model_Pagination $_pagination)
    {
        if (count($_filter->container) === 0) {
            throw new Exception('$_container can not be empty');
        }
        $select = $this->_db->select();
        $select->where($this->_db->quoteInto('owner IN (?)', $_filter->container->getArrayOfIds()));
        $result = $this->_getsFromTable($select, $_filter, $_pagination);
        return $result;
    }
    /**
     * get total count of contacts from given addressbooks
     *
     * @param  Tinebase_Record_RecordSet $_container container id's to read the contacts from
     * @param  Addressbook_Model_Filter  $_filter the search filter
     * @return int                       count of all other users contacts
     */
    public function searchCount(Addressbook_Model_Filter $_filter)
    {
        if (count($_filter->container) === 0) {
            throw new Exception('$_container can not be empty');
        }
        $select = $this->_db->select();
        $select->from(SQL_TABLE_PREFIX . 'addressbook', array('count' => 'COUNT(*)'));
        $select->where($this->_db->quoteInto('owner IN (?)', $_filter->container->getArrayOfIds()));
        $this->_addFilter($select, $_filter);
        $result = $this->_db->fetchOne($select);
        return $result;
    }
    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select           $_select current where filter
     * @param  Addressbook_Model_Filter $_filter the string to search for
     * @return void
     */
    protected function _addFilter (Zend_Db_Select $_select, Addressbook_Model_Filter $_filter)
    {
        $_select->where($this->_db->quoteInto('(n_family LIKE ? OR n_given LIKE ? OR org_name LIKE ? or email LIKE ?)', '%' . trim($_filter->query) . '%'));
        if (! empty($_filter->tag)) {
            Tinebase_Tags::appendSqlFilter($_select, $_filter->tag);
        }
    }
    /**
     * internal function to read the contacts from the database
     *
     * @param  Zend_Db_Select                     $_where where filter
     * @param  Addressbook_Model_Filter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet subtype Addressbook_Model_Contact
     */
    protected function _getsFromTable (Zend_Db_Select $_select, Addressbook_Model_Filter $_filter, Tinebase_Model_Pagination $_pagination)
    {
        $_select->from(SQL_TABLE_PREFIX . 'addressbook');
        $this->_addFilter($_select, $_filter);
        $_pagination->appendPagination($_select);
        $rows = $this->_db->fetchAssoc($_select);
        $result = new Tinebase_Record_RecordSet('Addressbook_Model_Contact', $rows, true);
        return $result;
    }
    /**
     * add a contact
     *
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @return Addressbook_Model_Contact
     */
    public function create(Addressbook_Model_Contact $_contactData)
    {
        if (! $_contactData->isValid()) {
            throw new Exception('invalid contact');
        }
        $contactData = $_contactData->toArray();
        if (empty($_contactData->id)) {
            unset($contactData['id']);
        }
        // tags are not property of this backend
        unset($contactData['tags']);
        $this->_db->insert(SQL_TABLE_PREFIX . 'addressbook', $contactData);
        $id = $this->_db->lastInsertId(SQL_TABLE_PREFIX . 'addressbook', 'id');
        // if we insert a contact without an id, we need to get back one
        if (empty($_contactData->id) && $id == 0) {
            throw new Exception("returned contact id is 0");
        }
        // if the account had no accountId set, set the id now
        if (empty($_contactData->id)) {
            $_contactData->id = $id;
        }
        return $this->get($_contactData->id);
    }
    /**
     * update an existing contact
     *
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @return Addressbook_Model_Contact
     */
    public function update(Addressbook_Model_Contact $_contactData)
    {
        if (! $_contactData->isValid()) {
            throw new Exception('invalid contact');
        }
        $contactId = Addressbook_Model_Contact::convertContactIdToInt($_contactData);
        $contactData = $_contactData->toArray();
        unset($contactData['id']);
        // tags are not property of this backend
        unset($contactData['tags']);
        $where = array($this->_db->quoteInto('id = ?', $contactId));
        $this->_db->update(SQL_TABLE_PREFIX . 'addressbook', $contactData, $where);
        return $this->get($contactId);
    }
    /**
     * delete contact identified by contact id
     *
     * @param int $_contactId contact ids
     * @return int the number of rows deleted
     */
    public function delete ($_contactId)
    {
        $contactId = Addressbook_Model_Contact::convertContactIdToInt($_contactId);
        $where = array($this->_db->quoteInto('id = ?', $contactId) , $this->_db->quoteInto('id = ?', $contactId));
        $result = $this->_db->delete(SQL_TABLE_PREFIX . 'addressbook', $where);
        return $result;
    }
    /**
     * fetch one contact identified by contactid
     *
     * @param int $_contactId
     * @return Addressbook_Model_Contact 
     */
    public function get ($_contactId)
    {
        $contactId = Addressbook_Model_Contact::convertContactIdToInt($_contactId);
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'addressbook')->where($this->_db->quoteInto('id = ?', $contactId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new UnderflowException('contact with id ' . $contactId . ' not found');
        }
        $result = new Addressbook_Model_Contact($row);
        return $result;
    }
    /**
     * Returns a set of contacts identified by their id's
     * 
     * @param  array $_ids array of int
     * @return Tinebase_RecordSet of Addressbook_Model_Contact
     */
    public function getMultiple(array $_contactIds)
    {
        $contacts = new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        
        if (!empty($_contactIds)) {
            $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'addressbook')->where($this->_db->quoteInto('id IN (?)', $_contactIds));
            $stmt = $this->_db->query($select);
            $contactsArray = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            
            foreach ($contactsArray as $contact) {
                $contacts->addRecord(new Addressbook_Model_Contact($contact));
            }
        }
        return $contacts;
    }
}
