<?php
/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold AccessToken data
 *
 * - shared access t0kens
 * - first one opening/editing creates access token
 *  -> creates open/callback urls with t0ken
 *  - each new editor get's added to the same t0ken
 *  - last close/absence invalidates t0ken
 * - get/updates are limited to t0ken owners
 *
 * @package   OnlyOfficeIntegrator
 * @subpackage    Model
 */
class OnlyOfficeIntegrator_Model_AccessToken extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'AccessToken';

    const TABLE_NAME = 'onlyoffice_accesstoken';

    const FLDS_DOCUMENT_TYPE = 'document_type';
    const FLDS_FILE_TYPE = 'file_type';
    const FLDS_GRANTS = 'grants';
    const FLDS_INVALIDATED = 'invalidated';
    const FLDS_KEY = 'key';
    const FLDS_LAST_SEEN = 'last_seen';
    const FLDS_NODE_ID = 'node_id';
    const FLDS_NODE_REVISION = 'node_revision';
    const FLDS_SESSION_ID = 'session_id';
    const FLDS_TITLE = 'title';
    const FLDS_TOKEN = 'token';
    const FLDS_USER_ID = 'user_id';
    const FLDS_RESOLUTION = 'resolution';
    const FLDS_MODE = 'mode';
    const FLDS_LAST_SAVE = 'last_save';
    const FLDS_LAST_SAVE_FORCED = 'last_save_forced';

    const GRANT_READ = 1;
    const GRANT_WRITE = 2;

    const MODE_READ_ONLY = 1;
    const MODE_READ_WRITE = 2;

    const TEMP_FILE_REVISION = -1;


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
        self::VERSION           => 3,
        self::APP_NAME          => OnlyOfficeIntegrator_Config::APP_NAME,
        self::MODEL_NAME        => self::MODEL_NAME_PART,

        self::TABLE             => [
            self::NAME                  => self::TABLE_NAME,
            self::INDEXES               => [
                self::FLDS_TOKEN            => [
                    self::COLUMNS               => [self::FLDS_TOKEN]
                ],
                self::FLDS_KEY              => [
                    self::COLUMNS               => [self::FLDS_KEY]
                ],
                self::FLDS_NODE_ID          => [
                    self::COLUMNS               => [self::FLDS_NODE_ID]
                ],
                self::FLDS_LAST_SEEN        => [
                    self::COLUMNS               => [self::FLDS_LAST_SEEN]
                ]
            ],
            self::ID_GENERATOR_TYPE     => Doctrine\ORM\Mapping\ClassMetadataInfo::GENERATOR_TYPE_IDENTITY,
        ],

        self::FIELDS            => [
            self::ID                        => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::NULLABLE                  => false,
                self::ID                        => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY  => true],
            ],
            self::FLDS_GRANTS               => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::NULLABLE                  => false,
                self::DEFAULT_VAL               => self::GRANT_READ,
            ],
            self::FLDS_LAST_SEEN            => [
                self::TYPE                      => self::TYPE_DATETIME,
                self::NULLABLE                  => false,
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
            self::FLDS_SESSION_ID           => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => false,
                self::LENGTH                    => 128, // as by access_log
            ],
            self::FLDS_TOKEN                => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => false,
                self::LENGTH                    => 40,
            ],
            self::FLDS_USER_ID              => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => false,
                self::LENGTH                    => 40,
            ],
            self::FLDS_INVALIDATED          => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::NULLABLE                  => false,
                self::DEFAULT_VAL               => 0,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY  => true],
            ],
            self::FLDS_KEY                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => false,
                self::LENGTH                    => 255,
            ],
            self::FLDS_DOCUMENT_TYPE        => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => false,
                self::LENGTH                    => 255,
            ],
            self::FLDS_FILE_TYPE            => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => false,
                self::LENGTH                    => 255,
            ],
            self::FLDS_TITLE                => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => false,
                self::LENGTH                    => 255,
            ],
            self::FLDS_RESOLUTION           => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::NULLABLE                  => false,
                self::DEFAULT_VAL               => 0,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY  => true],
            ],
            self::FLDS_MODE                 => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::NULLABLE                  => false,
                self::DEFAULT_VAL               => self::MODE_READ_WRITE,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY  => true],
            ],
            self::FLDS_LAST_SAVE            => [
                self::TYPE                      => self::TYPE_DATETIME,
                self::NULLABLE                  => false,
                self::DEFAULT_VAL               => self::CURRENT_TIMESTAMP,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY  => true],
            ],
            self::FLDS_LAST_SAVE_FORCED     => [
                self::TYPE                      => self::TYPE_DATETIME,
                self::NULLABLE                  => false,
                self::DEFAULT_VAL               => self::CURRENT_TIMESTAMP,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY  => true],
            ],
        ],
    ];

    public static function getBaseUrl()
    {
        static $baseUrl = null;
        if (null === $baseUrl) {
            $tineUrl = OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::TINE20_SERVER_URL};
            if (empty($tineUrl)) {
                $tineUrl = Tinebase_Core::getUrl();
            }
            $baseUrl = rtrim($tineUrl, '/') . '/' . OnlyOfficeIntegrator_Config::APP_NAME;
        }
        return $baseUrl;
    }
    
    public function getEditorConfig()
    {
        $user = Tinebase_Core::getUser();
        if ($user->getId() !== $this->{self::FLDS_USER_ID}) {
            $user = Tinebase_User::getInstance()->getFullUserById($this->{self::FLDS_USER_ID});
        }

        return [
            'document'      => [
                'fileType'      => $this->{self::FLDS_FILE_TYPE},
                'key'           => $this->{self::FLDS_KEY},
                'title'         => $this->{self::FLDS_TITLE},
                'url'           => static::getBaseUrl() . '/getDocument/' . $this->{self::FLDS_TOKEN},
                'permissions'   => [
                    'download'      => true,
                    'edit'          => $this->{self::FLDS_GRANTS} >= self::GRANT_WRITE,
                    'changeHistory' => $this->{self::FLDS_GRANTS} >= self::GRANT_WRITE,
                    'print'         => true,
                ],
            ],
            'documentType' => $this->{self::FLDS_DOCUMENT_TYPE},
            'editorConfig'  => [
                'mode'          => $this->{self::FLDS_GRANTS} >= self::GRANT_WRITE ? 'edit' : 'view',
                'callbackUrl'   => static::getBaseUrl() . '/updateStatus/' . $this->{self::FLDS_TOKEN},
                'user'          => [
                    'id'            => $user->getId(),
                    'name'          => $user->accountDisplayName,
                ]
            ]
        ];
    }
}
