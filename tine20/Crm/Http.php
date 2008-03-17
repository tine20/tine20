<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
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
            self::_appendFileTime("Crm/js/Crm.js"),
            self::_appendFileTime("Crm/js/LeadState.js"),
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
//      $view->formData['config']['initialTree'] = $eventschedulerJson->getInitialTree('mainTree');

        $view->jsIncludeFiles = array('extjs/build/locale/ext-lang-'.$locale->getLanguage().'.js');
        $view->cssIncludeFiles = array();
        
        $controller = Crm_Controller::getInstance();
        $leads = Crm_Backend_Factory::factory(Crm_Backend_Factory::SQL);
        
        if($_leadId !== NULL && $lead = $controller->getLead($_leadId)) {
            $leadData = $lead->toArray();
            
            $contact_links = $controller->getLinks($_leadId, 'addressbook');
            foreach($contact_links as $contact_link) {
                try {
                    $contact = Addressbook_Controller::getInstance()->getContact($contact_link['recordId']);
                    switch($contact_link['remark']) {
                        case 'customer':
                            $leadData['contactsCustomer'][] = $contact->toArray();
                            break;
                        case 'partner':
                            $leadData['contactsPartner'][] = $contact->toArray();
                            break;
                        case 'account':
                            $leadData['contactsInternal'][] = $contact->toArray();
                            break;
                    }
                } catch (Exception $e) {
                    // do nothing
                }
            }

            $no_links = '1';
            $task_links = $controller->getLinks($_leadId, 'tasks');
            foreach($task_links as $task_link) {
                try {
                    $task = Tasks_Controller::getInstance()->getTask($task_link['recordId']);            
                    $_task = $task->toArray();

                    $creator = Tinebase_Account::getInstance()->getAccountById($_task['created_by']);
                    $_creator = $creator->toArray();
                    $_task['creator'] = $_creator['accountFullName'];
                    
                    if($_task['last_modified_by'] != NULL) {
                        $modifier = Tinebase_Account::getInstance()->getAccountById($_task['last_modified_by']);
                        $_modifier = $modifier->toArray();
                        $_task['modifier'] = $_modifier['accountFullName'];         
                    }
                    
                    $stati = Tasks_Controller::getInstance()->getStati()->toArray();
                    foreach($stati AS $status) {
                        if($status['identifier'] == $task['status']) {
                            $_task['status_realname'] = $status['status'];
                        }
                    }
                                    
                    
                    $leadData['tasks'][] = $_task;  
                    $no_links = '0';
                    
                } catch (Exception $e) {
                    // do nothing
                }
            }
            
            if($no_links == '1') {
                 $leadData['tasks'] = array();   
            }
            
            $folder = Tinebase_Container::getInstance()->getContainerById($lead->container);
            $leadData['container'] = $folder->toArray();
            
            $products = $leads->getProductsById($_leadId);
            $leadData['products'] = $products->toArray();

            
        } else {
            $leadData = $controller->getEmptyLead()->toArray();
            $leadData['products'] = array();                
            $leadData['contacts'] = array();   
            $leadData['tasks'] = array();                                   
            
            $personalFolders = $controller->getPersonalContainer($currentAccount, $currentAccount->accountId, Tinebase_Container::GRANT_READ);
            foreach($personalFolders as $folder) {
                $leadData['container']     = $folder->toArray();
                break;
            }
            
        }

        $_leadTypes = $leads->getLeadtypes('leadtype','ASC');
        $view->formData['comboData']['leadtypes'] = $_leadTypes->toArray();
        
        $_leadStates =  $leads->getLeadStates('leadstate','ASC');
        $view->formData['comboData']['leadstates'] = $_leadStates->toArray();
        
        $_leadSources =  $leads->getLeadSources('leadsource','ASC');
        $view->formData['comboData']['leadsources'] = $_leadSources->toArray();

        $_productSource =  $leads->getProductsAvailable('productsource','ASC');
        $view->formData['comboData']['productsource'] = $_productSource->toArray();

        $view->jsIncludeFiles[] = self::_appendFileTime('Crm/js/Crm.js');
        $view->jsIncludeFiles[] = self::_appendFileTime('Crm/js/LeadState.js');
        $view->jsIncludeFiles[] = self::_appendFileTime('Tasks/js/Tasks.js');
        $view->cssIncludeFiles[] = 'Crm/css/Crm.css';
        $view->cssIncludeFiles[] = 'Tasks/css/Tasks.css';       
        $view->jsExecute = 'Tine.Crm.LeadEditDialog.displayDialog(' . Zend_Json::encode($leadData) . ' );';

        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        $view->title="edit lead";

        $view->isPopup = true;
        $view->jsIncludeFiles = array_merge(Tinebase_Http::getJsFilesToInclude(), $view->jsIncludeFiles);
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }

}