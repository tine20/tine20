<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <c.weiss@metaways.de>
 */

/**
 * Json Adapter class
 * 
 * @package     Tinebase
 * @subpackage  Adapter
 */
class Tinebase_Frontend_Json_AreaLock extends  Tinebase_Frontend_Json_Abstract
{
    /**
     * @param string $area
     * @param string $mfaId
     * @param string $password
     * @return array
     */
    public function unlock(string $area, string $userMfaId, string $password = null): array
    {
        $result = Tinebase_AreaLock::getInstance()->unlock($area, $userMfaId, $password, Tinebase_Core::getUser());

        return $this->_recordToJson($result);
    }

    public function triggerMFA(string $userMfaId): bool
    {
        if (null === ($userCfg = Tinebase_Auth_MFA::getAccountsMFAUserConfig($userMfaId, Tinebase_Core::getUser()))) {
            throw new Tinebase_Exception('User has no mfa configuration for id ' . $userMfaId);
        }
        return Tinebase_Auth_MFA::getInstance($userCfg->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID})
            ->sendOut($userCfg);
    }

    /**
     * @param string $area
     * @return array
     */
    public function lock(string $area): array
    {
        $result = Tinebase_AreaLock::getInstance()->lock($area);

        return $this->_recordToJson($result);
    }

    /**
     * @param string $area
     * @return boolean
     */
    public function isLocked(string $area): bool
    {
        return Tinebase_AreaLock::getInstance()->isLocked($area);
    }

    public function getState(string $area): array
    {
        $lockStates = Tinebase_AreaLock::getInstance()->getAllStates();
        if (!$lockState = $lockStates->filter('area', $area)->getFirstRecord()) {
            throw new Exception("no such area: $area");
        }
        return $this->_recordToJson($lockState);
    }
    
    public function getAllStates(): array
    {
        $lockStates = Tinebase_AreaLock::getInstance()->getAllStates();
        return $this->_multipleRecordsToJson($lockStates);
    }
    
    /**
     * get configured MFA devices for current user
     */
    public function getUsersMFAUserConfigs(?string $area): array
    {
        $result = [];

        $mfas = Tinebase_Config::getInstance()->{Tinebase_Config::MFA}->records;
        if ($area) {
            $mfaIds = [];
            foreach (Tinebase_AreaLock::getInstance()->getAreaConfigs($area) as $areaCfg) {
                $mfaIds = array_merge($areaCfg->{Tinebase_Model_AreaLockConfig::FLD_MFAS});
            }
            $mfaIds = array_unique($mfaIds);
            $mfas = $mfas->filter(function($val) use($mfaIds) {
                return in_array($val->{Tinebase_Model_MFA_Config::FLD_ID}, $mfaIds);
            });
        }
        if ($mfas instanceof Tinebase_Record_RecordSet &&
                ($mfaUserConfigs = Tinebase_Core::getUser()->mfa_configs) instanceof Tinebase_Record_RecordSet) {
            $mfaUserConfigs = $mfaUserConfigs->filter(function ($val) use ($mfas) {
                return $mfas->find(Tinebase_Model_MFA_Config::FLD_ID,
                    $val->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID}) !== null;
            });
            /** @var Tinebase_Model_MFA_UserConfig $mfaUCfg */
            foreach ($mfaUserConfigs as $mfaUCfg) {
                $result[] = $mfaUCfg->toFEArray();
            }
        }

        return $result;
    }
}
