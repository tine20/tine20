<?php
/**
 * AccessToken controller for OnlyOfficeIntegrator application
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * AccessToken controller class for OnlyOfficeIntegrator application
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Controller
 */
class OnlyOfficeIntegrator_Controller_AccessToken extends Tinebase_Controller_Record_Abstract
{
    protected $_cachedUnresolvedTokens = null;

    /**
     * holds the instance of the singleton
     *
     * @var OnlyOfficeIntegrator_Controller_AccessToken
     */
    private static $_instance = null;

    /**
     * OnlyOfficeIntegrator_Controller_AccessToken constructor.
     * @throws \Tinebase_Exception_Backend_Database
     */
    protected function __construct()
    {
        $this->_applicationName = OnlyOfficeIntegrator_Config::APP_NAME;
        $this->_modelName = OnlyOfficeIntegrator_Model_AccessToken::class;

        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => OnlyOfficeIntegrator_Model_AccessToken::class,
            Tinebase_Backend_Sql::TABLE_NAME    => OnlyOfficeIntegrator_Model_AccessToken::TABLE_NAME,
            //defaults to Tinebase_Backend_Sql::MODLOG_ACTIVE => false,
        ]);
        $this->_handleDependentRecords = false; // its a tiny performance enhancement (at time of writing)
        $this->_doContainerACLChecks = false;
        $this->_doRightChecks = false;
        //defaults to $this->_purgeRecords = true;
    }

    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return OnlyOfficeIntegrator_Controller_AccessToken
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    protected function _addDefaultFilter(Tinebase_Model_Filter_FilterGroup $_filter = null)
    {
        if (! $_filter->isFilterSet(OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, true)) {
            $_filter->addFilter(
                $_filter->createFilter(OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'equals', 0));
        }
    }

    /**
     * Deletes a set of records.
     *
     * If one of the records could not be deleted, no record is deleted
     *
     * @param  array|Tinebase_Record_Interface|Tinebase_Record_RecordSet $_ids array of record identifiers
     * @return Tinebase_Record_RecordSet
     * @throws Exception
     */
    public function delete($_ids)
    {
        if ($_ids instanceof $this->_modelName || $_ids instanceof Tinebase_Record_RecordSet) {
            /** @var Tinebase_Record_Interface $_ids */
            $_ids = (array)$_ids->getId();
        }

        if ($this->_purgeRecords) {
            return $this->_backend->delete($_ids);
        } else {
            return $this->_backend->softDelete($_ids);
        }
    }

    public function clearUnresolvedTokensCache()
    {
        $this->_cachedUnresolvedTokens = null;
    }
    
    /**
     * @return Tinebase_Record_RecordSet
     */
    public function getUnresolvedTokensCached()
    {
        if (null === $this->_cachedUnresolvedTokens) {
            $this->invalidateTimeouts();
            $ttl = Tinebase_DateTime::now()->subSecond((int)(OnlyOfficeIntegrator_Config::getInstance()
                ->{OnlyOfficeIntegrator_Config::TOKEN_LIVE_TIME}));
            $this->_cachedUnresolvedTokens = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_RESOLUTION, 'operator' => 'equals',
                    'value' => 0],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN, 'operator' => 'after_or_equals',
                    'value' => $ttl],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals',
                    'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET]
            ]));
        }

        return $this->_cachedUnresolvedTokens;
    }

    /**
     * if there is no open, other token for the passed node_ids, then reactivate the given tokens
     * reactivate means: invalidated = 0, last_seen = now()
     * only processes tokens that have been seen within the last day
     * returns reactivated tokens
     *
     * @param Tinebase_Record_RecordSet $tokens
     * @return Tinebase_Record_RecordSet
     */
    public function reactivateTokens(Tinebase_Record_RecordSet $tokens)
    {
        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        $result = new Tinebase_Record_RecordSet(OnlyOfficeIntegrator_Model_AccessToken::class);
        $timeLimit = Tinebase_DateTime::now()->subDay(1);

        /** @var OnlyOfficeIntegrator_Model_AccessToken $token */
        foreach ($tokens as $token) {
            if ($token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_RESOLUTION}) {
                continue;
            }
            if ($timeLimit->isLater($token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN}) ||
                $this->searchCount(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                        ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals',
                            'value' => $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}],
                        ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'not',
                            'value' => $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}],
                    ])) > 0) {
                continue;
            }

            $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED} = 0;
            $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN} = Tinebase_DateTime::now();
            $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SAVE} = Tinebase_DateTime::now();
            $result->addRecord($this->update($token));
        }

        $transaction->release();

        return $result;
    }

    public function invalidateTimeouts()
    {
        $ttl = Tinebase_DateTime::now()->subSecond(OnlyOfficeIntegrator_Config::getInstance()
            ->{OnlyOfficeIntegrator_Config::TOKEN_LIVE_TIME});

        $transRaii = Tinebase_RAII::getTransactionManagerRAII();
        $selectForUpdateRaii = Tinebase_Backend_Sql_SelectForUpdateHook::getRAII($this->_backend);

        foreach ($this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                OnlyOfficeIntegrator_Model_AccessToken::class, [
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN, 'operator' => 'before',
                        'value' => $ttl]
                ])) as $toInvalidate) {
            $toInvalidate->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED} = 1;
            $this->update($toInvalidate);
        }

        $transRaii->release();
        unset($selectForUpdateRaii);
    }

    public function cleanOldTokens()
    {
        $ttl = Tinebase_DateTime::now()->subSecond((int)(OnlyOfficeIntegrator_Config::getInstance()
            ->{OnlyOfficeIntegrator_Config::TOKEN_LIVE_TIME} * 1.1));

        $transRaii = Tinebase_RAII::getTransactionManagerRAII();
        $selectForUpdateRaii = Tinebase_Backend_Sql_SelectForUpdateHook::getRAII($this->_backend);

        $tokenCounts = [];
        $toDelete = [];
        foreach ($this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    OnlyOfficeIntegrator_Model_AccessToken::class, [
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN, 'operator' => 'before',
                        'value' => $ttl],
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals',
                        'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET]
                ])) as $toInspect) {
            if (!isset($tokenCounts[$toInspect->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}])) {
                $tokenCounts[$toInspect->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}] = $this->searchCount(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class,
                        [
                            ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals',
                                'value' => $toInspect->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}],
                        ]));
            }
            if (0 === (int)$tokenCounts[$toInspect->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}]) {
                $toDelete[] = $toInspect->getId();
            }
        }

        if (!empty($toDelete)) {
            $this->delete($toDelete);
        }

        $transRaii->release();
        unset($selectForUpdateRaii);
    }

    public function scheduleForceSaves(): bool
    {
        if (($forceSaveInteval = (int)(OnlyOfficeIntegrator_Config::getInstance()
                ->{OnlyOfficeIntegrator_Config::FORCE_SAVE_INTERVAL})) < 1) {
            return true;
        }

        $keys = [];
        $ttl = Tinebase_DateTime::now()->subSecond($forceSaveInteval);

        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        $selectForUpdate = Tinebase_Backend_Sql_SelectForUpdateHook::getRAII($this->_backend);

        $tokens = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            OnlyOfficeIntegrator_Model_AccessToken::class, [
            ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SAVE, 'operator' => 'before',
                'value' => $ttl],
            ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SAVE_FORCED, 'operator' => 'before',
                'value' => $ttl]
        ]));
        unset($selectForUpdate);

        $now = Tinebase_DateTime::now();
        foreach ($tokens as $toForceSave) {
            $keys[$toForceSave->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY}] = $toForceSave;
            $toForceSave->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SAVE_FORCED} = $now;
            $this->update($toForceSave);
        }

        $transaction->release();


        foreach ($keys as $token) {
            OnlyOfficeIntegrator_Controller::getInstance()->callCmdServiceForceSave($token);
        }

        return true;
    }

    /**
     * overwrite this function to check rights / don't forget to call parent
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_AreaLocked
     */
    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }
        parent::_checkRight($_action);

        if ('get' !== $_action && OnlyOfficeIntegrator_Controller::getInstance()->isGoingIntoOrInMaintenanceMode()) {
            throw new Tinebase_Exception_AccessDenied('in maintenance mode');
        }
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);

        if ($_createdRecord->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} != OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION &&
                Tinebase_BroadcastHub::getInstance()->isActive()) {
            $node = Tinebase_FileSystem::getInstance()->get($_createdRecord->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID});
            Tinebase_BroadcastHub::getInstance()->pushAfterCommit('update', get_class($node), $node->getId(), $node->getContainerId());
        }
    }

    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);

        if ($updatedRecord->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} != OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION &&
                Tinebase_BroadcastHub::getInstance()->isActive()) {
            $node = Tinebase_FileSystem::getInstance()->get($updatedRecord->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID});
            Tinebase_BroadcastHub::getInstance()->pushAfterCommit('update', get_class($node), $node->getId(), $node->getContainerId());
        }
    }
}
