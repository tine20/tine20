<?php
/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Crm application
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Json.php 199 2007-10-15 16:30:00Z twadewitz $
 *
 */
class Crm_Json extends Egwbase_Application_Json_Abstract
{

    protected $_appname = 'Crm';
  

    /**
	 * save leadsources
	 *
	 * if $_Id is -1 the options element gets added, otherwise it gets updated
	 * this function handles insert and updates as well as deleting vanished items
	 *
	 * @return array
	 */	
	public function saveLeadsources()
    {
        if(strlen($_POST['deletedOptions']) > 2) {
               $_deleted_options = Zend_Json::decode($_POST['deletedOptions']);
               
            if(is_array($_deleted_options)) {
                $backend = Crm_Backend::factory(Crm_Backend::SQL);

                foreach($_deleted_options as $_deleted_option) {
                    $backend->deleteLeadsourceById($_deleted_option);
                }
    
                $result = array('success'   => TRUE, 'ids' => $_deleted_options);
            } else {
                $result = array('success'   => FALSE);
                return $result;
            }
        }

       if(strlen($_POST['optionsData']) > 2) 
       {     
           $_leadsources = Zend_Json::decode($_POST['optionsData']);     

           foreach($_leadsources AS $_leadsource) {
               $options[] = array('pj_leadsource_id' => $_leadsource['key'], 'pj_leadsource' => $_leadsource['value']);    
           }

           if(is_array($options)) {
               	foreach($options AS $_option) {
                    if($_option['pj_leadsource_id'] == "-1") {
						unset($_option['pj_leadsource_id']);
					}
				
					$option = new Crm_Leadsource();
				        try {
				            $option->setFromUserData($_option);
				        } catch (Exception $e) {
				            // invalid data in some fields sent from client
				            $result = array('success'           => false,
				                            'errors'            => $option->getValidationErrors(),
				                            'errorMessage'      => 'filter NOT ok');
				
				            return $result;
				        }

			        $backend = Crm_Backend::factory(Crm_Backend::SQL);
				         
				        try {	            
				            $backend->saveLeadsource($option);
				            $result = array('success'           => true,
				                            'welcomeMessage'    => 'Entry updated');
				        } catch (Exception $e) {			            
				            $result = array('success'           => false,
				        					'errorMessage'      => $e->getMessage());
                            return $result;
				        }
				}
               
           }        
       } else {
            $result = array('success' => false,
                            'errorMessage' => 'nothing to save');
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
	public function saveLeadtypes()
    {
        if(strlen($_POST['deletedOptions']) > 2) {
               $_deleted_options = Zend_Json::decode($_POST['deletedOptions']);
               
            if(is_array($_deleted_options)) {
                $backend = Crm_Backend::factory(Crm_Backend::SQL);

                foreach($_deleted_options as $_deleted_option) {
                    $backend->deleteLeadtypeById($_deleted_option);
                }
    
                $result = array('success'   => TRUE, 'ids' => $_deleted_options);
            } else {
                $result = array('success'   => FALSE);
                return $result;
            }
        }

       if(strlen($_POST['optionsData']) > 2) 
       {     
           $_leadtypes = Zend_Json::decode($_POST['optionsData']);     

           foreach($_leadtypes AS $_leadtype) {
               $options[] = array('pj_leadtype_id' => $_leadtype['key'], 'pj_leadtype' => $_leadtype['value']);    
           }

           if(is_array($options)) {
               	foreach($options AS $_option) {
                    if($_option['pj_leadtype_id'] == "-1") {
						unset($_option['pj_leadtype_id']);
					}
				
					$option = new Crm_Leadtype();
				        try {
				            $option->setFromUserData($_option);
				        } catch (Exception $e) {
				            // invalid data in some fields sent from client
				            $result = array('success'           => false,
				                            'errors'            => $option->getValidationErrors(),
				                            'errorMessage'      => 'filter NOT ok');
				
				            return $result;
				        }

			        $backend = Crm_Backend::factory(Crm_Backend::SQL);
				         
				        try {	            
				            $backend->saveLeadtype($option);
				            $result = array('success'           => true,
				                            'welcomeMessage'    => 'Entry updated');
				        } catch (Exception $e) {			            
				            $result = array('success'           => false,
				        					'errorMessage'      => $e->getMessage());
                            return $result;
				        }
				}
               
           }        
       } else {
            $result = array('success' => false,
                            'errorMessage' => 'nothing to save');
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
	public function saveProjectstates()
    {
        if(strlen($_POST['deletedOptions']) > 2) {
               $_deleted_options = Zend_Json::decode($_POST['deletedOptions']);
               
            if(is_array($_deleted_options)) {
                $backend = Crm_Backend::factory(Crm_Backend::SQL);

                foreach($_deleted_options as $_deleted_option) {
                    $backend->deleteProjectstateById($_deleted_option);
                }
    
                $result = array('success'   => TRUE, 'ids' => $_deleted_options);
            } else {
                $result = array('success'   => FALSE);
                return $result;
            }
        }

       if(strlen($_POST['optionsData']) > 2) 
       {     
           $_projectstates = Zend_Json::decode($_POST['optionsData']);     

           foreach($_projectstates AS $_projectstate) {
               $options[] = array('pj_projectstate_id' => $_projectstate['key'], 'pj_projectstate' => $_projectstate['value']);    
           }

           if(is_array($options)) {
               	foreach($options AS $_option) {
                    if($_option['pj_projectstate_id'] == "-1") {
						unset($_option['pj_projectstate_id']);
					}
				
					$option = new Crm_Projectstate();
				        try {
				            $option->setFromUserData($_option);
				        } catch (Exception $e) {
				            // invalid data in some fields sent from client
				            $result = array('success'           => false,
				                            'errors'            => $option->getValidationErrors(),
				                            'errorMessage'      => 'filter NOT ok');
				
				            return $result;
				        }

			        $backend = Crm_Backend::factory(Crm_Backend::SQL);
				         
				        try {	            
				            $backend->saveProjectstate($option);
				            $result = array('success'           => true,
				                            'welcomeMessage'    => 'Entry updated');
				        } catch (Exception $e) {			            
				            $result = array('success'           => false,
				        					'errorMessage'      => $e->getMessage());
                            return $result;
				        }
				}
               
           }        
       } else {
            $result = array('success' => false,
                            'errorMessage' => 'nothing to save');
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
	public function saveProductsource()
    {
        if(strlen($_POST['deletedOptions']) > 2) {
               $_deleted_options = Zend_Json::decode($_POST['deletedOptions']);
               
            if(is_array($_deleted_options)) {
                $backend = Crm_Backend::factory(Crm_Backend::SQL);

                foreach($_deleted_options as $_deleted_option) {
                    $backend->deleteProductsourceById($_deleted_option);
                }
    
                $result = array('success'   => TRUE, 'ids' => $_deleted_options);
            } else {
                $result = array('success'   => FALSE);
                return $result;
            }
        }

       if(strlen($_POST['optionsData']) > 2) 
       {     
           $_productsources = Zend_Json::decode($_POST['optionsData']);     

           foreach($_productsources AS $_productsource) {
               $options[] = array('pj_productsource_id' => $_productsource['key'], 'pj_productsource' => $_productsource['value']);    
           }

           if(is_array($options)) {
               	foreach($options AS $_option) {
                    if($_option['pj_productsource_id'] == "-1") {
						unset($_option['pj_productsource_id']);
					}
				
					$option = new Crm_Productsource();
				        try {
				            $option->setFromUserData($_option);
				        } catch (Exception $e) {
				            // invalid data in some fields sent from client
				            $result = array('success'           => false,
				                            'errors'            => $option->getValidationErrors(),
				                            'errorMessage'      => 'filter NOT ok');
				
				            return $result;
				        }

			        $backend = Crm_Backend::factory(Crm_Backend::SQL);
				         
				        try {	            
				            $backend->saveProductsource($option);
				            $result = array('success'           => true,
				                            'welcomeMessage'    => 'Entry updated');
				        } catch (Exception $e) {			            
				            $result = array('success'           => false,
				        					'errorMessage'      => $e->getMessage());
                            return $result;
				        }
				}
               
           }        
       } else {
            $result = array('success' => false,
                            'errorMessage' => 'nothing to save');
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
                    if($_product['pj_project_id'] == "-1") {
						$_product['pj_project_id'] = $pj_id;
					}
					
					$product = new Crm_Product();
				        try {
				            $product->setFromUserData($_product);
				        } catch (Exception $e) {
				            // invalid data in some fields sent from client
				            $result = array('success'           => false,
				                            'errors'            => $product->getValidationErrors(),
				                            'errorMessage'      => 'filter NOT ok');
				
				            return $result;
				        }
				
				        $backend = Crm_Backend::factory(Crm_Backend::SQL);
				         
				        try {	            
				            $backend->saveProduct($product);
				            $result = array('success'           => true,
				                            'welcomeMessage'    => 'Entry updated');
				        } catch (Exception $e) {			            
				            $result = array('success'           => false,
				        					'errorMessage'      => $e->getMessage());
				        }
				}
				
			}      
   }


     /**
	 * save one project
	 *
	 * if $_projectId is 0 the project gets added, otherwise it gets updated
	 *
	 * @return array
	 */	
	public function saveProject()
    {
        if(empty($_POST['pj_id'])) {
            unset($_POST['pj_id']);
        }

        // timestamps
        $_changeDate = time();

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
        
        $project = new Crm_Project();
        try {
            $project->setFromUserData($_POST);
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errors'            => $project->getValidationErrors(),
                            'errorMessage'      => 'filter NOT ok');

            return $result;
        }

        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        try {
            $backend->saveProject($project);
            $result = array('success'           => true,
                            'welcomeMessage'    => 'Entry updated');
        } catch (Exception $e) {
            $result = array('success'           => false,
        					'errorMessage'      => $e->getMessage());
        }
        
        
        // products
		if(isset($_POST['products'])) {
            $this->saveProducts($_POST['products'], $project->pj_id);
		}
        
        if(isset($_POST['deletedProducts'])) {
               $_deleted_products = Zend_Json::decode($_POST['deletedProducts']);
               
              if(is_array($_deleted_products)) {
                $backend = Crm_Backend::factory(Crm_Backend::SQL);

                foreach($_deleted_products as $_deleted_product) {
                    $backend->deleteProductById($_deleted_product);
                }
    
                $result = array('success'   => TRUE, 'ids' => $_deleted_products);
            } else {
                $result = array('success'   => FALSE);
            }

            return $result;    
        }        
        

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
            $result['results']    = $rows;
            //$result['totalcount'] = $backend->getCountByOwner($owner);
        }

        return $result;
    }
     
   public function getProjectstates($sort, $dir)
    {
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
         
            
        if($rows = $backend->getProjectstates($sort, $dir)) {
            $result['results']    = $rows->toArray();
//              $result['results']    = $rows;
        }

        return $result;    
    }     
	
   public function getLeadsources($sort, $dir)
    {
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
         
            
        if($rows = $backend->getLeadsources($sort, $dir)) {
            $result['results']    = $rows->toArray();
//              $result['results']    = $rows;
        }

        return $result;    
    }     	
 
   public function getLeadtypes($sort, $dir)
    {
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
         
            
        if($rows = $backend->getLeadtypes($sort, $dir)) {
            $result['results']    = $rows->toArray();
//              $result['results']    = $rows;
        }

        return $result;    
    }   
	
	public function getProductsource($sort, $dir)
	{
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
         
            
        if($rows = $backend->getProductsAvailable($sort, $dir)) {
            $result['results']    = $rows->toArray();
//              $result['results']    = $rows;
        }

        return $result;
	}   
     
     
}