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
class Crm_Http
{

	public function editEvent($_venueId, $_eventId)
	{
        $locale = Zend_Registry::get('locale');
		
		$view = new Zend_View();
		 
		$view->setScriptPath('Egwbase/views');
		$view->formData = array();

		$list = $locale->getTranslationList('Dateformat');
		$view->formData['config']['dateFormat'] = str_replace(array('dd', 'MMMM', 'MMM','MM','yyyy','yy'), array('d','F','M','m','Y','y'), $list['long']);

		$crmJson = new Crm_Json;		
		$view->formData['config']['initialTree'] = $crmJson->getInitialTree('selectFolder');

		$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-'.$locale->getLanguage().'.js');
		$view->cssIncludeFiles = array();
		
		$currentAccount = Zend_Registry::get('currentAccount');
		//$egwbaseAcl = Egwbase_Acl::getInstance();

/*
		$addresses = Addressbook_Backend::factory(Addressbook_Backend::SQL);
		if($_contactId !== NULL && $contact = $addresses->getContactById($_contactId)) {
			$view->formData['values'] = $contact->toArray();
			if($contact->contact_owner == $currentAccount->account_id) {
			    $view->formData['config']['addressbookName'] = 'My Contacts';
			} else {
			    if($contact->contact_owner > 0) {
			        $view->formData['config']['addressbookName'] = 'Account ' . $contact->contact_owner;
			    } else {
			        $view->formData['config']['addressbookName'] = 'Group ' . $contact->contact_owner;
			    }
			}

			$locale = Zend_Registry::get('locale');
			if(!empty($contact->adr_one_countryname)) {
			    $view->formData['config']['oneCountryName'] = $locale->getCountryTranslation($contact->adr_one_countryname);
			}
			if(!empty($contact->adr_two_countryname)) {
			    $view->formData['config']['twoCountryName'] = $locale->getCountryTranslation($contact->adr_one_countryname);
			}
		} else {
		    $view->formData['values'] = array('contact_owner' => $currentAccount->account_id);
		    $view->formData['config']['addressbookName'] = 'My Contacts';
		}
	*/	
		$view->jsIncludeFiles[] = 'Crm/Js/Crm.js';
		$view->cssIncludeFiles[] = 'Crm/css/Crm.css';
		$view->jsExecute = 'Egw.Crm.EventEditDialog.display();';

		header('Content-Type: text/html; charset=utf-8');
		echo $view->render('popup.php');
	}

}