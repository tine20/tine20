<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * persistent filter controller
 * 
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * 
 * @todo rework account_id to container_id to let Persistent_Filters be organised
 *       in standard contaienr / grants way. This depends on container class to cope
 *       with multiple models per app which is not yet implementet (2010-05-05)
 */
class Tinebase_PersistentFilter extends Tinebase_Controller_Record_Abstract
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
     * do right checks - can be enabled/disabled by _setRightChecks
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
    protected $_modelName = 'Tinebase_Model_PersistentFilter';
    
    /**
     * @var Tinebase_PersistentFilter
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend         = new Tinebase_PersistentFilter_Backend_Sql();
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
    public static function getPreferenceValues($_appName, $_accountId = NULL, $_returnDefaultId = NULL)
    {
        $i18n = Tinebase_Translation::getTranslation($_appName);
        $pfilters = self::getInstance()->search(new Tinebase_Model_PersistentFilterFilter(array(
            array('field' => 'application_id', 'operator' => 'equals', 'value' => Tinebase_Application::getInstance()->getApplicationByName($_appName)->getId()),
            array('field' => 'account_id',     'operator' => 'equals', 'value'  => $_accountId ? $_accountId : Tinebase_Core::getUser()->getId()),
        )));
        
        if (! $_returnDefaultId) {
            $result = array();
            foreach ($pfilters as $pfilter) {
                $result[] = array($pfilter->getId(), $i18n->translate($pfilter->name));
            }
            return $result;
        } else {
            $filter = $pfilters->filter('name', $_returnDefaultId)->getFirstRecord();
            return $filter ? $filter->getId() : NULL;
        }
    }
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        // check first if we already have a filter with this name for this account/application in the db
        $this->_sanitizeAccountId($_record);
        
        $existing = $this->search(new Tinebase_Model_PersistentFilterFilter(array(
            'account_id'        => $_record->account_id,
            'application_id'    => $_record->application_id,
            'name'              => $_record->name,
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
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_checkManageRightForCurrentUser($_record, true);
        $modelName = explode('_', $_record->model);
        $translate = Tinebase_Translation::getTranslation($modelName[0]);
        // check if filter was shipped.
        if ($_oldRecord->created_by == NULL && $_oldRecord->account_id == NULL) {
            // if shipped, check if values have changed
            if (($_record->account_id !== NULL) || $translate->_($_oldRecord->name) != $_record->name || $translate->_($_oldRecord->description) != $_record->description) {
                // if values have changed, set created_by to current user, so record is not shipped anymore
                $_record->created_by = Tinebase_Core::getUser()->getId();
            }
        }
    }
    
    /**
     * set account_id to currentAccount if user has no MANAGE_SHARED_<recordName>_FAVORITES right
     * 
     * @param  Tinebase_Record_Interface $_record
     * @return void
     */
    protected function _sanitizeAccountId($_record)
    {
        if (! $_record->account_id || $_record->account_id !== Tinebase_Core::getUser()->getId()) {
            if (! $this->_checkManageRightForCurrentUser($_record, false)) {
                $_record->account_id = Tinebase_Core::getUser()->getId();
            }
        }
    }
    
    /**
     * checks if the current user has the manage shared favorites right for the model of the record
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_AccessDenied
     * @return boolean
     */
    protected function _checkManageRightForCurrentUser($_record, $_throwException = false)
    {
        $existing = $this->search(new Tinebase_Model_PersistentFilterFilter(array(
            'account_id'        => $_record->account_id,
            'application_id'    => $_record->application_id,
            'name'              => $_record->name,
        )));
        
        if ($existing->count() > 0) {
            $rec = $existing->getFirstRecord();
        } else {
            $rec = $_record;
        }
        
        $right = Tinebase_Core::getUser()->hasRight($_record->application_id, $this->_getManageSharedRight($rec));
        
        if (!$right && $_throwException) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to manage shared favorites!'); 
        }
        return $right;
    }
    
    /**
     * returns the name of the manage shared right for the record given
     * @param Tinebase_Record_Interface $_record
     * @return string
     */
    protected function _getManageSharedRight($_record)
    {
        $split = explode('_Model_', str_replace('Filter', '', $_record->model));
        $rightClass = $split[0] . '_Acl_Rights';
        $rightConstant = 'MANAGE_SHARED_' . strtoupper($split[1]) . '_FAVORITES';
        
        return constant($rightClass . '::' . $rightConstant);
    }
    
    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     */
    protected function _inspectDelete(array $_ids) {
        
        $recordsToDelete = $this->search(new Tinebase_Model_PersistentFilterFilter(array(array(
            'field' => 'id', 'operator' => 'in', 'value' => $_ids
        ))));

        if (! Tinebase_Core::getUser()->hasRight($recordsToDelete->getFirstRecord()->application_id, $this->_getManageSharedRight($recordsToDelete->getFirstRecord()))) {
            foreach ($recordsToDelete as $record) {
                if ($record->account_id === null) {
                    throw new Tinebase_Exception_AccessDenied('You are not allowed to manage shared favorites!');
                }
            }
        }
        
        // check if filter is from another user
        foreach ($recordsToDelete as $record) {
            if ($record->account_id !== null && $record->account_id !== Tinebase_Core::getUser()->accountId) {
                throw new Tinebase_Exception_AccessDenied('You are not allowed to delete other users\' favorites!');
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
