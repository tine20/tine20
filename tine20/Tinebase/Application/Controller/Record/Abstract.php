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
abstract class Tinebase_Application_Controller_Record_Abstract extends Tinebase_Application_Controller_Abstract implements Tinebase_Application_Controller_Record_Interface
{
   /**
     * application backend class
     *
     * @var Tinebase_Application_Backend_Interface
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
     * @param Tinebase_Record_Interface|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Record_Interface $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE)
    {
        if ($this->_doContainerACLChecks) {
            $this->_checkContainerACL($_filter);
        }
        
        $result = $this->_backend->search($_filter, $_pagination);
        
        if ($_getRelations) {
            $result->setByIndices('relations', Tinebase_Relations::getInstance()->getMultipleRelations($this->_modelName, $this->_backend->getType(), $result->getId()));
        }
        
        return $result;    
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Record_Interface $_filter
     * @return int
     */
    public function searchCount(Tinebase_Record_Interface $_filter) 
    {
        if ($this->_doContainerACLChecks) {
            $this->_checkContainerACL($_filter);
        }

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
            
            if ($this->_doContainerACLChecks && !$this->_currentAccount->hasGrant($record->container_id, Tinebase_Model_Container::GRANT_READ)) {
                throw new Tinebase_Exception_AccessDenied('Read access to record denied.');
            }
            
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
            if (!$this->_currentAccount->hasGrant($record->container_id, Tinebase_Model_Container::GRANT_READ)) {
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
            $db = $this->_backend->getDb();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

            // add personal container id if container id is missing in record
            if($_record->has('container_id') && empty($_record->container_id)) {
                $containers = Tinebase_Container::getInstance()->getPersonalContainer($this->_currentAccount, $this->_applicationName, $this->_currentAccount, Tinebase_Model_Container::GRANT_ADD);
                $_record->container_id = $containers[0]->getId();
            }            
            
            if(!$_record->isValid()) {
                throw new Tinebase_Exception_Record_Validation('Record is not valid. Invalid fields: ' . print_r($_record->getValidationErrors(), true));
            }
            
            if($this->_doContainerACLChecks && !$this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADD)) {
                throw new Tinebase_Exception_AccessDenied('Write access to records in container ' . $_record->container_id . ' denied.');
            }
        
            // add modlog info
            if ($_record->has('created_by')) {
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($_record, 'create');
            }
            
            $record = $this->_backend->create($_record);
            
            // set relations / tags / notes
            if ($record->has('relations') && isset($_record->relations) && is_array($_record->relations)) {
                Tinebase_Relations::getInstance()->setRelations($this->_modelName, $this->_backend->getType(), $record->getId(), $_record->relations);
            }                    
            if ($record->has('tags') && !empty($_record->tags)) {
                $record->tags = $_record->tags;
                Tinebase_Tags::getInstance()->setTagsOfRecord($record);
            }        
            if ($record->has('notes')) {
                if (isset($_record->notes)) {
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
            $db = $this->_backend->getDb();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            if(!$_record->isValid()) {
                throw new Tinebase_Exception_Record_Validation('Record is not valid.');
            }
            $currentRecord = $this->_backend->get($_record->getId());
            
            // ACL checks
            if ($this->_doContainerACLChecks) {                
                if ($currentRecord->container_id != $_record->container_id) {
                    if (! $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADD)) {
                        throw new Tinebase_Exception_AccessDenied('Write access in container ' . $_record->container_id . ' denied.');
                    }
                    // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
                    if (! $this->_currentAccount->hasGrant($currentRecord->container_id, Tinebase_Model_Container::GRANT_DELETE)) {
                        throw new Tinebase_Exception_AccessDenied('Delete access in container ' . $currentRecord->container_id . ' denied.');
                    }
                } elseif (! $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_EDIT)) {
                    throw new Tinebase_Exception_AccessDenied('Edit access in container ' . $_record->container_id . ' denied.');
                }
            }
    
            // concurrency management & history log
            if ($_record->has('created_by')) {
                $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
                $modLog->manageConcurrentUpdates($_record, $currentRecord, $this->_modelName, $this->_backend->getType(), $_record->getId());
                $modLog->setRecordMetaData($_record, 'update', $currentRecord);
                $currentMods = $modLog->writeModLog($_record, $currentRecord, $this->_modelName, $this->_backend->getType(), $_record->getId());
            }
            
            $record = $this->_backend->update($_record);
    
            // set relations & tags & notes
            if ($record->has('relations') && isset($_record->relations) && is_array($_record->relations)) {
                Tinebase_Relations::getInstance()->setRelations($this->_modelName, $this->_backend->getType(), $record->getId(), $_record->relations);
            }        
            if ($record->has('tags') && isset($_record->tags)) {
                Tinebase_Tags::getInstance()->setTagsOfRecord($_record);
            }
            if ($record->has('notes')) {
                if (isset($_record->notes)) {
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
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array array of record identifiers
     * @return  void
     * @throws Tinebase_Exception_NotFound|Tinebase_Exception
     */
    public function delete($_ids)
    {
        if ($_ids instanceof $this->_modelName) {
            $_ids = $_ids->getId();
        }
        
        $records = $this->_backend->getMultiple((array)$_ids);
        if (count((array)$_ids) != count($records)) {
            throw new Tinebase_Exception_NotFound('Error, only ' . count($records) . ' of ' . count((array)$_ids) . ' records exist');
        }
                    
        try {        
            $db = $this->_backend->getDb();
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
     * Removes containers where current user has no access to
     * 
     * @param Tinebase_Record_Interface $_filter
     * @return void
     */
    protected function _checkContainerACL($_filter)
    {
        $readableContainerIds = $this->_currentAccount->getContainerByACL($this->_applicationName, Tinebase_Model_Container::GRANT_READ, TRUE);
        $_filter->container = array_intersect($_filter->container, $readableContainerIds);
    }     

    /**
     * delete one recod
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        if ($_record->has('container_id')) {
            $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
        }
        
        if (!$this->_doContainerACLChecks 
            || !$_record->has('container_id')
            || ($this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_DELETE 
            && $container->type != Tinebase_Model_Container::TYPE_INTERNAL))) {
                
            if (!$this->_purgeRecords && $_record->has('created_by')) {
                Tinebase_Timemachine_Modificationlog::setRecordMetaData($_record, 'delete', $_record);
                $this->_backend->update($_record);
            } else {
                $this->_backend->delete($_record);
            }

            $this->_deleteLinkedObjects($_record);
            
        } else {
            throw new Tinebase_Exception_AccessDenied('Delete access in container ' . $_record->container_id . ' denied.');
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
}
