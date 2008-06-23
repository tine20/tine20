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
 * @todo        rework functions
 * @todo        add rights
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

        $this->getLinkedProperties($lead);
        
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
                $this->getLinkedProperties($lead);
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
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }
        
        if(!$this->_currentAccount->hasGrant($_lead->container, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to leads in container ' . $_lead->container . ' denied');
        }
        
        $leadBackend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);
        $lead = $leadBackend->create($_lead);
        
        $this->setLinksForApplication($lead, $_lead->responsible, 'Addressbook', 'responsible');
        $this->setLinksForApplication($lead, $_lead->customer, 'Addressbook', 'customer');
        $this->setLinksForApplication($lead, $_lead->partner, 'Addressbook', 'partner');
        $this->setLinksForApplication($lead, $_lead->tasks, 'Tasks');
        
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
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }
        
        if(!$this->_currentAccount->hasGrant($_lead->container, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('add access to leads in container ' . $_lead->container . ' denied');
        }

        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);
        $lead = $backend->updateLead($_lead);
        
        $this->setLinksForApplication($lead, $_lead->responsible, 'Addressbook', 'responsible');
        $this->setLinksForApplication($lead, $_lead->customer, 'Addressbook', 'customer');
        $this->setLinksForApplication($lead, $_lead->partner, 'Addressbook', 'partner');
        $this->setLinksForApplication($lead, $_lead->tasks, 'Tasks');                

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
    
    // @todo rework link functions / use new relation class
    
    /**
     * set lead links for an application
     *
     * @param int|Crm_Model_Lead $_leadId
     * @param array $_linkIds
     * @param string $_applicationName
     * @param string $_remark
     * @return unknown
     * 
     * @todo    add set ALL links functions (for performance/easy to use)?
     */
    public function setLinksForApplication($_leadId, $_linkIds, $_applicationName, $_remark = NULL)
    {
        $leadId = Crm_Model_Lead::convertLeadIdToInt($_leadId);
        $applicationName = strtolower($_applicationName);
        $remark = ( $_remark !== NULL ) ? $_remark : $applicationName;
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . 'set ' . $_applicationName . 
        //    ' links for lead id ' . $leadId . ': ' . print_r($_linkIds, true));
        
        if(is_array($_linkIds)) {
            $result = Tinebase_Links::getInstance()->setLinks('crm', $leadId, $applicationName, $_linkIds, $remark);
        } else {
            $result = Tinebase_Links::getInstance()->deleteLinks('crm', $leadId, $applicationName, $_remark);
        }
        
        return $result;
    }    
                
    /**
     * get lead links for an application
     *
     * @param int|Crm_Model_Lead $_leadId
     * @param string $_applicationName
     * @return array with links
     * 
     * @todo    add get ALL links functions (for performance/easy to use)?
     */
    public function getLinksForApplication($_leadId, $_applicationName)
    {
        $leadId = Crm_Model_Lead::convertLeadIdToInt($_leadId);
        $applicationName = strtolower($_applicationName);
        
        $result = Tinebase_Links::getInstance()->getLinks('crm', $leadId, $applicationName);
                        
        return $result;
    }    
    
    /**
     * fetch ids of linked properties(contacts, tasks, notes)
     *
     * @param Crm_Model_Lead $_lead
     * @deprecated ?
     * 
     * @todo    replace with getLinksForApplication / new relations handling
     */
    protected function getLinkedProperties(Crm_Model_Lead &$_lead)
    {
        $links = Tinebase_Links::getInstance()->getLinks('crm', $_lead->getId());
        $customer = array();
        $partner = array();
        $responsible = array();
        $tasks = array();
        foreach($links as $link) {
            switch(strtolower($link['applicationName'])) {
                case 'addressbook':
                    switch($link['remark']) {
                        case 'customer':
                            $customer[] = $link['recordId'];
                            break;
                        case 'partner':
                            $partner[] = $link['recordId'];
                            break;
                        case 'responsible':
                            $responsible[] = $link['recordId'];
                            break;
                    }
                    break;
                case 'tasks':
                    $tasks[] = $link['recordId'];
                    break;
            }
        }
        $_lead->customer = $customer;
        $_lead->partner = $partner;
        $_lead->responsible = $responsible;
        $_lead->tasks = $tasks;
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
     * save Products
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveProducts(Tinebase_Record_Recordset $_products)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::PRODUCTS);
        $result = $backend->saveProducts($_products);
        
        return $result;
    } 
    
    /**
     * delete products (belonging to one lead)
     *
     * @param string $_id
     * @return array
     */
    public function deleteProducts($_id)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::PRODUCTS);
        $result = $backend->deleteProducts($_id);

        return $result;    
    }     

    /**
     * save Products linked to a lead
     *
     * @todo implement
     * @todo write test
     */ 
    public function saveLeadProducts($_leadId, Tinebase_Record_Recordset $_products)
    {
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
     * save leadsources
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveLeadsources(Tinebase_Record_Recordset $_leadSources)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_SOURCES);
        $result = $backend->saveLeadsources($_leadSources);
        
        return $result;
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
     * save Leadtypes
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveLeadtypes(Tinebase_Record_Recordset $_leadTypes)
    {
    	$backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_TYPES);
        $result = $backend->saveLeadtypes($_leadTypes);
        
        return $result;
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
     * save Leadstates
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveLeadstates(Tinebase_Record_Recordset $_leadStates)
    {
    	$backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_STATES);
        $result = $backend->saveLeadstates($_leadStates);
        
        return $result;
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
    
          
        
    
}