<?php

/**
 * interface for contacs class
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
interface Addressbook_Backend_Interface
{
    /**
     * add a new addressbook
     *
     * @param string $_name the name of the addressbook
     * @param int $_type
     * @return int the id of the new addressbook
     */
    public function addAddressbook($_name, $_type);
    
    /**
     * delete an addressbook
     *
     * @param int $_addressbookId id of the addressbook
     * @return unknown
     */
    public function deleteAddressbook($_addressbookId);
        
    /**
     * deletes contact
     *
     * @param int $contacts contactid
     */
    public function deleteContactById($_contactId);
    
    /**
     * get list of contacts from all shared addressbooks the current user has access to
     *
     * @param string $_filter string to search for in contacts
     * @param array $_contactType filter by type (list or contact currently)
     * @param unknown_type $_sort fieldname to sort by
     * @param unknown_type $_dir sort ascending or descending (ASC | DESC)
     * @param unknown_type $_limit how many contacts to display
     * @param unknown_type $_start how many contaxts to skip
     * @return unknown The row results per the Zend_Db_Adapter fetch mode.
     */
    public function getAllContacts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL);
    
    public function getContactById($_contactId);
    
    /**
     * get contacts of user identified by account id
     *
     * @param int $_owner account id
     * @param string $_filter search filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_limit
     * @param int $_start
     * @return Zend_Db_Table_Rowset
     */
    public function getContactsByOwner($_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL);

    /**
     * returns total number of contacts
     * 
     * @param int $_owner owner of the addressbook
     * @return int total number of personal contacts
     *
     */
    public function getCountByOwner($_owner, $_filter);
    
    /**
     * get total count of contacts from all addressbooks
     *
     * @param string $_filter the search filter
     * @return int count of all other users contacts
     */
    public function getCountOfAllContacts($_filter);
    
    /**
     * get total count of all other users contacts
     *
     * @return int count of all other users contacts
     */
    public function getCountOfOtherPeopleContacts();
    
    /**
     * get total count of all contacts from shared addressbooks
     *
     * @return int count of all other users contacts
     */
    public function getCountOfSharedContacts();
            
    /**
     * get contacts of other people, takes acl of current owner into account
     *
     * @param string $_filter the search filter
     * @param string $_sort the columnname to sort after
     * @param string $_dir the direction to sort after
     * @param int $_limit
     * @param int $_start
     * @return Zend_Db_Table_Rowset
     */
    public function getOtherPeopleContacts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL);

    /**
     * get list of shared contacts
     *
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return Zend_Db_Table_Rowset returns false if user has no access to shared addressbooks
     */
    public function getSharedContacts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL); 
    
    /**
     * rename an addressbook
     *
     * @param int $_addressbookId id of the addressbook
     * @param string $_name new name of the addressbook
     * @return unknown
     */
    public function renameAddressbook($_addressbookId, $_name);    

    /**
     * add or updates a contact
     *
     * @param int $_contactOwner the owner of the addressbook entry
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @param int $_contactId the contact to update, if NULL the contact gets added
     * @todo check acl when adding contact
     * @return unknown
     */
    public function saveContact(Addressbook_Model_Contact $_contactData);
}
