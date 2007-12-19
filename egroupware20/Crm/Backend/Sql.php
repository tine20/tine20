<?php

/**
 * interface for projects class
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Sql.php 221 2007-11-12 12:35:08Z twadewitz  $
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
	* Instance of Crm_Backend_Sql_Projectstates
	*
	* @var Crm_Backend_Sql_Projectstates
	*/
    protected $projectstatesTable;    
        
    
	/**
	* the constructor
	*
	*/
    public function __construct()
    {
        $this->projectsTable      = new Egwbase_Db_Table(array('name' => 'egw_metacrm_project'));
        $this->leadsourcesTable   = new Egwbase_Db_Table(array('name' => 'egw_metacrm_leadsource'));
        $this->leadtypesTable     = new Egwbase_Db_Table(array('name' => 'egw_metacrm_leadtype'));
        $this->productsourceTable = new Egwbase_Db_Table(array('name' => 'egw_metacrm_productsource'));
        $this->projectstatesTable = new Egwbase_Db_Table(array('name' => 'egw_metacrm_projectstate'));
        $this->productsTable      = new Egwbase_Db_Table(array('name' => 'egw_metacrm_product'));
    }

	
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
	* get Projectstates
	*
	* @return unknown
	*/
    public function getProjectstates($sort, $dir)
    {	
    	$result = $this->projectstatesTable->fetchAll(NULL, $sort, $dir);
   
        return $result;
	}    
  

	/**
	* add or updates an option
	*
	* @param Crm_Leadsource $_optionData the optiondata
	* @return unknown
	*/
    public function saveLeadsource(Crm_Leadsource $_optionData)
    {
        $optionData = $_optionData->toArray();

        if($_optionData->pj_leadsource_id === NULL) {        
            $result = $this->leadsourcesTable->insert($optionData);
            $_optionData->pj_leadsource_id = $this->leadsourcesTable->getAdapter()->lastInsertId();
        } else {
            //$acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'crm', Egwbase_Acl::EDIT);

            // update the requested pj_id only if the pj_owner matches the current users acl
            $where  = array(
                $this->leadsourcesTable->getAdapter()->quoteInto('pj_leadsource_id = (?)', $_optionData->pj_leadsource_id),
            );
            $result = $this->leadsourcesTable->update($optionData, $where);
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
    
    //        $oldContactData = $this->getContactById($_contactId);
    
    //        if(!Zend_Registry::get('currentAccount')->hasGrant($oldContactData->contact_owner, Egwbase_Container::GRANT_DELETE)) {
    //            throw new Exception('delete access to addressbook denied');
    //        }
            
            $where  = array(
                $this->leadsourcesTable->getAdapter()->quoteInto('pj_leadsource_id = ?', $Id),
            );
             
            $result = $this->leadsourcesTable->delete($where);

        return $result;
    }


	/**
	* add or updates an option
	*
	* @param Crm_Leadtype $_optionData the optiondata
	* @return unknown
	*/
    public function saveLeadtype(Crm_Leadtype $_optionData)
    {
        $optionData = $_optionData->toArray();

        if($_optionData->pj_leadtype_id === NULL) {        
            $result = $this->leadtypesTable->insert($optionData);
            $_optionData->pj_leadtype_id = $this->leadtypesTable->getAdapter()->lastInsertId();
        } else {
            //$acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'crm', Egwbase_Acl::EDIT);

            // update the requested pj_id only if the pj_owner matches the current users acl
            $where  = array(
                $this->leadtypesTable->getAdapter()->quoteInto('pj_leadtype_id = (?)', $_optionData->pj_leadtype_id),
            );
            $result = $this->leadtypesTable->update($optionData, $where);
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
    
    //        $oldContactData = $this->getContactById($_contactId);
    
    //        if(!Zend_Registry::get('currentAccount')->hasGrant($oldContactData->contact_owner, Egwbase_Container::GRANT_DELETE)) {
    //            throw new Exception('delete access to addressbook denied');
    //        }
            
            $where  = array(
                $this->leadtypesTable->getAdapter()->quoteInto('pj_leadtype_id = ?', $Id),
            );
             
            $result = $this->leadtypesTable->delete($where);

        return $result;
    }


	/**
	* add or updates an option
	*
	* @param Crm_Productsource $_optionData the optiondata
	* @return unknown
	*/
    public function saveProductsource(Crm_Productsource $_optionData)
    {
        $optionData = $_optionData->toArray();

        if($_optionData->pj_productsource_id === NULL) {        
            $result = $this->productsourceTable->insert($optionData);
            $_optionData->pj_productsource_id = $this->productsourceTable->getAdapter()->lastInsertId();
        } else {
            //$acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'crm', Egwbase_Acl::EDIT);

            // update the requested pj_id only if the pj_owner matches the current users acl
            $where  = array(
                $this->productsourceTable->getAdapter()->quoteInto('pj_productsource_id = (?)', $_optionData->pj_productsource_id),
            );
            $result = $this->productsourceTable->update($optionData, $where);
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
    
    //        $oldContactData = $this->getContactById($_contactId);
    
    //        if(!Zend_Registry::get('currentAccount')->hasGrant($oldContactData->contact_owner, Egwbase_Container::GRANT_DELETE)) {
    //            throw new Exception('delete access to addressbook denied');
    //        }
            
            $where  = array(
                $this->productsourceTable->getAdapter()->quoteInto('pj_productsource_id = ?', $Id),
            );
             
            $result = $this->productsourceTable->delete($where);

        return $result;
    }


	/**
	* add or updates an option
	*
	* @param Crm_Projectstate $_optionData the optiondata
	* @return unknown
	*/
    public function saveProjectstate(Crm_Projectstate $_optionData)
    {
        $optionData = $_optionData->toArray();

        if($_optionData->pj_projectstate_id === NULL) {        
            $result = $this->projectstatesTable->insert($optionData);
            $_optionData->pj_projectstate_id = $this->projectstatesTable->getAdapter()->lastInsertId();
        } else {
            //$acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'crm', Egwbase_Acl::EDIT);

            // update the requested pj_id only if the pj_owner matches the current users acl
            $where  = array(
                $this->projectstatesTable->getAdapter()->quoteInto('pj_projectstate_id = (?)', $_optionData->pj_projectstate_id),
            );
            $result = $this->projectstatesTable->update($optionData, $where);
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
    public function deleteProjectstateById($_Id)
    {
        $Id = (int)$_Id;
        if($Id != $_Id) {
            throw new InvalidArgumentException('$_Id must be integer');
        }
    
    //        $oldContactData = $this->getContactById($_contactId);
    
    //        if(!Zend_Registry::get('currentAccount')->hasGrant($oldContactData->contact_owner, Egwbase_Container::GRANT_DELETE)) {
    //            throw new Exception('delete access to addressbook denied');
    //        }
            
            $where  = array(
                $this->projectstatesTable->getAdapter()->quoteInto('pj_projectstate_id = ?', $Id),
            );
             
            $result = $this->projectstatesTable->delete($where);

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
    public function saveProduct(Crm_Product $_productData)
    {
  //      $currentAccount = Zend_Registry::get('currentAccount');

        $productData = $_productData->toArray();
      
//        unset($productData['pj_project_id']);

        if($_productData->pj_id === NULL) {
            $result = $this->productsTable->insert($productData);
            $_productData->pj_id = $this->productsTable->getAdapter()->lastInsertId();
        } else {
            //$acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'crm', Egwbase_Acl::EDIT);

            // update the requested pj_id only if the pj_owner matches the current users acl
            $where  = array(
                $this->productsTable->getAdapter()->quoteInto('pj_id = (?)', $_productData->pj_id),
              //  $this->projectsTable->getAdapter()->quoteInto('pj_owner IN (?)', array_keys($acl))
            );

            $result = $this->productsTable->update($productData, $where);
        }

        return $_productData;
    }


	/**
	* add or updates an project
	*
	* @param int $_projectOwner the owner of the Crm entry
	* @param Crm_Project $_projectData the projectdata
	* @param int $_projectId the project to update, if NULL the project gets added
	* @return unknown
	*/
    public function saveProject(Crm_Project $_projectData)
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $projectData = $_projectData->toArray();


        unset($projectData['pj_id']);
        if(empty($projectData['pj_owner'])) {
            $projectData['pj_owner'] = $currentAccount->account_id;
        }

        if($_projectData->pj_id === NULL) {
            $result = $this->projectsTable->insert($projectData);
            $_projectData->pj_id = $this->projectsTable->getAdapter()->lastInsertId();
        } else {
            //$acl = $this->egwbaseAcl->getGrants($currentAccount->account_id, 'crm', Egwbase_Acl::EDIT);

            // update the requested pj_id only if the pj_owner matches the current users acl
            $where  = array(
                $this->projectsTable->getAdapter()->quoteInto('pj_id = (?)', $_projectData->pj_id),
              //  $this->projectsTable->getAdapter()->quoteInto('pj_owner IN (?)', array_keys($acl))
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
        
/*
        if(!Zend_Registry::get('currentAccount')->hasGrant($oldProjectData->pj_owner, Egwbase_Container::GRANT_DELETE)) {
            throw new Exception('delete access to CRM denied');
        }
   */     
        $where  = array(
            $this->projectsTable->getAdapter()->quoteInto('pj_id = ?', $projectId),
        );

        $result = $this->projectsTable->delete($where);

        return $result;
    }


    /**
     * delete product identified by product id
     *
     * @param int $_contacts contact ids
     * @return int the number of rows deleted
     */
    public function deleteProductById($_productId)
    {
        $productId = (int)$_productId;
        if($productId != $_productId) {
            throw new InvalidArgumentException('$_productId must be integer');
        }

//        $oldContactData = $this->getContactById($_contactId);

//        if(!Zend_Registry::get('currentAccount')->hasGrant($oldContactData->contact_owner, Egwbase_Container::GRANT_DELETE)) {
//            throw new Exception('delete access to addressbook denied');
//        }
        
        $where  = array(
            $this->productsTable->getAdapter()->quoteInto('pj_id = ?', $productId),
        );
         
        $result = $this->productsTable->delete($where);

        return $result;
    }

	/**
	* get all products which belong to one project
	* 
	* 
	* 
	* 
	* @return unknown
	*/
     public function getProductsByProjectId($_id)
    {    
        $id = (int)$_id;
        
        if($id != $_id) {
            throw new InvalidArgumentException('$_id must be integer');
        }

        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
        ->from('egw_metacrm_project')
        ->order('egw_metacrm_product. pj_id ASC')    
        ->join('egw_metacrm_product','egw_metacrm_product.pj_project_id = egw_metacrm_project.pj_id')
        ->join('egw_metacrm_productsource','egw_metacrm_productsource.pj_product_id = egw_metacrm_product.pj_product_id')
        ->where('egw_metacrm_project.pj_id = ?', $id);     
    }
    
    
	/**
	* get projects
	*
	* 
	* 
	* 
	* @return unknown
	*/
     public function getProjectsByOwner($_owner, $_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL)
    {
        // convert to int
        $owner = (int)$_owner;
        if($owner != $_owner) {
            throw new InvalidArgumentException('$_owner must be integer');
        }
    
        if(empty($_filter)) {
            $_filter = '%';
        } else {
            $_filter = '%' . $_filter .'%';
        }
        
        $currentAccount = Zend_Registry::get('currentAccount');
        
        if ($_owner == 'currentuser') {
            $_owner = $currentAccount->account_id;
        }
          
        $allContainer = Zend_Registry::get('currentAccount')->getContainerByACL('crm', Egwbase_Container::GRANT_READ);
    
        $containerIds = array();
    
        foreach($allContainer as $container) {
            $containerIds[] = $container->container_id;
        }

        $where = $this->projectsTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds);
        $where_filter = $this->projectsTable->getAdapter()->quoteInto('pj_name LIKE (?)', $_filter);        

 
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
        ->from('egw_metacrm_project')
        ->order($_sort . ' ' . $_dir)
        ->join('egw_metacrm_leadsource','egw_metacrm_leadsource.pj_leadsource_id = egw_metacrm_project.pj_leadsource_id')
        ->join('egw_metacrm_leadtype','egw_metacrm_leadtype.pj_leadtype_id = egw_metacrm_project.pj_customertype_id')
        ->join('egw_metacrm_projectstate','egw_metacrm_projectstate.pj_projectstate_id = egw_metacrm_project.pj_distributionphase_id')
//        ->where($where)
        ->where($where_filter)
        ->limit($limit, $start);

//        error_log("CRM :: SQL : getProjectsByOwner : " . $select->__toString());

        $stmt = $db->query($select);

        $result = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        return $result;        
      
    }
   
   
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
 /*      
        if(!Zend_Registry::get('currentAccount')->hasGrant($result->pj_owner, Egwbase_Container::GRANT_READ)) {
            throw new Exception('permission to project denied');
        }
*/
        return $result;
    }
    
}
