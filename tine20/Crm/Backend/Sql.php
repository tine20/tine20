<?php

/**
 * interface for projects class
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Sql.php 199 2007-10-15 16:30:00Z twadewitz $
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
                    `pj_id` int(11) NOT NULL auto_increment,
                    `pj_name` varchar(255) NOT NULL default '',
                    `pj_leadstate_id` int(11) NOT NULL default '0',
                    `pj_leadtype_id` int(11) NOT NULL default '0',
                    `pj_leadsource_id` int(11) NOT NULL default '0',
                    `pj_owner` int(11) NOT NULL default '0',
                    `pj_modifier` int(11) default NULL,
                    `pj_start` DATETIME NOT NULL,
                    `pj_modified` int(11) NOT NULL default '0',
                    `pj_created` int(11) unsigned NOT NULL default '0',
                    `pj_description` text,
                    `pj_end` DATETIME default NULL,
                    `pj_turnover` double default NULL,
                    `pj_probability` decimal(3,0) default NULL,
                    `pj_end_scheduled` DATETIME default NULL,
                    `pj_lastread` int(11) NOT NULL default '0',
                    `pj_lastreader` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`pj_id`)
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
                    `pj_leadsource_id` int(11) NOT NULL auto_increment,
                    `pj_leadsource` varchar(255) NOT NULL,
                    `pj_leadsource_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`pj_leadsource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $this->leadSourceTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadsource'));
        
        $this->leadSourceTable->insert(array(
            'pj_leadsource_id'    => 1,
            'pj_leadsource'       => 'telephone'
        ));
        $this->leadSourceTable->insert(array(
            'pj_leadsource_id'    => 2,
            'pj_leadsource'       => 'email'
        ));
        $this->leadSourceTable->insert(array(
            'pj_leadsource_id'    => 3,
            'pj_leadsource'       => 'website'
        ));
        $this->leadSourceTable->insert(array(
            'pj_leadsource_id'    => 4,
            'pj_leadsource'       => 'fair'
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
                    `pj_leadtype_id` int(11) NOT NULL auto_increment,
                    `pj_leadtype` varchar(255) default NULL,
                    `pj_leadtype_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`pj_leadtype_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $this->leadTypeTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadtype'));
        
        $this->leadTypeTable->insert(array(
            'pj_leadtype_id'    => 1,
            'pj_leadtype'       => 'customer'
        ));
        $this->leadTypeTable->insert(array(
            'pj_leadtype_id'    => 2,
            'pj_leadtype'       => 'partner'
        ));
        $this->leadTypeTable->insert(array(
            'pj_leadtype_id'    => 3,
            'pj_leadtype'       => 'reseller'
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
                    `pj_leadstate_id` int(11) NOT NULL auto_increment,
                    `pj_leadstate` varchar(255) default NULL,
                    `pj_leadstate_probability` tinyint(3) unsigned NOT NULL default '0',
                    `pj_leadstate_endsproject` tinyint(1) default NULL,
                    `pj_leadstate_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`pj_leadstate_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $this->leadStateTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadstate'));

        $this->leadStateTable->insert(array(
            'pj_leadstate_id'           => 1,
            'pj_leadstate'              => 'open',
            'pj_leadstate_probability'  => 0
        ));    
        $this->leadStateTable->insert(array(
            'pj_leadstate_id'           => 2,
            'pj_leadstate'              => 'contacted',
            'pj_leadstate_probability'  => 10
        ));
        $this->leadStateTable->insert(array(
            'pj_leadstate_id'           => 3,
            'pj_leadstate'              => 'waiting for feedback',
            'pj_leadstate_probability'  => 30
        ));
        $this->leadStateTable->insert(array(
            'pj_leadstate_id'           => 4,
            'pj_leadstate'              => 'quote sent',
            'pj_leadstate_probability'  => 50
        ));
        $this->leadStateTable->insert(array(
            'pj_leadstate_id'           => 5,
            'pj_leadstate'              => 'accepted',
            'pj_leadstate_probability'  => 100,
            'pj_leadstate_endsproject'  => 1
        ));
        $this->leadStateTable->insert(array(
            'pj_leadstate_id'           => 6,
            'pj_leadstate'              => 'lost',
            'pj_leadstate_probability'  => 0,
            'pj_leadstate_endsproject'  => 1
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
                    `pj_productsource_id` int(10) unsigned NOT NULL auto_increment,
                    `pj_productsource` varchar(200) NOT NULL default '',
                    `pj_productsource_price` decimal(12,2) unsigned NOT NULL default '0.00',
                    PRIMARY KEY  (`pj_productsource_id`)
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
                    `pj_id` int(11) NOT NULL auto_increment,
                    `pj_project_id` int(11) NOT NULL,
                    `pj_product_id` int(11) NOT NULL,
                    `pj_product_desc` varchar(255) default NULL,
                    `pj_product_price` decimal(12,2) unsigned NOT NULL default '0.00',
                    PRIMARY KEY  (`pj_id`),
                    KEY `pj_project_id` (`pj_project_id`)
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
            $this->leadSourceTable->getAdapter()->quoteInto('pj_leadsource_id = ?', $Id),
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
                $this->leadTypeTable->getAdapter()->quoteInto('pj_leadtype_id = ?', $Id),
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
                $this->productSourceTable->getAdapter()->quoteInto('pj_productsource_id = ?', $Id),
            );
             
            $result = $this->productSourceTable->delete($where);

        return $result;
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
                $this->leadStateTable->getAdapter()->quoteInto('pj_leadstate_id = ?', $Id),
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
            $this->productsTable->getAdapter()->quoteInto('pj_project_id = ?', $_id)
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
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_product', 'pj_project_id = '.$_id);      
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
        /*  if(!Zend_Registry::get('currentAccount')->hasGrant($_projectData->pj_owner, Egwbase_Container::GRANT_EDIT)) {
            throw new Exception('write access to project->product denied');
        }    
    */   
    
        $_daten = $_productData->toArray();
    
        $project_id = $_daten[0]['pj_project_id'];


        if(!(int)$project_id) {
             return $_productData;  
        }
        

        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete(SQL_TABLE_PREFIX . 'metacrm_product', 'pj_project_id = '.$project_id);

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

        if($_productData->pj_id === NULL) {
            $result = $this->productsTable->insert($productData);
            $_productData->pj_id = $this->productsTable->getAdapter()->lastInsertId();
        } else {
            $where  = array(
                $this->productsTable->getAdapter()->quoteInto('pj_id = (?)', $_productData->pj_id),
            );

            $result = $this->productsTable->update($productData, $where);
        }

        return $_productData;
    }

   
	// handle PROJECTS    
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
            ->where(Zend_Registry::get('dbAdapter')->quoteInto('pj_id = ?', $id));

        //error_log($select->__toString());
       
        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        if(empty($row)) {
            throw new UnderFlowExecption('project not found');
        }
        
        //error_log(print_r($row, true));
        
        $project = new Crm_Model_Project($row);
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($project->pj_owner, Egwbase_Container::GRANT_READ)) {
            throw new Exception('permission to project denied');
        }
        
        return $project;

