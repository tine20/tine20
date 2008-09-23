<?php
/**
 * controller for CRM application
 * 
 * the main logic of the CRM application
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add more rights? (removed MANAGE_LEADS for the moment, it is covered with the container grants)
 */

/**
 * controller class for CRM application
 * 
 * @package     Crm
 */
class Crm_Controller extends Tinebase_Container_Abstract implements Tinebase_Events_Interface
{
    /**
     * Holds instance of current account
     *
     * @var Tinebase_Model_User
     */
    protected $_currentAccount;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
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
     * holdes the instance of the singleton
     *
     * @var Crm_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Crm_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Crm_Controller;
        }
        
        return self::$_instance;
    }    
    
    /*********** get / search / count leads **************/
    
    /**
     * get lead identified by leadId
     *
     * @param int $_leadId
     * @return Crm_Model_Lead
     */
    public function getLead($_leadId)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);
        $lead = $backend->get($_leadId);
        
        if (!$this->_currentAccount->hasGrant($lead->container, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read permission to lead denied');
        }

        //$this->getLinkedProperties($lead);
        $this->getLeadLinks($lead);
        
        Tinebase_Tags::getInstance()->getTagsOfRecord($lead);
        
        $lead->notes = Tinebase_Notes::getInstance()->getNotesOfRecord('Crm_Model_Lead', $lead->getId());        
                
        return $lead;
    }

    /**
     * returns an empty lead with some defaults set
     * - add creator as internal contact
     *
     * @return Crm_Model_Lead
     * 
     * @todo add creator as internal contact 
     * @todo update test
     */
    public function getEmptyLead()
    {
        $defaultState  = (isset(Zend_Registry::get('configFile')->crm->defaultstate) ? Zend_Registry::get('configFile')->crm->defaultstate : 1);
        $defaultType   = (isset(Zend_Registry::get('configFile')->crm->defaulttype) ? Zend_Registry::get('configFile')->crm->defaulttype : 1);
        $defaultSource = (isset(Zend_Registry::get('configFile')->crm->defaultsource) ? Zend_Registry::get('configFile')->crm->defaultsource : 1);
        
        $defaultData = array(
            'leadstate_id'   => $defaultState,
            'leadtype_id'    => $defaultType,
            'leadsource_id'  => $defaultSource,
            'start'          => Zend_Date::now(),
            'probability'    => 0
        );
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($defaultData, true));
        $emptyLead = new Crm_Model_Lead($defaultData, true);
        
        // add creator as RESPONSIBLE
        /*
        $userContact = Addressbook_Controller::getInstance()->getContactByUserId($this->_currentAccount->getId());
        //$emptyLead->relations = new Tinebase_Model_Relation(array(
        $emptyLead->relations = array(array(
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => Crm_Backend_Factory::SQL,
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Addressbook_Model_Contact',
            'related_backend'        => Addressbook_Backend_Factory::SQL,
            'related_id'             => $userContact->getId(),
            'type'                   => 'RESPONSIBLE',
            'related_record'         => $userContact->toArray()
        ));
        */
        
        return $emptyLead;
    }
    
    /**
     * Search for leads matching given filter
     *
     * @param Crm_Model_LeadFilter $_filter
     * @param Crm_Model_LeadPagination $_pagination
     * @param bool $_getRelations
     * 
     * @return Tinebase_Record_RecordSet
     */
    public function searchLeads(Crm_Model_LeadFilter $_filter, Crm_Model_LeadPagination $_pagination, $_getRelations = FALSE)
    {
        $this->_checkContainerACL($_filter);
        
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);        
        $leads = $backend->search($_filter, $_pagination);
        
        if ( $_getRelations ) {
            foreach ($leads as $lead) {
                //$this->getLinkedProperties($lead);
                $this->getLeadLinks($lead);
            }
        }
        
        return $leads;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Crm_Model_LeadFilter $_filter
     * @return int
     */
    public function searchLeadsCount(Crm_Model_LeadFilter $_filter) 
    {
        $this->_checkContainerACL($_filter);
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);
        $count = $backend->searchCount($_filter);
        
        return $count;
    }
    
    /**
     * Removes containers where current user has no access to.
     * 
     * @param Crm_Model_LeadFilter $_filter
     * @return void
     */
    protected function _checkContainerACL($_filter)
    {
        $readableContainer = $this->_currentAccount->getContainerByACL('Crm', Tinebase_Container::GRANT_READ);
        $_filter->container = array_intersect($_filter->container, $readableContainer->getArrayOfIds());
    }    
        
    /*************** add / update / delete lead *****************/    
    
    /**
     * add Lead
     *
     * @param Crm_Model_Lead $_lead the lead to add
     * @return Crm_Model_Lead the newly added lead
     * 
     * @todo add notifications later
     */ 
    public function createLead(Crm_Model_Lead $_lead)
    {
        try {
            $db = Zend_Registry::get('dbAdapter');
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            //$this->checkRight('MANAGE_LEADS');
            
            if(!$_lead->isValid()) {
                throw new Exception('lead object is not valid');
            }
            
            if(!$this->_currentAccount->hasGrant($_lead->container, Tinebase_Container::GRANT_ADD)) {
                throw new Exception('add access to leads in container ' . $_lead->container . ' denied');
            }
            
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($_lead, 'create');
            $leadBackend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);
            $lead = $leadBackend->create($_lead);
            
            // set relations & links
            $this->setLeadLinks($lead->getId(), $_lead);        
            
            if (!empty($_lead->tags)) {
                $lead->tags = $_lead->tags;
                Tinebase_Tags::getInstance()->setTagsOfRecord($lead);
            }        
    
            if (isset($_lead->notes)) {
                $lead->notes = $_lead->notes;
                Tinebase_Notes::getInstance()->setNotesOfRecord($lead);
            }
                    
            // add created note to record
            Tinebase_Notes::getInstance()->addSystemNote($lead, $this->_currentAccount->getId(), 'created');
            $this->sendNotifications($lead, $this->_currentAccount, 'created');
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        return $this->getLead($lead->getId());
    }     
        
   /**
     * update Lead
     *
     * @param Crm_Model_Lead $_lead the lead to update
     * @return Crm_Model_Lead the updated lead
     * 
     * @todo add notifications later
     */ 
    public function updateLead(Crm_Model_Lead $_lead)
    {
        try {
            $db = Zend_Registry::get('dbAdapter');
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            //$this->checkRight('MANAGE_LEADS');
            
            if(!$_lead->isValid()) {
                throw new Exception('lead object is not valid');
            }
            $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);
            $currentLead = $backend->get($_lead->getId());
            
            // ACL checks
            if ($currentLead->container != $_lead->container) {
                if (! $this->_currentAccount->hasGrant($_lead->container, Tinebase_Container::GRANT_ADD)) {
                    throw new Exception('add access in container ' . $_lead->container . ' denied');
                }
                // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
                if (! $this->_currentAccount->hasGrant($currentLead->container, Tinebase_Container::GRANT_DELETE)) {
                    throw new Exception('delete access in container ' . $currentLead->container . ' denied');
                }
            } elseif (! $this->_currentAccount->hasGrant($_lead->container, Tinebase_Container::GRANT_EDIT)) {
                throw new Exception('edit access in container ' . $_lead->container . ' denied');
            }
    
            // concurrency management & history log
            $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
            $modLog->manageConcurrentUpdates($_lead, $currentLead, 'Crm_Model_Lead', Crm_Backend_Factory::SQL, $_lead->getId());
            $modLog->setRecordMetaData($_lead, 'update', $currentLead);
            $currentMods = $modLog->writeModLog($_lead, $currentLead, 'Crm_Model_Lead', Crm_Backend_Factory::SQL, $_lead->getId());
            
            $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);
            $lead = $backend->update($_lead);
    
            // set relations & links
            $this->setLeadLinks($lead->getId(), $_lead);        
            
            if (isset($_lead->tags)) {
                Tinebase_Tags::getInstance()->setTagsOfRecord($_lead);
            }
    
            if (isset($_lead->notes)) {
                Tinebase_Notes::getInstance()->setNotesOfRecord($_lead);
            }        
            
            // add changed note to record
            if (count($currentMods) > 0) {
                Tinebase_Notes::getInstance()->addSystemNote($lead, $this->_currentAccount->getId(), 'changed', $currentMods);
                $this->sendNotifications($lead, $this->_currentAccount, 'changed', $currentMods);
            }        
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        return $this->getLead($lead->getId());
    }

    /**
     * delete a lead
     *
     * @param int|array|Tinebase_Record_RecordSet|Crm_Model_Lead $_leadId
     * @return void
     */
    public function deleteLead($_leadId)
    {
        try {
            $db = Zend_Registry::get('dbAdapter');
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            //$this->checkRight('MANAGE_LEADS');
            
            if(is_array($_leadId) or $_leadId instanceof Tinebase_Record_RecordSet) {
                foreach($_leadId as $leadId) {
                    $this->deleteLead($leadId);
                }
            } else {
                $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);            
                $lead = $backend->get($_leadId);
                
                if($this->_currentAccount->hasGrant($lead->container, Tinebase_Container::GRANT_DELETE)) {
                    $backend->delete($_leadId);
    
                    // delete notes
                    Tinebase_Notes::getInstance()->deleteNotesOfRecord('Crm_Model_Lead', 'Sql', $lead->getId());                
                } else {
                    throw new Exception('delete access to lead denied');
                }
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }

    /*********************** links functions ************************/
    
    /**
     * set lead links and relations (contacts, tasks, products)
     *
     * @param integer $_leadId
     * @param Crm_Model_Lead $_lead
     */
    private function setLeadLinks($_leadId, Crm_Model_Lead $_lead)
    {
        // set relations
        if (isset($_lead->relations) && is_array($_lead->relations)) {
            Tinebase_Relations::getInstance()->setRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $_leadId, $_lead->relations);
        }

        // add product links
        $productsArray = array();
        if (isset($_lead->products) && is_array($_lead->products)) {
            foreach ($_lead->products as $product) {
                $product['lead_id'] = $_leadId; 
                $productsArray[] = $product;     
            }
        }        
        $products = new Tinebase_Record_RecordSet('Crm_Model_LeadProduct', $productsArray);
        $this->saveLeadProducts($_leadId, $products);                        
    }

    /**
     * get lead links and relations (contacts, tasks, products)
     *
     * @param Crm_Model_Lead $_lead
     */
    private function getLeadLinks(Crm_Model_Lead &$_lead)
    {
        $_lead->products = $this->getLeadProducts($_lead->getId());
        $_lead->relations = Tinebase_Relations::getInstance()->getRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $_lead->getId());
    }    
    
    /*************** products functions *****************/

    /**
     * get products available
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     * 
     */
    public function getProducts($_sort = 'id', $_dir = 'ASC')
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::PRODUCTS);
        $result = $backend->getAll($_sort, $_dir);
        
        return $result;    
    }     

    /**
     * get product
     *
     * @param integer $_productId
     * @return Crm_Model_Product
     * 
     */
    public function getProduct($_productId)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::PRODUCTS);
        $result = $backend->get($_productId);
        
        return $result;    
    }     
    
    /**
     * saves products
     *
     * Saving products means to calculate the difference between posted data
     * and existing data and than deleting, creating or updating as needed.
     * Every change is done one by one.
     * 
     * @param Tinebase_Record_Recordset $_products Products to save
     * @return Tinebase_Record_Recordset Exactly the same record set as in argument $_products
     */
    public function saveProducts(Tinebase_Record_Recordset $_products)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::PRODUCTS);
        $existingProducts = $backend->getAll();
        
        $migration = $existingProducts->getMigration($_products->getArrayOfIds());
        
        // delete
        foreach ($migration['toDeleteIds'] as $id) {
        	$backend->delete($id);
        }
        
        // add / create
        foreach ($_products as $product) {
        	if (in_array($product->id, $migration['toCreateIds'])) {
        		$backend->create($product);
        	}
        }
        
        // update
        foreach ($_products as $product) {
        	if (in_array($product->id, $migration['toUpdateIds'])) {
        		$backend->update($product);
        	}
        }
        
        return $_products;
    } 
    
    /**
     * get Products linked to a lead
     *
     * @param string $_leadId
     * @return Tinebase_Record_Recordset products
     * 
     * @todo write test
     */ 
    public function getLeadProducts($_leadId)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_PRODUCTS);
        $result = $backend->get($_leadId);

        return $result;    
    } 
    
    /**
     * save Products linked to a lead
     *
     * @param string $_leadId
     * @param Tinebase_Record_Recordset $_products
     * 
     * @todo write test
     */ 
    public function saveLeadProducts($_leadId, Tinebase_Record_Recordset $_products)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_PRODUCTS);
        $backend->saveProducts($_leadId, $_products);
    } 
    
    /*********** handling of lead sources/types/states **************/
    
    /**
     * get lead sources
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Crm_Model_Leadsource
     */
    public function getLeadSources($_sort = 'id', $_dir = 'ASC')
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_SOURCES);
    	$result = $backend->getAll($_sort, $_dir);

        return $result;    
    }
        
    /**
     * saves lead sources
     *
     * Saving lead source means to calculate the difference between posted data
     * and existing data and than deleting, creating or updating as needed.
     * Every change is done one by one.
     * 
     * @param Tinebase_Record_Recordset $_leadSources Lead states to save
     * @return Tinebase_Record_Recordset Exactly the same record set as in argument $_leadSources
     */
    public function saveLeadsources(Tinebase_Record_Recordset $_leadSources)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_SOURCES);
        $existingLeadSources = $backend->getAll();
        
        $migration = $existingLeadSources->getMigration($_leadSources->getArrayOfIds());
        
        // delete
        foreach ($migration['toDeleteIds'] as $id) {
            $backend->delete($id);
        }
        
        // add / create
        foreach ($_leadSources as $leadSource) {
            if (in_array($leadSource->id, $migration['toCreateIds'])) {
                $backend->create($leadSource);
            }
        }
        
        // update
        foreach ($_leadSources as $leadSource) {
            if (in_array($leadSource->id, $migration['toUpdateIds'])) {
                $backend->update($leadSource);
            }
        }
        
        return $_leadSources;
    }
    
    /**
     * get lead types
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Crm_Model_Leadtype
     */
    public function getLeadTypes($_sort = 'id', $_dir = 'ASC')
    {
    	$backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_TYPES);
        $result = $backend->getAll($_sort, $_dir);

        return $result;    
    }    
    
    /**
     * get one leadtype identified by id
     *
     * @param int $_typeId
     * @return Crm_Model_Leadtype
     */
    public function getLeadType($_typeId)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_TYPES);
        $result = $backend->get($_typeId);
        $result->translate();

        return $result;    
    }
    
    /**
     * saves lead types
     *
     * Saving lead types means to calculate the difference between posted data
     * and existing data and than deleting, creating or updating as needed.
     * Every change is done one by one.
     * 
     * @param Tinebase_Record_Recordset $_leadTypes Lead types to save
     * @return Tinebase_Record_Recordset Exactly the same record set as in argument $_leadTypes
     */
    public function saveLeadtypes(Tinebase_Record_Recordset $_leadTypes)
    {
    	$backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_TYPES);
        $existingLeadTypes = $backend->getAll();
        
        $migration = $existingLeadTypes->getMigration($_leadTypes->getArrayOfIds());
        
        // delete
        foreach ($migration['toDeleteIds'] as $id) {
            $backend->delete($id);
        }
        
        // add / create
        foreach ($_leadTypes as $leadType) {
            if (in_array($leadType->id, $migration['toCreateIds'])) {
                $backend->create($leadType);
            }
        }
        
        // update
        foreach ($_leadTypes as $leadType) {
            if (in_array($leadType->id, $migration['toUpdateIds'])) {
                $backend->update($leadType);
            }
        }
        
        return $_leadTypes;
    }      
    
    /**
     * get lead states
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Crm_Model_Leadstate
     */
    public function getLeadStates($_sort = 'id', $_dir = 'ASC')
    {
    	$backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_STATES);
        $result = $backend->getAll($_sort, $_dir);

        return $result;    
    }

    /**
     * get one state identified by id
     *
     * @param int $_id
     * @return Crm_Model_Leadstate
     */
    public function getLeadState($_id)
    {
    	$backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_STATES);
        $result = $backend->get($_id);
        $result->translate();

        return $result;    
    }

    /**
     * saves lead states
     *
     * Saving lead states means to calculate the difference between posted data
     * and existing data and than deleting, creating or updating as needed.
     * Every change is done one by one.
     * 
     * @param Tinebase_Record_Recordset $_leadStates Lead states to save
     * @return Tinebase_Record_Recordset Exactly the same record set as in argument $_leadStates
     */
    public function saveLeadstates(Tinebase_Record_Recordset $_leadStates)
    {
    	$backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_STATES);
        $existingLeadStates = $backend->getAll();
        
        $migration = $existingLeadStates->getMigration($_leadStates->getArrayOfIds());
        
        // delete
        foreach ($migration['toDeleteIds'] as $id) {
            $backend->delete($id);
        }
        
        // add / create
        foreach ($_leadStates as $leadState) {
            if (in_array($leadState->id, $migration['toCreateIds'])) {
                $backend->create($leadState);
            }
        }
        
        // update
        foreach ($_leadStates as $leadState) {
            if (in_array($leadState->id, $migration['toUpdateIds'])) {
                $backend->update($leadState);
            }
        }
        
        return $_leadStates;
    } 
  
    
    /**
     * get one leadsource identified by id
     *
     * @return Crm_Model_Leadsource
     */
    public function getLeadSource($_sourceId)
    {
    	$backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_SOURCES);
        $result = $backend->get($_sourceId);
        $result->translate();

        return $result;    
    }
    
    /********************* event handler and notifications ***************************/
    
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
     * @param mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet                            of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_accountId)
    {
        $translation = Tinebase_Translation::getTranslation('Crm');
        
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $account = Tinebase_User::getInstance()->getUserById($accountId);
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => sprintf($translation->_("%s's personal leads"), $account->accountFullName),
            'type'              => Tinebase_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId() 
        ));
        
        $personalContainer = Tinebase_Container::getInstance()->addContainer($newContainer, NULL, FALSE, $accountId);
        $personalContainer->account_grants = Tinebase_Container::GRANT_ANY;
        
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
    }

    /**
     * delets the personal folder for deleted accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return void
     * 
     * @todo    implement
     */
    public function deletePersonalFolder($_accountId)
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        // delete personal folder here
    }
    
    /**
     * creates notification text and sends out notifications
     *
     * @todo:
     *  - add changes to mail body
     *  - find updater in addressbook to notify him
     *  
     * @param Crm_Model_Lead            $_lead
     * @param Tinebase_Model_FullUser   $_updater
     * @param string                    $_action   {created|changed}
     * @param Tinebase_Record_RecordSet $_updates
     * @return void
     */
    protected function sendNotifications(Crm_Model_Lead $_lead, Tinebase_Model_FullUser $_updater, $_action, $_updates=array())
    {
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views');
        
        $view->updater = $_updater;
        $view->lead = $_lead;
        $view->leadState = $this->getLeadState($_lead->leadstate_id);
        $view->leadType = $this->getLeadType($_lead->leadtype_id);
        $view->leadSource = $this->getLeadSource($_lead->leadsource_id);
        $view->container = Tinebase_Container::getInstance()->getContainerById($_lead->container);
        
        if($_lead->start instanceof Zend_Date) {
            $view->start = $_lead->start->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->start = '-';
        }
        
        if($_lead->end instanceof Zend_Date) {
            $view->leadEnd = $_lead->end->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->leadEnd = '-';
        }
        
        if($_lead->end_scheduled instanceof Zend_Date) {
            $view->ScheduledEnd = $_lead->end_scheduled->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->ScheduledEnd = '-';
        }
        
        $translate = Tinebase_Translation::getTranslation('Crm');
        
        $view->lang_state = $translate->_('State');
        $view->lang_type = $translate->_('Type');
        $view->lang_source = $translate->_('Source');
        $view->lang_start = $translate->_('Start');
        $view->lang_scheduledEnd = $translate->_('Scheduled end');
        $view->lang_end = $translate->_('End');
        $view->lang_turnover = $translate->_('Turnover');
        $view->lang_probability = $translate->_('Probability');
        $view->lang_folder = $translate->_('Folder');
        $view->lang_updatedBy = $translate->_('Updated by');
        
        $plain = $view->render('newLeadPlain.php');
        $html = $view->render('newLeadHtml.php');
        
        if($_action == 'changed') {
            $subject = sprintf($translate->_('Lead %s has been changed'), $_lead->lead_name);
        } else {
            $subject = sprintf($translate->_('Lead %s has been creaded'), $_lead->lead_name);
        }

        // create pdf
        try {
            $pdfGenerator = new Crm_Pdf();
            $pdfGenerator->generateLeadPdf($_lead);
            $pdfOutput = $pdfGenerator->render();
        } catch ( Zend_Pdf_Exception $e ) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' error creating pdf: ' . $e->__toString());
            $pdfOutput = NULL;
        }
                
        $recipients = $this->_getNotificationRecipients($_lead);
        // send notificaton to updater in any case!
        // UGH! how to find out his adb id?
        //if (! in_array($_updater->accountId, $recipients)) {
        //    $recipients[] = $_updater->accountId;
        //}
        
        Tinebase_Notification::getInstance()->send($this->_currentAccount, $recipients, $subject, $plain, $html, $pdfOutput);
    }
    
    /**
     * returns recipients for a lead notification
     *
     * @param  Crm_Model_Lead $_lead
     * @return array          array of int|Addressbook_Model_Contact
     */
    protected function _getNotificationRecipients(Crm_Model_Lead $_lead) {
        $recipients = array();
        
        $relations = Tinebase_Relations::getInstance()->getRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $_lead->getId(), true);
        
        foreach ($relations as $relation) {
            if ($relation->related_model == 'Addressbook_Model_Contact' && $relation->type == 'RESPONSIBLE') {
                $recipients[] = $relation->related_record;
            }
        }
        
        // if no responsibles are defined, send message to all readers of container
        if (empty($recipients)) {
            Zend_Registry::get('logger')->debug(__CLASS__ . '::' . __METHOD__ . '::' . __LINE__ . ' no responsibles found for lead: ' . 
                $_lead->getId() . 'sending notification to all people having read access to container ' . $_lead->container);
                
            $containerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($_lead->container);
            // NOTE: we just send notifications to users, not to groups or anyones!
            foreach ($containerGrants as $grant) {
                if ($grant['account_type'] == 'user' && $grant['readGrant'] == 1) {
                    $recipients[] = $grant['account_id'];
                }
            }
        }
        
        return $recipients;
    }
    
    /**
     * generic check admin rights function
     * rules: 
     * - ADMIN right includes all other rights
     * - MANAGE_* right includes VIEW_* right 
     * 
     * @param   string  $_right to check
     * @todo    think about moving that to Tinebase_Acl or Tinebase_Application
     */    
    protected function checkRight( $_right ) {
        
        // array with the rights that should be checked, ADMIN is in it per default
        $rightsToCheck = array ( Tinebase_Acl_Rights::ADMIN );
        
        if ( preg_match("/MANAGE_/", $_right) ) {
            $rightsToCheck[] = constant('Crm_Acl_Rights::' . $_right);
        }

        if ( preg_match("/VIEW_([A-Z_]*)/", $_right, $matches) ) {
            $rightsToCheck[] = constant('Crm_Acl_Rights::' . $_right);
            // manage right includes view right
            $rightsToCheck[] = constant('Crm_Acl_Rights::MANAGE_' . $matches[1]);
        }
        
        $hasRight = FALSE;
        
        foreach ( $rightsToCheck as $rightToCheck ) {
            if ( Tinebase_Acl_Roles::getInstance()->hasRight('Crm', $this->_currentAccount->getId(), $rightToCheck) ) {
                $hasRight = TRUE;
                break;    
            }
        }
        
        if ( !$hasRight ) {
            throw new Exception("You are not allowed to $_right !");
        }        
                
    }    
    
}
