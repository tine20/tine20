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
    
    /**
     * get list of all contacts
     *
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return Zend_Db_Table_Rowset
     */
    public function getAllContacts($_filter = NULL, $_sort = 'id', $_dir = 'ASC', $_limit = NULL, $_start = NULL) 
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getContainerByACL('Addressbook', Tinebase_Container::GRANT_READ);
        
        if(count($readableContainer) === 0) {
            $this->createPersonalFolder(Zend_Registry::get('currentAccount'));
            $readableContainer = Zend_Registry::get('currentAccount')->getContainerByACL('Addressbook', Tinebase_Container::GRANT_READ);
        }
                
        $containerIds = array();
        foreach($readableContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $result = $this->_backend->getContacts($containerIds, $_filter, $_sort, $_dir, $_limit, $_start);

        return $result;
    }
    
    /**
     * get total count of all contacts matching filter
     *
     * @param string $_filter
     * @return int total number of matching leads
     */
    public function getCountOfAllContacts($_filter = NULL)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getContainerByACL('Addressbook', Tinebase_Container::GRANT_READ);
        
        if(count($readableContainer) === 0) {
            $this->createPersonalFolder(Zend_Registry::get('currentAccount'));
            $readableContainer = Zend_Registry::get('currentAccount')->getContainerByACL('Addressbook', Tinebase_Container::GRANT_READ);
        }
                
        $containerIds = array();
        foreach($readableContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $result = $this->_backend->getCountOfContacts($containerIds, $_filter);

        return $result;
    }
    
    /**
     * get list of all contacts of one account
     *
     * @param int $_owner account id of the account to get the folders from
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return Zend_Db_Table_Rowset
     */
    public function getContactsByOwner($_owner, $_filter = NULL, $_sort = 'id', $_dir = 'ASC', $_limit = NULL, $_start = NULL) 
    {
        $owner = (int)$_owner;
        if($owner != $_owner) {
            throw new InvalidArgumentException('$_owner must be integer');
        }
        $readableContainer = Zend_Registry::get('currentAccount')->getPersonalContainer('addressbook', $owner, Tinebase_Container::GRANT_READ);
        
        if(count($readableContainer) === 0) {
            return new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        }
        
        $containerIds = array();
        foreach($readableContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $result = $this->_backend->getContacts($containerIds, $_filter, $_sort, $_dir, $_limit, $_start);

        return $result;
    }
    
    /**
     * get total count of contacts matching filter
     *
     * @param int $_owner account id of the account to get the folders from
     * @param string $_filter
     * @return int total number of matching leads
     */
    public function getCountByOwner($_owner, $_filter = NULL)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getPersonalContainer('addressbook', $_owner, Tinebase_Container::GRANT_READ);
                
        if(count($readableContainer) === 0) {
            return 0;
        }
                
        $containerIds = array();
        foreach($readableContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $result = $this->_backend->getCountOfContacts($containerIds, $_filter);

        return $result;
    }
    
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
        $readableContainer = Zend_Registry::get('currentAccount')->getSharedContainer('addressbook', Tinebase_Container::GRANT_READ);
        
        if(count($readableContainer) === 0) {
            return new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        }
                        
        $containerIds = array();
        foreach($readableContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $result = $this->_backend->getContacts($containerIds, $_filter, $_sort, $_dir, $_limit, $_start);

        return $result;
    }
    
    /**
     * get total count of all contacts matching filter
     *
     * @param string $_filter
     * @return int total number of matching leads
     */
    public function getCountOfSharedContacts($_filter = NULL)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getSharedContainer('addressbook', Tinebase_Container::GRANT_READ);
        
        if(count($readableContainer) === 0) {
            return 0;
        }
                
        $containerIds = array();
        foreach($readableContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $result = $this->_backend->getCountOfContacts($containerIds, $_filter);

        return $result;
    }
    
    /**
     * get list of other peoples contacts
     *
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return Zend_Db_Table_Rowset
     */
    public function getOtherPeopleContacts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL) 
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getOtherUsersContainer('Addressbook', Tinebase_Container::GRANT_READ);
        
        if(count($readableContainer) === 0) {
            return new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        }
                        
        $containerIds = array();
        foreach($readableContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $result = $this->_backend->getContacts($containerIds, $_filter, $_sort, $_dir, $_limit, $_start);

        return $result;
    }
    
    /**
     * get total count of other peoples contacts matching filter
     *
     * @param string $_filter
     * @return int total number of matching leads
     */
    public function getCountOfOtherPeopleContacts($_filter = NULL)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getOtherUsersContainer('Addressbook', Tinebase_Container::GRANT_READ);
        
        if(count($readableContainer) === 0) {
            return 0;
        }
                
        $containerIds = array();
        foreach($readableContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $result = $this->_backend->getCountOfContacts($containerIds, $_filter);

        return $result;
    }

    /**
     * get list of all contacts of one addressbook
     *
     * @param int $_containerId container id to get the contacts from
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return Zend_Db_Table_Rowset
     */
    public function getContactsByAddressbookId($_containerId, $_filter = NULL, $_sort = 'id', $_dir = 'ASC', $_limit = NULL, $_start = NULL) 
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($containerId, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access denied to addressbook');
        }

        $containerIds = array($containerId);
        
        $result = $this->_backend->getContacts($containerIds, $_filter, $_sort, $_dir, $_limit, $_start);

        return $result;
    }
    
    /**
     * get total count of contacts for given addressbook
     *
     * @param int $_containerId container id to get the contacts from
     * @param string $_filter
     * @return int total number of matching leads
     */
    public function getCountByAddressbookId($_containerId, $_filter = NULL)
    {
        $containerId = Tinebase_Model_Container::convertContainerIdToInt($_containerId);
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($containerId, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access denied to addressbook');
        }

        $containerIds = array($containerId);
                
        $result = $this->_backend->getCountOfContacts($containerIds, $_filter);

        return $result;
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
}
