<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the Crm application
 *
 * @package     Crm
 */
class Crm_Http extends Tinebase_Application_Http_Abstract
{
    protected $_appname = 'Crm';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Crm/js/Crm.js',
            'Crm/js/LeadEditDialog.js',
            'Crm/js/LeadState.js',
            'Crm/js/LeadSource.js',
            'Crm/js/LeadType.js',
            'Crm/js/Product.js',
            'Crm/js/Contact.js',
        );
    }
    
   	/**
     * export lead
     * 
     * @param	string JSON encoded string with lead ids for multi export
     * @param	format	pdf or csv or ...
     * 
     * @todo	implement csv/... export
     */
	public function exportLead($_leadIds, $_format = 'pdf')
	{
        $leadIds = Zend_Json::decode($_leadIds);
	    
        switch ($_format) {
		    case 'pdf':		        		        
                $pdf = new Crm_Pdf();
		        
		        foreach ($leadIds as $leadId) {
                    $lead = Crm_Controller::getInstance()->getLead($leadId);
                    $pdf->generateLeadPdf($lead);
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
                header("Content-Disposition: inline; filename=lead.pdf"); 
                header("Content-type: application/x-pdf"); 
                echo $pdfOutput;            
                break;
                
		    default:
		        echo "Format $_format not supported yet.";
		        exit();
		}
	}    

    /**
     * Returns initial data which is send to the app at creation time.
     *
     * When the mainScreen is created, Tinebase_Http_Controller queries this function
     * to get the initial datas for this app. This pattern prevents that any app needs
     * to make an server-request for its initial datas.
     * 
     * Initial data objects are just javascript variables declared in the mainScreen html code.
     * 
     * The returned data have to be an array with the variable names as keys and
     * the datas as values. The datas will be JSON encoded later. Note that the
     * variable names get prefixed with Tine.<applicationname>
     * 
     * @return mixed array 'variable name' => 'data'
     * @todo    is the setTimezone needed?
     */
    public function getRegistryData()
    {   
        $json = new Crm_Json();
        
        $initialData = array(
            'LeadTypes' => $json->getLeadtypes('leadtype','ASC'),
            'LeadStates' => $json->getLeadStates('leadstate','ASC'),
            'LeadSources' => $json->getLeadSources('leadsource','ASC'),
            'Products' => $json->getProducts('productsource','ASC'),
        );
        
        /*
        foreach ($initialData as &$data) {
            $data->setTimezone(Zend_Registry::get('userTimeZone'));
            $data = $data->toArray();
        }
        */
        return $initialData;    
    }
	
}