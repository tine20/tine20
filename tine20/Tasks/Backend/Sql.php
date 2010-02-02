<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * SQL Backend for Tasks 2.0
 * 
 * The Tasks 2.0 Sql backend consists of various tables. Properties with single
 * appearance are stored in the egw_tasks table. Properties which could appear
 * more than one time are stored in corresponding tables.
 * 
 * @package     Tasks
 * @subpackage  Backend
 * 
 * @todo    remove current account from sql backend
 * @todo    add function for complete removal of tasks?
 * @todo    split backend (status/tasks)?
 */
class Tasks_Backend_Sql extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'tasks';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tasks_Model_Task';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;

    /**
     * For some said reason, Zend_Db doesn't support table prefixes. Thus each 
     * table calss needs to implement it its own.
     * 
     * @see http://framework.zend.com/issues/browse/ZF-827
     * @todo solve table prefix in Tinebase_Db (quite a bit of work)
     * @var array
     */
    protected $_tableNames = array(
        'tasks'     => 'tasks',
        'contact'   => 'tasks_contact',
        'status'    => 'tasks_status',
    );
    
    /**
     * Holds the table instances for the different tables
     *
     * @var array
     */
    protected $_tables = array();
    
    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_UnexpectedValue
     * 
     * @todo    remove autoincremental ids later
     */
    public function create(Tinebase_Record_Interface $_record) 
    {
        parent::create($_record);
        
        $taskParts = $this->seperateTaskData($_record);
        $this->insertDependentRows($taskParts);
        
        return $this->get($_record->getId());
    }
    
    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record) 
    {
        parent::update($_record);
        
        $taskParts = $this->seperateTaskData($_record);
        $this->deleteDependentRows($_record->getId());
        $this->insertDependentRows($taskParts);
        
        return $this->get($_record->getId(), TRUE);
    }
    
    /**
     * Inserts rows in dependent tables
     *
     * @param array $_taskparts
     */
    protected function insertDependentRows($_taskParts)
    {
        foreach (array('contact') as $table) {
            if (!empty($_taskParts[$table])) {
                $items = explode(',', $_taskParts[$table]);
                $TableObject = $this->getTableInstance($table);
                foreach ($items as $itemId) {
                    $TableObject->insert(array(
                        'task_id'    => $taskId,
                        $table . '_id' => $itemId
                    ));
                }
            }
        }
    }
    
    /**
     * Deletes all depended rows from a given parent task
     *
     * @param string $_parentTaskId
     * @return int number of deleted rows
     */
    protected function deleteDependentRows($_parentTaskId)
    {
        $deletedRows = 0;
        foreach (array('contact') as $table) {
            $TableObject = $this->getTableInstance($table);
            $deletedRows += $TableObject->delete(
                $this->_db->quoteInto('task_id = ?', $_parentTaskId)
            );
        }
        return $deletedRows;
    }
    
    /**
     * Seperates tasks data into the different tables
     *
     * @param Tasks_Model_Task $_task
     * @return array array of arrays
     */
    protected function seperateTaskData($_task)
    {
    	$_task->convertDates = true;
        $taskArray = $_task->toArray();
        $TableDescr = $this->getTableInstance('tasks')->info();
        $taskparts['tasks'] = array_intersect_key($taskArray, array_flip($TableDescr['cols']));
        
        foreach (array('contact') as $table) {
            if (!empty($taskArray[$table])) {
                $taksparts[$table] = $taskArray[$table];
            }
        }
        
        return $taskparts;
    }
    
    /**
     * Returns instance of given table-class
     *
     * @todo Move Migration to setup class once we have one!
     * @param string $_tablename
     * @return Tinebase_Db_Table
     */
    protected function getTableInstance($_tablename)
    {
        if (!isset($this->_tables[$_tablename])) {
            $this->_tables[$_tablename] = new Tinebase_Db_Table(array('name' => $this->_tablePrefix . $this->_tableNames[$_tablename]));
        }
        return $this->_tables[$_tablename];
    }
    
    /********************************** protected funcs **********************************/
    
    /**
     * Returns a common select Object
     * 
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        $cols = (array)$_cols;
        
        if (array_key_exists('count', $cols)) {
            $cols['count'] = "COUNT({$this->_db->quoteIdentifier('tasks.id')})";
        } else {
            $cols['is_due'] = "LENGTH({$this->_db->quoteIdentifier('tasks.due')})";
        }
        
        $select = $this->_db->select()
            ->from(array('tasks' => $this->_tablePrefix . $this->_tableNames['tasks']), $cols)
            ->joinLeft(array('status'  => $this->_tablePrefix . $this->_tableNames['status']), 'tasks.status_id = status.id', array());
            
        if ($_getDeleted !== TRUE) {
            $select->where($this->_db->quoteIdentifier('tasks.is_deleted') . ' = FALSE');
        }
        
        return $select;
    }
   
}
