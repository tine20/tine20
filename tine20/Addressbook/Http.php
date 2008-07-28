<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        put gmaps api key in config
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
    
    /**
     * edit contact dialog
     * 
     * @param	integer contact id
     * 
     */
    public function editContact ($_contactId)
    {
        if (empty($_contactId)) {
            $_contactId = NULL;
        }
        $locale = Zend_Registry::get('locale');
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $view = new Zend_View();
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();
        
        $tinebaseJson = new Tinebase_Json();
        $view->initialData['Addressbook'] = array('NoteTypes' => $tinebaseJson->getNoteTypes());        
        
        $addresses = Addressbook_Controller::getInstance();
        if ($_contactId !== NULL && $contact = $addresses->getContact($_contactId)) {
            $encodedContact = $contact->toArray();
            $addressbook = Tinebase_Container::getInstance()->getContainerById($contact->owner);
            $encodedContact['owner'] = $addressbook->toArray();
            $encodedContact['owner']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount($currentAccount, $contact->owner)->toArray();

            if (!empty($encodedContact['jpegphoto'])) {
                $encodedContact['jpegphoto'] = 'index.php?method=Tinebase.getImage&application=Addressbook&location=&id=' . $_contactId . '&width=90&height=90&ratiomode=0';
            }
            if (! empty($contact['adr_one_countryname'])) {
                $encodedContact['adr_one_countrydisplayname'] = $locale->getCountryTranslation($contact['adr_one_countryname']);
            }
            if (! empty($contact['adr_two_countryname'])) {
                $encodedContact['adr_two_countrydisplayname'] = $locale->getCountryTranslation($contact['adr_two_countryname']);
            }
            $encodedContact = Zend_Json::encode($encodedContact);
            
        } else {
            $personalAddressbooks = Tinebase_Container::getInstance()->getPersonalContainer($currentAccount, 
                'Addressbook', $currentAccount->accountId, Tinebase_Container::GRANT_READ);
            foreach ($personalAddressbooks as $addressbook) {
                $contact = array( 
                    'owner' => $addressbook->toArray()
                );
                $contact['owner']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount($currentAccount, $addressbook)->toArray();
                $encodedContact = Zend_Json::encode($contact);
                break;
            }
        }
        
        $view->jsExecute = 'Tine.Addressbook.ContactEditDialog.display(' . $encodedContact . ');';
        $view->configData = array('timeZone' => Zend_Registry::get('userTimeZone') , 'currentAccount' => Zend_Registry::get('currentAccount')->toArray());
        $view->title = "edit contact";
        $view->isPopup = true;
        $includeFiles = Tinebase_Http::getAllIncludeFiles();
        $view->jsIncludeFiles = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        // add google api key
        if (isset(Zend_Registry::get('configFile')->addressbook->googleKey)) {
            $view->googleApi = '<script src="http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=' . 
                                Zend_Registry::get('configFile')->addressbook->googleKey . 
                                '" type="text/javascript"></script>';
        }        
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }

    /**
     * export contact
     * 
     * @param	string JSON encoded string with contact ids for multi export
     * @param	format	pdf or csv or ...
     * 
     * @todo	implement csv export
     */
    public function exportContact ($_contactIds, $_format = 'pdf')
    {
        $contactIds = Zend_Json::decode($_contactIds);
        
        switch ($_format) {
            case 'pdf':                             
                $pdf = new Addressbook_Pdf();
                
                foreach ($contactIds as $contactId) {
                    $contact = Addressbook_Controller::getInstance()->getContact($contactId);
                    $pdf->generateContactPdf($contact);
                }
                    
                try {
                    $pdfOutput = $pdf->render();
                } catch ( Zend_Pdf_Exception $e ) {
                    Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' error creating pdf: ' . $e->__toString() );
                    echo "could not create pdf <br/>". $e->__toString();
                    exit();            
                }
                
                header("Content-Disposition: inline; filename=contact.pdf"); 
                header("Content-type: application/x-pdf"); 
                echo $pdfOutput;            
                break;
                
            default:
                echo "Format $_format not supported yet.";
                exit();
        }        
    }
    
    /**
     * Returns all JS files which must be included for Addressbook
     * 
     * @return array array of filenames
     */
    public function getJsFilesToInclude ()
    {
        return array('Addressbook/js/Addressbook.js' , 'Addressbook/js/EditDialog.js');
    }
}