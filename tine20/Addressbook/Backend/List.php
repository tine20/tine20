<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        move visibility='displayed' check from getSelect to contact filter
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
     * foreign tables (key => tablename)
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
        
        if (is_array($this->_foreignTables)) {
            $select->group($this->_tableName . '.id');
            
            foreach ($this->_foreignTables as $modelName => $join) {
                $select->joinLeft(
                    /* table  */ array($join['table'] => $this->_tablePrefix . $join['table']), 
                    /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.id') . ' = ' . $this->_db->quoteIdentifier($join['table'] . '.' . $join['joinOn']),
                    /* select */ array($modelName => 'GROUP_CONCAT(' . $this->_db->quoteIdentifier($join['table'] . '.' . $join['field']) . ')')
                );
            }
        }
        
        $select->joinLeft(
            /* table  */ array('groups' => $this->_tablePrefix . 'groups'), 
            /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.id') . ' = ' . $this->_db->quoteIdentifier('groups.list_id'),
            /* select */ array('group_id' => 'groups.id')
        );
        
        #if ($_cols == '*' || array_key_exists('jpegphoto', (array)$_cols)) {
        #    $select->joinLeft(
        #        /* table  */ array('image' => $this->_tablePrefix . 'addressbook_image'), 
        #        /* on     */ $this->_db->quoteIdentifier('image.contact_id') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.id'),
        #        /* select */ array('jpegphoto' => 'IF(ISNULL('. $this->_db->quoteIdentifier('image.image') .'), 0, 1)')
        #    );
        #}
        
        return $select;
    }
    
    /**
     * converts raw data from adapter into a single record
     *
     * @param  array $_rawData
     * @return Tinebase_Record_Abstract
     */
    protected function _rawDataToRecord(array $_rawData)
    {
        $result = parent::_rawDataToRecord($_rawData);
        
        if (!empty($result->members)) {
            $result->members = explode(',', $result->members);
        } else {
            $result->members = array();
        }
        
        return $result;
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
    
    /**
     * convert recordset, array of ids or records to array of ids
     * 
     * @param  mixed  $_mixed
     * @return array
     */
    protected function _getIdsFromMixed($_mixed)
    {
        if ($_mixed instanceof Tinebase_Record_RecordSet) { // Record set
            $ids = $_mixed->getArrayOfIds();
            
        } elseif (is_array($_mixed)) { // array
            foreach ($_mixed as $mixed) {
                if ($mixed instanceof Tinebase_Record_Abstract) {
                    $ids[] = $mixed->getId();
                } else {
                    $ids[] = $mixed;
                }
            }
            
        } else { // string
            $ids[] = $_mixed instanceof Tinebase_Record_Abstract ? $_mixed->getId() : $_mixed;
        }
        
        return $ids;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Backend_Sql_Abstract::_updateForeignKeys()
     */
    protected function _updateForeignKeys($_mode, Tinebase_Record_Abstract $_record)
    {
        //echo "Mode: $_mode" . PHP_EOL;
        
        if (is_array($this->_foreignTables)) {
            
            foreach ($this->_foreignTables as $modelName => $join) {
                $idsToAdd    = array();
                $idsToRemove = array();
                
                if (!empty($_record->$modelName)) {
                    $idsToAdd = $this->_getIdsFromMixed($_record->$modelName);
                }
                
                $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                
                if ($_mode == 'update') {
                    $select = $this->_db->select();
        
                    $select->from(array($join['table'] => $this->_tablePrefix . $join['table']), array($join['field']))
                        ->where($this->_db->quoteIdentifier($join['table'] . '.' . $join['joinOn']) . ' = ?', $_record->getId());
                        
                    $stmt = $this->_db->query($select);
                    $currentIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                    $stmt->closeCursor();
                    
                    $idsToRemove = array_diff($currentIds, $idsToAdd);
                    $idsToAdd    = array_diff($idsToAdd, $currentIds);
                }
                
                if (!empty($idsToRemove)) {
                    $where = '(' . 
                        $this->_db->quoteInto($this->_tablePrefix . $join['table'] . '.' . $join['joinOn'] . ' = ?', $_record->getId()) . 
                        ' AND ' . 
                        $this->_db->quoteInto($this->_tablePrefix . $join['table'] . '.' . $join['field'] . ' IN (?)', $idsToRemove) . 
                    ')';
                        
                    $this->_db->delete($this->_tablePrefix . $join['table'], $where);
                }
                
                foreach ($idsToAdd as $id) {
                    $recordArray = array (
                        $join['joinOn'] => $_record->getId(),
                        $join['field']  => $id
                    );
                    $this->_db->insert($this->_tablePrefix . $join['table'], $recordArray);
                }
                    
                
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            }
        }
    }
}
