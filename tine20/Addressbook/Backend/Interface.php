<?php

/**
 * interface for contacs class
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * interface for contacs class
 * 
 * @package     Addressbook
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
    public function deleteContact($_contactId);
    
    /**
     * get list of contacts from all shared addressbooks the current user has access to
     *
     * @param array $_container container to read the contacts from
     * @param string $_filter string to search for in contacts
     * @param array $_contactType filter by type (list or contact currently)
     * @param unknown_type $_sort fieldname to sort by
     * @param unknown_type $_dir sort ascending or descending (ASC | DESC)
     * @param unknown_type $_limit how many contacts to display
     * @param unknown_type $_start how many contaxts to skip
     * @return Tinebase_Record_RecordSet subtype Addressbook_Model_Contact
     */
    public function getContacts(array $_container, $_filter = NULL, $_sort = 'id', $_dir = 'ASC', $_limit = NULL, $_start = NULL);
    
    /**
     * fetch one contact identified by contactid
     *
     * @param int $_contactId
     * @return Addressbook_Model_Contact 
     */
    public function getContact($_contactId);
    
    /**
     * get total count of contacts from all addressbooks
     *
     * @param array $_container
     * @param string $_filter the search filter
     * @return int count of all other users contacts
     */
    public function getCountOfContacts(array $_container, $_filter);
    
    /**
     * add a contact
     *
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @return Addressbook_Model_Contact
     */
    public function addContact(Addressbook_Model_Contact $_contactData);

    /**
     * update a contact
     *
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @return Addressbook_Model_Contact
     */
    public function updateContact(Addressbook_Model_Contact $_contactData);
}
