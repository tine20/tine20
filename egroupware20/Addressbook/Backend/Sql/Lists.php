<?php

/**
 * this classes provides access to the sql table egw_addressbook_lists
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/license/gpl GPL
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: DbTable.php 4246 2007-03-27 22:35:56Z ralph $
 *
 */
class Addressbook_Backend_Sql_Lists extends Zend_Db_Table_Abstract
{
    protected $_name = 'egw_addressbook_lists';
    protected $_owner = 'list_owner';
    
    public function getPersonalLists()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $where = $this->getAdapter()->quoteInto($this->_owner . ' = ?', $currentAccount->account_id);
        
        $result = parent::fetchAll($where, "list_name ASC");
        
        return $result;
    }

}
