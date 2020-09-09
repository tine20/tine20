<?php
/**
 * Tine 2.0

 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Purchase Invoice data
 *
 * @package     Sales
 * @subpackage  Model
 */

class Sales_Model_PurchaseInvoice extends Tinebase_Record_Abstract
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
        'recordName'        => 'Purchase Invoice',
        'recordsName'       => 'Purchase Invoices', // ngettext('Purchase Invoice', 'Purchase Invoices', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,
        'titleProperty'     => 'description',
        'appName'           => 'Sales',
        'modelName'         => 'PurchaseInvoice',

        'exposeHttpApi'     => true,

        'defaultSortInfo'   => ['field' => 'number', 'direction' => 'DESC'],
        
        'filterModel' => array(
            'supplier' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Supplier', // _('Supplier')
                'options' => array(
                    'controller'      => 'Sales_Controller_Supplier',
                    'filtergroup'     => 'Sales_Model_SupplierFilter',
                    'own_filtergroup' => 'Sales_Model_PurchaseInvoiceFilter',
                    'own_controller'  => 'Sales_Controller_PurchaseInvoice',
                    'related_model'   => 'Sales_Model_Supplier',
                ),
                'jsConfig' => array('filtertype' => 'sales.supplier')
            ),
            'costcenter' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Cost Center', // _('Cost Center')
                'options' => array(
                    'controller'      => 'Sales_Controller_CostCenter',
                    'filtergroup'     => 'Sales_Model_CostCenterFilter',
                    'own_filtergroup' => 'Sales_Model_PurchaseInvoiceFilter',
                    'own_controller'  => 'Sales_Controller_PurchaseInvoice',
                    'related_model'   => 'Sales_Model_CostCenter',
                ),
                'jsConfig' => array('filtertype' => 'sales.contractcostcenter')
            ),
            'approver' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Approver', // _('Approver')
                'options' => array(
                    'controller'      => 'Addressbook_Controller_Contact',
                    'filtergroup'     => 'Addressbook_Model_ContactFilter',
                    'own_filtergroup' => 'Sales_Model_PurchaseInvoiceFilter',
                    'own_controller'  => 'Sales_Controller_PurchaseInvoice',
                    'related_model'   => 'Addressbook_Model_Contact',
                ),
                'jsConfig' => array('filtertype' => 'sales.purchaseinvoice_approver')
            )
        ),
        
        'fields'            => array(
            'number' => array(
                'label' => 'Invoice Number',    // _('Invoice Number')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'queryFilter' => TRUE,
            ),
            'description' => array(
                'label'   => 'Description',     // _('Description')
                'queryFilter' => TRUE,
                'type' => 'fulltext'
            ),
            'date' => array(
                'type'  => 'date',
                'label' => 'Date of invoice',   // _('Date of invoice')
            ),
            'due_in' => array(
                'title' => 'Due in',            // _('Due in')
                'type'  => 'integer',
                'label' => 'Due in',            // _('Due in')
                'default' => 10,
                'shy' => TRUE,
            ),
            'due_at' => array(
                    'type'  => 'date',
                    'label' => 'Due at',            // _('Due at')
            ),
            'payed_at' => array(
                'type'  => 'date',
                'label' => 'Payed at',          // _('Payed at')
            ),
            'payment_method' => array(
                'label'   => 'Payment Method', //_('Payment Method')
                'default' => 'BANK TRANSFER',
                'type'    => 'keyfield',
                'name'    => Sales_Config::PAYMENT_METHODS,
                'shy'     => TRUE,
            ),
            'discount' => array(
                'label'   => 'Discount (%)', // _('Discount (%)')
                'type'    => 'float',
                'specialType' => 'percent',
                'default' => 0,
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'shy'     => TRUE,
            ),
            'discount_until' => array(
                'type'  => 'date',
                'label' => 'Discount until',    // _('Discount until')
                'shy' => TRUE,
            ),
            'price_net' => array(
                'label' => 'Price Net', // _('Price Net')
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
            'price_gross2' => array(
                'label' => 'Additional Price Gross', // _('Additional Price Gross')
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
            'sales_tax' => array(
                'label' => 'Sales Tax', // _('Sales Tax')
                'type'  => 'float',
                'specialType' => 'percent',
                'default' => 16,
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'shy' => TRUE,
            ),
            'price_total' => array(
                'label' => 'Total Price', // _('Total Price')
                'type'  => 'money',
                'default' => 0,
                'inputFilters' => array('Zend_Filter_Empty' => 0),
            ),
            'costcenter' => array(
                'type'   => 'virtual',
                'config' => array(
                    'type'   => 'relation',
                    'label'  => 'Cost Center',    // _('Cost Center')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'CostCenter',
                        'type'      => 'COST_CENTER'
                    ),
                    'shy' => TRUE,
                ),
            ),
            'approver' => array(
                'type'   => 'virtual',
                'config' => array(
                    'type'   => 'relation',
                    'label'  => 'Approver',    // _('Approver')
                    'config' => array(
                        'appName'   => 'Addressbook',
                        'modelName' => 'Contact',
                        'type'      => 'APPROVER'
                    )
                )
            ),
            'supplier' => array(
                'type'   => 'virtual',
                'config' => array(
                    'type'   => 'relation',
                    'label'  => 'Supplier',    // _('Supplier')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'Supplier',
                        'type'      => 'SUPPLIER'
                    )
                )
            )
        )
    );
    
    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array(
            'relatedApp'   => 'Sales',
            'relatedModel' => 'Supplier',
            'config'       => array(
                array('type' => 'SUPPLIER', 'degree' => 'sibling', 'text' => 'Supplier', 'max' => '1:0'), // _('Supplier')
            ),
            'defaultType'  => 'SUPPLIER'
        ),
        array(
            'relatedApp'   => 'Addressbook',
            'relatedModel' => 'Contact',
            'config' => array(
                array('type' => 'APPROVER', 'degree' => 'sibling', 'text' => 'Approver', 'max' => '1:0'), // _('Approver')
            ),
            'defaultType'  => 'APPROVER'
        ),
        array(
            'relatedApp'   => 'Sales',
            'relatedModel' => 'CostCenter',
            'config' => array(
                array('type' => 'COST_CENTER', 'degree' => 'sibling', 'text' => 'Lead Cost Center', 'max' => '1:0'), // _('Lead Cost Center')
            ),
            'defaultType'  => 'COST_CENTER'
        ),
    );
}
