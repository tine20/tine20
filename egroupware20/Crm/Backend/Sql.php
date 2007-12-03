<?php
/**
 * the class needed to access the projects table
 *
 * @see Crm_Backend_Sql_Projects
 */
require_once 'Crm/Backend/Sql/Projects.php';

/**
 * the class needed to access the leadsoure table
 *
 * @see Crm_Backend_Sql_Leadsources
 */
require_once 'Crm/Backend/Sql/Leadsources.php';

/**
 * the class needed to access the leadtypes table
 *
 * @see Crm_Backend_Sql_Leadtypes
 */
require_once 'Crm/Backend/Sql/Leadtypes.php';

/**
 * the class needed to access the products source table
 *
 * @see Crm_Backend_Sql_Productsource
 */
require_once 'Crm/Backend/Sql/Productsource.php';

/**
 * the class needed to access the products table
 *
 * @see Crm_Backend_Sql_Products
 */
require_once 'Crm/Backend/Sql/Products.php';

/**
 * the class needed to access the projectstates table
 *
 * @see Crm_Backend_Sql_Projectstates
 */
require_once 'Crm/Backend/Sql/Projectstates.php';



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
        $this->projectsTable      = new Crm_Backend_Sql_Projects();
        $this->leadsourcesTable   = new Crm_Backend_Sql_Leadsources();
        $this->leadtypesTable     = new Crm_Backend_Sql_Leadtypes();
        $this->productsourceTable = new Crm_Backend_Sql_Productsource();
        $this->projectstatesTable = new Crm_Backend_Sql_Projectstates();
        $this->productsTable      = new Crm_Backend_Sql_Products();
    }

	
	/**
	* get Leadsources
	*
	* @return unknown
	*/
    public function getLeadsources()
    {	
		$result = $this->leadsourcesTable->fetchAll();
        return $result;
	}

	/**
	* get Leadtypes
	*
	* @return unknown
	*/
    public function getLeadtypes()
    {	
		$result = $this->leadtypesTable->fetchAll();
        return $result;
	}	
    
	/**
	* get Products
	*
	* @return unknown
	*/
    public function getProductsource()
    {	
		$result = $this->productsourceTable->fetchAll();
        return $result;
	}    
    
	/**
	* get Projectstates
	*
	* @return unknown
	*/
    public function getProjectstates()
    {	
    	$result = $this->projectstatesTable->fetchAll();
   
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
    public function saveProject(Crm_Project $_projectData)
    {
        $currentAccount = Zend_Registry::get('currentAccount');

        $projectData = $_projectData->toArray();

        foreach($projectData AS $atom) {
            $line .= $atom.' |';
        }
        

        
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
        ->join('egw_metacrm_productsource','egw_metacrm_productsource.product_id = egw_metacrm_product.pj_product_id')
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
        
        $currentAccount = Zend_Registry::get('currentAccount');
        
        if ($_owner == 'currentuser') {
            $_owner = $currentAccount->account_id;
        }
          
        $allContainer = Egwbase_Container::getInstance()->getContainerByACL('crm', Egwbase_Container::GRANT_READ);
    
        $containerIds = array();
    
        foreach($allContainer as $container) {
            $containerIds[] = $container->container_id;
        }

        $where = $this->projectsTable->getAdapter()->quoteInto('pj_owner IN (?)', $containerIds);

 
        $db = Zend_Registry::get('dbAdapter');

        $select = $db->select()
        ->from('egw_metacrm_project')
        ->order($_sort . ' ' . $_dir)
        ->join('egw_metacrm_leadsource','egw_metacrm_leadsource.pj_leadsource_id = egw_metacrm_project.pj_leadsource_id')
        ->join('egw_metacrm_leadtype','egw_metacrm_leadtype.pj_leadtype_id = egw_metacrm_project.pj_customertype_id')
        ->join('egw_metacrm_projectstate','egw_metacrm_projectstate.pj_projectstate_id = egw_metacrm_project.pj_distributionphase_id')
//        ->where($where)
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
      
 /*       if(!Egwbase_Container::getInstance()->hasGrant($result->pj_owner, Egwbase_Container::GRANT_READ)) {
            throw new Exception('permission to contact denied');
        }      
 */       
        return $result;
    }
    
}
