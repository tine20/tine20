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
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the addressbook application
 *
 * @package     Addressbook
 */
class Addressbook_Http extends Tinebase_Application_Http_Abstract
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
		 
		$view->setScriptPath('Tinebase/views');
		$view->formData = array();

		$view->jsIncludeFiles = array();
		$view->cssIncludeFiles = array();
		
		$addresses = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
		if($_contactId !== NULL && $contact = $addresses->getContactById($_contactId)) {
		    $encodedContact = $contact->toArray();

		    $addressbook = Tinebase_Container_Container::getInstance()->getContainerById($contact->contact_owner);
			$encodedContact['contact_owner'] = $addressbook->toArray();

			if(!empty($contact->adr_one_countryname)) {
			    $encodedContact['adr_one_countrydisplayname'] = $locale->getCountryTranslation($contact->adr_one_countryname);
			}
			if(!empty($contact->adr_two_countryname)) {
			    $encodedContact['adr_two_countrydisplayname'] = $locale->getCountryTranslation($contact->adr_two_countryname);
			}
            $encodedContact = Zend_Json::encode($encodedContact);
		} else {
		    $personalAddressbooks = $addresses->getAddressbooksByOwner($currentAccount->accountId);
		    foreach($personalAddressbooks as $addressbook) {
    		    $encodedContact = Zend_Json::encode(array('contact_owner' => $addressbook->toArray()));
                break;
		    }
		}
		
		$view->jsIncludeFiles[] = self::_appendFileTime('Addressbook/js/Addressbook.js');
		$view->cssIncludeFiles[] = self::_appendFileTime('Addressbook/css/Addressbook.css');
		$view->jsExecute = 'Tine.Addressbook.ContactEditDialog.display(' . $encodedContact . ');';
        
		$view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
		$view->title="edit contact";
		
		$view->isPopup = true;
        $view->jsIncludeFiles = array_merge(Tinebase_Http::getJsFilesToInclude(), $view->jsIncludeFiles);
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
	}
}