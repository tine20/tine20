<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold FreeTime data
 *
 * @package     HumanResources
 * @subpackage  Model
 *
 * @property Tinebase_Record_RecordSet freedays
 */
class HumanResources_Model_FreeTime extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be set in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject;
    
    /**
     * Holds the model configuration
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'version'           => 8,
        'recordName'      => 'Free Time', // ngettext('Free Time', 'Free Times', n)
        'recordsName'     => 'Free Times',
        'hasRelations'    => FALSE,
        'hasCustomFields' => FALSE,
        'hasNotes'        => FALSE,
        'hasTags'         => FALSE,
        'modlogActive'    => TRUE,
        'isDependent'     => TRUE,
        'createModule'    => FALSE,
        'titleProperty'   => 'description',
        'appName'         => 'HumanResources',
        'modelName'       => 'FreeTime',

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'employee_id' => [
                    'targetEntity' => 'HumanResources_Model_Employee',
                    'fieldName' => 'employee_id',
                    'joinColumns' => [[
                        'name' => 'employee_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],
        
        'table'             => array(
            'name'    => 'humanresources_freetime',
            'indexes' => array(
                'employee_id' => array(
                    'columns' => array('employee_id'),
                ),
            )
        ),
        
        'fields'          => array(
            'employee_id'       => array(
                'label'      => 'Employee',    // _('Employee')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'doctrineIgnore'        => true, // already defined as association
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Employee',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'account_id'       => array(
                'label'      => 'Account',    // _('Account')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Account',
                    'idProperty'  => 'id',
                    'isParent'    => FALSE
                )
            ),
            'type'            => array(
                'label' => 'Type', // _('Type')
                'type'  => self::TYPE_RECORD,
                'config' => array(
                    'appName'     => HumanResources_Config::APP_NAME,
                    'modelName'   => HumanResources_Model_FreeTimeType::MODEL_NAME_PART,
                ),
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'description'          => array(
                'label' => 'Description', // _('Description')
                'type'  => 'text',
                'queryFilter' => TRUE,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'status'          => array(
                'label' => 'Status', // _('Status')
                'queryFilter' => TRUE,
                'type'  => 'keyfield',
                'name'  => HumanResources_Config::VACATION_STATUS,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'firstday_date'   => array(
                'label' => 'First Day', // _('First Day')
                'type'  => 'date',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'lastday_date'   => array(
                'label' => 'Last Day', // _('Last Day')
                'type'  => 'date',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'days_count'   => array(
                'label' => 'Days Count', // _('Days Count')
                'type'         => 'integer',
                'nullable'     => true,
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
            ),
            
           'freedays' => array(
               'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
               'label' => 'Free Days', // _('Free Days')
               'type'       => 'records',
               'config'     => array(
                   'appName' => 'HumanResources',
                   'modelName'   => 'FreeDay',
                   'refIdField'  => 'freetime_id',
                   'dependentRecords' => TRUE
               ),
           ),
        )
    );
}