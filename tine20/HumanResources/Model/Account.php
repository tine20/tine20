<?php
/**
 * Tine 2.0

 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Account data
 *
 * @package     HumanResources
 * @subpackage  Model
 */

class HumanResources_Model_Account extends Tinebase_Record_Abstract
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
        'version'           => 5,
        'recordName'        => 'Personal account', // ngettext('Personal account', 'Personal accounts', n)
        'recordsName'       => 'Personal accounts',
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        self::DELEGATED_ACL_FIELD => 'employee_id',
        
        'createModule'      => TRUE,
        
        'titleProperty'     => 'year',
        'appName'           => 'HumanResources',
        'modelName'         => 'Account',
        
        'filterModel' => array(
            'query' => array(
                'label' => 'Quick search',    // _('Quick search')
                'filter' => 'HumanResources_Model_AccountQuicksearchFilter',
                'jsConfig' => array('valueType' => 'string')
            )
        ),
        
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

        'table'             => array(
            'name'    => 'humanresources_account',
            'indexes' => array(
                'employee_id' => array(
                    'columns' => array('employee_id'),
                ),
            ),
        ),
        
        'fields'            => array(
            'employee_id' => array(
                'label' => 'Employee',
                'type'  => 'record',
                'doctrineIgnore'        => true, // already defined as association
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'duplicateCheckGroup' => 'year-employee',
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Employee',
                    'idProperty'  => 'id'
                )
            ),
            'year' => array(
                'label' => 'Year', //_('Year')
                'duplicateCheckGroup' => 'year-employee',
                'group' => 'Account',
                'type'    => 'integer'
            ),
            'description' => array(
                'label' => 'Description', //_('Description')
                'group' => 'Miscellaneous', //_('Miscellaneous')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            // virtual fields
            'contracts' => array(
                'type' => 'virtual',
            ),
            'scheduled_remaining_vacation_days' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'integer'
                )
            ),
            'scheduled_taken_vacation_days' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'integer'
                )
            ),
            'possible_vacation_days' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'integer'
                )
            ),
            'vacation_expiary_date' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'date'
                )
            ),
            'excused_sickness' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'integer'
                )
            ),
            'unexcused_sickness' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'integer'
                )
            ),
            'working_days' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'integer'
                )
            ),
            'working_hours' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'integer'
                )
            ),
            'working_days_real' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'integer'
                )
            ),
            'working_hours_real' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'integer'
                )
            ),
        )
    );

    /**
     * returns period of this account
     * 
     * looks like we have a timezone problem here!
     * this code is just factored out from existing code
     * 
     * @return Tinebase_DateTime[]
     * @throws Exception
     */
    public function getAccountPeriod()
    {
        return [
            'from' => new Tinebase_DateTime($this->year . '-01-01 00:00:00'),
            'until' => new Tinebase_DateTime($this->year . '-12-31 23:59:59'),
        ];
    }
}
