<?php
/**
 * Abstract record controller for Tine 2.0 applications
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        this should be splitted into smaller parts!
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
     * @var Tinebase_Backend_Sql_Interface
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
     * check for container ACLs
     *
     * @var boolean
     *
     * @todo rename to containerACLChecks
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
     * omit mod log for this records
     *
     * @var boolean
     */
    protected $_omitModLog = FALSE;

    /**
     * resolve customfields in search()
     *
     * @var boolean
     */
    protected $_resolveCustomFields = FALSE;

    /**
     * send notifications?
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
     * the current user
     *
     * @var Tinebase_Model_User
     */
    protected $_currentAccount = NULL;

    /**
     * duplicate check fields / if this is NULL -> no duplicate check
     *
     * @var array
     */
    protected $_duplicateCheckFields = NULL;
    
    /**
     * result of updateMultiple function
     * 
     * @var array
     */
    protected $_updateMultipleResult = array();

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

    /*********** get / search / count **************/

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
        $this->_addDefaultFilter($_filter);

        $result = $this->_backend->search($_filter, $_pagination, $_onlyIds);

        if (! $_onlyIds) {
            if ($_getRelations) {
                $result->setByIndices('relations', Tinebase_Relations::getInstance()->getMultipleRelations($this->_modelName, $this->_backend->getType(), $result->getId()));
            }
            if ($this->_doResolveCustomfields()) {
                Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($result);
            }
        }

        return $result;
    }
    
    /**
     * you can define default filters here
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     */
    protected function _addDefaultFilter(Tinebase_Model_Filter_FilterGroup $_filter = NULL)
    {
        
    }

    /**
     * do customfields of record(s) need to be resolved?
     *
     * @return boolean
     */
    protected function _doResolveCustomfields()
    {
        return ($this->_resolveCustomFields && Tinebase_CustomField::getInstance()->appHasCustomFields($this->_applicationName));
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
     * set/get the sendNotifications state
     *
     * @param  boolean optional
     * @return boolean
     */
    public function sendNotifications()
    {
        $currValue = $this->_sendNotifications;
        if (func_num_args() === 1) {
            $paramValue = (bool) func_get_arg(0);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Resetting sendNotifications to ' . (int) $paramValue);
            $this->_sendNotifications = $paramValue;
        }

        return $currValue;
    }

    /**
     * set/get purging of record when deleting
     *
     * @param  boolean optional
     * @return boolean
     */
    public function purgeRecords()
    {
        $currValue = $this->_purgeRecords;
        if (func_num_args() === 1) {
            $paramValue = (bool) func_get_arg(0);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Resetting purgeRecords to ' . (int) $paramValue);
            $this->_purgeRecords = $paramValue;
        }

        return $currValue;
    }

    /**
     * set/get checking ACL rights
     *
     * @param  boolean optional
     * @return boolean
     */
    public function doContainerACLChecks()
    {
        $currValue = $this->_doContainerACLChecks;
        if (func_num_args() === 1) {
            $paramValue = (bool) func_get_arg(0);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Resetting doContainerACLChecks to ' . (int) $paramValue);
            $this->_doContainerACLChecks = $paramValue;
        }

        return $currValue;
    }

	/**
     * set/get modlog active
     *
     * @param  boolean optional
     * @return boolean
     */
    public function modlogActive()
    {
    	if (! $this->_backend) {
    		throw new Tinebase_Exception_NotFound('Backend not defined');
    	}

		$currValue = $this->_backend->getModlogActive();
        if (func_num_args() === 1) {
            $paramValue = (bool) func_get_arg(0);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Resetting modlog active to ' . (int) $paramValue);
            $this->_backend->setModlogActive($paramValue);
        }

        return $currValue;
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @return Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
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

            if ($this->_doResolveCustomfields()) {
	            Tinebase_CustomField::getInstance()->resolveRecordCustomFields($record);
	        }
        }

        return $record;
    }

    /**
     * Returns a set of records identified by their id's
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

        if ($this->_doResolveCustomfields()) {
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

        if ($this->_doResolveCustomfields()) {
            Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($records);
        }

        return $records;
    }

    /*************** add / update / delete / move *****************/

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = TRUE)
    {
        $this->_checkRight('create');

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
            . print_r($_record->toArray(),true));
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Create new ' . $this->_modelName);

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

            if ($_record->has('created_by')) {
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($_record, 'create');
            }

            $this->_inspectBeforeCreate($_record);
            if ($_duplicateCheck) {
                $this->_duplicateCheck($_record);
            }
            $createdRecord = $this->_backend->create($_record);
            $this->_inspectAfterCreate($createdRecord, $_record);
            $this->_setRelatedData($createdRecord, $_record);
            $this->_setNotes($createdRecord, $_record);

            if ($this->sendNotifications()) {
                $this->doSendNotifications($createdRecord, $this->_currentAccount, 'created');
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            throw $e;
        }

        return $this->get($createdRecord);
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {

    }

    /**
     * do duplicate check (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     * @throws Tinebase_Exception_Duplicate
     */
    protected function _duplicateCheck(Tinebase_Record_Interface $_record)
    {
        $duplicateFilter = $this->_getDuplicateFilter($_record);

        if ($duplicateFilter === NULL) {
            return;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Doing duplicate check.');

        $duplicates = $this->search($duplicateFilter, new Tasks_Model_Pagination(array('limit' => 5)));

        if (count($duplicates) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Found ' . count($duplicates) . ' duplicate(s).');

            $ted = new Tinebase_Exception_Duplicate('Duplicate record(s) found');
            $ted->setModelName($this->_modelName);
            $ted->setData($duplicates);
            $ted->setClientRecord($_record);
            throw $ted;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' No duplicates found.');
        }
    }

    /**
     * get duplicate filter
     *
     * @param Tinebase_Record_Interface $_record
     * @return Tinebase_Model_Filter_FilterGroup|NULL
     */
    protected function _getDuplicateFilter(Tinebase_Record_Interface $_record)
    {
        if (empty($this->_duplicateCheckFields)) {
            return NULL;
        }

        $filters = array();
        foreach ($this->_duplicateCheckFields as $group) {
            $addFilter = array();
            foreach ($group as $field) {
                if (! empty($_record->{$field})) {
                    $addFilter[] = array('field' => $field, 'operator' => 'equals', 'value' => $_record->{$field});
                }
            }
            if (! empty($addFilter)) {
                $filters[] = array('condition' => 'AND', 'filters' => $addFilter);
            }
        }

        if (empty($filters)) {
            return NULL;
        }

        $filterClass = $this->_modelName . 'Filter';
        $filterData = (count($filters) > 1) ? array(array('condition' => 'OR', 'filters' => $filters)) : $filters;

        // exclude own record if it has an id
        $recordId = $_record->getId();
        if (! empty($recordId)) {
            $filterData[] = array('field' => 'id', 'operator' => 'notin', 'value' => array($recordId));
        }

        $filter = new $filterClass($filterData);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($filter->toArray(), TRUE));
        
        return $filter;
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {

    }

    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = TRUE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
            . ' Record to update: ' . print_r($_record->toArray(), TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Update ' . $this->_modelName);

        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

            $_record->isValid(TRUE);

            $currentRecord = $this->get($_record->getId());
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Current record: ' . print_r($currentRecord->toArray(), TRUE));

            $this->_updateACLCheck($_record, $currentRecord);
            $this->_concurrencyManagement($_record, $currentRecord);
            $this->_inspectBeforeUpdate($_record, $currentRecord);
            
            if ($_duplicateCheck) {
                $this->_duplicateCheck($_record);
            }
            
            $updatedRecord = $this->_backend->update($_record);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Updated record: ' . print_r($updatedRecord->toArray(), TRUE));
            
            $this->_inspectAfterUpdate($updatedRecord, $_record);
            $this->_setRelatedData($updatedRecord, $_record);

            $updatedRecordWithRelatedData = $this->get($_record->getId());
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Updated record with related data: ' . print_r($updatedRecordWithRelatedData->toArray(), TRUE));
            
            $currentMods = $this->_writeModLog($updatedRecordWithRelatedData, $currentRecord);
            $this->_setNotes($updatedRecordWithRelatedData, $_record, Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $currentMods);
                        
            if ($this->_sendNotifications && count($currentMods) > 0) {
                $this->doSendNotifications($updatedRecordWithRelatedData, $this->_currentAccount, 'changed', $currentRecord);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        return $this->get($updatedRecord->getId());
    }
    
    /**
     * do ACL check for update record
     * 
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_currentRecord
     */
    protected function _updateACLCheck($_record, $_currentRecord)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Doing ACL check ...');
        
        if ($_currentRecord->has('container_id') && $_currentRecord->container_id != $_record->container_id) {
            $this->_checkGrant($_record, 'create');
            $this->_checkRight('create');
            // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
            $this->_checkGrant($_currentRecord, 'delete');
            $this->_checkRight('delete');
        } else {
            $this->_checkGrant($_record, 'update', TRUE, 'No permission to update record.', $_currentRecord);
            $this->_checkRight('update');
        }
    }
    
    /**
     * concurrency management & history log
     * 
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_currentRecord
     */
    protected function _concurrencyManagement($_record, $_currentRecord)
    {
        if (! $_record->has('created_by')) {
            return NULL;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Doing concurrency check ...');

        $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
        $modLog->manageConcurrentUpdates($_record, $_currentRecord, $this->_modelName, $this->_backend->getType(), $_record->getId());
        $modLog->setRecordMetaData($_record, 'update', $_currentRecord);
    }

    /**
     * write modlog
     * 
     * @param Tinebase_Record_Interface $_newRecord
     * @param Tinebase_Record_Interface $_oldRecord
     * @return NULL|Tinebase_Record_RecordSet
     */
    protected function _writeModLog($_newRecord, $_oldRecord)
    {
        if (! $_newRecord->has('created_by') || $this->_omitModLog === TRUE) {
            return NULL;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Writing modlog');
    
        $currentMods = Tinebase_Timemachine_ModificationLog::getInstance()->writeModLog($_newRecord, $_oldRecord, $this->_modelName, $this->_backend->getType(), $_newRecord->getId());
        
        return $currentMods;
    }
    
    
    /**
     * set relations / tags / alarms
     * 
     * @param   Tinebase_Record_Interface $_updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $_record          the update record
     */
    protected function _setRelatedData($_updatedRecord, $_record)
    {
        if ($_record->has('relations') && isset($_record->relations) && is_array($_record->relations)) {
            Tinebase_Relations::getInstance()->setRelations($this->_modelName, $this->_backend->getType(), $_updatedRecord->getId(), $_record->relations);
        }
        if ($_record->has('tags') && isset($_record->tags) && (is_array($_record->tags) || $_record->tags instanceof Tinebase_Record_RecordSet)) {
            $_updatedRecord->tags = $_record->tags;
            Tinebase_Tags::getInstance()->setTagsOfRecord($_updatedRecord);
        }
        if ($_record->has('alarms') && isset($_record->alarms)) {
            $_updatedRecord->alarms = $_record->alarms;
            $this->_saveAlarms($_record);
        }
    }

    /**
     * set notes
     * 
     * @param   Tinebase_Record_Interface $_updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $_record          the update record
     * @param   string $_systemNoteType
     * @param   Tinebase_Record_RecordSet $_currentMods
     */
    protected function _setNotes($_updatedRecord, $_record, $_systemNoteType = Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED, $_currentMods = NULL)
    {
        if (! $_record->has('notes')) {
            return;
        }

        if (isset($_record->notes) && is_array($_record->notes)) {
            $_updatedRecord->notes = $_record->notes;
            Tinebase_Notes::getInstance()->setNotesOfRecord($_updatedRecord);
        }
        Tinebase_Notes::getInstance()->addSystemNote($_updatedRecord, $this->_currentAccount->getId(), $_systemNoteType, $_currentMods);
    }
    
    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {

    }

    /**
     * inspect update of one record (after update)
     *
     * @param   Tinebase_Record_Interface $_updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $_record          the update record
     * @return  void
     */
    protected function _inspectAfterUpdate($_updatedRecord, $_record)
    {

    }

    /**
     * update modlog / metadata / add systemnote for multiple records defined by filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup|array $_filterOrIds
     * @param array $_oldData
     * @param array $_newData
     */
    public function concurrencyManagementAndModlogMultiple($_filterOrIds, $_oldData, $_newData)
    {
        $ids = ($_filterOrIds instanceof Tinebase_Model_Filter_FilterGroup) ? $this->search($_filterOrIds, NULL, FALSE, TRUE, 'update') : $_filterOrIds;
        if (! is_array($ids) || count($ids) === 0) {
            return;
        }
        
        list($currentAccountId, $currentTime) = Tinebase_Timemachine_ModificationLog::getCurrentAccountIdAndTime();
        $updateMetaData = array(
            'last_modified_by'   => $currentAccountId,
            'last_modified_time' => $currentTime,
        );
        $this->_backend->updateMultiple($ids, $updateMetaData);
        
        if ($this->_omitModLog !== TRUE) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Writing modlog for ' . count($ids) . ' records ...');
            
            $currentMods = Tinebase_Timemachine_ModificationLog::getInstance()->writeModLogMultiple($ids, $_oldData, $_newData, $this->_modelName, $this->_backend->getType(), $updateMetaData);
            Tinebase_Notes::getInstance()->addMultipleModificationSystemNotes($currentMods, $currentAccountId);
        }
    }
    
    /**
     * update multiple records
     *
     * @param   Tinebase_Model_Filter_FilterGroup $_filter
     * @param   array $_data
     * @return  integer number of updated records
     * 
     * @todo add param $_returnFullResults (if false, do not return updated records in 'results')
     */
    public function updateMultiple($_filter, $_data)
    {
        $this->_checkRight('update');
        $this->checkFilterACL($_filter, 'update');

        foreach($_data as $key => $value) {
            if(stristr($key,'#')) {
                $_data['customfields'][substr($key,1)] = $value;
                unset($_data[$key]);
            }
        }
        
        $this->_updateMultipleResult = array(
            'results'           => new Tinebase_Record_RecordSet($this->_modelName),
            'exceptions'        => new Tinebase_Record_RecordSet('Tinebase_Model_UpdateMultipleException'),
            'totalcount'        => 0,
            'failcount'         => 0,
        );
        
        $iterator = new Tinebase_Record_Iterator(array(
            'iteratable' => $this,
        	'controller' => $this,
        	'filter'     => $_filter,
        	'function'	 => 'processUpdateMultipleIteration',
        ));
        $result = $iterator->iterate($_data);
    
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updated ' . $this->_updateMultipleResult['totalcount'] . ' records.');
        
        return $this->_updateMultipleResult;
    }
    
    /**
    * update multiple records in an iteration
    * @see Tinebase_Record_Iterator / self::updateMultiple()
    *
    * @param Tinebase_Record_RecordSet $_records
    * @param array $_data
    */
    public function processUpdateMultipleIteration($_records, $_data)
    {
        if (count($_records) === 0) {
            return;
        }

        foreach ($_records as $currentRecord) {
            $oldRecordArray = $currentRecord->toArray();
            $data = array_merge($oldRecordArray, $_data);
            
            try {
            	$record = new $this->_modelName($data);
            	$updatedRecord = $this->update($record, FALSE);
            	
            	$this->_updateMultipleResult['results']->addRecord($updatedRecord);
            	$this->_updateMultipleResult['totalcount'] ++;
            	
            } catch (Tinebase_Exception_Record_Validation $e) {
                $this->_updateMultipleResult['exceptions']->addRecord(new Tinebase_Model_UpdateMultipleException(array(
                    'id'         => $record->getId(),
                    'exception'  => $e,
                    'code'       => $e->getCode(),
                    'message'    => $e->getMessage()
                )));
                $this->_updateMultipleResult['failcount'] ++;
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
            if ($this->sendNotifications()) {
                foreach ($records as $record) {
                    $this->doSendNotifications($record, $this->_currentAccount, 'deleted');
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
        $oldMaxExcecutionTime = ini_get('max_execution_time');

        Tinebase_Core::setExecutionLifeTime(300); // 5 minutes

        $ids = $this->search($_filter, NULL, FALSE, TRUE);

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Deleting ' . count($ids) . ' records ...');
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($ids, true));

        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);

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

    /**
     * move records to new container / folder / whatever
     *
     * @param mixed $_records (can be record set, filter, array, string)
     * @param mixed $_target (string, container record, ...)
     * @return array
     */
    public function move($_records, $_target, $_containerProperty = 'container_id')
    {
        $records = $this->_convertToRecordSet($_records);
        $targetContainerId = ($_target instanceof Tinebase_Model_Container) ? $_target->getId() : $_target;

        if ($this->_doContainerACLChecks) {
            // check add grant in target container
            if (! $this->_currentAccount->hasGrant($targetContainerId, Tinebase_Model_Grants::GRANT_ADD)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Permission denied to add records to container.');
                throw new Tinebase_Exception_AccessDenied('You are not allowed to move records to this container');
            }

            // check delete grant in source container
            $containerIdsWithDeleteGrant = Tinebase_Container::getInstance()->getContainerByACL($this->_currentAccount, $this->_applicationName, Tinebase_Model_Grants::GRANT_DELETE, TRUE);
            foreach ($records as $index => $record) {
                if (! in_array($record->{$_containerProperty}, $containerIdsWithDeleteGrant)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' Permission denied to remove record ' . $record->getId() . ' from container ' . $record->{$_containerProperty}
                    );
                    unset($records[$index]);
                }
            }
        }

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Moving ' . count($records) . ' ' . $this->_modelName . '(s) to container ' . $targetContainerId);

        // move (update container id)
        $idsToMove = $records->getArrayOfIds();
        $filterClass = $this->_modelName . 'Filter';
        if (! class_exists($filterClass)) {
            throw new Tinebase_Exception_NotFound('Filter class ' . $filterClass . ' not found!');
        }
        $filter = new $filterClass(array(
            array('field' => 'id', 'operator' => 'in', 'value' => $idsToMove)
        ));
        $this->updateMultiple($filter, array(
            $_containerProperty => $targetContainerId
        ));

        return $idsToMove;
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
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_DELETE);
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
        if (! $_record->{$this->_recordAlarmField} instanceof DateTime) {
            throw new Tinebase_Exception_InvalidArgument('alarm reference time is not set');
        }

        $_alarm->setTime($_record->{$this->_recordAlarmField});
    }

    /**
     * get and resolve all alarms of given record(s)
     *
     * @param  Tinebase_Record_Interface|Tinebase_Record_RecordSet $_record
     */
    public function getAlarms($_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Resolving alarms and add them to record set.");
        
        $alarms = Tinebase_Alarm::getInstance()->getAlarmsOfRecord($this->_modelName, $_record);
        $records = $_record instanceof Tinebase_Record_RecordSet ? $_record : new Tinebase_Record_RecordSet($this->_modelName, array($_record));

        foreach ($records as $record) {

            $record->alarms = $alarms->filter('record_id', $record->getId());

            // calc minutes_before
            if ($record->has($this->_recordAlarmField) && $record->{$this->_recordAlarmField} instanceof DateTime) {
                $this->_inspectAlarmGet($record);
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
        $_record->alarms->setMinutesBefore($_record->{$this->_recordAlarmField});
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
     * @param Tinebase_Model_Pagination $_pagination (only valid if $_mixed instanceof Tinebase_Model_Filter_FilterGroup)
     * @return Tinebase_Record_RecordSet
     */
    protected function _convertToRecordSet($_mixed, $_refresh = FALSE, Tinebase_Model_Pagination $_pagination = NULL)
    {
        if ($_mixed instanceof Tinebase_Model_Filter_FilterGroup) {
            // FILTER (Tinebase_Model_Filter_FilterGroup)
            $result = $this->search($_mixed, $_pagination);
        } elseif ($_mixed instanceof Tinebase_Record_RecordSet) {
            // RECORDSET (Tinebase_Record_RecordSet)
            $result = ($_refresh) ? $this->_backend->getMultiple($_mixed->getArrayOfIds()) : $_mixed;
        } elseif ($_mixed instanceof Tinebase_Record_Abstract) {
            // RECORD (Tinebase_Record_Abstract)
            if ($_refresh) {
                $result = $this->_backend->getMultiple($_mixed->getId());
            } else {
                $result = new Tinebase_Record_RecordSet(get_class($_mixed), array($_mixed));
            }
        } elseif (is_string($_mixed) || is_array($_mixed)) {
            // SINGLE ID or ARRAY OF IDS
            $result = $this->_backend->getMultiple($_mixed);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Could not convert input param to RecordSet: Unsupported type: ' . gettype($_mixed));
            $result = new Tinebase_Record_RecordSet($this->_modelName);
        }

        return $result;
    }
}
