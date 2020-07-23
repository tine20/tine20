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
 * class to hold Invoice data
 *
 * @package     Sales
 * @subpackage  Model
 * 
 * @property $number
 * @property $description
 * @property $address_id
 * @property $fixed_address
 * @property $date
 * @property Tinebase_DateTime $start_date
 * @property $end_date
 * @property $credit_term
 * @property $costcenter_id
 * @property $cleared
 * @property $type
 * @property $is_auto
 * @property $price_net
 * @property $price_tax
 * @property $price_gross
 * @property $sales_tax
 * @property $inventory_change
 * @property $positions
 * @property $contract
 * @property $customer
 * @property $fulltext
 */
class Sales_Model_Invoice extends Tinebase_Record_Abstract
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
        'recordName'        => 'Invoice',
        'recordsName'       => 'Invoices', // ngettext('Invoice', 'Invoices', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,

        'titleProperty'     => 'fulltext', //array('%s - %s', array('number', 'title')),

        'appName'           => 'Sales',
        'modelName'         => 'Invoice',

        'exposeHttpApi'     => true,

        'defaultSortInfo'   => ['field' => 'number', 'direction' => 'DESC'],
        
        'filterModel' => array(
            'contract' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Contract', // _('Contract')
                'options' => array(
                    'controller' => 'Sales_Controller_Contract',
                    'filtergroup' => 'Sales_Model_ContractFilter',
                    'own_filtergroup' => 'Sales_Model_InvoiceFilter',
                    'own_controller' => 'Sales_Controller_Invoice',
                    'related_model' => 'Sales_Model_Contract',
                ),
                'jsConfig' => array('filtertype' => 'sales.invoicecontract')
            ),
            'customer' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Customer', // _('Customer')
                'options' => array(
                    'controller' => 'Sales_Controller_Customer',
                    'filtergroup' => 'Sales_Model_CustomerFilter',
                    'own_filtergroup' => 'Sales_Model_InvoiceFilter',
                    'own_controller' => 'Sales_Controller_Invoice',
                    'related_model' => 'Sales_Model_Customer',
                ),
                'jsConfig' => array('filtertype' => 'sales.invoicecustomer')
            ),
        ),
        
        'fields'            => array(
            'number' => array(
                'label' => 'Invoice Number', //_('Invoice Number')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'queryFilter' => TRUE,
            ),
            'description' => array(
                'label'   => 'Description', // _('Description')
                'type'    => 'fulltext',
                'queryFilter' => TRUE,
            ),
            'address_id'       => array(
                'label'      => 'Address',    // _('Address')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'shy' => TRUE,
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Address',
                    'idProperty'  => 'id',
                )
            ),
            'fixed_address' => array(
                'label'      => 'Address',    // _('Address')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label' => NULL
            ),
            'date' => array(
                'type' => 'date',
                'label'      => 'Date',    // _('Date')
            ),
            'start_date' => array(
                'type' => 'date',
                'label'      => 'Interval Begins',    // _('Interval Begins')
            ),
            'end_date' => array(
                'type' => 'date',
                'label'      => 'Interval Ends',    // _('Interval Ends')
            ),
            'credit_term' => array(
                'title' => 'Credit Term', // _('Credit Term')
                'type'  => 'integer',
                'default' => 10
            ),
            'costcenter_id' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label' => 'Cost Center', //_('Cost Center')
                'type'  => 'record',
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'CostCenter',
                    'idProperty'  => 'id'
                )
            ),
            'cleared' => array(
                'label' => 'Cleared', //_('Cleared')
                'default' => 'TO_CLEAR',
                'type' => 'keyfield',
                'name' => Sales_Config::INVOICE_CLEARED
            ),
            'type' => array(
                'label' => 'Type', //_('Type')
                'default' => 'INVOICE',
                'type' => 'keyfield',
                'name' => Sales_Config::INVOICE_TYPE
            ),
            'is_auto' => array(
                'type' => 'bool',
                'label' => NULL
            ),
            'price_net' => array(
                'label' => 'Price Net', // _('Price Net')
                'type'  => 'money',
                'default' => 0,
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'shy' => TRUE,
            ),
            'price_tax' => array(
                'label' => 'Taxes (VAT)', // _('Taxes (VAT)')
                'type'  => 'money',
                'default' => 0,
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'shy' => TRUE,
            ),
            'price_gross' => array(
                'label' => 'Price Gross', // _('Price Gross')
                'type'  => 'money',
                'default' => 0,
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'shy' => TRUE,
            ),
            'sales_tax' => array(
                'label' => 'Sales Tax', // _('Sales Tax')
                'type'  => 'float',
                'specialType' => 'percent',
                'default' => 16,
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'shy' => TRUE,
            ),
            'inventory_change' => array(
                    'label' => 'Inventory Change', // _('Inventory Change')
                    'type'  => 'money',
                    'default' => 0,
                    'inputFilters' => array('Zend_Filter_Empty' => 0),
                    'shy' => TRUE,
            ),
            'positions' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label'      => 'Positions', // _('Positions')
                'type'       => 'records',
                'config'     => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'InvoicePosition',
                    'refIdField'  => 'invoice_id',
                    'paging'      => array('sort' => 'month', 'dir' => 'ASC'),
                    'dependentRecords' => TRUE
                ),
            ),
            'contract' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Contract',    // _('Contract')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'Contract',
                        'type' => 'CONTRACT'
                    )
                )
            ),
            'customer' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Customer',    // _('Customer')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'Customer',
                        'type' => 'CUSTOMER'
                    )
                )
            ),
            'fulltext' => array(
                'type'   => 'virtual',
                'config' => array(
                    'sortable' => false,
                    'type'   => 'string'
                )
            )
        )
    );

    /**
     * sets the record related properties from user generated input.
     *
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     **/
    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        $this->fulltext = $this->number . ' - ' . $this->description;
    }

    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Sales', 'relatedModel' => 'Contract', 'config' => array(
            array('type' => 'CONTRACT', 'degree' => 'sibling', 'text' => 'Contract', 'max' => '0:0'), // _('Contract')
            ), 'defaultType' => 'CONTRACT'
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Customer', 'config' => array(
            array('type' => 'CUSTOMER', 'degree' => 'sibling', 'text' => 'Customer', 'max' => '0:0'), // _('Customer')
        ), 'defaultType' => 'CUSTOMER'
            ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Invoice', 'config' => array(
            array('type' => 'REVERSAL', 'degree' => 'sibling', 'text' => 'Reversal Invoice', 'max' => '1:1'), // _('Reversal Invoice')
            ), 'defaultType' => 'REVERSAL'
        ),
        array('relatedApp' => 'Timetracker', 'relatedModel' => 'Timeaccount', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'IPNet', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'StoragePath', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'BackupPath', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'CertificateDomain', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'DReg', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'MailAccount', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
    );
}
