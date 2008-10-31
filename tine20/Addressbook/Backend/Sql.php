<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * sql backend class for the addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Backend_Sql extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     *
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'addressbook', 'Addressbook_Model_Contact');
    }
    
    /**
     * fetch one contact identified by contactid
     *
     * @param   int $_userId
     * @return  Addressbook_Model_Contact 
     * @throws  Addressbook_Exception_NotFound if contact not found
     */
    public function getByUserId($_userId)
    {
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'addressbook')->where($this->_db->quoteInto('account_id = ?', $_userId));
        $row = $this->_db->fetchRow($select);
        if (! $row) {
            throw new Addressbook_Exception_NotFound('Contact with user id ' . $_userId . ' not found.');
        }
        $result = new Addressbook_Model_Contact($row);
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
        $_select->where($this->_db->quoteInto('container_id IN (?)', $_filter->container));
        
        $_filter->appendFilterSql($_select);
    }    
}
