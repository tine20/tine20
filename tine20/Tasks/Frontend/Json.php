<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * json interface for tasks
 * @package     Tasks
 */
class Tasks_Frontend_Json extends Tinebase_Application_Frontend_Json_Abstract
{
    protected $_appname = 'Tasks';
    
    /**
     * user timezone
     *
     * @var string
     */
    protected $_userTimezone;
    
    /**
     * server timezone
     *
     * @var string
     */
    protected $_serverTimezone;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_userTimezone = Zend_Registry::get('userTimeZone');
        $this->_serverTimezone = date_default_timezone_get();
    }

    /**
     * Search for tasks matching given arguments
     *
     * @param $filter
     * @return array
     */
    public function searchTasks($filter)
    {
        $paginationFilter = Zend_Json::decode($filter);
        $filter = new Tasks_Model_Filter($paginationFilter);
        $pagination = new Tasks_Model_Pagination($paginationFilter);
        //Zend_Registry::get('logger')->debug(print_r($pagination->toArray(),true));
        
        $tasks = Tasks_Controller_Task::getInstance()->searchTasks($filter, $pagination);

        $results = $this->_multipleTasksToJson($tasks);
        
        return array(
            'results' => $results,
            'totalcount' => Tasks_Controller_Task::getInstance()->searchTasksCount($filter)
        );
    }
    
    /**
     * Return a single Task
     *
     * @param string $uid
     * @param int    $containerId
     * @param string $relatedApp
     * @return Tasks_Model_Task task
     */
    public function getTask($uid, $containerId = -1, $relatedApp = '')
    {
        if(strlen($uid) == 40) {
            $task = Tasks_Controller_Task::getInstance()->getTask($uid);
        } else {
            $task = new Tasks_Model_Task(array(
                'container_id' => $containerId
            ), true);
        
            if ($containerId <= 0) {
                $task->container_id = Tasks_Controller::getInstance()->getDefaultContainer($relatedApp)->getId();
            }
        }
        
        return $this->_taskToJson($task);
    }
    
    /**
     * Upate an existing Task
     *
     * @param  $task
     * @return array the updated task
     */
    public function updateTask($task)
    {
        $inTask = new Task_Model_Task();
        $inTask->setFromJson($task);
        
        //error_log(print_r($newTask->toArray(),true));
        $outTask = Tasks_Controller_Task::getInstance()->updateTask($inTask);
        return $this->_taskToJson($outTask);
    }
    
    /**
     * creates/updates a Task
     *
     * @param  $task
     * @return array created/updated task
     */
    public function saveTask($task)
    {
        $inTask = new Tasks_Model_Task();
        $inTask->setFromJsonInUsersTimezone($task);
        //Zend_Registry::get('logger')->debug(print_r($inTask->toArray(),true));
        
        $outTask = strlen($inTask->getId()) > 10 ? 
            Tasks_Controller_Task::getInstance()->updateTask($inTask): 
            Tasks_Controller_Task::getInstance()->createTask($inTask);

        return $this->_taskToJson($outTask);
    }
    
    /**
     * returns task prepared for json transport
     *
     * @param Tasks_Model_Task $_task
     * @return array task data
     */
    protected function _taskToJson($_task)
    {
        $_task->setTimezone($this->_userTimezone);
        $_task->bypassFilters = true;
        $taskArray = $_task->toArray();
        
        $taskArray['container_id'] = Tinebase_Container::getInstance()->getContainerById($_task->container_id)->toArray();
        $taskArray['container_id']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Zend_Registry::get('currentAccount'), $_task->container_id)->toArray();
        return $taskArray;
    }
    
    /**
     * returns multiple tasks prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_tasks Tasks_Model_Task
     * @return array tasks data
     */
    protected function _multipleTasksToJson(Tinebase_Record_RecordSet $_tasks)
    {        
        // get acls for tasks
        Tinebase_Container::getInstance()->getGrantsOfRecords($_tasks, Zend_Registry::get('currentAccount'));
        $_tasks->setTimezone($this->_userTimezone);
        $_tasks->convertDates = true;
        
        $result = $_tasks->toArray();
        
        return $result;
    }    
    
    /**
     * Deletes an existing Task
     *
     * @throws Exception
     * @param int $identifier
     * @return string
     */
    public function deleteTask($identifier)
    {
        if (strlen($identifier) > 40) {
            $identifier = Zend_Json::decode($identifier);
        }
        Tasks_Controller_Task::getInstance()->deleteTask($identifier);
        return 'success';
    }
    
    /**
     * temporaray function to get a default container
     * 
     * @return array container
     */
    public function getDefaultContainer()
    {
        $container = Tasks_Controller::getInstance()->getDefaultContainer();
        $container->setTimezone($this->_userTimezone);
        return $container->toArray();
    }
    
    /**
     * retruns all possible task stati
     * 
     * @return Tinebase_Record_RecordSet of Tasks_Model_Status
     */
    public function getAllStatus() {
        $result = Tasks_Controller_Status::getInstance()->getAllStatus();    
        $result->translate();
        return $result->toArray();
    }
    
    /**
     * Returns registry data of the tasks application.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $registryData = array(
            'AllStatus' => Tasks_Controller_Status::getInstance()->getAllStatus(),
            //'DefaultContainer' => $controller->getDefaultContainer()
        );
        
        foreach ($registryData as &$data) {
            $data->setTimezone(Zend_Registry::get('userTimeZone'));
            $data->translate();
            $data = $data->toArray();
        }
        return $registryData;    
    }
}
