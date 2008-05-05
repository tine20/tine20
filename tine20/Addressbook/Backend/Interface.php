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
    public function deleteContact ($_contactId);
    /**
     * get list of contacts from given addressbooks
     *
     * @param  Tinebase_Record_RecordSet $_container  container id's to read the contacts from
     * @param  Addressbook_Model_Filter  $_filter     string to search for in contacts
     * @param  Tinebase_Model_Pagination $_pagination 
     * @return Tinebase_Record_RecordSet subtype Addressbook_Model_Contact
     */
    public function getContacts (Tinebase_Record_RecordSet $_container, Addressbook_Model_Filter $_filter, Tinebase_Model_Pagination $_pagination);
    /**
     * get total count of contacts from given addressbooks
     *
     * @param  Tinebase_Record_RecordSet $_container container id's to read the contacts from
     * @param  Addressbook_Model_Filter  $_filter the search filter
     * @return int                       count of all other users contacts
     */
    public function getCountOfContacts (Tinebase_Record_RecordSet $_container, Addressbook_Model_Filter $_filter);
    /**
     * fetch one contact identified by contactid
     *
     * @param int $_contactId
     * @return Addressbook_Model_Contact 
     */
    public function getContact ($_contactId);
    /**
     * add a contact
     *
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @return Addressbook_Model_Contact
     */
    public function addContact (Addressbook_Model_Contact $_contactData);
    /**
     * update a contact
     *
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @return Addressbook_Model_Contact
     */
    public function updateContact (Addressbook_Model_Contact $_contactData);
}
