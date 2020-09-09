<?php
/**
 * class to hold InventoryItem data
 * 
 * @package     Inventory
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold InventoryItem data
 * 
 * @package     Inventory
 * @subpackage  Model
 *
 * @property    string $name
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
        'version'           => 12,
        'recordName'        => 'Inventory item',
        'recordsName'       => 'Inventory items', // ngettext('Inventory item', 'Inventory items', n)
        'containerProperty' => 'container_id',
        'titleProperty'     => 'name',
        'containerName'     => 'Inventory item list',
        'containersName'    => 'Inventory item lists', // ngettext('Inventory item list', 'Inventory item lists', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'exposeJsonApi'     => TRUE,

        'createModule'      => TRUE,

        'appName'           => 'Inventory',
        'modelName'         => 'InventoryItem',

        'table'             => array(
            'name'    => 'inventory_item',
            'indexes' => array(
                'container_id' => array(
                    'columns' => array('container_id')
                )
            ),
            'uniqueConstraints' => array(
                'inventory_id' => array(
                    'columns' => array('inventory_id', 'deleted_time')
                )
            )
        ),

        'import'            => array(
            'defaultImportContainerRegistryKey' => 'defaultInventoryItemContainer',
        ),
        'export'            => array(
            'supportedFormats' => array('csv', 'ods'),
        ),

        'fields'            => array(
            'name' => array(
                'type'        => 'string',
                'length'      => 255,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Name', // _('Name')
                'queryFilter' => true,
            ),
            'status' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
                'label' => 'Status', // _('Status')
                'type' => 'keyfield',
                'name' => 'inventoryStatus',
            ),
            'inventory_id' => array(
                'type'       => 'string',
                'length'     => 100,
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Inventory ID' // _('Inventory ID')
            ),
            'description' => array(
                // TODO convert to fulltext
                'type'       => 'text',
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      =>'Description' // _('Description')
            ),
            'location' => array(
                'type'       => 'string',
                'length'     => 255,
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Location', // _('Location')
                'queryFilter' => true,
            ),
            'invoice_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Invoice date', // _('Invoice date')
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'hidden'     => TRUE,
                'default'    => NULL,
                'type'       => 'datetime',
                'nullable'     => true,
            ),
            'total_number' => array(
                'type'         => 'integer',
                'nullable'     => true,
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'        => NULL,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'default'      => 1,
            ),
            'active_number' => array(
                'type'         => 'integer',
                'nullable'     => true,
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'        => 'Available number', // _(Available number)
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'default'      => 1,
            ),
            'invoice' => array(
                'type'       => 'string',
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Invoice', // _('Invoice')
                'hidden'     => TRUE
            ),
            'price' => array(
                'type'         => 'money',
                'nullable'     => true,
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'        => 'Price', // _('Price')
                'hidden'       => TRUE,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
            ),
            'costcenter' => array(
                'label'      => 'Cost center', // _('Cost Center')
                'hidden'     => TRUE,
                'type'       => 'record',
                'nullable'   => true,
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
                'type'       => 'datetime',
                'nullable'   => true,
            ),
            'added_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Item added', // _('Item added')
                'hidden'     => TRUE,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'type'       => 'datetime',
                'nullable'   => true,
            ),
            'removed_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Item removed', // _('Item removed')
                'hidden'     => TRUE,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'type'       => 'datetime',
                'nullable'   => true,
            ),
            'deprecated_status' => array(
                'type'         => 'integer',
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => 0),
                //'label'        => 'Depreciate', // _('Depreciate')
                'label'        => NULL,
                'default'      => 0,
            ),
            'image' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                // is saved in vfs, only image files allowed
                'type' => 'image'
            ),
        )
    );
}
