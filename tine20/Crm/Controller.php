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
 * @version     $Id: Controller.php 273 2007-11-08 22:51:16Z lkneschke $
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
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Crm_Controller
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Crm_Controller;
        }
        
        return self::$instance;
    }


    /**
     * get all leads
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getAllLeads($_filter, $_sort, $_dir, $_limit, $_start, $_dateFrom, $_dateTo, $_leadstate, $_probability)
    {
                
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        $result = $backend->getAllLeads($_filter, $_sort, $_dir, $_limit, $_start, $_dateFrom, $_dateTo, $_leadstate, $_probability);

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
        //$data = $_leadData->toArray();
          
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        
        $result = $backend->saveLead($_lead);
        
        return $result;
    }     
    
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
}
