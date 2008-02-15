<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * backend class for Egwbase_Http_Server
 *
 * This class handles all Http requests for the addressbook application
 *
 * @package     Addressbook
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
		//$view->formData['config']['initialTree'] = $addressbookJson->getSelectFolderTree();

		$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-'.$locale->getLanguage().'.js');
		$view->cssIncludeFiles = array();
		
		$addresses = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
		if($_contactId !== NULL && $contact = $addresses->getContactById($_contactId)) {
			$view->formData['values'] = $contact->toArray();
			$addressbook = Egwbase_Container_Container::getInstance()->getContainerById($contact->contact_owner);
			
			$view->formData['config']['addressbookName']   = $addressbook->container_name;
			$view->formData['config']['addressbookRights'] = $addressbook->account_grants;

			if(!empty($contact->adr_one_countryname)) {
			    $view->formData['config']['oneCountryName'] = $locale->getCountryTranslation($contact->adr_one_countryname);
			}
			if(!empty($contact->adr_two_countryname)) {
			    $view->formData['config']['twoCountryName'] = $locale->getCountryTranslation($contact->adr_one_countryname);
			}
		} else {
		    $personalAddressbooks = $addresses->getAddressbooksByOwner($currentAccount->accountId);
		    foreach($personalAddressbooks as $addressbook) {
    		    $view->formData['values'] = array('contact_owner' => $addressbook->container_id);
    		    $view->formData['config']['addressbookName']   = $addressbook->container_name;
    		    $view->formData['config']['addressbookRights'] = 31;
                break;
		    }
		}
		
		$view->jsIncludeFiles[] = 'Addressbook/js/Addressbook.js';
		$view->cssIncludeFiles[] = 'Addressbook/css/Addressbook.css';
		$view->jsExecute = 'Egw.Addressbook.ContactEditDialog.display();';
        
		$view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
		$view->title="edit contact";
		
		$view->isPopup = true;
        $view->jsIncludeFiles = array_merge(Egwbase_Http::getJsFilesToInclude(), $view->jsIncludeFiles);
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
	}
}