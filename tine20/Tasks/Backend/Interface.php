<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        remove that? add new Tinebase Backend Interface?
 */

/**
 * Interface for Tasks Backends
 * 
 * @package Tasks
 */
interface Tasks_Backend_Interface
{
    
    /**
     * Search for tasks matching given filter
     *
     * @param Tasks_Model_Filter $_filter
     * @param Tasks_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tasks_Model_Filter $_filter, Tasks_Model_Pagination $_pagination);
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tasks_Model_Filter $_filter
     * @return int
     */
    public function searchCount(Tasks_Model_Filter $_filter);
    
    /**
     * Return a single Task
     *
     * @param string $_id
     * @return Tasks_Model_Task task
     */
    public function get($_id);
    
    /**
     * Returns a set of tasks identified by their id's
     * 
     * @param  array $_ids array of string
     * @return Tinebase_RecordSet of Tasks_Model_Task
     */
    public function getMultiple(array $_ids);
    
    /**
     * Create a new Task
     *
     * @param Tasks_Model_Task $_task
     * @return Tasks_Model_Task
     */
    public function create(Tasks_Model_Task $_task);
    
    /**
     * Upate an existing Task
     *
     * @param Tasks_Model_Task $_task
     * @return Tasks_Model_Task
     */
    public function update(Tasks_Model_Task $_task);
    
    /**
     * Deletes an existing Task
     *
     * @param string $_identifier
     * @return void
     */
    public function delete($_identifier);
}