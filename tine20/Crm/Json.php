<?php
/**
 * Tine 2.0
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        rework functions
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
	public function getProductsource($sort, $dir)
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
	 * save productsources
	 *
	 * if $_Id is -1 the options element gets added, otherwise it gets updated
	 * this function handles insert and updates as well as deleting vanished items
	 *
	 * @return array
	 */	
	public function saveProductsource($optionsData)
    {
        $productSource = Zend_Json::decode($optionsData);
         
        try {
            $productSource = new Tinebase_Record_RecordSet('Crm_Model_Productsource', $productSource);
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'filter NOT ok'
            );
            
            return $result;
        }
            
        
        if(Crm_Controller::getInstance()->saveProductSource($productSource) === FALSE) {
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
    public function getProductsById($_id)
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
	 * save products
	 *
	 * 
	 * 
	 *
	 * @return array
	 */
    public function saveProducts($products, $id) 
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

     /**
	 * save one lead
	 *
	 * if $leadId is NULL the lead gets added, otherwise it gets updated
	 *
	 * @param  string  $lead           JSON encoded lead data
	 * @param  string  $linkedContacts JSON encoded contact ids / type [account, customer, partner]
	 * @param  string  $linkedTasks    JSON encoded tasks ids
	 * @param  string  $products       JSON encoded products
	 * @return array
	 * 
	 * @todo   add tasks and products again
	 */	
	public function saveLead($lead, $linkedContacts, $linkedTasks, $products)
    {
        $leadData = new Crm_Model_Lead();
        try {
            $leadData->setFromArray(Zend_Json::decode($lead));
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array(
                'success'       => false,
                'errors'        => $leadData->getValidationErrors(),
                'errorMessage'  => 'invalid data for some fields'
            );
            
            return $result;
        }
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($leadData->toArray(), true));
        
        $linkedContacts = Zend_Json::decode($linkedContacts);

        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($linkedContacts, true));

        foreach ( $linkedContacts as $contact ) {
            $tmpArray = $leadData->$contact['remark'];
            $tmpArray[] = $contact['recordId'];
            $leadData->$contact['remark'] = $tmpArray;            
        }        
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($leadData->toArray(), true));
        
        /*
        // tasks
        $tasks = Zend_Json::decode($linkedTasks);
        if(is_array($tasks)) {
            $leadData->tasks = $tasks;
        }

        // products    
        if(strlen($products) > 2) {     
            $this->saveProducts($products, $savedLead->id);
        } else {
            Crm_Controller::getInstance()->deleteProducts($savedLead->id);    
        } 
        */
        
        if(empty($leadData->id)) {
            $savedLead = Crm_Controller::getInstance()->addLead($leadData);
        } else {
            $savedLead = Crm_Controller::getInstance()->updateLead($leadData);
        }        
        
        //$result = $savedLead->toArray();
        //$result['container'] = Tinebase_Container::getInstance()->getContainerById($savedLead->container)->toArray();        

        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $savedLead->toArray()
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
     
    public function getLeadsByOwner($filter, $owner, $start, $sort, $dir, $limit, $leadstate, $probability, $getClosedLeads)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );

        if(empty($filter)) {
            $filter = NULL;
        }
        
        if($rows = Crm_Controller::getInstance()->getLeadsByOwner($owner, $filter, $sort, $dir, $limit, $start, $leadstate, $probability, $getClosedLeads)) {
            foreach($rows as &$lead) {
                $result['results'][] = $this->convertLeadToArray($lead);
            }
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = Crm_Controller::getInstance()->getCountByOwner($owner, $filter, $leadstate, $probability, $getClosedLeads);
            }
        }

        return $result;
    }
        
    public function getLeadsByFolder($folderId, $filter, $start, $sort, $dir, $limit, $leadstate, $probability, $getClosedLeads)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
                
        if($rows = Crm_Controller::getInstance()->getLeadsByFolder($folderId, $filter, $sort, $dir, $limit, $start, $leadstate, $probability, $getClosedLeads)) {
            foreach($rows as &$lead) {
                $result['results'][] = $this->convertLeadToArray($lead);
            }
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = Crm_Controller::getInstance()->getCountByFolder($folderId, $filter);
            }
        }

        return $result;
    }    
 
    /**
     * search trough all other people leads
     *
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return array
     */
    public function getOtherPeopleLeads($filter, $sort, $dir, $limit, $start, $leadstate, $probability, $getClosedLeads)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller::getInstance()->getOtherPeopleLeads($filter, $sort, $dir, $limit, $start, $leadstate, $probability, $getClosedLeads)) {
            foreach($rows as &$lead) {
                $result['results'][] = $this->convertLeadToArray($lead);
            }
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = Crm_Controller::getInstance()->getCountOfOtherPeopleLeads($filter, $leadstate, $probability, $getClosedLeads);
            }
        }

        return $result;                
    }
    
    /**
     * converts a lead to an array and resolves contactids
     *
     * @param Crm_Model_Lead $_lead
     * @return array
     */
    protected function convertLeadToArray(Crm_Model_Lead $_lead) {
        $result = $_lead->toArray();

        $result['leadstate']  = Crm_Controller::getInstance()->getLeadState($_lead['leadstate_id'])->toArray();
        $result['leadtype']   = Crm_Controller::getInstance()->getLeadType($_lead['leadtype_id'])->toArray();
        $result['leadsource'] = Crm_Controller::getInstance()->getLeadSource($_lead['leadsource_id'])->toArray();
        foreach($_lead->responsible as $contactId) {
            try {
                $result['responsible'][] = Addressbook_Controller::getInstance()->getContact($contactId)->toArray();
            } catch (Exception $e) {
                Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' skipped contact: ' . $contactId);
                // ignore, permission denied or contact not found
            }
        }
        $result['customer'] = array();
        foreach($_lead->customer as $contactId) {
            try {
                $result['customer'][] = Addressbook_Controller::getInstance()->getContact($contactId)->toArray();
            } catch (Exception $e) {
                Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' skipped contact: ' . $contactId);
                // ignore, permission denied or contact not found
            }
        }
        $result['partner'] = array();
        foreach($_lead->partner as $contactId) {
            try {
                $result['partner'][] = Addressbook_Controller::getInstance()->getContact($contactId)->toArray();
            } catch (Exception $e) {
                Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' skipped contact: ' . $contactId);
                // ignore, permission denied or contact not found
            }
        }
        
        return $result;
    }
    
    /**
     * search trough all leads
     *
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return array
     */
    public function getAllLeads($filter, $sort, $dir, $limit, $start, $leadstate, $probability, $getClosedLeads)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller::getInstance()->getAllLeads($filter, $sort, $dir, $limit, $start, $leadstate, $probability, $getClosedLeads)) {
            foreach($rows as &$lead) {
                $result['results'][] = $this->convertLeadToArray($lead);
            }
            
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = Crm_Controller::getInstance()->getCountOfAllLeads($filter, $leadstate, $probability, $getClosedLeads);
            }
        }

        return $result;                
    }
     
    /**
     * search trough all shared leads
     *
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getSharedLeads($filter, $sort, $dir, $limit, $start, $leadstate, $probability, $getClosedLeads)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller::getInstance()->getSharedLeads($filter, $sort, $dir, $limit, $start, $leadstate, $probability, $getClosedLeads)) {
            foreach($rows as &$lead) {
                $result['results'][] = $this->convertLeadToArray($lead);
            }
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = Crm_Controller::getInstance()->getCountOfSharedLeads($filter, $leadstate, $probability, $getClosedLeads);
            }
        }

        return $result;                
    }              
}