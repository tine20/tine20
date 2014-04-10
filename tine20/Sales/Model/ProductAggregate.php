<?php
/**
 * class to hold product aggregate data
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexaander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold product data
 * 
 * @package     Sales
 */
class Sales_Model_ProductAggregate extends Tinebase_Record_Abstract
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
        'recordName'        => 'Product',
        'recordsName'       => 'Products', // ngettext('Product', 'Products', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => FALSE,
        'hasAttachments'    => FALSE,
        'createModule'      => FALSE,
        'containerProperty' => NULL,
        'isDependent'       => TRUE,
        
        'titleProperty'     => 'product_id.name',
        'appName'           => 'Sales',
        'modelName'         => 'ProductAggregate',
        
        'fields'            => array(
            'product_id'       => array(
                'label'      => 'Product',    // _('Product')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'sortable'   => FALSE,
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Product',
                    'idProperty'  => 'id',
                )
            ),
            'contract_id'       => array(
                'isParent'    => TRUE,
                'label'      => 'Contract',    // _('Contract')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'sortable'   => FALSE,
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Contract',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'quantity' => array(
                'label' => 'Quantity', // _('Quantity')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'       => 'integer',
                'default'    => 1
            ),
            'interval' => array(
                'label'      => 'Billing Interval', // _('Billing Interval')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'integer',
                'default'    => 1
            ),
            'last_autobill' => array(
                'label'      => NULL,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'type'       => 'datetime',
                'default'    => NULL
            ),
        )
    );

    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Sales', 'relatedModel' => 'Invoice', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
        ), 'defaultType' => 'INVOICE_ITEM'
        ),
    );
}
