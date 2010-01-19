<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        use functions from Tinebase_Frontend_Json_Abstract
 */


/**
 * json interface for tasks
 * @package     Tasks
 */
class Tasks_Frontend_Json extends Tinebase_Frontend_Json_Abstract
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
        $this->_userTimezone = Tinebase_Core::get('userTimeZone');
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
        $filterData = Zend_Json::decode($filter);
        
        if (! is_array($filterData)) {
            $persitentFilter = new Tinebase_Frontend_Json_PersistentFilter();
            $filter = $persitentFilter->get($filterData);
        } else {
            $filter = new Tasks_Model_TaskFilter(array());
            $filter->setFromArrayInUsersTimezone($filterData);
        }
        
        $pagination = new Tasks_Model_Pagination(Zend_Json::decode($paging));
        
        $tasks = Tasks_Controller_Task::getInstance()->search($filter, $pagination, TRUE);

        $results = $this->_multipleTasksToJson($tasks);
        
        return array(
            'results' => $results,
            'totalcount' => Tasks_Controller_Task::getInstance()->searchCount($filter),
            'filter' => $filter->toArray(true)
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
        
        return $this->_recordToJson($task);
    }

    /**
     * creates/updates a Task
     *
     * @param  $recordData
     * @return array created/updated task
     */
    public function saveTask($recordData)
    {
        return $this->_save($recordData, Tasks_Controller_Task::getInstance(), 'Task');
    }
    
    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        if ($_record instanceof Tasks_Model_Task) {
            Tinebase_User::getInstance()->resolveUsers($_record, 'organizer', true); 
        }
        
        return parent::_recordToJson($_record);
    }    
    
    /**
     * returns multiple tasks prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_tasks Tasks_Model_Task
     * @return array tasks data
     * 
     * @todo use parent::_multipleRecordsToJson?
     */
    protected function _multipleTasksToJson(Tinebase_Record_RecordSet $_tasks)
    {        
        // get acls for tasks
        Tinebase_Container::getInstance()->getGrantsOfRecords($_tasks, Tinebase_Core::getUser());
        $_tasks->setTimezone($this->_userTimezone);
        $_tasks->convertDates = true;
        
        Tinebase_User::getInstance()->resolveMultipleUsers($_tasks, 'organizer', true);

        Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($_tasks);
        
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
        return $this->_delete($ids, Tasks_Controller_Task::getInstance());
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
            $data->setTimezone(Tinebase_Core::get('userTimeZone'));
            $data->translate();
            $data = $data->toArray();
        }
        return $registryData;    
    }
}
