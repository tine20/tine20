<?php declare(strict_types=1);

/**
 * class to hold OVpnApi AuthConfig data
 *
 * @package     Admin
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold OVpnApi AuthConfig data
 *
 * @package     Admin
 * @subpackage  Model
 */
class Admin_Model_OVpnApi_AuthConfig extends Tinebase_Model_MFA_UserConfig
{
    public const MODEL_NAME_PART = 'OVpnApi_AuthConfig';

    public const FLD_IS_ACTIVE = 'is_active';

    public static function inheritModelConfigHook(array &$_definition)
    {
        $_definition[self::RECORD_NAME] = 'OVPN Auth Config';
        $_definition[self::RECORDS_NAME] = 'OVPN Auth Configs'; // ngettext('OVPN Auth Config', 'OVPN Auth Configs', n)
        $_definition[self::APP_NAME] = Admin_Config::APP_NAME;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::FIELDS][self::FLD_CONFIG_CLASS][self::CONFIG][self::AVAILABLE_MODELS] = [
            Tinebase_Model_MFA_HOTPUserConfig::class,
            Tinebase_Model_MFA_PinUserConfig::class,
            Tinebase_Model_MFA_TOTPUserConfig::class,
            Tinebase_Model_MFA_YubicoOTPUserConfig::class,
        ];

        $_definition[self::FIELDS][self::FLD_IS_ACTIVE] = [
            self::TYPE => self::TYPE_BOOLEAN,
            self::DEFAULT_VAL => true,
            // TODO set filter, make default val work
            self::LABEL => 'is active', // _('is active')
        ];
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}