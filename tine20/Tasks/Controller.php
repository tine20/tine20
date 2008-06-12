<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
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
class Tasks_Controller extends Tinebase_Container_Abstract implements Tasks_Backend_Interface,Tinebase_Events_Interface
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
     * @var Tinebase_User_Model_User
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
        
        //$classTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'class'));
        $statiTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'tasks_status'));
        //$this->_classes = new Tinebase_Record_RecordSet('Tinebase_Record_Class', $classTable->fetchAll());
        $this->_stati = new Tinebase_Record_RecordSet('Tasks_Model_Status', $statiTable->fetchAll()->toArray(),  true);
        
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
        
        $tasks =  $this->_backend->searchTasks($_filter, $_pagination);
        //Tinebase_User::getBackend()->getPublicAccountProperties();
        //foreach ($tasks as $task) {
            //$taks->organizer = 
        //}
        return $tasks;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tasks_Model_Filter $_filter
     * @return int
     */
    public function getTotalCount(Tasks_Model_Filter $_filter) {
        $this->_checkContainerACL($_filter);
        return $this->_backend->getTotalCount($_filter);
    }
    
    /**
     * Removes containers where current user has no access to.
     * 
     * @param Tasks_Model_PaginationFilter $_filter
     * @return void
     */
    protected function _checkContainerACL($_filter)
    {
        foreach ($_filter->container as $containerId) {
            if ($this->_currentAccount->hasGrant($containerId, Tinebase_Container::GRANT_READ)) {
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
        if (! $this->_currentAccount->hasGrant($Task->container_id, Tinebase_Container::GRANT_READ)) {
            throw new Exception('Not allowed!');
        }
        
        return $Task;
    }
    
    /**
     * Create a new Task
     *
     * @param Tasks_Model_Task $_task
     * @return Tasks_Model_Task
     */
    public function createTask(Tasks_Model_Task $_task)
    {
        Zend_Registry::get('logger')->debug('Tasks_Controller->createTask');
    	if (empty($_task->container_id) || (int)$_task->container_id < 0) {
    		$_task->container_id = $this->getDefaultContainer()->getId();
    	}
        if (! $this->_currentAccount->hasGrant($_task->container_id, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('Not allowed!');
        }
        return $this->_backend->createTask($_task);
    }
    
    /**
     * Update an existing Task
     * 
     * acl rights are managed, which is a bit complicated when a container change
     * happens. Also concurrency management is done in this contoller function
     *
     * @param Tasks_Model_Task $_task
     * @return Tasks_Model_Task
     */
    public function updateTask(Tasks_Model_Task $_task)
    {
        Zend_Registry::get('logger')->debug('Tasks_Controller->updateTask');
        $oldtask = $this->getTask($_task->getId());

        // mamage acl
        if ($oldtask->container_id != $_task->container_id) {
            
            if (!$this->_currentAccount->hasGrant($_task->container_id, Tinebase_Container::GRANT_ADD)) {
                throw new Exception('Not allowed!');
            }
            // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
            if (!$this->_currentAccount->hasGrant($oldtask->container_id, Tinebase_Container::GRANT_DELETE)) {
                throw new Exception('Not allowed!');
            }
            
        } elseif(!$this->_currentAccount->hasGrant($_task->container_id, Tinebase_Container::GRANT_EDIT))  {
            throw new Exception('Not allowed!');
        }
        
        $Task = $this->_backend->updateTask($_task);
        return $Task;
    }
    
    /**
     * Deletes an existing Task
     *
     * @param string $_identifier
     * @return void
     */
    public function deleteTask($_identifier)
    {
        $Task = $this->getTask($_identifier);
        
        if (!$this->_currentAccount->hasGrant($Task->container_id, Tinebase_Container::GRANT_DELETE)) {
            throw new Exception('Not allowed!');
        }
        $this->_backend->deleteTask($_identifier);
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
        foreach ($_identifiers as $identifier) {
            $Task = $this->getTask($identifier);
            if (!$this->_currentAccount->hasGrant($Task->container_id, Tinebase_Container::GRANT_DELETE)) {
                throw new Exception('Not allowed!');
            }
        }
        
        $this->_backend->deleteTasks($_identifiers);
    }
    
    /**
     * temporaray function to get a default container]
     * 
     * @param string $_referingApplication
     * @return Tinebase_Model_Container container
     */
    public function getDefaultContainer($_referingApplication = 'tasks')
    {
        $taskConfig = Zend_Registry::get('configFile')->tasks;
        $configString = 'defaultcontainer_' . ( empty($_referingApplication) ? 'tasks' : $_referingApplication );
        
        if (isset($taskConfig->$configString)) {
            $defaultContainer = Tinebase_Container::getInstance()->getContainerById((int)$taskConfig->$configString);
        } else {
            $containers = Tinebase_Container::getInstance()->getPersonalContainer($this->_currentAccount, 'Tasks', $this->_currentAccount, Tinebase_Container::GRANT_ADD);
            //$containers = $this->getPersonalContainer($this->_currentAccount, $this->_currentAccount->accountId, Tinebase_Container::GRANT_READ);
            $defaultContainer = $containers[0];
        }
        
        return $defaultContainer;
    }
    
    /**
     * returns all possible task stati
     * 
     * @return Tinebase_Record_RecordSet of Tasks_Model_Status
     */
    public function getStati() {
        return $this->_stati;
    }

    /**
     * get task status array
     * 
     * @param   int $_statusId
     * 
     * @return array of task status with status_id given
     */
    public function getTaskStatus($_statusId) {
        
        foreach ( $this->_stati as $status ) {
            if ( $status->getId() === $_statusId ) {
                return $status->toArray();
            }
        }
    }
    
    /**
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_User_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet                            of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_accountId)
    {
        $accountId = Tinebase_User_Model_User::convertUserIdToInt($_accountId);
        
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => 'Personal Tasks',
            'type'              => Tinebase_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId() 
        ));
        
        $personalContainer = Tinebase_Container::getInstance()->addContainer($newContainer, NULL, FALSE, $accountId);
        $personalContainer->account_grants = Tinebase_Container::GRANT_ANY;
        
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
    }

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Events_Abstract $_eventObject the eventObject
     */
    public function handleEvents(Tinebase_Events_Abstract $_eventObject)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch(get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                $this->createPersonalFolder($_eventObject->account);
                break;
            case 'Admin_Event_DeleteAccount':
                #$this->deletePersonalFolder($_eventObject->account);
                break;
        }
    }
    
}