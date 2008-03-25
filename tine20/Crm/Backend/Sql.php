<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * interface for leads class
 *
 * @package     Crm
 */
class Crm_Backend_Sql implements Crm_Backend_Interface
{
	/**
	* Instance of Crm_Backend_Sql_Leads
	*
	* @var Crm_Backend_Sql_Leads
	*/
    protected $leadTable;

	/**
	* Instance of Crm_Backend_Sql_Leadsources
	*
	* @var Crm_Backend_Sql_Leadsources
	*/
    protected $leadSourceTable;

	/**
	* Instance of Crm_Backend_Sql_Leadtypes
	*
	* @var Crm_Backend_Sql_Leadtypes
	*/
    protected $leadTypeTable;
    
	/**
	* Instance of Crm_Backend_Sql_Products
	*
	* @var Crm_Backend_Sql_Products
	*/
    protected $productSourceTable;
    
	/**
	* Instance of Crm_Backend_Sql_Leadstates
	*
	* @var Crm_Backend_Sql_Leadstates
	*/
    protected $leadStateTable;    
        
    
	/**
	* the constructor
	*
	*/
    public function __construct()
    {
        $this->leadTable      		= new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_lead'));
        $this->leadSourceTable   	= new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadsource'));
        $this->leadTypeTable     	= new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadtype'));
		$this->productSourceTable 	= new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_productsource'));
        $this->leadStateTable    	= new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadstate'));
        $this->productsTable   		= new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_product'));
        $this->linksTable 			= new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'links'));
        
    }
    
	/**
	 * get Leadsources
	 *
	 * @return unknown
	 */
    public function getLeadsources($sort, $dir)
    {	
		$result = $this->leadSourceTable->fetchAll(NULL, $sort, $dir);
		
        return $result;
	}

    /**
    * add or updates an option
    *
    * @param Tinebase_Record_Recordset $_leadSources list of lead sources
    * @return unknown
    */
    public function saveLeadsources(Tinebase_Record_Recordset $_leadSources)
    {
        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_leadsource');

            foreach($_leadSources as $leadSource) {
                $db->insert(SQL_TABLE_PREFIX . 'metacrm_leadsource', $leadSource->toArray());                
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log($e->getMessage());
        }

        return $_leadSources;
    }
    
    /**
     * delete option identified by id and table
     *
     * @param int $_Id option id
     * @param $_table which option section
     * @return int the number of rows deleted
     */
    public function deleteLeadsourceById($_Id)
    {
        $Id = (int)$_Id;
        if($Id != $_Id) {
            throw new InvalidArgumentException('$_Id must be integer');
        }
        
        $where  = array(
            $this->leadSourceTable->getAdapter()->quoteInto('leadsource_id = ?', $Id),
        );
             
        $result = $this->leadSourceTable->delete($where);

        return $result;
    }
    
    
	// handle LEADTYPES
	/**
	* get Leadtypes
	*
	* @return unknown
	*/
    public function getLeadtypes($sort, $dir)
    {	
		$result = $this->leadTypeTable->fetchAll(NULL, $sort, $dir);
        return $result;
	}	
    
	/**
	* add or updates an option
	*
	* @param Crm_Leadtype $_optionData the optiondata
	* @return unknown
	*/
    public function saveLeadtypes(Tinebase_Record_Recordset $_optionData)
    {

        $_daten = $_optionData->toArray();
    

        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_leadtype');

            foreach($_daten as $_data) {
                $db->insert(SQL_TABLE_PREFIX . 'metacrm_leadtype', $_data);                
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log($e->getMessage());
        }

        return $_optionData;
    }

    /**
     * delete option identified by id and table
     *
     * @param int $_Id option id
     * @param $_table which option section
     * @return int the number of rows deleted
     */
    public function deleteLeadtypeById($_Id)
    {
        $Id = (int)$_Id;
        if($Id != $_Id) {
            throw new InvalidArgumentException('$_Id must be integer');
        }
            $where  = array(
                $this->leadTypeTable->getAdapter()->quoteInto('leadtype_id = ?', $Id),
            );
             
            $result = $this->leadTypeTable->delete($where);

        return $result;
    }    
    
  
	// handle PRODUCTS AVAILABLE
	/**
	* get Products available
	*
	* @return unknown
	*/
    public function getProductsAvailable($sort, $dir)
    {	
		$result = $this->productSourceTable->fetchAll(NULL, $sort, $dir);
        return $result;
	}    
    
	/**
	* add or updates an option
	*
	* @param Crm_Productsource $_optionData the optiondata
	* @return unknown
	*/
    public function saveProductsource(Tinebase_Record_Recordset $_optionData)
    {

        $_daten = $_optionData->toArray();
    

        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_productsource');

            foreach($_daten as $_data) {
                $db->insert(SQL_TABLE_PREFIX . 'metacrm_productsource', $_data);                
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log($e->getMessage());
        }

        return $_optionData;
    }
    /**
     * delete option identified by id and table
     *
     * @param int $_Id option id
     * @param $_table which option section
     * @return int the number of rows deleted
     */
    public function deleteProductsourceById($_Id)
    {
        $Id = (int)$_Id;
        if($Id != $_Id) {
            throw new InvalidArgumentException('$_Id must be integer');
        }      
            $where  = array(
                $this->linksTable->getAdapter()->quoteInto('leadsource_id = ?', $Id),
            );
             
            $result = $this->productSourceTable->delete($where);

        return $result;
    }    
    
    
	/**
	* get LeadContacts
	*
	* @return unknown
	*/  
    public function getLeadContacts(Tinebase_Record_Recordset $_leads)
    {    
        $leads = $_leads->toArray();
        
        foreach($leads AS $lead)
        {
            $_id = $lead['id'];
            $_contact = $this->getContactsById($_id);
            $leadContacts[$_id] = $_contact;
        }
    
        return $leadContacts;
    }
    
  
	/**
	* get Contacts
	*
	* @return unknown
	*/  
