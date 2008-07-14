<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Interface for Addressbook Backends
 * 
 * @package Addressbook
 */
interface Addressbook_Backend_Interface
{
    
    /**
     * Search for records matching given filter
     *
     * @param  Addressbook_Model_ContactFilter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function search(Addressbook_Model_ContactFilter $_filter, Tinebase_Model_Pagination $_pagination);
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Addressbook_Model_ContactFilter $_filter
     * @return int
     */
    public function searchCount(Addressbook_Model_ContactFilter $_filter);
    
    /**
     * Return a single record
     *
     * @param string $_id
     * @return Addressbook_Model_Contact
     */
    public function get($_id);
    
    /**
     * Returns a set of contacts identified by their id's
     * 
     * @param  array $_ids array of string
     * @return Tinebase_RecordSet of Addressbook_Model_Contact
     */
    public function getMultiple(array $_ids);
    
    /**
     * Create a new persistent contact
     *
     * @param  Addressbook_Model_Contact $_contactv
     * @return Addressbook_Model_Contact
     */
    public function create(Addressbook_Model_Contact $_contact);
    
    /**
     * Upates an existing persistent contact
     *
     * @param  Addressbook_Model_Contact $_contact
     * @return Addressbook_Model_Contact
     */
    public function update(Addressbook_Model_Contact $_contact);
    
    /**
     * Deletes one or more existing persistent contact(s)
     *
     * @param string|array $_identifier
     * @return void
     */
    public function delete($_identifier);
}
