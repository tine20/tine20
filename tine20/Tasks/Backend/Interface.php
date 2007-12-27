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

/**
 * Interface for Tasks Backends
 * 
 * @package Tasks
 */
interface Tasks_Backend_Interface
{
    
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
    public function searchTasks($_query='', $_due=NULL, $_container=NULL, $_organizer=NULL, $_tag=NULL);
    
    /**
     * Return a single Task
     *
     * @param string $_uid
     * @return Tasks_Task task
     */
    public function getTask($_uid);
    
    /**
     * Create a new Task
     *
     * @param Tasks_Task $_task
     * @return Tasks_Task
     */
    public function createTask(Tasks_Task $_task);
    
    /**
     * Upate an existing Task
     *
     * @param Tasks_Task $_task
     * @return Tasks_Task
     */
    public function updateTask(Tasks_Task $_task);
    
    /**
     * Deletes an existing Task
     *
     * @param string $_uid
     * @return void
     */
    public function deleteTask($_uid);
    
}