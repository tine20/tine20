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

use ParagonIE\ConstantTime\Base32;


/**
 * WebAuthn MFA UserConfig Model
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Model_MFA_WebAuthnUserConfig extends Tinebase_Auth_MFA_AbstractUserConfig
{
    public const MODEL_NAME_PART = 'MFA_WebAuthnUserConfig';

    public const FLD_PUBLIC_KEY_DATA = 'publicKeyData';
    public const FLD_WEBAUTHN_ID = 'webAuthnId';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'FIDO2 WebAuthn Device',  // gettext('GENDER_FIDO2 WebAuthn Device')
        self::RECORDS_NAME                  => 'FIDO2 WebAuthn Devices', // ngettext('FIDO2 WebAuthn Device', 'FIDO2 WebAuthn Devices', n)
        self::TITLE_PROPERTY                => 'FIDO2 WebAuthn Device is configured.', // _('FIDO2 WebAuthn Device is configured.')

        self::FIELDS                        => [
            self::FLD_PUBLIC_KEY_DATA           => [
                self::TYPE                          => self::TYPE_STRING,
            ],
            self::FLD_WEBAUTHN_ID               => [
                self::TYPE                          => self::TYPE_STRING,
            ],
        ]
    ];

    public function updateUserNewRecordCallback(Tinebase_Model_FullUser $newUser, ?Tinebase_Model_FullUser $oldUser, Tinebase_Model_MFA_UserConfig $userCfg)
    {
        if ($this->{self::FLD_PUBLIC_KEY_DATA}) {
            if ($this->{self::FLD_WEBAUTHN_ID} && null !== ($webauthnPublicKey = Tinebase_Controller_WebauthnPublicKey::getInstance()
                    ->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebauthnPublicKey::class, [
                        ['field' => Tinebase_Model_WebauthnPublicKey::FLD_KEY_ID, 'operator' => 'equals', 'value' => $this->{self::FLD_WEBAUTHN_ID}],
                        ['field' => Tinebase_Model_WebauthnPublicKey::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => $newUser->getId()]
                    ]))->getFirstRecord())) {
                Tinebase_Controller_WebauthnPublicKey::getInstance()->delete([$webauthnPublicKey->getId()]);
            }
            $this->{self::FLD_WEBAUTHN_ID} =
                Tinebase_Auth_Webauthn::webAuthnRegister($this->{self::FLD_PUBLIC_KEY_DATA}, $newUser);
        }
        $this->{self::FLD_PUBLIC_KEY_DATA} = null;
    }

    public function updateUserOldRecordCallback(Tinebase_Model_FullUser $newUser, Tinebase_Model_FullUser $oldUser, Tinebase_Model_MFA_UserConfig $userCfg)
    {
        if (!$newUser->mfa_configs || !$newUser->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_ID,
                $userCfg->{Tinebase_Model_MFA_UserConfig::FLD_ID})) {
            if (null !== ($webauthnPublicKey = Tinebase_Controller_WebauthnPublicKey::getInstance()
                    ->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebauthnPublicKey::class, [
                        ['field' => Tinebase_Model_WebauthnPublicKey::FLD_KEY_ID, 'operator' => 'equals', 'value' => $this->{self::FLD_WEBAUTHN_ID}],
                        ['field' => Tinebase_Model_WebauthnPublicKey::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => $oldUser->getId()]
                    ]))->getFirstRecord())) {
                Tinebase_Controller_WebauthnPublicKey::getInstance()->delete([$webauthnPublicKey->getId()]);
            }
        }
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
