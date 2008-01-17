<?php

/**
 * interface for projects class
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Sql.php 199 2008-01-15 15:27:00Z twadewitz $
 *
 */
class Crm_Backend_Sql implements Crm_Backend_Interface
{
	/**
	* Instance of Crm_Backend_Sql_Projects
	*
	* @var Crm_Backend_Sql_Projects
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
            $this->createLeadTable();
        }

        try {
            $this->leadSourceTable   = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadsource'));
        } catch (Zend_Db_Statement_Exception $e) {
            $this->createLeadSourceTable();
        }
        
        try {
            $this->leadTypeTable     = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadtype'));
        } catch (Zend_Db_Statement_Exception $e) {
            $this->createLeadTypeTable();
        }
        
        try {
            $this->leadStateTable    = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadstate'));
        } catch (Zend_Db_Statement_Exception $e) {
            // temporary hack, until setup is available
            $this->createLeadStateTable();
        }
        
        try {
            $this->productSourceTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_productsource'));
        } catch (Zend_Db_Statement_Exception $e) {
            // temporary hack, until setup is available
            $this->createProductSourceTable();
        }

        try {
            $this->productsTable      = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_product'));
        } catch (Zend_Db_Statement_Exception $e) {
            // temporary hack, until setup is available
            $this->createProductTable();
        }
        
        
        $this->linksTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'links'));
        
    }

    /**
     * temporary function to create the egw_metacrm_lead table on demand
     *
     */
    protected function createLeadTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_lead');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_lead` (
                    `lead_id` int(11) NOT NULL auto_increment,
                    `lead_name` varchar(255) NOT NULL default '',
                    `lead_leadstate_id` int(11) NOT NULL default '0',
                    `lead_leadtype_id` int(11) NOT NULL default '0',
                    `lead_leadsource_id` int(11) NOT NULL default '0',
                    `lead_container` int(11) NOT NULL default '0',
                    `lead_modifier` int(11) default NULL,
                    `lead_start` DATETIME NOT NULL,
                    `lead_modified` int(11) NOT NULL default '0',
                    `lead_created` int(11) unsigned NOT NULL default '0',
                    `lead_description` text,
                    `lead_end` DATETIME default NULL,
                    `lead_turnover` double default NULL,
                    `lead_probability` decimal(3,0) default NULL,
                    `lead_end_scheduled` DATETIME default NULL,
                    `lead_lastread` int(11) NOT NULL default '0',
                    `lead_lastreader` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`lead_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $this->leadTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_lead'));
    }
    
    /**
     * temporary function to create the egw_metacrm_leadsource table on demand
     *
     */
    protected function createLeadSourceTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_leadsource');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_leadsource` (
                    `lead_leadsource_id` int(11) NOT NULL auto_increment,
                    `lead_leadsource` varchar(255) NOT NULL,
                    `lead_leadsource_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`lead_leadsource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $this->leadSourceTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadsource'));
        
        $this->leadSourceTable->insert(array(
            'lead_leadsource_id'    => 1,
            'lead_leadsource'       => 'telephone'
        ));
        $this->leadSourceTable->insert(array(
            'lead_leadsource_id'    => 2,
            'lead_leadsource'       => 'email'
        ));
        $this->leadSourceTable->insert(array(
            'lead_leadsource_id'    => 3,
            'lead_leadsource'       => 'website'
        ));
        $this->leadSourceTable->insert(array(
            'lead_leadsource_id'    => 4,
            'lead_leadsource'       => 'fair'
        ));
    }
        
    /**
     * temporary function to create the egw_metacrm_leadtype table on demand
     *
     */
    protected function createLeadTypeTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_leadtype');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_leadtype` (
                    `lead_leadtype_id` int(11) NOT NULL auto_increment,
                    `lead_leadtype` varchar(255) default NULL,
                    `lead_leadtype_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`lead_leadtype_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $this->leadTypeTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadtype'));
        
