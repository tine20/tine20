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
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @see Tinebase_Controller_Record_Abstract
     */
    protected $_inspectRelatedRecords = TRUE;
    
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

        $this->_duplicateCheckFields = Crm_Config::getInstance()->get(Crm_Config::LEAD_DUP_FIELDS, array(
            array('relations', 'lead_name')
        ));
        $this->_duplicateCheckConfig = array(
            'relations' => array(
                'type'          => 'CUSTOMER',
                'filterField'   => 'contact'
            )
        );

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
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }    
    
    /****************************** overwritten functions ************************/
    
    /**
     * search for leads
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $leads = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
        
        if ($_getRelations) {
            $leads->setByIndices('relations', Tinebase_Relations::getInstance()->getMultipleRelations(
                $this->_modelName, 
                $this->_backend->getType(), 
                $leads->getId(), 
                NULL,
                array('CUSTOMER', 'PARTNER', 'TASK', 'RESPONSIBLE', 'PRODUCT'),
                FALSE,
                $_getRelations
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
     * @param Tinebase_Record_Interface $_lead
     * @param Tinebase_Model_FullUser   $_updater
     * @param string                    $_action   {created|changed}
     * @param Tinebase_Record_Interface $_oldLead
     * @param Array                     $_additionalData
     * @return void
     */
    public function doSendNotifications(Tinebase_Record_Interface $_lead, Tinebase_Model_FullUser $_updater, $_action, Tinebase_Record_Interface $_oldLead = NULL, array $_additionalData = array())
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
        $view->leadState = Crm_Config::getInstance()->get(Crm_Config::LEAD_STATES)->getTranslatedValue($_lead->leadstate_id);
        $view->leadType = Crm_Config::getInstance()->get(Crm_Config::LEAD_TYPES)->getTranslatedValue($_lead->leadtype_id);
        $view->leadSource = Crm_Config::getInstance()->get(Crm_Config::LEAD_SOURCES)->getTranslatedValue($_lead->leadsource_id);
        $view->container = Tinebase_Container::getInstance()->getContainerById($_lead->container_id);
        $view->tags = Tinebase_Tags::getInstance()->getTagsOfRecord($_lead);
        $view->updates = $this->_getNotificationUpdates($_lead, $_oldLead);
        
        if (isset($_lead->relations)) {
            $customer = $_lead->relations->filter('type', 'CUSTOMER')->getFirstRecord();
            if ($customer) {
                $view->customer = $customer->related_record->n_fn;
                if (isset($customer->related_record->org_name)) {
                    $view->customer .= ' (' . $customer->related_record->org_name . ')';
                }
            }
        }
        
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
        
        $view->lang_customer = $translate->_('Customer');
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
        $view->lang_updatedFieldMsg = $translate->_("'%s' changed from '%s' to '%s'.");
        $view->lang_tags = $translate->_('Tags');
        
        $plain = $view->render('newLeadPlain.php');
        $html = $view->render('newLeadHtml.php');
        
        if ($_action == 'changed') {
            $subject = sprintf($translate->_('Lead %s has been changed'), $_lead->lead_name);
        } else if ($_action == 'deleted') {
            $subject = sprintf($translate->_('Lead %s has been deleted'), $_lead->lead_name);
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
        // send notification to updater in any case!
        if (! in_array($_updater->contact_id, $recipients->getArrayOfIds())) {
            $updaterContact = Addressbook_Controller_Contact::getInstance()->get($_updater->contact_id);
            $recipients->addRecord($updaterContact);
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
     * @return Tinebase_Record_RecordSet of Addressbook_Model_Contact
     */
    protected function _getNotificationRecipients(Crm_Model_Lead $_lead) 
    {
        if (! $_lead->relations instanceof Tinebase_Record_RecordSet) {
            $_lead->relations = Tinebase_Relations::getInstance()->getRelations('Crm_Model_Lead', 'Sql', $_lead->getId(), true);
        }
        $recipients = $_lead->getResponsibles();

        // if no responsibles are defined, send message to all readers of container
        if (count($recipients) === 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __METHOD__ . '::' . __LINE__ . ' no responsibles found for lead: ' . 
                $_lead->getId() . ' sending notification to all people having read access to container ' . $_lead->container_id);
                
            $containerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($_lead->container_id, TRUE);
            // NOTE: we just send notifications to users, not to groups or anyones!
            foreach ($containerGrants as $grant) {
                if ($grant['account_type'] == Tinebase_Acl_Rights::ACCOUNT_TYPE_USER && $grant[Tinebase_Model_Grants::GRANT_READ] == 1) {
                    try {
                        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($grant['account_id'], TRUE);
                        $recipients->addRecord($contact);
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
     * get udpate diff for notification
     *
     * @param $lead
     * @param $oldLead
     * @return array
     *
     * TODO generalize
     * TODO translate field names (modelconfig?)
     * TODO allow non scalar values
     */
    protected function _getNotificationUpdates($lead, $oldLead)
    {
        if (! $oldLead) {
            return array();
        }

        $result = array();
        foreach ($lead->diff($oldLead, array('seq', 'notes', 'tags', 'relations', 'attachments', 'last_modified_time', 'last_modified_by'))->diff
             as $key => $value)
        {
            // only allow scalars atm
            if (! is_array($value) && ! is_array($lead->{$key})) {
                $result[] = array(
                    'modified_attribute' => $key,
                    'old_value' => $value,
                    'new_value' => $lead->{$key}
                );
            }
        }

        return $result;
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
        if (empty($_record->turnover)
                && isset($_record->relations)
                && (is_array($_record->relations) || $_record->relations instanceof Tinebase_Record_RecordSet))
        {
            $sum = 0;
            foreach ($_record->relations as $relation) {
                if (! is_array($relation)) {
                    $relation = $relation->toArray();
                }
                
                // check if relation is product and has price
                if ($relation['type'] == 'PRODUCT' && isset($relation['remark']['price'])) {
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
