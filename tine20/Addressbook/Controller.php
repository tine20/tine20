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
        
    /**
     * get list of all contacts
     *
     * @param  Addressbook_Model_Filter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function getAllContacts(Addressbook_Model_Filter $_filter, Tinebase_Model_Pagination $_pagination) 
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getContainerByACL('Addressbook', Tinebase_Container::GRANT_READ);
        
        $result = $this->_backend->getContacts($readableContainer, $_filter, $_pagination);

        return $result;
    }
    
    /**
     * get total count of all contacts matching filter
     *
     * @param  Addressbook_Model_Filter $_filter
     * @return int                      total number of matching contacts
     */
    public function getCountOfAllContacts(Addressbook_Model_Filter $_filter)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getContainerByACL('Addressbook', Tinebase_Container::GRANT_READ);
        
        $result = $this->_backend->getCountOfContacts($readableContainer, $_filter);

        return $result;
    }
    
    /**
     * get list of all contacts of one account
     *
     * @param  int                       $_owner account id of the account to get the folders from
     * @param  Addressbook_Model_Filter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function getContactsByOwner($_owner, Addressbook_Model_Filter $_filter, Tinebase_Model_Pagination $_pagination) 
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getPersonalContainer('addressbook', $_owner, Tinebase_Container::GRANT_READ);
        
        if (count($readableContainer) === 0) {
            return new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        }
        
        $result = $this->_backend->getContacts($readableContainer, $_filter, $_pagination);

        return $result;
    }
    
    /**
     * get total count of contacts matching filter
     *
     * @param  int                       $_owner account id of the account to get the folders from
     * @param  Addressbook_Model_Filter  $_filter
     * @return int                       total number of matching contacts
     */
    public function getCountByOwner($_owner, Addressbook_Model_Filter $_filter)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getPersonalContainer('addressbook', $_owner, Tinebase_Container::GRANT_READ);
                
        if (count($readableContainer) === 0) {
            return 0;
        }

        $result = $this->_backend->getCountOfContacts($readableContainer, $_filter);

        return $result;
    }
    
    /**
     * get list of shared contacts
     *
     * @param  Addressbook_Model_Filter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function getSharedContacts(Addressbook_Model_Filter $_filter, Tinebase_Model_Pagination $_pagination) 
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getSharedContainer('addressbook', Tinebase_Container::GRANT_READ);
        
        if (count($readableContainer) === 0) {
            return new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        }
                        
        $result = $this->_backend->getContacts($readableContainer, $_filter, $_pagination);

        return $result;
    }
    
    /**
     * get total count of all contacts matching filter
     *
     * @param  Addressbook_Model_Filter  $_filter
     * @return int                       total number of matching contacts
     */
    public function getCountOfSharedContacts(Addressbook_Model_Filter $_filter)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getSharedContainer('addressbook', Tinebase_Container::GRANT_READ);
        
        if (count($readableContainer) === 0) {
            return 0;
        }
                
        $result = $this->_backend->getCountOfContacts($readableContainer, $_filter);

        return $result;
    }
    
    /**
     * get list of other peoples contacts
     *
     * @param  Addressbook_Model_Filter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function getOtherPeopleContacts(Addressbook_Model_Filter $_filter, Tinebase_Model_Pagination $_pagination) 
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getOtherUsersContainer('Addressbook', Tinebase_Container::GRANT_READ);
        
        if (count($readableContainer) === 0) {
            return new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        }
                        
        $result = $this->_backend->getContacts($readableContainer, $_filter, $_pagination);

        return $result;
    }
    
    /**
     * get total count of all contacts matching filter
     *
     * @param  Addressbook_Model_Filter  $_filter
     * @return int                       total number of matching contacts
     */
    public function getCountOfOtherPeopleContacts(Addressbook_Model_Filter $_filter)
    {
        $readableContainer = Zend_Registry::get('currentAccount')->getOtherUsersContainer('Addressbook', Tinebase_Container::GRANT_READ);
        
        if (count($readableContainer) === 0) {
            return 0;
        }
                
        $result = $this->_backend->getCountOfContacts($readableContainer, $_filter);

        return $result;
    }

    /**
     * get list of all contacts of one addressbook
     *
     * @param  int                       $_containerId
     * @param  Addressbook_Model_Filter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function getContactsByAddressbookId($_containerId, Addressbook_Model_Filter $_filter, Tinebase_Model_Pagination $_pagination) 
    {
        $container = Tinebase_Container::getInstance()->getContainerById($_containerId);
        
        if (!Zend_Registry::get('currentAccount')->hasGrant($container->getId(), Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access denied to addressbook');
        }
        $containerIds = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($container), true);
        
        $result = $this->_backend->getContacts($containerIds, $_filter, $_pagination);

        return $result;
    }
    
    /**
     * get total count of contacts for given addressbook
     *
     * @param  int                      $_containerId container id to get the contacts from
     * @param  Addressbook_Model_Filter $_filter
     * @return int                      total number of matching contacts
     */
    public function getCountByAddressbookId($_containerId, $_filter)
    {
        $container = Tinebase_Container::getInstance()->getContainerById($_containerId);
        
        if (!Zend_Registry::get('currentAccount')->hasGrant($container->getId(), Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access denied to addressbook');
        }

        $containerIds = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($container), true);
                
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
     * delete all personal user folders and the contacts associated with these folders
     *
     * @param Tinebase_User_Model_User $_account the accountd object
     */
    public function deletePersonalFolder($_account)
    {
    }
    
    /**
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_User_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet                            of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_account)
    {
        $accountId = Tinebase_User_Model_User::convertAccountIdToInt($_account);
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
     * add one contact
     *
     * @param Addressbook_Model_Contact $_contact
     * @return  Addressbook_Model_Contact
     */
    public function addContact(Addressbook_Model_Contact $_contact)
    {
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
        }
        
        $contact = $this->_backend->addContact($_contact);
        
        if (!empty($_contact->tags)) {
            $contact->tags = $_contact->tags;
            Tinebase_Tags::getInstance()->setTagsOfRecord($contact);
        }
        
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
        // only get tags the user has view right for
        Tinebase_Tags::getInstance()->getTagsOfRecord($contact);

        if (!Zend_Registry::get('currentAccount')->hasGrant($contact->owner, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access to contact denied');
        }
        
        return $contact;            
    }
    
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
     * update one contact
     *
     * @param Addressbook_Model_Contact $_contact
     * @return  Addressbook_Model_Contact
     */
    public function updateContact(Addressbook_Model_Contact $_contact)
    {
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        
        //@todo move this to js frontend later on
        // update fullname
        if ( !empty($_data['n_given']) && !empty($_data['n_family']) ) {
            $_contact['n_fn'] = $_contact['n_given'] . ' ' . $_contact['n_family'];
        }
        if (isset($_contact->tags)) {
            Tinebase_Tags::getInstance()->setTagsOfRecord($_contact);
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
        if (is_array($_contactId) or $_contactId instanceof Tinebase_Record_RecordSet) {
            foreach ($_contactId as $contactId) {
                $this->deleteContact($contactId);
            }
        } else {
            $contact = $this->_backend->getContact($_contactId);
            if (Zend_Registry::get('currentAccount')->hasGrant($contact->owner, Tinebase_Container::GRANT_DELETE)) {
                $this->_backend->deleteContact($_contactId);
            } else {
                throw new Exception('delete access to contact denied');
            }
        }
    }
}