/*    public function getContactsById($_id)
    {
        $id = (int) $_id;
        if($id != $_id) {
            throw new InvalidArgumentException('$_id must be integer');
        }
        
        $where[] = Zend_Registry::get('dbAdapter')->quoteInto('links.link_app1 = ?', 'crm');
        $where[] = Zend_Registry::get('dbAdapter')->quoteInto('links.link_app2 = ?', 'addressbook');        
        $where[] = Zend_Registry::get('dbAdapter')->quoteInto('links.link_id1 = ?', $id);        
				        
        $select = $this->_getContactsSelectObject();

        if(is_array($where)) {
             foreach($where as $_where) {
                  $select->where($_where);
             }               
        }

        //error_log($select->__toString());
       
        $stmt = $select->query();
    
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $contacts = new Tinebase_Record_RecordSet($rows, 'Crm_Model_Contact');
        
        return $contacts;
    } */
  
  
  
	/**
	* get Leadstates
	*
	* @return unknown
	*/
    public function getLeadstates($sort, $dir)
    {	
    	$result = $this->leadStateTable->fetchAll(NULL, $sort, $dir);
   
        return $result;
	}    

	/**
    * get state identified by id
    *
    * @return Crm_Model_Leadstate
    */
    public function getLeadState($_leadstateId)
    {   
        $stateId = (int)$_leadstateId;
        if($stateId != $_leadstateId) {
            throw new InvalidArgumentException('$_leadstateId must be integer');
        }
        $rowSet = $this->leadStateTable->find($stateId);
        
        if(count($rowSet) == 0) {
            // something bad happend
        }
        
        $result = new Crm_Model_Leadstate($rowSet->current()->toArray());
   
        return $result;
    }
        
    /**
    * get leadtype identified by id
    *
    * @return Crm_Model_Leadtype
    */
    public function getLeadType($_typeId)
    {   
        $typeId = (int)$_typeId;
        if($typeId != $_typeId) {
            throw new InvalidArgumentException('$_typeId must be integer');
        }
        $rowSet = $this->leadTypeTable->find($typeId);
        
        if(count($rowSet) == 0) {
            // something bad happend
        }
        
        $result = new Crm_Model_Leadtype($rowSet->current()->toArray());
   
        return $result;
    }
        
    /**
    * get leadsource identified by id
    *
    * @return Crm_Model_Leadsource
    */
    public function getLeadSource($_sourceId)
    {   
        $sourceId = (int)$_sourceId;
        if($sourceId != $_sourceId) {
            throw new InvalidArgumentException('$_sourceId must be integer');
        }
        $rowSet = $this->leadSourceTable->find($sourceId);
        
        if(count($rowSet) == 0) {
            // something bad happend
        }
        
        $result = new Crm_Model_Leadsource($rowSet->current()->toArray());
   
        return $result;
    }    
    
	/**
	* add or updates an option
	*
	* @param Crm_Leadstate $_optionData the optiondata
	* @return unknown
	*/
    public function saveLeadstates(Tinebase_Record_Recordset $_optionData)
    {

        $_daten = $_optionData->toArray();
    

        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_leadstate');

            foreach($_daten as $_data) {
                $db->insert(SQL_TABLE_PREFIX . 'metacrm_leadstate', $_data);                
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log($e->getMessage());
        }

        return $_optionData;
    }

    /**
     * delete option identified by id and table
     *
     * @param int $_Id option id
     * @param $_table which option section
     * @return int the number of rows deleted
     */
    public function deleteLeadstateById($_Id)
    {
        $Id = (int)$_Id;
        if($Id != $_Id) {
            throw new InvalidArgumentException('$_Id must be integer');
        }      
            $where  = array(
                $this->leadStateTable->getAdapter()->quoteInto('leadstate_id = ?', $Id),
            );
             
            $result = $this->leadStateTable->delete($where);

        return $result;
    }


	// handle PRODUCTS (associated to lead)
	/**
	* get products by lead id
	*
	* 
	* 
	* 
	* @return unknown
	*/
     public function getProductsById($_id)
    {
        $id = (int) $_id;
        if($id != $_id) {
            throw new InvalidArgumentException('$_id must be integer');
        }

        $where  = array(
            $this->productsTable->getAdapter()->quoteInto('lead_id = ?', $_id)
        );

        $result = $this->productsTable->fetchAll($where);

        return $result;
    }      

	/**
	* delete products (which belong to one lead)
	*
	* @param int $_Id the id of the lead
    *
	* @return unknown
	*/
    public function deleteProducts($_id)
    {
        $id = (int) $_id;
        if($id != $_id) {
            throw new InvalidArgumentException('$_id must be integer');
        }

        $db = Zend_Registry::get('dbAdapter');      
        
        try {          
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_product', 'lead_id = ' . $id);      
        } catch (Exception $e) {
            error_log($e->getMessage());
        }      
        
        return true;
   
    }

	/**
	* add or updates an product (which belongs to one lead)
	*
	* @param int $_productId the id of the product, NULL if new, else gets updated
	* @param Crm_Product $_productData the productdata
	* @param int $_leadId the lead id
	* @return unknown
	*/
    public function saveProducts(Tinebase_Record_Recordset $_productData)
    {
        /*  if(!Zend_Registry::get('currentAccount')->hasGrant($_leadData->container, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('write access to lead->product denied');
        }    
    */   
    
        $_daten = $_productData->toArray();
    
        $lead_id = $_daten[0]['lead_id'];


        if(!(int)$lead_id) {
             return $_productData;  
        }
        

        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_product', 'lead_id = '.$lead_id);

            foreach($_daten as $_data) {
                $db->insert(SQL_TABLE_PREFIX . 'metacrm_product', $_data);                
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log($e->getMessage());
        }

        return $_optionData;
         
         
         
         
        $productData = $_productData->toArray();

        if($_productData->product_id === NULL) {
            $result = $this->productsTable->insert($productData);
            $_productData->product_id = $this->productsTable->getAdapter()->lastInsertId();
        } else {
            $where  = array(
                $this->productsTable->getAdapter()->quoteInto('product_id = (?)', $_productData->id),
            );

            $result = $this->productsTable->update($productData, $where);
        }

        return $_productData;
    }

   
	
    /**
    * adds contacts
    *
    * @param Tinebase_Record_Recordset $_leadSources list of lead sources
    * @return unknown
    */
