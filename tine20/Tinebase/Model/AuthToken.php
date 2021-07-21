<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * AuthToken Model
 *
 * @package     Tinebase
 * @subpackage  Model
 *
 * @property string                     account_id
 * @property string                     auth_token
 * @property Tinebase_DateTime          valid_until
 * @property array                      channels
 */

class Tinebase_Model_AuthToken extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'AuthToken';
    const TABLE_NAME = 'auth_token';

    const FLD_AUTH_TOKEN = 'auth_token';
    const FLD_ACCOUNT_ID = 'account_id';
    const FLD_VALID_UNTIL = 'valid_until';
    const FLD_CHANNELS = 'channels';
    const FLD_MAX_TTL = 'max_ttl';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION               => 1,
        self::APP_NAME              => Tinebase_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE         => false,

        self::TABLE                 => [
            self::NAME                  => self::TABLE_NAME,
            self::INDEXES               => [
                self::FLD_ACCOUNT_ID       => [
                    self::COLUMNS               => [self::FLD_ACCOUNT_ID]
                ],
                self::FLD_VALID_UNTIL      => [
                    self::COLUMNS               => [self::FLD_VALID_UNTIL]
                ],
            ],
            self::UNIQUE_CONSTRAINTS    => [
                self::FLD_AUTH_TOKEN       => [
                    self::COLUMNS               => [self::FLD_AUTH_TOKEN]
                ],
            ],
        ],

        self::FIELDS                => [
            self::FLD_AUTH_TOKEN       => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_ACCOUNT_ID       => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 40,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_VALID_UNTIL      => [
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_CHANNELS         => [
                self::TYPE                  => self::TYPE_JSON,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    Tinebase_Record_Validator_Json::class,
                ],
            ],
            self::FLD_MAX_TTL          => [
                self::TYPE                  => self::TYPE_VIRTUAL
            ]
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
