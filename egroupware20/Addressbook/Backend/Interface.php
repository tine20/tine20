<?php

/**
 * interface for contacs class
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
interface Addressbook_Backend_Interface
{
    /**
     * deletes personal contacts
     *
     * @param array $contacts list of contactids
     */
    public function deletePersonalContacts(array $contacts);

    /**
     * get list of personal contacts
     *
     * @param string $filter string to search for in contacts
     * @param string $sort fieldname to sort by
     * @param string $dir sort ascending or descending (ASC | DESC)
     * @param int $limit how many contacts to display
     * @param int $start how many contaxts to skip
     * 
     * @return array
     */
    public function getPersonalContacts($filter, $sort, $dir, $limit = NULL, $start = NULL);

    /**
     * returns total number of personal contacts
     * 
     * @return int total number of personal contacts
     *
     */
    public function getPersonalCount();
    
    /**
     * Enter description here...
     *
     * @param int $list id of the personal contact list
     * @param string $filter string to search for in contacts
     * @param string $sort fieldname to sort by
     * @param string $dir sort ascending or descending (ASC | DESC)
     * @param int $limit how many contacts to display
     * @param int $start how many contaxts to skip
     * 
     * @return array list of contacts from contact list identified by $list
     */
    public function getPersonalList($list, $filter, $sort, $dir, $limit = NULL, $start = NULL);
    
    /**
     * returns list of all personal contact lists
     * 
     * @return array list of all personal contact lists
     *
     */
    public function getPersonalLists();
    
    /**
     * return list of internal conacts (aka accounts)
     *
     * @param string $filter string to search for in contacts
     * @param string $sort fieldname to sort by
     * @param string $dir sort ascending or descending (ASC | DESC)
     * @param int $limit how many contacts to display
     * @param int $start how many contaxts to skip
     * 
     * @return array list of internal contacts
    */
    public function getInternalContacts($filter, $sort, $dir, $limit = NULL, $start = NULL);

    /**
     * returns total number of internal contacts
     * 
     * @return int total number of internal contacts
     *
     */    
    public function getInternalCount();
}
