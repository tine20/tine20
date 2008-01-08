<?php
/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Crm application
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Json.php 199 2007-10-15 16:30:00Z twadewitz $
 *
 */
class Crm_Json extends Egwbase_Application_Json_Abstract
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
        
        if($rows = Crm_Controller::getInstance()->getLeadsources($sort, $dir)) {
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
            $leadSources = new Egwbase_Record_RecordSet($leadSources, 'Crm_Model_Leadsource');
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
        
        if($rows = Crm_Controller::getInstance()->getLeadtypes($sort, $dir)) {
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
            $leadTypes = new Egwbase_Record_RecordSet($leadTypes, 'Crm_Model_Leadtype');
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
     * get project states
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */   
   public function getProjectstates($sort, $dir)
    {
         $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Crm_Controller::getInstance()->getProjectstates($sort, $dir)) {
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;   
    }  

    /**
	 * save projectstates
	 *
	 * if $_Id is -1 the options element gets added, otherwise it gets updated
	 * this function handles insert and updates as well as deleting vanished items
	 *
	 * @return array
	 */	
	public function saveProjectstates($optionsData)
    {
        $projectStates = Zend_Json::decode($optionsData);
         
        try {
            $projectStates = new Egwbase_Record_RecordSet($projectStates, 'Crm_Model_Projectstate');
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'filter NOT ok'
            );
            
            return $result;
        }
            
        
        if(Crm_Controller::getInstance()->saveProjectstates($projectStates) === FALSE) {
            $result = array('success'   => FALSE);
        } else {
            $result = array('success'   => TRUE);
        }
        
        return $result;       
    }    
    
 
    /**
     * get product source
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
        
        if($rows = Crm_Controller::getInstance()->getProductsAvailable($sort, $dir)) {
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
            $productSource = new Egwbase_Record_RecordSet($productSource, 'Crm_Model_Productsource');
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
    

// handle PRODUCTS
   public function getProductsById($_id)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );

        if(empty($filter)) {
            $filter = NULL;
        }

        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        if($rows = $backend->getProductsById($_id)) {
            $result['results']    = $rows->toArray();
            //$result['totalcount'] = $backend->getCountByOwner($owner);
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
   public function saveProducts($products, $pj_id) {	
   
        $_products = Zend_Json::decode($products);
       
       	if(is_array($_products)) {

		foreach($_products AS $_product) {
			if($_product['pj_id'] == "-1") {
				unset($_product['pj_id']);
			}
            if($_product['pj_project_id'] == "-1" || empty($_product['pj_project_id'])) {
				$_product['pj_project_id'] = $pj_id;

			}			
            
            $_productsData[] = $_product;
    	}
   
        try {
            $_products = new Egwbase_Record_RecordSet($_productsData, 'Crm_Model_Product');
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'products filter NOT ok'
            );
            
            return $result;
        }
            
        
        if(Crm_Controller::getInstance()->saveProducts($_products) === FALSE) {
            $result = array('success'   => FALSE);
        } else {
            $result = array('success'   => TRUE);
        }
       
        return $result;  
      
       }
   }



     /**
	 * save one project
	 *
	 * if $_projectId is NULL the project gets added, otherwise it gets updated
	 *
	 * @return array
	 */	
	public function saveProject()
    {
        // timestamps
        $_changeDate = time();

        if(empty($_POST['pj_id'])) {
            unset($_POST['pj_id']);
            $_POST['pj_created'] = $_changeDate;
        }



        if(empty($_POST['pj_created'])) {
            $_POST['pj_created'] = $_changeDate;
        }
		
		$_POST['pj_modified'] = $_changeDate;
        
        // project modifier
        $_POST['pj_modifier'] = Zend_Registry::get('currentAccount')->account_id;


        // date transition
		if(isset($_POST['pj_start'])) {
		   // $locale = Zend_Registry::get('locale');
           // $dateFormat = $locale->getTranslationList('Dateformat');
            try {
           //     $date = new Zend_Date($_POST['pj_start'], $dateFormat['long'], 'en');
                $date = new Zend_Date($_POST['pj_start'], 'dd.MM.YYYY');
                $_POST['pj_start'] = $date->toString('U');
            } catch (Exception $e) {
                unset($_POST['pj_start']);
            }
		}
		
		if(isset($_POST['pj_end'])) {
		   // $locale = Zend_Registry::get('locale');
           // $dateFormat = $locale->getTranslationList('Dateformat');
            try {
           //     $date = new Zend_Date($_POST['pj_end'], $dateFormat['long'], 'en');
                $date = new Zend_Date($_POST['pj_end'], 'dd.MM.YYYY');
                $_POST['pj_end'] = $date->toString('U');
            } catch (Exception $e) {
                unset($_POST['pj_end']);
            }			
		}		
        
		if(isset($_POST['pj_end_scheduled'])) {
		   // $locale = Zend_Registry::get('locale');
           // $dateFormat = $locale->getTranslationList('Dateformat');
            try {
           //     $date = new Zend_Date($_POST['pj_end_scheduled'], $dateFormat['long'], 'en');
                $date = new Zend_Date($_POST['pj_end_scheduled'], 'dd.MM.YYYY');
                $_POST['pj_end_scheduled'] = $date->toString('U');
            } catch (Exception $e) {
                unset($_POST['pj_end_scheduled']);
            }						
		}		
  
        // products
		if(isset($_POST['products'])) {
//            $this->saveProducts($_POST['products'], $projectData->pj_id);
        //    $this->saveProducts($_POST['products'], $_POST['pj_id']);
		}          
  
          
        $projectData[] = $_POST;  
          
        try {
            $projectData = new Egwbase_Record_RecordSet($projectData, 'Crm_Model_Project');
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errorMessage'      => 'project filter NOT ok'
            );
            
            return $result;
        }
            
        
        if(Crm_Controller::getInstance()->saveProject($projectData) === FALSE) {
            $result = array('success'   => FALSE);
        } else {
            $result = array('success'   => TRUE);
        }
        
//error_log('JSON :: returned pj_id : '.$projectData->pj_id);        
        
  
        
        
        return $result;  
 
    }      
 
     /**
     * delete a array of projects
     *
     * @param array $_projectIDs
     * @return array
     */
    public function deleteProjects($_projectIds)
    {
        $projectIds = Zend_Json::decode($_projectIds);

        if(is_array($projectIds)) {
            $projects = Crm_Backend::factory(Crm_Backend::SQL);
            foreach($projectIds as $projectId) {
                $projects->deleteProjectById($projectId);
            }

            $result = array('success'   => TRUE, 'ids' => $projectIds);
        } else {
            $result = array('success'   => FALSE);
        }

        return $result;
        
    } 
    
 

     
    public function getProjectsByOwner($filter, $owner, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );

        if(empty($filter)) {
            $filter = NULL;
        }
        
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        if($rows = $backend->getProjectsByOwner($owner, $filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = $backend->getCountByOwner($owner, $filter);
            }
        }

        return $result;
    }
        
     public function getProjectsByFolderId($folderId, $filter, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
                
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        if($rows = $backend->getProjectsByFolderId($folderId, $filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = $backend->getCountByFolderId($folderId, $filter);
            }
        }
        
        return $result;
    }    
 

    /**
     * get data for the overview
     *
     * returns the data to be displayed in a ExtJS grid
     *
     * @todo implement correc total count for lists
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getSharedProjects($filter, $sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
                
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        $rows = $backend->getSharedProjects($filter, $sort, $dir, $limit, $start);
        
        if($rows !== false) {
            $result['results']    = $rows->toArray();
            //$result['totalcount'] = $backend->getCountOfSharedProjects();
        }

        return $result;
    }

    /**
     * get data for the overview
     *
     * returns the data to be displayed in a ExtJS grid
     *
     * @todo implement correc total count for lists
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getOtherPeopleProjects($filter, $sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
                
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        $rows = $backend->getOtherPeopleProjects($filter, $sort, $dir, $limit, $start);
        
        if($rows !== false) {
            $result['results']    = $rows->toArray();
            //$result['totalcount'] = $backend->getCountOfOtherPeopleProjects();
        }

        return $result;
    }
  
 
 
   /**
     * get data for the overview
     *
     * returns the data to be displayed in a ExtJS grid
     *
     * @todo implement correc total count for lists
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getAllProjects($filter, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
                
        $backend = Crm_Backend::factory(Crm_Backend::SQL);

        if($rows = $backend->getAllProjects($filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            $result['totalcount'] = $backend->getCountOfAllProjects($filter);
        }

        return $result;
    } 
     
     
// handle FOLDERS
    public function getFoldersByOwner($owner)
    {
        $treeNodes = array();
        
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        if($rows = $backend->getFoldersByOwner($owner)) {
            foreach($rows as $folderData) {
                $childNode = new Egwbase_Ext_Treenode('Crm', 'projects', 'folder-' . $folderData->container_id, $folderData->container_name, TRUE);
                $childNode->folderId = $folderData->container_id;
                $childNode->nodeType = 'singleFolder';
                $treeNodes[] = $childNode;
            }
        }
        
        echo Zend_Json::encode($treeNodes);

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }    


    public function getSharedFolders()
    {
        $treeNodes = array();
        
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        if($rows = $backend->getSharedFolders()) {
            foreach($rows as $folderData) {
                $childNode = new Egwbase_Ext_Treenode('Crm', 'projects', 'shared-' . $folderData->container_id, $folderData->container_name, TRUE);
                $childNode->folderId = $folderData->container_id;
                $childNode->nodeType = 'singleFolder';
                $treeNodes[] = $childNode;
            }
        }
        
        echo Zend_Json::encode($treeNodes);

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }    

   /**
     * returns a list a accounts who gave current account at least read access to 1 personal folder 
     *
     */
    public function getOtherUsers()
    {
        $treeNodes = array();
        
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        try {
            $rows = $backend->getOtherUsers();
        
            foreach($rows as $accountData) {
                $treeNode = new Egwbase_Ext_Treenode(
                    'Crm',
                    'projects',
                    'otherfolder_'. $accountData->account_id, 
                    $accountData->account_name,
                    false
                );
                $treeNode->owner  = $accountData->account_id;
                $treeNode->nodeType = 'userFolders';
                $treeNodes[] = $treeNode;
            }
        } catch (Exception $e) {
            // do nothing
            // or throw Execption???
        }
        echo Zend_Json::encode($treeNodes);

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }  


    public function getAccounts($filter, $start, $sort, $dir, $limit)
    {
        $internalContainer = Egwbase_Container::getInstance()->getInternalContainer('crm');
        
        $folderId = $internalContainer->container_id;
        
        $result = $this->getProjectsByFolderId($folderId, $filter, $start, $sort, $dir, $limit);

        return $result;
    }


   public function addFolder($name, $type)
    {
        $backend = Crm_Backend::factory(Crm_Backend::SQL);

        $id = $backend->addFolder($name, $type);
        
        $result = array('folderId' => $id);
        
        return $result;
    }
    
    public function deleteFolder($folderId)
    {
        $backend = Crm_Backend::factory(Crm_Backend::SQL);

        $backend->deleteFolder($folderId);
            
        return TRUE;
    }
    
    public function renameFolder($folderId, $name)
    {
        $backend = Crm_Backend::factory(Crm_Backend::SQL);

        $backend->renameFolder($folderId, $name);
            
        return TRUE;
    }     
     
}