<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
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
     * the contacts backend
     *
     * @var Addressbook_Backend_Sql
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
    }
    
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
        
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
    }
    
    /**
     * add one contact
     *
     * @param Addressbook_Model_Contact $_contact
     * @return  Addressbook_Model_Contact
     */
    public function addContact(Addressbook_Model_Contact $_contact)
    {
        if(!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
        }
        
        $contact = $this->_backend->addContact($_contact);
        
        return $contact;
    }
    
    /**
     * fetch one contact identified by contactid
     *
     * @param int $_contactId
     * @return Addressbook_Model_Contact
     */
    public function getContact($_contactId)
    {
        $contact = $this->_backend->getContact($_contactId);

        if(!Zend_Registry::get('currentAccount')->hasGrant($contact->owner, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access to contact denied');
        }
        
        return $contact;            
    }
    
    
    /**
     * update one contact
     *
     * @param Addressbook_Model_Contact $_contact
     * @return  Addressbook_Model_Contact
     */
    public function updateContact(Addressbook_Model_Contact $_contact)
    {
        if(!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        
        $contact = $this->_backend->updateContact($_contact);
        
        return $contact;
    }
    
    /**
     * delete one or multiple contacts
     *
     * @param mixed $_contactId
     * @throws Exception 
     */
    public function deleteContact($_contactId)
    {
        if(is_array($_contactId) or $_contactId instanceof Tinebase_Record_RecordSet) {
            foreach($_contactId as $contactId) {
                $this->deleteContact($contactId);
            }
        } else {
            $contact = $this->_backend->getContact($_contactId);
            if(Zend_Registry::get('currentAccount')->hasGrant($contact->owner, Tinebase_Container::GRANT_DELETE)) {
                $this->_backend->deleteContact($_contactId);
            } else {
                throw new Exception('delete access to contact denied');
            }
        }
    }
    
    /**
     * converts a int, string or Addressbook_Model_Contact to an contact id
     *
     * @param int|string|Addressbook_Model_Contact $_accountId the contact id to convert
     * @return int
     */
    static public function convertContactIdToInt($_contactId)
    {
        if($_contactId instanceof Addressbook_Model_Contact) {
            if(empty($_contactId->id)) {
                throw new Exception('no contact id set');
            }
            $id = (int) $_contactId->id;
        } else {
            $id = (int) $_contactId;
        }
        
        if($id === 0) {
            throw new Exception('contact id can not be 0');
        }
        
        return $id;
    }
}
