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
        );
    }
    
    /**
     * create edit lead dialog
     *
     * @param int $_leadId
     * @todo catch permission denied exceptions only
     * 
     */
    public function editLead($_leadId)
    {
         if(empty($_leadId)) {
            $_leadId = NULL;
        }
        
        $locale = Zend_Registry::get('locale');
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $view = new Zend_View();
         
        $view->setScriptPath('Tinebase/views');
        $view->formData = array();
        
        $crmJson = new Crm_Json;        
        
        $controller = Crm_Controller::getInstance();
        
        // @todo getLead() from Crm_Json
        if($_leadId !== NULL && $lead = $controller->getLead($_leadId)) {
            $leadData = $lead->toArray();

            // add contact links
            $leadData['contacts'] = array();
            $contact_links = $controller->getLinksForApplication($_leadId, 'Addressbook');
            foreach($contact_links as $contact_link) {
                try {
                    $contact = Addressbook_Controller::getInstance()->getContact($contact_link['recordId']);
                    $contactArray = $contact->toArray();
                    $contactArray['link_remark'] = $contact_link['remark'];
                    $leadData['contacts'][] = $contactArray;                    
                } catch (Exception $e) {
                    // do nothing
                    // catch only permission denied exception
                }
            }

            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($leadData['contacts'], true));
            
            // add task links
            $leadData['tasks'] = array();
            $taskLinks = $controller->getLinksForApplication($_leadId, 'Tasks');
            // @todo    move that to controller?
            foreach ( $taskLinks as $taskLink ) {
                try {
                    $task = Tasks_Controller::getInstance()->getTask($taskLink['recordId']);            
                    $taskArray = $task->toArray();

                    $creator = Tinebase_User::getInstance()->getUserById($task->created_by);
                    $taskArray['creator'] = $creator->accountFullName;
                    
                    if ($task->last_modified_by != NULL) {
                        $modifier = Tinebase_User::getInstance()->getUserById($task->last_modified_by);
                        $taskArray['modifier'] = $modifier->accountFullName;         
                    }

                    // @todo write function for that: getStatusById()
                    $stati = Tasks_Controller::getInstance()->getStati()->toArray();
                    foreach ($stati as $status) {
                        if ($status['id'] == $taskArray['status_id']) {
                            $taskArray['status_realname'] = $status['status_name'];
                            $taskArray['status_icon'] = $status['status_icon'];
                        }
                    }

                    $leadData['tasks'][] = $taskArray;  
                    
                } catch (Exception $e) {
                    // do nothing
                    //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->__toString());
                }
            }
            
            // add container
            $folder = Tinebase_Container::getInstance()->getContainerById($lead->container);            
            $leadData['container'] = $folder->toArray();
            
            // add products
            $products = $controller->getProductsByLeadId($_leadId);
            $leadData['products'] = $products->toArray();
            
            // add tags
             $leadData['tags'] = $leadData['tags']->toArray();            
            
        } else {
            // @todo set default values in js and remove getEmptyXXX functions
            $leadData = $controller->getEmptyLead()->toArray();
            $leadData['products'] = array();                
            $leadData['contacts'] = array();   
            $leadData['tasks'] = array();                                   
            
            $personalFolders = Zend_Registry::get('currentAccount')->getPersonalContainer('Crm', $currentAccount, Tinebase_Container::GRANT_READ);
            foreach($personalFolders as $folder) {
                $leadData['container']     = $folder->toArray();
                break;
            }
            
        }

        // add lead types/states/sources and products to initialData
        $view->initialData = array();
        $view->initialData['Crm'] = $this->getInitialMainScreenData();
        
        $view->jsExecute = 'Tine.Crm.LeadEditDialog.display(' . Zend_Json::encode($leadData) . ' );';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit lead";

        $view->isPopup = true;
        
        $includeFiles = Tinebase_Http::getAllIncludeFiles();
        $view->jsIncludeFiles  = $includeFiles['js'];
        $view->cssIncludeFiles = $includeFiles['css'];
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }

   	/**
     * export lead
     * 
     * @param	integer lead id
     * @param	format	pdf or csv or ...
     * 
     * @todo	implement csv export
     */
	public function exportLead($_leadId, $_format = 'pdf')
	{
		// get lead
		$lead = Crm_Controller::getInstance()->getLead($_leadId);
		
		// export
		if ( $_format === "pdf" ) {
			$pdf = new Crm_Pdf();
			$pdfOutput = $pdf->getLeadPdf($lead);

			header("Content-Disposition: inline; filename=lead.pdf"); 
			header("Content-type: application/x-pdf"); 
			echo $pdfOutput; 
			
		}
	}    

    /**
     * Returns initial data which is send to the app at creation time.
     *
     * When the mainScreen is created, Tinebase_Http_Controller queries this function
     * to get the initial datas for this app. This pattern prevents that any app needs
     * to make an server-request for its initial datas.
     * 
     * Initial datas are just javascript varialbes declared in the mainScreen html code.
     * 
     * The returned data have to be an array with the variable names as keys and
     * the datas as values. The datas will be JSON encoded later. Note that the
     * variable names get prefixed with Tine.<applicationname>
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getInitialMainScreenData()
    {   
        /*     
        $controller = Crm_Controller::getInstance();
        $initialData = array(
            'LeadTypes' => $controller->getLeadtypes('leadtype','ASC'),
            'LeadStates' => $controller->getLeadStates('leadstate','ASC'),
            'LeadSources' => $controller->getLeadSources('leadsource','ASC'),
            'Products' => $controller->getProducts('productsource','ASC'),
        );
        */

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