<?php
/**
 * leads controller for CRM application
 * 
 * the main logic of the CRM application (for leads)
 *
 * @package     Crm
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
    private function __construct()
    {
        $this->_applicationName         = 'Crm';
        $this->_modelName               = 'Crm_Model_Lead';
        $this->_relatedObjectsToDelete  = array('Tasks_Model_Task');
        $this->_sendNotifications       = TRUE;
        $this->_purgeRecords            = FALSE;
        $this->_doRightChecks           = TRUE;
        $this->_resolveCustomFields     = TRUE;
        
        $this->_backend                 = new Crm_Backend_Lead();
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
     * search for leads
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Record_Interface $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $leads = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
        
        if ($_getRelations) {
            $leads->setByIndices('relations', Tinebase_Relations::getInstance()->getMultipleRelations(
                $this->_modelName, 
                $this->_backend->getType(), 
                $leads->getId(), 
                NULL, 
                array('CUSTOMER', 'PARTNER', 'TASK', 'RESPONSIBLE', 'PRODUCT')
            ));
        }
        
        return $leads;
    }
    
/**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return array
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get') 
    {
        $this->checkFilterACL($_filter, $_action);

        $result['totalcount'] = $this->_backend->searchCount($_filter);
        
        // add counts for leadstates/sources/types
        $result['leadstates'] = $this->_backend->getGroupCountForField($_filter, 'leadstate_id');
        $result['leadsources'] = $this->_backend->getGroupCountForField($_filter, 'leadsource_id');
        $result['leadtypes'] = $this->_backend->getGroupCountForField($_filter, 'leadtype_id');
        
        return $result;
    }            
    
    /********************* notifications ***************************/
    
    /**
     * creates notification text and sends out notifications
     *
     * @todo:
     *  - add changes to mail body
     *  - find updater in addressbook to notify him
     *  - add products?
     *  - add notification levels
     *  
     * @param Crm_Model_Lead            $_lead
     * @param Tinebase_Model_FullUser   $_updater
     * @param string                    $_action   {created|changed}
     * @param Crm_Model_Lead            $_oldLead
     * @return void
     */
    protected function doSendNotifications(Crm_Model_Lead $_lead, Tinebase_Model_FullUser $_updater, $_action, $_oldLead = NULL)
    {
        $sendOnOwnActions = Tinebase_Core::getPreference('Crm')->getValue(Crm_Preference::SEND_NOTIFICATION_OF_OWN_ACTIONS);
        if (! $sendOnOwnActions) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Sending of Lead notifications disabled by user.');
            return;
        }
        
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views');
        
        $translate = Tinebase_Translation::getTranslation('Crm');
        
        $view->updater = $_updater;
        $view->lead = $_lead;
        $settings = Crm_Controller::getInstance()->getConfigSettings();
        $view->leadState = $settings->getOptionById($_lead->leadstate_id, 'leadstates');
        $view->leadType = $settings->getOptionById($_lead->leadtype_id, 'leadtypes');
        $view->leadSource = $settings->getOptionById($_lead->leadsource_id, 'leadsources');
        $view->container = Tinebase_Container::getInstance()->getContainerById($_lead->container_id);
        //$view->updates = $_updates;
        
        if($_lead->start instanceof DateTime) {
            $view->start = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_lead->start, NULL, NULL, 'date');
        } else {
            $view->start = '-';
        }
        
        if($_lead->end instanceof DateTime) {
            $view->leadEnd = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_lead->end, NULL, NULL, 'date');
        } else {
            $view->leadEnd = '-';
        }
        
        if($_lead->end_scheduled instanceof DateTime) {
            $view->ScheduledEnd = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_lead->end_scheduled, NULL, NULL, 'date');
        } else {
            $view->ScheduledEnd = '-';
        }
        
        $view->lang_state = $translate->_('State');
        $view->lang_type = $translate->_('Role');
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
            $subject = sprintf($translate->_('Lead %s has been created'), $_lead->lead_name);
        }

        // create pdf
        try {
            $pdfGenerator = new Crm_Export_Pdf();
            $pdfGenerator->generate($_lead);
            $attachment = array('rawdata' => $pdfGenerator->render(), 'filename' => $_lead->lead_name . '.pdf');
        } catch ( Zend_Pdf_Exception $e ) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' error creating pdf: ' . $e->__toString());
            $attachment = NULL;
        }
                
        $recipients = $this->_getNotificationRecipients($_lead);
        // send notificaton to updater in any case!
        if (! in_array($_updater->accountId, $recipients)) {
            $recipients[] = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId(), TRUE)->getId();
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . $plain);
        
        try {
            Tinebase_Notification::getInstance()->send(Tinebase_Core::getUser(), $recipients, $subject, $plain, $html, array($attachment));
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__CLASS__ . '::' . __METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__CLASS__ . '::' . __METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
        }
    }
    
    /*********************** helper functions ************************/
    
    /**
     * returns recipients for a lead notification
     *
     * @param  Crm_Model_Lead $_lead
     * @return array          array of int|Addressbook_Model_Contact
     */
    protected function _getNotificationRecipients(Crm_Model_Lead $_lead) 
    {
        $recipients = array();
        
        $relations = Tinebase_Relations::getInstance()->getRelations('Crm_Model_Lead', 'Sql', $_lead->getId(), true);
        
        foreach ($relations as $relation) {
            if ($relation->related_model == 'Addressbook_Model_Contact' && $relation->type == 'RESPONSIBLE') {
                $recipients[] = $relation->related_record;
            }
        }
        
        // if no responsibles are defined, send message to all readers of container
        if (empty($recipients)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __METHOD__ . '::' . __LINE__ . ' no responsibles found for lead: ' . 
                $_lead->getId() . ' sending notification to all people having read access to container ' . $_lead->container_id);
                
            $containerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($_lead->container_id, TRUE);
            // NOTE: we just send notifications to users, not to groups or anyones!
            foreach ($containerGrants as $grant) {
                if ($grant['account_type'] == Tinebase_Acl_Rights::ACCOUNT_TYPE_USER && $grant[Tinebase_Model_Grants::GRANT_READ] == 1) {
                    try {
                        $recipients[] = Addressbook_Controller_Contact::getInstance()->getContactByUserId($grant['account_id'], TRUE)->getId();
                    } catch (Addressbook_Exception_NotFound $aenf) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__CLASS__ . '::' . __METHOD__ . '::' . __LINE__ 
                            . ' Do not send notification to non-existant user: ' . $aenf->getMessage());
                    }
                }
            }
        }
        
        return $recipients;
    }
    
    /**
     * check if user has the right to manage leads
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }
        
        switch ($_action) {
            case 'create':
            case 'update':
            case 'delete':
                if (! Tinebase_Core::getUser()->hasRight('Crm', Crm_Acl_Rights::MANAGE_LEADS)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage leads!");
                }
                break;
            default;
               break;
        }
    }
    
    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_setTurnover($_record);
    }
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_setTurnover($_record);
    }
    
    /**
     * set turnover of record if empty by calulating sum of product prices
     * 
     * @param Tinebase_Record_Interface $_record
     * @return void
     */
    protected function _setTurnover($_record)
    {
        if (empty($_record->turnover) && isset($_record->relations)) {
            $sum = 0;
            foreach ($_record->relations as $relation) {
                if (! is_array($relation)) {
                    $relation = $relation->toArray();
                }
                
                // check if relation is product and has price
                if ($relation['type'] == 'PRODUCT') {
                    $quantity = (isset($relation['remark']['quantity'])) ? $relation['remark']['quantity'] : 1;
                    $sum += $relation['remark']['price'] * (integer) $quantity;
                }
            }
            
            if ($sum > 0) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Set turnover of record by calculating sum of product prices: ' . $sum);
                $_record->turnover = $sum;
            }
        }
    }
}
