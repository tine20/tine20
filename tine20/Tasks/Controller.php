<?php
/**
 * egroupware 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Tasks Controller (composite)
 * 
 * The Tasks 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Tasks
 */
class Tasks_Controller implements Tasks_Backend_Interface
{
    
    /**
     * holds self
     * @var Tasks_Controller
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
     * Holds possible classes of a task
     *
     * @var Zend_Db_Table_Rowset
     */
    protected $_classes;
    
    /**
     * Holds possible states of a task
     *
     * @var Zend_Db_Table_Rowset
     */
    protected $_stati;
    
    /**
     * Holds instance of current account
     *
     * @var Egwbase_Account_Model_Account
     */
    protected $_currentAccount;
    
    /**
     * prohibit use of clone()
     */
    private function __clone() {}
    
    /**
     * singleton
     *
     * @return Tasks_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tasks_Controller();
        }
        return self::$_instance;
    }
    
    protected function __construct()
    {
        $this->_backend = Tasks_Backend_Factory::factory(Tasks_Backend_Factory::SQL);
        
        $classTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'class'));
        $statiTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'tasks_status'));
        //$this->_classes = new Egwbase_Record_RecordSet($classTable->fetchAll(), 'Egwbase_Record_Class');
        $this->_stati = new Egwbase_Record_RecordSet($statiTable->fetchAll()->toArray(), 'Tasks_Model_Status');
        
        $this->_currentAccount = Zend_Registry::get('currentAccount');
    }
    
    /**
     * Search for tasks matching given filter
     *
     * @param Tasks_Model_PagnitionFilter $_filter
     * @return Egwbase_Record_RecordSet
     */
    public function searchTasks($_filter)
    {
        $this->_checkContainerACL($_filter);
        
        $tasks =  $this->_backend->searchTasks($_filter);
        //Egwbase_Account::getBackend()->getPublicAccountProperties();
        //foreach ($tasks as $task) {
            //$taks->organizer = 
        //}
        return $tasks;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tasks_Model_PagnitionFilter $_filter
     * @return int
     */
    public function getTotalCount($_filter) {
        $this->_checkContainerACL($_filter);
        return $this->_backend->getTotalCount($_filter);
    }
    
    /**
     * Removes containers where current user has no access to.
     * 
     * @param Tasks_Model_PagnitionFilter $_filter
     * @return void
     */
    protected function _checkContainerACL($_filter)
    {
        foreach ($_filter->container as $containerId) {
            if ($this->_currentAccount->hasGrant($containerId, Egwbase_Container::GRANT_READ)) {
                $container[] = $containerId;
            }
        }
        $_filter->container = $container;
    }
    
    /**
     * Return a single Task
     *
     * @param string $_uid
     * @return Tasks_Model_Task task
     */
    public function getTask($_uid)
    {
        $Task = $this->_backend->getTask($_uid);
        if (! $this->_currentAccount->hasGrant($Task->container, Egwbase_Container::GRANT_READ)) {
            throw new Exception('Not allowed!');
        }
        
        return $Task;
    }
    
	
    /**
     * set linking to another app
     *
     * @param Tasks_Model_Task $_task
     * @return Tasks_Model_Task
     */	
    public function setLink($_app1, $_id1, $_id2)
    {
        Egwbase_Links::getInstance()->setLinks($_app1, $_id1, 'tasks', $_id2, '');
    }	
	
    /**
     * Create a new Task
     *
     * @param Tasks_Model_Task $_task
     * @return Tasks_Model_Task
     */
    public function createTask(Tasks_Model_Task $_task)
    {
        if (! $this->_currentAccount->hasGrant($_task->container, Egwbase_Container::GRANT_ADD)) {
            throw new Exception('Not allowed!');
        }
        return $this->_backend->createTask($_task);
    }
    
    /**
     * Upate an existing Task
     * 
     * acl rights are managed, which is a bit complicated when a container change
     * happens. Also concurrency management is done in this contoller function
     *
     * @param Tasks_Model_Task $_task
     * @return Tasks_Model_Task
     */
    public function updateTask(Tasks_Model_Task $_task)
    {
        $oldtask = $this->getTask($_task->identifier);

        // mamage acl
        if ($oldtask->container != $_task->container) {
            
            if (!$this->_currentAccount->hasGrant($_task->container, Egwbase_Container::GRANT_ADD)) {
                throw new Exception('Not allowed!');
            }
            // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
            if (!$this->_currentAccount->hasGrant($oldtask->container, Egwbase_Container::GRANT_DELETE)) {
                throw new Exception('Not allowed!');
            }
            
        } elseif(!$this->_currentAccount->hasGrant($_task->container, Egwbase_Container::GRANT_EDIT))  {
            throw new Exception('Not allowed!');
        }
        
        $Task = $this->_backend->updateTask($_task);
        return $Task;
    }
    
    /**
     * Deletes an existing Task
     *
     * @param string $_uid
     * @return void
     */
    public function deleteTask($_uid)
    {
        $Task = $this->getTask($_uid);
        
        if (!$this->_currentAccount->hasGrant($Task->container, Egwbase_Container::GRANT_DELETE)) {
            throw new Exception('Not allowed!');
        }
        $this->_backend->deleteTask($_uid);
    }

    /**
     * temporaray function to get a default container
     * 
     * @return Egwbase_Record_Container container
     */
    public function getDefaultContainer()
    {
        $containers = Egwbase_Container::getInstance()->getPersonalContainer('Tasks', $this->_currentAccount->accountId);
        // autch! ugly hack, we need to rework the recordSet!
        foreach ($containers as $container) {
            return $container;
        }
    }    
    
    /**
     * retruns all possible task stati
     * 
     * @return Egwbase_Record_RecordSet of Tasks_Model_Status
     */
    public function getStati() {
        return $this->_stati;
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
    }
}