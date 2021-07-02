<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold JWT access routes data
 * - jwt issuer
 * - optional jwt key id
 * - the account linked to that JWT
 * - the routes accessible using this JWT
 * // todo - optional max ttl to accept
 *
 * @package   Admin
 * @subpackage    Model
 */
class Admin_Model_JWTAccessRoutes extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'JWTAccessRoutes';

    const TABLE_NAME = 'jwt_access_routes';

    const FLD_ISSUER = 'issuer';
    const FLD_KEYID = 'key_id';
    const FLD_KEY = 'key';
    const FLD_ACCOUNTID = 'account_id';
    const FLD_ROUTES = 'routes';
    //const FLD_MAXTTL = 'max_ttl';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::APP_NAME => Admin_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE => true,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS => [
                self::FLD_ISSUER => [
                    self::COLUMNS => [
                        self::FLD_ISSUER,
                        self::FLD_KEYID
                    ]
                ],
            ],
        ],

        self::FIELDS    => [
            self::FLD_ISSUER        => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 255,
                self::NULLABLE          => false,
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_KEYID         => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 255,
                self::NULLABLE          => false,
                self::DEFAULT_VAL       => '',
            ],
            self::FLD_ACCOUNTID     => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 40,
                self::NULLABLE          => false,
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_KEY           => [
                self::TYPE              => self::TYPE_TEXT,
                self::NULLABLE          => false,
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_ROUTES        => [
                self::TYPE              => self::TYPE_JSON,
                self::NULLABLE          => false,
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
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
