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
 */

/**
 * controller for Addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Controller extends Tinebase_Container_Abstract implements Tinebase_Events_Interface
{
    /**
     * Holds instance of current account
     *
     * @var Tinebase_Model_User
     */
    protected $_currentAccount;
    
    /**
     * the contacts backend
     *
     * @var Addressbook_Backend_Sql
     */
    protected $_backend;
    
    /**
     * holdes the instance of the singleton
     *
     * @var Adressbook_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        $this->_currentAccount = Zend_Registry::get('currentAccount');
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
        
    }

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
        
        $contact->notes = Tinebase_Notes::getInstance()->getNotesOfRecord('Addressbook_Model_Contact', $contact->getId());

        if (!$this->_currentAccount->hasGrant($contact->owner, Tinebase_Container::GRANT_READ)) {
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
     */
    public function searchContacts(Addressbook_Model_ContactFilter $_filter, Tinebase_Model_Pagination $_pagination)
    {
        $this->_checkContainerACL($_filter);
        
        if (count($_filter->container) === 0) {
            return new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        }        
        
        $contacts = $this->_backend->search($_filter, $_pagination);
        
        return $contacts;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Addressbook_Model_ContactFilter $_filter
     * @return int
     */
    public function searchContactsCount(Addressbook_Model_ContactFilter $_filter) 
    {
        $this->_checkContainerACL($_filter);
        
        if (count($_filter->container) === 0) {
            return 0;
        }        
        
        $count = $this->_backend->searchCount($_filter);
        
        return $count;
    }
    
    /**
     * Removes containers where current user has no access to.
     * 
     * @param Addressbook_Model_ContactFilter $_filter
     * @return void
     */
    protected function _checkContainerACL($_filter)
    {
        $readableContainer = $this->_currentAccount->getContainerByACL('Addressbook', Tinebase_Container::GRANT_READ);
        $_filter->container = array_intersect($_filter->container, $readableContainer->getArrayOfIds());
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' .$_filter->containerType . ' ' . print_r($_filter->container, true));
    }    

    /**
     * Returns a set of contacts identified by their id's
     * 
     * @param  array $_ids array of string
     * @return Tinebase_Record_RecordSet of Addressbook_Model_Contact
     * 
     * @todo    write test
     */
    public function getMultipleContacts($_contactIds)
    {
        $contacts = $this->_backend->getMultiple($_contactIds);
        
        foreach ($contacts as $contact) {
            if (! $this->_currentAccount->hasGrant($contact->owner, Tinebase_Container::GRANT_READ)) {
                $index = $contacts->getIndexById($contact->getId());
                unset($contacts[$index]);
            } 
        }
        return $contacts;
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
            $containers = Tinebase_Container::getInstance()->getPersonalContainer($this->_currentAccount, 'Addressbook', $this->_currentAccount, Tinebase_Container::GRANT_ADD);
            $_contact->owner = $containers[0]->getId();
        }
        if (! $this->_currentAccount->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
        }

        Tinebase_Timemachine_ModificationLog::setRecordMetaData($_contact, 'create');
        $contact = $this->_backend->create($_contact);
        
        if (!empty($_contact->tags)) {
            $contact->tags = $_contact->tags;
            Tinebase_Tags::getInstance()->setTagsOfRecord($contact);
        }

        if (isset($_contact->notes)) {
            $contact->notes = $_contact->notes;
            Tinebase_Notes::getInstance()->setNotesOfRecord($contact);
        }
        
        // add created note to record
        Tinebase_Notes::getInstance()->addSystemNote($contact, $this->_currentAccount->getId(), 'created');
                        
        return $this->getContact($contact->getId());
    }
    
    /**
     * update one contact
     *
     * @param Addressbook_Model_Contact $_contact
     * @return  Addressbook_Model_Contact
     */
    public function updateContact(Addressbook_Model_Contact $_contact)
    {
        $currentContact = $this->getContact($_contact->getId());
        
        // ACL checks
        if ($currentContact->owner != $_contact->owner) {
            if (! $this->_currentAccount->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
                throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
            }
            // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
            if (! $this->_currentAccount->hasGrant($currentContact->owner, Tinebase_Container::GRANT_DELETE)) {
                throw new Exception('delete access to contacts in container ' . $currentContact->owner . ' denied');
            }
        } elseif (! $this->_currentAccount->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        
        // concurrency management & history log
        $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
        $modLog->manageConcurrentUpdates($_contact, $currentContact, 'Addressbook_Model_Contact', Addressbook_Backend_Factory::SQL, $_contact->getId());
        $modLog->setRecordMetaData($_contact, 'update', $currentContact);
        $currentMods = $modLog->writeModLog($_contact, $currentContact, 'Addressbook_Model_Contact', Addressbook_Backend_Factory::SQL, $_contact->getId());
        
        $contact = $this->_backend->update($_contact);                
                
        if (isset($_contact->tags)) {
            Tinebase_Tags::getInstance()->setTagsOfRecord($_contact);
        }

        if (isset($_contact->notes)) {
            Tinebase_Notes::getInstance()->setNotesOfRecord($_contact);
        }
        
        // add changed note to record
        if (count($currentMods) > 0) {
            Tinebase_Notes::getInstance()->addSystemNote($contact, $this->_currentAccount->getId(), 'changed', $currentMods);
        }

        return $this->getContact($contact->getId());
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
            
            if ($this->_currentAccount->hasGrant($contact->owner, Tinebase_Container::GRANT_DELETE &&
                $container->type != Tinebase_Container::TYPE_INTERNAL)) {
                    
                $this->_backend->delete($_contactId);
                
                // delete notes
                Tinebase_Notes::getInstance()->deleteNotesOfRecord('Addressbook_Model_Contact', Addressbook_Backend_Factory::SQL, $contact->getId());
                
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
     * 
     * @todo    write test
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
     * @param mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet                            of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_account)
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_account);
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
     * @param Tinebase_Model_User $_account the accountd object
     * @todo implement and write test
     */
    public function deletePersonalFolder($_account)
    {
    }
    
}
