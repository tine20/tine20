<?php
/**
 * backend class for Egwbase_Http_Server
 *
 * This class handles all Http requests for the addressbook application
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Addressbook_Http
{
	public function editContact($_contactId)
	{
        if(empty($_contactId)) {
            $_contactId = NULL;
        }
	    error_log("CONTACTID:: $_contactId");
	    $locale = Zend_Registry::get('locale');
		
		$view = new Zend_View();
		 
		$view->setScriptPath('Egwbase/views');
		$view->formData = array();

		$list = $locale->getTranslationList('Dateformat');
		$view->formData['config']['dateFormat'] = str_replace(array('dd', 'MMMM', 'MMM','MM','yyyy','yy'), array('d','F','M','m','Y','y'), $list['long']);
		
		$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-'.$locale->getLanguage().'-min.js');
		$view->cssIncludeFiles = array();
		
		$className = "Addressbook_Json";			
		$application = new $className;		
		$view->application = $application->getInitialTree('selectFolder');

		$currentAccount = Zend_Registry::get('currentAccount');
		$egwbaseAcl = Egwbase_Acl::getInstance();

		$acl = $egwbaseAcl->getGrantors($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ);

		foreach($acl as $value) {
			$view->formData['config']['addressbooks'][] = array($value, $value);
		}

		$addresses = Addressbook_Backend::factory(Addressbook_Backend::SQL);
		if($_contactId !== NULL && $contact = $addresses->getContactById($_contactId)) {
			$view->formData['values'] = $contact->toArray();
		}
		
		$view->jsIncludeFiles[] = 'Addressbook/Js/Addressbook.js';
		$view->cssIncludeFiles[] = 'Addressbook/css/Addressbook.css';
		$view->jsExecute = 'Egw.Addressbook.ContactEditDialog.display();';

		header('Content-Type: text/html; charset=utf-8');
		echo $view->render('popup.php');
	}

	public function editList($_listId)
	{
        if(empty($_listId)) {
            $_listId = NULL;
        }
        
	    $locale = Zend_Registry::get('locale');
		
		$view = new Zend_View();
		 
		$view->setScriptPath('Egwbase/views');
		$view->formData = array();
		
		$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-'.$locale->getLanguage().'-min.js');
		$view->cssIncludeFiles = array();
		
		$className = "Addressbook_Json";			
		$application = new $className;		
		$view->application = $application->getInitialTree('selectFolder');
		
		$currentAccount = Zend_Registry::get('currentAccount');
		$egwbaseAcl = Egwbase_Acl::getInstance();

		// the list of available addressbooks
		$acl = $egwbaseAcl->getGrantors($currentAccount->account_id, 'addressbook', Egwbase_Acl::READ);

		foreach($acl as $value) {
			$view->formData['config']['addressbooks'][] = array($value, $value);
		}
		
		// get the list
		$addresses = Addressbook_Backend::factory(Addressbook_Backend::SQL);
		if($_listId && $list = $addresses->getListById((int)$_listId)) {
			$view->formData['values']['list_id'] = $_REQUEST['contactid'];
			$view->formData['values']['list_name'] = $list->list_name;
			$view->formData['values']['list_description'] = $list->list_description;
			$view->formData['values']['list_owner'] = $list->list_owner;
			foreach($list->list_members as $member) {
				$view->formData['values']['list_members'][] = array($member->contact_id, $member->n_family, $member->contact_email);
			} 
		} else {
			$view->formData['values']['list_owner'] = $currentAccount->account_id;
		}
		
		$view->jsIncludeFiles[] = 'Addressbook/Js/Addressbook.js';
		$view->cssIncludeFiles[] = 'Addressbook/css/Addressbook.css';
		$view->jsExecute = 'Egw.Addressbook.ListEditDialog.display();';

		header('Content-Type: text/html; charset=utf-8');
		echo $view->render('popup.php');
	}
}