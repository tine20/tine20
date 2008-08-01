<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * the class needs to access the backend interface
 *
 * @see Tasks_Backend_Interface
 */
require_once('Interface.php');

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
 * @todo    Use of special Exceptions
 * @todo    remove current account from sql backend?
 * @todo    add function for complete removal of tasks?
 */
class Tasks_Backend_Sql implements Tasks_Backend_Interface
{
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
     * @var unknown_type
     */
    protected $_tables = array();
    
    /**
     * Holds Zend_Db_Adapter_Pdo_Mysql
     *
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $_db;
    
    /**
     * Holds instance of current account
     *
     * @var Tinebase_User_Model_User
     */
    protected $_currentAccount;
    
    /**
     * Constructor
     *
     */
    public function __construct()
    {
        // fix table prefixes
        foreach ($this->_tableNames as $basename => $name) {
            $this->_tableNames[$basename] = SQL_TABLE_PREFIX . $name;
        }
        
        $this->_db = Zend_Registry::get('dbAdapter');
        try {
            $this->_currentAccount = Zend_Registry::get('currentAccount');
        } catch ( Zend_Exception $e ) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . 'no account available: ' . $e->getMessage());
        }
    }
    
    /**
     * Search for tasks matching given filter
     *
     * @param Tasks_Model_Filter $_filter
     * @param Tasks_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tasks_Model_Filter $_filter, Tasks_Model_Pagination $_pagination)
    {
        //Zend_Registry::get('logger')->debug(print_r($_filter->toArray(),true));
        $TaskSet = new Tinebase_Record_RecordSet('Tasks_Model_Task');
        
        // empty means, that e.g. no shared containers exist
        if (empty($_filter->container)) {
            return $TaskSet;
        }
        
        // build query
        $select = $this->_getSelect();

        if (!empty($_pagination->limit)) {
            $select->limit($_pagination->limit, $_pagination->start);
        }
        if (!empty($_pagination->sort)){
            if ($_pagination->sort == 'due') {
                $select->order('is_due ' . ($_pagination->dir == 'DESC' ? 'ASC' : 'DESC'));
            } 
            $select->order($_pagination->sort . ' ' . $_pagination->dir);
        }
        
        $this->_addFilter($select, $_filter);
                          

        $stmt = $this->_db->query($select);
        $Tasks = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        foreach ($Tasks as $TaskArray) {
            $Task = new Tasks_Model_Task($TaskArray, true, true);
            $TaskSet->addRecord($Task);
            //error_log(print_r($Task->toArray(),true));
        }
        return $TaskSet;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tasks_Model_Filter $_filter
     * @return int
     */
    public function searchCount(Tasks_Model_Filter $_filter)
    {
        if (count($_filter->container) === 0) {
            return 0;
        }
        $select = $this->_getSelect(TRUE);
        $this->_addFilter($select, $_filter);
        $result = $this->_db->fetchOne($select);
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' count: ' . $select->__toString());
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' count: ' . $result);
        
        return $result;
    }
    
    /**
     * Return a single Task
     *
     * @param string $_id
     * @return Tasks_Model_Task task
     * @throws Exception if task could not be found
     */
    public function get($_id)
    {
        $stmt = $this->_db->query($this->_getSelect()
            ->where($this->_db->quoteInto('tasks.id = ?', $_id))
        );
        
        $TaskArray = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        if (empty($TaskArray)) {
            throw new Exception("Task with uid: $_id not found!");
        }
        
        $Task = new Tasks_Model_Task($TaskArray[0], true, true); 
        return $Task;
    }
    
    /**
     * returns a set of tasks identified by their id's
     * 
     * @param  array $_ids
     * @return Tinebase_RecordSet of Tasks_Model_Task
     */
    public function getMultiple(array $_ids)
    {
        $taskSet = new Tinebase_Record_RecordSet('Tasks_Model_Task');
        
        if (!empty($_ids)) {
            $stmt = $this->_db->query($this->_getSelect()
                ->where($this->_db->quoteInto('tasks.id IN (?)', $_ids))
            );
            
            $taskArray = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            
            foreach ($taskArray as $task) {
                $taskSet->addRecord(new Tasks_Model_Task($task));
            }
        }
        return $taskSet;
    }
    
    /**
     * Returns a common select Object
     * 
     * @return Zend_Db_Select
     */
    protected function _getSelect($_getCount = FALSE)
    {
        $select = $this->_db->select()
            ->where('tasks.is_deleted = FALSE');
        
        $tablename = array('tasks' => $this->_tableNames['tasks']);
        
        if ($_getCount) {
            $fields = array('count' => 'COUNT(tasks.id)');
            $select->from($tablename, $fields) 
                ->joinLeft(array('status'  => $this->_tableNames['status']), 'tasks.status_id = status.id', array());
        } else {
            $fields = array(
                'tasks.*', 
                'contact' => 'GROUP_CONCAT(DISTINCT contact.contact_id)',
                'is_due'  => 'LENGTH(tasks.due)',
                //'is_open' => 'status.status_is_open',
            );
            $select->from($tablename, $fields)
                ->joinLeft(array('contact' => $this->_tableNames['contact']), 'tasks.id = contact.task_id', array())
                ->joinLeft(array('status'  => $this->_tableNames['status']), 'tasks.status_id = status.id', array())
                ->group('tasks.id');
        }

        return $select;
    }

    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select           $_select current where filter
     * @param  Crm_Model_LeadFilter $_filter the string to search for
     * @return void
     */
    protected function _addFilter(Zend_Db_Select $_select, Tasks_Model_Filter $_filter)
    {
        $_select->where($this->_db->quoteInto('tasks.container_id IN (?)', $_filter->container));
                                
        if(!empty($_filter->query)){
            $_select->where($this->_db->quoteInto('(tasks.summary LIKE ? OR tasks.description LIKE ?)', '%' . $_filter->query . '%'));
        }
        if(!empty($_filter->status)){
            $_select->where($this->_db->quoteInto('tasks.status_id = ?',$_filter->status));
        }
        if(!empty($_filter->organizer)){
            $_select->where($this->_db->quoteInto('tasks.organizer = ?', (int)$_filter->organizer));
        }
        if(isset($_filter->showClosed) && $_filter->showClosed){
            // nothing to filter
        } else {
            $_select->where('status.status_is_open = TRUE OR tasks.status_id IS NULL');
        }
    }        
    
    /**
     * Create a new Task
     *
     * @param Tasks_Model_Task $_task
     * @return Tasks_Model_Task
     */
    public function create(Tasks_Model_Task $_task)
    {
        if ( empty($_task->id) ) {
        	$newId = $_task->generateUID();
        	$_task->setId($newId);
        }
        Tinebase_Timemachine_ModificationLog::setRecordModData($_task, 'create');
        $taskParts = $this->seperateTaskData($_task);
        
        try {
            $this->_db->beginTransaction();
            $tasksTable = $this->getTableInstance('tasks');
            $tasksTable->insert($taskParts['tasks']);
            $this->insertDependentRows($taskParts);
            $this->_db->commit();

            return $this->get($_task->getId());
            
        } catch (Exception $e) {
        	echo $e;
            $this->_db->rollBack();
            throw($e);
        }
    }
    
    
    /**
     * Upate an existing Task
     *
     * @param Tasks_Model_Task $_task
     * @return Tasks_Model_Task
     */ 
    public function update(Tasks_Model_Task $_task)
    {
        try {
            $this->_db->beginTransaction();
            
            $oldTask = $this->get($_task->id);
            
            $dbMods = array_diff_assoc($oldTask->toArray(), $_task->toArray());
            $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
            
            if (empty($dbMods)) {
                // nothing canged!
                $this->_db->rollBack();
                return $_task;
            }
            
            // concurrency management            
            if(!empty($dbMods['last_modified_time'])) {
                $loggedMods = $modLog->getModifications('Tasks', $_task->id,
                        'Tasks_Model_Task', Tasks_Backend_Factory::SQL, $_task->last_modified_time, $oldTask->last_modified_time);
                $diffs = $modLog->computeDiff($loggedMods);

                foreach ($diffs as $diff) {
                    $modified_attribute = $diff->modified_attribute;
                    if (!array_key_exists($modified_attribute, $dbMods)) {
                        // user updated to same value, nothing to do.
                    }  elseif ($diff->old_value == $_task->$modified_attribute ) {
                        unset($dbMods[$modified_attribute]);
                        // merge diff into current contact, as it was not changed in current update request.
                        $_task->$modified_attribute = $diff->new_value;
                    }  else {
                        // non resolvable conflict!
                        throw new Tinebase_Timemachine_Exception_ConcurrencyConflict('concurrency confilict!');
                    }
                }

                unset($dbMods['last_modified_time']);
            }
            
            // database update
            Tinebase_Timemachine_ModificationLog::setRecordModData($_task, 'update', $oldTask);
            $taskParts = $this->seperateTaskData($_task);
        
            $tasksTable = $this->getTableInstance('tasks');
            $numAffectedRows = $tasksTable->update($taskParts['tasks'], array(
                $this->_db->quoteInto('id = ?', $_task->id),
            ));
            $this->deleteDependentRows($_task->id);
            $this->insertDependentRows($taskParts);

            // modification log
            $modLogEntry = new Tinebase_Timemachine_Model_ModificationLog(array(
                'application_id'       => 'tasks',
                'record_id'            => $_task->getId(),
                'record_type'          => 'Tasks_Model_Task',
                'record_backend'       => Tasks_Backend_Factory::SQL,
                'modification_time'    => $taskParts['tasks']['last_modified_time'],
                'modification_account' => $this->_currentAccount->getId()
            ),true);
            foreach ($dbMods as $modified_attribute => $modified_to) {
                $modLogEntry->modified_attribute = $modified_attribute;
                $modLogEntry->old_value = $oldTask->$modified_attribute;
                $modLogEntry->new_value = $modified_to;
                $modLog->setModification($modLogEntry);
            }
            
            $this->_db->commit();

            return $this->get($_task->id);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw($e);
        }
    }
    
    /**
     * Deletes one or more existing Task
     *
     * @param string|array $_id
     * @return void
     * @throws Exception
     */
    public function delete($_id)
    {
        $tasksTable = $this->getTableInstance('tasks');
        $data = array(
            'is_deleted'   => true, 
            'deleted_time' => Zend_Date::now()->getIso(),
            'deleted_by'   => $this->_currentAccount->getId()
        );
        
        try {
            $this->_db->beginTransaction();
            foreach ((array)$_id as $id) {
                // NOTE: cascading delete through the use of forign keys!
                //$tasksTable->delete($tasksTable->getAdapter()->quoteInto('id = ?', $_uid));
                $tasksTable->update($data, array(
                    $this->_db->quoteInto('id = ?', $id)
                ));
            }
            $this->_db->commit();
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Returns a record as it was at a given point in history
     * 
     * @param string _id 
     * @param Zend_Date _at 
     * @return Tinebase_Record
     * @access public
     */
    public function getRecord($_id,  Zend_Date $_at)
    {
        
    }
    
    /**
     * Returns a set of records as they where at a given point in history
     * 
     * @param array _ids array of strings
     * @param Zend_Date _at 
     * @return Tinebase_Record_RecordSet
     * @access public
     */
    public function getRecords(array $_ids,  Zend_Date $_at)
    {
        
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
            $this->_tables[$_tablename] = new Tinebase_Db_Table(array('name' => $this->_tableNames[$_tablename]));
        }
        return $this->_tables[$_tablename];
    }
    
}
