<?php

/**
 * interface for contacs class
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: DbTable.php 4246 2007-03-27 22:35:56Z ralph $
 *
 */
class Addressbook_Backend_Sql implements Addressbook_Backend_Interface
{
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
        $contacts = new Addressbook_Backend_Sql_Contacts();
            
        $result = $contacts->fetchAll(NULL, "$sort $dir", $limit, $start);
        
        return $result;
    }

    public function getPersonalCount()
    {
        $contacts = new Addressbook_Backend_Sql_Contacts();
        
        $result = $contacts->getPersonalCount();

        return $result;
    }
    
    public function getPersonalList($list, $filter, $sort, $dir, $limit = NULL, $start = NULL)
    {
        $lists = new Addressbook_Backend_Sql_Lists();
        
        $listRowset = $lists->find($list);
        
        $currentList = $listRowset->current();
        
        $result = $currentList->findManyToManyRowset('Addressbook_Backend_Sql_Contacts', 'Addressbook_Backend_Sql_ListMapping');
        
        return $result;
    }
    
    public function getPersonalLists()
    {
        $lists = new Addressbook_Backend_Sql_Lists();
        
        $result = $lists->getPersonalLists();
        
        return $result;
    }

    public function getInternalContacts($filter, $sort, $dir, $limit = NULL, $start = NULL)
    {
        $where = 'account_id IS NOT NULL';
        
        $contacts = new Addressbook_Backend_Sql_Contacts();
        
        $result = $contacts->fetchAll($where, "$sort $dir", $limit, $start, TRUE);
        
        return $result;
    }

    public function getInternalCount()
    {
        $contacts = new Addressbook_Backend_Sql_Contacts();
        
        $result = $contacts->getInternalCount();

        return $result;
    }

}
