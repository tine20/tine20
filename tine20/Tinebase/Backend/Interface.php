<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Interface for Application Backends
 * 
 * @package     Tinebase
 * @subpackage  Backend
 */
interface Tinebase_Backend_Interface
{
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup $_filter
     * @param  Tinebase_Model_Pagination         $_pagination
     * @param  boolean                           $_onlyIds
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE);
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter);
    
    /**
     * Return a single record
     *
     * @param string $_id
     * @param $_getDeleted get deleted records
     * @return Tinebase_Record_Interface
     */
    public function get($_id, $_getDeleted = FALSE);
    
    /**
     * Returns a set of records identified by their id's
     * 
     * @param  string|array $_id Ids
     * @param array $_containerIds all allowed container ids that are added to getMultiple query
     * @return Tinebase_RecordSet of Tinebase_Record_Interface
     */
    public function getMultiple($_ids, $_containerIds = NULL);

    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC');
    
    /**
     * Create a new persistent contact
     *
     * @param  Tinebase_Record_Interface $_record
     * @return Tinebase_Record_Interface
     */
    public function create(Tinebase_Record_Interface $_record);
    
    /**
     * Upates an existing persistent record
     *
     * @param  Tinebase_Record_Interface $_contact
     * @return Tinebase_Record_Interface|NULL
     */
    public function update(Tinebase_Record_Interface $_record);
    
    /**
     * Updates multiple entries
     *
     * @param array $_ids to update
     * @param array $_data
     * @return integer number of affected rows
     */
    public function updateMultiple($_ids, $_data);
        
    /**
     * Deletes one or more existing persistent record(s)
     *
     * @param string|array $_identifier
     * @return void
     */
    public function delete($_identifier);
    
    /**
     * get backend type
     *
     * @return string
     */
    public function getType();
}
