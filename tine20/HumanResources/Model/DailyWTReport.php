<?php
/**
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of a Daily Working Time Report
 *
 * The daily working time report combines multiple source records into a single
 * working time report. A time report is splitted into multiple categories of
 * working time. It's important to note, that the computed times in a report
 * are _not_ the sum of it's source timesheet records:
 * - times are cut according to evaluation_period
 * - break_deduction according to HumanResources_Model_WorkingTime
 * - goodies (might be extra time category) according to HumanResources_Model_WorkingTime
 *
 * DailyWorkingTimeReports are calculated once a day by a scheduler job. New
 * reports are created and all reports which from this and the last month which
 * don't have their is_cleared flag set get updated. Older reports can be
 * created/updated manually in the UI
 *
 * Timesheet records get their working_time_is_cleared and cleared_in fields
 * managed by the WorkingTimeReports calculations and clearance
 * @TODO: disallow to edit workingtime props in ts when clearance is set
 *
 * - controller holt timesheets
 * - regeln (wegschneiden + pausenzeiten) werden darauf angewendet => business rule processor
 * - transportmodel
 * -> report
 *
 * - alle zeiten in sekunden! auch stundenzettel müssen auf sekunden umgestellt werden...
 *
 * @package     HumanResources
 * @subpackage  Model
 *
 * @property Tinebase_DateTime          date
 * @property Tinebase_DateTime          evaluation_period_start
 * @property Tinebase_DateTime          evaluation_period_end
 * @property boolean                    is_cleared
 * @property integer                    break_time_deduction
 * @property integer                    working_time_correction
 * @property integer                    working_time_actual
 * @property integer                    working_time_total
 * @property integer                    working_time_target
 * @property integer                    working_time_target_correction
 * @property integer                    break_time_net
 * @property Tinebase_Record_RecordSet  working_times
 * @property string                     system_remark
 * @property boolean                    calculation_failure
 */
class HumanResources_Model_DailyWTReport extends Tinebase_Record_Abstract
{
    const MODEL_NAME_PART = 'DailyWTReport';

    const FLDS_MONTHLYWTREPORT = 'monthlywtreport';
    const FLDS_WORKING_TIMES = 'working_times';

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
        'version' => 2,
        'recordName' => 'Daily Working Time Report',
        'recordsName' => 'Daily Working Time Reports', // ngettext('Daily Working Time Report', 'Daily Working Time Reports', n)
        'containerProperty' => null,
        'hasRelations' => true,
        'hasCustomFields' => true,
        'hasNotes' => true,
        'hasTags' => true,
        'modlogActive' => true,

        'createModule'    => true,
        'exposeHttpApi'     => true,
        'exposeJsonApi'     => true,

        'appName' => 'HumanResources',
        'modelName' => self::MODEL_NAME_PART,

        self::TITLE_PROPERTY=> "{# {{date - sorting! #}{% if working_time_actual %}{{ working_time_actual |date('H:i', 'GMT')}}{% else %}00:00{% endif %} - {{ date | localizeddate('full', 'none', app.request.locale ) }}",


        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'employee_id' => [
                    'targetEntity' => 'HumanResources_Model_Employee',
                    'fieldName' => 'employee_id',
                    'joinColumns' => [[
                        'name' => 'employee_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ]
            ],
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'monthlywtreport' => [
                    'targetEntity' => HumanResources_Model_MonthlyWTReport::class,
                    'fieldName' => 'monthlywtreport',
                    'joinColumns' => [[
                        'name' => 'monthlywtreport',
                        'referencedColumnName'  => 'id'
                    ]],
                ]
            ],
        ],

        // why do i have to define this -> autodefine???
        'table'             => [
            'name'    => 'humanresources_wt_dailyreport',
            'indexes' => [
                'employee_id' => [
                    'columns' => ['employee_id'],
                ],
            ],
            self::UNIQUE_CONSTRAINTS => [
                'employee_id__date' => [
                    self::COLUMNS       => ['employee_id', 'date'],
                ],
            ],
        ],