/*    public function saveContacts(array $_contacts, $_id)
    {
        $id = (int)$_id;
        if($id != $_id) {
            throw new InvalidArgumentException('$_id must be integer');
        }

        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $where[] = $db->quoteInto('link_id1 = ?', $id);
            $where[] = $db->quoteInto('link_app1 = ?', 'crm');            
            $where[] = $db->quoteInto('link_app2 = ?', 'addressbook');              
            
            $db->delete(SQL_TABLE_PREFIX . 'links', $where);

            foreach($_contacts as $_contact) {
                $db->insert(SQL_TABLE_PREFIX . 'links', $_contact);                
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log($e->getMessage());
        }

        return $_contacts;
    }    
 */   
    
    
	/**
	* get single lead by id
	*
	* 
	* 
	* 
	* @return unknown
	*/
    public function getLeadById($_id)
    {
        $id = (int) $_id;
        if($id != $_id) {
            throw new InvalidArgumentException('$_id must be integer');
        }

        $select = $this->_getLeadSelectObject()
            ->where(Zend_Registry::get('dbAdapter')->quoteInto('lead.id = ?', $id));

      // echo $select->__toString();
       
        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        if(empty($row)) {
            throw new UnderFlowExecption('lead not found');
        }
        
        //error_log(print_r($row, true));
        
        $lead = new Crm_Model_Lead($row);
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($lead->container, Tinebase_Container::GRANT_READ)) {
            throw new Exception('permission to lead denied');
        }
        
        return $lead;

