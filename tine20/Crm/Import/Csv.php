<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Import class for the Crm
 * 
 * @package     Crm
 * @subpackage  Import
 */
class Crm_Import_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id' => '',
        'dates' => array('start','end','end_scheduled','resubmission_date'),
    );


    /**
     * constructs a new importer from given config
     *
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);

        // disable lead notifications on import
        Crm_Controller_Lead::getInstance()->sendNotifications(false);

        // get container id from default container if not set
        if (empty($this->_options['container_id'])) {
            $defaultContainer = Tinebase_Container::getInstance()->getDefaultContainer('Crm_Model_Lead');
            $this->_options['container_id'] = $defaultContainer->getId();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Setting default container id: ' . $this->_options['container_id']);
        }
    }

    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }

    /**
     * do conversions (transformations, charset, replacements ...)
     *
     * @param array $_data
     * @return array
     *
     * TODO think about moving this to import definition
     */
    protected function _doConversions($_data)
    {
        $data = parent::_doConversions($_data);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($data, true));

        // adjust lead_name/leadstate/source/types if missing
        $keyFields = array(
            Crm_Config::LEAD_STATES  => 'leadstate_id',
            Crm_Config::LEAD_TYPES   => 'leadtype_id',
            Crm_Config::LEAD_SOURCES => 'leadsource_id',
        );

        foreach ($keyFields as $keyFieldName => $fieldName) {
            $keyField = Crm_Config::getInstance()->get($keyFieldName);

            if (isset($data[$fieldName])) {
                $data[$fieldName] = $keyField->getIdByTranslatedValue($data[$fieldName]);
            } else {
                $data[$fieldName] = $keyField->getKeyfieldDefault()->getId();
            }
        }

        //if($this->_options['demoData']) $data = $this->_getDay($data, $this->_additionalOptions['dates']);

        return $data;
    }

    /**
     * do something with the imported record
     *
     * @param $importedRecord
     */
    protected function _inspectAfterImport($importedRecord)
    {
        if (Crm_Config::getInstance()->get(Crm_Config::LEAD_IMPORT_AUTOTASK) && ! $this->_options['dryrun']) {
            $this->_addLeadAutoTaskForResponsibles($importedRecord);
        }
    }

    /**
     * add auto tasks if config option is set and lead has responsible person
     *
     * @param Crm_Model_Lead $lead
     */
    protected function _addLeadAutoTaskForResponsibles(Crm_Model_Lead $lead)
    {
        $responsibles = $lead->getResponsibles();

        if (count($responsibles) === 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No responsibles found');
            return;
        }

        $translate = Tinebase_Translation::getTranslation('Crm');

        // create task (if current user has edit grant for other users default tasks container)
        $autoTask = new Tasks_Model_Task(array(
            'summary'   => $translate->_('Edit new lead'),
            'due'       => Tinebase_DateTime::now()->addHour(2),
            'status'    => 'IN-PROCESS',
            'relations' => array(array(
                'own_model'              => 'Tasks_Model_Task',
                'own_backend'            => 'Sql',
                'own_id'                 => 0,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'type'                   => 'TASK',
                'related_record'         => $lead,
                'related_id'             => $lead->getId(),
                'related_model'          => 'Crm_Model_Lead',
                'related_backend'        => 'Sql'
            )),
        ));

        foreach ($responsibles as $responsible) {
            if ($responsible->type !== Addressbook_Model_Contact::CONTACTTYPE_USER) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Responsible is no user');
                continue;
            }
            try {
                $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $responsible->account_id);
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Could not find user');
                continue;
            }

            $autoTaskForResponsible = clone($autoTask);
            $responsibleContainer = Tinebase_Container::getInstance()->getDefaultContainer('Tasks_Model_Task', $user->getId(), 'defaultTaskList');
            if (! $responsibleContainer) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Could not find default container of user with ADD grant');
                continue;
            }
            $autoTaskForResponsible->container_id = $responsibleContainer->getId();
            $autoTaskForResponsible->organizer = $responsible->account_id;
            Tasks_Controller_Task::getInstance()->create($autoTaskForResponsible);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Created auto task for user ' . $user->getId() . ' in container ' . $responsibleContainer->name);
        }
    }

    /**
     * do something after the import
     */
    protected function _afterImport()
    {
        if (Crm_Config::getInstance()->get(Crm_Config::LEAD_IMPORT_NOTIFICATION) && ! $this->_options['dryrun']) {
            $this->_sendNotificationToResponsibles();
        }
    }

    /**
     * sends a notification email to all responsibles of imported leads
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     *
     * @see TODO add issue
     */
    protected function _sendNotificationToResponsibles()
    {
        $responsibles = new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        $leadsByResponsibleId = array();
        foreach ($this->_importResult['results'] as $importedRecord) {
            // add responsibles if not already in record set and add lead to $leadsByResponsibleId
            $responsiblesForImportedLead = $importedRecord->getResponsibles();
            foreach ($responsiblesForImportedLead as $responsible) {
                if (! $responsibles->getById($responsible->getId())) {
                    $responsibles->addRecord($responsible);
                    $leadsByResponsibleId[$responsible->getId()] = new Tinebase_Record_RecordSet('Crm_Model_Lead');
                }
                $leadsByResponsibleId[$responsible->getId()]->addRecord($importedRecord);
            }
        }

        foreach ($responsibles as $responsible) {
            if (isset($leadsByResponsibleId[$responsible->getId()]))
            $this->_sendNotificationToResponsible($responsible, $leadsByResponsibleId[$responsible->getId()]);
        }
    }

    /**
     * send mail with related (imported) leads for responsibles
     *
     * @param Addressbook_Model_Contact $responsible
     * @param Tinebase_Record_RecordSet
     */
    protected function _sendNotificationToResponsible($responsible, $leads)
    {
        if (count($leads) === 0) {
            // do nothing
            return;
        }

        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views');

        $translate = Tinebase_Translation::getTranslation('Crm');

        $containerName = Tinebase_Container::getInstance()->get($leads->getFirstRecord()->container_id)->name;
        $view->lang_importedLeads = sprintf($translate->_('The following leads have just been imported into lead list %s'), $containerName);
        $view->importedLeads = $leads;

        $plain = $view->render('importNotificationPlain.php');

        $subject = sprintf($translate->_('%s new leads have been imported'), count($leads));

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . $plain);

        try {
            Tinebase_Notification::getInstance()->send(Tinebase_Core::getUser(), array($responsible), $subject, $plain);
        } catch (Exception $e) {
            Tinebase_Exception::log($e, /* $suppressTrace = */ false);
        }
    }
}
