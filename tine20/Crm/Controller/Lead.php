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
 * @todo        replace functions (use them from abstract controller)
 */

/**
 * leads controller class for CRM application
 * 
 * @package     Crm
 * @subpackage  Controller
 */
class Crm_Controller_Lead extends Tinebase_Application_Controller_Record_Abstract
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
    }
    
    /**
     * holdes the instance of the singleton
     *
     * @var Crm_Controller
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
     * Search for leads matching given filter
     *
     * @param Crm_Model_LeadFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param bool $_getRelations
     * 
     * @return Tinebase_Record_RecordSet
     */
    public function search(Crm_Model_LeadFilter $_filter, Tinebase_Model_Pagination $_pagination, $_getRelations = FALSE)
    {
        $leads = parent::search($_filter, $_pagination);
        
        if ($_getRelations) {
            foreach ($leads as &$lead) {
                $lead->relations = Tinebase_Relations::getInstance()->getRelations($this->_modelName, $this->_backend->getType(), $lead->getId());
            }
        }
        
        return $leads;
    }

    /**
     * add Lead
     *
     * @param   Crm_Model_Lead $_lead the lead to add
     * @return  Crm_Model_Lead the newly added lead
     */ 
    public function create(Crm_Model_Lead $_lead)
    {
        $lead = parent::create($_lead);
        $this->_setLeadProducts($lead->getId(), $_lead);
        
        return $lead;
    }

   /**
     * update Lead
     *
     * @param   Crm_Model_Lead $_lead the lead to update
     * @return  Crm_Model_Lead the updated lead
     */ 
    public function update(Crm_Model_Lead $_lead)
    {
        $lead = parent::update($_lead);
        $this->_setLeadProducts($lead->getId(), $_lead);
        
        return $lead;
    }
    
    /*********** other public functions **************/
    
    /**
     * returns an empty lead with some defaults set
     * - add creator as internal contact
     *
     * @return Crm_Model_Lead
     */
    public function getEmptyLead()
    {
        $defaultState  = (isset(Zend_Registry::get('configFile')->crm->defaultstate) ? Zend_Registry::get('configFile')->crm->defaultstate : 1);
        $defaultType   = (isset(Zend_Registry::get('configFile')->crm->defaulttype) ? Zend_Registry::get('configFile')->crm->defaulttype : 1);
        $defaultSource = (isset(Zend_Registry::get('configFile')->crm->defaultsource) ? Zend_Registry::get('configFile')->crm->defaultsource : 1);
        
        $defaultData = array(
            'leadstate_id'   => $defaultState,
            'leadtype_id'    => $defaultType,
            'leadsource_id'  => $defaultSource,
            'start'          => Zend_Date::now(),
            'probability'    => 0
        );
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($defaultData, true));
        $emptyLead = new Crm_Model_Lead($defaultData, true);
        
        // add creator as RESPONSIBLE (only if user backend isn't LDAP)
        if (Tinebase_User::getConfiguredBackend() !== Tinebase_User::LDAP) {
            $userContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($this->_currentAccount->getId());
            $emptyLead->relations = new Tinebase_Record_RecordSet('Tinebase_Model_Relation');
            $emptyLead->relations->addRecord(new Tinebase_Model_Relation(array(
                'own_id'                 => 0,
                'own_model'              => 'Crm_Model_Lead',
                'own_backend'            => Crm_Backend_Factory::SQL,
                'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Addressbook_Model_Contact',
                'related_backend'        => Addressbook_Backend_Factory::SQL,
                'related_id'             => $userContact->getId(),
                'type'                   => 'RESPONSIBLE',
                'related_record'         => $userContact->toArray()
            )));
        }
        
        return $emptyLead;
    }

    /*************** add / update / delete lead *****************/    
    
    // @todo check the following funcs
    
    /**
     * delete a lead
     *
     * @param   int|array|Tinebase_Record_RecordSet|Crm_Model_Lead $_leadId
     * @throws  Crm_Exception_AccessDenied
     */
    public function deleteLead($_leadId)
    {
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEADS);
        
        try {
            $db = $backend->getDb();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            if(is_array($_leadId) or $_leadId instanceof Tinebase_Record_RecordSet) {
                foreach($_leadId as $leadId) {
                    $this->deleteLead($leadId);
                }
            } else {                            
                $lead = $backend->get($_leadId);
                
                if($this->_currentAccount->hasGrant($lead->container_id, Tinebase_Model_Container::GRANT_DELETE)) {
                    $backend->delete($_leadId);
    
                    // delete notes
                    Tinebase_Notes::getInstance()->deleteNotesOfRecord('Crm_Model_Lead', 'Sql', $lead->getId());                
                } else {
                    throw new Crm_Exception_AccessDenied('Delete access to lead denied.');
                }
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }

    /*********************** links functions ************************/
    
    /**
     * set lead products
     *
     * @param integer $_leadId
     * @param Crm_Model_Lead $_lead
     */
    private function _setLeadProducts($_leadId, Crm_Model_Lead $_lead)
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
     * @param Tinebase_Record_RecordSet $_updates
     * @return void
     */
    protected function sendNotifications(Crm_Model_Lead $_lead, Tinebase_Model_FullUser $_updater, $_action, $_updates=array())
    {
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views');
        
        $translate = Tinebase_Translation::getTranslation('Crm');
        
        $view->updater = $_updater;
        $view->lead = $_lead;
        $view->leadState = Crm_Controller_LeadStates::getInstance()->getLeadState($_lead->leadstate_id);
        $view->leadType = Crm_Controller_LeadTypes::getInstance()->getLeadType($_lead->leadtype_id);
        $view->leadSource = Crm_Controller_LeadSources::getInstance()->getLeadSource($_lead->leadsource_id);
        $view->container = Tinebase_Container::getInstance()->getContainerById($_lead->container_id);
        $view->updates = $_updates;
        
        if($_lead->start instanceof Zend_Date) {
            $view->start = $_lead->start->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->start = '-';
        }
        
        if($_lead->end instanceof Zend_Date) {
            $view->leadEnd = $_lead->end->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
        } else {
            $view->leadEnd = '-';
        }
        
        if($_lead->end_scheduled instanceof Zend_Date) {
            $view->ScheduledEnd = $_lead->end_scheduled->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale'));
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
            $pdfGenerator->generateLeadPdf($_lead);
            $pdfOutput = $pdfGenerator->render();
        } catch ( Zend_Pdf_Exception $e ) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' error creating pdf: ' . $e->__toString());
            $pdfOutput = NULL;
        }
                
        $recipients = $this->_getNotificationRecipients($_lead);
        // send notificaton to updater in any case!
        // UGH! how to find out his adb id?
        //if (! in_array($_updater->accountId, $recipients)) {
        //    $recipients[] = $_updater->accountId;
        //}
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . $plain);
        Tinebase_Notification::getInstance()->send($this->_currentAccount, $recipients, $subject, $plain, $html, $pdfOutput);
    }
    
    /**
     * returns recipients for a lead notification
     *
     * @param  Crm_Model_Lead $_lead
     * @return array          array of int|Addressbook_Model_Contact
     */
    protected function _getNotificationRecipients(Crm_Model_Lead $_lead) {
        $recipients = array();
        
        $relations = Tinebase_Relations::getInstance()->getRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $_lead->getId(), true);
        
        foreach ($relations as $relation) {
            if ($relation->related_model == 'Addressbook_Model_Contact' && $relation->type == 'RESPONSIBLE') {
                $recipients[] = $relation->related_record;
            }
        }
        
        // if no responsibles are defined, send message to all readers of container
        if (empty($recipients)) {
            Zend_Registry::get('logger')->debug(__CLASS__ . '::' . __METHOD__ . '::' . __LINE__ . ' no responsibles found for lead: ' . 
                $_lead->getId() . ' sending notification to all people having read access to container ' . $_lead->container_id);
                
            $containerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($_lead->container_id);
            // NOTE: we just send notifications to users, not to groups or anyones!
            foreach ($containerGrants as $grant) {
                if ($grant['account_type'] == 'user' && $grant['readGrant'] == 1) {
                    $recipients[] = $grant['account_id'];
                }
            }
        }
        
        return $recipients;
    }    
}
