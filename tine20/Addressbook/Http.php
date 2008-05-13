<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
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
        $addresses = Addressbook_Controller::getInstance();
        if ($_contactId !== NULL && $contact = $addresses->getContact($_contactId)) {
            $encodedContact = $contact->toArray();
            $addressbook = Tinebase_Container::getInstance()->getContainerById($contact->owner);
            $encodedContact['owner'] = $addressbook->toArray();
            $encodedContact['grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount($currentAccount, $contact->owner)->toArray();
            $encodedContact['tags'] = $encodedContact['tags']->toArray();
            //Zend_Registry::get('logger')->debug(print_r($encodedContact,true));
            if (!empty($encodedContact['jpegphoto'])) {
                $encodedContact['jpegphoto'] = 'index.php?method=Addressbook.getImage&id=' . $_contactId . '&width=90&height=90&ratiomode=0';
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
                $encodedContact = Zend_Json::encode(array(
                    'owner' => $addressbook->toArray() ,
                    'grants' => Tinebase_Container::getInstance()->getGrantsOfAccount($currentAccount, $addressbook)->toArray()
                ));
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
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
    /**
     * gets image of a contact identified by its id
     * 
     * @param  int $id
     * @param  int width
     * @param  int $height
     */
    public function getImage($id, $width, $height, $ratiomode)
    {
        $contact = Addressbook_Controller::getInstance()->getContact($id);

        $tmpPath = tempnam('/tmp', 'tine20_tmp_gd');
        file_put_contents($tmpPath, $contact->jpegphoto);

        $image = Tinebase_ImageHelper::resize($tmpPath, $width, $height, $ratiomode);
        
        header('Content-Type: image/jpeg');
        imagejpeg($image);
        imagedestroy($image);
        
        unlink($tmpPath);
        die();
    }
    /**
     * export contact
     * 
     * @param	integer contact id
     * @param	format	pdf or csv or ...
     * 
     * @todo	implement csv export
     */
    public function exportContact ($_contactId, $_format = 'pdf')
    {
        // get contact
        $contact = Addressbook_Controller::getInstance()->getContact($_contactId);
        // export
        if ($_format === "pdf") {
            $pdf = new Addressbook_Pdf();
            $pdfOutput = $pdf->getContactPdf($contact);
            header("Content-Disposition: inline; filename=contact.pdf");
            header("Content-type: application/x-pdf");
            echo $pdfOutput;
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