<?php

/**
 * this classes provides access to the sql table egw_addressbook
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Addressbook_Backend_Sql_Contacts extends Zend_Db_Table_Abstract
{
    protected $_name = 'egw_addressbook';
    protected $_owner = 'contact_owner';
    
    private static $instance = NULL;
    
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Addressbook_Backend_Sql_Contacts;
        }
        
        return self::$instance;
    }

    /**
     * create sql statement to filter by acl; handles emtpy where string and empty acl 
     *
     * @param string $_where where filter
     * @param array $_acl list of acl to match owner against; can be NULL
     * @return string sql where filter
     */
    protected function getACLStatement($_where, $_acl)
    {
        if(isset($this->_owner) && is_array($_acl)) {
            if($_where !== NULL) {
                $where = '(' . $_where . ') AND ';
            }
            $where .=  $this->getAdapter()->quoteInto($this->_owner . ' IN (?)', $_acl);
        } else {
            $where = $_where;
        }
        
        return $where;
    }
    
    /**
     * delete a list of rows(identified by primary key)
     *
     * @param array $_key primary key of the row to be deleted
     * @param array $_deleteACL delete ACL; delete rows only if owner is in in_array; can be NULL to disable ACL check
     * @return unknown
     */
    public function delete(array $_key, $_deleteACL = NULL)
    {
        //$currentAccount = Zend_Registry::get('currentAccount');
        
        $deleteSql = $this->getAdapter()->quoteInto($this->_primary[1] . ' IN (?)', $_key); 
        $where = $this->getACLStatement($deleteSql, $_deleteACL); 

        //error_log($where);
        
        $result = parent::delete($where);
        
        return $result;
    }
    
    /**
     * fetch all entries matching where parameter.
     *
     * @param string/array $_where where filter
     * @param string $_order order by 
     * @param int $_count maximum rows to return
     * @param int $_offset how many rows to skio
     * @param array $_readACL read ACL; return row only if owner is in in_array; can be NULL to disable ACL check
     * @return unknown
     */
    public function fetchAll($_where = NULL, $_order = NULL, $_dir = NULL, $_count = NULL, $_offset = NULL, $_readACL = NULL)
    {
        if($_dir !== NULL && ($_dir != 'ASC' && $_dir != 'DESC')) {
            throw new Exception('$_dir can be only ASC or DESC');
        }
        $where = $this->getACLStatement($_where, $_readACL);
        
        $result = parent::fetchAll($where, "$_order $_dir", $_count, $_offset);
        
        return $result;
    }
    
    /**
     * find row identified by primary key
     *
     * @param string $_key value of the primary key
     * @param array $_readACL read ACL; return row only if owner is in in_array; can be NULL to disable ACL check
     * @return unknown
     */
    public function find($_key, $_readACL)
    {
        $where = $this->getAdapter()->quoteInto($this->_primary[1] . ' = ?', $_key);
        return parent::fetchAll($where, NULL, NULL, NULL, $_readACL);
    }
    
    /**
     * update row identified by primary key
     *
     * @param array $_data the row data
     * @param string $_where update filter
     * @param array $_editACL edit ACL; update row only if owner is in in_array; can be NULL to disable ACL check
     * @return unknown
     */
    public function update(array $_data, $_where, $_editACL)
    {
        $where = $this->getACLStatement($_where, $_editACL);
        
        return parent::update($_data, $where);
    }
        
    /**
     * get total count of rows matching acl
     *
     * @param array $_readACL read ACL; count only rows machting owner in array
     * @return int number of rows matching acl
     */
    public function getCountByAcl($_readACL)
    {
        $where = $this->getAdapter()->quoteInto($this->_owner . ' IN (?)', $_readACL);
        
        return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name . ' WHERE ' . $where);
    }
}
       