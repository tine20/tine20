<?php
/**
 * Abstract record controller for Tine 2.0 applications
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * clear customfields cache on create / update
     * 
     * @var boolean
     */
    protected $_clearCustomFieldsCache = FALSE;
    
    /**
     * Do we update relation to this record
     * 
     * @var boolean
     */
    protected $_doRelationUpdate = TRUE;
    
    /**
     * Do we force sent modlog for this record
     * 
     * @var boolean
     */
    protected $_doForceModlogInfo = FALSE;

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
     * duplicate check fields / if this is NULL -> no duplicate check
     *
     * @var array
     */
    protected $_duplicateCheckFields = NULL;

    /**
     * holds new relation on update multiple
     * @var array
     */
    protected $_newRelations = NULL;
    
    /**
     * holds relations to remove on update multiple
     * @var array
     */
    protected $_removeRelations = NULL;
    
    /**
     * result of updateMultiple function
     * 
     * @var array
     */
    protected $_updateMultipleResult = array();

    /**
     * should each record be validated in updateMultiple 
     * - FALSE: only the first record is validated with the incoming data
     *
     * @var boolean
     */
    protected $_updateMultipleValidateEachRecord = FALSE;

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
    
    /**
     * returns backend for this controller
     * 
     */
    public function getBackend()
    {
        return $this->_backend;
    }

    /*********** get / search / count **************/

    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param boolean|array $_getRelations
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
                // if getRelations is true, all relations should be fetched
                if ($_getRelations === TRUE) {
                    $_getRelations = NULL;
                }
                $result->setByIndices('relations', Tinebase_Relations::getInstance()->getMultipleRelations($this->_modelName, $this->_getBackendType(), $result->getId(), NULL, array(), FALSE, $_getRelations));
            }
            if ($this->resolveCustomfields()) {
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
        $value = (func_num_args() === 1) ? (bool) func_get_arg(0) : NULL;
        return $this->_setBooleanMemberVar('_sendNotifications', $value);
    }
    
    /**
     * set/get a boolean member var
     * 
     * @param string $name
     * @param boolean $value
     * @return boolean
     */
    protected function _setBooleanMemberVar($name, $value = NULL)
    {
        $currValue = $this->{$name};
        if ($value !== NULL) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Resetting ' . $name . ' to ' . (int) $value);
            $this->{$name} = $value;
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
        $value = (func_num_args() === 1) ? (bool) func_get_arg(0) : NULL;
        return $this->_setBooleanMemberVar('_purgeRecords', $value);
    }

    /**
     * set/get checking ACL rights
     *
     * @param  boolean optional
     * @return boolean
     */
    public function doContainerACLChecks()
    {
        $value = (func_num_args() === 1) ? (bool) func_get_arg(0) : NULL;
        return $this->_setBooleanMemberVar('_doContainerACLChecks', $value);
    }
    
    /**
     * set/get resolving of customfields
     *
     * @param  boolean optional
     * @return boolean
     */
    public function resolveCustomfields()
    {
        $value = (func_num_args() === 1) ? (bool) func_get_arg(0) : NULL;
        $currentValue = ($this->_setBooleanMemberVar('_resolveCustomFields', $value) 
            && Tinebase_CustomField::getInstance()->appHasCustomFields($this->_applicationName, $this->_modelName));
        return $currentValue;
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
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Resetting modlog active to ' . (int) $paramValue);
            $this->_backend->setModlogActive($paramValue);
            $this->_omitModLog = ! $paramValue;
        }

        return $currValue;
    }

    /**
     * set/get relation update
     *
     * @param  boolean optional
     * @return boolean
     */
    public function doRelationUpdate()
    {
        $value = (func_num_args() === 1) ? (bool) func_get_arg(0) : NULL;
        return $this->_setBooleanMemberVar('_doRelationUpdate', $value);
    }
    
    /**
     * set/get force modlog info
     *
     * @param  boolean optional
     * @return boolean
     */
    public function doForceModlogInfo()
    {
        $value = (func_num_args() === 1) ? (bool) func_get_arg(0) : NULL;
        return $this->_setBooleanMemberVar('_doForceModlogInfo', $value);
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @param bool         $_getRelatedData
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_AccessDenied
     */
    public function get($_id, $_containerId = NULL, $_getRelatedData = TRUE)
    {
        $this->_checkRight('get');
        
        if (! $_id) { // yes, we mean 0, null, false, ''
            $record = new $this->_modelName(array(), true);
            
            if ($this->_doContainerACLChecks) {
                if ($_containerId === NULL) {
                    $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_ADD);
                    $record->container_id = $containers[0]->getId();
                } else {
                    $record->container_id = $_containerId;
                }
            }
            
        } else {
            $record = $this->_backend->get($_id);
            $this->_checkGrant($record, 'get');
            
            // get related data only on request (defaults to TRUE)
            if ($_getRelatedData) {
                $this->_getRelatedData($record);
                
                if ($record->has('notes')) {
                    $record->notes = Tinebase_Notes::getInstance()->getNotesOfRecord($this->_modelName, $record->getId());
                }
            }
        }
        
        return $record;
    }
    
    /**
     * check if record with given $id exists
     * 
     * @param string $id
     * @return boolean
     */
    public function exists($id)
    {
        $this->_checkRight('get');
        
        try {
            $record = $this->_backend->get($id);
            $result = $this->_checkGrant($record, 'get', FALSE);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $result = FALSE;
        }
        
        return $result;
    }
    
    /**
     * add related data to record
     * 
     * @param Tinebase_Record_Interface $record
     */
    protected function _getRelatedData($record)
    {
        if ($record->has('tags')) {
            Tinebase_Tags::getInstance()->getTagsOfRecord($record);
        }
        if ($record->has('relations')) {
            $record->relations = Tinebase_Relations::getInstance()->getRelations($this->_modelName, $this->_getBackendType(), $record->getId());
        }
        if ($record->has('alarms')) {
            $this->getAlarms($record);
        }
        if ($this->resolveCustomfields()) {
            Tinebase_CustomField::getInstance()->resolveRecordCustomFields($record);
        }
        if ($record->has('attachments') && Setup_Controller::getInstance()->isFilesystemAvailable()) {
            Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($record);
        }
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
               Tinebase_Core::getUser(),
               $this->_applicationName,
               Tinebase_Model_Grants::GRANT_READ,
               TRUE)
           : NULL;
        $records = $this->_backend->getMultiple($_ids, $containerIds);

        if ($this->resolveCustomfields()) {
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

        if ($this->resolveCustomfields()) {
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

        $db = (method_exists($this->_backend, 'getAdapter')) ? $this->_backend->getAdapter() : Tinebase_Core::getDb();
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

            // add personal container id if container id is missing in record
            if ($_record->has('container_id') && empty($_record->container_id)) {
                $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_ADD);
                $_record->container_id = $containers[0]->getId();
            }

            $_record->isValid(TRUE);

            $this->_checkGrant($_record, 'create');

            // added _doForceModlogInfo behavior
            if ($_record->has('created_by')) {
                $origRecord = clone ($_record);
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($_record, 'create');
                $this->_forceModlogInfo($_record, $origRecord, 'create');
            }

            $this->_inspectBeforeCreate($_record);
            if ($_duplicateCheck) {
                $this->_duplicateCheck($_record);
            }
            $createdRecord = $this->_backend->create($_record);
            $this->_inspectAfterCreate($createdRecord, $_record);
            $this->_setRelatedData($createdRecord, $_record, TRUE);
            $this->_setNotes($createdRecord, $_record);

            if ($this->sendNotifications()) {
                $this->doSendNotifications($createdRecord, Tinebase_Core::getUser(), 'created');
            }
            
            $this->_increaseContainerContentSequence($createdRecord, Tinebase_Model_ContainerContent::ACTION_CREATE);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        } catch (Exception $e) {
            $this->_handleRecordCreateOrUpdateException($e);
        }

        if ($this->_clearCustomFieldsCache) {
            Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
        }

        return $this->get($createdRecord);
    }
    
    /**
     * handle record exception
     * 
     * @param Exception $e
     * @throws Exception
     * 
     * @todo invent hooking mechanism for database/backend independant exception handling (like lock timeouts)
     */
    protected function _handleRecordCreateOrUpdateException(Exception $e)
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
        
        if ($e instanceof Zend_Db_Statement_Exception && preg_match('/Lock wait timeout exceeded/', $e->getMessage())) {
            throw new Tinebase_Exception_Backend_Database_LockTimeout($e->getMessage());
        }
        
        throw $e;
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

        $duplicates = $this->search($duplicateFilter, new Tinebase_Model_Pagination(array('limit' => 5)));

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
     * increase container content sequence
     * 
     * @param Tinebase_Record_Interface $_record
     * @param string $action
     */
    protected function _increaseContainerContentSequence(Tinebase_Record_Interface $record, $action = NULL)
    {
        if ($record->has('container_id')) {
            Tinebase_Container::getInstance()->increaseContentSequence($record->container_id, $action, $record->getId());
        }
    }
    
    /**
     * Force modlog info if set
     *  
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_origRecord
     * @param string $_action
     * @return  void
     */
    protected function _forceModlogInfo(Tinebase_Record_Interface $_record, Tinebase_Record_Interface $_origRecord, $_action = NULL)
    {
        if ($this->_doForceModlogInfo && ! empty($_origRecord)) {
            // on create
            if ($_action == 'create') {
                if (! empty($_origRecord->created_by)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Force modlog - created_by: ' . $_origRecord->created_by);
                    $_record->created_by = $_origRecord->created_by;
                }
                if (! empty($_origRecord->creation_time)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Force modlog - creation_time: ' . $_origRecord->creation_time);
                    $_record->creation_time = $_origRecord->creation_time;
                }
                if (! empty($_origRecord->last_modified_by)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Force modlog - last_modified_by: ' . $_origRecord->last_modified_by);
                    $_record->last_modified_by = $_origRecord->last_modified_by;
                }
                if (! empty($_origRecord->last_modified_time)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Force modlog - last_modified_time: ' . $_origRecord->last_modified_time);
                    $_record->last_modified_time = $_origRecord->last_modified_time;
                }
            }
            
            // on update
            if ($_action == 'update') {
                if (! empty($_origRecord->last_modified_by)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Force modlog - last_modified_by: ' . $_origRecord->last_modified_by);
                    $_record->last_modified_by = $_origRecord->last_modified_by;
                }
                if (! empty($_origRecord->last_modified_time)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Force modlog - last_modified_time: ' . $_origRecord->last_modified_time);
                    $_record->last_modified_time = $_origRecord->last_modified_time;
                }
            }
        }   
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * 
     * @todo    fix duplicate check on update / merge needs to remove the changed record / ux discussion
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = TRUE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
            . ' Record to update: ' . print_r($_record->toArray(), TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Update ' . $this->_modelName);

        $db = (method_exists($this->_backend, 'getAdapter')) ? $this->_backend->getAdapter() : Tinebase_Core::getDb();

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

            $_record->isValid(TRUE);
            $currentRecord = $this->get($_record->getId());
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Current record: ' . print_r($currentRecord->toArray(), TRUE));

        // add _doForceModlogInfo behavior
            $origRecord = clone ($_record);
            $this->_updateACLCheck($_record, $currentRecord);
            $this->_concurrencyManagement($_record, $currentRecord);
            $this->_forceModlogInfo($_record, $origRecord, 'update');
            $this->_inspectBeforeUpdate($_record, $currentRecord);
            
            // NOTE removed the duplicate check because we can not remove the changed record yet
//             if ($_duplicateCheck) {
//                 $this->_duplicateCheck($_record);
//             }
            
            $updatedRecord = $this->_backend->update($_record);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Updated record: ' . print_r($updatedRecord->toArray(), TRUE));
            
            $this->_inspectAfterUpdate($updatedRecord, $_record, $currentRecord);
            $updatedRecordWithRelatedData = $this->_setRelatedData($updatedRecord, $_record, TRUE);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Updated record with related data: ' . print_r($updatedRecordWithRelatedData->toArray(), TRUE));
            
            $currentMods = $this->_writeModLog($updatedRecordWithRelatedData, $currentRecord);
            $this->_setNotes($updatedRecordWithRelatedData, $_record, Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $currentMods);
            
            if ($this->_sendNotifications && count($currentMods) > 0) {
                $this->doSendNotifications($updatedRecordWithRelatedData, Tinebase_Core::getUser(), 'changed', $currentRecord);
            }
            
            if ($_record->has('container_id') && $currentRecord->container_id !== $updatedRecord->container_id) {
                $this->_increaseContainerContentSequence($currentRecord, Tinebase_Model_ContainerContent::ACTION_DELETE);
                $this->_increaseContainerContentSequence($updatedRecord, Tinebase_Model_ContainerContent::ACTION_CREATE);
            } else {
                $this->_increaseContainerContentSequence($updatedRecord, Tinebase_Model_ContainerContent::ACTION_UPDATE);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        } catch (Exception $e) {
            $this->_handleRecordCreateOrUpdateException($e);
        }

        if ($this->_clearCustomFieldsCache) {
            Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
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
        $modLog->manageConcurrentUpdates($_record, $_currentRecord);
        $modLog->setRecordMetaData($_record, 'update', $_currentRecord);
    }
    
    /**
     * get backend type
     * 
     * @return string
     */
    protected function _getBackendType()
    {
        $type = (method_exists( $this->_backend, 'getType')) ? $this->_backend->getType() : 'Sql';
        return $type;
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
            . ' Writing modlog for ' . get_class($_newRecord));
    
        $currentMods = Tinebase_Timemachine_ModificationLog::getInstance()->writeModLog($_newRecord, $_oldRecord, $this->_modelName, $this->_getBackendType(), $_newRecord->getId());
        
        return $currentMods;
    }
    
    /**
     * set relations / tags / alarms
     * 
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   boolean $returnUpdatedRelatedData
     * @return  Tinebase_Record_Interface
     */
    protected function _setRelatedData($updatedRecord, $record, $returnUpdatedRelatedData = FALSE)
    {
        if ($record->has('relations') && isset($record->relations) && is_array($record->relations)) {
            $type = $this->_getBackendType();
            Tinebase_Relations::getInstance()->setRelations($this->_modelName, $type, $updatedRecord->getId(), $record->relations);
        }
        if ($record->has('tags') && isset($record->tags) && (is_array($record->tags) || $record->tags instanceof Tinebase_Record_RecordSet)) {
            $updatedRecord->tags = $record->tags;
            Tinebase_Tags::getInstance()->setTagsOfRecord($updatedRecord);
        }
        if ($record->has('alarms') && isset($record->alarms)) {
            $this->_saveAlarms($record);
        }
        if ($record->has('attachments') && isset($record->attachments) && Setup_Controller::getInstance()->isFilesystemAvailable()) {
            $updatedRecord->attachments = $record->attachments;
            Tinebase_FileSystem_RecordAttachments::getInstance()->setRecordAttachments($updatedRecord);
        }
        
        if ($returnUpdatedRelatedData) {
            $this->_getRelatedData($updatedRecord);
        }
        
        return $updatedRecord;
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
        Tinebase_Notes::getInstance()->addSystemNote($_updatedRecord, Tinebase_Core::getUser(), $_systemNoteType, $_currentMods);
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
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
    }

    /**
     * update modlog / metadata / add systemnote for multiple records defined by filter
     * 
     * NOTE: this should be done in a transaction because of the concurrency handling as
     *  we want the same seq in the record and in the modlog
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
        
        if ($this->_omitModLog !== TRUE) {
            $recordSeqs = $this->_backend->getPropertyByIds($ids, 'seq');
            
            list($currentAccountId, $currentTime) = Tinebase_Timemachine_ModificationLog::getCurrentAccountIdAndTime();
            $updateMetaData = array(
                'last_modified_by'   => $currentAccountId,
                'last_modified_time' => $currentTime,
                'seq'                => new Zend_Db_Expr('seq + 1'),
                'recordSeqs'         => $recordSeqs, // is not written to DB yet
            );
        } else {
            $updateMetaData = array();
        }
        
        $this->_backend->updateMultiple($ids, $updateMetaData);
        
        if ($this->_omitModLog !== TRUE && is_object(Tinebase_Core::getUser())) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Writing modlog for ' . count($ids) . ' records ...');
            
            $currentMods = Tinebase_Timemachine_ModificationLog::getInstance()->writeModLogMultiple($ids, $_oldData, $_newData, $this->_modelName, $this->_getBackendType(), $updateMetaData);
            Tinebase_Notes::getInstance()->addMultipleModificationSystemNotes($currentMods, $currentAccountId, $this->_modelName);
        }
    }
    
    /**
     * handles relations on update multiple
     * @param string $key
     * @param string $value
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    protected function _handleRelations($key, $value)
    {
        $model = new $this->_modelName;
        $relConfig = $model::getRelatableConfig();
        unset($model);
        $getRelations = true;
        preg_match('/%(.+)-((.+)_Model_(.+))/', $key, $a);
        if(count($a) < 4) {
            throw new Tinebase_Exception_Record_DefinitionFailure('The relation to delete/set is not configured properly!');
        } 
        // TODO: check config from foreign side
        // $relConfig = $a[2]::getRelatableConfig();

        $constrainsConfig = false;
        foreach($relConfig as $config) {
            if($config['relatedApp'] == $a[3] && $config['relatedModel'] == $a[4] && (isset($config['config']) || array_key_exists('config', $config)) && is_array($config['config'])) {
                foreach($config['config'] as $constrain) {
                    if($constrain['type'] == $a[1]) {
                        $constrainsConfig = $constrain;
                        break 2; 
                    }
                }
            }
        }

        if(!$constrainsConfig) {
            throw new Tinebase_Exception_Record_DefinitionFailure('No relation definition could be found for this model!');
        }

        $rel = array(
            'own_model' => $this->_modelName,
            'own_backend' => 'Sql',
            'own_degree' =>(isset($constrainsConfig['sibling']) || array_key_exists('sibling', $constrainsConfig)) ? $constrainsConfig['sibling'] : 'sibling',
            'related_model' => $a[2],
            'related_backend' => 'Sql',
            'type' => (isset($constrainsConfig['type']) || array_key_exists('type', $constrainsConfig)) ? $constrainsConfig['type'] : '-',
            'remark' => (isset($constrainsConfig['defaultRemark']) || array_key_exists('defaultRemark', $constrainsConfig)) ? $constrainsConfig['defaultRemark'] : ' '
        );
        
        if(empty($value)) { // delete relations in iterator
            if(!$this->_removeRelations) $this->removeRelations = array();
            $this->_removeRelations[] = $rel;
        } else { // create relations in iterator
            if(! $this->_newRelations) $this->_newRelations = array();
            $rel['related_id'] = $value;
            $this->_newRelations[] = $rel;
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
        $getRelations = false;
        
        $this->_newRelations = NULL;
        $this->_removeRelations = NULL;
        
        foreach ($_data as $key => $value) {
            if (stristr($key,'#')) {
                $_data['customfields'][substr($key,1)] = $value;
                unset($_data[$key]);
            }
            if (stristr($key, '%')) {
                $getRelations = true;
                $this->_handleRelations($key, $value);
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
            'options'    => array('getRelations' => $getRelations),
            'function'   => 'processUpdateMultipleIteration',
        ));
        $result = $iterator->iterate($_data);
    
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updated ' . $this->_updateMultipleResult['totalcount'] . ' records.');
        
        if ($this->_clearCustomFieldsCache) {
            Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
        }
        
        return $this->_updateMultipleResult;
    }
    
    /**
     * iterate relations
     * 
     * @param Tinebase_Record_Abstract $currentRecord
     * @return array
     */
    protected function _iterateRelations($currentRecord)
    {
        if(! $currentRecord->relations || get_class($currentRecord->relations) != 'Tinebase_Record_RecordSet') {
            $currentRecord->relations = new Tinebase_Record_RecordSet('Tinebase_Model_Relation');
        }
        
        // handle relations to remove
        if($this->_removeRelations) {
            if($currentRecord->relations->count()) {
                foreach($this->_removeRelations as $remRelation) {
                    $removeRelations = $currentRecord->relations->filter('type', $remRelation['type']);
                    $removeRelations = $removeRelations->filter('related_model', $remRelation['related_model']);
                    $removeRelations = $removeRelations->filter('own_degree', $remRelation['own_degree']);
                    $currentRecord->relations->removeRecords($removeRelations);
                }
            }
        }
        
        // handle new relations
        if($this->_newRelations) {
            $removeRelations = NULL;
            foreach($this->_newRelations as $newRelation) {
                $removeRelations = $currentRecord->relations->filter('type', $newRelation['type']);
                $removeRelations = $removeRelations->filter('related_model', $newRelation['related_model']);
                $removeRelations = $removeRelations->filter('own_degree', $newRelation['own_degree']);
                $already = $removeRelations->filter('related_id', $newRelation['related_id']);
                if($already->count() > 0) {
                    $removeRelations = NULL;
                } else {
                    $newRelation['own_id'] = $currentRecord->getId();
                    $rel = new Tinebase_Model_Relation();
                    $rel->setFromArray($newRelation);
                    if($removeRelations) $currentRecord->relations->removeRecords($removeRelations);
                    $currentRecord->relations->addRecord($rel);
                }
            }
        }
        return $currentRecord->relations->toArray();
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
        $bypassFilters = FALSE;
        foreach ($_records as $currentRecord) {
            $oldRecordArray = $currentRecord->toArray();
            unset($oldRecordArray['relations']);
            
            $data = array_merge($oldRecordArray, $_data);
            
            if($this->_newRelations || $this->_removeRelations) {
                $data['relations'] = $this->_iterateRelations($currentRecord);
            }
            try {
                $record = new $this->_modelName($data, $bypassFilters);
                $updatedRecord = $this->update($record, FALSE);
                
                $this->_updateMultipleResult['results']->addRecord($updatedRecord);
                $this->_updateMultipleResult['totalcount'] ++;
                
            } catch (Tinebase_Exception_Record_Validation $e) {
                if ($this->_updateMultipleValidateEachRecord === FALSE) {
                    throw $e;
                }
                $this->_updateMultipleResult['exceptions']->addRecord(new Tinebase_Model_UpdateMultipleException(array(
                    'id'         => $currentRecord->getId(),
                    'exception'  => $e,
                    'record'     => $currentRecord,
                    'code'       => $e->getCode(),
                    'message'    => $e->getMessage()
                )));
                $this->_updateMultipleResult['failcount'] ++;
            }
            if ($this->_updateMultipleValidateEachRecord === FALSE) {
                // only validate the first record
                $bypassFilters = TRUE;
            }
        }
    }
    
    /**
     * Deletes a set of records.
     *
     * If one of the records could not be deleted, no record is deleted
     *
     * @param   array $_ids array of record identifiers
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
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Only ' . count($records) . ' of ' . count((array)$ids) . ' records exist.');
        }
        
        if (empty($records)) {
            return $records;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Deleting ' . count($records) . ' of ' . $this->_modelName . ' records ...');

        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            $this->_checkRight('delete');

            foreach ($records as $record) {
                $this->_deleteRecord($record);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

            // send notifications
            if ($this->sendNotifications()) {
                foreach ($records as $record) {
                    $this->doSendNotifications($record, Tinebase_Core::getUser(), 'deleted');
                }
            }

        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . print_r($e->getMessage(), true));
            throw $e;
        }
        
        if ($this->_clearCustomFieldsCache) {
             Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
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
        $deletedRecords = $this->delete($ids);
        
        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);

        return $deletedRecords;
    }

    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     */
    protected function _inspectDelete(array $_ids)
    {
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
            if (! Tinebase_Core::getUser()->hasGrant($targetContainerId, Tinebase_Model_Grants::GRANT_ADD)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Permission denied to add records to container.');
                throw new Tinebase_Exception_AccessDenied('You are not allowed to move records to this container');
            }
            
            // check delete grant in source container
            $containerIdsWithDeleteGrant = Tinebase_Container::getInstance()->getContainerByACL(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Model_Grants::GRANT_DELETE, TRUE);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Containers with delete grant: ' . print_r($containerIdsWithDeleteGrant, true));
            foreach ($records as $index => $record) {
                if (! in_array($record->{$_containerProperty}, $containerIdsWithDeleteGrant)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' Permission denied to remove record ' . $record->getId() . ' from container ' . $record->{$_containerProperty}
                    );
                    unset($records[$index]);
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Moving ' . count($records) . ' ' . $this->_modelName . '(s) to container ' . $targetContainerId);
        
        // move (update container id)
        $idsToMove = $records->getArrayOfIds();
        $filterClass = $this->_modelName . 'Filter';
        if (! class_exists($filterClass)) {
            throw new Tinebase_Exception_NotFound('Filter class ' . $filterClass . ' not found!');
        }
        $filter = new $filterClass(array(
            array('field' => 'id', 'operator' => 'in', 'value' => $idsToMove)
        ));
        $updateResult = $this->updateMultiple($filter, array(
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

        if (! $this->_purgeRecords && $_record->has('created_by')) {
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($_record, 'delete', $_record);
            $this->_backend->update($_record);
        } else {
            $this->_backend->delete($_record);
        }
        
        $this->_increaseContainerContentSequence($_record, Tinebase_Model_ContainerContent::ACTION_DELETE);
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
            Tinebase_Notes::getInstance()->deleteNotesOfRecord($this->_modelName, $this->_getBackendType(), $_record->getId());
        }
        if ($_record->has('relations')) {
            $relations = Tinebase_Relations::getInstance()->getRelations($this->_modelName, $this->_getBackendType(), $_record->getId());
            if (!empty($relations)) {
                // remove relations
                Tinebase_Relations::getInstance()->setRelations($this->_modelName, $this->_getBackendType(), $_record->getId(), array());

                // remove related objects
                if (!empty($this->_relatedObjectsToDelete)) {
                    foreach ($relations as $relation) {
                        if (in_array($relation->related_model, $this->_relatedObjectsToDelete)) {
                            list($appName, $i, $itemName) = explode('_', $relation->related_model);
                            $appController = Tinebase_Core::getApplicationInstance($appName, $itemName);

                            try {
                                $appController->delete($relation->related_id);
                            } catch (Exception $e) {
                                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Error deleting: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }
        if ($_record->has('attachments') && Setup_Controller::getInstance()->isFilesystemAvailable()) {
            Tinebase_FileSystem_RecordAttachments::getInstance()->deleteRecordAttachments($_record);
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
        if (   ! $this->_doContainerACLChecks
            || ! $_record->has('container_id')) {
            return TRUE;
        }
        
        if (! is_object(Tinebase_Core::getUser())) {
            throw new Tinebase_Exception_AccessDenied('User object required to check grants');
        }
        
        // admin grant includes all others
        if (Tinebase_Core::getUser()->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_ADMIN)) {
            return TRUE;
        }
        
        $hasGrant = FALSE;
        
        switch ($_action) {
            case 'get':
                $hasGrant = Tinebase_Core::getUser()->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_READ);
                break;
            case 'create':
                $hasGrant = Tinebase_Core::getUser()->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_ADD);
                break;
            case 'update':
                $hasGrant = Tinebase_Core::getUser()->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_EDIT);
                break;
            case 'delete':
                $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
                $hasGrant = Tinebase_Core::getUser()->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_DELETE);
                break;
        }

        if (!$hasGrant) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' No permissions to ' . $_action . ' in container ' . $_record->container_id);
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
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
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' Container ACL disabled for ' . $_filter->getModelName() . '.');
            return TRUE;
        }

        $aclFilters = $_filter->getAclFilters();

        if (! $aclFilters) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Force a standard containerFilter (specialNode = all) as ACL filter.');
            
            $containerFilter = $_filter->createFilter('container_id', 'specialNode', 'all', array('applicationName' => $_filter->getApplicationName()));
            $_filter->addFilter($containerFilter);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Setting filter grants for action ' . $_action);
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
            . " About to save " . count($alarms) . " alarms for {$_record->id} ");
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
            if (count($alarms) === 0) {
                $record->alarms = $alarms;
                continue;
            }

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
    

    /**
     * creates dependent records after creating the parent record
     *
     * @param Tinebase_Record_Interface $_createdRecord
     * @param Tinebase_Record_Interface $_record
     * @param string $_property
     * @param array $_fieldConfig
     */
    protected function _createDependentRecords(Tinebase_Record_Interface $_createdRecord, Tinebase_Record_Interface $_record, $_property, $_fieldConfig)
    {
        if (! (isset($_fieldConfig['dependentRecords']) || array_key_exists('dependentRecords', $_fieldConfig)) || ! $_fieldConfig['dependentRecords']) {
            return;
        }
        
        if ($_record->has($_property) && $_record->{$_property}) {
            $recordClassName = $_fieldConfig['recordClassName'];
            $new = new Tinebase_Record_RecordSet($recordClassName);
            $ccn = $_fieldConfig['controllerClassName'];
            $controller = $ccn::getInstance();
    
            // legacy - should be already done in frontend json - remove if all record properties are record sets before getting to controller
            if (is_array($_record->{$_property})) {
                $rs = new Tinebase_Record_RecordSet($recordClassName);
                foreach ($_record->{$_property} as $recordArray) {
                    $rec = new $recordClassName(array(),true);
                    $rec->setFromJsonInUsersTimezone($recordArray);
                    $rs->addRecord($rec);
                }
                $_record->{$_property} = $rs;
            }
            // legacy end

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__. ' Creating ' . $_record->{$_property}->count() . ' dependent records on property ' . $_property . ' for ' . $this->_applicationName . ' ' . $this->_modelName);
            }
            
            foreach ($_record->{$_property} as $record) {
                $record->{$_fieldConfig['refIdField']} = $_createdRecord->getId();
                $new->add($controller->create($record));
            }
    
            $_createdRecord->{$_property} = $new->toArray();
        }
    }
    
    /**
     * updates dependent records on update the parent record
     *
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_oldRecord
     * @param string $_property
     * @param array $_fieldConfig
     */
    protected function _updateDependentRecords(Tinebase_Record_Interface $_record, Tinebase_Record_Interface $_oldRecord, $_property, $_fieldConfig)
    {
        if (! (isset($_fieldConfig['dependentRecords']) || array_key_exists('dependentRecords', $_fieldConfig)) || ! $_fieldConfig['dependentRecords']) {
            return;
        }
    
        if ($_record->has($_property)) {
    
            $ccn = $_fieldConfig['controllerClassName'];
            $controller = $ccn::getInstance();
            $recordClassName = $_fieldConfig['recordClassName'];
            $filterClassName = $_fieldConfig['filterClassName'];
            $existing = new Tinebase_Record_RecordSet($recordClassName);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' ' . print_r($_record->{$_property}, TRUE));
            
            if (! empty($_record->{$_property}) && $_record->{$_property}) {
                
                // legacy - should be already done in frontent json - remove if all record properties are record sets before getting to controller
                if (is_array($_record->{$_property})) {
                    $rs = new Tinebase_Record_RecordSet($recordClassName);
                    foreach ($_record->{$_property} as $recordArray) {
                        $rec = new $recordClassName(array(),true);
                        $rec->setFromJsonInUsersTimezone($recordArray);
                        $rs->addRecord($rec);
                    }
                    $_record->{$_property} = $rs;
                }
                
                $idProperty = $_record->{$_property}->getFirstRecord()->getIdProperty();
                
                // legacy end
                $oldFilter = new $filterClassName(array(array('field' => $idProperty, 'operator' => 'in', 'value' => $_record->{$_property}->getId())));
                $oldRecords = $controller->search($oldFilter);
                
                foreach ($_record->{$_property} as $record) {
                    $record->{$_fieldConfig['refIdField']} = $_oldRecord->getId();
                    // update record if ID exists and has a length of 40 (it has a length of 10 if it is a timestamp)
                    if ($record->getId() && strlen($record->getId()) == 40) {
                        
                        // do not try to update if the record hasn't changed
                        $oldRecord = $oldRecords->getById($record->getId());
                        
                        if (! empty($oldRecord->diff($record)->diff)) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__. ' Updating dependent record with id = "' . $record->getId() . '" on property ' . $_property . ' for ' . $this->_applicationName . ' ' . $this->_modelName);
                            }
                            $existing->addRecord($controller->update($record));
                        } else {
                            $existing->addRecord($record);
                        }
                        // create if ID does not exist or has not a length of 40
                    } else {
                        $record->id = NULL;
                        $crc = $controller->create($record);
                        $existing->addRecord($crc);
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__. ' Creating dependent record with id = "' . $crc->getId() . '" on property ' . $_property . ' for ' . $this->_applicationName . ' ' . $this->_modelName);
                        }
                    }
                }
            }
    
            $filter = new $filterClassName(isset($_fieldConfig['addFilters']) ? $_fieldConfig['addFilters'] : array(), 'AND');
            $filter->addFilter(new Tinebase_Model_Filter_Text($_fieldConfig['refIdField'], 'equals', $_record->getId()));
            $filter->addFilter(new Tinebase_Model_Filter_Id('id', 'notin', $existing->getId()));
    
            $deleteContracts = $controller->search($filter);
            
            if ($deleteContracts->count()) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__. ' Deleting dependent records with id = "' . print_r($deleteContracts->getId(), 1) . '" on property ' . $_property . ' for ' . $this->_applicationName . ' ' . $this->_modelName);
                }
                $controller->delete($deleteContracts->id);
            }
            $_record->{$_property} = $existing->toArray();
        }
    }
}
