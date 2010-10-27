<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * sql backend class for the addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Backend_List extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'addressbook_lists';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Addressbook_Model_List';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
    
    /**
     * foreign tables 
     * name => array(table, joinOn, field)
     *
     * @var array
     */
    protected $_foreignTables = array(
        'members'    => array(
        	'table'  => 'addressbook_list_members',
            'joinOn' => 'list_id',
            'field'  => 'contact_id'
        )
    );
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {        
        $select = parent::_getSelect($_cols, $_getDeleted);
        
        $select->joinLeft(
            /* table  */ array('groups' => $this->_tablePrefix . 'groups'), 
            /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.id') . ' = ' . $this->_db->quoteIdentifier('groups.list_id'),
            /* select */ array('group_id' => 'groups.id')
        );
        
        return $select;
    }
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Abstract $_record
     * @return array
     */
    protected function _recordToRawData($_record)
    {
        $result = parent::_recordToRawData($_record);
        
        // stored in foreign key
        unset($result['members']);
        unset($result['group_id']);

        return $result;
    }
    
    /**
     * add new members to list
     * 
     * @param  mixed  $_listId
     * @param  mixed  $_newMembers
     * @return Addressbook_Model_List
     */
    public function addListMember($_listId, $_newMembers)
    {
        $list = $this->get($_listId);
        
        if (empty($_newMembers)) {
            return $list;
        }
        
        $newMembers = $this->_getIdsFromMixed($_newMembers);
        $idsToAdd   = array_diff($newMembers, $list->members);
        
        $listId     = $this->_convertId($_listId);
        
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        foreach ($idsToAdd as $id) {
            $recordArray = array (
                $this->_foreignTables['members']['joinOn'] => $listId,
                $this->_foreignTables['members']['field']  => $id
            );
            $this->_db->insert($this->_tablePrefix . $this->_foreignTables['members']['table'], $recordArray);
        }
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        return $this->get($_listId);
    }
    
    /**
     * remove members from list
     * 
     * @param  mixed  $_listId
     * @param  mixed  $_newMembers
     * @return Addressbook_Model_List
     */
    public function removeListMember($_listId, $_oldMembers)
    {
        $list = $this->get($_listId);
        
        if (empty($_oldMembers)) {
            return $list;
        }
        
        $oldMembers  = $this->_getIdsFromMixed($_oldMembers);
        $idsToRemove = array_intersect($list->members, $oldMembers);
        $listId      = $this->_convertId($_listId);
        
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        if (!empty($idsToRemove)) {
            $where = '(' . 
                $this->_db->quoteInto($this->_tablePrefix . $this->_foreignTables['members']['table'] . '.' . $this->_foreignTables['members']['joinOn'] . ' = ?', $listId) . 
                ' AND ' . 
                $this->_db->quoteInto($this->_tablePrefix . $this->_foreignTables['members']['table'] . '.' . $this->_foreignTables['members']['field'] . ' IN (?)', $idsToRemove) . 
            ')';
                
            $this->_db->delete($this->_tablePrefix . $this->_foreignTables['members']['table'], $where);
        }
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        return $this->get($_listId);
    }
}