        'fields' => [
            'employee_id' => [
                self::LABEL                 => 'Employee',
                self::TYPE                  => 'record',
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                'duplicateCheckGroup' => 'date-employee', // TODO this doesnt work I guess
                'config' => [
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Employee',
                ],
                self::DISABLED              => true,
                self::QUERY_FILTER          => true,
            ],
            'monthlywtreport' => [
                self::LABEL                 => 'Monthly Working Time Report',
                self::TYPE                  => 'record',
                self::INPUT_FILTERS         => ['Zend_Filter_Empty' => false],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::CONFIG                => [
                    'appName'     => HumanResources_Config::APP_NAME,
                    'modelName'   => HumanResources_Model_MonthlyWTReport::MODEL_NAME_PART,
                ],
                self::DISABLED              => true,
                self::QUERY_FILTER          => true,
            ],
            'date' => [
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::LABEL                 => 'Date', // _('Date')
                self::TYPE                  => 'date',
                self::DISABLED              => true,
            ],
            // kommt aus WorkingTime, z.b. von 9-17 uhr, kann auf tagesbasis im report geändert werden, siehe correction properties
            // änderungen stoßen neuberechnung an
            'evaluation_period_start' => [
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                 => 'Evaluation Start Time', // _('Evaluation Start Time')
                self::TYPE                  => 'time',
                self::NULLABLE              => true,
                self::DISABLED              => true,
            ],
            'evaluation_period_end' => [ // kommt aus WorkingTime
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                 => 'Evaluation End Time', // _('Evaluation End Time')
                self::TYPE                  => 'time',
                self::NULLABLE              => true,
                self::DISABLED              => true,
            ],
            'evaluation_period_start_correction' => [
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                 => 'Corrected Evaluation Start Time', // _('Corrected Evaluation Start Time')
                self::TYPE                  => 'time',
                self::NULLABLE              => true,
                self::INPUT_FILTERS         => ['Zend_Filter_Empty' => null],
            ],
            'evaluation_period_end_correction' => [
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                 => 'Corrected Evaluation End Time', // _('Corrected Evaluation End Time')
                self::TYPE                  => 'time',
                self::NULLABLE              => true,
                self::INPUT_FILTERS         => ['Zend_Filter_Empty' => null],
            ],

            // ziel zeit aus WorkingTime
            'working_time_target' => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_DURATION_SEC,
                self::LABEL                 => 'Target Working Time', // _('Target Working Time')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS         => ['Zend_Filter_Empty' => null],
                self::DISABLED              => true,
            ],
            'working_time_target_correction' => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_DURATION_SEC,
                self::LABEL                 => 'Target Working Time Correction', // _('Target Working Time Correction')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS         => ['Zend_Filter_Empty' => null],
            ],

            //  zeit zwischen den zetteln (brutto pausenzeit - in transportklasse) + break_time_deduction
            'break_time_net'    => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_DURATION_SEC,
                self::LABEL                 => 'Break Time Net', // _('Break Time Net')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DISABLED              => true,
            ],
            // WorkingTime - passiert, wenn MA zu wenig pause gemacht hat
            'break_time_deduction' => [self::TYPE                  => self::TYPE_INTEGER,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_DURATION_SEC,
                self::LABEL                 => 'Break Deduction Time', // _('Break Deduction Time')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DISABLED              => true,
            ],
            self::FLDS_WORKING_TIMES  => [
                self::TYPE                  => self::TYPE_RECORDS,
                self::LABEL                 => 'Working Times', // _('Working Times')
                self::NULLABLE              => true,
                self::CONFIG                => [
                    self::APP_NAME              => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => HumanResources_Model_BLDailyWTReport_WorkingTime::MODEL_NAME_PART,
                    self::STORAGE               => self::TYPE_JSON,
                ],
                self::DISABLED              => true,
            ],
            // echte arbeitszeit nach regelanwendung
            'working_time_actual' => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_DURATION_SEC,
                self::LABEL                 => 'Actual Working Time', // _('Actual Working Time')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS         => ['Zend_Filter_Empty' => null],
                self::DISABLED              => true,
            ],
            // manuelles feld für korrekturen ("musterschüler")
            'working_time_correction' => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_DURATION_SEC,
                self::LABEL                 => 'Working Time Correction', // _('Working Time Correction')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS         => ['Zend_Filter_Empty' => null],
            ],
            'working_time_total' => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_DURATION_SEC,
                self::LABEL                 => 'Total Working Time', // _('Total Working Time')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS         => ['Zend_Filter_Empty' => null],
                self::DISABLED              => true,
            ],
            // z.b. krankheit, urlaub, feiertag (bei regelarbeit leer)
            'system_remark' => [
                self::LABEL                 => 'System Remark', // _('System Remark')
                self::TYPE                  => 'string',
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE              => true,
                self::DISABLED              => true,
                self::QUERY_FILTER          => true,
            ],
            'user_remark' => [
                self::LABEL                 => 'Remark', // _('Remark')
                self::TYPE                  => 'text',
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE              => true,
                self::QUERY_FILTER          => true,
            ],
            // monatsprotokoll rechnet ab - nach übergabe an lohnbuchhaltung
            'is_cleared' => [
                self::LABEL                 => 'Is Cleared', // _('Is Cleared')
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0],
                self::TYPE                  => 'boolean',
                self::DEFAULT_VAL           => 0,
                self::SHY                   => true,
                self::COPY_OMIT             => true,
                self::DISABLED              => true,
            ],
            'calculation_failure' => [
                self::LABEL                 => 'Calculation Error', // _('Calculation Error')
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0],
                self::TYPE                  => 'boolean',
                self::DEFAULT_VAL           => 0,
                self::COPY_OMIT             => true,
            ],
        ]
    ];

    /**
     * @return HumanResources_Model_DailyWTReport
     */
    public function getCleanClone()
    {
        $result = clone $this;
        $result->break_time_net = 0;
        $result->break_time_deduction = 0;
        $result->working_time_actual = 0;
        $result->working_time_target = 0;
        $result->working_time_total = 0;
        $result->working_times = null;
        $result->evaluation_period_start = null;
        $result->evaluation_period_end = null;
        $result->calculation_failure = 0;
        $result->system_remark = '';

        return $result;
    }

    /**
     * @return int
     */
    public function getIsWorkingTime()
    {
        return (int)$this->working_time_actual + (int)$this->working_time_correction;
    }

    /**
     * @return int
     */
    public function getShouldWorkingTime()
    {
        return (int)$this->working_time_target + (int)$this->working_time_target_correction;
    }
}