<?php
/**
 * Tine 2.0

 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Customer data
 *
 * @package     Sales
 * @subpackage  Model
 *
 * @property String name_shorthand    
 */

class Sales_Model_Customer extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'Customer';

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
        'recordName'        => 'Customer', // gettext('GENDER_Customer')
        'recordsName'       => 'Customers', // ngettext('Customer', 'Customers', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,

        'exposeHttpApi'     => true,
        
        'titleProperty'     => 'name',
        'appName'           => 'Sales',
        'modelName'         => self::MODEL_NAME_PART,
        
        'fieldGroups'       => array(
            'core'       => 'Core Data',     // _('Core Data')
            'accounting' => 'Accounting',    // _('Accounting')
            'misc'       => 'Miscellaneous',    // _('Miscellaneous')
        ),

        'defaultSortInfo'   => ['field' => 'number', 'direction' => 'DESC'],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'delivery'      => [],
                'billing'       => [],
                'postal'        => [],
                'cpextern_id'   => [],
                'cpintern_id'   => [],
            ],
        ],

        'fields'            => array(
            'number' => array(
                'label'       => 'Customer Number', //_('Customer Number')
                'group'       => 'core',
                // TODO number can't be part of query filter because it is an integer
                // for mysql it is ok, but for pgsql we need a typecast...
                //'queryFilter' => TRUE,
                'type'        => self::TYPE_BIGINT,
                self::UNSIGNED => true,
//                self::DEFAULT_VAL => 0, // -> no default to autopick number
            ),
            'name' => array(
                'label'       => 'Name', // _('Name')
                'type'        => 'text',
                'duplicateCheckGroup' => 'name',
                'group'       => 'core',
                'queryFilter' => TRUE,
            ),
            'name_shorthand' => array(
                'label'       => 'Name shorthand', // _('Name shorthand')
                'type'        => 'text',
                'duplicateCheckGroup' => 'name',
                'group'       => 'accounting',
                'queryFilter' => TRUE,
                self::NULLABLE => true,
            ),
            'url' => array(
                'label'       => 'Web', // _('Web')
                'type'        => 'text',
                'group'       => 'misc',
                'shy'         => TRUE,
                self::NULLABLE => true,
            ),
            'description' => array(
                'label'       => 'Description', // _('Description')
                'group'       => 'core',
                'type'        => 'fulltext',
                'queryFilter' => TRUE,
                'shy'         => TRUE,
                self::NULLABLE => true,
            ),
            'cpextern_id'       => array(
                'label'   => 'Contact Person (external)',    // _('Contact Person (external)')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'    => 'record',
                'group'   => 'core',
                'config'  => array(
                    'appName'     => 'Addressbook',
                    'modelName'   => 'Contact',
                    'idProperty'  => 'id',
                ),
                'recursiveResolving' => true,
                self::NULLABLE => true,
            ),
            'cpintern_id'    => array(
                'label'      => 'Contact Person (internal)',    // _('Contact Person (internal)')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'record',
                'group'      => 'core',
                'config' => array(
                    'appName'     => 'Addressbook',
                    'modelName'   => 'Contact',
                    'idProperty'  => 'id',
                ),
                'recursiveResolving' => true,
                self::NULLABLE => true,
            ),
            'vatid' => array (
                'label'   => 'VAT ID', // _('VAT ID')
                'type'    => 'text',
                'group'   => 'accounting',
                'shy'     => TRUE,
                self::NULLABLE => true,
            ),
            'credit_term' => array (
                'label'   => 'Credit Term (days)', // _('Credit Term (days)')
                'type'    => 'integer',
                'group'   => 'accounting',
                self::UNSIGNED => true,
                'shy'     => TRUE,
                self::NULLABLE => true,
            ),
            'currency' => array (
                'label'   => 'Currency', // _('Currency')
                'type'    => self::TYPE_STRING,
                'group'   => 'accounting',
                self::NULLABLE => true,
                self::LENGTH => 4,
            ),
            'currency_trans_rate' => array (
                'label'   => 'Currency Translation Rate', // _('Currency Translation Rate')
                'type'    => 'float',
                'group'   => 'accounting',
                'default' => 1,
                'shy'     => TRUE,
                self::NULLABLE => true,
                self::UNSIGNED => true, // TODO FIXME doesnt work?!
            ),
            'iban' => array (
                'label'   => 'IBAN',
                'group'   => 'accounting',
                'shy'     => TRUE,
                self::NULLABLE => true,
            ),
            'bic' => array (
                'label'   => 'BIC',
                'group'   => 'accounting',
                'shy'     => TRUE,
                self::NULLABLE => true,
            ),
            'discount' => array (
                'label'   => 'Discount (%)', // _('Discount (%)')
                'type'    => 'float',
                'group'   => 'accounting',
                self::NULLABLE => true,
                self::UNSIGNED => true, // TODO FIXME doesnt work?!
            ),
            'delivery' => array (
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label'      => 'Delivery Addresses', // _('Delivery Addresses')
                'type'       => 'records',
                'config'     => array(
                    'appName'          => 'Sales',
                    'modelName'        => 'Address',
                    'refIdField'       => 'customer_id',
                    'addFilters'       => array(array('field' => 'type', 'operator' => 'equals', 'value' => 'delivery')),
                    'paging'           => array('sort' => 'locality', 'dir' => 'ASC'),
                    'dependentRecords' => TRUE,
                    self::FORCE_VALUES      => [
                        'type'                  => 'delivery',
                    ],
                ),
            ),
            'billing' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label'      => 'Billing Addresses', // _('Billing Addresses')
                'type'       => 'records',
                'config'     => array(
                    // we need the billing address on search in the contract-customer combo to automatically set the first billing address
                    'omitOnSearch'     => FALSE,
                    'appName'          => 'Sales',
                    'modelName'        => 'Address',
                    'refIdField'       => 'customer_id',
                    'addFilters'       => array(array('field' => 'type', 'operator' => 'equals', 'value' => 'billing')),
                    'paging'           => array('sort' => 'locality', 'dir' => 'ASC'),
                    'dependentRecords' => TRUE,
                    self::FORCE_VALUES      => [
                        'type'                  => 'billing',
                    ],
                ),
            ),
            'postal' => [
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL],
                self::TYPE              => self::TYPE_RECORD,
                self::DOCTRINE_IGNORE   => true,
                self::CONFIG            => [
                    self::APP_NAME          => Sales_Config::APP_NAME,
                    self::MODEL_NAME        => Sales_Model_Address::MODEL_NAME_PART,
                    self::ADD_FILTERS       =>[['field' => 'type', 'operator' => 'equals', 'value' => 'postal']],
                    self::REF_ID_FIELD      => 'customer_id',
                    self::DEPENDENT_RECORDS => true,
                    self::FORCE_VALUES      => [
                        'type'                  => 'postal',
                    ],
                ]
            ],
            'fulltext' => array(
                'type'   => 'virtual',
                'config' => array(
                    'sortable' => false,
                    'type'   => 'string'
                )
            ),
        )
    );
    
    /**
     * sets the record related properties from user generated input.
     *
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     *
     * @todo remove custom fields handling (use Tinebase_Record_RecordSet for them)
     */
    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        $this->fulltext = $this->number . ' - ' . $this->name;
    }
    
    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact', 'config' => array(
            array('type' => 'CUSTOMER', 'degree' => 'sibling', 'text' => 'Customer', 'max' => '0:0'), // _('Customer')
        ), 'defaultType' => 'CUSTOMER'
        )
    );
}
