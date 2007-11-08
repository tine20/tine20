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
class Addressbook_Http extends Egwbase_Application_Http_Abstract
{
    protected $_appname = 'Addressbook';
    
	public function editContact($_contactId)
	{
        if(empty($_contactId)) {
            $_contactId = NULL;
        }
	    
	    $locale = Zend_Registry::get('locale');
		$currentAccount = Zend_Registry::get('currentAccount');
	    
		$view = new Zend_View();
		 
		$view->setScriptPath('Egwbase/views');
		$view->formData = array();

		$list = $locale->getTranslationList('Dateformat');
		$view->formData['config']['dateFormat'] = str_replace(array('dd', 'MMMM', 'MMM','MM','yyyy','yy'), array('d','F','M','m','Y','y'), $list['long']);

		$addressbookJson = new Addressbook_Json;		
		$view->formData['config']['initialTree'] = $addressbookJson->getSelectFolderTree();

		$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-'.$locale->getLanguage().'.js');
		$view->cssIncludeFiles = array();
		
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
		
		$view->jsIncludeFiles[] = 'Addressbook/js/Addressbook.js';
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
		$currentAccount = Zend_Registry::get('currentAccount');
	    
		$view = new Zend_View();
		 
		$view->setScriptPath('Egwbase/views');
		$view->formData = array();
		
		$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-'.$locale->getLanguage().'.js');
		$view->cssIncludeFiles = array();
		
		$addressbookJson = new Addressbook_Json;		
		$view->formData['config']['initialTree'] = $addressbookJson->getSelectFolderTree();
		
		// get the list
		$addresses = Addressbook_Backend::factory(Addressbook_Backend::SQL);
		if($_listId && $list = $addresses->getListById($_listId)) {
			$view->formData['values']['list_id'] = $list->list_id;
			$view->formData['values']['list_name'] = $list->list_name;
			$view->formData['values']['list_description'] = $list->list_description;
			$view->formData['values']['list_owner'] = $list->list_owner;
			foreach($list->list_members as $member) {
				$view->formData['values']['list_members'][] = array($member->contact_id, $member->n_family, $member->contact_email);
			} 

			if($list->list_owner == $currentAccount->account_id) {
			    $view->formData['config']['addressbookName'] = 'My Lists';
			} else {
			    if($list->list_owner > 0) {
			        $view->formData['config']['addressbookName'] = 'Account ' . $list->list_owner;
			    } else {
			        $view->formData['config']['addressbookName'] = 'Group ' . $list->list_owner;
			    }
			}
		} else {
		    $view->formData['values'] = array('list_owner' => $currentAccount->account_id);
		    $view->formData['config']['addressbookName'] = 'My Lists';
		}
		
		$view->jsIncludeFiles[] = 'Addressbook/js/Addressbook.js';
		$view->cssIncludeFiles[] = 'Addressbook/css/Addressbook.css';
		$view->jsExecute = 'Egw.Addressbook.ListEditDialog.display();';

		header('Content-Type: text/html; charset=utf-8');
		echo $view->render('popup.php');
	}
}