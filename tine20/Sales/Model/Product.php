<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold product data
 * 
 * @package     Sales
 */
class Sales_Model_Product extends Tinebase_Record_Abstract
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
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,
        
        'titleProperty'     => 'name',
        'appName'           => 'Sales',
        'modelName'         => 'Product',
        
        'fields'            => array(
            'number'        => array(
                'label'       => 'Number',       //_('Number')
                'type'        => 'string',
                'queryFilter' => TRUE,
            ),
            'name'          => array(
                'label'       => 'Name',         // _('Name')
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'queryFilter' => TRUE,
            ),
            'description'   => array(
                'label'       => 'Description',  // _('Description')
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter' => TRUE,
                'type'        => 'text',
            ),
            'purchaseprice' => array(
                'label'        => 'Purchaseprice', // _('Purchaseprice')
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'         => 'float',
                'specialType'  => 'euMoney',
                'default'      => 0,
                'inputFilters' => array('Zend_Filter_Empty' => 0),
            ),
            'salesprice'    => array(
                'label'        => 'Salesprice',   // _('Salesprice')
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'         => 'float',
                'specialType'  => 'euMoney',
                'default'      => 0,
                'inputFilters' => array('Zend_Filter_Empty' => 0),
            ),
            'category' => array(
                'label'       => 'Category',     // _('Category')
                'default'     => 'DEFAULT',
                'type'        => 'keyfield',
                'name'        => Sales_Config::PRODUCT_CATEGORY
            ),
            'accountable'   => array(
                'label'       => 'Accountable',  // _('Accountable')
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type'        => 'string',
            ),
            'gtin'          => array(
                'label'       => 'GTIN',         // _('GTIN')
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter' => TRUE,
            ),
            'lifespan_start'=> array(
                'label'       => 'Lifespan start',    // _('Lifespan start')
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type'        => 'datetime',
            ),
            'lifespan_end'  => array(
                'label'       => 'Lifespan end',    // _('Lifespan end')
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type'        => 'datetime',
            ),
            'is_active'     => array(
                'label'       => 'Is active',    // _('Is active')
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
                'type'        => 'boolean',
            ),
        )
    );
}