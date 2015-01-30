<?php
/**
 * class to hold product data
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        get categories from settings/config
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
            'name'              => array(
                'label'      => 'Name',    // _('Name')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'queryFilter' => TRUE,
            ),
            'description'       => array(
                'label'      => 'Description',    // _('Description')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter' => TRUE,
                'type' => 'text',
            ),
            'price'             => array(
                'label'      => 'Price',    // _('Price')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
            ),
            'manufacturer'      => array(
                'label'      => 'Manufacturer',    // _('Manufacturer')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter' => TRUE,
            ),
            'category'          => array(
                'label'      => 'Category',    // _('Category')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter' => TRUE,
            ),
            'accountable' => array(
                'label' => 'Accountable',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type' => 'string',
            ),
            'lifespan_start'  => array(
                'label'      => 'Lifespan Start',    // _('Lifespan Start')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type' => 'datetime',
            ),
            'lifespan_end'  => array(
                'label'      => 'Lifespan End',    // _('Lifespan End')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type' => 'datetime',
            ),
            'is_active'  => array(
                'label'      => 'Is Active',    // _('Is Active')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
                'type' => 'boolean',
            ),
        )
    );
}
