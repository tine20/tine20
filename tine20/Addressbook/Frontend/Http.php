<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Addressbook http frontend class
 *
 * This class handles all Http requests for the addressbook application
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_Http extends Tinebase_Application_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'Addressbook';

    /**
     * export contact
     * 
     * @param	string JSON encoded string with contact ids for multi export or contact filter
     * @param	format	pdf or csv or ...
     */
    public function exportContacts($_filter, $_format = 'pdf')
    {        
        switch ($_format) {
            case 'pdf':
                $contactIds = Zend_Json::decode($_filter);                
                
                $pdf = new Addressbook_Export_Pdf();
                
                foreach ($contactIds as $contactId) {
                    $contact = Addressbook_Controller_Contact::getInstance()->get($contactId);
                    $pdf->generateContactPdf($contact);
                }
                    
                try {
                    $pdfOutput = $pdf->render();
                } catch (Zend_Pdf_Exception $e) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' error creating pdf: ' . $e->__toString());
                    echo "could not create pdf <br/>". $e->__toString();
                    exit();            
                }
                
                header("Pragma: public");
                header("Cache-Control: max-age=0");
                header("Content-Disposition: inline; filename=contact.pdf"); 
                header("Content-type: application/x-pdf"); 
                echo $pdfOutput;
                break;
            
            case 'csv':
                $filter = new Addressbook_Model_ContactFilter(Zend_Json::decode($_filter));
                
                $csvExportClass = new Addressbook_Export_Csv();
                $result = $csvExportClass->exportContacts($filter);
                
                header("Pragma: public");
                header("Cache-Control: max-age=0");
                header("Content-Disposition: inline; filename=contacts_export.csv");
                header("Content-Description: csv File");  
                header("Content-type: text/csv"); 
                readfile($result);
                exit;
                                
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
