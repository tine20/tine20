<?php
/**
 * Tine 2.0
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Crm application
 *
 * @package     Crm
 */
class Crm_Frontend_Json extends Tinebase_Application_Frontend_Json_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_appname = 'Crm';
    
    /**
     * user timezone
     *
     * @var unknown_type
     */
    protected $_userTimezone;

    /**
     * server timezone
     *
     * @var unknown_type
     */
    protected $_serverTimezone;

    /**
     * constructor
     *
     */
    public function __construct()
    {
        $this->_userTimezone = Zend_Registry::get('userTimeZone');
        $this->_serverTimezone = date_default_timezone_get();
    }
    
    /*************************** get leads ****************************/

    /**
     * get single lead
     * fetches a lead and adds resolves linked objects
     *
     * @param int $leadId
     * @return array with lead data
     * 
     * @todo set default values in js and remove getEmptyXXX functions ?
     */
    public function getLead($leadId)
    {
        $controller = Crm_Controller_Lead::getInstance();

        if(!$leadId ) {   
            $lead = $controller->getEmptyLead();
        } else {
            $lead = $controller->getLead($leadId);
        }   
        $leadData = $this->_leadToJson($lead); 
        
        //Zend_Registry::get('logger')->debug(__CLASS__ . '::' . __METHOD__ . '(' . __LINE__ . ')' .  print_r($leadData,true));
        return $leadData;
    }
        
    /**
     * Search for leads matching given arguments
     *
     * @param array $filter
     * @return array
     */
    public function searchLeads($filter)
    {
        $paginationFilter = Zend_Json::decode($filter);
        $filter = new Crm_Model_LeadFilter($paginationFilter);
        $pagination = new Tinebase_Model_Pagination($paginationFilter);
        
        //Zend_Registry::get('logger')->debug(print_r($paginationFilter,true));
        
        $leads = Crm_Controller_Lead::getInstance()->searchLeads($filter, $pagination, TRUE);
        
        $result = $this->_multipleLeadsToJson($leads);
        
        //Zend_Registry::get('logger')->debug(print_r($result,true));
        
        return array(
            'results'       => $result,
            'totalcount'    => Crm_Controller_Lead::getInstance()->searchLeadsCount($filter)
        );
    }
    
    /*************************** save/delete leads ****************************/
    
    /**
     * save one lead
     *
     * if $leadId is NULL the lead gets added, otherwise it gets updated
     *
     * @param  string  $lead           JSON encoded lead data
     * @return array
     */ 
    public function saveLead($lead)
    {
        $inLead = new Crm_Model_Lead();
        $inLead->setFromJsonInUsersTimezone($lead);
                  
        if(empty($inLead->id)) {
            $savedLead = Crm_Controller_Lead::getInstance()->createLead($inLead);
        } else {
            $savedLead = Crm_Controller_Lead::getInstance()->updateLead($inLead);
        }
        
        $result = $this->getLead($savedLead->getId());
        return $result;  
    }      

    /**
     * delete a array of leads
     *
     * @param array $_leadIDs
     * @return array
     */
    public function deleteLeads($_leadIds)
    {
        $leadIds = Zend_Json::decode($_leadIds);

        Crm_Controller_Lead::getInstance()->deleteLead($leadIds);
        
        $result = array(
            'success'   => TRUE
        );

        return $result;
        
    }
    
    /****************************************** helper functions ***********************************/
    
    /**
     * returns lead prepared for json transport
     *
     * @param Crm_Model_Lead    $_lead
     * @return array lead data
     */
    protected function _leadToJson($_lead)
    {
        $_lead->setTimezone($this->_userTimezone);
        $result = $_lead->toArray();
        
        // set container
        if (!$_lead->container_id) {
            $personalFolders = Zend_Registry::get('currentAccount')->getPersonalContainer('Crm', Zend_Registry::get('currentAccount'), Tinebase_Model_Container::GRANT_READ);
            $container_id = $personalFolders[0];
        } else {
            $container_id = $_lead->container_id;
        }
        $result['container_id'] = Tinebase_Container::getInstance()->getContainerById($container_id)->toArray();
        $result['container_id']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Zend_Registry::get('currentAccount'), $container_id)->toArray();
        
        return $result;                
    }
    
    /**
     * returns multiple leads prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_leads Crm_Model_Lead
     * @return array leads data
     */
    protected function _multipleLeadsToJson(Tinebase_Record_RecordSet $_leads)
    {        
        // get acls for leads
        Tinebase_Container::getInstance()->getGrantsOfRecords($_leads, Zend_Registry::get('currentAccount'));
        
        $_leads->setTimezone($this->_userTimezone);
        $_leads->convertDates = true;
        
        $result = $_leads->toArray();
        
        return $result;
    }
    
    /**
     * Returns registry data of crm.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {   
        $registryData = array(
            'LeadTypes'   => $this->getLeadtypes('leadtype','ASC'),
            'LeadStates'  => $this->getLeadStates('leadstate','ASC'),
            'LeadSources' => $this->getLeadSources('leadsource','ASC'),
            'Products'    => $this->getProducts('productsource','ASC'),
        );
        
        /*
        foreach ($registryData as &$data) {
            $data->setTimezone(Zend_Registry::get('userTimeZone'));
            $data = $data->toArray();
        }
        */
        return $registryData;    
    }
    
    
    /********************** handling of lead types/sources/states and products *************************/
    
    /**
     * get lead sources
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getLeadsources($sort, $dir)
    {     
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller_LeadSources::getInstance()->getLeadSources($sort, $dir)) {
            $rows->translate();
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

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
    public function saveLeadsources($optionsData)
    {
        $leadSources = Zend_Json::decode($optionsData);
         
        try {
            $leadSources = new Tinebase_Record_RecordSet('Crm_Model_Leadsource', $leadSources);
        } catch (Tinebase_Exception_Record_Validation $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'filter NOT ok'
            );
            
            return $result;
        }
            
        
        if(Crm_Controller_LeadSources::getInstance()->saveLeadsources($leadSources) === FALSE) {
            $result = array('success'   => FALSE);
        } else {
            $result = array('success'   => TRUE);
        }
        
        return $result;        
    }    


    /**
     * get lead types
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
   public function getLeadtypes($sort, $dir)
    {
         $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller_LeadTypes::getInstance()->getLeadTypes($sort, $dir)) {
            $rows->translate();
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }  

    /**
     * save leadtypes
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveLeadtypes($optionsData)
    {
        $leadTypes = Zend_Json::decode($optionsData);
         
        try {
            $leadTypes = new Tinebase_Record_RecordSet('Crm_Model_Leadtype', $leadTypes);
        } catch (Tinebase_Exception_Record_Validation $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'filter NOT ok'
            );
            
            return $result;
        }
            
        if(Crm_Controller_LeadTypes::getInstance()->saveLeadtypes($leadTypes) === FALSE) {
            $result = array('success'   => FALSE);
        } else {
            $result = array('success'   => TRUE);
        }
        
        return $result;     
    }
    
    
    /**
     * get lead states
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */   
    public function getLeadstates($sort, $dir)
    {
         $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller_LeadStates::getInstance()->getLeadStates($sort, $dir)) {
            $rows->translate();
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;   
    }  

    /**
     * save states
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveLeadstates($optionsData)
    {
        $leadStates = Zend_Json::decode($optionsData);
         
        try {
            $leadStates = new Tinebase_Record_RecordSet('Crm_Model_Leadstate', $leadStates);
        } catch (Tinebase_Exception_Record_Validation $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'filter NOT ok'
            );
            
            return $result;
        }
            
        
        if(Crm_Controller_LeadStates::getInstance()->saveLeadstates($leadStates) === FALSE) {
            $result = array('success'   => FALSE);
        } else {
            $result = array('success'   => TRUE);
        }
        
        return $result;       
    }    
    
 
    /**
     * get available products
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getProducts($sort, $dir)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller_LeadProducts::getInstance()->getProducts($sort, $dir)) {
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;  
    }    
  
    /**
     * save products
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveProducts($optionsData)
    {
        $products = Zend_Json::decode($optionsData);
         
        try {
            $products = new Tinebase_Record_RecordSet('Crm_Model_Product', $products);
        } catch (Tinebase_Exception_Record_Validation $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'filter NOT ok'
            );
            
            return $result;
        }
            
        
        if(Crm_Controller_LeadProducts::getInstance()->saveProducts($products) === FALSE) {
            $result = array('success'   => FALSE);
        } else {
            $result = array('success'   => TRUE);
        }
        
        return $result;       
    }     
    
}
