<?php
/**
 * class to hold InventoryItem data
 * 
 * @package     Inventory
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * key in $_validators/$_properties array for the filed which
     * represents the identifier
     *
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Inventory';

    /**
     * list of zend validator
     *
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected static $_meta = array(
        'id'                => 'id',
        'name'              => 'name',
        'titleProperty'     => 'name',
        'container_id'      => 'container_id',
        'recordName'        => 'Inventory item', // _('Inventory item') ngettext('Inventory item', 'Inventory items', n)
        'recordsName'       => 'Inventory items', // _('Inventory items')
        'containerProperty' => 'container_id',
        'containerName'     => 'Inventory item list', // _('Inventory item list')
        'containersName'    => 'Inventory items lists', // _('Inventory items lists')
        'hasRelations'      => true,
        'hasCustomFields'   => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'useModlog'         => true
    );
    
    /**
     * fields for auto start
     * @var array
     */
    protected static $_fields = array(
            'id' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => NULL
            ),
            'name' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'      => 'Name', // _('Name')
            ),
            'status' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label' => 'Status', // _('Status')
                'type' => 'keyfield',
                'name' => 'inventoryStatus'
            ),
            'container_id' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'      => NULL
            ),
            'inventory_id' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Inventory ID' // _('Inventory ID')
            ),
            'description' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Description' // _('Description')
            ),
            'location' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Location' // _('Location')
            ),
            'invoice_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Invoice date', // _('Invoice date')
                'hidden'     => true
            ),
            'total_number' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => NULL
            ),
            'invoice' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Invoice', // _('Invoice')
                'hidden'     => TRUE
            ),
            'price' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Price', // _('Price')
                'hidden'     => TRUE
            ),
            'costcentre' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Cost centre', // _('Cost centre')
                'hidden'     => TRUE
            ),
            'warranty' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Warranty', // _('Warranty')
                'hidden'     => TRUE
            ),
            'added_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Item added', // _('Item added')
                'hidden'     => TRUE
            ),
            'removed_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Item removed', // _('Item removed')
                'hidden'     => TRUE
            ),
            'active_number' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Available number' // _(Available number)
            ),
            'total_number' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Quantity' // _(Quantity)
            ),
            'depreciate_status' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                //'label' => 'Depreciate', // _('Depreciate')
                'label'      => NULL,
            ),
            'created_by' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => NULL,
                'hidden'     => TRUE
            ),
            'creation_time' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => NULL,
                'hidden'     => TRUE
            ),
            'last_modified_by' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => NULL
            ),
            'last_modified_time'=> array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => NULL
            ),
            'is_deleted'        => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => NULL,
                'hidden'     => true
            ),
            'deleted_time'      => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => NULL
            ),
            'deleted_by'        => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => NULL
            ),
            'adt_info'              => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true)
            )
     );
    
    /**
     * Returns every valid key to manage in Frontend/Json.php
     * 
     * @todo to be removed if we have a similiar function in the core
     * @param void
     * @return array
     */
    public static function getValidFields ()
    {
        return array_keys(self::$_fields);
    }
    
    /**
     * overwrite constructor to add more filters
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     * @return void
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        foreach (array("total_number", "active_number", "invoice_date",
                    "price", "added_date", "warranty",
                    "removed_date", "description", "location")
                as $val)
        {
            $this->_filters[$val] = new Zend_Filter_Empty(NULL);
        }
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
}
