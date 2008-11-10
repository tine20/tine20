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
    protected $_applicationName = 'Tasks';
    
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
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchTasks($filter, $paging)
    {
        $filter = new Tasks_Model_Filter(Zend_Json::decode($filter));
        $pagination = new Tasks_Model_Pagination(Zend_Json::decode($paging));
        //Zend_Registry::get('logger')->debug(print_r($pagination->toArray(),true));
        
        $tasks = Tasks_Controller_Task::getInstance()->search($filter, $pagination);

        $results = $this->_multipleTasksToJson($tasks);
        
        return array(
            'results' => $results,
            'totalcount' => Tasks_Controller_Task::getInstance()->searchCount($filter)
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
            $task = Tasks_Controller_Task::getInstance()->get($uid);
        } else {
            $task = new Tasks_Model_Task(array(
                'container_id' => $containerId
            ), true);
        
            if ($containerId <= 0) {
                $task->container_id = Tasks_Controller::getInstance()->getDefaultContainer($relatedApp)->getId();
            }
            
            $task->organizer = Tinebase_Core::getUser()->getId();
        }
        
        return $this->_taskToJson($task);
    }

    /**
     * creates/updates a Task
     *
     * @param  $recordData
     * @return array created/updated task
     */
    public function saveTask($recordData)
    {
        $inTask = new Tasks_Model_Task();
        $inTask->setFromJsonInUsersTimezone($recordData);
        //Zend_Registry::get('logger')->debug(print_r($inTask->toArray(),true));
        
        $outTask = strlen($inTask->getId()) > 10 ? 
            Tasks_Controller_Task::getInstance()->update($inTask): 
            Tasks_Controller_Task::getInstance()->create($inTask);

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
        
        $taskArray['organizer'] = $taskArray['organizer'] ? Tinebase_User::getInstance()->getUserById($taskArray['organizer'])->toArray() : $taskArray['organizer'];
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
     * @param array $ids 
     * @return string
     */
    public function deleteTasks($ids)
    {
        if (strlen($ids) > 40) {
            $ids = Zend_Json::decode($ids);
        }
        Tasks_Controller_Task::getInstance()->delete($ids);
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
