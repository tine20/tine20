<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * controller for Addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Controller extends Tinebase_Container_Abstract implements Tinebase_Events_Interface
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Adressbook_Controller
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Adressbook_Controller
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Addressbook_Controller;
        }
        
        return self::$instance;
    }
    
    public function getGrants($_addressbookId)
    {
        $addressbookId = (int)$_addressbookId;
        if($addressbookId != $_addressbookId) {
            throw new InvalidArgumentException('$_addressbookId must be integer');
        }
        
        $result = Tinebase_Container::getInstance()->getAllGrants($addressbookId);
                
        return $result;
    }
    
    public function setGrants($_addressbookId, Tinebase_Record_RecordSet $_grants)
    {
        $addressbookId = (int)$_addressbookId;
        if($addressbookId != $_addressbookId) {
            throw new InvalidArgumentException('$_addressbookId must be integer');
        }
        
        $result = Tinebase_Container::getInstance()->setAllGrants($addressbookId, $_grants);
                
        return $result;
    }
    
/*    public function getOtherUsers() 
    {
        $result = Tinebase_Container::getInstance()->getOtherUsers('addressbook');
        
        return $result;
    }*/
        
    /**
     * get list of shared contacts
     *
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return Zend_Db_Table_Rowset
     */
    public function getSharedContacts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL) 
    {
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        $rows = $backend->getSharedContacts($_filter, $_sort, $_dir, $_limit, $_start);
        
        return $rows;
    }
    
    /**
     * fetch one contact identified by contactid
     *
     * @param int $_contactId
     * @return Addressbook_Model_Contact
     */
    public function getContact($_contactId)
    {
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        $result = $backend->getContactById($_contactId);
        
        return $result;
    }
    
    /**
     * save one contact
     *
     * @param Addressbook_Model_Contact $_contact the contact object
     * @return Addressbook_Model_Contact the updated contact
     */
    public function saveContact(Addressbook_Model_Contact $_contact)
    {
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        $updatedContact = $backend->saveContact($_contact);
        
        return $updatedContact;
    }
    
    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Events_Abstract $_eventObject the eventObject
     */
    public function handleEvents(Tinebase_Events_Abstract $_eventObject)
    {
        switch(get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                $this->createPersonalFolder($_eventObject->account);
                break;
            case 'Admin_Event_DeleteAccount':
                $this->deletePersonalFolder($_eventObject->account);
                break;
        }
    }

    /**
     * delete all personal user folders and the contacts associated with these folders
     *
     * @param Tinebase_Account_Model_Account $_account the accountd object
     */
    public function deletePersonalFolder($_account)
    {
    }
    
    /**
     * creates the initial folder for new accounts
     *
     * @param Tinebase_Account_Model_Account $_account the accountd object
     * @return Tinebase_Model_Container
     */
    public function createPersonalFolder(Tinebase_Account_Model_Account $_account)
    {
        $personalContainer = Tinebase_Container::getInstance()->addPersonalContainer($_account->accountId, 'addressbook', 'Personal Contacts');
        
        $container = new Tinebase_Record_RecordSet(array($personalContainer), 'Tinebase_Model_Container');
        
        return $container;
    }
}
