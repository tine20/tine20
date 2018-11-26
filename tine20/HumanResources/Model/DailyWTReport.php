<?php
/**
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of a Daily Working Time Report
 *
 * The daily working time report combines multiple source records into a single
 * working time report. A time report is slitted into multiple categories of
 * working time. It's important to note, that the computed times in a report
 * are _not_ the sum of it's source timesheet records:
 * - times are cut according to evaluation_period
 * - break_deduction according to WorkTimeModel
 * - goodies (might be extra time category) according to WorkTimeModel
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
 * @package     HumanResources
 * @subpackage  Model
 *
 */
class HumanResources_Model_DailyWTReport extends Tinebase_Record_Abstract
{
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
        'version' => 1,
        'recordName' => 'Daily Working Time Report',
        'recordsName' => 'Daily Working Time Reports', // ngettext('Daily Working Time Report', 'Daily Working Time Reports', n)
        'containerProperty' => null,
        'titleProperty' => null, // TODO change this?
        'hasRelations' => false, // TODO really no relations?
        'hasCustomFields' => true,
        'hasNotes' => true,
        'hasTags' => true,
        'modlogActive' => true,
        'hasAttachments' => false,

        'createModule'    => true,
        'exposeHttpApi'     => true,
        'exposeJsonApi'     => true,

        'isDependent'     => false, // TODO remove?

        'appName' => 'HumanResources',
        'modelName' => 'DailyWTReport',

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
        ],

        // why do i have to define this -> autodefine???
        'table'             => [
            'name'    => 'humanresources_wt_dailyreport',
            'indexes' => [
                'employee_id' => [
                    'columns' => ['employee_id'],
                ],
            ],
        ],

        'fields' => [
            'employee_id' => [
                'label' => 'Employee',
                'type'  => 'record',
                'doctrineIgnore'        => true, // already defined as association
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL],
                'duplicateCheckGroup' => 'year-employee',
                'config' => [
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Employee',
                    'idProperty'  => 'id'
                ]
            ],
            'evaluation_period_start' => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                'label'         => 'Evaluation Start Time', // _('Evaluation Start Time')
                'type'          => 'time',
                'nullable'      => true,
            ],
            'evaluation_period_end' => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                'label'         => 'Evaluation End Time', // _('Evaluation End Time')
                'type'          => 'time',
                'nullable'      => true,
            ],
            'absence_time_paid' => [
                'label'         => 'Paid Absence Time', // _('Paid Absence Time')
                'type'          => 'integer',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'  => ['Zend_Filter_Empty' => null],
                'nullable'      => true,
                'default'       => 0,
            ],
            'absence_time_unpaid' => [
                'label'         => 'Unpaid Absence Time', // _('Unpaid Absence Time')
                'type'          => 'integer',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'  => ['Zend_Filter_Empty' => null],
                'nullable'      => true,
                'default'       => 0,
            ],
//            'absence' => [
//                'label'      => 'Absence', // _('Absence')
//                'type' => 'integer',
//                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
//                'inputFilters' => ['Zend_Filter_Empty' => null],
//                'nullable'     => true,
//                'default' => 0,
//            ],
            'break_time_net'    => [
                'label'         => 'Break Time Net', // _('Break Time Net')
                'type'          => 'integer',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'  => ['Zend_Filter_Empty' => false],
                'nullable'      => true,
                'default'       => 0,
            ],
            'break_time_deduction' => [
                'label'         => 'Break Deduction Time', // _('Break Deduction Time')
                'type'          => 'integer',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'  => ['Zend_Filter_Empty' => false],
                'nullable'      => true,
                'default'       => 0,
            ],
            'feast_time' => [
                'type'          => 'integer',
                'label'         => 'Feast Time', // _('Feast Time')
                'nullable'      => true,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'  => ['Zend_Filter_Empty' => null],
                'default'       => 0,
            ],
            'vacation_time' => [
                'type'          => 'integer',
                'label'         => 'Break Time', // _('Break Time')
                'nullable'      => true,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'  => ['Zend_Filter_Empty' => null],
                'default'       => 0,
            ],
            'sickness_time' => [
                'type'          => 'integer',
                'label'         => 'Sickness Time', // _('Sickness Time')
                'nullable'      => true,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'  => ['Zend_Filter_Empty' => null],
                'default'       => 0,
            ],
            'working_time_correction' => [
                'type'          => 'integer',
                'label'         => 'Working Time Correction', // _('Working Time Correction')
                'nullable'      => true,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'  => ['Zend_Filter_Empty' => null],
                'default'       => 0,
            ],
            'working_time_actual' => [
                'type'          => 'integer',
                'label'         => 'Actual Working Time', // _('Actual Working Time')
                'nullable'      => true,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'  => ['Zend_Filter_Empty' => null],
                'default'       => 0,
            ],
            'working_time_target' => [
                'type'          => 'integer',
                'label'         => 'Target Working Time', // _('Target Working Time')
                'nullable'      => true,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'  => ['Zend_Filter_Empty' => null],
                'default'       => 0,
            ],
            'system_remark' => [
                'label'         => 'System Remark', // _('System Remark')
                'type'          => 'string',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'nullable'      => true,
            ],
            'user_remark' => [
                'label'         => 'Remark', // _('Remark')
                'type'          => 'text',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'nullable'      => true,
            ],
            'is_cleared' => [
                'label'         => 'Is Cleared', // _('Is Cleared')
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0],
                'type'          => 'boolean',
                'default'       => 0,
                'shy'           => true,
                'copyOmit'      => true,
            ],
            'cleared_in' => [
                'label'         => 'Cleared in', // _('Cleared in')
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'shy'           => true,
                'nullable'      => true,
                'copyOmit'      => true,
            ],
        ]
    ];
}