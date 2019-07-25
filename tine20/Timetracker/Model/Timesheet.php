<?php
/**
 * class to hold Timesheet data
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold Timesheet data
 * 
 * @package     Timetracker
 *
 * @property    Tinebase_DateTime   start_date
 * @property    string              start_time
 * @property    integer             duration
 * @property    string              timeaccount_id
 */
class Timetracker_Model_Timesheet extends Tinebase_Record_Abstract implements Sales_Model_Billable_Interface
{
    const TYPE_WORKINGTIME = 'AZ';
    const TYPE_PROJECTTIME = 'PZ';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'version'           => 8,
        'recordName'        => 'Timesheet',
        'recordsName'       => 'Timesheets', // ngettext('Timesheet', 'Timesheets', n)
        'hasRelations'      => true,
        'hasCustomFields'   => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'modlogActive'      => true,
        'hasAttachments'    => true,
        'createModule'      => true,
        'containerProperty' => null,
        'copyNoAppendTitle' => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,

        'titleProperty'     => 'description',
        'appName'           => 'Timetracker',
        'modelName'         => 'Timesheet',

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'timeaccount_id' => [
                    'targetEntity' => 'Timetracker_Model_Timeaccount',
                    'fieldName' => 'timeaccount_id',
                    'joinColumns' => [[
                        'name' => 'timeaccount_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ]
            ],
        ],

        'table'             => array(
            'name'    => 'timetracker_timesheet',
            'indexes' => array(
                'start_date' => array(
                    'columns' => array('start_date')
                ),
                'timeaccount_id' => array(
                    'columns' => array('timeaccount_id'),
                ),
                'description' => array(
                    'columns' => array('description'),
                    'flags' => array('fulltext')
                ),
            ),
        ),

        // frontend
        'multipleEdit'      => true,
        'splitButton'       => true,
        'defaultFilter'     => 'start_date',

        'fields'            => array(
            'account_id'            => array(
                'label'                 => 'Account', //_('Account')
                'duplicateCheckGroup'   => 'account',
                'type'                  => 'user',
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                // TODO add filter: 'inputFilters'          => array('Zend_Filter_Empty' => CURRENT USER ACCOUNT ID),
            ),
            'timeaccount_id'        => array(
                'label'                 => 'Time Account (Number - Title)', //_('Time Account (Number - Title)')
                'type'                  => 'record',
                'doctrineIgnore'        => true, // already defined as association
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'config'                => array(
                    'appName'               => 'Timetracker',
                    'modelName'             => 'Timeaccount',
                    'idProperty'            => 'id',
                    'doNotCheckModuleRight'      => true
                ),
                // TODO ?????
                //'default'               => array('account_grants' => array('bookOwnGrant' => true)),
                'filterDefinition'      => array(
                    'filter'                => 'Tinebase_Model_Filter_ForeignId',
                    'options'               => array(
                        'filtergroup'           => 'Timetracker_Model_TimeaccountFilter',
                        'controller'            => 'Timetracker_Controller_Timeaccount',
                        'useTimesheetAcl'       => true,
                        'showClosed'            => true,
                        'appName'               => 'Timetracker',
                        'modelName'             => 'Timeaccount',
                    ),
                    'jsConfig'              => array('filtertype' => 'timetracker.timeaccount')
                ),
            ),
            'is_billable'           => array(
                'label'                 => 'Billable', // _('Billable')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
                'type'                  => 'boolean',
                'default'               => 1,
                'shy'                   => true,
            ),
            // ts + ta fields combined
            'is_billable_combined'  => array(
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type'                  => 'virtual',
                'config'                => [
                    'label'                 => 'Billable (combined)', // _('Billable (combined)')
                    'type'                  => 'boolean',
                ],
                'filterDefinition'      => [
                    'filter'                => 'Tinebase_Model_Filter_Bool',
                    'title'                 => 'Billable', // _('Billable')
                    'options'               => array(
                        'leftOperand'           => '(timetracker_timesheet.is_billable*timetracker_timeaccount.is_billable)',
                        'requiredCols'          => array('is_billable_combined')
                    ),
                ],
            ),
            'billed_in'             => array(
                'label'                 => 'Cleared in', // _('Cleared in')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'shy'                   => true,
                'nullable'              => true,
                'copyOmit'              => true,
            ),
            'invoice_id'            => array(
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'                 => 'Invoice', // _('Invoice')
                'type'                  => 'record',
                'nullable'              => true,
                'inputFilters'          => array('Zend_Filter_Empty' => null),
                'config'                => array(
                    'appName'               => 'Sales',
                    'modelName'             => 'Invoice',
                    'idProperty'            => 'id',
                    // TODO we should replace this with a generic approach to fetch configured models of an app
                    // -> APP_Frontend_Json::$_configuredModels should be moved from json to app controller
                    'feature'               => 'invoicesModule', // Sales_Config::FEATURE_INVOICES_MODULE
                ),
                'copyOmit'              => true,
            ),
            'is_cleared'            => array(
                'label'                 => 'Cleared', // _('Cleared')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'                  => 'boolean',
                'default'               => 0,
                'shy'                   => true,
                'copyOmit'              => true,
            ),
            // ts + ta fields combined
            'is_cleared_combined'   => array(
                'type'                  => 'virtual',
                'config'                => [
                    'label'                 => 'Cleared (combined)', // _('Cleared (combined)')
                    'type'                  => 'boolean',
                ], 
                'filterDefinition'      => array(
                    'filter'                => 'Tinebase_Model_Filter_Bool',
                    'options'               => array(
                        'leftOperand'           => "( (CASE WHEN timetracker_timesheet.is_cleared = '1' THEN 1 ELSE 0 END) | (CASE WHEN timetracker_timeaccount.status = 'billed' THEN 1 ELSE 0 END) )",
                        'requiredCols'          => array('is_cleared_combined'),
                    ),
                ),
            ),
            // TODO combine those three fields like this?
            // TODO create individual fields in MC and Doctrine Mapper? how to handle filter/validators/labels/...?
//            'start'            => array(
//                'label'                 => 'Date', // _('Date')
//                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
//                'type'                  => 'datetime_separated',
//                // strip time information from datetime string
//                'inputFilters'          => array('Zend_Filter_PregReplace' => array('/(\d{4}-\d{2}-\d{2}).*/', '$1'))
//            ),
            'start_date'            => array(
                'label'                 => 'Date', // _('Date')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                //'type'                  => 'date',
                'type'                  => 'datetime_separated_date',
                // strip time information from datetime string
                'inputFilters'          => array('Zend_Filter_PregReplace' => array('/(\d{4}-\d{2}-\d{2}).*/', '$1'))
            ),
            'start_time'            => array(
                'label'                 => 'Start time', // _('Start time')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'inputFilters'          => array('Zend_Filter_Empty' => null),
                'type'                  => 'time',
                // 'type'                  => 'datetime_separated_time',
                'nullable'              => true,
                'shy'                   => true
            ),
            // TODO make this work
            // TODO set user / default tz for existing/new records?
//            'start_tz'            => array(
//                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
//                'inputFilters'          => array('Zend_Filter_Empty' => null),
//                'type'                  => 'datetime_separated_tz',
//                'shy'                   => true,
//                'nullable'              => true,
//            ),
            'end_time'            => array(
                'label'                 => 'End time', // _('End time')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'inputFilters'          => array('Zend_Filter_Empty' => NULL),
                'nullable'              => true,
                'type'                  => 'time',
                'shy'                   => TRUE
            ),
            'duration'              => array(
                'label'                 => 'Duration', // _('Duration')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'type'                  => 'integer',
                'specialType'           => 'minutes',
                'default'               => '30'
            ),
            'description'           => array(
                'label'                 => 'Description', // _('Description')
                'type'                  => 'fulltext',
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'queryFilter'           => true
            ),
            'need_for_clarification'    => array(
                'label'                 => 'Need for Clarification', // _('Need for Clarification')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'                  => 'boolean',
                'default'               => 0
            ),
            'accounting_time_factor'    => array(
                'label'                 => 'Accounting time factor', // _('Accounting time factor')
                'inputFilters' => array('Zend_Filter_Empty' => 1),
                'type'                  => 'float',
                'default'               => 1
            ),
            'accounting_time'  => array(
                'label'                 => 'Accounting time', // _('Accounting time')
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'type'                  => 'integer',
                'specialType'           => 'minutes',
                'default'               => '30'
            ),
            'workingtime_is_cleared'    => array(
                'label'                 => 'Workingtime is cleared', // _('Workingtime is cleared')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'                  => 'boolean',
                'default'               => 0
            ),
            'workingtime_cleared_in'             => array(
                'label'                 => 'Workingtime cleared in', // _('Workingtime cleared in')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'shy'                   => true,
                'nullable'              => true,
                'copyOmit'              => true,
            ),
        )
    );
    
    /**
     * returns the interval of this billable
     *
     * @return array
     */
    public function getInterval()
    {
        $startDate = clone new Tinebase_DateTime($this->start_date);
        $startDate->setTimezone(Tinebase_Core::getUserTimezone());
        $startDate->setDate($startDate->format('Y'), $startDate->format('n'), 1);
        $startDate->setTime(0,0,0);
        
        $endDate = clone $startDate;
        $endDate->addMonth(1)->subSecond(1);
        
        return array($startDate, $endDate);
    }
    
    /**
     * returns the quantity of this billable
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->duration / 60;
    }
    
    /**
     * returns the unit of this billable
     *
     * @return string
     */
    public function getUnit()
    {
        return 'hour'; // _('hour')
    }
}
