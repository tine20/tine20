<?php
/**
 * backend class for Egwbase_Http_Server
 *
 * This class handles all Http requests for the Crm application
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Sql.php 199 2008-01-15 15:10:04Z twadewitz $
 *
 */
class Crm_Http extends Egwbase_Application_Http_Abstract
{
    protected $_appname = 'Crm';
      
    
	public function editLead($_leadId)
	{
         if(empty($_leadId)) {
            $_leadId = NULL;
        }
	    
	    $locale = Zend_Registry::get('locale');
		$currentAccount = Zend_Registry::get('currentAccount');
	    
		$view = new Zend_View();
		 
		$view->setScriptPath('Egwbase/views');
		$view->formData = array();
        
		$list = $locale->getTranslationList('Dateformat');
		$view->formData['config']['dateFormat'] = str_replace(array('dd', 'MMMM', 'MMM','MM','yyyy','yy'), array('d','F','M','m','Y','y'), $list['long']);

		$crmJson = new Crm_Json;		
//		$view->formData['config']['initialTree'] = $eventschedulerJson->getInitialTree('mainTree');

		$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-'.$locale->getLanguage().'.js');
		$view->cssIncludeFiles = array();
		
		$controller = Crm_Controller::getInstance();
		$leads = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
		if($_leadId !== NULL && $lead = $leads->getLeadById($_leadId)) {
			$view->formData['values'] = $lead->toArray();
			$folder = Egwbase_Container::getInstance()->getContainerById($lead->lead_container);
			
            $_products = $leads->getProductsById($_leadId);
            $view->formData['values']['products'] = $_products->toArray();
            
            $_contacts = $leads->getContactsById($_leadId);
            $view->formData['values']['contacts'] = $_contacts->toArray();      
           
			$view->formData['config']['folderName']   = $folder->container_name;
			$view->formData['config']['folderRights'] = $folder->account_grants;
		    
		} else {
            $view->formData['values'] = $controller->getEmptyLead()->toArray();
            $view->formData['values']['products'] = array();                
            $view->formData['values']['contacts'] = array();                       
            
            $personalFolders = $leads->getFoldersByOwner($currentAccount->account_id);
		    foreach($personalFolders as $folder) {
		        $view->formData['values']['lead_container']     = $folder->container_id;
    		    $view->formData['config']['folderName']   = $folder->container_name;
    		    $view->formData['config']['folderRights'] = 31;
                break;
		    }
		    
		}

		$_leadTypes = $leads->getLeadtypes('lead_leadtype','ASC');
		$view->formData['comboData']['leadtypes'] = $_leadTypes->toArray();
		
		$_leadStates =  $leads->getLeadStates('lead_leadstate','ASC');
		$view->formData['comboData']['leadstates'] = $_leadStates->toArray();
		
		$_leadSources =  $leads->getLeadSources('lead_leadsource','ASC');
		$view->formData['comboData']['leadsources'] = $_leadSources->toArray();

		$_productSource =  $leads->getProductsAvailable('lead_productsource','ASC');
		$view->formData['comboData']['productsource'] = $_productSource->toArray();


		$view->jsIncludeFiles[] = 'Crm/js/Crm.js';
		$view->cssIncludeFiles[] = 'Crm/css/Crm.css';
		$view->jsExecute = 'Egw.Crm.LeadEditDialog.display();';

		$view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
		$view->title="edit lead";

		header('Content-Type: text/html; charset=utf-8');
		echo $view->render('popup.php');
	}

}