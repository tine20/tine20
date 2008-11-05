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
    
    /****************************** public functions ************************/
            
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
