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
class Crm_Controller
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
     * @param int $_leadstate
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return Egwbase_Record_RecordSet subclass Crm_Model_Lead
     */
    public function getAllLeads($_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        $result = $backend->getAllLeads($_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads);

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
    public function saveLeadsources(Egwbase_Record_Recordset $_leadSources)
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
    public function saveLeadtypes(Egwbase_Record_Recordset $_leadTypes)
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
    public function saveProductSource(Egwbase_Record_Recordset $_productSource)
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
     * get one leadstate identified by id
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
    public function saveLeadstates(Egwbase_Record_Recordset $_leadStates)
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
    public function saveProducts(Egwbase_Record_Recordset $_productData)
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
        
        $this->sendNotifications((empty($_lead->lead_id) ? false : true), $updatedLead);
        
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
        
        $view->lead = $_lead;
        $view->leadState = $this->getLeadState($_lead->lead_leadstate_id);
        $view->leadType = $this->getLeadType($_lead->lead_leadtype_id);
        $view->leadSource = $this->getLeadSource($_lead->lead_leadsource_id);
        $view->container = Egwbase_Container_Container::getInstance()->getContainerById($_lead->lead_container);
        
        if(is_a($_lead->lead_start, 'Zend_Date')) {
            $view->leadStart = $_lead->lead_start->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->leadStart = '-';
        }
        
        if(is_a($_lead->lead_end, 'Zend_Date')) {
            $view->leadEnd = $_lead->lead_end->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->leadEnd = '-';
        }
        
        if(is_a($_lead->lead_end_scheduled, 'Zend_Date')) {
            $view->leadScheduledEnd = $_lead->lead_end_scheduled->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->leadScheduledEnd = '-';
        }
        
        $plain = $view->render('newLeadPlain.php');
        $html = $view->render('newLeadHtml.php');
        
        if($_isUpdate === true) {
            $subject = 'Lead updated: ' . $_lead->lead_name;
        } else {
            $subject = 'Lead added: ' . $_lead->lead_name;
        }
        
        $notification = Egwbase_Notification_Factory::getBackend(Egwbase_Notification_Factory::SMTP);
        $notification->send(Zend_Registry::get('currentAccount'), $subject, $plain, $html);
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
        $links = Egwbase_Links::getInstance()->getLinks('crm', $_leadId, $_application);
        
        return $links;
    }
    
    public function getEmptyLead()
    {
        $defaultData = array(
            'lead_leadstate_id'   => 1,
            'lead_leadtype_id'    => 1,
            'lead_leadsource_id'  => 1,
            'lead_start'          => new Zend_Date(),
            'lead_probability'    => 0
        );
        $emptyLead = new Crm_Model_Lead($defaultData, true);
        
        return $emptyLead;
    }
    
    public function setLinkedCustomer($_leadId, array $_contactIds)
    {
        Egwbase_Links::getInstance()->setLinks('crm', $_leadId, 'addressbook', $_contactIds, 'customer');
    }

    public function setLinkedPartner($_leadId, array $_contactIds)
    {
        Egwbase_Links::getInstance()->setLinks('crm', $_leadId, 'addressbook', $_contactIds, 'partner');
    }

    public function setLinkedAccount($_leadId, array $_contactIds)
    {
        Egwbase_Links::getInstance()->setLinks('crm', $_leadId, 'addressbook', $_contactIds, 'account');
    }
    
    public function setLinkedTasks($_leadId, array $_taskIds)
    {
        Egwbase_Links::getInstance()->setLinks('crm', $_leadId, 'tasks', $_taskIds, '');
    }    
}
