<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
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
        try {
            $this->leadTable      = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_lead'));
        } catch (Zend_Db_Statement_Exception $e) {
            Crm_Setup_SetupSqlTables::createLeadTable();
            $this->leadTable      = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_lead'));
        }

        try {
            $this->leadSourceTable   = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadsource'));
        } catch (Zend_Db_Statement_Exception $e) {
            Crm_Setup_SetupSqlTables::createLeadSourceTable();
            $this->leadSourceTable   = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadsource'));
        }
        
        try {
            $this->leadTypeTable     = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadtype'));
        } catch (Zend_Db_Statement_Exception $e) {
            Crm_Setup_SetupSqlTables::createLeadTypeTable();
            $this->leadTypeTable     = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadtype'));
        }
        
        try {
            $this->leadStateTable    = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadstate'));
        } catch (Zend_Db_Statement_Exception $e) {
            // temporary hack, until setup is available
            Crm_Setup_SetupSqlTables::createLeadStateTable();
            $this->leadStateTable    = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadstate'));
        }
        
        try {
            $this->productSourceTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_productsource'));
        } catch (Zend_Db_Statement_Exception $e) {
            // temporary hack, until setup is available
            Crm_Setup_SetupSqlTables::createProductSourceTable();
            $this->productSourceTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_productsource'));
        }

        try {
            $this->productsTable      = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_product'));
        } catch (Zend_Db_Statement_Exception $e) {
            // temporary hack, until setup is available
            Crm_Setup_SetupSqlTables::createProductTable();
            $this->productsTable      = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_product'));
        }
        
        
        $this->linksTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'links'));
        
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
    * @param Egwbase_Record_Recordset $_leadSources list of lead sources
    * @return unknown
    */
    public function saveLeadsources(Egwbase_Record_Recordset $_leadSources)
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
            $this->leadSourceTable->getAdapter()->quoteInto('lead_leadsource_id = ?', $Id),
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
    public function saveLeadtypes(Egwbase_Record_Recordset $_optionData)
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
                $this->leadTypeTable->getAdapter()->quoteInto('lead_leadtype_id = ?', $Id),
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
    public function saveProductsource(Egwbase_Record_Recordset $_optionData)
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
                $this->linksTable->getAdapter()->quoteInto('lead_productsource_id = ?', $Id),
            );
             
            $result = $this->productSourceTable->delete($where);

        return $result;
    }    
    
    
	/**
	* get LeadContacts
	*
	* @return unknown
	*/  
    public function getLeadContacts(Egwbase_Record_Recordset $_leads)
    {    
        $leads = $_leads->toArray();
        
        foreach($leads AS $lead)
        {
            $_id = $lead['lead_id'];
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
    public function getContactsById($_id)
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
        $contacts = new Egwbase_Record_RecordSet($rows, 'Crm_Model_Contact');
        
        return $contacts;
    }
  
  
  
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
    * get leadstate identified by id
    *
    * @return Crm_Model_Leadstate
    */
    public function getLeadState($_stateId)
    {   
        $stateId = (int)$_stateId;
        if($stateId != $_stateId) {
            throw new InvalidArgumentException('$_stateId must be integer');
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
    public function saveLeadstates(Egwbase_Record_Recordset $_optionData)
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
                $this->leadStateTable->getAdapter()->quoteInto('lead_leadstate_id = ?', $Id),
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
            $this->productsTable->getAdapter()->quoteInto('lead_lead_id = ?', $_id)
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
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_product', 'lead_lead_id = ' . $id);      
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
    public function saveProducts(Egwbase_Record_Recordset $_productData)
    {
        /*  if(!Zend_Registry::get('currentAccount')->hasGrant($_leadData->lead_container, Egwbase_Container_Container::GRANT_EDIT)) {
            throw new Exception('write access to lead->product denied');
        }    
    */   
    
        $_daten = $_productData->toArray();
    
        $lead_id = $_daten[0]['lead_lead_id'];


        if(!(int)$lead_id) {
             return $_productData;  
        }
        

        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_product', 'lead_lead_id = '.$lead_id);

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

        if($_productData->lead_id === NULL) {
            $result = $this->productsTable->insert($productData);
            $_productData->lead_id = $this->productsTable->getAdapter()->lastInsertId();
        } else {
            $where  = array(
                $this->productsTable->getAdapter()->quoteInto('lead_id = (?)', $_productData->lead_id),
            );

            $result = $this->productsTable->update($productData, $where);
        }

        return $_productData;
    }

   
	
    /**
    * adds contacts
    *
    * @param Egwbase_Record_Recordset $_leadSources list of lead sources
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
            ->where(Zend_Registry::get('dbAdapter')->quoteInto('lead_id = ?', $id));

        //error_log($select->__toString());
       
        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        if(empty($row)) {
            throw new UnderFlowExecption('lead not found');
        }
        
        //error_log(print_r($row, true));
        
        $lead = new Crm_Model_Lead($row);
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($lead->lead_container, Egwbase_Container_Container::GRANT_READ)) {
            throw new Exception('permission to lead denied');
        }
        
        return $lead;

/*        $result = $this->leadTable->fetchRow($where);

        if($result === NULL) {
            throw new UnderFlowExecption('lead not found');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($result->lead_container, Egwbase_Container_Container::GRANT_READ)) {
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
        $ownerContainer = Egwbase_Container_Container::getInstance()->getPersonalContainer('crm', $owner);
        
        if($ownerContainer->count() === 0) {
            return false;
        }
        
        $containerIds = array();

        foreach($ownerContainer as $container) {
            $containerIds[] = $container->container_id;
        }

        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container IN (?)', $containerIds)
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
        $ownerContainer = Egwbase_Container_Container::getInstance()->getPersonalContainer('crm', $owner);
        
        $containerIds = array();
        
        foreach($ownerContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container IN (?)', $containerIds)
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

        if(empty($_lead->lead_container)) {
            throw new UnderflowException('lead_container can not be empty');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_lead->lead_container, Egwbase_Container_Container::GRANT_EDIT)) {
            throw new Exception('write access to lead denied');
        }

        $leadArray = $_lead->toArray();
        unset($leadArray['lead_id']);
        
        if(empty($_lead->lead_id)) {
            $_lead->lead_id = $this->leadTable->insert($leadArray);
            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' added new lead ' . $_lead->lead_id);
        } else {      
            $where  = array(
                $this->leadTable->getAdapter()->quoteInto('lead_id = ?', $_lead->lead_id),
            );

            $result = $this->leadTable->update($_lead->toArray(), $where);
            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' updated lead ' . $_lead->lead_id);
        }

        return $_lead;
    }

    /**
     * delete lead identified by lead_id
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

        if(!Zend_Registry::get('currentAccount')->hasGrant($oldLeadData->lead_container, Egwbase_Container_Container::GRANT_DELETE)) {
            throw new Exception('delete access to CRM denied');
        }

        $db = Zend_Registry::get('dbAdapter');
        
        $db->beginTransaction();
        
        try {
            $where_lead    = $db->quoteInto('lead_id = ?', $leadId);
            $where_product = $db->quoteInto('lead_lead_id = ?', $leadId);          
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
            $this->leadTable->getAdapter()->quoteInto('lead_id = ?', $leadId),
        );

        $result = $this->leadTable->delete($where);

        return $result;
    }


	// handle FOLDERS  
    public function addFolder($_name, $_type) 
    {
    	
        $egwbaseContainer = Egwbase_Container_Container::getInstance();
        $accountId   = Zend_Registry::get('currentAccount')->accountId;
        $allGrants = array(
            Egwbase_Container_Container::GRANT_ADD,
            Egwbase_Container_Container::GRANT_ADMIN,
            Egwbase_Container_Container::GRANT_DELETE,
            Egwbase_Container_Container::GRANT_EDIT,
            Egwbase_Container_Container::GRANT_READ
        );
        
        if($_type == Egwbase_Container_Container::TYPE_SHARED) {
            $folderId = $egwbaseContainer->addContainer('crm', $_name, Egwbase_Container_Container::TYPE_SHARED, Crm_Backend_Factory::SQL);

            // add admin grants to creator
            $egwbaseContainer->addGrants($folderId, $accountId, $allGrants);
            // add read grants to any other user
            $egwbaseContainer->addGrants($folderId, NULL, array(Egwbase_Container_Container::GRANT_READ));
        } else {
            $folderId = $egwbaseContainer->addContainer('crm', $_name, Egwbase_Container_Container::TYPE_PERSONAL, Crm_Backend_Factory::SQL);
        
            // add admin grants to creator
            $egwbaseContainer->addGrants($folderId, $accountId, $allGrants);
        }
        
        return $folderId;
    }
    
    public function deleteFolder($_folderId)
    {
        $egwbaseContainer = Egwbase_Container_Container::getInstance();
        
        $egwbaseContainer->deleteContainer($_folderId);
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container = ?', (int)$_folderId)
        );
        
        $this->leadTable->delete($where);
        
        return true;
    }
    
    public function renameFolder($_folderId, $_name)
    {
        $egwbaseContainer = Egwbase_Container_Container::getInstance();
        
        $egwbaseContainer->renameContainer($_folderId, $_name);
                
        return true;
    }    
     
     
    public function getFoldersByOwner($_owner) 
    {
        $personalFolders = Egwbase_Container_Container::getInstance()->getPersonalContainer('crm', $_owner);
                
        return $personalFolders;
    }   
 
    public function getSharedFolders() {
        $sharedFolders = Egwbase_Container_Container::getInstance()->getSharedContainer('crm');
                
        return $sharedFolders;
    }
    
    public function getOtherUsers() 
    {
        $rows = Egwbase_Container_Container::getInstance()->getOtherUsers('crm');

        //$accountData = array();
        
        $result = new Egwbase_Record_RecordSet(NULL, 'Egwbase_Account_Model_Account');

        foreach($rows as $account) {
            $accountData = array(
                'account_id'      => $account['account_id'],
                'account_loginid' => 'loginid',
                'account_name'    => 'Account ' . $account['account_id']
            );
            $result->addRecord($accountData);
        }

        //$result = new Egwbase_Record_RecordSet($accountData, 'Egwbase_Account_Model_Account');
        
        return $result;
    }

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
                $where[] = Zend_Registry::get('dbAdapter')->quoteInto('(lead_name LIKE ? OR lead_description LIKE ?)', '%' . $search_value . '%');                            
            }
        }
        
        if( is_numeric($_leadstate) && $_leadstate > 0 ) {
            $where[] = Zend_Registry::get('dbAdapter')->quoteInto('lead.lead_leadstate_id = ?', (int)$_leadstate);
        }
        
        if( is_numeric($_probability) && $_probability > 0 ) {
            $where[] = Zend_Registry::get('dbAdapter')->quoteInto('lead_probability >= ?', (int)$_probability);
        }       

        if($_getClosedLeads === FALSE  || $_getClosedLeads == 'false') {
            $where[] = 'lead_end IS NULL';
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
        //error_log($select->__toString());
       
        $stmt = $db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $leads = new Egwbase_Record_RecordSet($rows, 'Crm_Model_Lead');
        
        return $leads;
    }   
    
    /**
     * get the basic select object to fetch contacts from the database 
     *
     * @todo do we still need this function
     * @return Zend_Db_Select
     */
    protected function _getContactsSelectObject()
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
                    'contact_owner',
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
    }    
    
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
                'lead_id',
                'lead_name',
                'lead_leadstate_id',
                'lead_leadtype_id',
                'lead_leadsource_id',
                'lead_container',
                'lead_start',
                'lead_description',
                'lead_end',
                'lead_turnover',
                'lead_probability',
                'lead_end_scheduled')
            )
            ->join(array('state' => SQL_TABLE_PREFIX . 'metacrm_leadstate'), 
                    'lead.lead_leadstate_id = state.lead_leadstate_id');

        return $selectObject;
    }

    protected function _addQuickSearchFilter($_where, $_filter)
    {
        if(!empty($_filter)) {
            $search_values = explode(" ", $_filter);
            
            foreach($search_values AS $search_value) {
                $_where[] = $this->leadTable->getAdapter()->quoteInto('(lead_name LIKE ? OR lead_description LIKE ?)', '%' . $search_value . '%');                            
            }
        }
        
        return $_where;
    }


