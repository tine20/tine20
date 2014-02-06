<?php
/**
 * Abstract record controller for records with grants
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
abstract class Tinebase_Controller_Record_Grants extends Tinebase_Controller_Record_Abstract
{
   /**
     * record grants backend class
     *
     * @var Tinebase_Backend_Sql_Grants
     */
    protected $_grantsBackend;

   /**
     * record grants model class
     *
     * @var string
     */
    protected $_grantsModel;
    
    /**
     * check grant for action (CRUD)
     *
     * @param Tinebase_Record_Interface $record
     * @param string $action
     * @param boolean $throw
     * @param string $errorMessage
     * @param Tinebase_Record_Interface $oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     * 
     * @todo allow to skip this (ignoreACL)
     */
    protected function _checkGrant($record, $action, $throw = true, $errorMessage = 'No Permission.', $oldRecord = null)
    {
        $hasGrant = parent::_checkGrant($record, $action, $throw, $errorMessage, $oldRecord);
        
        if (! $record->getId() || $action === 'create') {
            // no record based grants for new records
            return $hasGrant;
        }
        
        $recordForGrantsCheck = $oldRecord ? $oldRecord : $record;
        
        if (empty($recordForGrantsCheck->grants)) {
            $this->_getGrants($recordForGrantsCheck);
        }
        
        // @todo switch to TRACE
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Checked record (incl. grants): ' . print_r($recordForGrantsCheck->toArray(), true));
        
        switch ($action) {
            case 'get':
                $hasGrant = $this->hasGrant($recordForGrantsCheck, Tinebase_Model_Grants::GRANT_READ);
                break;
            case 'update':
                $hasGrant = $this->hasGrant($recordForGrantsCheck, Tinebase_Model_Grants::GRANT_EDIT);
                break;
            case 'delete':
                $hasGrant = $this->hasGrant($recordForGrantsCheck, Tinebase_Model_Grants::GRANT_DELETE);
                break;
        }
        
        if (! $hasGrant) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' No permissions to ' . $action . ' record.');
            if ($throw) {
                throw new Tinebase_Exception_AccessDenied($errorMessage);
            }
        }
        
        return $hasGrant;
    }
    
    /**
     * checks if user has grant for record
     * 
     * @param Tinebase_Record_Interface $record
     * @param string $grant
     * @return boolean
     */
    public function hasGrant($record, $grant)
    {
        if (empty($record->grants)) {
            return false;
        }
        
        foreach ($record->grants as $grantRecord) {
            if ($grantRecord->userHasGrant($grant)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * set relations / tags / alarms / grants
     * 
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   boolean $returnUpdatedRelatedData
     * @return  Tinebase_Record_Interface
     */
    protected function _setRelatedData($updatedRecord, $record, $returnUpdatedRelatedData = false)
    {
        $updatedRecord->grants = $record->grants;
        $this->_setGrants($updatedRecord);
        
        return parent::_setRelatedData($updatedRecord, $record, $returnUpdatedRelatedData);
    }
    
    /**
     * set grants of record
     * 
     * @param Tinebase_Record_Abstract $record
     * @param $boolean $addDuringSetup -> let admin group have all rights instead of user
     * @return Tinebase_Record_RecordSet of record grants
     * 
     * @todo improve algorithm: only update/insert/delete changed grants
     */
    protected function _setGrants($record, $addDuringSetup = false)
    {
        $recordId = $record->getId();
        
        if (empty($recordId)) {
            throw new Timetracker_Exception_UnexpectedValue('record id required to set grants');
        }
        
        // @todo always add default grants? we should add a check for "valid" grants: one "edit" grant should always exist.
        if (empty($record->grants)) {
            $this->_addDefaultGrants($record, $addDuringSetup);
        }
        
        try {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Setting grants for record ' . $recordId);
            
            // @todo switch to TRACE
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Grants: ' . print_r($record->grants->toArray(), true));
            
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            $this->_grantsBackend->deleteByProperty($recordId, 'record_id');
            
            foreach ($record->grants as $newGrant) {
                foreach (call_user_func($this->_grantsModel . '::getAllGrants') as $grant) {
                    if ($newGrant->{$grant}) {
                        $newGrant->account_grant = $grant;
                        $newGrant->record_id = $recordId;
                        $this->_grantsBackend->create($newGrant);
                    }
                }
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            // @todo add caching?
            //$this->_clearCache();
            
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw new Tinebase_Exception_Backend($e->getMessage());
        }
        
        return $record->grants;
    }

    /**
     * add default grants
     * 
     * @param   Tinebase_Record_Interface $record
     * @param   $boolean $addDuringSetup -> let admin group have all rights instead of user
     */
    protected function _addDefaultGrants($record, $addDuringSetup = false)
    {
        $record->grants = new Tinebase_Record_RecordSet($this->_grantsModel);
        $availableGrants = call_user_func($this->_grantsModel . '::getAllGrants');
        
        if ($addDuringSetup) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Set all available grants for filter ' . $record->name . ' for admin group');
            
            $allGrants = array(
                'account_id'       => Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId(),
                'account_type'     => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            );
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Set all available grants for filter ' . $record->name . '  for current user');
            
            $allGrants = array(
                'account_id'       => Tinebase_Core::getUser()->getId(),
                'account_type'     => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            );
        }
        $allGrants['record_id'] = $record->getId();
        foreach ($availableGrants as $grant) {
            $allGrants[$grant] = true;
        }
        $record->grants->addRecord(new Tinebase_Model_PersistentFilterGrant($allGrants));
        
        if (    $record->account_id === null 
            && ! Tinebase_Config::getInstance()->get(Tinebase_Config::ANYONE_ACCOUNT_DISABLED, false) 
             && in_array(Tinebase_Model_Grants::GRANT_READ, $availableGrants)
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Set read grant for anyone');
            
            $record->grants->addRecord(new Tinebase_Model_PersistentFilterGrant, array(
                'account_id'       => 0,
                'account_type'     => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                'record_id'        => $record->getId(),
                Tinebase_Model_Grants::GRANT_READ   => true,
            ));
        }
    }
    
    /**
     * this function creates a new record with default grants during inital setup
     * 
     * TODO  think about adding a ignoreAcl, ignoreModlog param to normal create()
     *   OR    allow add setup user that can do everything
     *   OR    add helper function to disable all ACL and user stuff
     * 
     * @param Tinebase_Record_Interface $record
     * @return  Tinebase_Record_Interface
     */
    public function createDuringSetup(Tinebase_Record_Interface $record)
    {
        $createdRecord = $this->_backend->create($record);
        $createdRecord->grants = $record->grants;
        $this->_setGrants($createdRecord, /* addDuringSetup = */ true);
        return $createdRecord;
    }
    
    /**
     * add related data / grants to record
     * 
     * @param Tinebase_Record_Interface $record
     */
    protected function _getRelatedData($record)
    {
        parent::_getRelatedData($record);
        
        if (empty($record->grants)) {
            // grants may have already been fetched
            $this->_getGrants($record);
        }
    }
    
    /**
     * get record grants
     * 
     * @param Tinebase_Record_Abstract|Tinebase_Record_RecordSet $records
     */
    protected function _getGrants($records)
    {
        $recordset = ($records instanceof Tinebase_Record_Abstract) ? new Tinebase_Record_RecordSet($this->_modelName, array($records)) : $records;
        
        // @todo switch to TRACE
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Get grants for ' . count($recordset). ' records.');
        
        $this->_grantsBackend->getGrantsForRecords($recordset);
    }
}