/*        $result = $this->leadTable->fetchRow($where);

        if($result === NULL) {
            throw new UnderFlowExecption('project not found');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($result->pj_owner, Egwbase_Container::GRANT_READ)) {
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
            $this->leadTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds)
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
            $this->leadTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds)
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
        
/*        if($projectData['pj_start'] instanceof Zend_Date) {
            $projectData['pj_start'] = $projectData['pj_start']->get(Zend_Date::TIMESTAMP);
        }

        if($projectData['pj_end'] instanceof Zend_Date) {
            $projectData['pj_end'] = $projectData['pj_end']->get(Zend_Date::TIMESTAMP);
        } else {
            $projectData['pj_end'] = null;
        }
        
        if($projectData['pj_end_scheduled'] instanceof Zend_Date) {
            $projectData['pj_end_scheduled'] = $projectData['pj_end_scheduled']->get(Zend_Date::TIMESTAMP);
        } else {
            $projectData['pj_end_scheduled'] = null;
        } */
        
        //error_log(print_r($projectData, true));

        if(empty($projectData['pj_owner'])) {
            throw new UnderflowException('pj_owner can not be empty');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($projectData['pj_owner'], Egwbase_Container::GRANT_EDIT)) {
            throw new Exception('write access to project denied');
        }

        //$currentAccount = Zend_Registry::get('currentAccount');

        //if(empty($projectData['pj_owner'])) {
        //    $projectData['pj_owner'] = $currentAccount->account_id;
        //}

        if($projectData['pj_id'] === NULL) {
            $result = $this->leadTable->insert($projectData);
            $_projectData->pj_id = $this->leadTable->getAdapter()->lastInsertId();
        } else {      
            $where  = array(
                $this->leadTable->getAdapter()->quoteInto('pj_id = (?)', $projectData['pj_id']),
            );

            $result = $this->leadTable->update($projectData, $where);
        }

        return $_projectData;
    }

    /**
     * delete project identified by pj_id
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

        if(!Zend_Registry::get('currentAccount')->hasGrant($oldProjectData->pj_owner, Egwbase_Container::GRANT_DELETE)) {
            throw new Exception('delete access to CRM denied');
        }
       
        $where  = array(
            $this->leadTable->getAdapter()->quoteInto('pj_id = ?', $projectId),
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
            $this->leadTable->getAdapter()->quoteInto('pj_owner = ?', (int)$_folderId)
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
            $where[] = $this->leadTable->getAdapter()->quoteInto('pj_start >= ? ', $_datenFrom);
        }

        if(is_numeric($_datenTo)) {    
            $where[] = $this->leadTable->getAdapter()->quoteInto('pj_end <= ? ', $_datenTo);
        }
*/

		if( is_numeric($_leadstate) && ($_leadstate > 0) ) {
			$where[] = $this->leadTable->getAdapter()->quoteInto('project.pj_leadstate_id = ?', $_leadstate);
		}
		
		if( is_numeric($_probability) && ($_probability > 0) ) {
			$where[] = $this->leadTable->getAdapter()->quoteInto('pj_probability >= ?', $_probability);
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
        //error_log($select->__toString());
       
        $stmt = $db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $projects = new Egwbase_Record_RecordSet($rows, 'Crm_Model_Project');
        
        return $projects;
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
            'pj_id',
            'pj_name',
            'pj_leadstate_id',
            'pj_leadtype_id',
            'pj_leadsource_id',
            'pj_owner',
            'pj_start',
            'pj_description',
            'pj_end',
            'pj_turnover',
            'pj_probability',
            'pj_end_scheduled')
        )
        ->join(array('state' => SQL_TABLE_PREFIX . 'metacrm_leadstate'), 
                'project.pj_leadstate_id = state.pj_leadstate_id');
        
        return $select;
    }

    protected function _addQuickSearchFilter($_where, $_filter)
    {
        if(!empty($_filter)) {
            $search_values = explode(" ", $_filter);
            
            foreach($search_values AS $search_value) {
                $_where[] = $this->leadTable->getAdapter()->quoteInto('(pj_name LIKE ? OR pj_description LIKE ?)', '%' . $search_value . '%');                            
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
            Zend_Registry::get('dbAdapter')->quoteInto('pj_owner IN (?)', $containerIds)
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
            $this->leadTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds)
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
            $this->leadTable->getAdapter()->quoteInto('pj_owner = ?', $folderId)
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
            $this->leadTable->getAdapter()->quoteInto('pj_owner = ?', $folderId)
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
            $this->leadTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds)
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
            $this->leadTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds)
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
