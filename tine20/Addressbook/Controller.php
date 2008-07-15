<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        replace getXXX functions by searchContacts
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
    private function __clone()
    {
        
    }

    /**
     * holdes the instance of the singleton
     *
     * @var Adressbook_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller;
        }
        
        return self::$_instance;
    }
        
    /*********** get / search / count contacts **************/
    
    /**
     * fetch one contact identified by contactid
     *
     * @param int $_contactId
     * @return Addressbook_Model_Contact
     */
    public function getContact($_contactId)
    {
        $contact = $this->_backend->get($_contactId);
        // only get tags the user has view right for
        Tinebase_Tags::getInstance()->getTagsOfRecord($contact);

        if (!Zend_Registry::get('currentAccount')->hasGrant($contact->owner, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access to contact denied');
        }
        
        return $contact;            
    }
    
    /**
     * Search for contacts matching given filter
     *
     * @param Addressbook_Model_ContactFilter $_filter
     * @param Addressbook_Model_ContactPagination $_pagination
     * 
     * @return Tinebase_Record_RecordSet
     * 
     * @todo test and use it
     */
    public function searchContacts(Addressbook_Model_ContactFilter $_filter, Tinebase_Model_Pagination $_pagination)
    {
        $this->_checkContainerACL($_filter);
        
        $contacts = $this->_backend->search($_filter, $_pagination);
        
        return $contacts;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Addressbook_Model_ContactFilter $_filter
     * @return int
     * 
     * @todo test and use it
     */
    public function searchContactsCount(Addressbook_Model_ContactFilter $_filter) 
    {
        $this->_checkContainerACL($_filter);
        $count = $this->_backend->searchCount($_filter);
        
        return $count;
    }
    
    /**
     * Removes containers where current user has no access to.
     * 
     * @param Addressbook_Model_ContactFilter $_filter
     * @return void
     * 
     * @todo test and use it
     */
    protected function _checkContainerACL($_filter)
    {
        $container = array();
        
        foreach ($_filter->container as $containerId) {
            if ($this->_currentAccount->hasGrant($containerId, Tinebase_Container::GRANT_READ)) {
                $container[] = $containerId;
            }
        }
        $_filter->container = $container;
    }    

    /**
     * Returns a set of contacts identified by their id's
     * 
     * @param  array $_ids array of string
     * @return Tinebase_Record_RecordSet of Addressbook_Model_Contact
     */
    public function getMultipleContacts($_contactIds)
    {
        $contacts = $this->_backend->getMultiple($_contactIds);
        $currentAccount = Zend_Registry::get('currentAccount');
        
        foreach ($contacts as $contact) {
            if (! $currentAccount->hasGrant($contact->owner, Tinebase_Container::GRANT_READ)) {
                $index = $contacts->getIndexOfId($contact->getId());
                unset($contacts[$index]);
            } 
        }
        return $contacts;
    }    
    
    /************* remove these functions later ****************/
    
    /**
     * get list of all contacts
     *
     * @param  Addressbook_Model_ContactFilter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     * 
     * @deprecated 
     */
    public function getAllContacts(Addressbook_Model_ContactFilter $_filter, Tinebase_Model_Pagination $_pagination) 
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getContainerByACL('Addressbook', Tinebase_Container::GRANT_READ);
        $_filter->container = $readableContainer;
        $result = $this->_backend->search($_filter, $_pagination);

        return $result;
    }
    
    /**
     * get total count of all contacts matching filter
     *
     * @param  Addressbook_Model_ContactFilter $_filter
     * @return int                      total number of matching contacts
     * 
     * @deprecated 
     */
    public function getCountOfAllContacts(Addressbook_Model_ContactFilter $_filter)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getContainerByACL('Addressbook', Tinebase_Container::GRANT_READ);
        $_filter->container = $readableContainer;
        
        $result = $this->_backend->searchCount($_filter);

        return $result;
    }
    
    /**
     * get list of all contacts of one account
     *
     * @param  int                       $_owner account id of the account to get the folders from
     * @param  Addressbook_Model_ContactFilter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     * 
     * @deprecated 
     */
    public function getContactsByOwner($_owner, Addressbook_Model_ContactFilter $_filter, Tinebase_Model_Pagination $_pagination) 
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getPersonalContainer('addressbook', $_owner, Tinebase_Container::GRANT_READ);
        
        if (count($readableContainer) === 0) {
            return new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        }
        $_filter->container = $readableContainer;
        
        $result = $this->_backend->search($_filter, $_pagination);

        return $result;
    }
    
    /**
     * get total count of contacts matching filter
     *
     * @param  int                       $_owner account id of the account to get the folders from
     * @param  Addressbook_Model_ContactFilter  $_filter
     * @return int                       total number of matching contacts
     * 
     * @deprecated 
     */
    public function getCountByOwner($_owner, Addressbook_Model_ContactFilter $_filter)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getPersonalContainer('addressbook', $_owner, Tinebase_Container::GRANT_READ);
                
        if (count($readableContainer) === 0) {
            return 0;
        }
        
        $_filter->container = $readableContainer;
        $result = $this->_backend->searchCount($_filter);

        return $result;
    }
    
    /**
     * get list of shared contacts
     *
     * @param  Addressbook_Model_ContactFilter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     * 
     * @deprecated 
     */
    public function getSharedContacts(Addressbook_Model_ContactFilter $_filter, Tinebase_Model_Pagination $_pagination) 
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getSharedContainer('addressbook', Tinebase_Container::GRANT_READ);
        
        if (count($readableContainer) === 0) {
            return new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        }
        
        $_filter->container = $readableContainer;
        $result = $this->_backend->search($_filter, $_pagination);

        return $result;
    }
    
    /**
     * get total count of all contacts matching filter
     *
     * @param  Addressbook_Model_ContactFilter  $_filter
     * @return int                       total number of matching contacts
     * 
     * @deprecated 
     */
    public function getCountOfSharedContacts(Addressbook_Model_ContactFilter $_filter)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getSharedContainer('addressbook', Tinebase_Container::GRANT_READ);
        
        if (count($readableContainer) === 0) {
            return 0;
        }
        
        $_filter->container = $readableContainer;
        $result = $this->_backend->searchCount($_filter);

        return $result;
    }
    
    /**
     * get list of other peoples contacts
     *
     * @param  Addressbook_Model_ContactFilter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     * 
     * @deprecated 
     */
    public function getOtherPeopleContacts(Addressbook_Model_ContactFilter $_filter, Tinebase_Model_Pagination $_pagination) 
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getOtherUsersContainer('Addressbook', Tinebase_Container::GRANT_READ);
        
        if (count($readableContainer) === 0) {
            return new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        }

        $_filter->container = $readableContainer;
        $result = $this->_backend->search($_filter, $_pagination);

        return $result;
    }
    
    /**
     * get total count of all contacts matching filter
     *
     * @param  Addressbook_Model_ContactFilter  $_filter
     * @return int                       total number of matching contacts
     * 
     * @deprecated 
     */
    public function getCountOfOtherPeopleContacts(Addressbook_Model_ContactFilter $_filter)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getOtherUsersContainer('Addressbook', Tinebase_Container::GRANT_READ);
        
        if (count($readableContainer) === 0) {
            return 0;
        }
        
        $_filter->container = $readableContainer;
        $result = $this->_backend->searchCounnt($_filter);

        return $result;
    }

    /**
     * get list of all contacts of one addressbook
     *
     * @param  int                       $_containerId
     * @param  Addressbook_Model_ContactFilter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     * 
     * @deprecated 
     */
    public function getContactsByAddressbookId($_containerId, Addressbook_Model_ContactFilter $_filter, Tinebase_Model_Pagination $_pagination) 
    {
        $container = Tinebase_Container::getInstance()->getContainerById($_containerId);
        
        if (!Zend_Registry::get('currentAccount')->hasGrant($container->getId(), Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access denied to addressbook');
        }

        $containerIds = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($container), true);
        $_filter->container = $containerIds;
        $result = $this->_backend->search($_filter, $_pagination);

        return $result;
    }
    
    /**
     * get total count of contacts for given addressbook
     *
     * @param  int                      $_containerId container id to get the contacts from
     * @param  Addressbook_Model_ContactFilter $_filter
     * @return int                      total number of matching contacts
     * 
     * @deprecated 
     */
    public function getCountByAddressbookId($_containerId, $_filter)
    {
        $container = Tinebase_Container::getInstance()->getContainerById($_containerId);
        
        if (!Zend_Registry::get('currentAccount')->hasGrant($container->getId(), Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access denied to addressbook');
        }

        $containerIds = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($container), true);
        $_filter->container = $containerIds;
        
        $result = $this->_backend->searchCount($_filter);

        return $result;
    }        
        
    /*************** add / update / delete contact *****************/  
    
    /**
     * add one contact
     *
     * @param Addressbook_Model_Contact $_contact
     * @return  Addressbook_Model_Contact
     */
    public function createContact(Addressbook_Model_Contact $_contact)
    {
        if(empty($_contact->owner)) {
            $currentAccount = Zend_Registry::get('currentAccount');
            $containers = Tinebase_Container::getInstance()->getPersonalContainer($currentAccount, 'Addressbook', $currentAccount, Tinebase_Container::GRANT_ADD);
            $_contact->owner = $containers[0]->getId();
        }
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
        }
        
        $contact = $this->_backend->create($_contact);
        
        if (!empty($_contact->tags)) {
            $contact->tags = $_contact->tags;
            Tinebase_Tags::getInstance()->setTagsOfRecord($contact);
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
        $currentAccount = Zend_Registry::get('currentAccount');
        $currentContact = $this->getContact($_contact->getId());
        
        if ($currentContact->owner != $_contact->owner) {
            
            if (!$currentAccount->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
                throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
            }
            // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
            if (!$currentAccount->hasGrant($currentContact->owner, Tinebase_Container::GRANT_DELETE)) {
                throw new Exception('delete access to contacts in container ' . $currentContact->owner . ' denied');
            }
            
        } elseif (!$currentAccount->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
                
        if (isset($_contact->tags)) {
            Tinebase_Tags::getInstance()->setTagsOfRecord($_contact);
        }

        $contact = $this->_backend->update($_contact);
        
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
        if (is_array($_contactId) or $_contactId instanceof Tinebase_Record_RecordSet) {
            foreach ($_contactId as $contactId) {
                $this->deleteContact($contactId);
            }
        } else {
            $contact = $this->_backend->get($_contactId);
            $container = Tinebase_Container::getInstance()->getContainerById($contact->owner);
            
            if (Zend_Registry::get('currentAccount')->hasGrant($contact->owner, Tinebase_Container::GRANT_DELETE &&
                $container->type != Tinebase_Container::TYPE_INTERNAL)) {
                    
                $this->_backend->delete($_contactId);
            } else {
                throw new Exception('delete access to contact denied');
            }
        }
    }

    /*************** helper functions *****************/  

    /**
     * returns contact image
     * 
     * @param  string $_identifier record identifier
     * @param  string $_location not used, requierd by interface
     * @return Tinebase_Model_Image
     */
    public function getImage($_identifier, $_location='')
    {
        $contact = $this->getContact($_identifier);
        if (empty($contact->jpegphoto)) {
            throw new Exception('Contact has no image');
        }
        $imageInfo = Tinebase_ImageHelper::getImageInfoFromBlob($contact->jpegphoto);
        
        return new Tinebase_Model_Image($imageInfo + array(
            'id'           => $_identifier,
            'application'  => 'Addressbook',
            'data'         => $contact->jpegphoto
        ));
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
        Zend_Registry::get('logger')->debug(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
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
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_User_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet                            of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_account)
    {
        $accountId = Tinebase_User_Model_User::convertUserIdToInt($_account);
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => 'Personal Contacts',
            'type'              => Tinebase_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId() 
        ));
        
        $personalContainer = Tinebase_Container::getInstance()->addContainer($newContainer, NULL, FALSE, $accountId);
        $personalContainer['account_grants'] = Tinebase_Container::GRANT_ANY;
        
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
    }
    
    /**
     * delete all personal user folders and the contacts associated with these folders
     *
     * @param Tinebase_User_Model_User $_account the accountd object
     * @todo implement
     */
    public function deletePersonalFolder($_account)
    {
    }
    
}
