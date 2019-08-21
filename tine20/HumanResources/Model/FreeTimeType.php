<?php
/**
 * class to hold FreeTimeType data
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold FreeTimeType data
 *
 * NOTE: timesheets get clock_out_reason system CF, all auto-tracked timesheets get this prop
 * NOTE: wether an freetime is paid or not is defined via wage_type
 * NOTE: if $allow_planning but not $allow_booking WTDailyReport blpipe takes planning times otherwise tracked times have precedence
 *
 * @package     HumanResources
 * @subpackage  Model
 *
 * @property    string                          $abbreviation           Short abbreviation of the name. Used when there is no space for the full name e.g. in Absence Planning UI
 * @property    string                          $name
 * @property    string                          $description
 * @property    bool                            $system                 this is a record which could not be deleted
 * @property    HumanResources_Model_WageType   $wage_type
 * @property    bool                            $allow_booking          reason can be used in terminal / timesheet
 * @property    bool                            $allow_planning         reason is shown in freetime planner
 * @property    bool                            $enable_timetracking    a new timesheet is created when this freetimetype is used
 *                                                                      the new timesheet get's it's final length from the blpipe based on fill config
 * @property    Timetracker_Model_Timeaccount   $timeaccount            only available with enable_timetracking - if not set the ta of the previous ts is used
 * @property    string                          $compensation_type      none|fixed|fill
 * @property    int                             $compensation_time      fixed compensation time definition
 */
class HumanResources_Model_FreeTimeType extends Tinebase_Record_Abstract
{
    const MODEL_NAME_PART = 'FreeTimeType';
    const TABLE_NAME = 'humanresources_freetimetype';

    const TT_TS_SYSCF_CLOCK_OUT_REASON = 'clock_out_reason';

    const ID_SICKNESS = 'sickness';
    const ID_VACATION = 'vacation';

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
        self::VERSION                   => 1,
        self::RECORD_NAME               => 'Absence reason',
        self::RECORDS_NAME              => 'Absence reasons', // ngettext('Absence reason', 'Absence reasons', n)
        self::TITLE_PROPERTY            => 'name',
        self::HAS_CUSTOM_FIELDS         => true,
        self::HAS_NOTES                 => true,
        self::HAS_TAGS                  => true,
        self::MODLOG_ACTIVE             => true,
        self::EXPOSE_JSON_API           => true,

        self::SINGULAR_CONTAINER_MODE   => true,
        self::HAS_PERSONAL_CONTAINER    => false,

        self::CREATE_MODULE             => false,
        self::APP_NAME                  => HumanResources_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
        ],

        self::FIELDS => [
            'abbreviation' => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 5,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Abbreviation', // _('Abbreviation')
            ],
            'name' => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 255,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Name', // _('Name')
            ],
            'description' => [
                self::TYPE              => self::TYPE_FULLTEXT,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                self::LABEL             => 'Description', // _('Description')
                self::NULLABLE          => true,
            ],
            'system' => [
                self::TYPE              => self::TYPE_BOOLEAN,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                self::DEFAULT_VAL       => false,
                self::LABEL             => 'System', // _('System')
            ],
            'wage_type' => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME        => HumanResources_Model_WageType::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Wage type', // _('Wage type')
                self::QUERY_FILTER      => true,
            ],
            'allow_booking' => [
                self::TYPE              => self::TYPE_BOOLEAN,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                self::DEFAULT_VAL       => false,
                self::LABEL             => 'Allow Booking', // _('Allow Booking')
            ],
            'allow_planning' => [
                self::TYPE              => self::TYPE_BOOLEAN,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                self::DEFAULT_VAL       => false,
                self::LABEL             => 'Allow Planning', // _('Allow Planning')
            ],
            'enable_timetracking' => [ // -> freetime planner
                self::TYPE              => self::TYPE_BOOLEAN,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                self::DEFAULT_VAL       => false,
                self::LABEL             => 'Enable Freetime Timetracking', // _('Enable Freetime Timetracking')
            ],
            'timeaccount' => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => Timetracker_Config::APP_NAME,
                    self::MODEL_NAME        => Timetracker_Model_Timeaccount::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL             => 'Timeaccount', // _('Timeaccount')
                self::QUERY_FILTER      => false,
                self::NULLABLE          => true,
            ],
        ]
    ];
}
