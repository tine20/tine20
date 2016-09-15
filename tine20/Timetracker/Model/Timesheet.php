<?php
/**
 * class to hold Timesheet data
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold Timesheet data
 * 
 * @package     Timetracker
 */
class Timetracker_Model_Timesheet extends Tinebase_Record_Abstract implements Sales_Model_Billable_Interface
{
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
    protected static $_modelConfiguration = array(
        'recordName'        => 'Timesheet',
        'recordsName'       => 'Timesheets', // ngettext('Timesheet', 'Timesheets', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,

        'titleProperty'     => 'title',
        'appName'           => 'Timetracker',
        'modelName'         => 'Timesheet',
        'multipleEdit'      => TRUE,
        'splitButton'       => TRUE,

        'defaultFilter' => 'start_date',

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
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'config'                => array(
                    'appName'               => 'Timetracker',
                    'modelName'             => 'Timeaccount',
                    'idProperty'            => 'id'
                ),
                // TODO ?????
                //'default'               => array('account_grants' => array('bookOwnGrant' => true)),
                'filterDefinition'      => array(
                    'filter'                => 'Tinebase_Model_Filter_ForeignId',
                    'options'               => array(
                        'filtergroup'           => 'Timetracker_Model_TimeaccountFilter',
                        'controller'            => 'Timetracker_Controller_Timeaccount',
                        'useTimesheetAcl'       => TRUE,
                        'showClosed'            => TRUE,
                        'appName'               => 'Timetracker',
                        'modelName'             => 'Timeaccount',
                    ),
                    'jsConfig'              => array('filtertype' => 'timetracker.timeaccount')
                ),
            ),
            'is_billable'           => array(
                'label'                 => NULL,
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
                'type'                  => 'boolean',
                'default'               => 1,
                'shy'                   => TRUE
            ),
            'is_billable_combined'  => array(
                'label'                 => 'Billable', // _('Billable')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type'                  => 'boolean',
                'filterDefinition'      => array(
                    'filter'                => 'Tinebase_Model_Filter_Bool',
                    'title'                 => 'Billable', // _('Billable')
                    'options'               => array(
                        'leftOperand'           => '(timetracker_timesheet.is_billable*timetracker_timeaccount.is_billable)',
                        'requiredCols'          => array('is_billable_combined')
                    ),
                ),
            ),
            'billed_in'             => array(
                'label'                 => 'Cleared in', // _('Cleared in')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'copyOmit'              => TRUE,
                'shy'                   => TRUE,
                'copyOmit'              => true,
            ),
            'invoice_id'            => array(
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'                 => 'Invoice', // _('Invoice')
                'type'                  => 'record',
                'inputFilters'          => array('Zend_Filter_Empty' => NULL),
                'config'                => array(
                    'appName'               => 'Sales',
                    'modelName'             => 'Invoice',
                    'idProperty'            => 'id'
                ),
                'copyOmit'              => true,
            ),
            'is_cleared'            => array(
                'label'                 => NULL,
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'                  => 'boolean',
                'default'               => 0,
                'copyOmit'              => TRUE,
                'shy'                   => TRUE,
                'copyOmit'              => true,
            ),
            'is_cleared_combined'   => array(
                'label'                 => 'Cleared', // _('Cleared')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type'                  => 'boolean',
                'filterDefinition'      => array(
                    'filter'                => 'Tinebase_Model_Filter_Bool',
                    'options'               => array(
                        'leftOperand'           => "( (CASE WHEN timetracker_timesheet.is_cleared = '1' THEN 1 ELSE 0 END) | (CASE WHEN timetracker_timeaccount.status = 'billed' THEN 1 ELSE 0 END) )",
                        'requiredCols'          => array('is_cleared_combined'),
                    ),
                ),
            ),
            'start_date'            => array(
                'label'                 => 'Date', // _('Date')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'type'                  => 'date',
                'default'               => 'today',
                // strip time information from datetime string
                'inputFilters'          => array('Zend_Filter_PregReplace' => array('/(\d{4}-\d{2}-\d{2}).*/', '$1'))
            ),
            'start_time'            => array(
                'label'                 => 'Start time', // _('Start time')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'inputFilters'          => array('Zend_Filter_Empty' => NULL),
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
                'type'                  => 'text',
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'queryFilter'           => TRUE
            ),
            // TODO ?????
            /*
            'timeaccount_closed' => array(
                'label' => 'Time Account closed', // _('Time Account closed')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type' => 'boolean',

            ),*/
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
