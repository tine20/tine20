<?php
/**
 * egroupware 2.0
 * 
 * @package     Tasks
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: $
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
     * Constructor
     *
     */
    public function __construct()
    {
        // fix talbe prefixes
        foreach ($this->_tableNames as $basename => $name) {
            $this->_tableNames[$basename] = SQL_TABLE_PREFIX . $name;
        }
        
        $this->_db = Zend_Registry::get('dbAdapter');
        
        //temporary hack to enshure migration from egw14
        $this->getTableInstance('tasks');
    }
    
    /**
     * Search for tasks matching given arguments
     *
     * @param string $_query
     * @param Zend_Date $_due
     * @param array $_container array of containers to search, defaults all accessable
     * @param array $_organizer array of organizers to search, defaults all
     * @param array $_tag array of tag to search defaults all
     * @return RecordSet
     */
    public function searchTasks($_query='', $_due=NULL, $_container=NULL, $_organizer=NULL, $_tag=NULL)
    {
        $stmt = $this->_db->query($this->_db->select()
            ->from(array('tasks' => $this->_tableNames['tasks']), array('tasks.*', 
                'contact'    => 'GROUP_CONCAT(DISTINCT contact.contact_identifier)',
                'related'    => 'GROUP_CONCAT(DISTINCT related.related_identifier)',
                'tag' => 'GROUP_CONCAT(DISTINCT tag.tag_identifier)'
            ))
            ->joinLeft(array('contact'    => $this->_tableNames['contact']), 'tasks.identifier = contact.task_identifier', array())
            ->joinLeft(array('related'    => $this->_tableNames['related']), 'tasks.identifier = related.task_identifier', array())
            ->joinLeft(array('tag' => $this->_tableNames['tag']), 'tasks.identifier = tag.task_identifier', array())
            
            ->group('tasks.identifier')
        );
        
        $TaskSet = new Egwbase_Record_RecordSet(array(), 'Tasks_Task');
        $Tasks = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        foreach ($Tasks as $TaskArray) {
            $Task = new Tasks_Task($TaskArray, true, array('part' => Zend_Date::ISO_8601));
            $TaskSet->addRecord($Task);
            //error_log(print_r($Task->toArray(),true));
        }
        return $TaskSet;
    }
    
    /**
     * Return a single Task
     *
     * @param string $_uid
     * @return Tasks_Task task
     */
    public function getTask($_uid)
    {
        $stmt = $this->_db->query($this->_db->select()
            ->from(array('tasks' => $this->_tableNames['tasks']), array('tasks.*', 
                'contact'    => 'GROUP_CONCAT(DISTINCT contact.contact_identifier)',
                'related'    => 'GROUP_CONCAT(DISTINCT related.related_identifier)',
                'tag' => 'GROUP_CONCAT(DISTINCT tag.tag_identifier)'
            ))
            ->joinLeft(array('contact'    => $this->_tableNames['contact']), 'tasks.identifier = contact.task_identifier', array())
            ->joinLeft(array('related'    => $this->_tableNames['related']), 'tasks.identifier = related.task_identifier', array())
            ->joinLeft(array('tag' => $this->_tableNames['tag']), 'tasks.identifier = tag.task_identifier', array())
            ->group('tasks.identifier')
            ->where($this->_db->quoteInto('tasks.identifier = ?', $_uid))
        );
        $TaskArray = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        if (empty($TaskArray)) {
            throw new Exception("Task with uid: $_uid not found!");
        }
        
        $Task = new Tasks_Task($TaskArray[0], true, array('part' => Zend_Date::ISO_8601)); 
        return $Task;
    }
    
    /**
     * Create a new Task
     *
     * @param Tasks_Task $_task
     * @return Tasks_Task
     */
    public function createTask(Tasks_Task $_task)
    {
        $_task->creation_time = Zend_Date::now();
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
     * @param Tasks_Task $_task
     * @return Tasks_Task
     */ 
    public function updateTask(Tasks_Task $_task)
    {
        $taskParts = $this->seperateTaskData($_task);
        $taskParts['last_modified_time'] = Zend_Date::now()->getIso();

        try {
            $this->_db->beginTransaction();
            $tasksTable = $this->getTableInstance('tasks');
            $numAffectedRows = $tasksTable->update($taskParts['tasks'], array(
                $this->_db->quoteInto('identifier = ?', $_task->identifier),
                $this->_db->quoteInto('last_modified_time = ?', $_task->last_modified_time),
                'is_deleted = 0'
            ));
            
            if ($numAffectedRows != 1) {
                // TODO: need to investigate situation!
                throw new Exception('update failed!');
            }
            
            $this->deleteDependentRows($_task->identifier);
            $this->insertDependentRows($taskParts);
            $this->_db->commit();

            return $this->getTask($taskId);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw($e);
        }
    }
    
    /**
     * Deletes an existing Task
     *
     * @param string $_uid
     * @return void
     */
    public function deleteTask($_uid)
    {
        $tasksTable = $this->getTableInstance($this->tablenames['tasks']);
        $data = array(
            'is_deleted' => true, 
            'deleted_time' => Zend_Date::now()->getIso()
        );
        $tasksTable->update($data, array(
            $this->_db->quoteInto('identifier = ?', $_uid)
        ));
        
        // NOTE: cascading delete through the use of forign keys!
        //$tasksTable->delete($tasksTable->getAdapter()->quoteInto('identifier = ?', $_uid));
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
     * @param Tasks_Task $_task
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