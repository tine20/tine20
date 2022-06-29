<?php
/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold history data
 *
 * @package   OnlyOfficeIntegrator
 * @subpackage    Model
 */
class OnlyOfficeIntegrator_Model_History extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'History';

    const TABLE_NAME = 'onlyoffice_history';

    const FLDS_JSON = 'json';
    const FLDS_NODE_ID = 'node_id';
    const FLDS_NODE_REVISION = 'node_revision';
    const FLDS_VERSION = 'version';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION           => 1,
        self::APP_NAME          => OnlyOfficeIntegrator_Config::APP_NAME,
        self::MODEL_NAME        => self::MODEL_NAME_PART,

        self::TABLE             => [
            self::NAME                  => self::TABLE_NAME,
            self::INDEXES               => [
                self::FLDS_NODE_ID          => [
                    self::COLUMNS               => [self::FLDS_NODE_ID, self::FLDS_NODE_REVISION]
                ],
            ],
            self::ID_GENERATOR_TYPE     => Doctrine\ORM\Mapping\ClassMetadataInfo::GENERATOR_TYPE_IDENTITY,
        ],

        self::FIELDS            => [
            self::ID                        => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::NULLABLE                  => false,
                self::ID                        => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            self::FLDS_NODE_ID              => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => false,
                self::LENGTH                    => 40,
            ],
            self::FLDS_NODE_REVISION        => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::NULLABLE                  => false,
            ],
            self::FLDS_VERSION              => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::NULLABLE                  => false,
            ],
            self::FLDS_JSON                 => [
                self::TYPE                      => self::TYPE_JSON,
                self::NULLABLE                  => false,
            ],
        ],
    ];

    public function getRefreshHistoryData(OnlyOfficeIntegrator_Model_AccessToken $token = null)
    {
        return [
            'changes'       => $this->{self::FLDS_JSON}['history']['changes'],
            'created'       => (new Tinebase_DateTime($this->{self::FLDS_JSON}['created']))
                ->setTimezone(Tinebase_Core::getUserTimezone())->toString(),

            'key'           => $token ? $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY} : null,
            'serverVersion' => $this->{self::FLDS_JSON}['history']['serverVersion'],
            'user'          => $this->{self::FLDS_JSON}['user'],
            'version'       => $this->{self::FLDS_VERSION},
        ];
    }
}
