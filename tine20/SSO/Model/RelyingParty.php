<?php declare(strict_types=1);

/**
 * class to hold RelyingParty data
 *
 * @package     SSO
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold RelyingParty data
 *
 * @package     SSO
 * @subpackage  Model
 */
class SSO_Model_RelyingParty extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'RelyingParty';
    public const TABLE_NAME = 'sso_relying_party';

    public const FLD_NAME = 'name';
    public const FLD_REDIRECT_URLS = 'redirect_urls';
    public const FLD_SECRET = 'secret';
    public const FLD_IS_CONFIDENTIAL = 'is_confidential';

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
        self::VERSION => 1,
        self::RECORD_NAME => 'Relying Party',
        self::RECORDS_NAME => 'Relying Parties', // ngettext('Relying Party', 'Relying Parties', n)
        self::TITLE_PROPERTY => self::FLD_NAME,
        self::MODLOG_ACTIVE => true,
        self::HAS_DELETED_TIME_UNIQUE => true,
        self::EXPOSE_JSON_API => true,

        self::CREATE_MODULE => false,
        self::APP_NAME => SSO_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS => [
                'name' => [
                    self::COLUMNS => ['name', 'deleted_time']
                ]
            ]
        ],

        self::FIELDS => [
            self::FLD_NAME              => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Name', // _('Name')
            ],
            self::FLD_REDIRECT_URLS     => [
                self::TYPE                  => self::TYPE_JSON,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Redirect URLs', // _('Redirect URLs')
            ],
            self::FLD_SECRET            => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Secret', // _('Secret')
            ],
            self::FLD_IS_CONFIDENTIAL   => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL           => 0,
            ],
        ]
    ];

    public function validateSecret(string $secret): bool
    {
        // TODO fixme needs to use hashing
        return $this->{self::FLD_SECRET} === $secret;
    }
}
