<?php declare(strict_types=1);

/**
 * class to hold OVpnApi Realm data
 *
 * @package     Admin
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold OVpnApi Realm data
 *
 * @package     Admin
 * @subpackage  Model
 */
class Admin_Model_OVpnApiRealm extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'OVpnApiRealm';
    public const TABLE_NAME = 'admin_ovpnapirealm';

    public const FLD_ISSUER = 'issuer';
    public const FLD_KEY = 'key';
    public const FLD_NAME = 'name';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME => 'OVPN Realm',
        self::RECORDS_NAME => 'OVPN Realms', // ngettext('OVPN Realm', 'OVPN Realms', n)
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
                self::FLD_KEY => [
                    self::COLUMNS => [self::FLD_KEY, self::FLD_DELETED_TIME]
                ],
            ],
        ],

        self::FIELDS => [
            self::FLD_NAME => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::QUERY_FILTER => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL => 'Name', // _('Name')
            ],
            self::FLD_KEY => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL => 'Key', // _('Key')
            ],
            self::FLD_ISSUER => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::LABEL => 'Issuer', // _('Issuer')
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}