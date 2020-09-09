<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Container Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_Container extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName       = 'Admin';
        $this->_modelName             = 'Tinebase_Model_Container';
        $this->_doContainerACLChecks  = false;
        $this->_purgeRecords          = false;
        // modlog will be written by Tinebase_Container aka the backend, disable it in Tinebase_Controller_Record_Abstract (exception: update)
        $this->_omitModLog            = true;

        // we need to avoid that anybody else gets this instance ... as it has acl turned off!
        Tinebase_Container::destroyInstance();
        $this->_backend = Tinebase_Container::getInstance();
        $this->_backend->doSearchAclFilter(false);
        // unset internal reference to prevent others to get instance without acl
        Tinebase_Container::destroyInstance();
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
     * @var Admin_Controller_Container
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Admin_Controller_Container
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_Container;
        }
        
        return self::$_instance;
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @param bool         $_getRelatedData
     * @param bool $_getDeleted
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_AccessDenied
     */
    public function get($_id, $_containerId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE)
    {
        $this->_checkRight('get');
        
        $container = $this->_backend->getContainerById($_id);
        $container->account_grants = $this->_backend->getGrantsOfContainer($container, TRUE);
        
        return $container;
    }

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        $this->_checkRight('create');

        $_record->isValid(TRUE);

        /** @var Tinebase_Model_Container$_record */
        $_record->account_grants = $this->_convertGrantsToRecordSet($_record->account_grants, $_record->getGrantClass());
        Tinebase_Container::getInstance()->checkContainerOwner($_record);

        Tinebase_Timemachine_ModificationLog::setRecordMetaData($_record, 'create');
        
        $container = $this->_backend->addContainer($_record, $_record->account_grants, TRUE);
        $container->account_grants = $this->_backend->getGrantsOfContainer($container, TRUE);
        
        return $container;
    }
    
    /**
     * convert grants to record set
     * 
     * @param Tinebase_Record_RecordSet|array $_grants
     * @param string $_grantsModel
     * @return Tinebase_Record_RecordSet
     */
    protected function _convertGrantsToRecordSet($_grants, $_grantsModel)
    {
        $result = (! $_grants instanceof Tinebase_Record_RecordSet && is_array($_grants)) 
            ? new Tinebase_Record_RecordSet($_grantsModel, $_grants)
            : $_grants;
        
        return $result;
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   array $_additionalArguments
     * @return  Tinebase_Record_Interface
     *
     * @todo use CONTEXT instead of $_additionalArguments
     */
    public function update(Tinebase_Record_Interface $_record, $_additionalArguments = array())
    {
        // do not skip modlog here because we use the abstract record controller for updates
        $this->_omitModLog = false;
        $container = parent::update($_record);
        $this->_omitModLog = true;

        if ($container->type === Tinebase_Model_Container::TYPE_PERSONAL) {
            $this->_sendNotification($container, ((isset($_additionalArguments['note']) || array_key_exists('note', $_additionalArguments))) ? $_additionalArguments['note'] : '');
        }    
        return $container;
    }
    
    /**
     * inspect update of one record (before update)
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     * @throws Tinebase_Exception_Record_NotAllowed
     * 
     * @todo if shared -> personal remove all admins except new owner
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if ($_oldRecord->application_id !== $_record->application_id) {
            throw new Tinebase_Exception_Record_NotAllowed('It is not allowed to change the application of a container.');
        }

        /** @var Tinebase_Model_Container $_record */
        $_record->account_grants = $this->_convertGrantsToRecordSet($_record->account_grants, $_record->getGrantClass());
        
        Tinebase_Container::getInstance()->checkContainerOwner($_record);
        $this->_backend->setGrants($_record, $_record->account_grants, TRUE, FALSE);
    }
    
    /**
     * send notification to owner
     * 
     * @param $container
     * @param $note
     */
    protected function _sendNotification($container, $note)
    {
        if (empty($note)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Empty note: do not send notification for container ' . $container->name);
            return;
        }
        
        $ownerId = Tinebase_Container::getInstance()->getContainerOwner($container);
        
        if ($ownerId !== FALSE) {
            try {
                $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($ownerId, TRUE);
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                    . ' Do not send notification for container ' . $container->name . ': ' . $tenf);
                return;
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Sending notification for container ' . $container->name . ' to ' . $contact->n_fn);
            
            $translate = Tinebase_Translation::getTranslation('Admin');
            $messageSubject = $translate->_('Your container has been changed');
            $messageBody = sprintf($translate->_('Your container has been changed by %1$s %2$sNote: %3$s'), Tinebase_Core::getUser()->accountDisplayName, "\n\n", $note);
            
            try {
                Tinebase_Notification::getInstance()->send(Tinebase_Core::getUser(), array($contact), $messageSubject, $messageBody);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__ . ' Could not send notification :' . $e);
            }
        }
    }
    
    /**
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet
     */
    public function delete($_ids)
    {
        $this->_checkRight('delete');
        
        $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container');
        
        foreach ($_ids as $id) {
            $containers->addRecord(Tinebase_Container::getInstance()->deleteContainer($id, true));
        }
        
        return $containers;
    }

    /**
     * set multiple container grants
     * 
     * @param Tinebase_Record_RecordSet $_containers
     * @param array|string              $_grants single or multiple grants
     * @param array|string              $_accountId single or multiple account ids
     * @param string                    $_accountType
     * @param boolean                   $_overwrite replace grants?
     */
    public function setGrantsForContainers($_containers, $_grants, $_accountId, $_accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, $_overwrite = FALSE)
    {
        $this->_checkRight('update');
        
        $accountType = ($_accountId === '0') ? Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE : $_accountType;
        $accountIds = (array) $_accountId;
        $grantsArray = ($_overwrite) ? array() : (array) $_grants;
        
        /** @var Tinebase_Model_Container $container */
        foreach ($_containers as $container) {
            foreach ($accountIds as $accountId) {
                if ($_overwrite) {
                    foreach((array) $_grants as $grant) {
                        $grantsArray[] = array(
                            'account_id'    => $accountId,
                            'account_type'  => $accountType,
                            $grant          => TRUE,
                        );
                    }
                } else {
                    Tinebase_Container::getInstance()->addGrants($container->getId(), $accountType, $accountId, $grantsArray, TRUE);
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                        . ' Added grants to container "' . $container->name . '" for userid ' . $accountId . ' (' . $accountType . ').');
                }
            }
            
            if ($_overwrite) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Set grants for container "' . $container->name . '".');
                $grants = new Tinebase_Record_RecordSet($container->getGrantClass(), $grantsArray);
                
                Tinebase_Container::getInstance()->setGrants($container, $grants, TRUE, FALSE);
            }
        }
    }
    
    /**
     * Removes containers where current user has no access to
     * -> remove timetracker containers, too (those are managed within the timetracker)
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        if ($_action == 'get') {
            $userApps = Tinebase_Core::getUser()->getApplications(TRUE);
            $filterAppIds = array();
            foreach ($userApps as $app) {
                if ($app->name !== 'Timetracker') {
                    $filterAppIds[] = $app->getId();
                }
            }
            
            $appFilter = $_filter->createFilter('application_id', 'in', $filterAppIds);
            $_filter->addFilter($appFilter);
        }
    }
    
    /**
     * check if user has the right to manage containers
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        switch ($_action) {
            case 'get':
                $this->checkRight('VIEW_CONTAINERS');
                break;
            case 'create':
            case 'update':
            case 'delete':
                $this->checkRight('MANAGE_CONTAINERS');
                break;
            default;
               break;
        }

        parent::_checkRight($_action);
    }
}
