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

    public const FLD_CONFIG = 'config';
    public const FLD_CONFIG_CLASS = 'config_class';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_LABEL = 'label';
    public const FLD_LOGO = 'logo';
    public const FLD_NAME = 'name';

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
        self::VERSION => 3,
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
            self::FLD_LABEL             => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::NULLABLE              => true,
                self::LABEL                 => 'Label', // _('Label')
            ],
            self::FLD_DESCRIPTION       => [
                self::TYPE                  => self::TYPE_TEXT,
                self::NULLABLE              => true,
                self::LABEL                 => 'Description', // _('Description')
            ],
            self::FLD_LOGO              => [
                self::TYPE                  => self::TYPE_BLOB,
                self::NULLABLE              => true,
                self::LABEL                 => 'Logo', // _('Logo')
            ],
            self::FLD_CONFIG_CLASS      => [
                self::TYPE                  => self::TYPE_MODEL,
                self::CONFIG                    => [
                    self::AVAILABLE_MODELS              => [
                        SSO_Model_OAuthOIdRPConfig::class,
                        SSO_Model_Saml2RPConfig::class,
                    ],
                ],
            ],
            self::FLD_CONFIG            => [
                self::TYPE                  => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                => [
                    self::REF_MODEL_FIELD       => self::FLD_CONFIG_CLASS,
                    self::PERSISTENT            => true,
                ],
            ],
        ]
    ];

    public function validateSecret(string $secret): bool
    {
        // TODO fixme needs to use hashing
        // TODO fixme this is OAuth and should be moved to oauth config class
        return $this->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_SECRET} === $secret;
    }
}
