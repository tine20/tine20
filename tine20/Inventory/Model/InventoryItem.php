<?php
/**
 * class to hold InventoryItem data
 * 
 * @package     Inventory
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold InventoryItem data
 * 
 * @package     Inventory
 * @subpackage  Model
 */
class Inventory_Model_InventoryItem extends Tinebase_Record_Abstract
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
        'recordName'        => 'Inventory item', // _('Inventory item') ngettext('Inventory item', 'Inventory items', n)
        'recordsName'       => 'Inventory items', // _('Inventory items')
        'containerProperty' => 'container_id',
        'titleProperty'     => 'name',
        'containerName'     => 'Inventory item list', // _('Inventory item list')
        'containersName'    => 'Inventory items lists', // _('Inventory items lists')
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,

        'createModule'    => TRUE,

        'appName'         => 'Inventory',
        'modelName'       => 'InventoryItem',
        
        'fields'          => array(
            'name' => array(
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Name', // _('Name')
                'queryFilter' => TRUE
            ),
            'status' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label' => 'Status', // _('Status')
                'type' => 'keyfield',
                'name' => 'inventoryStatus',
                'default' => 'UNKNOWN'
            ),
            'inventory_id' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Inventory ID' // _('Inventory ID')
            ),
            'description' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      =>'Description' // _('Description')
            ),
            'location' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Location', // _('Location')
            ),
            'invoice_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Invoice date', // _('Invoice date')
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'hidden'     => TRUE,
                'default'    => NULL,
                'type'       => 'datetime',
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
            ),
            'total_number' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => NULL,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'default'    => 1,
            ),
            'invoice' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Invoice', // _('Invoice')
                'hidden'     => TRUE
            ),
            'price' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Price', // _('Price')
                'hidden'     => TRUE,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
            ),
            'costcentre' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Cost centre', // _('Cost centre')
                'hidden'     => TRUE,
                'type'       => 'record',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'CostCenter',
                    'idProperty'  => 'id',
                ),
            ),
            'warranty' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Warranty', // _('Warranty')
                'hidden'     => TRUE,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'type'       => 'datetime'
            ),
            'added_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Item added', // _('Item added')
                'hidden'     => TRUE,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'type'       => 'datetime'
            ),
            'removed_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Item removed', // _('Item removed')
                'hidden'     => TRUE,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'type'       => 'datetime'
            ),
            'active_number' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Available number', // _(Available number)
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'default'    => 1,
            ),
            'depreciate_status' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => 0),
                //'label' => 'Depreciate', // _('Depreciate')
                'label'      => NULL,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
            ),
        )
     );
    
    /**
     * Returns every valid key to manage in Frontend/Json.php
     * 
     * @todo to be removed if we have a similiar function in the core
     * @param void
     * @return array
     */
    public static function getValidFields()
    {
        if (static::$_configurationObject===NULL){
            static::getConfiguration();
        }
        return static::$_configurationObject->fieldKeys;
    }
}
