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
        date_default_timezone_set($this->_userTimezone);
        $newTask = new Tasks_Model_Task(Zend_Json::decode($task));
        $newTask->setTimezone($this->_serverTimezone);
        date_default_timezone_set($this->_serverTimezone);
        
        //error_log(print_r($newTask->toArray(),true));
        $createdTask = $this->_controller->createTask($newTask);
        $createdTask->setTimezone($this->_userTimezone);
        return $createdTask->toArray();
    }
    
    /**
     * Upate an existing Task
     *
     * @param  $task
     * @return array the updated task
     */
    public function updateTask($task)
    {
        
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
     * retruns all possible task stati
     * 
     * @return Egwbase_Record_RecordSet of Tasks_Model_Status
     */
    public function getStati() {
        return $this->_controller->getStati()->toArray();
    }
}