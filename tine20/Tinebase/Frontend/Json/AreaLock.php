<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2022 Metaways Infosystems GmbH (http://www.metaways.de)
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
    public function unlock(string $areaLockName, string $userMfaId, string $password = null): array
    {
        $result = Tinebase_AreaLock::getInstance()->unlock($areaLockName, $userMfaId, $password, Tinebase_Core::getUser());

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

    public function lock(string $areaLockName): array
    {
        $result = Tinebase_AreaLock::getInstance()->lock($areaLockName);

        return $this->_recordToJson($result);
    }

    public function isLocked(string $areaLockName): bool
    {
        return Tinebase_AreaLock::getInstance()->isAreaLockLocked($areaLockName);
    }

    public function getState(string $areaLockName): array
    {
        $lockStates = Tinebase_AreaLock::getInstance()->getAllStates();
        if (!$lockState = $lockStates->filter('area', $areaLockName)->getFirstRecord()) {
            throw new Exception("no such area: $areaLockName");
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
    public function getUsersMFAUserConfigs(?string $areaLockName): array
    {
        $result = [];

        $mfas = Tinebase_Config::getInstance()->{Tinebase_Config::MFA}->records;
        if ($areaLockName) {
            $mfaIds = [];
            if ($areaLock = Tinebase_AreaLock::getInstance()->_getConfig()->records->find(
                    Tinebase_Model_AreaLockConfig::FLD_AREA_NAME, $areaLockName)) {
                $mfaIds = $areaLock->{Tinebase_Model_AreaLockConfig::FLD_MFAS};
            }
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

    public function getSelfServiceableMFAs(): array
    {
        return (new Admin_Frontend_Json())->getPossibleMFAs(Tinebase_Core::getUser()->getId());
    }

    public function deleteMFAUserConfigs(array $userConfigIds): array
    {
        $user = clone Tinebase_Core::getUser();
        if (!$user->mfa_configs) {
            return [];
        }
        $result = [];

        foreach ($userConfigIds as $id) {
            if (!$user->mfa_configs->getById($id)) continue;
            $user->mfa_configs->removeById($id);
            $result[] = $id;
        }

        if (!empty($result)) {
            Tinebase_User::getInstance()->updateUser($user);
        }

        return $result;
    }

    public function saveMFAUserConfig(string $mfaId, array $mfaUserConfig, ?string $MFAPassword = null): bool
    {
        if (!isset($mfaUserConfig['id']) ||  empty($mfaUserConfig['id'])) {
            $mfaUserConfig['id'] = Tinebase_Record_NewAbstract::generateUID();
        }
        $userCfg = new Tinebase_Model_MFA_UserConfig($mfaUserConfig);
        if ($mfaId !== $userCfg->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID}) {
            throw new Tinebase_Exception_UnexpectedValue('mfaId doesn\'t match user configs mfa id');
        }
        $mfa = Tinebase_Auth_MFA::getInstance($mfaId);

        // we need to test the unsaved mfa user config here, so we clone it and the user
        // then we get it ready, that's a bit tedious sadly
        $testUserCfg = clone $userCfg;
        /** @var Tinebase_Model_FullUser $user */
        $user = clone Tinebase_Core::getUser();
        if (!$user->mfa_configs) {
            $user->mfa_configs = new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class);
        }
        $user->mfa_configs->removeById($userCfg->getId());
        $user->mfa_configs->addRecord($testUserCfg);

        // do the tedious task of getting the mfa user config "ready"
        try {
            $testUserCfg->updateUserNewRecordCallback($user, Tinebase_Core::getUser());
            $testUserCfg->runConvertToData();
            $user->mfa_configs->removeById($testUserCfg->getId());
            $testUserCfg = new Tinebase_Model_MFA_UserConfig($testUserCfg->toArray());
            $testUserCfg->runConvertToRecord();
            $user->mfa_configs->addRecord($testUserCfg);

            // test the mfa user config
            if (null !== $MFAPassword) {
                if (!$mfa->validate($MFAPassword, $testUserCfg)) {
                    $e = new Tinebase_Exception_AreaUnlockFailed('mfa password wrong');
                    $e->setMFAUserConfigs(new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class, [$userCfg]));
                    throw $e;
                }
            } else {
                $mfa->sendOut($testUserCfg);
                $e = new Tinebase_Exception_AreaLocked('mfa send out triggered');
                $e->setMFAUserConfigs(new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class, [$userCfg]));
                throw $e;
            }
        } finally {
            // clean up, eventually we created something persistent
            $testUserCfg->updateUserOldRecordCallback(Tinebase_Core::getUser(), $user);
        }

        // no exception? persist the mfa user config
        $user = clone Tinebase_Core::getUser();
        if (!$user->mfa_configs) {
            $user->mfa_configs = new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class);
        }
        $user->mfa_configs->removeById($userCfg->getId());
        $user->mfa_configs->addRecord($userCfg);
        Tinebase_User::getInstance()->updateUser($user);

        return true;
    }
}
