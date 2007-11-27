<?php
/**
 * backend class for Egwbase_Http_Server
 *
 * This class handles all Http requests for the Crm application
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Http.php 135 2007-09-26 13:37:11Z twadewitz $
 *
 */
class Crm_Http extends Egwbase_Application_Http_Abstract
{
    protected $_appname = 'Crm';
    
    public function getJsFilesToInclude()
    {
        return array_merge( parent::getJsFilesToInclude(), array(
            //self::_appendFileTime("{$this->_appname}/js/Crm.js")
        ));
    }

    public function getCssFilesToInclude()
    {
        
    }

    public function getInitialMainScreenData()
    {
      //  return array('initialTree' => Crm_Json::getInitialTree());
    }    
    
    
	public function editProject($_projectId)
	{
         if(empty($_projectId)) {
            $_projectId = NULL;
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
		
		$projects = Crm_Backend::factory(Crm_Backend::SQL);
		if($_projectId !== NULL && $project = $projects->getProjectById($_projectId)) {
			$view->formData['values'] = $project->toArray();
			
		} else {
		    
		}
		
		$view->jsIncludeFiles[] = 'Crm/js/Crm.js';
		$view->cssIncludeFiles[] = 'Crm/css/Crm.css';
		$view->jsExecute = 'Egw.Crm.ProjectEditDialog.display();';

		header('Content-Type: text/html; charset=utf-8');
		echo $view->render('popup.php');
	}

}