<?php

/**
 * SQL backend class to access contacts
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/license/gpl GPL
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Addressbook_Contacts_Sql extends Zend_Db_Table_Abstract implements Addressbook_Contacts_Interface
{
    protected $_name = 'egw_addressbook';
    protected $_owner = 'contact_owner';
    
    public function delete(array $_key)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $where  = $this->getAdapter()->quoteInto($this->_primary[1] . ' IN (?)', $_key);
        $where .= $this->getAdapter()->quoteInto(' AND ' . $this->_owner . ' = ?', $currentAccount->account_id);
        
        error_log($where);
        
        parent::delete($where);
    }
    
    public function getTotalCount()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name . ' WHERE ' . $this->_owner . ' = ' . $currentAccount->account_id);
    }
    
    public function fetchAll($_where = null, $_order = null, $_count = null, $_offset = null)
    {
        if(isset($this->_owner)) {
            $currentAccount = Zend_Registry::get('currentAccount');
            
            if($_where !== NULL) {
                $where = $_where . ' AND ';
            }
            $where .=  $this->getAdapter()->quoteInto($this->_owner . ' = ?', $currentAccount->account_id);
        } else {
            $where = $_where;
        }
        
        return parent::fetchAll($where, $_order, $_count, $_offset);
    }
    
    public function find($_key)
    {
        $where = $this->getAdapter()->quoteInto($this->_primary[1] . ' = ?', $_key);
        return parent::fetchAll($where);
    }
    
    public function insert($_data)
    {
        if(isset($this->_owner)) {
            $currentAccount = Zend_Registry::get('currentAccount');
            $_data[$this->_owner] = $currentAccount->account_id;
        }
    
        return parent::insert($_data);
    }
    
    public function update(array $_data, $_where)
    {
        if(isset($this->_owner)) {
            $currentAccount = Zend_Registry::get('currentAccount');
            $where = $_where . ' AND ' . $this->getAdapter()->quoteInto($this->_owner . ' = ?', $currentAccount->account_id);
        } else {
            $where = $_where;
        }
        
        return parent::update($_data, $where);
    }
    
    public function deletePersonalContacts(array $contacts)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $where  = $this->getAdapter()->quoteInto($this->_primary[1] . ' IN (?)', $contacts);
        $where .= $this->getAdapter()->quoteInto(' AND ' . $this->_owner . ' = ?', $currentAccount->account_id);

        //error_log($where);
        
        $result = parent::delete($where);
        
        return $result;
    }
    
    public function getPersonalContacts($filter, $sort, $dir, $limit = NULL, $start = NULL)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
            
        if($_where !== NULL) {
            $where = $_where . ' AND ';
        }
        $where .=  $this->getAdapter()->quoteInto($this->_owner . ' = ?', $currentAccount->account_id);
        
        $result = parent::fetchAll($where, "$sort $dir", $limit, $start);
        
        return $result;
    }

    public function getPersonalCount()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name . ' WHERE ' . $this->_owner . ' = ' . $currentAccount->account_id);
    }
    
    public function getInternalContacts($filter, $sort, $dir, $limit = NULL, $start = NULL)
    {
        if($_where !== NULL) {
            $where = $_where . ' AND ';
        }
        $where .=  'account_id IS NOT NULL';
        
        $result = parent::fetchAll($where, "$sort $dir", $limit, $start);
        
        return $result;
    }

    public function getInternalCount()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name . ' WHERE account_id IS NOT NULL');
    }
    
}

?>
        
        