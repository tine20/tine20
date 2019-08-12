<?php
/**
 * Abstract record controller for records with grants
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo add caching? we could use the record seq for this.
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
     * @var string acl record property for join with acl table
     */
    protected $_aclIdProperty = 'id';

    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $result = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
        
        // @todo allow to configure if grants are needed
        $this->_getGrants($result);
        
        return $result;
    }
    
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
     */
    protected function _checkGrant($record, $action, $throw = true, $errorMessage = 'No Permission.', $oldRecord = null)
    {
        if (! $this->_doContainerACLChecks) {
            return TRUE;
        }

        $hasGrant = parent::_checkGrant($record, $action, $throw, $errorMessage, $oldRecord);
        
        if (! $record->getId() || $action === 'create') {
            // no record based grants for new records
            return $hasGrant;
        }
        
        // always get current record grants
        $currentRecord = $this->_backend->get($record->getId());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Checked record (incl. grants): ' . print_r($currentRecord->toArray(), true));
        
        switch ($action) {
            case 'get':
                $hasGrant = $this->hasGrant($currentRecord, Tinebase_Model_Grants::GRANT_READ);
                break;
            case 'update':
                $hasGrant = $this->hasGrant($currentRecord, Tinebase_Model_Grants::GRANT_EDIT);
                break;
            case 'delete':
                $hasGrant = $this->hasGrant($currentRecord, Tinebase_Model_Grants::GRANT_DELETE);
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
     * @param Tinebase_Model_User $account
     * @return boolean
     */
    public function hasGrant($record, $grant, Tinebase_Model_User $account = null)
    {
        // always get current grants
        $recordset = new Tinebase_Record_RecordSet($this->_modelName, array($record));
        $this->_grantsBackend->getGrantsForRecords($recordset, $this->_aclIdProperty);

        if (! empty($record->grants)) {
            /**
             * @var Tinebase_Model_Grants $grantRecord
             */
            foreach ($record->grants as $grantRecord) {
                if ($grantRecord->userHasGrant($grant, $account)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * @return string
     */
    public function getGrantsModel()
    {
        return $this->_grantsModel;
    }

    /**
     * set relations / tags / alarms / grants
     * 
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the original record if one exists
     * @param   boolean $returnUpdatedRelatedData
     * @param   boolean $isCreate
     * @return  Tinebase_Record_Interface
     */
    protected function _setRelatedData(Tinebase_Record_Interface $updatedRecord, Tinebase_Record_Interface $record, Tinebase_Record_Interface $currentRecord = null, $returnUpdatedRelatedData = false, $isCreate = false)
    {
        $updatedRecord->grants = $record->grants;
        $this->setGrants($updatedRecord);
        
        return parent::_setRelatedData($updatedRecord, $record, $currentRecord, $returnUpdatedRelatedData, $isCreate);
    }

    /**
     * set grants of record
     *
     * @param Tinebase_Record_Interface $record
     * @param bool $addDuringSetup -> let admin group have all rights instead of user
     * @return Tinebase_Record_RecordSet of record grants
     * @throws Timetracker_Exception_UnexpectedValue
     * @throws Tinebase_Exception_Backend
     *
     * @todo improve algorithm: only update/insert/delete changed grants
     */
    public function setGrants(Tinebase_Record_Interface $record, $addDuringSetup = false)
    {
        $recordId = $record->getId();
        
        if (empty($recordId)) {
            throw new Timetracker_Exception_UnexpectedValue('record id required to set grants');
        }
        
        if (! $this->_validateGrants($record)) {
            $this->_setDefaultGrants($record, $addDuringSetup);
        }
        
        try {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Setting grants for record ' . $recordId);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' Grants: ' . print_r($record->grants->toArray(), true));
            
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            $this->_grantsBackend->deleteByProperty($recordId, 'record_id');

            $uniqueGate = [];
            /** @var Tinebase_Model_Grants $newGrant */
            foreach ($record->grants as $newGrant) {
                $uniqueKey = $newGrant->account_type . $newGrant->account_id;
                if (isset($uniqueGate[$uniqueKey])) {
                    continue;
                }
                $uniqueGate[$uniqueKey] = true;
                
                foreach (call_user_func($this->_grantsModel . '::getAllGrants') as $grant) {
                    if ($newGrant->{$grant}) {
                        $newGrant->id = null;
                        $newGrant->account_grant = $grant;
                        $newGrant->record_id = $recordId;
                        $this->_grantsBackend->create($newGrant);
                    }
                }
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw new Tinebase_Exception_Backend($e->getMessage());
        }
        
        return $record->grants;
    }
    
    /**
     * check for "valid" grants: one "edit" / admin? grant should always exist.
     * 
     * -> returns false if no edit grants were found
     * 
     * @param Tinebase_Record_Interface $record
     * @return boolean
     */
    protected function _validateGrants($record)
    {
        if (empty($record->grants)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Record has no grants.');
            return false;
        }
        
        if (is_array($record->grants)) {
            $record->grants = new Tinebase_Record_RecordSet($this->_grantsModel, $record->grants);
        }
        
        $editGrants = $record->grants->filter(Tinebase_Model_Grants::GRANT_EDIT, true);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Number of edit grants: ' . count($editGrants));
        
        return (count($editGrants) > 0);
    }

    /**
     * add default grants
     * 
     * @param   Tinebase_Record_Interface $record
     * @param   boolean $addDuringSetup -> let admin group have all rights instead of user
     */
    protected function _setDefaultGrants(Tinebase_Record_Interface $record, $addDuringSetup = false)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Setting default grants ...');
        
        $record->grants = new Tinebase_Record_RecordSet($this->_grantsModel);
        /** @var Tinebase_Model_Grants $grant */
        $grant = new $this->_grantsModel(array(
            'account_type' => $addDuringSetup ? Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP : Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'record_id'    => $record->getId(),
        ));
        $grant->sanitizeAccountIdAndFillWithAllGrants();
        $record->grants->addRecord($grant);
    }

    /**
     * @param string $recordId
     */
    public function deleteGrantsOfRecord($recordId)
    {
        $this->_grantsBackend->deleteByProperty($recordId, 'record_id');
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
        $this->setGrants($createdRecord, /* addDuringSetup = */ true);
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
     * @param Tinebase_Record_Interface|Tinebase_Record_RecordSet $records
     */
    protected function _getGrants($records)
    {
        $recordset = ($records instanceof Tinebase_Record_Interface)
            ? new Tinebase_Record_RecordSet($this->_modelName, array($records))
            : ($records instanceof Tinebase_Record_RecordSet ? $records : new Tinebase_Record_RecordSet($this->_modelName, $records));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Get grants for ' . count($recordset). ' records.');
        
        $this->_grantsBackend->getGrantsForRecords($recordset, $this->_aclIdProperty);
    }

    /**
     * get grants for account
     * 
     * @param Tinebase_Model_User $user
     * @param Tinebase_Record_Interface $record
     * @return Tinebase_Model_Grants
     * 
     * @todo force refetch from db or add user param to _getGrants()?
     */
    public function getGrantsOfAccount($user, $record)
    {
        if ($user === null) {
            $user = Tinebase_Core::getUser();
        }

        if (empty($record->grants)) {
            $this->_getGrants($record);
        }

        $roleMemberships = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($user);
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($user);
        $accountGrants = new $this->_grantsModel(array(
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id'   => $user->getId(),
            'record_id'    => ($this->_aclIdProperty === 'id' ? $record->getId() : $record->{$this->_aclIdProperty}),
        ));
        if (empty($record->grants)) {
            // grants might still be empty
            return $accountGrants;
        }
        foreach ($record->grants as $grantRecord) {
            foreach (call_user_func($this->_grantsModel . '::getAllGrants') as $grant) {
                if ($grantRecord->{$grant} &&
                    (
                        $grantRecord->account_type === Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE ||
                        $grantRecord->account_type === Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP && in_array($grantRecord->account_id, $groupMemberships) ||
                        $grantRecord->account_type === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER && $user->getId() === $grantRecord->account_id ||
                        $grantRecord->account_type === Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE && in_array($grantRecord->account_id, $roleMemberships)
                    )
                ) {
                    $accountGrants->{$grant} = true;
                }
            }
        }
        
        return $accountGrants;
    }

    /**
     * returns grants of record
     *
     * @param Tinebase_Record_Interface $record
     * @return  Tinebase_Record_RecordSet subtype Tinebase_Model_Grants
     */
    public function getGrantsForRecord($record)
    {
        if (empty($record->grants)) {
            $this->_getGrants($record);
        }

        return $record->grants;
    }

    /**
     * Returns a set of records identified by their id's
     *
     * @param   array $_ids array of record identifiers
     * @param   bool $_ignoreACL don't check acl grants
     * @param Tinebase_Record_Expander $_expander
     * @param   bool $_getDeleted
     * @return Tinebase_Record_RecordSet of $this->_modelName
     */
    public function getMultiple($_ids, $_ignoreACL = false, Tinebase_Record_Expander $_expander = null, $_getDeleted = false)
    {
        $this->_checkRight(self::ACTION_GET);

        $records = $this->_backend->getMultiple($_ids);
        $this->_getGrants($records);
        if (!$_ignoreACL) {
            /** @var Tinebase_Record_Interface $records */
            $records = $records->filter(function($record) {
                if ($record->grants instanceof Tinebase_Record_RecordSet) {
                    /** @var Tinebase_Model_Grants $grant */
                    foreach ($record->grants as $grant) {
                        if ($grant->userHasGrant(Tinebase_Model_Grants::GRANT_READ) ||
                                $grant->userHasGrant(Tinebase_Model_Grants::GRANT_ADMIN)) {
                            return true;
                        }
                    }
                }
                return false;
            });
        }

        if ($_expander !== null) {
            $_expander->expand($records);
        } elseif ($this->resolveCustomfields()) {
            Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($records);
        }

        return $records;
    }
}
