<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_Auth_MFA_YubicoUtil as Yubico;

/**
 * Yubico OTP SecondFactor Auth Adapter
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_MFA_YubicoOTPAdapter implements Tinebase_Auth_MFA_AdapterInterface
{
    protected $_mfaId;

    public function __construct(Tinebase_Record_Interface $_config, string $id)
    {
        $this->_mfaId = $id;
    }

    public function sendOut(Tinebase_Model_MFA_UserConfig $_userCfg): bool
    {
        return true;
    }

    public function validate($_data, Tinebase_Model_MFA_UserConfig $_userCfg): bool
    {
        if (!$_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG} instanceof Tinebase_Model_MFA_YubicoOTPUserConfig) {
            return false;
        }
        /** @var Tinebase_Model_MFA_YubicoOTPUserConfig $yubicoOTPCfg */
        $yubicoOTPCfg = $_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG};
        if (empty($yubicoOTPCfg->{Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_CC_ID})) {
            return false;
        }

        $_data = strtolower($_data);
        if (!preg_match("/^([cbdefghijklnrtuv]{0,16})([cbdefghijklnrtuv]{32})$/", $_data, $matches)) {
            return false;
        }
        $id = $matches[1];
        $modhex_ciphertext = $matches[2];

        if ($id !== $yubicoOTPCfg->{Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_PUBLIC_ID}) {
            return false;
        }

        /** @var Tinebase_Model_CredentialCache $cc */
        $cc = Tinebase_Auth_CredentialCache::getInstance()->get(
            $yubicoOTPCfg->{Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_CC_ID});
        $cc->key = Tinebase_Config::getInstance()->{Tinebase_Config::CREDENTIAL_CACHE_SHARED_KEY};
        Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($cc);

        $ciphertext = Yubico::modhex2hex($modhex_ciphertext);
        $plaintext = Yubico::aes128ecb_decrypt($cc->password, $ciphertext);

        if (!Yubico::crc_is_good($plaintext)) {
            return false;
        }

        if (substr($plaintext, 0, 12) !== $cc->username) {
            return false;
        }
        $counter = intval(substr($plaintext, 14, 2) . substr($plaintext, 12, 2), 16);
        $session = intval(substr($plaintext, 22, 2), 16);

        if ($counter > intval($yubicoOTPCfg->{Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_COUNTER}) || (
                $counter === intval($yubicoOTPCfg->{Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_COUNTER}) &&
                $session > intval($yubicoOTPCfg->{Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_SESSIONC}))) {
            $user = Tinebase_User::getInstance()->getUserById(
                $yubicoOTPCfg->{Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_ACCOUNT_ID}, Tinebase_Model_FullUser::class);
            if (!($cfg = $user->mfa_configs->getById($_userCfg->getId()))) {
                return false;
            }
            $cfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}->{Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_COUNTER} =
                $counter;
            $cfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}->{Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_SESSIONC} =
                $session;
            Tinebase_User::getInstance()->updateUserInSqlBackend($user);
            return true;
        }

        return false;
    }
}
