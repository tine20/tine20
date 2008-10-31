<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Tasks Controller for Tasks
 * 
 * The Tasks 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Tasks
 * @subpackage  Controller
 */
class Tasks_Controller_Task extends Tinebase_Application_Controller_Abstract
{
    
    /**
     * holds self
     * @var Tasks_Controller_Task
     */
    private static $_instance = NULL;
    
    /**
     * holds backend instance
     * (only sql atm.)
     *
     * @var Tasks_Backend_Interface
     */
    protected $_backend;
    
    /**
     * singleton
     *
     * @return Tasks_Controller_Task
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tasks_Controller_Task();
        }
        return self::$_instance;
    }
    
    /**
     * the constructor
     * 
     * init backend and currentAccount
     */
    protected function __construct()
    {
        $this->_backend = Tasks_Backend_Factory::factory(Tasks_Backend_Factory::SQL);        
        $this->_currentAccount = Zend_Registry::get('currentAccount');
    }
    
    /**
     * Search for tasks matching given filter
     *
     * @param Tasks_Model_Filter $_filter
     * @param Tasks_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function searchTasks(Tasks_Model_Filter $_filter, Tasks_Model_Pagination $_pagination)
    {
        $this->_checkContainerACL($_filter);
        
        $tasks =  $this->_backend->search($_filter, $_pagination);
        return $tasks;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tasks_Model_Filter $_filter
     * @return int
     */
    public function searchTasksCount(Tasks_Model_Filter $_filter) {
        $this->_checkContainerACL($_filter);
        return $this->_backend->searchCount($_filter);
    }
    
    /**
     * Removes containers where current user has no access to.
     * 
     * @param Tasks_Model_Filter $_filter
     * @return void
     */
    protected function _checkContainerACL($_filter)
    {
        $readableContainer = $this->_currentAccount->getContainerByACL('Tasks', Tinebase_Model_Container::GRANT_READ);
        $_filter->container = array_intersect($_filter->container, $readableContainer->getArrayOfIds());
    }
    
    /**
     * Return a single Task
     *
     * @param   string $_uid
     * @return  Tasks_Model_Task task
     * @throws  Tasks_Exception_AccessDenied
     */
    public function getTask($_uid)
    {
        $Task = $this->_backend->get($_uid);
        if (! $this->_currentAccount->hasGrant($Task->container_id, Tinebase_Model_Container::GRANT_READ)) {
            throw new Tasks_Exception_AccessDenied('Not allowed!');
        }
        
        return $Task;
    }
    
    /**
     * Returns a set of tasks identified by their id's
     * 
     * @param  array $_ids array of string
     * @return Tinebase_RecordSet of Tasks_Model_Task
     */
    public function getMultipleTasks($_uids)
    {
        $tasks = $this->_backend->getMultiple($_uids);
        foreach ($tasks as $task) {
            if (! $this->_currentAccount->hasGrant($task->container_id, Tinebase_Model_Container::GRANT_READ)) {
                $index = $tasks->getIndexById($task->getId());
                unset($tasks[$index]);
            } 
        }
        return $tasks;
    }
    
    /**
     * Create a new Task
     *
     * @param   Tasks_Model_Task $_task
     * @return  Tasks_Model_Task
     * @throws  Tasks_Exception_AccessDenied
     */
    public function createTask(Tasks_Model_Task $_task)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' $_task: '. print_r($_task->toArray(), true));
    	if (empty($_task->container_id) || (int)$_task->container_id < 0) {
    		$_task->container_id = Tasks_Controller::getInstance()->getDefaultContainer()->getId();
    	}
        if (! $this->_currentAccount->hasGrant($_task->container_id, Tinebase_Model_Container::GRANT_ADD)) {
            throw new Tasks_Exception_AccessDenied('Not allowed!');
        }
        if(empty($_task->class_id)) {
            $_task->class_id = NULL;
        }
        return $this->_backend->create($_task);
    }

    /**
     * Update an existing Task
     * 
     * acl rights are managed, which is a bit complicated when a container change
     * happens. Also concurrency management is done in this contoller function
     *
     * @param   Tasks_Model_Task $_task
     * @return  Tasks_Model_Task
     * @throws  Tasks_Exception_AccessDenied
     */
    public function updateTask(Tasks_Model_Task $_task)
    {
        Zend_Registry::get('logger')->debug('Tasks_Controller_Task->updateTask');
        $oldtask = $this->getTask($_task->getId());

        // manage acl
        if ($oldtask->container_id != $_task->container_id) {
            
            if (!$this->_currentAccount->hasGrant($_task->container_id, Tinebase_Model_Container::GRANT_ADD)) {
                throw new Tasks_Exception_AccessDenied('Not allowed!');
            }
            // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
            if (!$this->_currentAccount->hasGrant($oldtask->container_id, Tinebase_Model_Container::GRANT_DELETE)) {
                throw new Tasks_Exception_AccessDenied('Not allowed!');
            }
            
        } elseif(!$this->_currentAccount->hasGrant($_task->container_id, Tinebase_Model_Container::GRANT_EDIT))  {
            throw new Tasks_Exception_AccessDenied('Not allowed!');
        }
        
        $Task = $this->_backend->update($_task);
        return $Task;
    }
    
    /**
     * Deletes one or more existing Task
     *
     * @param   string|array $_identifier
     * @return  void
     * @throws  Tasks_Exception_NotFound
     * @throws  Tasks_Exception
     */
    public function deleteTask($_identifier)
    {
        $tasks = $this->_backend->getMultiple((array)$_identifier);
        if (count((array)$_identifier) != count($tasks)) {
            throw new Tasks_Exception_NotFound('Error, only ' . count($tasks) . ' of ' . count((array)$_identifier) . ' tasks exist');
        }
                    
        try {        
            $db = Zend_Registry::get('dbAdapter');
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            foreach ($tasks as $task) {
                if (!$this->_currentAccount->hasGrant($task->container_id, Tinebase_Model_Container::GRANT_DELETE)) {
                    throw new Tasks_Exception_AccessDenied('You are only allowed to delete task "' . $task->getId() . '"');
                }
                $this->_backend->delete($task);
                
                // remove relations
                Tinebase_Relations::getInstance()->setRelations('Tasks_Model_Task', 'Sql', $task->getId(), array());
                
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            }
                
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw new Tasks_Exception($e->getMessage());
        }                
    }
}
