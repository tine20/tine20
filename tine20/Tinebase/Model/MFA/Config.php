<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  MFA
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * MFA_Config Model
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Model_MFA_Config extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'MFA_Config';

    const FLD_ID = 'id';
    const FLD_PROVIDER_CLASS = 'provider_class';
    const FLD_PROVIDER_CONFIG_CLASS = 'provider_config_class';
    const FLD_PROVIDER_CONFIG = 'provider_config';
    const FLD_USER_CONFIG_CLASS = 'user_config_class';
    const FLD_ALLOW_SELF_SERVICE = 'allow_self_service';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::FIELDS                        => [
            self::FLD_ID                      => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLD_PROVIDER_CLASS        => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLD_PROVIDER_CONFIG_CLASS => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLD_PROVIDER_CONFIG       => [
                self::TYPE                      => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                    => [
                    self::REF_MODEL_FIELD           => self::FLD_PROVIDER_CONFIG_CLASS,
                ],
            ],
            self::FLD_USER_CONFIG_CLASS     => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLD_ALLOW_SELF_SERVICE    => [
                self::TYPE                      => self::TYPE_BOOLEAN,
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