        $this->leadTypeTable->insert(array(
            'lead_leadtype_id'    => 1,
            'lead_leadtype'       => 'customer'
        ));
        $this->leadTypeTable->insert(array(
            'lead_leadtype_id'    => 2,
            'lead_leadtype'       => 'partner'
        ));
        $this->leadTypeTable->insert(array(
            'lead_leadtype_id'    => 3,
            'lead_leadtype'       => 'reseller'
        ));
    }
        
    /**
     * temporary function to create the egw_metacrm_leadstate table on demand
     *
     */
    protected function createLeadStateTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_leadstate');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_leadstate` (
                    `lead_leadstate_id` int(11) NOT NULL auto_increment,
                    `lead_leadstate` varchar(255) default NULL,
                    `lead_leadstate_probability` tinyint(3) unsigned NOT NULL default '0',
                    `lead_leadstate_endsproject` tinyint(1) default NULL,
                    `lead_leadstate_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`lead_leadstate_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $this->leadStateTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadstate'));

        $this->leadStateTable->insert(array(
            'lead_leadstate_id'           => 1,
            'lead_leadstate'              => 'open',
            'lead_leadstate_probability'  => 0
        ));    
        $this->leadStateTable->insert(array(
            'lead_leadstate_id'           => 2,
            'lead_leadstate'              => 'contacted',
            'lead_leadstate_probability'  => 10
        ));
        $this->leadStateTable->insert(array(
            'lead_leadstate_id'           => 3,
            'lead_leadstate'              => 'waiting for feedback',
            'lead_leadstate_probability'  => 30
        ));
        $this->leadStateTable->insert(array(
            'lead_leadstate_id'           => 4,
            'lead_leadstate'              => 'quote sent',
            'lead_leadstate_probability'  => 50
        ));
        $this->leadStateTable->insert(array(
            'lead_leadstate_id'           => 5,
            'lead_leadstate'              => 'accepted',
            'lead_leadstate_probability'  => 100,
            'lead_leadstate_endsproject'  => 1
        ));
        $this->leadStateTable->insert(array(
            'lead_leadstate_id'           => 6,
            'lead_leadstate'              => 'lost',
            'lead_leadstate_probability'  => 0,
            'lead_leadstate_endsproject'  => 1
        ));
    }
        
    /**
     * temporary function to create the egw_metacrm_productsource table on demand
     *
     */
    protected function createProductSourceTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_productsource');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_productsource` (
                    `lead_productsource_id` int(10) unsigned NOT NULL auto_increment,
                    `lead_productsource` varchar(200) NOT NULL default '',
                    `lead_productsource_price` decimal(12,2) unsigned NOT NULL default '0.00',
                    PRIMARY KEY  (`lead_productsource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $this->productSourceTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_productsource'));
    }
    
    /**
     * temporary function to create the egw_metacrm_product table on demand
     *
     */
    protected function createProductTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_product');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_product` (
                    `lead_id` int(11) NOT NULL auto_increment,
                    `lead_project_id` int(11) NOT NULL,
                    `lead_product_id` int(11) NOT NULL,
                    `lead_product_desc` varchar(255) default NULL,
                    `lead_product_price` decimal(12,2) unsigned NOT NULL default '0.00',
                    PRIMARY KEY  (`lead_id`),
                    KEY `lead_project_id` (`lead_project_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $this->productsTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_product'));
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
        $where[] = Zend_Registry::get('dbAdapter')->quoteInto('links.link_id1 = ?', $_id);        
				        
        $select = $this->_getContactsSelectObject();

        if(is_array($where)) {
             foreach($where as $_where) {
                  $select->where($_where);
             }               
        }

   //     error_log($select->__toString());
       
        $stmt = $select->query();
/*
        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        
        if(empty($row)) {
            throw new UnderFlowExecption('no contacts found');
        }
        
        //error_log(print_r($row, true));
        
        $contacts = new Crm_Model_Project($row);
    */    
    
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $contacts = new Egwbase_Record_RecordSet($rows, 'Crm_Model_Contact');
        
        return $contacts;
    }
  
  
  
	// handle LEADSTATES    
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


	// handle PRODUCTS (associated to project)
	/**
	* get products by project id
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
            $this->productsTable->getAdapter()->quoteInto('lead_project_id = ?', $_id)
        );

        $result = $this->productsTable->fetchAll($where);

        return $result;
    }      

	/**
	* delete products (which belong to one project)
	*
	* @param int $_Id the id of the project
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
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_product', 'lead_project_id = '.$_id);      
        } catch (Exception $e) {
            error_log($e->getMessage());
        }      
        
        return true;
   
    }

	/**
	* add or updates an product (which belongs to one project)
	*
	* @param int $_productId the id of the product, NULL if new, else gets updated
	* @param Crm_Product $_productData the productdata
	* @param int $_projectId the project id
	* @return unknown
	*/
    public function saveProducts(Egwbase_Record_Recordset $_productData)
    {
        /*  if(!Zend_Registry::get('currentAccount')->hasGrant($_projectData->lead_container, Egwbase_Container::GRANT_EDIT)) {
            throw new Exception('write access to project->product denied');
        }    
    */   
    
        $_daten = $_productData->toArray();
    
        $project_id = $_daten[0]['lead_project_id'];


        if(!(int)$project_id) {
             return $_productData;  
        }
        

        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_product', 'lead_project_id = '.$project_id);

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
    public function saveContacts(array $_contacts, $_id)
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
    
    
    
	/**
	* get single project by id
	*
	* 
	* 
	* 
	* @return unknown
	*/
    public function getProjectById($_id)
    {
        $id = (int) $_id;
        if($id != $_id) {
            throw new InvalidArgumentException('$_id must be integer');
        }

        $select = $this->_getProjectSelectObject()
            ->where(Zend_Registry::get('dbAdapter')->quoteInto('lead_id = ?', $id));

        //error_log($select->__toString());
       
        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        if(empty($row)) {
            throw new UnderFlowExecption('project not found');
        }
        
        //error_log(print_r($row, true));
        
        $project = new Crm_Model_Project($row);
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($project->lead_container, Egwbase_Container::GRANT_READ)) {
            throw new Exception('permission to project denied');
        }
        
        return $project;

/*        $result = $this->leadTable->fetchRow($where);

        if($result === NULL) {
            throw new UnderFlowExecption('project not found');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($result->lead_container, Egwbase_Container::GRANT_READ)) {
            throw new Exception('permission to project denied');
        }
        
        return $result;*/
    }    
    
    public function getLeadsByOwner($_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_leadstate = NULL, $_probability = NULL)
    {
        $owner = (int)$_owner;
        if($owner != $_owner) {
            throw new InvalidArgumentException('$_owner must be integer');
        }
        $ownerContainer = Egwbase_Container::getInstance()->getPersonalContainer('crm', $owner);
        
        $containerIds = array();

        foreach($ownerContainer as $container) {
            $containerIds[] = $container->container_id;
        }

        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container IN (?)', $containerIds)
        );

        $result = $this->_getProjectsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_dateFrom = NULL, $_dateTo = NULL, $_leadstate, $_probability);
         
        return $result;
    }
    
    public function getCountByOwner($_owner, $_filter)
    {
        $owner = (int)$_owner;
        if($owner != $_owner) {
            throw new InvalidArgumentException('$_owner must be integer');
        }
        $ownerContainer = Egwbase_Container::getInstance()->getPersonalContainer('crm', $owner);
        
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
	* add or updates an project
	*
	* @param int $_projectOwner the owner of the Crm entry
	* @param Crm_Project $_projectData the projectdata
	* @param int $_projectId the project to update, if NULL the project gets added
	* @return unknown
	*/
    public function saveProject(Crm_Model_Project $_project)
    {
        // do not convert timestamps, until we have changed the table layout to store iso dates
        //$projectData = $_project->toArray(false);
        $projectData = $_project->toArray();
        
/*        if($projectData['lead_start'] instanceof Zend_Date) {
            $projectData['lead_start'] = $projectData['lead_start']->get(Zend_Date::TIMESTAMP);
        }

        if($projectData['lead_end'] instanceof Zend_Date) {
            $projectData['lead_end'] = $projectData['lead_end']->get(Zend_Date::TIMESTAMP);
        } else {
            $projectData['lead_end'] = null;
        }
        
        if($projectData['lead_end_scheduled'] instanceof Zend_Date) {
            $projectData['lead_end_scheduled'] = $projectData['lead_end_scheduled']->get(Zend_Date::TIMESTAMP);
        } else {
            $projectData['lead_end_scheduled'] = null;
        } */
        
        //error_log(print_r($projectData, true));

        if(empty($projectData['lead_container'])) {
            throw new UnderflowException('lead_container can not be empty');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($projectData['lead_container'], Egwbase_Container::GRANT_EDIT)) {
            throw new Exception('write access to project denied');
        }

        //$currentAccount = Zend_Registry::get('currentAccount');

        //if(empty($projectData['lead_container'])) {
        //    $projectData['lead_container'] = $currentAccount->account_id;
        //}

        if($projectData['lead_id'] === NULL) {
            $result = $this->leadTable->insert($projectData);
            $_projectData->lead_id = $this->leadTable->getAdapter()->lastInsertId();
        } else {      
            $where  = array(
                $this->leadTable->getAdapter()->quoteInto('lead_id = (?)', $projectData['lead_id']),
            );

            $result = $this->leadTable->update($projectData, $where);
        }

        return $_projectData;
    }

    /**
     * delete project identified by lead_id
     *
     * @param int $_projects project ids
     * @return int the number of rows deleted
     */
    public function deleteProjectById($_projectId)
    {
        $projectId = (int)$_projectId;
        if($projectId != $_projectId) {
            throw new InvalidArgumentException('$_projectId must be integer');
        }

        $oldProjectData = $this->getProjectById($_projectId);

        if(!Zend_Registry::get('currentAccount')->hasGrant($oldProjectData->lead_container, Egwbase_Container::GRANT_DELETE)) {
            throw new Exception('delete access to CRM denied');
        }
       
        $where  = array(
            $this->leadTable->getAdapter()->quoteInto('lead_id = ?', $projectId),
        );

        $result = $this->leadTable->delete($where);

        return $result;
    }


	// handle FOLDERS  
    public function addFolder($_name, $_type) 
    {
        $egwbaseContainer = Egwbase_Container::getInstance();
        $accountId   = Zend_Registry::get('currentAccount')->account_id;
        $allGrants = array(
            Egwbase_Container::GRANT_ADD,
            Egwbase_Container::GRANT_ADMIN,
            Egwbase_Container::GRANT_DELETE,
            Egwbase_Container::GRANT_EDIT,
            Egwbase_Container::GRANT_READ
        );
        
        if($_type == Egwbase_Container::TYPE_SHARED) {
            $folderId = $egwbaseContainer->addContainer('crm', $_name, Egwbase_Container::TYPE_SHARED, Crm_Backend::SQL);

            // add admin grants to creator
            $egwbaseContainer->addGrants($folderId, $accountId, $allGrants);
            // add read grants to any other user
            $egwbaseContainer->addGrants($folderId, NULL, array(Egwbase_Container::GRANT_READ));
        } else {
            $folderId = $egwbaseContainer->addContainer('crm', $_name, Egwbase_Container::TYPE_PERSONAL, Crm_Backend::SQL);
        
            // add admin grants to creator
            $egwbaseContainer->addGrants($folderId, $accountId, $allGrants);
        }
        
        return $folderId;
    }
    
    public function deleteFolder($_folderId)
    {
        $egwbaseContainer = Egwbase_Container::getInstance();
        
        $egwbaseContainer->deleteContainer($_folderId);
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container = ?', (int)$_folderId)
        );
        
        $this->leadTable->delete($where);
        
        return true;
    }
    
    public function renameFolder($_folderId, $_name)
    {
        $egwbaseContainer = Egwbase_Container::getInstance();
        
        $egwbaseContainer->renameContainer($_folderId, $_name);
                
        return true;
    }    
     
     
    public function getFoldersByOwner($_owner) 
    {
        $personalFolders = Egwbase_Container::getInstance()->getPersonalContainer('crm', $_owner);
                
        return $personalFolders;
    }   
 
    public function getSharedFolders() {
        $sharedFolders = Egwbase_Container::getInstance()->getSharedContainer('crm');
                
        return $sharedFolders;
    }
    
    public function getOtherUsers() 
    {
        $rows = Egwbase_Container::getInstance()->getOtherUsers('crm');

        $accountData = array();

        foreach($rows as $account) {
            $accountData[] = array(
                'account_id'      => $account['account_id'],
                'account_loginid' => 'loginid',
                'account_name'    => 'Account ' . $account['account_id']
            );
        }

        $result = new Egwbase_Record_RecordSet($accountData, 'Egwbase_Account_Model_Account');
        
        return $result;
    }


    //handle for FOLDER->PROJECTS functions
    protected function _getProjectsFromTable(array $_where, $_filter, $_sort, $_dir, $_limit, $_start, $_datenFrom = NULL, $_dateTo = NULL, $_leadstate, $_probability)
    {
        $where = $this->_addQuickSearchFilter($_where, $_filter);
/*
        if(is_numeric($_datenFrom)) {    
            $where[] = $this->leadTable->getAdapter()->quoteInto('lead_start >= ? ', $_datenFrom);
        }

        if(is_numeric($_datenTo)) {    
            $where[] = $this->leadTable->getAdapter()->quoteInto('lead_end <= ? ', $_datenTo);
        }
*/

		if( is_numeric($_leadstate) && ($_leadstate > 0) ) {
			$where[] = $this->leadTable->getAdapter()->quoteInto('project.lead_leadstate_id = ?', $_leadstate);
		}
		
		if( is_numeric($_probability) && ($_probability > 0) ) {
			$where[] = $this->leadTable->getAdapter()->quoteInto('lead_probability >= ?', $_probability);
		}		
              

        $db = Zend_Registry::get('dbAdapter');

        $select = $this->_getProjectSelectObject()
            ->order($_sort.' '.$_dir)
            ->limit($_limit, $_start);

        if(is_array($where)) {
             foreach($where as $_where) {
                  $select->where($_where);
             }               
        }
        error_log($select->__toString());
       
        $stmt = $db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $projects = new Egwbase_Record_RecordSet($rows, 'Crm_Model_Project');



        $leadContacts = $this->getLeadContacts($projects);

        $projects->setContactData($leadContacts);

//error_log(print_r($projects));            
        
        return $projects;
    }   
    
    /**
     * get the basic select object to fetch contacts from the database 
     *
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
                                    'adr_one_countryname')
                );
        
        return $select;
    }    
    
    /**
     * get the basic select object to fetch projects from the database 
     *
     * @return Zend_Db_Select
     */
    protected function _getProjectSelectObject()
    {
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
        ->from(array('project' => SQL_TABLE_PREFIX . 'metacrm_lead'), array(
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
                'project.lead_leadstate_id = state.lead_leadstate_id');
        
        return $select;
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


// handle FOLDER->PROJECTS overview
    /**
     * get list of projects from all shared folders the current user has access to
     *
     * @param string $_filter string to search for in projects
     * @param unknown_type $_sort fieldname to sort by
     * @param unknown_type $_dir sort ascending or descending (ASC | DESC)
     * @param unknown_type $_limit how many projects to display
     * @param unknown_type $_start how many projects to skip
     * @param string $_dateFrom
     * @param string $_dateTo
     * @return Egwbase_Record_RecordSet subclass Crm_Model_Project
     */
    public function getAllProjects($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_dateFrom = NULL, $_dateTo = NULL, $_leadstate, $_probability)
    {
        $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Egwbase_Container::GRANT_READ);
        
        if(count($allContainer) === 0) {
            $this->createPersonalContainer();
            $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Egwbase_Container::GRANT_READ);
        }        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('lead_container IN (?)', $containerIds)
        );

        $result = $this->_getProjectsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_dateFrom, $_dateTo ,$_leadstate, $_probability);
         
        return $result;
    }

    /**
     * get total count of all projects from shared folders
     *
     * @todo return the correct count (the accounts are missing)
     *
     * @return int count of all other users projects
     */
    public function getCountOfAllProjects($_filter)
    {
        $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Egwbase_Container::GRANT_READ);
        
        $containerIds = array();
        
        foreach($allContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container IN (?)', $containerIds)
        );
        
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->leadTable->getTotalCount($where);

        return $result;
    }
   
   
    public function getLeadsByFolder($_folderId, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_leadstate = NULL, $_probability = NULL)
    {
        // convert to int
        $folderId = (int)$_folderId;
        if($folderId != $_folderId) {
            throw new InvalidArgumentException('$_folderId must be integer');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($_folderId, Egwbase_Container::GRANT_READ)) {
            throw new Exception('read access denied to folder');
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container = ?', $folderId)
        );

        $result = $this->_getProjectsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_dateFrom = NULL, $_dateTo = NULL, $_leadstate, $_probability);
         
        return $result;
    }
    
    public function getCountByFolderId($_folderId, $_filter)
    {
        $folderId = (int)$_folderId;
        if($folderId != $_folderId) {
            throw new InvalidArgumentException('$_folderId must be integer');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($folderId, Egwbase_Container::GRANT_READ)) {
            throw new Exception('read access denied to folder');
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container = ?', $folderId)
        );
                
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->leadTable->getTotalCount($where);

        return $result;
    } 

    
    public function getSharedLeads($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_leadstate = NULL, $_probability = NULL) 
    {
        $sharedContainer = Egwbase_Container::getInstance()->getSharedContainer('crm');
        
        $containerIds = array();
        
        foreach($sharedContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->leadTable->getAdapter()->quoteInto('lead_container IN (?)', $containerIds)
        );

        $result = $this->_getProjectsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_dateFrom = NULL, $_dateTo = NULL, $_leadstate, $_probability);
         
        return $result;
    }
    
    /**
     * get total count of all projects from shared folders
     *
     * @return int count of all other users projects
     */
    public function getCountOfSharedProjects()
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'crm', Egwbase_Acl::READ, Egwbase_Acl::GROUP_GRANTS);

        if(empty($acl)) {
            return false;
        }

        $groupIds = array_keys($acl);

        $result = $this->leadTable->getCountByAcl($groupIds);

        return $result;
    }        
 
   
   public function getOtherPeopleProjects($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_dateFrom = NULL, $_dateTo = NULL, $_leadstate, $_probability) 
    {
        $otherPeoplesContainer = Egwbase_Container::getInstance()->getOtherUsersContainer('crm');
        
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

        $result = $this->_getProjectsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_dateFrom = NULL, $_dateTo = NULL, $_leadstate, $_probability);
         
        return $result;
    }
    
    /**
     * get total count of all other users projects
     *
     * @return int count of all other users projects
     * 
     */
    public function getCountOfOtherPeopleProjects()
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'crm', Egwbase_Acl::READ, Egwbase_Acl::ACCOUNT_GRANTS);

        if(empty($acl)) {
            return false;
        }

        $groupIds = array_keys($acl);

        $result = $this->leadTable->getCountByAcl($groupIds);

        return $result;
    }   
 
   /**
     * create personal container for current user
     *
     */
    public function createPersonalContainer()
    {
        $this->addFolder('Personal Leads', Egwbase_Container::TYPE_PERSONAL);
    } 
    
}
