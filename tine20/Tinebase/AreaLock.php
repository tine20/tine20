<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * AreaLock facility
 *
 * - handles locking/unlocking of certain "areas" (could be login, apps, data safe, ...)
 * - areas can be locked with Tinebase_Auth_AreaLock_*
 * - @todo add more doc
 *
 * @package     Tinebase
 * @subpackage  AreaLock
 */
class Tinebase_AreaLock implements Tinebase_Controller_Interface
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * @var array<Tinebase_Record_RecordSet<Tinebase_Model_AreaLockConfig>>
     */
    protected $_locks = [];

    /**
     * @var Tinebase_Config_KeyField|null
     */
    protected $_config;

    /**
     * @var bool
     */
    protected $_activatedByFE = false;

    /**
     * @var Tinebase_Model_AreaLockConfig|null
     */
    protected $_lastAuthFailedAreaConfig = null;

    public function _getConfig(): Tinebase_Config_KeyField
    {
        if (null === $this->_config) {
            $this->_config = Tinebase_Config::getInstance()->{Tinebase_Config::AREA_LOCKS};
            if (null === $this->_config->records) {
                $this->_config->records = new Tinebase_Record_RecordSet(Tinebase_Model_AreaLockConfig::class);
            }
        }
        return $this->_config;
    }

    /**
     * @return Tinebase_Record_RecordSet<Tinebase_Model_AreaLockConfig>
     */
    public function getAreaConfigs(string $area): Tinebase_Record_RecordSet
    {
        if (isset($this->_locks[$area])) {
            return $this->_locks[$area];
        }
        $this->_locks[$area] = $this->_getConfig()->records->filter(
            function(Tinebase_Model_AreaLockConfig $val) use ($area) {
                if ($val->areaMatch($area)) return true;
                return false;
            });

        return $this->_locks[$area];
    }

    /**
     * @param string $area
     * @return bool
     */
    public function hasLock($area)
    {
        if ($this->getAreaConfigs($area)->count() > 0) {
            return true;
        }
        return false;
    }

    public function isLocked(string $area): bool
    {
        return !$this->_hasValidAuth($area);
    }

    public function isAreaLockLocked(string $areaLockName): bool
    {
        if (null === ($areaLockCfg = $this->_getConfig()->records->find(Tinebase_Model_AreaLockConfig::FLD_AREA_NAME, $areaLockName))) {
            throw new Tinebase_Exception_NotFound('no area ' . $areaLockName . ' found');
        }
        return !$areaLockCfg->getBackend()->hasValidAuth();
    }

    /**
     * returns area lock status
     */
    public function getStatus(): array
    {
        $status = [
            'active' => false,
            'problems' => [],
        ];

        $status['active'] = $this->_getConfig()->records->count() > 0;

        // @todo check configs + backends

        return $status;
    }

    /**
     * @param string $areaLockName
     * @return Tinebase_Model_AreaLockState
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function lock(string $areaLockName): Tinebase_Model_AreaLockState
    {
        if (null === ($areaLockCfg = $this->_getConfig()->records->find(Tinebase_Model_AreaLockConfig::FLD_AREA_NAME, $areaLockName))) {
            throw new Tinebase_Exception_NotFound('no area ' . $areaLockName . ' found');
        }
        if ($areaLockCfg->getBackend()->hasValidAuth()) {
            try {
                $areaLockCfg->getBackend()->resetValidAuth();
            } catch (Zend_Session_Exception $zse) {
                throw new Tinebase_Exception_AccessDenied($zse->getMessage());
            }
        }

        return new Tinebase_Model_AreaLockState([
            'area' => $areaLockName,
            'expires' => new Tinebase_DateTime('1970-01-01')
        ]);
    }

    /**
     * @throws Tinebase_Exception_AreaUnlockFailed
     */
    public function unlock(string $areaLockName, string $userMfaId, string $password, Tinebase_Model_FullUser $identity): Tinebase_Model_AreaLockState
    {
        /** @var Tinebase_Model_AreaLockConfig $areaConfig */
        if (null === ($areaConfig = $this->_getConfig()->records
                ->find(Tinebase_Model_AreaLockConfig::FLD_AREA_NAME, $areaLockName))) {
            throw new Tinebase_Exception('Config for area lock "' . $areaLockName . '" not found');
        }
        /** @var Tinebase_Model_MFA_UserConfig $userCfg */
        if (null === ($userCfg = Tinebase_Auth_MFA::getAccountsMFAUserConfig($userMfaId, $identity))) {
            throw new Tinebase_Exception('User has no mfa configuration for id ' . $userMfaId);
        }
        if (!in_array($userCfg->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID},
                $areaConfig->{Tinebase_Model_AreaLockConfig::FLD_MFAS})) {
            throw new Tinebase_Exception('No MFA with id "' .
                $userCfg->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID} . '" for area "' . $areaLockName . '" found');
        }

        if ($areaConfig->getBackend()->hasValidAuth()) {
            $expires = $areaConfig->getBackend()->getAuthValidity();
        } else {
            $mfa = Tinebase_Auth_MFA::getInstance($userCfg->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID});
            if ($mfa->validate($password, $userCfg)) {
                $expires = $areaConfig->getBackend()->saveValidAuth();
            } else {
                $teauf = new Tinebase_Exception_AreaUnlockFailed('Invalid authentication'); //_('Invalid authentication')
                $teauf->setArea($areaConfig->{Tinebase_Model_AreaLockConfig::FLD_AREA_NAME});
                $teauf->setMFAUserConfigs($areaConfig->getUserMFAIntersection($identity));
                throw $teauf;
            }
        }

        return new Tinebase_Model_AreaLockState([
            'area' => $areaConfig->{Tinebase_Model_AreaLockConfig::FLD_AREA_NAME},
            'expires' => $expires
        ]);
    }

    public function forceUnlock(string $area): void
    {
        if (($configs = $this->getAreaConfigs($area))->count() === 0) {
            throw new Tinebase_Exception('Config for area lock "' . $area . '" not found');
        }
        /** @var Tinebase_Model_AreaLockConfig $config */
        foreach($configs as $config) {
            $config->getBackend()->saveValidAuth();
        }
    }

    /**
     * @return Tinebase_Record_RecordSet of Tinebase_Model_AreaLockState
     */
    public function getAllStates(): Tinebase_Record_RecordSet
    {
        $states = [];
        foreach ($this->_getConfig()->records as $areaConfig) {
            $states = array_merge($states, $this->_getAuthValidity(
                current($areaConfig->{Tinebase_Model_AreaLockConfig::FLD_AREAS})
            ));
        }

        $result = new Tinebase_Record_RecordSet(Tinebase_Model_AreaLockState::class);
        foreach ($states as $areaName => $expires) {
            $result->addRecord(new Tinebase_Model_AreaLockState([
                'area' => $areaName,
                'expires' => $expires ? $expires : new Tinebase_DateTime('1970-01-01')
            ]));
        }

        return $result;
    }

    /**
     * @return Tinebase_Record_RecordSet of Tinebase_Model_AreaLockState
     */
    public function getState(string $area): Tinebase_Record_RecordSet
    {
        $result = new Tinebase_Record_RecordSet(Tinebase_Model_AreaLockState::class);
        foreach ($this->_getAuthValidity($area) as $areaName => $expires) {
            $result->addRecord(new Tinebase_Model_AreaLockState([
                'area' => $areaName,
                'expires' => $expires ? $expires : new Tinebase_DateTime('1970-01-01')
            ]));
        }

        return $result;
    }

    public function getLastAuthFailedAreaConfig(): ?Tinebase_Model_AreaLockConfig
    {
        return $this->_lastAuthFailedAreaConfig;
    }

    protected function _hasValidAuth(string $area): bool
    {
        if (($configs = $this->getAreaConfigs($area))->count() === 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                . __LINE__ . ' Config not found for area ' . $area);
            return false;
        }
        /** @var Tinebase_Model_AreaLockConfig $config */
        foreach($configs as $config) {
            if (!$config->getBackend()->hasValidAuth()) {
                $this->_lastAuthFailedAreaConfig = $config;
                return false;
            }
        }
        return true;
    }

    /**
     * @return array<Tinebase_DateTime>
     */
    protected function _getAuthValidity(string $area): array
    {
        $result = [];
        foreach ($this->getAreaConfigs($area) as $config) {
            $result[$config->{Tinebase_Model_AreaLockConfig::FLD_AREA_NAME}] = $config->getBackend()->getAuthValidity();
        }
        return $result;
    }

    public function resetValidAuth(string $area): void
    {
        foreach ($this->getAreaConfigs($area) as $config) {
            $config->getBackend()->resetValidAuth();
        }
    }

    public function activatedByFE(bool $value = true): void
    {
        $this->_activatedByFE = $value;
    }

    public function isActivatedByFE(): bool
    {
        return $this->_activatedByFE;
    }
}
