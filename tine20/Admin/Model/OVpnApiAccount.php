<?php declare(strict_types=1);

/**
 * class to hold OVpnApi Account data
 *
 * @package     Admin
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold OVpnApi Account data
 *
 * @package     Admin
 * @subpackage  Model
 */
class Admin_Model_OVpnApiAccount extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'OVpnApiAccount';
    public const TABLE_NAME = 'admin_ovpnapiaccount';

    public const FLD_AUTH_CONFIGS = 'auth_configs';
    public const FLD_IS_ACTIVE = 'is_active';
    public const FLD_NAME = 'name';
    public const FLD_REALM = 'realm';
    public const FLD_PIN = 'pin';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME => 'OVPN Account',
        self::RECORDS_NAME => 'OVPN Accounts', // ngettext('OVPN Account', 'OVPN Accounts', n)
        self::TITLE_PROPERTY => self::FLD_NAME,
        self::MODLOG_ACTIVE => true,
        self::HAS_DELETED_TIME_UNIQUE => true,
        self::EXPOSE_JSON_API => true,
        self::CREATE_MODULE => true,
        self::APP_NAME => Admin_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS => [
                self::FLD_NAME => [
                    self::COLUMNS => [self::FLD_NAME, self::FLD_DELETED_TIME]
                ],
            ],
        ],

        self::FIELDS => [
            self::FLD_NAME => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::LABEL => 'Name', // _('Name')
            ],
            self::FLD_IS_ACTIVE => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL => true,
                self::LABEL => 'is active', // _('is active')
            ],
            self::FLD_REALM => [
                self::TYPE => self::TYPE_RECORD,
                self::LENGTH => 255,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::LABEL => 'Realm', // _('Realm')
                self::CONFIG => [
                    self::APP_NAME => Admin_Config::APP_NAME,
                    self::MODEL_NAME => Admin_Model_OVpnApiRealm::MODEL_NAME_PART,
                ],
            ],
            self::FLD_PIN => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Pin', // _('Pin')
            ],
            self::FLD_AUTH_CONFIGS => [
                self::TYPE      => self::TYPE_RECORDS,
                self::LABEL     => 'Authentication Configuration', // _('Authentication Configuration')
                self::CONFIG => [
                    self::DEPENDENT_RECORDS         => true,
                    self::STORAGE                   => self::TYPE_JSON,
                    self::APP_NAME                  => Admin_Config::APP_NAME,
                    self::MODEL_NAME                => Admin_Model_OVpnApi_AuthConfig::MODEL_NAME_PART,
                ]
            ]
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}