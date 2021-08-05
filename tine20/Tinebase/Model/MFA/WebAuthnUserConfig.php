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

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'Time based OTP (TOTP)',
        self::RECORDS_NAME                  => 'Time based OTPs (TOTP)', // ngettext('Time based OTP (TOTP)', 'Time based OTPs (TOTP)', n)
        self::TITLE_PROPERTY                => 'Time based OTP (TOPT) is configured', // _('Time based OTP (TOPT) is configured')

        self::FIELDS                        => [
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
