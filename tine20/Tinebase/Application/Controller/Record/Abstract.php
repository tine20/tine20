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
 */

/**
 * abstract record controller class for Tine 2.0 applications
 * 
 * @package     Tinebase
 * @subpackage  Controller
 */
abstract class Tinebase_Application_Controller_Record_Abstract extends Tinebase_Application_Controller_Abstract
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

    /*********** get / search / count leads **************/
    
    /**
     * get list of records
     *
     * @param Tinebase_Record_Interface|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Record_Interface $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL)
    {
        if ($this->_doContainerACLChecks) {
            $this->_checkContainerACL($_filter);
        }
        
        $result = $this->_backend->search($_filter, $_pagination);
        
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
     * @return Tinebase_Record_RecordSet
     * @throws  Tinebase_Exception_Record_Validation
     * 
     * @todo    add get relations ?
     */
    public function get($_id)
    {
        $record = $this->_backend->get($_id);
        
        if ($this->_doContainerACLChecks && !$this->_currentAccount->hasGrant($record->container_id, Tinebase_Model_Container::GRANT_READ)) {
            throw new Tinebase_Exception_AccessDenied('Read permission to record denied.');
        }
        
        return $record;    
    }
    
    /**
     * Returns a set of leads identified by their id's
     * 
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet of $this->_modelName
     */
    public function getMultiple($_identifiers)
    {
        $records = $this->_backend->getMultiple($_identifiers);
        
        foreach ($records as $record) {
            if (!$this->_currentAccount->hasGrant($record->container_id, Tinebase_Model_Container::GRANT_READ)) {
                $index = $records->getIndexById($record->getId());
                unset($records[$index]);
            } 
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
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_record)
    {        
        try {
            $db = $this->_backend->getDb();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
                        
            if(!$_record->isValid()) {
                throw new Tinebase_Exception_Record_Validation('Record is not valid.');
            }
            
            if($this->_doContainerACLChecks && !$this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADD)) {
                throw new Tinebase_Exception_AccessDenied('Write access to records in container ' . $_record->container_id . ' denied.');
            }
        
            // add modlog info
            if ($_record->has('created_by')) {
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($_record, 'create');
            }
            
            $record = $this->_backend->create($_record);
            
            // set relations
            if ($record->has('relations') && isset($_record->relations) && is_array($_record->relations)) {
                Tinebase_Relations::getInstance()->setRelations($this->_modelName, $this->_backend->getType(), $record->getId(), $_record->relations);
            }        
            
            // add tags
            if ($record->has('tags') && !empty($_record->tags)) {
                $record->tags = $_record->tags;
                Tinebase_Tags::getInstance()->setTagsOfRecord($record);
            }        
    
            // add notes
            if ($record->has('notes') && isset($_record->notes)) {
                $record->notes = $_record->notes;
                Tinebase_Notes::getInstance()->setNotesOfRecord($record);
            }
                    
            // add created note to record
            if ($record->has('notes')) {
                Tinebase_Notes::getInstance()->addSystemNote($record, $this->_currentAccount->getId(), 'created');
            }
            
            //$this->sendNotifications($lead, $this->_currentAccount, 'created');
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
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
    
            if ($record->has('notes') && isset($_record->notes)) {
                Tinebase_Notes::getInstance()->setNotesOfRecord($_record);
            }        
            
            // add changed note to record
            if ($record->has('created_by') && count($currentMods) > 0) {
                Tinebase_Notes::getInstance()->addSystemNote($record, $this->_currentAccount->getId(), 'changed', $currentMods);
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
     */
    public function delete($_identifiers)
    {
        $records = $this->_backend->getMultiple((array)$_identifiers);
        if (count((array)$_identifiers) != count($records)) {
            throw new Tinebase_Exception_NotFound('Error, only ' . count($records) . ' of ' . count((array)$_identifiers) . ' records exist');
        }
                    
        try {        
            $db = $this->_backend->getDb();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            foreach ($records as $record) {
                if ($this->_doContainerACLChecks && !$this->_currentAccount->hasGrant($record->container_id, Tinebase_Model_Container::GRANT_DELETE)) {
                    throw new Tinebase_Exception_AccessDenied('Delete access in container ' . $record->container_id . ' denied.');
                }
                $this->_backend->delete($record);
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
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
        $readableContainer = $this->_currentAccount->getContainerByACL($this->_applicationName, Tinebase_Model_Container::GRANT_READ);
        $_filter->container = array_intersect($_filter->container, $readableContainer->getArrayOfIds());
    }     
}
