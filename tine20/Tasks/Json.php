<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * json interface for tasks
 * @package     Tasks
 */
class Tasks_Json extends Egwbase_Application_Json_Abstract
{
    protected $_appname = 'Tasks';
    
    /**
     * @var Tasks_Controller
     */
    protected $_controller;
    
    protected $_userTimezone;
    protected $_serverTimezone;
    
    public function __construct()
    {
        try{
            $this->_controller = Tasks_Controller::getInstance();
        } catch (Exception $e) {
            //error_log($e);
        }
        $this->_userTimezone = Zend_Registry::get('userTimeZone');
        $this->_serverTimezone = date_default_timezone_get();
    }

    /**
     * Search for tasks matching given arguments
     *
     * @param array $filter
     * @return array
     */
    public function searchTasks($filter)
    {
        $filter = new Tasks_Model_PagnitionFilter(Zend_Json::decode($filter));
        //error_log(print_r($filter->toArray(),true));
        
        $tasks = $this->_controller->searchTasks($filter);
        $tasks->setTimezone($this->_userTimezone);
        
        return array(
            'results' => $tasks->toArray(array('part' => Zend_Date::ISO_8601)),
            'totalcount' => $this->_controller->getTotalCount($filter)
        );
    }
    
    /**
     * Return a single Task
     *
     * @param string $_uid
     * @return Tasks_Model_Task task
     */
    public function getTask($uid)
    {
        
        return $this->_backend->getTask($uid)->toArray();
    }
    
    /**
     * Create a new Task
     *
     * @param  $task
     * @return array the created task
     */
    public function createTask($task)
    {
        $inTask = $this->_json2task($task);
        
        //error_log(print_r($newTask->toArray(),true));
        $outTask = $this->_controller->createTask($inTask);
        $outTask->setTimezone($this->_userTimezone);
        return $outTask->toArray();
    }
    
    /**
     * Upate an existing Task
     *
     * @param  $task
     * @return array the updated task
     */
    public function updateTask($task)
    {
        $inTask = $this->_json2task($task);
        
        //error_log(print_r($newTask->toArray(),true));
        $outTask = $this->_controller->updateTask($inTask);
        $outTask->setTimezone($this->_userTimezone);
        return $outTask->toArray();
    }
    
    /**
     * creates/updates a Task
     *
     * @param  $task
     * @return array created/updated task
     */
    public function saveTask($task)
    {
        $inTask = $this->_json2task($task);
        
        //error_log(print_r($inTask->toArray(),true));
        $outTask = $inTask->getId() > 0 ? 
            $this->_controller->updateTask($inTask): 
            $this->_controller->createTask($inTask);
            
        $outTask->setTimezone($this->_userTimezone);
        return $outTask->toArray();
    }
    
    /**
     * returns instance of Tasks_Model_Task from json encoded data
     * 
     * @param string JSON encoded task
     * @return Tasks_Model_Task task
     */
    protected function _json2task($json) {
        date_default_timezone_set($this->_userTimezone);
        $inTask = new Tasks_Model_Task(Zend_Json::decode($json));
        $inTask->setTimezone($this->_serverTimezone);
        date_default_timezone_set($this->_serverTimezone);
        
        return $inTask;
    }
    
    /**
     * Deletes an existing Task
     *
     * @param string $_uid
     */
    public function deleteTask($_uid)
    {
        
    }
    
    /**
     * temporaray function to get a default container
     * 
     * @return array container
     */
    public function getDefaultContainer()
    {
        $container = $this->_controller->getDefaultContainer();
        $container->setTimezone($this->_userTimezone);
        return $container->toArray();
    }
    
    /**
     * retruns all possible task stati
     * 
     * @return Egwbase_Record_RecordSet of Tasks_Model_Status
     */
    public function getStati() {
        return $this->_controller->getStati()->toArray();
    }
}