/*        $result = $this->leadTable->fetchRow($where);

        if($result === NULL) {
            throw new UnderFlowExecption('lead not found');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($result->container, Tinebase_Container::GRANT_READ)) {
            throw new Exception('permission to lead denied');
        }
        
        return $result;*/
    }    
    
    public function getLeadsByOwner($_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_leadstate = NULL, $_probability = NULL, $_getClosedLeads = NULL)
    {    
		
        $owner = (int)$_owner;
        if($owner != $_owner) {
            throw new InvalidArgumentException('$_owner must be integer');
        }
        $ownerContainer = Zend_Registry::get('currentAccount')->getPersonalContainer('crm', $owner, Tinebase_Container::GRANT_READ);
        
        if(count($ownerContainer) === 0) {
            return false;
        }
        
        $containerIds = array();

        foreach($ownerContainer as $container) {
            $containerIds[] = $container->id;
        }

        $where = array(
            $this->leadTable->getAdapter()->quoteInto('container IN (?)', $containerIds)
        );

        $result = $this->_getLeadsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads);
         
        return $result;
    }
    
    public function getCountByOwner($_owner, $_filter)
    {
        $owner = (int)$_owner;
        if($owner != $_owner) {
            throw new InvalidArgumentException('$_owner must be integer');
        }
        $ownerContainer = Zend_Registry::get('currentAccount')->getPersonalContainer('crm', $owner, Tinebase_Container::GRANT_READ);
        
        if(count($ownerContainer) === 0) {
            return 0;
        }
        
        $containerIds = array();
        
        foreach($ownerContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('container IN (?)', $containerIds)
        );
        
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->leadTable->getTotalCount($where);

        return $result;
    }    

	/**
	* add or updates an lead
	*
	* @param int $_leadOwner the owner of the Crm entry
	* @param Crm_Lead $_leadData the leaddata
	* @param int $_leadId the lead to update, if NULL the lead gets added
	* @return unknown
	*/
    public function saveLead(Crm_Model_Lead $_lead)
    {
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_lead->toArray(), true));

        if(empty($_lead->container)) {
            throw new UnderflowException('container can not be empty');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_lead->container, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('write access to lead denied');
        }

        $leadArray = $_lead->toArray();
        unset($leadArray['id']);
        
        if(empty($_lead->id)) {
            $_lead->id = $this->leadTable->insert($leadArray);
            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' added new lead ' . $_lead->id);
        } else {      
            $where  = array(
                $this->leadTable->getAdapter()->quoteInto('id = ?', $_lead->id),
            );

            $result = $this->leadTable->update($_lead->toArray(), $where);
            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' updated lead ' . $_lead->id);
        }

        return $_lead;
    }

    /**
     * delete lead identified by id
     *
     * @param int $_leads lead ids
     * @return int the number of rows deleted
     */
    public function deleteLeadById($_leadId)
    {
        $leadId = (int)$_leadId;
        if($leadId != $_leadId) {
            throw new InvalidArgumentException('$_leadId must be integer');
        }

        $oldLeadData = $this->getLeadById($_leadId);

        if(!Zend_Registry::get('currentAccount')->hasGrant($oldLeadData->container, Tinebase_Container::GRANT_DELETE)) {
            throw new Exception('delete access to CRM denied');
        }

        $db = Zend_Registry::get('dbAdapter');
        
        $db->beginTransaction();
        
        try {
            $where_lead    = $db->quoteInto('id = ?', $leadId);
            $where_product = $db->quoteInto('lead_id = ?', $leadId);          
            $where_links[] = $db->quoteInto('link_app1 = ?', 'crm');          
            $where_links[] = $db->quoteInto('link_id1 = ?', $leadId);                      
            $where_links[] = $db->quoteInto('link_app2 = ?', 'addressbook');                                  

            $db->delete(SQL_TABLE_PREFIX . 'metacrm_lead', $where_lead);
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_product', $where_product);            
            $db->delete(SQL_TABLE_PREFIX . 'links', $where_links);               


            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log('TRANSACTION ERROR ' . $e->getMessage());
        }

       
        $where  = array(
            $this->leadTable->getAdapter()->quoteInto('id = ?', $leadId),
        );

        $result = $this->leadTable->delete($where);

        return $result;
    }


	// handle FOLDERS  
    public function addFolder($_name, $_type) 
    {
    	
        $tinebaseContainer = Tinebase_Container::getInstance();
        $accountId   = Zend_Registry::get('currentAccount')->accountId;
        $allGrants = array(
            Tinebase_Container::GRANT_ADD,
            Tinebase_Container::GRANT_ADMIN,
            Tinebase_Container::GRANT_DELETE,
            Tinebase_Container::GRANT_EDIT,
            Tinebase_Container::GRANT_READ
        );
        
        if($_type == Tinebase_Container::TYPE_SHARED) {
            $folderId = $tinebaseContainer->addContainer('crm', $_name, Tinebase_Container::TYPE_SHARED, Crm_Backend_Factory::SQL);

            // add admin grants to creator
            $tinebaseContainer->addGrants($folderId, $accountId, $allGrants);
            // add read grants to any other user
            $tinebaseContainer->addGrants($folderId, NULL, array(Tinebase_Container::GRANT_READ));
        } else {
            $folderId = $tinebaseContainer->addContainer('crm', $_name, Tinebase_Container::TYPE_PERSONAL, Crm_Backend_Factory::SQL);
        
            // add admin grants to creator
            $tinebaseContainer->addGrants($folderId, $accountId, $allGrants);
        }
        
        return $folderId;
    }
    
    public function deleteFolder($_folderId)
    {
        $tinebaseContainer = Tinebase_Container::getInstance();
        
        $tinebaseContainer->deleteContainer($_folderId);
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('container = ?', (int)$_folderId)
        );
        
        $this->leadTable->delete($where);
        
        return true;
    }
    
    public function renameFolder($_folderId, $_name)
    {
        $tinebaseContainer = Tinebase_Container::getInstance();
        
        $tinebaseContainer->renameContainer($_folderId, $_name);
                
        return true;
    }    
     
