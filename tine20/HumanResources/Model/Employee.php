<?php
/**
 * Tine 2.0

 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Employee data
 *
 * @package     HumanResources
 * @subpackage  Model
 */

class HumanResources_Model_Employee extends Tinebase_Record_Abstract
{
    const MODEL_NAME_PART = 'Employee';

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
        'version'           => 16,
        'recordName'        => 'Employee',
        'recordsName'       => 'Employees', // ngettext('Employee', 'Employees', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,
      
        'titleProperty'     => 'n_fn',
        'appName'           => 'HumanResources',
        'modelName'         =>  self::MODEL_NAME_PART,
        
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

        'table'             => array(
            'name'    => 'humanresources_employee',
        ),
        
        'fields'            => array(
            'number' => array(
                'label' => 'Number', //_('Number')
                'duplicateCheckGroup' => 'number',
                'group' => 'employee',
                'type'  => 'integer',
                'nullable'     => true,
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE,  'presence' => 'required'),
            ),
            'account_id' => array(
                'label' => 'Account', //_('Account')
                'duplicateCheckGroup' => 'account',
                'type' => 'user',
                'group' => 'employee',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'dfcom_id' => array(
                'label' => 'Transponder Id', //_('Transponder Id')
                'type'  => 'string',
                'length'      => 255,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'shy' => TRUE,
                'nullable' => true,
            ),
            'description' => array(
                'label'   => 'Description', // _('Description')
                'type'    => 'text',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'countryname' => array(
                'label'   => 'Country', //_('Country')
                'group'   => 'private',
                'default' => 'Germany', // _('Germany')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'     => TRUE
            ),
            'locality' => array(
                'label' => 'Locality', //_('Locality')
                'group' => 'private',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'postalcode' => array(
                'label' => 'Postalcode', //_('Postalcode')
                'group' => 'private',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'region' => array(
                'label' => 'Region', //_('Region')
                'group' => 'private',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'street' => array(
                'label' => 'Street', //_('Street')
                'group' => 'private',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'street2' => array(
                'label' => 'Street 2', //_('Street 2')
                'group' => 'private',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'email' => array(
                'label' => 'E-Mail', //_('E-Mail')
                'group' => 'private',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'tel_home' => array(
                'label' => 'Telephone Number', //_('Telephone Number')
                'group' => 'private',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'tel_cell' => array(
                'label' => 'Cell Phone Number', //_('Cell Phone Number')
                'group' => 'private',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'title' => array(
                'label' => 'Title', //_('Title')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'salutation' => array(
                'label' => 'Salutation', //_('Salutation')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'n_family' => array(
                'label' => 'Last Name', //_('Last Name')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'n_given' => array(
                'label' => 'First Name', //_('First Name')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'n_fn' => array(
                'label'       => 'Employee name', //_('Employee name')
                'queryFilter' => TRUE,
                'shy'         => TRUE,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'bday' => array(
                'label' => 'Birthday', //_('Birthday')
                'type'  => 'datetime',
                'group' => 'private',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'bank_account_holder' => array(
                'label' => 'Account Holder', //_('Account Holder')
                'group' => 'banking',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'bank_account_number' => array(
                'label' => 'Account Number', //_('Account Number')
                'duplicateCheckGroup' => 'bankaccount',
                'group' => 'banking',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'iban' => array(
                'label' => 'IBAN',
                'duplicateCheckGroup' => 'iban',
                'group' => 'banking',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'bic' => array(
                'label' => 'BIC',
                'duplicateCheckGroup' => 'bic',
                'group' => 'banking',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'bank_name' => array(
                'label' => 'Bank Name', //_('Bank Name')
                'group' => 'banking',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'bank_code_number' => array(
                'label' => 'Code Number', //_('Code Number')
                'duplicateCheckGroup' => 'bankaccount',
                'group' => 'banking',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'employment_begin' => array(
                'label' => 'Employment begin', //_('Employment begin')
                'type'  => 'date',
                'group' => 'private',
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE,  'presence' => 'required'),
                'nullable' => true,
            ),
            'employment_end' => array(
                'label' => 'Employment end', //_('Employment end')
                'type'  => 'date',
                'group' => 'private',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'supervisor_id' => array(
                'label' => 'Supervisor', //_('Supervisor')
                'type'  => 'record',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'nullable' => true,
                'config' => array(
                    'appName'       => 'HumanResources',
                    'modelName'     => 'Employee',
                    'idProperty'    => 'id',
                    'titleProperty' => 'n_fn' // TODO add documentation
                )
            ),
            'division_id' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'nullable' => true,
                'label' => 'Division', //_('Division')
                'type'  => 'record',
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Division',
                    'idProperty'  => 'id'
                )
            ),
            'health_insurance' => array(
                'label' => 'Health Insurance', //_('Health Insurance')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE
            ),
            'profession' => array(
                'label' => 'Profession', //_('Profession')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'position' => array(
                'label' => 'Position', //_('Position')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'contracts' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label'      => 'Contracts', // _('Contracts')
                'type'       => 'records',
                'config'     => array(
                    'omitOnSearch' => FALSE,
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Contract',
                    'refIdField'  => 'employee_id',
                    'paging'      => array('sort' => 'start_date', 'dir' => 'ASC'),
                    'dependentRecords' => TRUE,
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

    /**
     * @param Tinebase_DateTime|null $_date
     * @return null|HumanResources_Model_Contract
     */
    public function getValidContract(Tinebase_DateTime $_date = null)
    {
        if (!$this->contracts instanceof Tinebase_Record_RecordSet) {
            return null;
        }
        if (null === $_date) {
            $_date = Tinebase_DateTime::now();
        }
        $_date = $_date->getClone()->setTimezone(Tinebase_Core::getInstanceTimeZone());

        /** @var HumanResources_Model_Contract $contract */
        foreach ($this->contracts as $contract) {
            if ($contract->isValidAt($_date)) {
                return $contract;
            }
        }

        return null;
    }
}
