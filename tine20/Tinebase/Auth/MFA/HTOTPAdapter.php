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

use OTPHP\TOTP;
use OTPHP\HOTP;

/**
 * H/T OTP SecondFactor Auth Adapter
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_MFA_HTOTPAdapter implements Tinebase_Auth_MFA_AdapterInterface
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
        if (!$_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG} instanceof Tinebase_Model_MFA_HOTPUserConfig &&
                !$_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG} instanceof Tinebase_Model_MFA_TOTPUserConfig) {
            return false;
        }
        /** @var Tinebase_Model_MFA_HOTPUserConfig $htOTPCfg */
        $htOTPCfg = $_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG};

        /** @var Tinebase_Model_CredentialCache $cc */
        $cc = Tinebase_Auth_CredentialCache::getInstance()->get(
            $htOTPCfg->{Tinebase_Model_MFA_HOTPUserConfig::FLD_CC_ID});
        $cc->key = Tinebase_Config::getInstance()->{Tinebase_Config::CREDENTIAL_CACHE_SHARED_KEY};
        Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($cc);

        if ($htOTPCfg instanceof Tinebase_Model_MFA_HOTPUserConfig) {
            for ($i = 0; $i < 8; ++$i) {
                $otp = HOTP::create(
                    $cc->password,
                    (int)$htOTPCfg->{Tinebase_Model_MFA_HOTPUserConfig::FLD_COUNTER} + $i
                );
                try {
                    $result = $otp->verify($_data);
                } catch (RuntimeException $re) {
                    $result = false;
                }
                if ($result) {
                    ++$i;
                    return Tinebase_Auth_MFA::getInstance($_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID})
                        ->persistUserConfig($htOTPCfg->{Tinebase_Model_MFA_HOTPUserConfig::FLD_ACCOUNT_ID},
                            function(Tinebase_Model_FullUser $user) use($i, $_userCfg, $htOTPCfg) {
                                if (!($cfg = $user->mfa_configs->getById($_userCfg->getId()))) {
                                    return false;
                                }
                                $cfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}
                                    ->{Tinebase_Model_MFA_HOTPUserConfig::FLD_COUNTER} =
                                        $htOTPCfg->{Tinebase_Model_MFA_HOTPUserConfig::FLD_COUNTER} + $i;
                                return true;
                            });
                }
            }
            return false;
        } else {
            $otp = TOTP::create($cc->password);

            try {
                $result = $otp->verify($_data); // TODO TBD with or without time window?, time(), 120);
            } catch (RuntimeException $re) {
                $result = false;
            }
            return $result;
        }
    }
}
