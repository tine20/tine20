<?php
/**
 * Abstract record controller for Tine 2.0 applications
 * 
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add (empty) sendNotifications()
 */

/**
 * abstract record controller class for Tine 2.0 applications
 * 
 * @package     Tinebase
 * @subpackage  Controller
 */
abstract class Tinebase_Controller_Record_Abstract 
    extends Tinebase_Controller_Abstract 
    implements Tinebase_Controller_Record_Interface, Tinebase_Controller_SearchInterface
{
   /**
     * application backend class
     *
     * @var Tinebase_Backend_Interface
     */
    protected $_backend;

    /**
     * Model name
     *
     * @var string
     * 
     * @todo perhaps we can remove that and build model name from name of the class (replace 'Controller' with 'Model')
     */
    protected $_modelName;
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = TRUE;

    /**
     * do right checks - can be enabled/disabled by _setRightChecks
     * 
     * @var boolean
     */
    protected $_doRightChecks = TRUE;
    
    /**
     * delete or just set is_delete=1 if record is going to be deleted
     * - legacy code -> remove that when all backends/applications are using the history logging
     *
     * @var boolean
     */
    protected $_purgeRecords = TRUE;
    
    /**
     * ommit mod log for this records
     * 
     * @var boolean
     */
    protected $_ommitModLog = FALSE;
    
    /**
     * resolve customfields in search()
     *
     * @var boolean
     */
    protected $_resolveCustomFields = FALSE;
    
    /**
     * send notifications?
     * - the controller has to define a sendNotifications() function
     *
     * @var boolean
     */
    protected $_sendNotifications = FALSE;

    /**
     * if some of the relations should be deleted
     *
     * @var array
     */
    protected $_relatedObjectsToDelete = array();
    
    /**
     * record alarm field
     * 
     * @var string
     */
    protected $_recordAlarmField = 'dtstart';
    
    /**
     * @var Tinebase_Model_User
     */
    protected $_currentAccount = NULL;
    
    /**
     * returns controller for records of given model
     * 
     * @param string $_model
     */
    public static function getController($_model)
    {
        list($appName, $i, $modelName) = explode('_', $_model);
        return Tinebase_Core::getApplicationInstance($appName, $modelName);
    }
    
    /*********** get / search / count leads **************/
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
    	$this->_checkRight($_action);
        $this->checkFilterACL($_filter, $_action);
        
        $result = $this->_backend->search($_filter, $_pagination, $_onlyIds);
        
        if (! $_onlyIds) {
            if ($_getRelations) {
                $result->setByIndices('relations', Tinebase_Relations::getInstance()->getMultipleRelations($this->_modelName, $this->_backend->getType(), $result->getId()));
            }
            if ($this->_resolveCustomFields) {
                Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($result);
            }
        }
        
        return $result;    
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get') 
    {
        $this->checkFilterACL($_filter, $_action);

        $count = $this->_backend->searchCount($_filter);
        
        return $count;
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @return Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * 
     * @todo    add get customfields ?
     */
    public function get($_id, $_containerId = NULL)
    {
    	$this->_checkRight('get');
    	
        if (!$_id) { // yes, we mean 0, null, false, ''
            $record = new $this->_modelName(array(), true);
            
            if ($this->_doContainerACLChecks) {
                if ($_containerId === NULL) {
                    $containers = Tinebase_Container::getInstance()->getPersonalContainer($this->_currentAccount, $this->_applicationName, $this->_currentAccount, Tinebase_Model_Grants::GRANT_ADD);
                    $record->container_id = $containers[0]->getId();
                } else {
                    $record->container_id = $_containerId;
                }
            }
            
        } else {
            $record = $this->_backend->get($_id);
            
            $this->_checkGrant($record, 'get');
            
            // get tags / notes / relations / alarms
            if ($record->has('tags')) {
                Tinebase_Tags::getInstance()->getTagsOfRecord($record);
            }
            if ($record->has('notes')) {
                $record->notes = Tinebase_Notes::getInstance()->getNotesOfRecord($this->_modelName, $record->getId());
            }
            if ($record->has('relations')) {
                $record->relations = Tinebase_Relations::getInstance()->getRelations($this->_modelName, $this->_backend->getType(), $record->getId());
            }
            if ($record->has('alarms')) {
                $this->getAlarms($record);
            }
            
            if ($this->_resolveCustomFields) {
	            Tinebase_CustomField::getInstance()->resolveRecordCustomFields($record);
	        }   
        }
        
        return $record;    
    }
    
    /**
     * Returns a set of leads identified by their id's
     * 
     * @param   array $_ids       array of record identifiers
     * @param   bool  $_ignoreACL don't check acl grants
     * @return  Tinebase_Record_RecordSet of $this->_modelName
     */
    public function getMultiple($_ids, $_ignoreACL = FALSE)
    {
    	$this->_checkRight('get');
    	
    	// get all allowed containers and add them to getMultiple query
    	$containerIds = ($this->_doContainerACLChecks && $_ignoreACL !== TRUE) 
    	   ? Tinebase_Container::getInstance()->getContainerByACL(
    	       $this->_currentAccount, 
    	       $this->_applicationName, 
    	       Tinebase_Model_Grants::GRANT_READ,
    	       TRUE) 
    	   : NULL;
        $records = $this->_backend->getMultiple($_ids, $containerIds);
        
        if ($this->_resolveCustomFields) {
            Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($records);
        }
        
        return $records;
    }    
    
    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC') 
    {
    	$this->_checkRight('get');
        
        $records = $this->_backend->getAll($_orderBy, $_orderDirection);
        
        if ($this->_resolveCustomFields) {
            Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($records);
        }
        
        return $records;
    }
    
    /*************** add / update / delete lead *****************/    

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $this->_checkRight('create');
    	
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_record->toArray(),true));
        
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

            // add personal container id if container id is missing in record
            if($_record->has('container_id') && empty($_record->container_id)) {
                $containers = Tinebase_Container::getInstance()->getPersonalContainer($this->_currentAccount, $this->_applicationName, $this->_currentAccount, Tinebase_Model_Grants::GRANT_ADD);
                $_record->container_id = $containers[0]->getId();
            }            
            
            $_record->isValid(TRUE);
            
            $this->_checkGrant($_record, 'create');
        
            // add modlog info
            if ($_record->has('created_by')) {
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($_record, 'create');
            }
            
            $this->_inspectCreate($_record);
            $record = $this->_backend->create($_record);
            
            // set relations / tags / notes / alarms
            if ($record->has('relations') && isset($_record->relations) && is_array($_record->relations)) {
                Tinebase_Relations::getInstance()->setRelations($this->_modelName, $this->_backend->getType(), $record->getId(), $_record->relations);
            }                    
            if ($record->has('tags') && !empty($_record->tags) && is_array($_record->tags)) {
                $record->tags = $_record->tags;
                Tinebase_Tags::getInstance()->setTagsOfRecord($record);
            }        
            if ($record->has('notes')) {
                if (isset($_record->notes) && is_array($_record->notes)) {
                    $record->notes = $_record->notes;
                    Tinebase_Notes::getInstance()->setNotesOfRecord($record);
                }
                Tinebase_Notes::getInstance()->addSystemNote($record, $this->_currentAccount->getId(), 'created');                
            }
            if ($record->has('alarms') && isset($_record->alarms)) {
                $record->alarms = $_record->alarms;
                $this->_saveAlarms($record);
            }
            
            if ($this->_sendNotifications) {
                $this->sendNotifications($record, $this->_currentAccount, 'created');  
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            throw $e;
        }
        
        return $this->get($record);
    }
    
    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectCreate(Tinebase_Record_Interface $_record)
    {
        
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $_record->isValid(TRUE);
            
            $currentRecord = $this->_backend->get($_record->getId());
            
            // ACL checks
            if ($currentRecord->has('container_id') && $currentRecord->container_id != $_record->container_id) {                
                $this->_checkGrant($_record, 'create');
                $this->_checkRight('create');
                // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
                $this->_checkGrant($currentRecord, 'delete');
                $this->_checkRight('delete');
            } else {
                $this->_checkGrant($_record, 'update', TRUE, 'No permission to update record.', $currentRecord);
                $this->_checkRight('update');
            }
    
            // concurrency management & history log
            if ($_record->has('created_by')) {
                $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
                $modLog->manageConcurrentUpdates($_record, $currentRecord, $this->_modelName, $this->_backend->getType(), $_record->getId());
                $modLog->setRecordMetaData($_record, 'update', $currentRecord);
                if ($this->_ommitModLog !== TRUE) {
                    $currentMods = $modLog->writeModLog($_record, $currentRecord, $this->_modelName, $this->_backend->getType(), $_record->getId());
                } else {
                    $currentMods = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog');
                }
            }
            
            $this->_inspectUpdate($_record, $currentRecord);
            $record = $this->_backend->update($_record);
    
            // set relations / tags / notes / alarms
            if ($record->has('relations') && isset($_record->relations) && is_array($_record->relations)) {
                Tinebase_Relations::getInstance()->setRelations($this->_modelName, $this->_backend->getType(), $record->getId(), $_record->relations);
            }        
            if ($record->has('tags') && isset($_record->tags) && is_array($_record->tags)) {
                Tinebase_Tags::getInstance()->setTagsOfRecord($_record);
            }
            if ($record->has('notes')) {
                if (isset($_record->notes) && is_array($_record->notes)) {
                    Tinebase_Notes::getInstance()->setNotesOfRecord($_record);
                }
                Tinebase_Notes::getInstance()->addSystemNote($record, $this->_currentAccount->getId(), 'changed', $currentMods);
            }
            if ($record->has('alarms') && isset($_record->alarms)) {
                $this->_saveAlarms($_record);
            }
            
            // send notifications
            if ($this->_sendNotifications && $record->has('created_by') && count($currentMods) > 0) {
                $this->sendNotifications($record, $this->_currentAccount, 'changed', $currentRecord);
            }        
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        return $this->get($record->getId());
    }    
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectUpdate($_record, $_oldRecord)
    {
        
    }
    
    /**
     * update multiple records
     * 
     * @param   Tinebase_Model_Filter_FilterGroup $_filter
     * @param   array $_data
     * @return  integer number of updated records
     */
    public function updateMultiple($_filter, $_data)
    {
        $this->_checkRight('update');
        $this->checkFilterACL($_filter, 'update');
        
        // get only ids
        $ids = $this->_backend->search($_filter, NULL, TRUE);
        
        return $this->_backend->updateMultiple($ids, $_data);
    }    
    
    /**
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_NotFound|Tinebase_Exception
     */
    public function delete($_ids)
    {
        if ($_ids instanceof $this->_modelName) {
            $_ids = (array)$_ids->getId();
        }
        
        $ids = $this->_inspectDelete((array) $_ids);
        
        $records = $this->_backend->getMultiple((array)$ids);
        if (count((array)$ids) != count($records)) {
            //throw new Tinebase_Exception_NotFound('Error, only ' . count($records) . ' of ' . count((array)$ids) . ' records exist');
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Only ' . count($records) . ' of ' . count((array)$ids) . ' records exist.');
        }
                    
        try {        
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            $this->_checkRight('delete');
            
            foreach ($records as $record) {
                $this->_checkGrant($record, 'delete');
                $this->_deleteRecord($record);
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            // send notifications
            if ($this->_sendNotifications) {
                foreach ($records as $record) {
                    $this->sendNotifications($record, $this->_currentAccount, 'deleted');
                }
            }
            
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . print_r($e->getMessage(), true));
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . print_r($e->getTraceAsString(), true));
            throw $e;
        }
        
        // returns deleted records
        return $records;
    }
    
    /**
     * delete records by filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return  Tinebase_Record_RecordSet
     */
    public function deleteByFilter(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        $ids = $this->search($_filter, NULL, FALSE, TRUE);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Deleting ' . count($ids) . ' records ...');
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($ids, true));
        
        return $this->delete($ids);
    }
    
    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     */
    protected function _inspectDelete(array $_ids) {
        return $_ids;
    }
    
    /*********** helper funcs **************/
    
    /**
     * delete one record
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        $this->_checkGrant($_record, 'delete');
                
        $this->_deleteLinkedObjects($_record);
        
        if (!$this->_purgeRecords && $_record->has('created_by')) {
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($_record, 'delete', $_record);
            $this->_backend->update($_record);
        } else {
            $this->_backend->delete($_record);
        }
    }
    
    /**
     * delete linked objects (notes, relations, ...) of record
     *
     * @param Tinebase_Record_Interface $_record
     */
    protected function _deleteLinkedObjects(Tinebase_Record_Interface $_record)
    {
        // delete notes & relations
        if ($_record->has('notes')) {
            Tinebase_Notes::getInstance()->deleteNotesOfRecord($this->_modelName, $this->_backend->getType(), $_record->getId());
        }
        if ($_record->has('relations')) {
            $relations = Tinebase_Relations::getInstance()->getRelations($this->_modelName, $this->_backend->getType(), $_record->getId());
            if (!empty($relations)) {
                // remove relations
                Tinebase_Relations::getInstance()->setRelations($this->_modelName, $this->_backend->getType(), $_record->getId(), array());
                
                // remove related objects
                if (!empty($this->_relatedObjectsToDelete)) {
                    foreach ($relations as $relation) {
                        if (in_array($relation->related_model, $this->_relatedObjectsToDelete)) {
                            list($appName, $i, $itemName) = explode('_', $relation->related_model);
                            $appController = Tinebase_Core::getApplicationInstance($appName, $itemName);
                            $appController->delete($relation->related_id);
                        }
                    }                
                }
            }
        }        
    }

    /**
     * check grant for action (CRUD)
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Tinebase_Record_Interface $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     * 
     * @todo use this function in other create + update functions
     * @todo invent concept for simple adding of grants (plugins?) 
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (   !$this->_doContainerACLChecks 
            || !$_record->has('container_id') 
            // admin grant includes all others
            || $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_ADMIN)) {
            return TRUE;
        }

        $hasGrant = FALSE;
        
        switch ($_action) {
            case 'get':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_READ);
                break;
            case 'create':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_ADD);
                break;
            case 'update':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_EDIT);
                break;
            case 'delete':
                $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
                $hasGrant = (
                    $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_DELETE)
                    && $container->type != Tinebase_Model_Container::TYPE_INTERNAL
                );
                break;
        }
        
        if (!$hasGrant) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            } else {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' No permissions to ' . $_action . ' in container ' . $_record->container_id);
            }
        }
        
        return $hasGrant;
    }
    
    /**
     * overwrite this function to check rights
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        return;
    }
    
    /**
     * Removes containers where current user has no access to
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        if (! $this->_doContainerACLChecks) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Container ACL disabled for ' . $_filter->getModelName() . '.');
            return TRUE;
        }
        
        $aclFilters = $_filter->getAclFilters();
        
        if (! $aclFilters) {
            // force a standard containerFilter as ACL filter
            $containerFilter = $_filter->createFilter('container_id', 'specialNode', 'all', array('applicationName' => $_filter->getApplicationName()));
            $_filter->addFilter($containerFilter);
        }
        
        // set grants according to action
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting filter grants for action ' . $_action);
        switch ($_action) {
            case 'get':
                $_filter->setRequiredGrants(array(
                    Tinebase_Model_Grants::GRANT_READ,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            case 'update':
                $_filter->setRequiredGrants(array(
                    Tinebase_Model_Grants::GRANT_EDIT,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            case 'export':
                $_filter->setRequiredGrants(array(
                    Tinebase_Model_Grants::GRANT_EXPORT,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            case 'sync':
                $_filter->setRequiredGrants(array(
                    Tinebase_Model_Grants::GRANT_SYNC,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue('Unknown action: ' . $_action);
        }
    }     

    /**
     * saves alarms of given record
     * 
     * @param Tinebase_Record_Abstract $_record
     * @return void
     */
    protected function _saveAlarms(Tinebase_Record_Abstract $_record)
    {
        if (! $_record->alarms instanceof Tinebase_Record_RecordSet) {
            $_record->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        }
        $alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        
        // create / update alarms
        foreach ($_record->alarms as $alarm) {
            try {
                $this->_inspectAlarmSet($_record, $alarm);
                $alarms->addRecord($alarm);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $teia->getMessage());
            }
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . " About to save " . count($alarms) . " alarms for {$_record->id} " 
            //.  print_r($alarms->toArray(), true)
        );
        $_record->alarms = $alarms;
        
        Tinebase_Alarm::getInstance()->setAlarmsOfRecord($_record);
    }
    
    /**
     * inspect alarm and set time
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param Tinebase_Model_Alarm $_alarm
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _inspectAlarmSet(Tinebase_Record_Abstract $_record, Tinebase_Model_Alarm $_alarm)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Setting alarm time for ' . $this->_recordAlarmField);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' alarm data: ' . print_r($_alarm->toArray(), TRUE));
        
        // check if alarm field is Zend_Date
        if (! ($_alarm->alarm_time instanceof Zend_Date && $_alarm->minutes_before == 'custom')) {
            if ($_record->{$this->_recordAlarmField} instanceof Zend_Date && isset($_alarm->minutes_before)) {
                $_alarm->setTime($_record->{$this->_recordAlarmField});
            } else {
                throw new Tinebase_Exception_InvalidArgument('Record has no alarm field, no alarm time set or minutes before are missing.');
            }
        } else {
            // save in options that we have a custom defined datetime for the alarm
            $_alarm->options = Zend_Json::encode(array(
                'custom'         => TRUE,
            ));
        }
    }
    
    /**
     * get and resolve all alarms of given record(s)
     * 
     * @param  Tinebase_Record_Interface|Tinebase_Record_RecordSet $_record
     */
    public function getAlarms($_record)
    {
        $alarms = Tinebase_Alarm::getInstance()->getAlarmsOfRecord($this->_modelName, $_record);
        
        if ($_record instanceof Tinebase_Record_RecordSet) {
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Resolving alarms and add them to record set.");
            
            $alarms->addIndices(array('record_id'));
            foreach ($_record as $record) {
                
                $record->alarms = $alarms->filter('record_id', $record->getId());
                
                // calc minutes_before
                if ($record->has($this->_recordAlarmField)) {
                    $this->_inspectAlarmGet($record);
                }
            }
            
        } else if ($_record instanceof Tinebase_Record_Interface) {
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Resolving alarms and add them to record.");
            
            $_record->alarms = $alarms;

            // calc minutes_before
            if ($_record->has($this->_recordAlarmField)) {
                $this->_inspectAlarmGet($_record);
            }
        }
    }

    /**
     * inspect alarms of record (all alarms minutes_before fields are set here by default)
     * 
     * @param Tinebase_Record_Abstract $_record
     * @return void
     */
    protected function _inspectAlarmGet(Tinebase_Record_Abstract $_record)
    {
        if ($_record->{$this->_recordAlarmField} instanceof Zend_Date) {
            $_record->alarms->setMinutesBefore($_record->{$this->_recordAlarmField});
        }
    }
    
    /**
     * delete alarms for records
     *
     * @param array $_recordIds
     */
    protected function _deleteAlarmsForIds($_recordIds)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " Deleting alarms for records " . print_r($_recordIds, TRUE)
        );
        
        Tinebase_Alarm::getInstance()->deleteAlarmsOfRecord($this->_modelName, $_recordIds);
    }

    /**
     * enable / disable right checks
     * 
     * @param boolean $_value
     * @return void
     */
    protected function _setRightChecks($_value)
    {
        $this->_doRightChecks = (bool) $_value;
    }
    
    /**
     * convert input to recordset
     * 
     * input can have the following datatypes:
     * - Tinebase_Model_Filter_FilterGroup
     * - Tinebase_Record_RecordSet
     * - Tinebase_Record_Abstract
     * - string (single id)
     * - array (multiple ids)
     * 
     * @param mixed $_mixed
     * @param boolean $_refresh if this is TRUE, refresh the recordset by calling getMultiple
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _convertToRecordSet($_mixed, $_refresh = FALSE)
    {
        if ($_mixed instanceof Tinebase_Model_Filter_FilterGroup) {
            $result = $this->search($_mixed);
        } elseif ($_mixed instanceof Tinebase_Record_RecordSet) {
            $result = ($_refresh) ? $this->_backend->getMultiple($_mixed->getArrayOfIds()) : $_mixed;
        } elseif ($_mixed instanceof Tinebase_Record_Abstract) {
            $result = $this->_backend->getMultiple($_mixed->getId());
        } elseif (is_string($_mixed) || is_array($_mixed)) {
            $result = $this->_backend->getMultiple($_mixed);
        } else {
            throw new Tinebase_Exception_InvalidArgument('Wrong type.');
        }
        
        return $result;
    }
}