/*    public function getOtherUsers() 
    {
        $rows = Tinebase_Container::getInstance()->getOtherUsers('crm');

        //$accountData = array();
        
        $result = new Tinebase_Record_RecordSet(NULL, 'Tinebase_Account_Model_Account');

        foreach($rows as $account) {
            $accountData = array(
                'account_id'      => $account['account_id'],
                'account_loginid' => 'loginid',
                'account_name'    => 'Account ' . $account['account_id']
            );
            $result->addRecord($accountData);
        }

        //$result = new Tinebase_Record_RecordSet($accountData, 'Tinebase_Account_Model_Account');
        
        return $result;
    } */

    /**
     * create search filter
     *
     * @param string $_filter
     * @param int $_leadstate
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return array
     */
    protected function _getSearchFilter($_filter, $_leadstate, $_probability, $_getClosedLeads)
    {
        $where = array();
        
        if(!empty($_filter)) {
            $search_values = explode(" ", $_filter);
            
            foreach($search_values AS $search_value) {
                $where[] = Zend_Registry::get('dbAdapter')->quoteInto('(lead_name LIKE ? OR description LIKE ?)', '%' . $search_value . '%');                            
            }
        }
        
        if( is_numeric($_leadstate) && $_leadstate > 0 ) {
            $where[] = Zend_Registry::get('dbAdapter')->quoteInto('lead.leadstate_id = ?', (int)$_leadstate);
        }
        
        if( is_numeric($_probability) && $_probability > 0 ) {
            $where[] = Zend_Registry::get('dbAdapter')->quoteInto('lead.probability >= ?', (int)$_probability);
        }       

        if($_getClosedLeads === FALSE  || $_getClosedLeads == 'false') {
            $where[] = 'end IS NULL';
        }
        return $where;
    }
    
    //handle for FOLDER->LEADS functions
    protected function _getLeadsFromTable(array $_where, $_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads)
    {
		
        $where = array_merge($_where, $this->_getSearchFilter($_filter, $_leadstate, $_probability, $_getClosedLeads));

        $db = Zend_Registry::get('dbAdapter');

        $select = $this->_getLeadSelectObject()
            ->order($_sort.' '.$_dir)
            ->limit($_limit, $_start);

         foreach($where as $whereStatement) {
              $select->where($whereStatement);
         }               
       //echo  $select->__toString();
       
        $stmt = $db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $leads = new Tinebase_Record_RecordSet('Crm_Model_Lead', $rows);
        		
        return $leads;
    }   
    
    /**
     * get the basic select object to fetch contacts from the database 
     *
     * @todo do we still need this function
     * @return Zend_Db_Select
     */
