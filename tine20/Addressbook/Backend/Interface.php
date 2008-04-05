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
     * delete contact identified by contact id
     *
     * @param int $_contacts contact ids
     * @return int the number of rows deleted
     */
    public function deleteContact($_contactId);
    
    /**
     * get list of contacts from given addressbooks
     *
     * @param array $_container container id's to read the contacts from
     * @param string $_filter string to search for in contacts
     * @param array $_contactType filter by type (list or contact currently)
     * @param string $_sort fieldname to sort by
     * @param string $_dir sort ascending or descending (ASC | DESC)
     * @param int $_limit how many contacts to display
     * @param int $_start how many contaxts to skip
     * @return Tinebase_Record_RecordSet subtype Addressbook_Model_Contact
     */
    public function getContacts(array $_container, $_filter = NULL, $_pagination = NULL);
    
    /**
     * fetch one contact identified by contactid
     *
     * @param int $_contactId
     * @return Addressbook_Model_Contact 
     */
    public function getContact($_contactId);
    
    /**
     * get total count of contacts from given addressbooks
     *
     * @param array $_container container id's to read the contacts from
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
