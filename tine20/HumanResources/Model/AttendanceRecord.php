<?php declare(strict_types=1);
/**
 * class to hold AttendanceRecord data
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * class to hold AttendanceRecord data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_AttendanceRecord extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'AttendanceRecord';
    const TABLE_NAME = 'humanresources_attendance_record';

    const STATUS_CLOSED = 'closed';
    const STATUS_FAULTY = 'faulty';
    const STATUS_OPEN = 'open';

    const TYPE_CLOCK_IN = 'clock_in';
    const TYPE_CLOCK_OUT = 'clock_out';
    const TYPE_CLOCK_PAUSED = 'clock_paused';

    const FLD_ACCOUNT_ID = 'account_id';
    const FLD_AUTOGEN = 'autogen';
    const FLD_BLPROCESSED = 'blprocessed';
    const FLD_CREATION_CONFIG = 'creation_config';
    const FLD_DEVICE_ID = 'device_id';
    const FLD_FREETIMETYPE_ID = 'freetimetype_id';
    const FLD_SEQUENCE = 'sequence';
    const FLD_STATUS = 'status';
    const FLD_TIMESTAMP = 'ts';
    const FLD_TYPE = 'type';
    const FLD_REFID = 'refId';

    const META_DATA = 'metaData';
    const CLOCK_OUT_GRACEFULLY = 'clockOutGracefully';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION               => 2,
        self::MODLOG_ACTIVE         => true,
        self::HAS_XPROPS            => true,

        self::APP_NAME              => HumanResources_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,

        self::JSON_EXPANDER         => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_FREETIMETYPE_ID => [],
            ],
        ],

        self::TABLE                 => [
            self::NAME                  => self::TABLE_NAME,
            self::INDEXES               => [
                self::FLD_DEVICE_ID         => [
                    self::COLUMNS               => [self::FLD_DEVICE_ID, self::FLD_ACCOUNT_ID, self::FLD_STATUS]
                ],
                self::FLD_TIMESTAMP         => [
                    self::COLUMNS               => [self::FLD_ACCOUNT_ID, self::FLD_TIMESTAMP]
                ],
                self::FLD_ACCOUNT_ID        => [
                    self::COLUMNS               => [self::FLD_ACCOUNT_ID, self::FLD_STATUS]
                ],
                self::FLD_BLPROCESSED       => [
                    self::COLUMNS               => [self::FLD_BLPROCESSED, self::FLD_DEVICE_ID, self::FLD_ACCOUNT_ID, self::FLD_SEQUENCE]
                ],
            ],
            self::UNIQUE_CONSTRAINTS    => [
                self::FLD_SEQUENCE          => [
                    self::COLUMNS               => [self::FLD_SEQUENCE]
                ],
            ],
        ],

        self::FIELDS                => [
            self::FLD_DEVICE_ID         => [
                self::TYPE                  => self::TYPE_RECORD,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::CONFIG                => [
                    self::APP_NAME              => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => HumanResources_Model_AttendanceRecorderDevice::MODEL_NAME_PART,
                ],
            ],
            self::FLD_ACCOUNT_ID        => [
                self::TYPE                  => self::TYPE_USER,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_SEQUENCE          => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::AUTOINCREMENT         => true,
                self::READ_ONLY             => true,
            ],
            self::FLD_TIMESTAMP         => [
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_TYPE              => [
                self::TYPE                  => self::TYPE_STRING,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    Zend_Validate_InArray::class    => [
                        self::TYPE_CLOCK_IN,
                        self::TYPE_CLOCK_OUT,
                        self::TYPE_CLOCK_PAUSED,
                    ]
                ],
            ],
            self::FLD_STATUS            => [
                self::TYPE                  => self::TYPE_STRING,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    Zend_Validate_InArray::class    => [
                        self::STATUS_CLOSED,
                        self::STATUS_FAULTY,
                        self::STATUS_OPEN,
                    ]
                ],
            ],
            self::FLD_BLPROCESSED       => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => 0,
            ],
            self::FLD_AUTOGEN           => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => 0,
            ],
            self::FLD_CREATION_CONFIG   => [
                self::TYPE                  => self::TYPE_TEXT,
            ],
            self::FLD_FREETIMETYPE_ID   => [
                self::TYPE                  => self::TYPE_RECORD,
                self::NULLABLE              => true,
                self::CONFIG                => [
                    self::APP_NAME              => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => HumanResources_Model_FreeTimeType::MODEL_NAME_PART,
                ],
            ],
            self::FLD_REFID             => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 40,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function getConfig(): HumanResources_Config_AttendanceRecorder
    {
        return unserialize($this->{self::FLD_CREATION_CONFIG});
    }
}