/*    protected function _getContactsSelectObject()
    {
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
            ->from(array('links' => SQL_TABLE_PREFIX . 'links'), array(
                'link_remark',
                'link_id')
            )
            ->join(array('contacts' => SQL_TABLE_PREFIX . 'addressbook'), 
                'links.link_id2 = contacts.contact_id', array(
                    'contact_id',
                    'owner',
                    'n_family',
                    'n_given',
                    'n_middle',
                    'n_prefix',
                    'n_suffix',
                    'n_fn',
                    'n_fileas',
                    'org_name',
                    'org_unit',
                    'adr_one_street',
                    'adr_one_locality',
                    'adr_one_region',
                    'adr_one_postalcode',
                    'adr_one_countryname'
                )
            );
        
        return $select;
    }*/    
    
    /**
     * get the basic select object to fetch leads from the database 
     *
     * @todo do we need this join here
     * @return Zend_Db_Select
     */
    protected function _getLeadSelectObject()
    {
		
        $db = Zend_Registry::get('dbAdapter');
		$selectObject = $db->select()
            ->from(array('lead' => SQL_TABLE_PREFIX . 'metacrm_lead'), array(
                'id',
                'lead_name',
                'leadstate_id',
                'leadtype_id',
                'leadsource_id',
                'container',
                'start',
                'description',
                'end',
                'turnover',
                'probability',
                'end_scheduled')
            )
            ->join(array('leadstate' => SQL_TABLE_PREFIX . 'metacrm_leadstate'),
				'lead.leadstate_id = leadstate.id', array( 'leadstate') );        
				// 'lead.id = leadstate.id');
				
			//echo $selectObject->__toString();		
				
        return $selectObject;
    }

    protected function _addQuickSearchFilter($_where, $_filter)
    {
        if(!empty($_filter)) {
            $search_values = explode(" ", $_filter);
            
            foreach($search_values AS $search_value) {
                $_where[] = $this->leadTable->getAdapter()->quoteInto('(lead_name LIKE ? OR description LIKE ?)', '%' . $search_value . '%');                            
            }
        }
        
        return $_where;
    }


    /**
     * get list of leads from all shared folders the current user has access to
     *
     * @param string $_filter string to search for in leads
     * @param unknown_type $_sort fieldname to sort by
     * @param unknown_type $_dir sort ascending or descending (ASC | DESC)
     * @param unknown_type $_limit how many leads to display
     * @param unknown_type $_start how many leads to skip
     * @param int $_leadstate
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return Tinebase_Record_RecordSet subclass Crm_Model_Lead
     */
    public function getAllLeads($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_leadstate, $_probability, $_getClosedLeads)
    {
        $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Tinebase_Container::GRANT_READ);
        
        if(count($allContainer) === 0) {
            $this->createPersonalContainer();
            $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Tinebase_Container::GRANT_READ);
        }        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('container IN (?)', $containerIds)
        );
        $result = $this->_getLeadsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads);
         
        return $result;
    }
    
