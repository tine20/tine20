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
                
                header("Pragma: public");
                header("Cache-Control: max-age=0");
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
    
    /**
     * Returns initial data which is send to the app at creation time.
     *
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {   
        $json = new Addressbook_Json();
        
        $registryData = array(
            'Salutations' => $json->getSalutations(),
        );        
        return $registryData;    
    }
}