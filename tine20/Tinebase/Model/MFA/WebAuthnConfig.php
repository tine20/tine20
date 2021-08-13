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

/**
 * WebAuthn MFA Config Model
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Model_MFA_WebAuthnConfig extends Tinebase_Auth_MFA_AbstractUserConfig
{
    public const MODEL_NAME_PART = 'MFA_WebAuthnConfig';

    public const FLD_AUTHENTICATOR_ATTACHMENT = 'authenticator_attachment';
    public const FLD_USER_VERIFICATION_REQUIREMENT = 'user_verification_requirement';
    public const FLD_RESIDENT_KEY_REQUIREMENT = 'resident_key_requirement';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::FIELDS                        => [
            self::FLD_AUTHENTICATOR_ATTACHMENT      => [
                self::TYPE                              => self::TYPE_STRING,
            ],
            self::FLD_USER_VERIFICATION_REQUIREMENT => [
                self::TYPE                              => self::TYPE_STRING,
            ],
            self::FLD_RESIDENT_KEY_REQUIREMENT      => [
                self::TYPE                              => self::TYPE_STRING,
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
