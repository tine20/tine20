<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        use function from Tinebase_Application_Controller_Record_Abstract
 */

/**
 * contact controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 */
class Addressbook_Controller_Contact extends Tinebase_Application_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Addressbook';
        $this->_modelName = 'Addressbook_Model_Contact';
        $this->_backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        $this->_currentAccount = Tinebase_Core::getUser();
    }
    
    /**
     * holdes the instance of the singleton
     *
     * @var Addressbook_Controller_Contact
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller_Contact
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller_Contact();
        }
        
        return self::$_instance;
    }

    /****************************** overwritten functions ************************/
            
    /**
     * fetch one contact identified by $_userId
     *
     * @param   int $_userId
     * @return  Addressbook_Model_Contact
     * @throws  Addressbook_Exception_AccessDenied if user has no read grant
     */
    public function getContactByUserId($_userId)
    {
        $contact = $this->_backend->getByUserId($_userId);
        if (!$this->_currentAccount->hasGrant($contact->container_id, Tinebase_Model_Container::GRANT_READ)) {
            throw new Addressbook_Exception_AccessDenied('read access to contact denied');
        }            
        return $contact;            
    }
    
    /*************** add / update / delete contact *****************/  
    
    /**
     * add one contact
     *
     * @param   Addressbook_Model_Contact $_contact
     * @return  Addressbook_Model_Contact
     * 
     * @deprecated
     */
    public function createContact(Addressbook_Model_Contact $_contact)
    {
        try {
            $db = $this->_backend->getDb();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            if(empty($_contact->container_id)) {
                $containers = Tinebase_Container::getInstance()->getPersonalContainer($this->_currentAccount, 'Addressbook', $this->_currentAccount, Tinebase_Model_Container::GRANT_ADD);
                $_contact->container_id = $containers[0]->getId();
            }
            if (! $this->_currentAccount->hasGrant($_contact->container_id, Tinebase_Model_Container::GRANT_ADD)) {
                throw new Addressbook_Exception_AccessDenied('Add access to contacts in container ' . $_contact->container_id . ' denied.');
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
    
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        return $this->get($contact->getId());
    }
    
    /**
     * update one contact
     *
     * @param Addressbook_Model_Contact $_contact
     * @return  Addressbook_Model_Contact
     * 
     * @deprecated
     */
    public function updateContact(Addressbook_Model_Contact $_contact)
    {
        try {
            $db = $this->_backend->getDb();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $currentContact = $this->get($_contact->getId());
            
            // ACL checks
            if ($currentContact->container_id != $_contact->container_id) {
                if (! $this->_currentAccount->hasGrant($_contact->container_id, Tinebase_Model_Container::GRANT_ADD)) {
                    throw new Addressbook_Exception_AccessDenied('Add access to contacts in container ' . $_contact->container_id . ' denied.');
                }
                // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
                if (! $this->_currentAccount->hasGrant($currentContact->container_id, Tinebase_Model_Container::GRANT_DELETE)) {
                    throw new Addressbook_Exception_AccessDenied('Delete access to contacts in container ' . $currentContact->container_id . ' denied.');
                }
            } elseif (! $this->_currentAccount->hasGrant($_contact->container_id, Tinebase_Model_Container::GRANT_EDIT)) {
                throw new Addressbook_Exception_AccessDenied('Edit access to contacts in container ' . $_contact->container_id . ' denied.');
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
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }

        return $this->get($contact->getId());
    }
    
    /**
     * delete one or multiple contacts
     *
     * @param mixed $_contactId
     * @throws Exception 
     * 
     * @deprecated 
     */
    public function deleteContact($_contactId)
    {
        try {
            $db = $this->_backend->getDb();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            if (is_array($_contactId) or $_contactId instanceof Tinebase_Record_RecordSet) {
                foreach ($_contactId as $contactId) {
                    $this->deleteContact($contactId);
                }
            } else {
                $contact = $this->_backend->get($_contactId);
                $container = Tinebase_Container::getInstance()->getContainerById($contact->container_id);
                
                if ($this->_currentAccount->hasGrant($contact->container_id, Tinebase_Model_Container::GRANT_DELETE &&
                    $container->type != Tinebase_Model_Container::TYPE_INTERNAL)) {
                        
                    $this->_backend->delete($_contactId);
                    
                    // delete notes
                    Tinebase_Notes::getInstance()->deleteNotesOfRecord('Addressbook_Model_Contact', Addressbook_Backend_Factory::SQL, $contact->getId());
                    
                    // delete relations
                    Tinebase_Relations::getInstance()->setRelations('Addressbook_Model_Contact', 'Sql', $contact->getId(), array());
                    
                } else {
                    throw new Addressbook_Exception_AccessDenied('Delete access to contact denied.');
                }
            }
        
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }
}
