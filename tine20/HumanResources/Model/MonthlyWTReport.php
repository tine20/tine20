<?php
/**
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of a Monthly Working Time Report
 *
 *
 * @package     HumanResources
 * @subpackage  Model
 *
 * @proeprty HumanResources_Model_Employee  employee_id
 * @property Tinebase_Record_RecordSet      dailywtreports
 * @property Tinebase_Record_RecordSet      working_times
 * @proeprty string                         month
 * @property boolean                        is_cleared
 * @property integer                        working_time_actual
 * @property integer                        working_time_target
 * @property integer                        working_time_correction
 * @property integer                        working_time_balance
 * @property integer                        working_time_balance_previous
 */
class HumanResources_Model_MonthlyWTReport extends Tinebase_Record_Abstract
{
    const MODEL_NAME_PART                       = 'MonthlyWTReport';
    const TABLE_NAME                            = 'humanresources_wt_monthlyreport';

    const FLDS_CORRECTIONS                      = 'corrections';
    const FLDS_MONTH                            = 'month';
    const FLDS_EMPLOYEE_ID                      = 'employee_id';
    const FLDS_DAILY_WT_REPORTS                 = 'dailywtreports';
    const FLDS_IS_CLEARED                       = 'is_cleared';
    const FLDS_LAST_CALCULATION                 = 'last_calc';
    const FLDS_WORKING_TIME_ACTUAL              = 'working_time_actual';
    const FLDS_WORKING_TIME_TARGET              = 'working_time_target';
    const FLDS_WORKING_TIME_CORRECTION          = 'working_time_correction';
    const FLDS_WORKING_TIME_BALANCE             = 'working_time_balance';
    const FLDS_WORKING_TIME_BALANCE_PREVIOUS    = 'working_time_balance_previous';

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
        self::VERSION                   => 2,
        self::RECORD_NAME               => 'Monthly Working Time Report', // gettext('GENDER_Monthly Working Time Report')
        self::RECORDS_NAME              => 'Monthly Working Time Reports', // ngettext('Monthly Working Time Report', 'Monthly Working Time Reports', n)
        self::TITLE_PROPERTY            => self::FLDS_MONTH,
        self::HAS_CUSTOM_FIELDS         => true,
        self::HAS_NOTES                 => true,
        self::HAS_TAGS                  => true,
        self::MODLOG_ACTIVE             => true,

        self::CREATE_MODULE             => true,
        self::EXPOSE_JSON_API           => true,
        self::EXPOSE_HTTP_API           => true,
        self::HAS_PERSONAL_CONTAINER    => false,
        self::DELEGATED_ACL_FIELD       => self::FLDS_EMPLOYEE_ID,

