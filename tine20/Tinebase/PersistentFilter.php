<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * persistent filter controller
 * 
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * 
 * @todo remove account_id and only use grants to manage shared / personal filters
 */
class Tinebase_PersistentFilter extends Tinebase_Controller_Record_Grants
{
    /**
     * application name
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = FALSE;

    /**
     * do right checks - can be enabled/disabled by doRightChecks
     * 
     * @var boolean
     */
    protected $_doRightChecks = FALSE;
    
    /**
     * delete or just set is_delete=1 if record is going to be deleted
     *
     * @var boolean
     */
    protected $_purgeRecords = FALSE;
    
    /**
     * omit mod log for this records
     * 
     * @var boolean
     */
    protected $_omitModLog = TRUE;
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = Tinebase_Model_PersistentFilter::class;
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_grantsModel = Tinebase_Model_PersistentFilterGrant::class;
    
    /**
     * @var Tinebase_PersistentFilter
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_backend = new Tinebase_PersistentFilter_Backend_Sql();
        $this->_grantsBackend = new Tinebase_Backend_Sql_Grants(array(
            'modelName' => $this->_grantsModel,
            'tableName' => 'filter_acl',
            'recordTable' => 'filter'
        ));
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() 
    {
        
    }
    
    /**
     * singleton
     *
     * @return Tinebase_PersistentFilter
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_PersistentFilter();
        }
        
        return self::$_instance;
    }
    
    /**
     * returns persistent filter identified by id
     * 
     * @param  string $_id
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public static function getFilterById($_id)
    {
        $persistentFilter = self::getInstance()->get($_id);
        
        return $persistentFilter->filters;
    }
    
    /**
     * helper fn for prefereces
     * 
     * @param  string $_appName
     * @param  string $_accountId
     * @param  string $_returnDefaultId only return id of default identified by given name
     * @return array|string filterId => translated name
     */
    public static function getPreferenceValues($_appName, $_accountId = null, $_returnDefaultId = null)
    {
        $i18n = Tinebase_Translation::getTranslation($_appName);
        $i18nTinebase = Tinebase_Translation::getTranslation('Tinebase');
        $pfilters = self::getInstance()->search(new Tinebase_Model_PersistentFilterFilter(array(
            array('field' => 'application_id', 'operator' => 'equals', 'value' => Tinebase_Application::getInstance()->getApplicationByName($_appName)->getId()),
            array('field' => 'account_id',     'operator' => 'equals', 'value'  => $_accountId ? $_accountId : Tinebase_Core::getUser()->getId()),
        )));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Got ' . count($pfilters) . ' persistent filters');
        
        if (! $_returnDefaultId) {
            $result = array();
            foreach ($pfilters as $pfilter) {
                $result[] = array($pfilter->getId(), $i18n->translate($pfilter->name));
            }
            
            $result[] = array(
                Tinebase_Preference_Abstract::LASTUSEDFILTER,
                $i18nTinebase->translate('- The last filter I used -')
            );
            return $result;
        } else {
            $filter = $pfilters->filter('name', $_returnDefaultId)->getFirstRecord();
            return $filter ? $filter->getId() : null;
        }
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
        // check first if we already have a filter with this name for this account/application in the db
        $this->_sanitizeAccountId($_record);
        
        $existing = $this->search(new Tinebase_Model_PersistentFilterFilter(array(
            'account_id'        => $_record->account_id,
            'application_id'    => $_record->application_id,
            'name'              => $_record->name,
            'model'             => $_record->model,
        )));
        
        if (count($existing) > 0) {
            $_record->setId($existing->getFirstRecord()->getId());
            $result = $this->update($_record);
        } else {
            $result = parent::create($_record);
        }
        
        return $result;
    }
    
    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $record      the update record
     * @param   Tinebase_Record_Interface $oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($record, $oldRecord)
    {
        $this->_checkManageRightForCurrentUser($record, /* $_throwException = */ true, $oldRecord);
        $modelName = explode('_', $record->model);
        $translate = Tinebase_Translation::getTranslation($modelName[0]);
        // check if filter was shipped.
        if ($oldRecord->created_by == NULL && $oldRecord->account_id == NULL) {
            // if shipped, check if values have changed
            if (($record->account_id !== NULL) || $translate->_($oldRecord->name) != $record->name || $translate->_($oldRecord->description) != $record->description) {
                // if values have changed, set created_by to current user, so record is not shipped anymore
                $record->created_by = Tinebase_Core::getUser()->getId();
            }
        }
    }
    
    /**
     * set account_id to currentAccount if user has no MANAGE_SHARED_<recordName>_FAVORITES right
     * 
     * @param  Tinebase_Record_Interface $record
     * @return void
     */
    protected function _sanitizeAccountId($record)
    {
        if (! $record->account_id || ! $this->_belongsToCurrentUser($record)) {
            if (! $this->_checkManageRightForCurrentUser($record, false)) {
                $record->account_id = Tinebase_Core::getUser()->getId();
            }
        }
    }
    
