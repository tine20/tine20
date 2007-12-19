<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: $
 */

/**
 * json interface for tasks
 * @package     Tasks
 */
class Tasks_Json extends Egwbase_Application_Json_Abstract
{
    protected $_appname = 'Tasks';
    
    protected $_controller;
    
    /**
     * TODO: Invoke Contoller!
     *
     */
    public function __construct()
    {
        try{
            $this->_controller = Tasks_Controller::getInstance();
        } catch (Exception $e) {
            //error_log($e);
        }
    }

    /**
     * Search for tasks matching given arguments
     *
     * @param string $_query
     * @param Zend_Date $_due
     * @param array $_container array of containers to search, defaults all accessable
     * @param array $_organizer array of organizers to search, defaults all
     * @param array $_tag array of tags to search defaults all
     * @return RecordSet
     */
    public function searchTasks($query='', $due=NULL, $container=NULL, $organizer=NULL, $tag=NULL)
    {
        //Tasks_Setup_MigrateFromEgw14::MigrateInfolog2Tasks();
        //$this->_controller->searchTasks($query, $due, $container, $organizer, $tag);
        return array(
            'results' => $this->_controller->searchTasks($query, $due, $container, $organizer, $tag)->toArray(array('part' => Zend_Date::ISO_8601)),
            'totalcount' => 4
        );
    }
    
    /**
     * Return a single Task
     *
     * @param string $_uid
     * @return Tasks_Task task
     */
    public function getTask($uid)
    {
        return $this->_backend->getTask($uid)->toArray();
    }
    
    /**
     * Create a new Task
     *
     * @param Tasks_Task $_task
     * @return Tasks_Task
     */
    public function createTask(Tasks_Task $_task)
    {
        
    }
    
    /**
     * Upate an existing Task
     *
     * @param Tasks_Task $_task
     * @return Tasks_Task
     */
    public function updateTask(Tasks_Task $_task)
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
    
}