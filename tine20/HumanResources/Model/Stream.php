<?php
/**
 * class to hold Stream data
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold Stream data
 *
 * @package     HumanResources
 * @subpackage  Model
 *
 * @property Tinebase_Record_RecordSet $stream_modalities
 * @property Tinebase_Record_RecordSet $responsibles
 * @property Tinebase_Record_RecordSet $time_accounts
 */
class HumanResources_Model_Stream extends Tinebase_Record_NewAbstract
{
    const FLD_TYPE              = 'type';
    const FLD_TITLE             = 'title';
    const FLD_DESCRIPTION       = 'description';
    const FLD_BOARDINFO         = 'boardinfo';
    const FLD_RESPONSIBLES      = 'responsibles';
    const FLD_STREAM_MODALITIES = 'stream_modalities';
    const FLD_TIME_ACCOUNTS     = 'time_accounts';

    const MODEL_NAME_PART       = 'Stream';
    const TABLE_NAME            = 'humanresources_stream';

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
        self::VERSION                   => 1,
        self::RECORD_NAME               => 'Stream',
        self::RECORDS_NAME              => 'Streams', // ngettext('Stream', 'Streams', n)
        self::HAS_RELATIONS             => true,
        self::COPY_RELATIONS            => false,
        self::HAS_NOTES                 => true,
        self::MODLOG_ACTIVE             => true,
        self::HAS_ATTACHMENTS           => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,

        self::CREATE_MODULE             => true,

        self::APP_NAME                  => HumanResources_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        /*self::ASSOCIATIONS          => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_MANY => [
                'id'                => [
                    'targetEntity'          => HumanResources_Model_StreamModality::class,
                    'fieldName'             => 'id',
                    'mappedBy'              => HumanResources_Model_StreamModality::FLD_STREAM_ID,
                    'isCascadeRemove'       => true,
                    'cascade'               => ['remove'],
                ]
            ],
        ],*/

        self::TABLE                 => [
            self::NAME                  => self::TABLE_NAME,
            self::INDEXES               => [
                self::FLD_DESCRIPTION       => [
                    self::COLUMNS               => [self::FLD_DESCRIPTION],
                    self::FLAGS                 => [self::TYPE_FULLTEXT]
                ],
                self::FLD_BOARDINFO         => [
                    self::COLUMNS               => [self::FLD_BOARDINFO],
                    self::FLAGS                 => [self::TYPE_FULLTEXT]
                ]
            ],
            self::UNIQUE_CONSTRAINTS    => [
                self::FLD_TITLE             => [
                    self::COLUMNS               => [self::FLD_TITLE, self::FLD_DELETED_TIME]
                ]
            ]
        ],

        self::FIELDS                => [
            self::FLD_TITLE             => [
                self::LABEL                 => 'Title', // _('Title')
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
                self::QUERY_FILTER          => true,
            ],
            self::FLD_DESCRIPTION       => [
                self::LABEL                 => 'Description', // _('Description')
                self::TYPE                  => self::TYPE_FULLTEXT,
                self::NULLABLE              => true,
                self::QUERY_FILTER          => true,
            ],
            self::FLD_TYPE              => [
                self::LABEL                 => 'Type', // _('Type')
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::NAME                  => HumanResources_Config::STREAM_TYPE,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
            ],
            self::FLD_BOARDINFO         => [
                self::LABEL                 => 'Board Info', // _('Board Info')
                self::TYPE                  => self::TYPE_FULLTEXT,
                self::NULLABLE              => true,
                self::QUERY_FILTER          => true,
            ],
            self::FLD_STREAM_MODALITIES => [
                self::LABEL                 => 'Stream Modalities', // _('Stream Modalities')
                self::TYPE                  => self::TYPE_RECORDS,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
                self::CONFIG                => [
                    self::APP_NAME              => \HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => \HumanResources_Model_StreamModality::MODEL_NAME_PART,
                    self::RECORD_CLASS_NAME     => \HumanResources_Model_StreamModality::class,
                    self::DEPENDENT_RECORDS     => true,
                    self::REF_ID_FIELD          => \HumanResources_Model_StreamModality::FLD_STREAM_ID,
                ]
            ],
            self::FLD_RESPONSIBLES      => [
                self::LABEL                 => 'Responsibles', // _('Responsibles')
                self::TYPE                  => self::TYPE_VIRTUAL,
                self::CONFIG                => [
                    self::TYPE                  => self::TYPE_RELATIONS,
                    self::LABEL                 => 'Responsibles',
                    self::CONFIG                => [
                        self::APP_NAME              => \Addressbook_Config::APP_NAME,
                        self::MODEL_NAME            => \Addressbook_Model_Contact::MODEL_PART_NAME,
                        self::TYPE                  => 'RESPONSIBLES'
                    ]
                ]
            ],
            self::FLD_TIME_ACCOUNTS     => [
                self::LABEL                 => 'Timeaccounts', // _('Timeaccounts')
                self::TYPE                  => self::TYPE_VIRTUAL,
                self::CONFIG                => [
                    self::TYPE                  => self::TYPE_RELATIONS,
                    self::LABEL                 => 'Timeaccounts',
                    self::CONFIG                => [
                        self::APP_NAME              => Timetracker_Config::APP_NAME,
                        self::MODEL_NAME            => Timetracker_Model_Timeaccount::MODEL_NAME_PART,
                        self::TYPE                  => Timetracker_Model_Timeaccount::MODEL_NAME_PART
                    ]
                ]
            ]
        ],
    ];
}
