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
        'version'           => 17,
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
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,
        self::DELEGATED_ACL_FIELD => 'division_id',

        'titleProperty'     => 'n_fn',
        'appName'           => 'HumanResources',
        'modelName'         =>  self::MODEL_NAME_PART,

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
                'type'  => 'integer',
                'nullable'     => true,
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE,  'presence' => 'required'),
            ),
            'account_id' => array(
                'label' => 'Account', //_('Account')
                'duplicateCheckGroup' => 'account',
                'type' => 'user',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            'description' => array(
                'label'   => 'Description', // _('Description')
                'type'    => 'text',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'countryname' => array(
                'label'   => 'Country', //_('Country')
                'default' => 'Germany', // _('Germany')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'     => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'locality' => array(
                'label' => 'Locality', //_('Locality')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'postalcode' => array(
                'label' => 'Postalcode', //_('Postalcode')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'region' => array(
                'label' => 'Region', //_('Region')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'street' => array(
                'label' => 'Street', //_('Street')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'street2' => array(
                'label' => 'Street 2', //_('Street 2')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'email' => array(
                'label' => 'E-Mail', //_('E-Mail')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'tel_home' => array(
                'label' => 'Telephone Number', //_('Telephone Number')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'tel_cell' => array(
                'label' => 'Cell Phone Number', //_('Cell Phone Number')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
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
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'bank_account_holder' => array(
                'label' => 'Account Holder', //_('Account Holder')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'bank_account_number' => array(
                'label' => 'Account Number', //_('Account Number')
                'duplicateCheckGroup' => 'bankaccount',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'iban' => array(
                'label' => 'IBAN',
                'duplicateCheckGroup' => 'iban',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'bic' => array(
                'label' => 'BIC',
                'duplicateCheckGroup' => 'bic',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'bank_name' => array(
                'label' => 'Bank Name', //_('Bank Name')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'bank_code_number' => array(
                'label' => 'Code Number', //_('Code Number')
                'duplicateCheckGroup' => 'bankaccount',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'employment_begin' => array(
                'label' => 'Employment begin', //_('Employment begin')
                'type'  => 'date',
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE,  'presence' => 'required'),
                'nullable' => true,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
            ),
            'employment_end' => array(
                'label' => 'Employment end', //_('Employment end')
                'type'  => 'date',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'filterDefinition'  => [
                    'filter'    => Tinebase_Model_Filter_Date::class,
                    'options'   => [
                        Tinebase_Model_Filter_Date::BEFORE_OR_IS_NULL => true,
                        Tinebase_Model_Filter_Date::AFTER_OR_IS_NULL  => true,
                    ]
                ],
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
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
                )
            ),
            'division_id' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'nullable' => true,
                'label' => 'Division', //_('Division')
                'type'  => 'record',
                'config' => array(
                    'appName'     => HumanResources_Config::APP_NAME,
                    'modelName'   => HumanResources_Model_Division::MODEL_NAME_PART,
                    'idProperty'  => 'id'
                )
            ),
            'health_insurance' => array(
                'label' => 'Health Insurance', //_('Health Insurance')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'shy'   => TRUE,
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
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
                self::REQUIRED_GRANTS => [
                    HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
                    HumanResources_Model_DivisionGrants::READ_OWN_DATA,
                ],
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

    public function applyFieldGrants(string $action, Tinebase_Record_Interface $oldRecord = null)
    {
        if (Tinebase_Core::getUser()
                ->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::MANAGE_EMPLOYEE)) {
            return;
        }
        parent::applyFieldGrants($action, $oldRecord);
    }

    public function setAccountGrants(Tinebase_Record_Interface $grants)
    {
        if ($grants->{HumanResources_Model_DivisionGrants::READ_OWN_DATA} &&
                $this->getIdFromProperty('account_id') !== Tinebase_Core::getUser()->getId()) {
            $grants->{HumanResources_Model_DivisionGrants::READ_OWN_DATA} = false;
        }
        parent::setAccountGrants($grants);
    }
}
