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
     * @param int $_leadId
     * @return array with lead data
     * 
     * @todo set default values in js and remove getEmptyXXX functions ?
     */
    public function getLead($_leadId)
    {
        $controller = Crm_Controller::getInstance();

        if($_leadId !== NULL && $lead = $controller->getLead($_leadId)) {
            
            //$leadData = $this->convertLeadToArray($lead, FALSE);
            $leadData = $this->leadToJson($lead, FALSE);
                        
        } else {

            $leadData = $controller->getEmptyLead()->toArray();
            $leadData['products'] = array();                
            $leadData['contacts'] = array();   
            $leadData['tasks'] = array();                                   
            
            $personalFolders = Zend_Registry::get('currentAccount')->getPersonalContainer('Crm', Zend_Registry::get('currentAccount'), Tinebase_Container::GRANT_READ);
            foreach($personalFolders as $folder) {
                $leadData['container']     = $folder->toArray();
                break;
            }            
        }    

        return $leadData;
    }
        
    /**
     * Search for leads matching given arguments
     *
     * @param array $filter
     * @return array
     * 
     * @todo    resolve links/relations every time?
     */
    public function searchLeads($filter)
    {
        $paginationFilter = Zend_Json::decode($filter);
        $filter = new Crm_Model_LeadFilter($paginationFilter);
        $pagination = new Crm_Model_LeadPagination($paginationFilter);
        
        //Zend_Registry::get('logger')->debug(print_r($paginationFilter,true));
        
        $leads = Crm_Controller::getInstance()->searchLeads($filter, $pagination, TRUE);
        $leads->setTimezone($this->_userTimezone);
        $leads->convertDates = true;
        
        $result = array();
        foreach ($leads as $lead) {
            //$result[] = $this->convertLeadToArray($lead);
            $result[] = $this->leadToJson($lead);
        }
        
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
        /*
        $decodedLead = Zend_Json::decode($lead);       
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($decodedLead, true));
        
        // tags
        if (isset($decodedLead['tags'])) {
            $decodedLead['tags'] = Zend_Json::decode($decodedLead['tags']);
        }                     
        
        // lead data
        $leadData = new Crm_Model_Lead();
        try {
            $leadData->setFromArray($decodedLead);
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array(
                'success'       => false,
                'errors'        => $leadData->getValidationErrors(),
                'errorMessage'  => 'invalid data for some fields'
            );
            
            return $result;
        } 
        */

        $leadData = Crm_Model_Lead::setFromJson($lead);
                  
        if(empty($leadData->id)) {
            $savedLead = Crm_Controller::getInstance()->createLead($leadData);
        } else {
            $savedLead = Crm_Controller::getInstance()->updateLead($leadData);
        }
        
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        //'updatedData'       => $this->convertLeadToArray($savedLead, FALSE)
                        'updatedData'       => $this->leadToJson($savedLead, FALSE)
        );
        
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
     * converts a lead to an array and resolves contact/tasks/product ids, tags and container
     *
     * @param Crm_Model_Lead    $_lead              lead record
     * @param boolean           $_getOnlyContacts   resolve only contact links
     * @return array
     * 
     * @deprecated 
     * @todo remove function?
     */
    protected function convertLeadToArray(Crm_Model_Lead $_lead, $_getOnlyContacts = TRUE) 
    {
        $result = $_lead->toArray();

        // add contact links
        $types = array(
            'responsible',
            'customer',
            'partner'
        );
        foreach ( $types as $type ) {
            $result[$type] = array();
            foreach($_lead->$type as $relation) {
                try {
                    $contact = Addressbook_Controller::getInstance()->getContact($relation->related_id)->toArray();
                    $contact['link_id'] = $relation->getId();
                    $contact['link_remark'] = $type;
                    $result[$type][] = $contact;
                } catch (Exception $e) {
                    Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' skipped contact: ' . $relation->related_id);
                    // ignore, permission denied or contact not found
                }
            }
        }

        if ( !$_getOnlyContacts ) {
            // add tasks
            $result['tasks'] = array();
            foreach($_lead->tasks as $relation) {
                try {
                    $task = Tasks_Controller::getInstance()->getTask($relation->related_id)->toArray();
                    $task['link_id'] = $relation->getId();
                    $result['tasks'][] = $task;
                } catch (Exception $e) {
                    Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' skipped task: ' . $relation->related_id);
                    // ignore, permission denied or task not found
                }
            }
            
            // add products
            $products = Crm_Controller::getInstance()->getLeadProducts($_lead->getId());
            $result['products'] = $products->toArray();
                
            // add tags
            $result['tags'] = $_lead['tags']->toArray();  
                              
        } else {
            // return empty arrays
            $result['tasks'] = array();
            $result['tags'] = array();
            $result['products'] = array();
        }

        // add container
        $folder = Tinebase_Container::getInstance()->getContainerById($_lead->container);            
        $result['container'] = $folder->toArray();                
        
        return $result;
    }

    /**
     * returns instance of Crm_Model_Lead from json encoded data
     * 
     * @param string JSON encoded lead
     * @return Crm_Model_Lead lead
     * 
     * @todo use it
     */
    public function jsonToLead($json)
    {
        date_default_timezone_set($this->_userTimezone);
        $inTask = new Tasks_Model_Task(Zend_Json::decode($json));
        $inTask->setTimezone($this->_serverTimezone);
        date_default_timezone_set($this->_serverTimezone);
        
        return $inTask;
    }
    
    /**
     * returns lead prepared for json transport
     *
     * @param Crm_Model_Lead    $_lead
     * @param boolean           $_getOnlyContacts   resolve only contact links
     * @return array lead data
     * 
     * @todo get relation objects from json classes for the applications/models
     * @todo add toResolve array ?
     */
    public function leadToJson($_lead /*, $_getOnlyContacts = TRUE */)
    {
        // @todo is that needed?
        //$_lead->setTimezone(Zend_Registry::get('userTimeZone'));
        //$_lead->bypassFilters = true;

        $result = $_lead->toArray();        
        
        // add container
        $result['container'] = Zend_Json::encode(Tinebase_Container::getInstance()->getContainerById($_lead->container)->toArray());
        
        // add products ?
        //$result['products'] = $_lead->products->toArray();

        return $result;
        
        // add contact links
        /*
        $types = array(
            'responsible',
            'customer',
            'partner'
        );
        foreach ( $types as $type ) {
            $result[$type] = array();
            foreach($_lead->$type as $relation) {
                try {
                    $contact = Addressbook_Controller::getInstance()->getContact($relation->related_id)->toArray();
                    $contact['link_id'] = $relation->getId();
                    $contact['link_remark'] = $type;
                    $result[$type][] = $contact;
                } catch (Exception $e) {
                    Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' skipped contact: ' . $relation->related_id);
                    // ignore, permission denied or contact not found
                }
            }
        }

        if ( !$_getOnlyContacts ) {
            // add tasks
            $result['tasks'] = array();
            foreach($_lead->tasks as $relation) {
                try {
                    $task = Tasks_Controller::getInstance()->getTask($relation->related_id)->toArray();
                    $task['link_id'] = $relation->getId();
                    $result['tasks'][] = $task;
                } catch (Exception $e) {
                    Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' skipped task: ' . $relation->related_id);
                    // ignore, permission denied or task not found
                }
            }
            
            // add products
            $products = Crm_Controller::getInstance()->getLeadProducts($_lead->getId());
            $result['products'] = $products->toArray();
                
            // add tags
            $result['tags'] = $_lead['tags']->toArray();  
                              
        } else {
            // return empty arrays
            $result['tasks'] = array();
            $result['tags'] = array();
            $result['products'] = array();
        }
        */
        
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
    

    /**
     * get products associated with one lead
     *
     * @param int $_id lead id
     * @return array
     */
    public function getLeadProducts($_id)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );

        if($rows = Crm_Controller::getInstance()->getProductsByLeadId($_id)) {
            $result['results']    = $rows->toArray();
            $result['totalcount'] = count($result['results']);
        }

        return $result;
    } 
    
    /**
     * save lead products
     *
     * @param  string  json encoded array
     * @param  int     lead id
     * @return array
     */
    public function saveLeadProducts($products, $id) 
    {   
        $_products = Zend_Json::decode($products);
        $_productsData = array();

        if(is_array($_products)) {
            foreach($_products AS $_product) {
                if($_product['id'] == "NULL") {
                    unset($_product['id']);
                }
                if($_product['lead_id'] == "-1" || empty($_product['lead_id'])) {
                    $_product['lead_id'] = $id;
    
                }           
                
                $_productsData[] = $_product;
            }
           
            try {
                $_productsData = new Tinebase_Record_RecordSet('Crm_Model_Product', $_productsData);
            } catch (Exception $e) {
                // invalid data in some fields sent from client
                $result = array('success'           => false,
                                'errorMessage'      => 'products filter NOT ok'
                );
                
                return $result;
            } 
        }
            
        if(Crm_Controller::getInstance()->saveProducts($_productsData) === FALSE) {
            $result = array('success'   => FALSE);
        } else {
            $result = array('success'   => TRUE);
        }
       
        return $result;  

   }
}