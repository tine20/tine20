<?php
/**
 * Abstract record controller for Tine 2.0 applications
 * 
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add (empty) sendNotifications()
 * @todo        add rights to check to generic functions (do that before extending this class in admin controllers)
 */

/**
 * abstract record controller class for Tine 2.0 applications
 * 
 * @package     Tinebase
 * @subpackage  Controller
 */
abstract class Tinebase_Controller_Record_Abstract extends Tinebase_Controller_Abstract implements Tinebase_Controller_Record_Interface
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
     * delete or just set is_delete=1 if record is going to be deleted
     * - legacy code -> remove that when all backends/applications are using the history logging
     *
     * @var boolean
     */
    protected $_purgeRecords = TRUE;
    
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
    
    /*********** get / search / count leads **************/
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        $this->_checkFilterACL($_filter);
        
        $result = $this->_backend->search($_filter, $_pagination, $_onlyIds);
        
        if ($_getRelations) {
            $result->setByIndices('relations', Tinebase_Relations::getInstance()->getMultipleRelations($this->_modelName, $this->_backend->getType(), $result->getId()));
        }
        
        return $result;    
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter) 
    {
        $this->_checkFilterACL($_filter);

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
     * @todo    add get relations ?
     */
    public function get($_id, $_containerId = NULL)
    {
        if (!$_id) { // yes, we mean 0, null, false, ''
            $record = new $this->_modelName(array(), true);
            
            if ($this->_doContainerACLChecks) {
                if ($_containerId === NULL) {
                    $containers = Tinebase_Container::getInstance()->getPersonalContainer($this->_currentAccount, $this->_applicationName, $this->_currentAccount, Tinebase_Model_Container::GRANT_ADD);
                    $record->container_id = $containers[0]->getId();
                } else {
                    $record->container_id = $_containerId;
                }
            }
            
        } else {
            $record = $this->_backend->get($_id);
            
            $this->_checkGrant($record, 'get');
            
            // get tags / notes / relations
            if ($record->has('tags')) {
                Tinebase_Tags::getInstance()->getTagsOfRecord($record);
            }            
            if ($record->has('notes')) {
                $record->notes = Tinebase_Notes::getInstance()->getNotesOfRecord($this->_modelName, $record->getId());
            }        
            if ($record->has('relations')) {
                $record->relations = Tinebase_Relations::getInstance()->getRelations($this->_modelName, $this->_backend->getType(), $record->getId());
            }            
        }
        
        return $record;    
    }
    
    /**
     * Returns a set of leads identified by their id's
     * 
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet of $this->_modelName
     */
    public function getMultiple($_ids)
    {
        $records = $this->_backend->getMultiple($_ids);
        
        foreach ($records as $record) {
            if (!$this->_checkGrant($record, 'get', FALSE)) {
                $index = $records->getIndexById($record->getId());
                unset($records[$index]);
            } 
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
        return $this->_backend->getAll($_orderBy, $_orderDirection);
    }
    
    /*************** add / update / delete lead *****************/    

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_record)
    {        
        //Tinebase_Core::getLogger()->debug(print_r($_record->toArray(),true));
        
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

            // add personal container id if container id is missing in record
            if($_record->has('container_id') && empty($_record->container_id)) {
                $containers = Tinebase_Container::getInstance()->getPersonalContainer($this->_currentAccount, $this->_applicationName, $this->_currentAccount, Tinebase_Model_Container::GRANT_ADD);
                $_record->container_id = $containers[0]->getId();
            }            
            
            if(!$_record->isValid()) {
                throw new Tinebase_Exception_Record_Validation('Record is not valid. Invalid fields: ' . print_r($_record->getValidationErrors(), true));
            }
            
            $this->_checkGrant($_record, 'create');
        
            // add modlog info
            if ($_record->has('created_by')) {
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($_record, 'create');
            }
            
            $this->_inspectCreate($_record);
            $record = $this->_backend->create($_record);
            
            // set relations / tags / notes
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
            
            if ($this->_sendNotifications) {
                $this->sendNotifications($record, $this->_currentAccount, 'created');  
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
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
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            if(!$_record->isValid()) {
                throw new Tinebase_Exception_Record_Validation('Record is not valid.');
            }
            $currentRecord = $this->_backend->get($_record->getId());
            
            // ACL checks
            if ($currentRecord->has('container_id') && $currentRecord->container_id != $_record->container_id) {                
                $this->_checkGrant($_record, 'create');
                // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
                $this->_checkGrant($currentRecord, 'delete');
            } else {
                $this->_checkGrant($_record, 'update', TRUE, 'No permission to update record.', $currentRecord);
            }
    
            // concurrency management & history log
            if ($_record->has('created_by')) {
                $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
                $modLog->manageConcurrentUpdates($_record, $currentRecord, $this->_modelName, $this->_backend->getType(), $_record->getId());
                $modLog->setRecordMetaData($_record, 'update', $currentRecord);
                $currentMods = $modLog->writeModLog($_record, $currentRecord, $this->_modelName, $this->_backend->getType(), $_record->getId());
            }
            
            $this->_inspectUpdate($_record, $currentRecord);
            $record = $this->_backend->update($_record);
    
            // set relations & tags & notes
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
            
            // send notifications
            if ($this->_sendNotifications && $record->has('created_by') && count($currentMods) > 0) {
                $this->sendNotifications($record, $this->_currentAccount, 'changed', $currentMods);
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
     * @param   Tinebase_Record_Interface $_record          the update record
     * @param   Tinebase_Record_Interface $_currentRecord   the current persistent record
     * @return  void
     */
    protected function _inspectUpdate($_record, $_currentRecord)
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
        $this->_checkFilterACL($_filter);
        
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
     * @return  void
     * @throws Tinebase_Exception_NotFound|Tinebase_Exception
     * 
     * @todo check container grants!!
     */
    public function delete($_ids)
    {
        if ($_ids instanceof $this->_modelName) {
            $_ids = $_ids->getId();
        }
        
        $records = $this->_backend->getMultiple((array)$_ids);
        if (count((array)$_ids) != count($records)) {
            //throw new Tinebase_Exception_NotFound('Error, only ' . count($records) . ' of ' . count((array)$_ids) . ' records exist');
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Only ' . count($records) . ' of ' . count((array)$_ids) . ' records exist.');
        }
                    
        try {        
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            foreach ($records as $record) {
                $this->_deleteRecord($record);
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($e->getMessage(), true));
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($e->getTraceAsString(), true));
            throw new Tinebase_Exception($e->getMessage());
        }                        
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
        if (    !$this->_doContainerACLChecks 
            ||  !$_record->has('container_id') 
            // admin grant includes all others
            ||  $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADMIN)) {
            return TRUE;
        }

        $hasGrant = FALSE;
        
        switch ($_action) {
            case 'get':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_READ);
                break;
            case 'create':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADD);
                break;
            case 'update':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_EDIT);
                break;
            case 'delete':
                $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
                $hasGrant = (
                    $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_DELETE)
                    && $container->type != Tinebase_Model_Container::TYPE_INTERNAL
                );
                break;
        }
        
        if (!$hasGrant) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            } else {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 'No permissions to ' . $_action . ' in container ' . $_record->container_id);
            }
        }
        
        return $hasGrant;
    }

    /**
     * Removes containers where current user has no access to
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    protected function _checkFilterACL(/*Tinebase_Model_Filter_FilterGroup */$_filter, $_action = 'get')
    {
        if ($this->_doContainerACLChecks) {
            $containerFilter = $_filter->getAclFilter();
            
            if (! $containerFilter) {
                // force a $containerFilter filter (ACL)
                $containerFilter = $_filter->createFilter('container_id', 'specialNode', 'all', array('applicationName' => $this->_applicationName));
                $_filter->addFilter($containerFilter);
            }
            
            // do something like that
            switch ($_action) {
                case 'get':
                    $containerFilter->setRequiredGrants(array(
                        Tinebase_Model_Container::GRANT_READ,
                        Tinebase_Model_Container::GRANT_ADMIN,
                        //Tinebase_Model_Container::GRANT_ANY
                    ));
                    break;
                case 'update':
                    $containerFilter->setRequiredGrants(array(
                        Tinebase_Model_Container::GRANT_EDIT,
                        Tinebase_Model_Container::GRANT_ADMIN,
                        //Tinebase_Model_Container::GRANT_ANY
                    ));
                    break;
                default:
                    throw new Tinebase_Exception_UnexpectedValue('Unknown action: ' . $_action);
            }
            
        
            /*
            $containerProperty = 'container_id';
    
            if (! array_key_exists($containerProperty, $_filter->getFilterModel())) {
                $_filter->addFilter(new Tinebase_Model_Filter_Container($containerProperty, 'specialNode', 'all', array('applicationName' => $this->_applicationName)));
            }
            */
        }
    }     

}