        self::APP_NAME                  => HumanResources_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::FLDS_EMPLOYEE_ID . '__' . self::FLDS_MONTH
                                                => [
                    self::COLUMNS                   => [self::FLDS_EMPLOYEE_ID, self::FLDS_MONTH],
                ],
            ],
            self::INDEXES                   => [
                self::FLDS_MONTH                => [
                    self::COLUMNS                   => [self::FLDS_MONTH],
                ]
            ],
        ],

        self::ASSOCIATIONS              => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLDS_EMPLOYEE_ID => [
                    'targetEntity' => HumanResources_Model_Employee::class,
                    'fieldName' => self::FLDS_EMPLOYEE_ID,
                    'joinColumns' => [[
                        'name' => self::FLDS_EMPLOYEE_ID,
                        'referencedColumnName'  => 'id'
                    ]],
                ]
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLDS_CORRECTIONS          => [],
                self::FLDS_EMPLOYEE_ID => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'division_id' => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES => [
                                Tinebase_Record_Expander::PROPERTY_CLASS_ACCOUNT_GRANTS => [],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        self::FIELDS                    => [
            self::FLDS_EMPLOYEE_ID              => [
                self::LABEL                         => 'Employee', // _('Employee')
                self::TYPE                          => self::TYPE_RECORD,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY          => false,
                    Zend_Filter_Input::PRESENCE             => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::CONFIG                        => [
                    self::APP_NAME                      => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                    => HumanResources_Model_Employee::MODEL_NAME_PART,
                    self::RESOLVE_DELETED               => true,
                ],
                self::QUERY_FILTER                  => true,
            ],
            self::FLDS_MONTH                    => [
                self::LABEL                         => 'Month', // _('Month')
                self::TYPE                          => self::TYPE_STRING,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
                self::LENGTH                        => 7, // 2019-01 => char(7) => TODO set collation latin1(? 1 byte), type char not varchar
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY          => false,
                    Zend_Filter_Input::PRESENCE             => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::QUERY_FILTER                  => true,
            ],
            self::FLDS_DAILY_WT_REPORTS         => [
                self::LABEL                         => 'Daily Working Time Reports', // _('Daily Working Time Reports')
                self::TYPE                          => self::TYPE_RECORDS,
                self::UI_CONFIG                     => [
                    'allowDelete'                       => false, // we should have a mc option for models which could not be deleted
                ],
                self::VALIDATORS                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::CONFIG                        => [
                    self::APP_NAME                      => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                    => HumanResources_Model_DailyWTReport::MODEL_NAME_PART,
                    self::REF_ID_FIELD                  => HumanResources_Model_DailyWTReport::FLDS_MONTHLYWTREPORT,
                ]
            ],
            self::FLDS_LAST_CALCULATION         => [
                self::LABEL                         => 'Last Calculation', // _('Last Calculation')
                self::TYPE                          => self::TYPE_DATETIME,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLDS_WORKING_TIME_BALANCE_PREVIOUS => [
                self::TYPE                          => self::TYPE_INTEGER,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_DURATION_SEC,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
                self::LABEL                         => 'Working Time Balance Previous Month', // _('Working Time Balance Previous Month')
                self::VALIDATORS                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL                   => 0,
            ],
            self::FLDS_WORKING_TIME_ACTUAL      => [
                self::TYPE                          => self::TYPE_INTEGER,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_DURATION_SEC,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
                self::LABEL                         => 'Actual Working Time', // _('Actual Working Time')
                self::VALIDATORS                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL                   => 0,
            ],
            self::FLDS_WORKING_TIME_TARGET      => [
                self::TYPE                          => self::TYPE_INTEGER,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_DURATION_SEC,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
                self::LABEL                         => 'Target Working Time', // _('Target Working Time')
                self::VALIDATORS                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL                   => 0,
            ],
            self::FLDS_CORRECTIONS              => [
                self::TYPE                          => self::TYPE_RECORDS,
                self::LABEL                         => 'Working Time Correction Requests', // _('Working Time Correction Requests')
                self::NULLABLE                      => true,
                self::DOCTRINE_IGNORE               => true,
                self::CONFIG                        => [
                    self::APP_NAME                      => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                    => HumanResources_Model_WTRCorrection::MODEL_NAME_PART,
                    self::REF_ID_FIELD                  => HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY,
                ],
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLDS_WORKING_TIME_CORRECTION  => [
                self::TYPE                          => self::TYPE_INTEGER,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_DURATION_SEC,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
                self::LABEL                         => 'Sum Accepted Working Time Correction', // _('Sum Accepted Working Time Correction')
                self::VALIDATORS                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL                   => 0,
            ],
            self::FLDS_WORKING_TIME_BALANCE     => [
                self::TYPE                          => self::TYPE_INTEGER,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_DURATION_SEC,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
                self::LABEL                         => 'Total Working Time Balance', // _('Total Working Time Balance')
                self::VALIDATORS                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL                   => 0,
            ],
            self::FLDS_IS_CLEARED               => [
                self::LABEL                         => 'Is Cleared', // _('Is Cleared')
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => 0
                ],
                self::DEFAULT_VAL                   => 0,
                self::COPY_OMIT                     => true,
            ],
            // data-transport for exports
            HumanResources_Model_DailyWTReport::FLDS_WORKING_TIMES => [
                self::SHY                           => true,
                self::TYPE                          => self::TYPE_VIRTUAL,
                self::DISABLED                      => true,
            ],
        ],
    ];

    /**
     * @return HumanResources_Model_MonthlyWTReport
     */
    public function getCleanClone()
    {
        $result = clone $this;

        return $result;
    }

    /**
     * @return DateTime[]
     */
    public function getPeriod()
    {
        $from = new Tinebase_DateTime("{$this->{self::FLDS_MONTH}}-01 00:00:00");
        $until = $from->getClone()->addMonth(1)->subSecond(1);

        return [
            'from' => $from,
            'until' => $until
        ];
    }
}
