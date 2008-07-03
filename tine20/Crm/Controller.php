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
 * @todo        add other rights
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
     * @var Tinebase_User_Model_User
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
                
        return $lead;
    }

    /**
     * returns an empty lead with some defaults set
     *
     * @return Crm_Model_Lead
     * @deprecated ?
     * @todo    move functionality to getLead() or javascript?
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
    	$container = array();
    	
        foreach ($_filter->container as $containerId) {
            if ($this->_currentAccount->hasGrant($containerId, Tinebase_Container::GRANT_READ)) {
                $container[] = $containerId;
            }
        }
        $_filter->container = $container;
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
        $this->checkRight('MANAGE_LEADS');
        
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }
        
        if(!$this->_currentAccount->hasGrant($_lead->container, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to leads in container ' . $_lead->container . ' denied');
        }
        
        $leadBackend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);
        $lead = $leadBackend->create($_lead);
        
        // set relations & links
        $this->setLeadLinks($lead->getId(), $_lead);        
        
        if (!empty($_lead->tags)) {
            $lead->tags = $_lead->tags;
            Tinebase_Tags::getInstance()->setTagsOfRecord($lead);
        }        
                
        //$this->sendNotifications(false, $lead, $_lead->responsible);
        
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
        $this->checkRight('MANAGE_LEADS');
        
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }
        
        if(!$this->_currentAccount->hasGrant($_lead->container, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('add access to leads in container ' . $_lead->container . ' denied');
        }

        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);
        $lead = $backend->updateLead($_lead);

        // set relations & links
        $this->setLeadLinks($lead->getId(), $_lead);        
        
        if (isset($_lead->tags)) {
            Tinebase_Tags::getInstance()->setTagsOfRecord($_lead);
        }
        
        //$this->sendNotifications(true, $lead, $_lead->responsible);
        
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
        $this->checkRight('MANAGE_LEADS');
        
        if(is_array($_leadId) or $_leadId instanceof Tinebase_Record_RecordSet) {
            foreach($_leadId as $leadId) {
                $this->deleteLead($leadId);
            }
        } else {
            $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);            
            $lead = $backend->get($_leadId);
            
            if($this->_currentAccount->hasGrant($lead->container, Tinebase_Container::GRANT_DELETE)) {
                $backend->delete($_leadId);
            } else {
                throw new Exception('delete access to lead denied');
            }
        }
    }

    /*********************** links functions ************************/
    
    /**
     * set lead links and relations (contacts, tasks, products)
     *
     * @param integer $_leadId
     * @param Crm_Model_Lead $_lead
     * 
     * @todo add different backend types
     * @todo add creation of new records
     */
    private function setLeadLinks($_leadId, Crm_Model_Lead $_lead)
    {
        $relationTypes = array(
            'responsible' => array(
                'model'     => 'Addressbook_Model_Contact',
                'backend'   => Addressbook_Backend_Factory::SQL,
                'type'      => 'RESPONSIBLE'
        ),
            'customer' => array(
                'model'     => 'Addressbook_Model_Contact',
                'backend'   => Addressbook_Backend_Factory::SQL,
                'type'      => 'CUSTOMER'
            ), 
            'partner' => array(
                'model'     => 'Addressbook_Model_Contact',
                'backend'   => Addressbook_Backend_Factory::SQL,
                'type'      => 'PARTNER'
            ), 
            'tasks' => array(
                'model'     => 'Tasks_Model_Task',
                'backend'   => Tasks_Backend_Factory::SQL,
                'type'      => 'TASK'
            ), 
        );
        
        // build relation data array
        $relationData = array();
        foreach ($relationTypes as $type => $values) {  
            if (isset($_lead[$type])) {          
                foreach ($_lead[$type] as $relation) {
                    if ($relation instanceOf Tinebase_Relation_Model_Relation) {
                        $relationData[] = $relation->toArray();
                    } else {
                        $data = array(
                            'own_model'              => 'Crm_Model_Lead',
                            'own_backend'            => Crm_Backend_Factory::SQL,
                            'own_id'                 => $_leadId,
                            'own_degree'             => Tinebase_Relation_Model_Relation::DEGREE_SIBLING,
                            'related_model'          => $values['model'],
                            'related_backend'        => $values['backend'],
                            'related_id'             => $relation,
                            'type'                   => $values['type']                    
                        );
                        
                        $relationData[] = $data;
                    }
                }
            }
        }

        // set relations
        Tinebase_Relations::getInstance()->setRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $_leadId, $relationData);       

        // add product links
        $productsArray = array();
        if (is_array($_lead->products)) {
            foreach ($_lead->products as $product) {
                $product['lead_id'] = $_leadId; 
                $productsArray[] = $product;     
            }
        }
        try {
            $products = new Tinebase_Record_RecordSet('Crm_Model_LeadProduct', $productsArray);
        } catch (Exception $e) {
            throw $e;
        }                
        
        $this->saveLeadProducts($_leadId, $products);                
        
    }

    /**
     * get lead links and relations (contacts, tasks, products)
     *
     * @param Crm_Model_Lead $_lead
     * 
     * @todo add different backend types
     */
    private function getLeadLinks(Crm_Model_Lead &$_lead)
    {
        $relations = Tinebase_Relations::getInstance()->getRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $_lead->getId());        
        
        $customer = array();
        $partner = array();
        $responsible = array();
        $tasks = array();
        foreach($relations as $relation) {
            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . $relation->type . ' for id ' . $_lead->getId());
            switch(strtolower($relation->type)) {
                case 'customer':
                    $customer[] = $relation;
                    break;
                case 'partner':
                    $partner[] = $relation;
                    break;
                case 'responsible':
                    $responsible[] = $relation;
                    break;
                case 'task':
                    $tasks[] = $relation;
                    break;
            }
        }
        $_lead->customer = $customer;
        $_lead->partner = $partner;
        $_lead->responsible = $responsible;
        $_lead->tasks = $tasks;
        $_lead->products = $this->getLeadProducts($_lead->getId());
        
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
     * @param mixed[int|Tinebase_User_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet                            of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_accountId)
    {
        $accountId = Tinebase_User_Model_User::convertUserIdToInt($_accountId);
        
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => 'Personal Leads',
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
     * @param mixed[int|Tinebase_User_Model_User] $_account   the accountd object
     * @return void
     * 
     * @todo    implement
     */
    public function deletePersonalFolder($_accountId)
    {
        $accountId = Tinebase_User_Model_User::convertUserIdToInt($_accountId);
        
        // delete personal folder here
    }
    
    /**
     * creates notification text and sends out notifications
     *
     * @param bool $_isUpdate set to true(lead got updated) or false(lead got added)
     * @param Crm_Model_Lead $_lead
     * @return void
     */
    protected function sendNotifications($_isUpdate, Crm_Model_Lead $_lead, $_contactIds)
    {
        if(empty($_contactIds)) {
            // nothing to do
            return;
        }
        
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views');
        
        $view->updater = $this->_currentAccount;
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
        
        if($_isUpdate === true) {
            $subject = $translate->_('Lead updated') . ': ' . $_lead->lead_name;
        } else {
            $subject = $translate->_('Lead added') . ': ' . $_lead->lead_name;
        }
        
        Tinebase_Notification::getInstance()->send($this->_currentAccount, $_contactIds, $subject, $plain, $html);
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
            if ( Tinebase_Acl_Roles::getInstance()->hasRight('Admin', $this->_currentAccount->getId(), $rightToCheck) ) {
                $hasRight = TRUE;
                break;    
            }
        }
        
        if ( !$hasRight ) {
            throw new Exception("You are not allowed to $_right !");
        }        
                
    }    
    
}