<?php
/**
 * Tine 2.0

 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Employee data
 *
 * @package     HumanResources
 * @subpackage  Model
 */

class HumanResources_Model_Employee extends Tinebase_Record_Abstract
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
        'recordName'        => 'Employee', // _('Employee')
        'recordsName'       => 'Employees', // _('Employees')
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        
        'createModule'      => TRUE,
        'containerProperty' => NULL,
      
        'titleProperty'     => 'n_fn',
        'appName'           => 'HumanResources',
        'modelName'         => 'Employee',
        
        'fieldGroups'       => array(
            'banking' => 'Banking Information',    // _('Banking Information')
            'private' => 'Private Information',    // _('Private Information')
        ),
        'fieldGroupRights'  => array(
            'private' => array(
                // TODO: handle see right
                'see'  => HumanResources_Acl_Rights::EDIT_PRIVATE,
                'edit' => HumanResources_Acl_Rights::EDIT_PRIVATE,
            )
        ),

        'filterModel' => array(
            'is_employed' => array(
                'label' => 'Is employed',    // _('Is employed')
                'filter' => 'HumanResources_Model_EmployeeEmployedFilter',
                'jsConfig' => array('valueType' => 'bool')
            )
        ),
        
        'fields'            => array(
            'number' => array(
                'label' => 'Number', //_('Number')
                'duplicateCheckGroup' => 'number',
                'group' => 'employee',
                'queryFilter' => TRUE,
            ),
            'account_id' => array(
                'label' => 'Account', //_('Account')
                'duplicateCheckGroup' => 'account',
                'type' => 'user',
                'group' => 'employee'
            ),
            'description' => array(
                'label'   => 'Description', // _('Description')
                'type'    => 'text'
            ),
            'countryname' => array(
                'label'   => 'Country', //_('Country')
                'group'   => 'private',
                'default' => 'Germany', // _('Germany')
                'shy'     => TRUE
            ),
            'locality' => array(
                'label' => 'Locality', //_('Locality')
                'group' => 'private',
                'shy'   => TRUE
            ),
            'postalcode' => array(
                'label' => 'Postalcode', //_('Postalcode')
                'group' => 'private',
                'shy'   => TRUE
            ),
            'region' => array(
                'label' => 'Region', //_('Region')
                'group' => 'private',
                'shy'   => TRUE
            ),
            'street' => array(
                'label' => 'Street', //_('Street')
                'group' => 'private',
                'shy'   => TRUE
            ),
            'street2' => array(
                'label' => 'Street 2', //_('Street 2')
                'group' => 'private',
                'shy'   => TRUE
            ),
            'email' => array(
                'label' => 'E-Mail', //_('E-Mail')
                'group' => 'private',
                'shy'   => TRUE
            ),
            'tel_home' => array(
                'label' => 'Telephone Number', //_('Telephone Number')
                'group' => 'private',
                'shy'   => TRUE
            ),
            'tel_cell' => array(
                'label' => 'Cell Phone Number', //_('Cell Phone Number')
                'group' => 'private',
                'shy'   => TRUE
            ),
            'title' => array(
                'label' => 'Title', //_('Title')
            ),
            'salutation' => array(
                'label' => 'Salutation', //_('Salutation')
            ),
            'n_family' => array(
                'label' => 'Last Name', //_('Last Name')
            ),
            'n_given' => array(
                'label' => 'First Name', //_('First Name')
            ),
            'n_fn' => array(
                'label'       => 'Employee name', //_('Employee name')
                'queryFilter' => TRUE,
                'shy'         => TRUE
            ),
            'bday' => array(
                'label' => 'Birthday', //_('Birthday')
                'type'  => 'date',
                'group' => 'private',
            ),
            'bank_account_holder' => array(
                'label' => 'Account Holder', //_('Account Holder')
                'group' => 'banking',
                'shy'   => TRUE
            ),
            'bank_account_number' => array(
                'label' => 'Account Number', //_('Account Number')
                'duplicateCheckGroup' => 'bankaccount',
                'group' => 'banking',
                'shy'   => TRUE
            ),
            'bank_name' => array(
                'label' => 'Bank Name', //_('Bank Name')
                'group' => 'banking',
                'shy'   => TRUE
            ),
            'bank_code_number' => array(
                'label' => 'Code Number', //_('Code Number')
                'duplicateCheckGroup' => 'bankaccount',
                'group' => 'banking',
                'shy'   => TRUE
            ),
            'employment_begin' => array(
                'label' => 'Employment begin', //_('Employment begin')
                'type'  => 'date',
                'group' => 'private',
            ),
            'employment_end' => array(
                'label' => 'Employment end', //_('Employment end')
                'type'  => 'date',
                'group' => 'private',
            ),
            'supervisor_id' => array(
                'label' => 'Supervisor', //_('Supervisor')
                'type'  => 'record',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Employee',
                    'idProperty'  => 'id'
                )
            ),
            'division_id' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label' => 'Division', //_('Division')
                'type'  => 'record',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Division',
                    'idProperty'  => 'id'
                )
            ),
            'health_insurance' => array(
                'label' => 'Health Insurance', //_('Health Insurance')
                'shy'   => TRUE
            ),
            'profession' => array(
                'label' => 'Profession', //_('Profession')
            ),
            'contracts' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label'      => 'Contracts', // _('Contracts')
                'type'       => 'records',
                'config'     => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Contract',
                    'refIdField'  => 'employee_id',
                    'paging'      => array('sort' => 'start_date', 'dir' => 'ASC'),
                    'dependentRecords' => TRUE
                ),
                'group' => 'private',
            ),
            'costcenters' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label'      => 'Cost Centers', // _('Cost Centers')
                'type'       => 'records',
                'config'     => array(
                    'appName' => 'HumanResources',
                    'modelName'   => 'CostCenter',
                    'refIdField'  => 'employee_id',
                    'paging'        => array('sort' => 'start_date', 'dir' => 'ASC'),
                    'dependentRecords' => TRUE
                ),
            ),
            'vacation' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label'      => 'Vacation', // _('Vacation')
                'type'       => 'records',
                'config'     => array(
                    'appName' => 'HumanResources',
                    'modelName'   => 'FreeTime',
                    'refIdField'  => 'employee_id',
                    'addFilters' => array(array('field' => 'type', 'operator' => 'equals', 'value' => 'vacation')),
                    'paging'        => array('sort' => 'firstday_date', 'dir' => 'ASC'),
                    'dependentRecords' => TRUE
                ),
            ),
            'sickness' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label'      => 'Sickness', // _('Sickness')
                'type'       => 'records',
                'config'     => array(
                    'appName' => 'HumanResources',
                    'modelName'   => 'FreeTime',
                    'refIdField'  => 'employee_id',
                    'addFilters' => array(array('field' => 'type', 'operator' => 'equals', 'value' => 'sickness')),
                    'paging'        => array('sort' => 'firstday_date', 'dir' => 'ASC'),
                    'dependentRecords' => TRUE
                ),
            )
        )
    );
}
