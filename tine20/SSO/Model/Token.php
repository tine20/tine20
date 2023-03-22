<?php declare(strict_types=1);

/**
 * class to hold Token data
 *
 * @package     SSO
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold OAuth2 Access Token data
 *
 * @package     SSO
 * @subpackage  Model
 */
class SSO_Model_Token extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'Token';
    public const TABLE_NAME = 'sso_token';

    public const TYPE_ACCESS = 'access';
    public const TYPE_AUTH = 'auth';
    public const TYPE_REFRESH = 'refresh';

    public const FLD_TOKEN = 'token';
    public const FLD_TYPE = 'type';
    public const FLD_DATA = 'data';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 2,
        self::RECORD_NAME => 'OAuth2 Access Token',
        self::RECORDS_NAME => 'OAuth2 Access Tokens', // ngettext('OAuth2 Access Token', 'OAuth2 Access Tokens', n)
        self::MODLOG_ACTIVE => true,
        self::EXPOSE_JSON_API => true,

        self::CREATE_MODULE => false,
        self::APP_NAME => SSO_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [
                self::FLD_TOKEN => [
                    self::COLUMNS => [self::FLD_TOKEN]
                ]
            ]
        ],

        self::FIELDS => [
            self::FLD_TOKEN             => [
                self::TYPE                  => self::TYPE_STRING,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Token', // _('Token')
            ],
            self::FLD_TYPE              => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Type', // _('Type')
            ],
            self::FLD_DATA              => [
                self::TYPE                  => self::TYPE_JSON,
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY  => true],
                self::LABEL                 => 'Data', // _('Data')
            ],
        ]
    ];
}
