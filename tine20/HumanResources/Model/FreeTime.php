<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
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
    public const MODEL_NAME_PART = 'FreeTime';

    public const TABLE_NAME = 'humanresources_freetime';
    public const FLD_TYPE_STATUS = 'type_status';
    public const FLD_PROCESS_STATUS = 'process_status';

    /**
     * Holds the model configuration
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'version'         => 10,
        'recordName'      => 'Free Time', // ngettext('Free Time', 'Free Times', n)
        'recordsName'     => 'Free Times', // gettext('GENDER_Free Time')
        'hasRelations'    => FALSE,
        'hasCustomFields' => FALSE,
        'hasNotes'        => FALSE,
        'hasTags'         => FALSE,
        'hasAttachments'  => TRUE,
        'modlogActive'    => TRUE,
        'isDependent'     => TRUE,
        'createModule'    => TRUE,
        'titleProperty'   => 'description',
        'appName'         => 'HumanResources',
        'modelName'       => 'FreeTime',
        self::DELEGATED_ACL_FIELD => 'employee_id',
        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'employee_id' => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'division_id' => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES => [
                                Tinebase_Record_Expander::PROPERTY_CLASS_ACCOUNT_GRANTS => [],
                            ]
                        ]
                    ]
                ]
            ]
        ],
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
                'type' => [
                    'targetEntity' => HumanResources_Model_FreeTimeType::class,
                    'fieldName' => 'type',
                    'joinColumns' => [[
                        'name' => 'type',
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        'table'             => array(
            'name'    => self::TABLE_NAME,
            'indexes' => array(
                'employee_id' => array(
                    'columns' => array('employee_id'),
                ),
                'type' => array(
                    'columns' => array('type'),
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
                'label' => 'Absence reason', // _('Absence reason')
                'type'  => self::TYPE_RECORD,
                'config' => array(
                    'appName'     => HumanResources_Config::APP_NAME,
                    'modelName'   => HumanResources_Model_FreeTimeType::MODEL_NAME_PART,
                ),
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
            ),
            'description'          => array(
                'label' => 'Description', // _('Description')
                'type'  => 'text',
                'queryFilter' => TRUE,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            self::FLD_TYPE_STATUS          => array(
                'label' => 'Status', // _('Status')
                'type'  => 'keyfield',
                'name'  => HumanResources_Config::FREE_TIME_TYPE_STATUS,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                self::DEFAULT_VAL => null,
            ),
            self::FLD_PROCESS_STATUS          => array(
                'label' => 'Status', // _('Status')
                'type'  => 'keyfield',
                'name'  => HumanResources_Config::FREE_TIME_PROCESS_STATUS,
                'validators' => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
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
                   'dependentRecords' => TRUE,
                   self::IGNORE_ACL => true,
               ),
           ),
        )
    );

    /**
     * holds the configuration object (must be set in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject;
}
