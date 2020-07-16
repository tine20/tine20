<?php
/**
 * class to hold Stream Modality Report data
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * class to hold Stream Modality Report data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_StreamModalReport extends Tinebase_Record_NewAbstract
{
    const FLD_STREAM_MODALITY_ID    = 'stream_modality_id';
    const FLD_INTERVAL              = 'interval';
    const FLD_START                 = 'start';
    const FLD_END                   = 'end';
    const FLD_IS                    = 'is';
    const FLD_OVERFLOW_IN           = 'overflow_in';
    const FLD_OVERFLOW_OUT          = 'overflow_out';
    const FLD_CLOSED                = 'closed';
    const FLD_TIMESHEETS            = 'timesheets';

    const MODEL_NAME_PART       = 'StreamModalReport';
    const TABLE_NAME            = 'humanresources_streammodalityreport';

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
        self::VERSION               => 1,
        self::HAS_RELATIONS         => true,
        self::COPY_RELATIONS        => false,
        self::MODLOG_ACTIVE         => true,
        self::IS_DEPENDENT          => true,

        self::APP_NAME              => HumanResources_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,

        self::ASSOCIATIONS          => [
            ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_STREAM_MODALITY_ID    => [
                    'targetEntity'                  => HumanResources_Model_StreamModality::class,
                    'fieldName'                     => self::FLD_STREAM_MODALITY_ID,
                    'joinColumns'                   => [[
                        'name' => self::FLD_STREAM_MODALITY_ID,
                        'referencedColumnName'  => 'id'
                    ]],
                ]
            ],
        ],

        self::TABLE                 => [
            self::NAME                  => self::TABLE_NAME,
            self::INDEXES               => [
                self::FLD_STREAM_MODALITY_ID         => [
                    self::COLUMNS               => [self::FLD_STREAM_MODALITY_ID]
                ],
            ],
        ],

        self::FIELDS                => [
            self::FLD_STREAM_MODALITY_ID    => [
                self::TYPE                      => self::TYPE_RECORD,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_StreamModality::MODEL_NAME_PART,
                ]
            ],
            self::FLD_START                 => [
                self::LABEL                     => 'Start', // _('Start')
                self::TYPE                      => self::TYPE_DATE,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
            ],
            self::FLD_END                   => [
                self::LABEL                     => 'End', // _('End')
                self::TYPE                      => self::TYPE_DATE,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
            ],
            self::FLD_INTERVAL              => [
                self::LABEL                     => 'Interval', // _('Interval')
                self::TYPE                      => self::TYPE_INTEGER,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
            ],
            self::FLD_IS                    => [
                self::LABEL                     => 'Number of Intervals', // _('Number of Intervals')
                self::TYPE                      => self::TYPE_INTEGER,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
            ],
            self::FLD_OVERFLOW_IN           => [
                self::LABEL                     => 'Overflow In', // _('Overflow In')
                self::TYPE                      => self::TYPE_INTEGER,
                self::DEFAULT_VAL               => 0,
            ],
            self::FLD_OVERFLOW_OUT          => [
                self::LABEL                     => 'Overflow Out', // _('Overflow Out')
                self::TYPE                      => self::TYPE_INTEGER,
                self::DEFAULT_VAL               => 0,
            ],
            self::FLD_CLOSED                => [
                self::LABEL                     => 'Closed', // _('Closed')
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL               => 0,
            ],
            self::FLD_TIMESHEETS            => [
                self::TYPE                      => self::TYPE_VIRTUAL,
                self::CONFIG                    => [
                    self::TYPE                      => self::TYPE_RELATIONS,
                    self::LABEL                     => 'Timesheets',
                    self::CONFIG                    => [
                        self::APP_NAME                  => Timetracker_Config::APP_NAME,
                        self::MODEL_NAME                => Timetracker_Model_Timesheet::MODEL_NAME_PART,
                        self::TYPE                      => self::MODEL_NAME_PART
                    ]
                ]
            ],
        ],
    ];

    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = [
        [
            'relatedApp' => Timetracker_Config::APP_NAME,
            'relatedModel' => Timetracker_Model_Timesheet::MODEL_NAME_PART,
            'config' => [
                ['type' => self::MODEL_NAME_PART, 'degree' => Tinebase_Model_Relation::DEGREE_SIBLING, 'max' => '0:1'],
            ],
        ],
    ];
}