/*    public function _getCountOfAllLeads($_filter, $_leadstate, $_probability, $_getClosedLeads)
    {
        $containers = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Tinebase_Container::GRANT_READ);
        
        if(count($containers) === 0) {
            return 0;
        }
        
        $containerIds = array();
        
        foreach($containers as $container) {
            $containerIds[] = $container->id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('owner IN (?)', $containerIds)
        );
        
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->contactsTable->getTotalCount($where);

        return $result;
    } */
    
    /**
     * get total count of all leads from shared folders
     *
     * @todo return the correct count (the accounts are missing)
     *
     * @return int count of all other users leads
     */
    public function getCountOfAllLeads($_filter, $_leadstate, $_probability, $_getClosedLeads)
    {
        $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Tinebase_Container::GRANT_READ);

        if(count($allContainer) === 0) {
            return 0;
        }
        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('container IN (?)', $containerIds)
        );
        
        $where = array_merge($where, $this->_getSearchFilter($_filter, $_leadstate, $_probability, $_getClosedLeads));
        
        $result = $this->leadTable->getTotalCount($where);

        return $result;
    }
   
   
    public function getLeadsByFolder($_folderId, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_leadstate = NULL, $_probability = NULL, $_getClosedLeads = TRUE)
    {
        // convert to int
        $folderId = (int)$_folderId;
        if($folderId != $_folderId) {
            throw new InvalidArgumentException('$_folderId must be integer');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_folderId, Tinebase_Container::GRANT_READ)) {
            throw new Exception('read access denied to folder');
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('container = ?', $folderId)
        );

        $result = $this->_getLeadsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads);
         
        return $result;
    }
    
    public function getCountByFolderId($_folderId, $_filter)
    {
        $folderId = (int)$_folderId;
        if($folderId != $_folderId) {
            throw new InvalidArgumentException('$_folderId must be integer');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($folderId, Tinebase_Container::GRANT_READ)) {
            return 0;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('container = ?', $folderId)
        );
                
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->leadTable->getTotalCount($where);

        return $result;
    } 
    
    /**
     * get total count of all leads from shared folders
     *
     * @return int count of all other users leads
     */
    public function getCountOfSharedLeads($_filter, $_leadstate, $_probability, $_getClosedLeads)
    {
        $allContainer = Tinebase_Container::getInstance()->getSharedContainer('crm');

        if(count($allContainer) === 0) {
            return 0;
        }
        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('container IN (?)', $containerIds)
        );
        
        $where = array_merge($where, $this->_getSearchFilter($_filter, $_leadstate, $_probability, $_getClosedLeads));
        
        $result = $this->leadTable->getTotalCount($where);

        return $result;
    }        
     
    /**
     * get total count of all other users leads
     *
     * @return int count of all other users leads
     * 
     */
    public function getCountOfOtherPeopleLeads($_filter, $_leadstate, $_probability, $_getClosedLeads)
    {
        $allContainer = Tinebase_Container::getInstance()->getOtherUsersContainer('crm');

        if(count($allContainer) === 0) {
            return 0;
        }
        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('container IN (?)', $containerIds)	
        );
        
        $where = array_merge($where, $this->_getSearchFilter($_filter, $_leadstate, $_probability, $_getClosedLeads));
        
        $result = $this->leadTable->getTotalCount($where);

        return $result;
    }   
 
    /**
    * add a lead
    *
    * @param Crm_Lead $_leadData the leaddata
    * @return Crm_Model_Lead
    */
    public function addLead(Crm_Model_Lead $_lead)
    {
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }

        $leadData = $_lead->toArray();
        if(empty($_lead->id)) {
            unset($leadData['id']);
        }
        
        $id = $this->leadTable->insert($leadData);

        // if we insert a contact without an id, we need to get back one
        if(empty($_lead->id) && $id == 0) {
            throw new Exception("returned lead id is 0");
        }
        
        // if the account had no accountId set, set the id now
        if(empty($_lead->id)) {
            $_lead->id = $id;
        }
        
        return $this->getLead($_lead->id);
        
    }
    
    /**
     * get lead
     *
     * @param int $_id
     * @return Crm_Model_Lead
     */
    public function getLead($_id)
    {
        $id = Crm_Controller::convertLeadIdToInt($_id);

        $select = $this->_getLeadSelectObject()
            ->where(Zend_Registry::get('dbAdapter')->quoteInto('lead.id = ?', $id));

      // echo $select->__toString();
       
        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        if(empty($row)) {
            throw new UnderflowException('lead not found');
        }
        
        $lead = new Crm_Model_Lead($row);

        return $lead;
    }

    /**
     * get list of leads from all shared folders the current user has access to
     *
     * @param array $_container container to read the contacts from
     * @param string $_filter string to search for in leads
     * @param unknown_type $_sort fieldname to sort by
     * @param unknown_type $_dir sort ascending or descending (ASC | DESC)
     * @param unknown_type $_limit how many leads to display
     * @param unknown_type $_start how many leads to skip
     * @param int $_leadstate
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return Tinebase_Record_RecordSet subclass Crm_Model_Lead
     */
    public function getLeads(array $_container, $_filter = NULL, $_sort = 'id', $_dir = 'ASC', $_limit = NULL, $_start = NULL, $_leadstate, $_probability, $_getClosedLeads)
    {
        if(count($allContainer) === 0) {
            throw new Exception('$_container can not be empty');
        }        

        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('container IN (?)', $containerIds)
        );
        $result = $this->_getLeadsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads);
         
        return $result;
    }
    
    
    /**
     * delete lead
     *
     * @param int|Crm_Model_Lead $_leads lead ids
     * @return void
     */
    public function deleteLead($_leadId)
    {
        $leadId = Crm_Controller::convertLeadIdToInt($_leadId);

        $db = Zend_Registry::get('dbAdapter');
        
        $db->beginTransaction();
        
        try {
            $where = array(
                $db->quoteInto('lead_id = ?', $leadId)
            );          
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_product', $where);            

            $where = array(
                $db->quoteInto('link_app1 = ?', 'crm'),
                $db->quoteInto('link_id1 = ?', $leadId),
                $db->quoteInto('link_app2 = ?', 'addressbook')
            );                                  
            $db->delete(SQL_TABLE_PREFIX . 'links', $where);               
            
            $where = array(
                $db->quoteInto('id = ?', $leadId)
            );
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_lead', $where);

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * updates a lead
     *
     * @param Crm_Lead $_leadData the leaddata
     * @return Crm_Model_Lead
     */
    public function updateLead(Crm_Model_Lead $_lead)
    {
        if(!$_lead->isValid()) {
            throw new Exception('lead object is not valid');
        }
        
        $leadId = Crm_Controller::convertLeadIdToInt($_lead);        

        $leadData = $_lead->toArray();
        unset($leadData['id']);
        
        $where  = array(
            $this->leadTable->getAdapter()->quoteInto('id = ?', $leadId),
        );
        
        $updatedRows = $this->leadTable->update($leadData, $where);
        
        if($updatedRows == 0) {
            throw new Exception("update of lead failed! Does the leadId exist?");
        }
        
        return $this->getLead($leadId);
    }
}
