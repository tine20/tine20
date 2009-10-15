<?php
/**
 * leads controller for CRM application
 * 
 * the main logic of the CRM application (for leads)
 *
 * @package     Crm
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Controller.php 5029 2008-10-21 16:28:16Z p.schuele@metaways.de $
 *
 */

/**
 * leads controller class for CRM application
 * 
 * @package     Crm
 * @subpackage  Controller
 */
class Crm_Controller_Lead extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Crm';
        $this->_modelName = 'Crm_Model_Lead';
        $this->_backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);
        $this->_currentAccount = Tinebase_Core::getUser();
        
        // send notifications
        $this->_sendNotifications = TRUE;
        
        // delete related tasks
        $this->_relatedObjectsToDelete = array('Tasks_Model_Task');
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Crm_Controller_Lead
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Crm_Controller_Lead
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Crm_Controller_Lead();
        }
        
        return self::$_instance;
    }    
    
    /****************************** overwritten functions ************************/
    
    /**
     * get lead identified by leadId
     *
     * @param   int $_id
     * @return  Crm_Model_Lead
     */
    public function get($_id)
    {
        $lead = parent::get($_id);
        
        // add products
        $lead->products = Crm_Controller_LeadProducts::getInstance()->getLeadProducts($lead->getId());
        
        return $lead;
    }
    
    /**
     * add Lead
     *
     * @param   Tinebase_Record_Interface $_lead the lead to add
     * @return  Crm_Model_Lead the newly added lead
     */ 
    public function create(Tinebase_Record_Interface $_lead)
    {
        $lead = parent::create($_lead);
        $this->_setLeadProducts($lead->getId(), $_lead);
        
        return $lead;
    }

   /**
     * update Lead
     *
     * @param   Tinebase_Record_Interface $_lead the lead to update
     * @return  Crm_Model_Lead the updated lead
     */ 
    public function update(Tinebase_Record_Interface $_lead)
    {
        $lead = parent::update($_lead);
        $this->_setLeadProducts($lead->getId(), $_lead);
        
        return $lead;
    }
    
    /**
     * search for leads
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Record_Interface $_pagination
     * @param boolean $_getRelations
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        $leads = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds);
        
        if ($_getRelations) {
            $leads->setByIndices('relations', Tinebase_Relations::getInstance()->getMultipleRelations(
                $this->_modelName, 
                $this->_backend->getType(), 
                $leads->getId(), 
                NULL, 
                array('CUSTOMER', 'PARTNER', 'TASK', 'RESPONSIBLE')
            ));
        }
        
        return $leads;
    }
    
    
    /********************* notifications ***************************/
    
    /**
     * creates notification text and sends out notifications
     *
     * @todo:
     *  - add changes to mail body
     *  - find updater in addressbook to notify him
     *  
     * @param Crm_Model_Lead            $_lead
     * @param Tinebase_Model_FullUser   $_updater
     * @param string                    $_action   {created|changed}
     * @param Crm_Model_Lead            $_oldLead
     * @return void
     * 
     * @todo add leadState/Type/Source again (move that to app controller?)
     */
    protected function sendNotifications(Crm_Model_Lead $_lead, Tinebase_Model_FullUser $_updater, $_action, $_oldLead = NULL)
    {
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views');
        
        $translate = Tinebase_Translation::getTranslation('Crm');
        
        $view->updater = $_updater;
        $view->lead = $_lead;
        /*
        $view->leadState = Crm_Controller_LeadStates::getInstance()->getLeadState($_lead->leadstate_id);
        $view->leadType = Crm_Controller_LeadTypes::getInstance()->getLeadType($_lead->leadtype_id);
        $view->leadSource = Crm_Controller_LeadSources::getInstance()->getLeadSource($_lead->leadsource_id);
        */
        $view->container = Tinebase_Container::getInstance()->getContainerById($_lead->container_id);
        //$view->updates = $_updates;
        
        if($_lead->start instanceof Zend_Date) {
            $view->start = $_lead->start->toString(Zend_Locale_Format::getDateFormat(Tinebase_Core::get('locale')), Tinebase_Core::get('locale'));
        } else {
            $view->start = '-';
        }
        
        if($_lead->end instanceof Zend_Date) {
            $view->leadEnd = $_lead->end->toString(Zend_Locale_Format::getDateFormat(Tinebase_Core::get('locale')), Tinebase_Core::get('locale'));
        } else {
            $view->leadEnd = '-';
        }
        
        if($_lead->end_scheduled instanceof Zend_Date) {
            $view->ScheduledEnd = $_lead->end_scheduled->toString(Zend_Locale_Format::getDateFormat(Tinebase_Core::get('locale')), Tinebase_Core::get('locale'));
        } else {
            $view->ScheduledEnd = '-';
        }
        
        $view->lang_state = $translate->_('State');
        $view->lang_type = $translate->_('Type');
        $view->lang_source = $translate->_('Source');
        $view->lang_start = $translate->_('Start');
        $view->lang_scheduledEnd = $translate->_('Scheduled end');
        $view->lang_end = $translate->_('End');
        $view->lang_turnover = $translate->_('Turnover');
        $view->lang_probability = $translate->_('Probability');
        $view->lang_folder = $translate->_('Folder');
        $view->lang_updatedBy = $translate->_('Updated by');
        $view->lang_updatedFields = $translate->_('Updated Fields:');
        $view->lang_updatedFieldMsg = $translate->_('%s changed from %s to %s.');
        
        $plain = $view->render('newLeadPlain.php');
        $html = $view->render('newLeadHtml.php');
        
        if($_action == 'changed') {
            $subject = sprintf($translate->_('Lead %s has been changed'), $_lead->lead_name);
        } else {
            $subject = sprintf($translate->_('Lead %s has been creaded'), $_lead->lead_name);
        }

        // create pdf
        try {
            $pdfGenerator = new Crm_Export_Pdf();
            $pdfGenerator->generate($_lead);
            $pdfOutput = $pdfGenerator->render();
        } catch ( Zend_Pdf_Exception $e ) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' error creating pdf: ' . $e->__toString());
            $pdfOutput = NULL;
        }
                
        $recipients = $this->_getNotificationRecipients($_lead);
        // send notificaton to updater in any case!
        // UGH! how to find out his adb id?
        //if (! in_array($_updater->accountId, $recipients)) {
        //    $recipients[] = $_updater->accountId;
        //}
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . $plain);
        Tinebase_Notification::getInstance()->send($this->_currentAccount, $recipients, $subject, $plain, $html, $pdfOutput);
    }
    
    /*********************** helper functions ************************/
    
    /**
     * set lead products
     *
     * @param integer $_leadId
     * @param Crm_Model_Lead $_lead
     */
    protected function _setLeadProducts($_leadId, Crm_Model_Lead $_lead)
    {
        // add product links
        $productsArray = array();
        if (isset($_lead->products) && is_array($_lead->products)) {
            foreach ($_lead->products as $product) {
                $product['lead_id'] = $_leadId; 
                $productsArray[] = $product;     
            }
        }       
        
        $products = new Tinebase_Record_RecordSet('Crm_Model_LeadProduct', $productsArray);
        Crm_Controller_LeadProducts::getInstance()->saveLeadProducts($_leadId, $products);                        
    }
    
    /**
     * returns recipients for a lead notification
     *
     * @param  Crm_Model_Lead $_lead
     * @return array          array of int|Addressbook_Model_Contact
     */
    protected function _getNotificationRecipients(Crm_Model_Lead $_lead) 
    {
        $recipients = array();
        
        $relations = Tinebase_Relations::getInstance()->getRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $_lead->getId(), true);
        
        foreach ($relations as $relation) {
            if ($relation->related_model == 'Addressbook_Model_Contact' && $relation->type == 'RESPONSIBLE') {
                $recipients[] = $relation->related_record;
            }
        }
        
        // if no responsibles are defined, send message to all readers of container
        if (empty($recipients)) {
            Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __METHOD__ . '::' . __LINE__ . ' no responsibles found for lead: ' . 
                $_lead->getId() . ' sending notification to all people having read access to container ' . $_lead->container_id);
                
            $containerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($_lead->container_id, TRUE);
            // NOTE: we just send notifications to users, not to groups or anyones!
            foreach ($containerGrants as $grant) {
                if ($grant['account_type'] == Tinebase_Acl_Rights::ACCOUNT_TYPE_USER && $grant['readGrant'] == 1) {
                    $recipients[] = $grant['account_id'];
                }
            }
        }
        
        return $recipients;
    }    
}
