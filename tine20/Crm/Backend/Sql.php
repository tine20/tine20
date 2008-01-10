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
    protected $projectsTable;

	/**
	* Instance of Crm_Backend_Sql_Leadsources
	*
	* @var Crm_Backend_Sql_Leadsources
	*/
    protected $leadsourcesTable;

	/**
	* Instance of Crm_Backend_Sql_Leadtypes
	*
	* @var Crm_Backend_Sql_Leadtypes
	*/
    protected $leadtypesTable;
    
	/**
	* Instance of Crm_Backend_Sql_Products
	*
	* @var Crm_Backend_Sql_Products
	*/
    protected $productsourceTable;
    
	/**
	* Instance of Crm_Backend_Sql_Leadstates
	*
	* @var Crm_Backend_Sql_Leadstates
	*/
    protected $leadstatesTable;    
        
    
	/**
	* the constructor
	*
	*/
    public function __construct()
    {
        $this->projectsTable      = new Egwbase_Db_Table(array('name' => 'egw_metacrm_project'));
        $this->leadsourcesTable   = new Egwbase_Db_Table(array('name' => 'egw_metacrm_leadsource'));
        $this->leadtypesTable     = new Egwbase_Db_Table(array('name' => 'egw_metacrm_leadtype'));
        try {
            $this->productsourceTable = new Egwbase_Db_Table(array('name' => 'egw_metacrm_productsource'));
        } catch (Zend_Db_Statement_Exception $e) {
            // temporary hack, until setup is available
            $this->createProductSourceTable();
        }

        $this->leadstatesTable    = new Egwbase_Db_Table(array('name' => 'egw_metacrm_leadstate'));
        $this->productsTable      = new Egwbase_Db_Table(array('name' => 'egw_metacrm_product'));
    }

   /**
     * temporary function to create the egw_metacrm_productsource table on demand
     *
     */
    protected function createProductSourceTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable('egw_metacrm_productsource');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `egw_metacrm_productsource` (
                `pj_productsource_id` int(10) unsigned NOT NULL auto_increment,
                `pj_productsource` varchar(200) NOT NULL default '',
                PRIMARY KEY  (`pj_productsource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            );
        }
    }
        
    
    
	// handle LEADSOURCES
	/**
	* get Leadsources
	*
	* @return unknown
	*/
    public function getLeadsources($sort, $dir)
    {	
		$result = $this->leadsourcesTable->fetchAll(NULL, $sort, $dir);
        return $result;
	}

    /**
    * add or updates an option
    *
    * @param Crm_Leadsource $_optionData the optiondata
    * @return unknown
    */
    public function saveLeadsources(Egwbase_Record_Recordset $_optionData)
    {
        // transaction start
        // delete all
        // datentype(Crm_Model_Leadsource) checken und schreiben 
        // wenn fehler rollback
        // transaction commit

        $_daten = $_optionData->toArray();
    

        $db = Zend_Registry::get('dbAdapter');
  
        $db->beginTransaction();
        
        try {
            $db->delete('egw_metacrm_leadsource');

            foreach($_daten as $_data) {
                $db->insert('egw_metacrm_leadsource', $_data);                
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
    public function deleteLeadsourceById($_Id)
    {
        $Id = (int)$_Id;
        if($Id != $_Id) {
            throw new InvalidArgumentException('$_Id must be integer');
        }
            $where  = array(
                $this->leadsourcesTable->getAdapter()->quoteInto('pj_leadsource_id = ?', $Id),
            );
             
            $result = $this->leadsourcesTable->delete($where);

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
		$result = $this->leadtypesTable->fetchAll(NULL, $sort, $dir);
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
            $db->delete('egw_metacrm_leadtype');

            foreach($_daten as $_data) {
                $db->insert('egw_metacrm_leadtype', $_data);                
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
                $this->leadtypesTable->getAdapter()->quoteInto('pj_leadtype_id = ?', $Id),
            );
             
            $result = $this->leadtypesTable->delete($where);

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
		$result = $this->productsourceTable->fetchAll(NULL, $sort, $dir);
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
            $db->delete('egw_metacrm_productsource');

            foreach($_daten as $_data) {
                $db->insert('egw_metacrm_productsource', $_data);                
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
                $this->productsourceTable->getAdapter()->quoteInto('pj_productsource_id = ?', $Id),
            );
             
            $result = $this->productsourceTable->delete($where);

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
    	$result = $this->leadstatesTable->fetchAll(NULL, $sort, $dir);
   
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
            $db->delete('egw_metacrm_leadstate');

            foreach($_daten as $_data) {
                $db->insert('egw_metacrm_leadstate', $_data);                
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
                $this->leadstatesTable->getAdapter()->quoteInto('pj_leadstate_id = ?', $Id),
            );
             
            $result = $this->leadstatesTable->delete($where);

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
            $db->delete('egw_metacrm_product', 'pj_project_id = '.$project_id);

            foreach($_daten as $_data) {
                $db->insert('egw_metacrm_product', $_data);                
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

        $accountId = Zend_Registry::get('currentAccount')->account_id;

        $where  = array(
            $this->projectsTable->getAdapter()->quoteInto('pj_id = ?', $_id)
        );

        $result = $this->projectsTable->fetchRow($where);

        if($result === NULL) {
            throw new UnderFlowExecption('project not found');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($result->pj_owner, Egwbase_Container::GRANT_READ)) {
            throw new Exception('permission to project denied');
        }
        
        return $result;
    }    
    
    public function getProjectsByOwner($_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
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
            $this->projectsTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds)
        );


        $result = $this->_getProjectsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start);
         
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
            $this->projectsTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds)
        );
        
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->projectsTable->getTotalCount($where);

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
    public function saveProject(Egwbase_Record_Recordset $_projectData)
    {
        $projectData = $_projectData->toArray();
        $projectData = $projectData[0];
    

        if(empty($projectData['pj_owner'])) {
            throw new UnderflowException('pj_owner can not be empty');
        }
        
        if(!Zend_Registry::get('currentAccount')->hasGrant($projectData['pj_owner'], Egwbase_Container::GRANT_EDIT)) {
            throw new Exception('write access to project denied');
        }

        $currentAccount = Zend_Registry::get('currentAccount');

        if(empty($projectData['pj_owner'])) {
            $projectData['pj_owner'] = $currentAccount->account_id;
        }

        if($projectData['pj_id'] === NULL) {
            $result = $this->projectsTable->insert($projectData);
            $_projectData->pj_id = $this->projectsTable->getAdapter()->lastInsertId();
        } else {      
            $where  = array(
                $this->projectsTable->getAdapter()->quoteInto('pj_id = (?)', $projectData['pj_id']),
            );

            $result = $this->projectsTable->update($projectData, $where);
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
            $this->projectsTable->getAdapter()->quoteInto('pj_id = ?', $projectId),
        );

        $result = $this->projectsTable->delete($where);

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
            $this->projectsTable->getAdapter()->quoteInto('pj_owner = ?', (int)$_folderId)
        );
        
        $this->projectsTable->delete($where);
        
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

        $result = new Egwbase_Record_RecordSet($accountData, 'Egwbase_Record_Account');
        
        return $result;
    }


    //handle for FOLDER->PROJECTS functions
    protected function _getProjectsFromTable(array $_where, $_filter, $_sort, $_dir, $_limit, $_start) //, $_datenFrom, $_dateTo)
    {
        $where = $this->_addQuickSearchFilter($_where, $_filter);
/*
        if((int)$_datenFrom) {    
            $where[] = $this->projectsTable->getAdapter()->quoteInto('pj_start >= ? ', $_datenFrom);
        }

        if((int)$_datenTo) {    
            $where[] = $this->projectsTable->getAdapter()->quoteInto('pj_end <= ? ', $_datenTo);
        }
*/
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
        ->from(array('project' => 'egw_metacrm_project'))
        ->join(array('state' => 'egw_metacrm_leadstate'), 
                'project.pj_leadstate_id = state.pj_leadstate_id')
        ->order($_sort.' '.$_dir)
        ->limit($_limit, $_start);

        if(is_array($where)) {
             foreach($where as $_where) {
                  $select->where($_where);
             }               
        }
        //error_log($select->__toString());
       
        $stmt = $db->query($select);
        //$result = array();
        $result = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        return $result;
    }   

    protected function _addQuickSearchFilter($_where, $_filter)
    {
        if(!empty($_filter)) {
            $_where[] = $this->projectsTable->getAdapter()->quoteInto('(pj_name LIKE ? OR pj_description LIKE ?)', '%' . $_filter . '%');
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
     * @return unknown The row results per the Zend_Db_Adapter fetch mode.
     */
    public function getAllProjects($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL, $_dateFrom = NULL, $_dateTo = NULL)
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
            $this->projectsTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds)
        );

        $result = $this->_getProjectsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start, $_dateFrom, $_dateTo);
         
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
            $this->projectsTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds)
        );
        
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->projectsTable->getTotalCount($where);

        return $result;
    }
   
   
    public function getProjectsByFolderId($_folderId, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
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
            $this->projectsTable->getAdapter()->quoteInto('pj_owner = ?', $folderId)
        );

        $result = $this->_getProjectsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start);
         
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
            $this->projectsTable->getAdapter()->quoteInto('pj_owner = ?', $folderId)
        );
                
        $where = $this->_addQuickSearchFilter($where, $_filter);
        
        $result = $this->projectsTable->getTotalCount($where);

        return $result;
    } 

    
    public function getSharedProjects($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL) 
    {
        $sharedContainer = Egwbase_Container::getInstance()->getSharedContainer('crm');
        
        $containerIds = array();
        
        foreach($sharedContainer as $container) {
            $containerIds[] = $container->container_id;
        }
        
        $where = array(
            $this->projectsTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds)
        );

        $result = $this->_getProjectsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start);
         
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

        $result = $this->projectsTable->getCountByAcl($groupIds);

        return $result;
    }        
 
   
   public function getOtherPeopleProjects($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL) 
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
            $this->projectsTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds)
        );

        $result = $this->_getProjectsFromTable($where, $_filter, $_sort, $_dir, $_limit, $_start);
         
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

        $result = $this->projectsTable->getCountByAcl($groupIds);

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
