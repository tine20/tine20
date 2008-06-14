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
        $decodedLead = Zend_Json::decode($lead);        
        if (isset($decodedLead['tags'])) {
            $decodedLead['tags'] = Zend_Json::decode($decodedLead['tags']);
        }      
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($decodedLead, true));
        
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
        
        if(empty($leadData->id)) {
            $savedLead = Crm_Controller::getInstance()->addLead($leadData);
        } else {
            $savedLead = Crm_Controller::getInstance()->updateLead($leadData);
        } 
               
        
        $resultData = $savedLead->toArray();
        $resultData['container'] = Tinebase_Container::getInstance()->getContainerById($savedLead->container)->toArray();
        
        // testing
        //$resultData = $leadData->toArray();
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($resultData, true));

        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $resultData
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

    /*************************** get leads ****************************/

    /**
     * get single lead
     * fetches a lead and adds resolves linked objects
     *
     * @param int $_leadId
     * @return array with lead data
     */
    public function getLead($_leadId)
    {
        $controller = Crm_Controller::getInstance();

        if($_leadId !== NULL && $lead = $controller->getLead($_leadId)) {
            
            $leadData = $this->convertLeadToArray($lead, FALSE);

            /*
            // add contact links
            $leadData['contacts'] = array();
            $contact_links = $controller->getLinksForApplication($_leadId, 'Addressbook');
            foreach($contact_links as $contact_link) {
                try {
                    $contact = Addressbook_Controller::getInstance()->getContact($contact_link['recordId']);
                    $contactArray = $contact->toArray();
                    $contactArray['link_remark'] = $contact_link['remark'];
                    $leadData['contacts'][] = $contactArray;                    
                } catch (Exception $e) {
                    // do nothing
                    // catch only permission denied exception
                }
            }

            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($leadData['contacts'], true));
            
            // add task links
            $leadData['tasks'] = array();
            $taskLinks = $controller->getLinksForApplication($_leadId, 'Tasks');
            // @todo    move that to controller?
            foreach ( $taskLinks as $taskLink ) {
                try {
                    $task = Tasks_Controller::getInstance()->getTask($taskLink['recordId']);            
                    $taskArray = $task->toArray();

                    $creator = Tinebase_User::getInstance()->getUserById($task->created_by);
                    $taskArray['creator'] = $creator->accountFullName;
                    
                    if ($task->last_modified_by != NULL) {
                        $modifier = Tinebase_User::getInstance()->getUserById($task->last_modified_by);
                        $taskArray['modifier'] = $modifier->accountFullName;         
                    }

                    // @todo write function for that: getStatusById()
                    $stati = Tasks_Controller::getInstance()->getStati()->toArray();
                    foreach ($stati as $status) {
                        if ($status['id'] == $taskArray['status_id']) {
                            $taskArray['status_realname'] = $status['status_name'];
                            $taskArray['status_icon'] = $status['status_icon'];
                        }
                    }

                    $leadData['tasks'][] = $taskArray;  
                    
                } catch (Exception $e) {
                    // do nothing
                    //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->__toString());
                }
            }
            */
                        
        } else {
            // @todo set default values in js and remove getEmptyXXX functions
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
     * get leads by owner
     *
     * @param unknown_type $filter
     * @param unknown_type $owner
     * @param unknown_type $start
     * @param unknown_type $sort
     * @param unknown_type $dir
     * @param unknown_type $limit
     * @param unknown_type $leadstate
     * @param unknown_type $probability
     * @param unknown_type $getClosedLeads
     * @return unknown
     * 
     * @todo    add phpdoc
     */
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
        
    /**
     * get leads by folder
     *
     * @param unknown_type $folderId
     * @param unknown_type $filter
     * @param unknown_type $start
     * @param unknown_type $sort
     * @param unknown_type $dir
     * @param unknown_type $limit
     * @param unknown_type $leadstate
     * @param unknown_type $probability
     * @param unknown_type $getClosedLeads
     * @return unknown
     * 
     * @todo    add phpdoc
     */
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

    /****************************************** helper functions ***********************************/
    
    /**
     * converts a lead to an array and resolves contact/tasks/product ids, tags and container
     *
     * @param Crm_Model_Lead    $_lead              lead record
     * @param boolean           $_getOnlyContacts   resolve only contact links
     * @return array
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
            foreach($_lead->$type as $contactId) {
                try {
                    $contact = Addressbook_Controller::getInstance()->getContact($contactId)->toArray();
                    $contact['link_remark'] = $type;
                    $result[$type][] = $contact;
                } catch (Exception $e) {
                    Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' skipped contact: ' . $contactId);
                    // ignore, permission denied or contact not found
                }
            }
        }

        if ( !$_getOnlyContacts ) {
            // add tasks
            $result['tasks'] = array();
            foreach($_lead->tasks as $taskId) {
                try {
                    $result['tasks'][] = Tasks_Controller::getInstance()->getTask($taskId)->toArray();
                } catch (Exception $e) {
                    Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' skipped task: ' . $taskId);
                    // ignore, permission denied or contact not found
                }
            }
            
            // add container
            $folder = Tinebase_Container::getInstance()->getContainerById($_lead->container);            
            $result['container'] = $folder->toArray();
                
            // add products
            $products = Crm_Controller::getInstance()->getProductsByLeadId($_lead->getId());
            $result['products'] = $products->toArray();
                
            // add tags
            $result['tags'] = $_lead['tags']->toArray();                    
        }
        
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