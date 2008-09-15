<?php
/**
 * Tine 2.0
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Crm application
 *
 * @package     Crm
 */
class Crm_Json extends Tinebase_Application_Json_Abstract
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
        $controller = Crm_Controller::getInstance();

        if(!$leadId ) {   
            $leadData = $controller->getEmptyLead()->toArray();
            $leadData['products'] = array();                
            $leadData['contacts'] = array();   
            $leadData['tasks'] = array();                                   
            
            $personalFolders = Zend_Registry::get('currentAccount')->getPersonalContainer('Crm', Zend_Registry::get('currentAccount'), Tinebase_Container::GRANT_READ);
            foreach($personalFolders as $folder) {
                $leadData['container']     = $folder->toArray();
                $leadData['container']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Zend_Registry::get('currentAccount'), $folder->getId())->toArray();
                break;
            }
        } else {
            $lead = $controller->getLead($leadId);
            $leadData = $this->_leadToJson($lead);            
        }    

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
        $pagination = new Crm_Model_LeadPagination($paginationFilter);
        
        //Zend_Registry::get('logger')->debug(print_r($paginationFilter,true));
        
        $leads = Crm_Controller::getInstance()->searchLeads($filter, $pagination, TRUE);
        
        $result = $this->_multipleLeadsToJson($leads);
        
        //Zend_Registry::get('logger')->debug(print_r($result,true));
        
        return array(
            'results'       => $result,
            'totalcount'    => Crm_Controller::getInstance()->searchLeadsCount($filter)
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
        $leadData = Crm_Model_Lead::setFromJson($lead);
                  
        if(empty($leadData->id)) {
            $savedLead = Crm_Controller::getInstance()->createLead($leadData);
        } else {
            $savedLead = Crm_Controller::getInstance()->updateLead($leadData);
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

        Crm_Controller::getInstance()->deleteLead($leadIds);
        
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
     * @param boolean           $_getOnlyContacts   resolve only contact links (not working at the moment)
     * @return array lead data
     * 
     * @todo add toResolve array ?
     */
    protected function _leadToJson($_lead)
    {
        $_lead->setTimezone(Zend_Registry::get('userTimeZone'));
        $result = $_lead->toArray();
                        
        $result['container'] = Tinebase_Container::getInstance()->getContainerById($_lead->container)->toArray();
        $result['container']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Zend_Registry::get('currentAccount'), $_lead->container)->toArray();
        
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
        Tinebase_Container::getInstance()->getGrantsOfRecords($_leads, Zend_Registry::get('currentAccount'), 'container');
        
        $_leads->setTimezone($this->_userTimezone);
        $_leads->convertDates = true;
        
        $result = $_leads->toArray();
        
        return $result;
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
        
        if($rows = Crm_Controller::getInstance()->getLeadSources($sort, $dir)) {
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
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'filter NOT ok'
            );
            
            return $result;
        }
            
        
        if(Crm_Controller::getInstance()->saveLeadsources($leadSources) === FALSE) {
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
        
        if($rows = Crm_Controller::getInstance()->getLeadTypes($sort, $dir)) {
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
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'filter NOT ok'
            );
            
            return $result;
        }
            
        if(Crm_Controller::getInstance()->saveLeadtypes($leadTypes) === FALSE) {
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
        
        if($rows = Crm_Controller::getInstance()->getLeadStates($sort, $dir)) {
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
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'filter NOT ok'
            );
            
            return $result;
        }
            
        
        if(Crm_Controller::getInstance()->saveLeadstates($leadStates) === FALSE) {
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
        
        if($rows = Crm_Controller::getInstance()->getProducts($sort, $dir)) {
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
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'filter NOT ok'
            );
            
            return $result;
        }
            
        
        if(Crm_Controller::getInstance()->saveProducts($products) === FALSE) {
            $result = array('success'   => FALSE);
        } else {
            $result = array('success'   => TRUE);
        }
        
        return $result;       
    }     
    
}
