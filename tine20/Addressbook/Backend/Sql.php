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
     * get list of contacts from given addressbooks
     *
     * @param  Tinebase_Record_RecordSet $_container  container id's to read the contacts from
     * @param  Addressbook_Model_ContactFilter  $_filter     string to search for in contacts
     * @param  Tinebase_Model_Pagination $_pagination 
     * @return Tinebase_Record_RecordSet subtype Addressbook_Model_Contact
     */
    public function search(Addressbook_Model_ContactFilter $_filter, Tinebase_Model_Pagination $_pagination)
    {
        if (count($_filter->container) === 0) {
            throw new Exception('$_container can not be empty');
        }
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'addressbook')
            ->where($this->_db->quoteInto('owner IN (?)', $_filter->container));
        
        //$this->_addFilter($select, $_filter);
        $_filter->appendFilterSql($select);
        $_pagination->appendPagination($select);
        
        $rows = $this->_db->fetchAssoc($select);
        $result = new Tinebase_Record_RecordSet('Addressbook_Model_Contact', $rows, true);

        return $result;
    }
    
    /**
     * get total count of contacts from given addressbooks
     *
     * @param  Tinebase_Record_RecordSet $_container container id's to read the contacts from
     * @param  Addressbook_Model_ContactFilter  $_filter the search filter
     * @return int                       count of all other users contacts
     */
    public function searchCount(Addressbook_Model_ContactFilter $_filter)
    {
        if (count($_filter->container) === 0) {
            return 0;
        }
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'addressbook', array('count' => 'COUNT(*)'))
            ->where($this->_db->quoteInto('owner IN (?)', $_filter->container));
        
        //$this->_addFilter($select, $_filter);
        $_filter->appendFilterSql($select);
        
        $result = $this->_db->fetchOne($select);
        return $result;
    }
    
    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select           $_select current where filter
     * @param  Addressbook_Model_ContactFilter $_filter the string to search for
     * @return void
     */
    protected function _addFilter (Zend_Db_Select $_select, Addressbook_Model_ContactFilter $_filter)
    {
        $_select->where($this->_db->quoteInto('(n_family LIKE ? OR n_given LIKE ? OR org_name LIKE ? or email LIKE ?)', '%' . trim($_filter->query) . '%'));
        if (! empty($_filter->tag)) {
            Tinebase_Tags::appendSqlFilter($_select, $_filter->tag);
        }
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
        // tags and notes are not property of this backend
        unset($contactData['tags']);
        unset($contactData['notes']);
        
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
        unset($contactData['notes']);
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
     * fetch one contact identified by contactid
     *
     * @param int $_userId
     * @return Addressbook_Model_Contact 
     * 
     * @todo add test
     */
    public function getByUserId($_userId)
    {
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'addressbook')->where($this->_db->quoteInto('account_id = ?', $_userId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new UnderflowException('contact with user id ' . $_userId . ' not found');
        }
        $result = new Addressbook_Model_Contact($row);
        return $result;
    }
    
    /**
     * Returns a set of contacts identified by their id's
     * 
     * @param  array $_ids array of int
     * @return Tinebase_Record_RecordSet of Addressbook_Model_Contact
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
