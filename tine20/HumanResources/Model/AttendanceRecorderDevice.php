<?php declare(strict_types=1);
/**
 * class to hold AttendanceRecorderDevice data
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold AttendanceRecorderDevice data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_AttendanceRecorderDevice extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'AttendanceRecorderDevice';
    const TABLE_NAME = 'humanresources_attendance_record_device';

    const FLD_ALLOW_MULTI_START = 'allowMultiStart';
    const FLD_ALLOW_PAUSE = 'allowPause';
    const FLD_BLPIPE = 'blpipe';
    const FLD_IS_TINE_UI_DEVICE = 'is_tine_ui_device';
    const FLD_NAME = 'name';
    const FLD_STOPS = 'stops';
    const FLD_STARTS = 'starts';

    const SYSTEM_WORKING_TIME_ID = 'wt00000000000000000000000000000000000000';
    const SYSTEM_PROJECT_TIME_ID = 'pt00000000000000000000000000000000000000';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 2,
        self::APP_NAME                  => HumanResources_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::TITLE_PROPERTY            => self::FLD_NAME,

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_STOPS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => [],
                    ],
                ],
                self::FLD_STARTS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => [],
                    ],
                ],
            ],
        ],

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_NAME                  => [
                    self::COLUMNS                   => [self::FLD_NAME, 'deleted_time'],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_NAME                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_IS_TINE_UI_DEVICE     => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL               => false,
            ],
            self::FLD_ALLOW_MULTI_START     => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL               => false,
            ],
            self::FLD_ALLOW_PAUSE           => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL               => true,
            ],
            self::FLD_STOPS                 => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_AttendanceRecorderDeviceRef::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_PARENT_ID,
                    self::DEPENDENT_RECORDS         => true,
                    self::FORCE_VALUES              => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE => self::FLD_STOPS,
                    ],
                    self::ADD_FILTERS               => [
                        ['field' => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE, 'operator' => 'equals', 'value' => self::FLD_STOPS],
                    ],
                ],
            ],
            self::FLD_STARTS                => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_AttendanceRecorderDeviceRef::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_PARENT_ID,
                    self::DEPENDENT_RECORDS         => true,
                    self::FORCE_VALUES              => [
                        HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE => self::FLD_STARTS,
                    ],
                    self::ADD_FILTERS               => [
                        ['field' => HumanResources_Model_AttendanceRecorderDeviceRef::FLD_TYPE, 'operator' => 'equals', 'value' => self::FLD_STARTS],
                    ],
                ],
            ],
            // field restarts?
            // field pauses?
            self::FLD_BLPIPE                => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::NULLABLE                  => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_BLAttendanceRecorder_Config::MODEL_NAME_PART,
                    self::STORAGE                   => self::TYPE_JSON,
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
