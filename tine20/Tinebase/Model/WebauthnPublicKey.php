<?php declare(strict_types=1);

/**
 * class to hold webauthn / fido2 public key data
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold webauthn / fido2 public key data
 *
 * @package     Tinebase
 * @subpackage  Model
 */
class Tinebase_Model_WebauthnPublicKey extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'WebauthnPublicKey';
    public const TABLE_NAME = 'webauthn_public_key';

    public const FLD_ACCOUNT_ID = 'account_id';
    public const FLD_KEY_ID = 'key_id';
    public const FLD_DATA = 'data';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::MODLOG_ACTIVE => true,

        self::CREATE_MODULE => false,
        self::APP_NAME => Tinebase_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [
                self::FLD_ACCOUNT_ID => [
                    self::COLUMNS => [self::FLD_ACCOUNT_ID]
                ]
            ],
            self::UNIQUE_CONSTRAINTS => [
                self::FLD_KEY_ID => [
                    self::COLUMNS => [self::FLD_KEY_ID]
                ]
            ]
        ],

        self::FIELDS => [
            self::FLD_KEY_ID             => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_ACCOUNT_ID        => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 40,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_DATA              => [
                self::TYPE                  => self::TYPE_JSON,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
