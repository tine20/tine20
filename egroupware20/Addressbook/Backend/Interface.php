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
    public function deleteContactsById(array $contacts);
    
    public function getContactById($_contactId);
    
    /**
     * get list of personal contacts
     *
     * @param int $_owner id of the addressbook to read the contacts from
     * @param string $_filter string to search for in contacts
     * @param string $_sort fieldname to sort by
     * @param string $_dir sort ascending or descending (ASC | DESC)
     * @param int $_limit how many contacts to display
     * @param int $_start how many contaxts to skip
     * 
     * @return array
     */
    public function getContactsByOwner($_owner, $_filter, array $_contactType, $_sort, $_dir, $_limit = NULL, $_start = NULL);

    /**
     * returns total number of contacts
     * 
     * @param int $_owner owner of the addressbook
     * @return int total number of personal contacts
     *
     */
    public function getCountByOwner($_owner);
    
    /**
     * Enter description here...
     *
     * @param int $_list id of the personal contact list
     * @param int $_owner 
     * @param string $_filter string to search for in contacts
     * @param string $_sort fieldname to sort by
     * @param string $_dir sort ascending or descending (ASC | DESC)
     * @param int $_limit how many contacts to display
     * @param int $_start how many contaxts to skip
     * 
     * @return array list of contacts from contact list identified by $list
     */
    public function getContactsByList($_list, $_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL);
    
    /**
     * returns list of all personal contact lists
     * 
     * @param int $_owner owner of the addressbook
     * @return array list of all personal contact lists
     *
     */
    public function getListsByOwner($_owner);
    
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
    public function getAccounts($filter, $sort, $dir, $limit = NULL, $start = NULL);

    /**
     * returns total number of internal contacts
     * 
     * @return int total number of internal contacts
     *
     */    
    public function getCountOfAccounts();
}
