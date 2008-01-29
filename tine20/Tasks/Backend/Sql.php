<?php
/**
 * egroupware 2.0
 * 
 * @package     Tasks
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
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
 * @todo searchTasks: filter..., pageing
 * @todo Use of spechial Exceptions
 */
class Tasks_Backend_Sql implements Tasks_Backend_Interface
{
    /**
     * For some said reason, Zend_Db doesn't support table prefixes. Thus each 
     * table calss needs to implement it its own.
     * 
     * @see http://framework.zend.com/issues/browse/ZF-827
     * @todo solve table prefix in Egwbase_Db (quite a bit of work)
     * @var array
     */
    protected $_tableNames = array(
        'tasks'     => 'tasks',
        'related'   => 'tasks_related',
        'contact'   => 'tasks_contact',
        'tag'       => 'tasks_tag',
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
     * @var Egwbase_Account_Model_Account
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
        $this->_currentAccount = Zend_Registry::get('currentAccount');
        
        //temporary hack to enshure migration from egw14
        $this->getTableInstance('tasks');
    }
    
    /**
     * Search for tasks matching given filter
     *
     * @param Tasks_Model_PagnitionFilter $_filter
     * @return Egwbase_Record_RecordSet
     */
    public function searchTasks($_filter)
    {
        $TaskSet = new Egwbase_Record_RecordSet(array(), 'Tasks_Model_Task');
        
        if(empty($_filter->container)){
            return $TaskSet;
        }
        
        // build query
        // TODO: abstract filter2sql
        $select = $this->_getSelect()
            ->where($this->_db->quoteInto('tasks.container IN (?)', $_filter->container));
            
        if (!empty($_filter->limit)) {
            $select->limit($_filter->limit, $_filter->start);
        }
        if (!empty($_filter->sort)){
            $select->order($_filter->sort . ' ' . $_filter->dir);
        }
        if(!empty($_filter->query)){
            $select->where($this->_db->quoteInto('(summaray LIKE ? OR description LIKE ?)', '%' . $_filter->query . '%'));
        }
        if(!empty($_filter->status)){
            $select->where($this->_db->quoteInto('status = ?',$_filter->status));
        }
        if(!empty($_filter->organizer)){
            $select->where($this->_db->quoteInto('organizer = ?', (int)$_filter->organizer));
        }

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
     * @param Tasks_Model_PagnitionFilter $_filter
     * @return int
     */
    public function getTotalCount($_filter)
    {
        if(empty($_filter->container)) return 0;
        return $this->getTableInstance('tasks')->getTotalCount(array(
            $this->_db->quoteInto('container IN (?)', $_filter->container),
            'is_deleted = FALSE'
        ));
    }
    
    /**
     * Return a single Task
     *
     * @param string $_uid
     * @return Tasks_Model_Task task
     */
    public function getTask($_uid)
    {
        $stmt = $this->_db->query($this->_getSelect()
            ->where($this->_db->quoteInto('tasks.identifier = ?', $_uid))
        );
        
        $TaskArray = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        if (empty($TaskArray)) {
            throw new Exception("Task with uid: $_uid not found!");
        }
        
        $Task = new Tasks_Model_Task($TaskArray[0], true, array('part' => Zend_Date::ISO_8601)); 
        return $Task;
    }
    
    /**
     * Returns a common select Object
     * 
     * @return Zend_Db_Select
     */
    protected function _getSelect()
    {
        return $this->_db->select()
            ->from(array('tasks' => $this->_tableNames['tasks']), array('tasks.*', 
                'contact'    => 'GROUP_CONCAT(DISTINCT contact.contact_identifier)',
                'related'    => 'GROUP_CONCAT(DISTINCT related.related_identifier)',
                'tag' => 'GROUP_CONCAT(DISTINCT tag.tag_identifier)'
            ))
            ->joinLeft(array('contact'    => $this->_tableNames['contact']), 'tasks.identifier = contact.task_identifier', array())
            ->joinLeft(array('related'    => $this->_tableNames['related']), 'tasks.identifier = related.task_identifier', array())
            ->joinLeft(array('tag' => $this->_tableNames['tag']), 'tasks.identifier = tag.task_identifier', array())
            ->where('tasks.is_deleted = FALSE')
            ->group('tasks.identifier');
    }
    
    /**
     * Create a new Task
     *
     * @param Tasks_Model_Task $_task
     * @return Tasks_Model_Task
     */
    public function createTask(Tasks_Model_Task $_task)
    {
        $_task->creation_time = Zend_Date::now();
        $_task->created_by = $this->_currentAccount->getId();
        
        $taskParts = $this->seperateTaskData($_task);
        
        try {
            $this->_db->beginTransaction();
            $tasksTable = $this->getTableInstance('tasks');
            $taskId = $tasksTable->insert($taskParts['tasks']);
            $this->insertDependentRows($taskParts);
            $this->_db->commit();

            return $this->getTask($taskId);
            
        } catch (Exception $e) {
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
    public function updateTask(Tasks_Model_Task $_task)
    {
        try {
            $this->_db->beginTransaction();
            
            $oldTask = $this->getTask($_task->identifier);
            
            $dbMods = array_diff_assoc($_task->toArray(), $oldTask->toArray());
            $modLog = Egwbase_Timemachine_ModificationLog::getInstance();
            
            if (empty($dbMods)) {
                // nothing canged!
                $this->_db->rollBack();
                return $_task;
            }
            
            // concurrency management
            if(!empty($dbMods['last_modified_time'])) {
                $logedMods = $modLog->getModifications('Tasks', $_task->identifier,
                        'Tasks_Model_Task', Tasks_Backend_Factory::SQL, $_task->last_modified_time, $oldTask->last_modified_time);
                $diffs = $modLog->computeDiff($logedMods);
                        
                foreach ($diffs as $diff) {
                    $modified_attribute = $diff->modified_attribute;
                    if (!array_key_exists($modified_attribute, $dbMods)) {
                        // useres updated to same value, nothing to do.
                    } elseif ($diff->modified_from == $_task->$modified_attribute) {
                        unset($dbMods[$modified_attribute]);
                        // merge diff into current contact, as it was not changed in current update request.
                        $_task->$modified_attribute = $diff->modified_to;
                    } else {
                        // non resolvable conflict!
                        throw new Exception('concurrency confilict!');
                    }
                }
                unset($dbMods['last_modified_time']);
            }
            
            // database update
            $taskParts = $this->seperateTaskData($_task);
            $taskParts['tasks']['last_modified_time'] = Zend_Date::now()->getIso();
            $taskParts['tasks']['last_modified_by'] = $this->_currentAccount->getId();
        
            $tasksTable = $this->getTableInstance('tasks');
            $numAffectedRows = $tasksTable->update($taskParts['tasks'], array(
                $this->_db->quoteInto('identifier = ?', $_task->identifier),
            ));
            $this->deleteDependentRows($_task->identifier);
            $this->insertDependentRows($taskParts);

            // modification log
            $modLogEntry = new Egwbase_Timemachine_Model_ModificationLog(array(
                'application'          => 'Tasks',
                'record_identifier'    => $_task->identifier,
                'record_type'          => 'Tasks_Model_Task',
                'record_backend'       => Tasks_Backend_Factory::SQL,
                'modification_time'    => $taskParts['tasks']['last_modified_time'],
                'modification_account' => $this->_currentAccount->getId()
            ),true);
            foreach ($dbMods as $modified_attribute => $modified_to) {
                $modLogEntry->modified_attribute = $modified_attribute;
                $modLogEntry->modified_from      = $oldTask->$modified_attribute;
                $modLogEntry->modified_to        = $modified_to;
                $modLog->setModification($modLogEntry);
            }
            
            $this->_db->commit();

            return $this->getTask($_task->identifier);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw($e);
        }
    }
    
    /**
     * Deletes an existing Task
     *
     * @param int $_identifier
     * @return void
     */
    public function deleteTask($_identifier)
    {
        $tasksTable = $this->getTableInstance('tasks');
        $data = array(
            'is_deleted'   => true, 
            'deleted_time' => Zend_Date::now()->getIso(),
            'deleted_by'   => $this->_currentAccount->getId()
        );
        $tasksTable->update($data, array(
            $this->_db->quoteInto('identifier = ?', $_identifier)
        ));
        
        // NOTE: cascading delete through the use of forign keys!
        //$tasksTable->delete($tasksTable->getAdapter()->quoteInto('identifier = ?', $_uid));
    }
    
    /**
     * Deletes a set of tasks.
     * 
     * If one of the tasks could not be deleted, no taks is deleted
     * 
     * @throws Exception
     * @param array array of task identifiers
     * @return void
     */
    public function deleteTasks($_identifiers)
    {
        try {
            $this->_db->beginTransaction();
            foreach ($_identifiers as $identifier) {
                $this->deleteTask($identifier);
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
     * @param [string|int] _id 
     * @param Zend_Date _at 
     * @param bool _idIsUID wether global (string) or local (int) identifiers are given as _id
     * @return Egwbase_Record
     * @access public
     */
    public function getRecord( $_id,  Zend_Date $_at, $_idIsUID = FALSE)
    {
        
    }
    
    /**
     * Returns a set of records as they where at a given point in history
     * 
     * @param array _ids array of [string|int] 
     * @param Zend_Date _at 
     * @param bool _idsAreUIDs wether global (string) or local (int) identifiers are given as _ids
     * @return Egwbase_Record_RecordSet
     * @access public
     */
    public function getRecords( array $_ids,  Zend_Date $_at, $_idsAreUIDs = FALSE )
    {
        
    }
    
    /**
     * Deletes all depended rows from a given parent task
     *
     * @param int $_parentTaskId
     * @return int number of deleted rows
     */
    protected function deleteDependentRows($_parentTaskId)
    {
        $deletedRows = 0;
        foreach (array('contact', 'related', 'tag') as $table) {
            $TableObject = $this->getTableInstance($table);
            $deletedRows += $TableObject->delete(
                $this->_db->quoteInto('task_identifier = ?', $_parentTaskId)
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
        foreach (array('contact', 'related', 'tag') as $table) {
            if (!empty($_taskParts[$table])) {
                $items = explode(',', $_taskParts[$table]);
                $TableObject = $this->getTableInstance($table);
                foreach ($items as $itemId) {
                    $TableObject->insert(array(
                        'task_identifier'    => $taskId,
                        $table . '_identifier' => $itemId
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
        $taskArray = $_task->toArray(array('part' => Zend_Date::ISO_8601));
        $TableDescr = $this->getTableInstance('tasks')->info();
        $taskparts['tasks'] = array_intersect_key($taskArray, array_flip($TableDescr['cols']));
        
        foreach (array('contact', 'related', 'tag') as $table) {
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
     * @return Egwbase_Db_Table
     */
    protected function getTableInstance($_tablename)
    {
        
        if (!isset($this->_tables[$_tablename])) {
            try {
                $this->_tables[$_tablename] = new Egwbase_Db_Table(array('name' => $this->_tableNames[$_tablename]));
            } catch (Zend_Db_Statement_Exception $e) {
                Tasks_Setup_SetupSqlTables::createTasksTables();
                Tasks_Setup_SetupSqlTables::insertDefaultRecords();
                $this->_tables[$_tablename] = new Egwbase_Db_Table(array('name' => $this->_tableNames[$_tablename]));

                Tasks_Setup_MigrateFromEgw14::MigrateInfolog2Tasks();
            }
        }
        return $this->_tables[$_tablename];
    }
    
}