// handle FOLDER->LEADS overview
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
     * @return Egwbase_Record_RecordSet subclass Crm_Model_Lead
     */
    public function getAllLeads($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_leadstate, $_probability, $_getClosedLeads)
    {
        $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Egwbase_Container_Container::GRANT_READ);
        
        if(count($allContainer) === 0) {
            $this->createPersonalContainer();
            $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Egwbase_Container_Container::GRANT_READ);
        }        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('lead_container IN (?)', $containerIds)
        );
        $result = $this->_getLeadsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads);
         
        return $result;
    }
    
    public function _getCountOfAllLeads($_filter, $_leadstate, $_probability, $_getClosedLeads)
    {
        $containers = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Egwbase_Container_Container::GRANT_READ);
        
        
        $containerIds = array();
        
        foreach($containers as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('contact_owner IN (?)', $containerIds)
        );
        
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->contactsTable->getTotalCount($where);

        return $result;
    }
    
    /**
     * get total count of all leads from shared folders
     *
     * @todo return the correct count (the accounts are missing)
     *
     * @return int count of all other users leads
     */
    public function getCountOfAllLeads($_filter, $_leadstate, $_probability, $_getClosedLeads)
    {
        $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Egwbase_Container_Container::GRANT_READ);

        if(empty($allContainer)) {
            return 0;
        }
        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container IN (?)', $containerIds)
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
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_folderId, Egwbase_Container_Container::GRANT_READ)) {
            throw new Exception('read access denied to folder');
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container = ?', $folderId)
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
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($folderId, Egwbase_Container_Container::GRANT_READ)) {
            throw new Exception('read access denied to folder');
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container = ?', $folderId)
        );
                
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->leadTable->getTotalCount($where);

        return $result;
    } 

    
    public function getSharedLeads($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_leadstate = NULL, $_probability = NULL, $_getClosedLeads = TRUE) 
    {
        $sharedContainer = Egwbase_Container_Container::getInstance()->getSharedContainer('crm');
        
        if($sharedContainer->count() === 0) {
            return false;
        }
        
        $containerIds = array();
        
        foreach($sharedContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container IN (?)', $containerIds)
        );

        $result = $this->_getLeadsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads);
         
        return $result;
    }
    
    /**
     * get total count of all leads from shared folders
     *
     * @return int count of all other users leads
     */
    public function getCountOfSharedLeads($_filter, $_leadstate, $_probability, $_getClosedLeads)
    {
        $allContainer = Egwbase_Container_Container::getInstance()->getSharedContainer('crm');

        if(empty($allContainer)) {
            return 0;
        }
        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container IN (?)', $containerIds)
        );
        
        $where = array_merge($where, $this->_getSearchFilter($_filter, $_leadstate, $_probability, $_getClosedLeads));
        
        $result = $this->leadTable->getTotalCount($where);

        return $result;
    }        
 
   
   public function getOtherPeopleLeads($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_leadstate, $_probability, $_getClosedLeads) 
    {
        $otherPeoplesContainer = Egwbase_Container_Container::getInstance()->getOtherUsersContainer('crm');
        
        $containerIds = array();
        $containerIdsPresent = "0";

        foreach($otherPeoplesContainer as $container) {
            $containerIds[] = $container->container_id;
            
            if(is_numeric($container->container_id)) {
                  $containerIdsPresent = "1";
            }            
        }

        if($containerIdsPresent == "0") {
             return false;   
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container IN (?)', $containerIds)
        );

        $result = $this->_getLeadsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_leadstate, $_probability, $_getClosedLeads);
         
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
        $allContainer = Egwbase_Container_Container::getInstance()->getOtherUsersContainer('crm');

        if(empty($allContainer)) {
            return 0;
        }
        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container IN (?)', $containerIds)
        );
        
        $where = array_merge($where, $this->_getSearchFilter($_filter, $_leadstate, $_probability, $_getClosedLeads));
        
        $result = $this->leadTable->getTotalCount($where);

        return $result;
    }   
 
   /**
     * create personal container for current user
     *
     */
    public function createPersonalContainer()
    {
        $this->addFolder('Personal Leads', Egwbase_Container_Container::TYPE_PERSONAL);
    }
     
    
}
