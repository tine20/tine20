<?php
/**
 * class to hold InventoryItem data
 * 
 * @package     Inventory
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
        'type'              => 'type',
        'container_id'      => 'container_id',
        'recordName'        => 'inventory record', // _('inventory record')
        'recordsName'       => 'inventory records', // _('inventory records')
        'containerProperty' => 'container_id',
        'containerName'     => 'inventory record list', // _('inventory record list')
        'containersName'    => 'inventory record lists', // _('inventory record lists')
        // relations (linked Inventory_Model_InventoryItem records) and other metadata
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
            'id'                => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => NULL
            ),
            'name'              => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                    'label'      => 'Name', // _('Name')
            ),
            'type'              => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                    'label'      => 'Type',// _('Type')
            ),
            'container_id'      => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                    'label'      => NULL
            ),
            'inventory_id'      => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => 'Inventory ID' // _('Inventory ID')
            ),
            'description'       => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => NULL
                   
            ),
            'location'          => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'presence' => 'required'),
                    'label'      => 'Location' // _('Location')
            ),
            'add_time'          => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => NULL
            ),
            'total_number'      => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => NULL
                    
            ),
            'active_number'     => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => 'Available' // _(Available)
            ),
            // modlog information
            'created_by'        => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => NULL
            ),
            'creation_time'     => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => NULL
            ),
            'last_modified_by'  => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => NULL
            ),
            'last_modified_time'=> array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => NULL
            ),    
            'is_deleted'        => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => NULL
            ),
            'deleted_time'      => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => NULL
            ),
            'deleted_by'        => array(
                    'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    'label'      => NULL
            )
     );
    
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
    
        foreach (array("total_number", "active_number") AS $val)
        {
            $this->_filters[$val] = new Zend_Filter_Empty(NULL);
        }
    
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
}