    /**
     * add default grants
     * 
     * @param   Tinebase_Record_Interface $record
     * @param   boolean $addDuringSetup -> let admin group have all rights instead of user
     */
    protected function _setDefaultGrants(Tinebase_Record_Interface $record, $addDuringSetup = false)
    {
        parent::_setDefaultGrants($record, $addDuringSetup);
        
        if (    ! $record->isPersonal()
             && ! Tinebase_Config::getInstance()->get(Tinebase_Config::ANYONE_ACCOUNT_DISABLED, false) 
             && in_array(Tinebase_Model_Grants::GRANT_READ, call_user_func($this->_grantsModel . '::getAllGrants'))
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Set read grant for anyone');
            
            $record->grants->addRecord(new Tinebase_Model_PersistentFilterGrant(array(
                'account_id'       => 0,
                'account_type'     => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                'record_id'        => $record->getId(),
                Tinebase_Model_Grants::GRANT_READ   => true,
            )));
        }
    }
    
    /**
     * checks if filter belongs to current user
     * 
     * @param Tinebase_Record_Interface $record
     * @return boolean
     */
    protected function _belongsToCurrentUser($record)
    {
        return (is_object(Tinebase_Core::getUser()) && $record->account_id === Tinebase_Core::getUser()->getId());
    }
    
    /**
     * checks if the current user has the manage shared favorites right for the model of the record
     * @param Tinebase_Record_Interface $record
     * @param boolean $_throwException
     * @param Tinebase_Record_Interface $oldRecord   the current persistent record
     * @throws Tinebase_Exception_AccessDenied
     * @return boolean
     */
    protected function _checkManageRightForCurrentUser($record, $_throwException = false, $oldRecord = null)
    {
        $user = Tinebase_Core::getUser();
        
        if (! is_object($user)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No valid user found.');
            return true;
        }
        
        if ($this->_belongsToCurrentUser($record)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' You always have the right to manage your own filters');
            return true;
        }
        
        if ($oldRecord && $oldRecord->account_id === $record->account_id && $this->_checkGrant($record, 'update')) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Edit grant is sufficient to change record if account id does not change');
            return true;
        }

        $existing = $this->search(new Tinebase_Model_PersistentFilterFilter(array(
            'account_id'        => $record->account_id,
            'application_id'    => $record->application_id,
            'name'              => $record->name,
        )));
        
        if ($existing->count() > 0) {
            $rec = $existing->getFirstRecord();
        } else {
            $rec = $record;
        }

        $sharedRight = $this->_getManageSharedRight($rec);
        if (! $sharedRight) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Application has no "manage shared favorites" right');
            return true;
        }

        $right = $user->hasRight($record->application_id, $sharedRight);

        if (! $right && $_throwException) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to manage shared favorites!'); 
        }

        return $right;
    }
    
    /**
     * returns the name of the manage shared right for the record given
     * @param Tinebase_Record_Interface $record
     * @return string|null
     */
    protected function _getManageSharedRight($record)
    {
        $split = explode('_Model_', str_replace('Filter', '', $record->model));
        $rightClass = $split[0] . '_Acl_Rights';
        $rightConstant = 'MANAGE_SHARED_' . strtoupper($split[1]) . '_FAVORITES';
        $constantWithClass = $rightClass . '::' . $rightConstant;

        if (! defined($constantWithClass)) {
            return null;
        } else {
            return constant($rightClass . '::' . $rightConstant);
        }
    }
    
    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _inspectDelete(array $_ids) 
    {
        $recordsToDelete = $this->search(new Tinebase_Model_PersistentFilterFilter(array(array(
            'field' => 'id', 'operator' => 'in', 'value' => (array)$_ids
        ))));
        
        if (count($recordsToDelete) === 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' No records found.');
            return array();
        }
        
        foreach ($recordsToDelete as $record) {
            $this->_checkGrant($record, 'delete');
            
            // check if filter is from another user
            if ($record->account_id !== null && $record->account_id !== Tinebase_Core::getUser()->accountId) {
                throw new Tinebase_Exception_AccessDenied('You are not allowed to delete other users\' favorites!');
            }
        }
        
        if (! Tinebase_Core::getUser()->hasRight($recordsToDelete->getFirstRecord()->application_id, $this->_getManageSharedRight($recordsToDelete->getFirstRecord()))) {
            foreach ($recordsToDelete as $record) {
                if ($record->account_id === null) {
                    throw new Tinebase_Exception_AccessDenied('You are not allowed to manage shared favorites!');
                }
            }
        }
        
        // delete all persistenfilter prefs with this ids
        $prefFilter = new Tinebase_Model_PreferenceFilter(array(
            'name'        => Tinebase_Preference_Abstract::DEFAULTPERSISTENTFILTER,
            array('field' => 'value', 'operator' => 'in', 'value' => (array) $_ids),
        ));
        $prefIds = Tinebase_Core::getPreference()->search($prefFilter, NULL, TRUE);
        Tinebase_Core::getPreference()->delete($prefIds);
        
        return $_ids;
    }
}
