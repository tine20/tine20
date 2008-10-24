<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
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
 * @subpackage  Application
 */
interface Tinebase_Application_Backend_Interface
{
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Record_Interface  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Record_Interface $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL);
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Record_Interface $_filter
     * @return int
     */
    public function searchCount(Tinebase_Record_Interface $_filter);
    
    /**
     * Return a single record
     *
     * @param string $_id
     * @return Tinebase_Record_Interface
     */
    public function get($_id);
    
    /**
     * Returns a set of contacts identified by their id's
     * 
     * @param  string|array $_id Ids
     * @return Tinebase_RecordSet of Tinebase_Record_Interface
     */
    public function getMultiple($_ids);
    
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
     * @return Tinebase_Record_Interface
     */
    public function update(Tinebase_Record_Interface $_record);
    
    /**
     * Deletes one or more existing persistent record(s)
     *
     * @param string|array $_identifier
     * @return void
     */
    public function delete($_identifier);
}
