<?php
/**
 * controller for CRM application
 * 
 * the main logic of the CRM application
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * controller class for CRM application
 * 
 * @package     Crm
 */
class Crm_Controller extends Tinebase_Container_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

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


    /**
     * get all leads
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    
    /**
     * get all leads, filtered by different criteria
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_limit
     * @param int $_start
     * @param int $_state
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return Tinebase_Record_RecordSet subclass Crm_Model_Lead
     */
    public function getAllLeads($_filter, $_sort, $_dir, $_limit, $_start, $_state, $_probability, $_getClosedLeads)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        $result = $backend->getAllLeads($_filter, $_sort, $_dir, $_limit, $_start, $_state, $_probability, $_getClosedLeads);

        return $result;
    }


    /**
     * get lead sources
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getLeadsources($_sort, $_dir)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);       
        $result = $backend->getLeadsources($_sort, $_dir);

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
          $daten = $_leadSources->toArray();
          
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        $backend->saveLeadsources($_leadSources);
    }  
    
    /**
     * get lead types
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getLeadtypes($_sort, $_dir)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);       
        $result = $backend->getLeadtypes($_sort, $_dir);

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
          $daten = $_leadTypes->toArray();
          
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        $backend->saveLeadtypes($_leadTypes);
    }      
    
    /**
     * get products available
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getProductsAvailable($_sort, $_dir)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);       
        $result = $backend->getProductsAvailable($_sort, $_dir);

        return $result;    
    }     

   /**
     * save Productsource
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveProductSource(Tinebase_Record_Recordset $_productSource)
    {
          $daten = $_productSource->toArray();
          
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        $backend->saveProductsource($_productSource);
    } 

    /**
     * get lead states
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getLeadstates($_sort, $_dir)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);       
        $result = $backend->getLeadstates($_sort, $_dir);

        return $result;    
    }

    /**
     * get one state identified by id
     *
     * @return Crm_Model_Leadstate
     */
    public function getLeadState($_id)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);       
        $result = $backend->getLeadState($_id);

        return $result;    
    }

    /**
     * get one leadsource identified by id
     *
     * @return Crm_Model_Leadsource
     */
    public function getLeadSource($_sourceId)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);       
        $result = $backend->getLeadSource($_sourceId);

        return $result;    
    }
    
    /**
     * get one leadtype identified by id
     *
     * @return Crm_Model_Leadtype
     */
    public function getLeadType($_typeId)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);       
        $result = $backend->getLeadType($_typeId);

        return $result;    
    }
    
    /**
     * delete products (belonging to one lead)
     *
     * @param string $_id
     *
     * @return array
     */
    public function deleteProducts($_id)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);       
        $result = $backend->deleteProducts($_id);

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
          $daten = $_leadStates->toArray();
          
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        $backend->saveLeadstates($_leadStates);
    } 
  

   /**
     * save Contacts
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveContacts(array $_contacts, $_id)
    {        
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        $backend->saveContacts($_contacts, $_id);
    }   
  
  
   /**
     * save Products
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveProducts(Tinebase_Record_Recordset $_productData)
    {
          $daten = $_productData->toArray();
          
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        $backend->saveProducts($_productData);
    }   
    
    
   /**
     * save Lead
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveLead(Crm_Model_Lead $_lead)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        
        $updatedLead = $backend->saveLead($_lead);
        
        $this->sendNotifications((empty($_lead->id) ? false : true), $updatedLead);
        
        return $updatedLead;
    }     
    
    /**
     * creates notification text and sends out notifications
     *
     * @param bool $_isUpdate set to true(lead got updated) or false(lead got added)
     * @param Crm_Model_Lead $_lead
     */
    protected function sendNotifications($_isUpdate, Crm_Model_Lead $_lead)
    {
        $view = new Zend_View();
        $view->setScriptPath('Crm/views');
        
        $view->updater = Zend_Registry::get('currentAccount');
        $view->lead = $_lead;
        $view->leadState = $this->getLeadState($_lead->id);
        $view->leadType = $this->getLeadType($_lead->leadtype_id);
        $view->leadSource = $this->getLeadSource($_lead->leadsource_id);
        $view->container = Tinebase_Container::getInstance()->getContainerById($_lead->container);
        
        if(is_a($_lead->start, 'Zend_Date')) {
            $view->start = $_lead->start->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->start = '-';
        }
        
        if(is_a($_lead->end, 'Zend_Date')) {
            $view->leadEnd = $_lead->end->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->leadEnd = '-';
        }
        
        if(is_a($_lead->end_scheduled, 'Zend_Date')) {
            $view->ScheduledEnd = $_lead->end_scheduled->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->ScheduledEnd = '-';
        }
        
        #$translate = new Zend_Translate('gettext', 'Crm/translations/de.mo', 'de');
        $translate = new Zend_Translate('gettext', 'Crm/translations', null, array('scan' => Zend_Translate::LOCALE_FILENAME));
        $translate->setLocale(Zend_Registry::get('locale'));
        
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
            $subject = $translate->_('Lead updated') . ': ' . $_lead->description_ld;
        } else {
            $subject = $translate->_('Lead added') . ': ' . $_lead->description_ld;
        }
        
        // send notifications to all accounts in the first step
        $accounts = Tinebase_Account::getInstance()->getFullAccounts();
        Tinebase_Notification::getInstance()->send(Zend_Registry::get('currentAccount'), $accounts, $subject, $plain, $html);
    }
    
    /**
     * get lead identified by leadId
     *
     * @param int $_leadId
     * @return Crm_Model_Lead
     */
    public function getLead($_leadId)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        
        $result = $backend->getLeadById($_leadId);
                
        return $result;
    }
    
    public function getLinks($_leadId, $_application = NULL)
    {
        $links = Tinebase_Links::getInstance()->getLinks('crm', $_leadId, $_application);
        
        return $links;
    }
    
    public function getEmptyLead()
    {
        $defaultState  = (isset(Zend_Registry::get('configFile')->crm->defaultstate) ? Zend_Registry::get('configFile')->crm->defaultstate : 1);
        $defaultType   = (isset(Zend_Registry::get('configFile')->crm->defaulttype) ? Zend_Registry::get('configFile')->crm->defaulttype : 1);
        $defaultSource = (isset(Zend_Registry::get('configFile')->crm->defaultsource) ? Zend_Registry::get('configFile')->crm->defaultsource : 1);
        
        $defaultData = array(
            'leadstate_id'   => $defaultState,
            'leadtype_id'    => $defaultType,
            'leadsource_id'  => $defaultSource,
            'start'          => new Zend_Date(),
            'probability'    => 0
        );
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($defaultData, true));
        $emptyLead = new Crm_Model_Lead($defaultData, true);
        
        return $emptyLead;
    }
    
    public function setLinkedCustomer($_leadId, array $_contactIds)
    {
        Tinebase_Links::getInstance()->setLinks('crm', $_leadId, 'addressbook', $_contactIds, 'customer');
    }

    public function setLinkedPartner($_leadId, array $_contactIds)
    {
        Tinebase_Links::getInstance()->setLinks('crm', $_leadId, 'addressbook', $_contactIds, 'partner');
    }

    public function setLinkedAccount($_leadId, array $_contactIds)
    {
        Tinebase_Links::getInstance()->setLinks('crm', $_leadId, 'addressbook', $_contactIds, 'account');
    }
    
    public function setLinkedTasks($_leadId, array $_taskIds)
    {
        Tinebase_Links::getInstance()->setLinks('crm', $_leadId, 'tasks', $_taskIds, '');
    }
    
    /**
     * get total count of all leads
     *
     * @return int count of all leads
     */
    public function getCountOfAllLeads($_filter, $_state, $_probability, $_getClosedLeads)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        
        return $backend->getCountOfAllLeads($_filter, $_state, $_probability, $_getClosedLeads);
    }

    /**
     * get total count of leads from shared folders
     *
     * @return int count of shared leads
     */
    public function getCountOfSharedLeads($_filter, $_state, $_probability, $_getClosedLeads)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        
        return $backend->getCountOfSharedLeads($_filter, $_state, $_probability, $_getClosedLeads);
    }

    /**
     * get total count of leads from other users
     *
     * @return int count of shared leads
     */
    public function getCountOfOtherPeopleLeads($_filter, $_state, $_probability, $_getClosedLeads)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        
        return $backend->getCountOfOtherPeopleLeads($_filter, $_state, $_probability, $_getClosedLeads);
    }
    
    /**
     * creates the initial folder for new accounts
     *
     * @param Tinebase_Account_Model_Account $_account the accountd object
     * @return Tinebase_Record_RecordSet of type Tinebase_Model_Container
     */
    public function createPersonalFolder(Tinebase_Account_Model_Account $_account)
    {
        $personalContainer = Tinebase_Container::getInstance()->addPersonalContainer($_account->accountId, 'crm', 'Personal Leads');
        
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
    }
}