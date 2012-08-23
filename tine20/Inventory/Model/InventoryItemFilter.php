<?php
/**
 * Tine 2.0
 * 
 * @package     Inventory
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * InventoryItem filter Class
 * 
 * @package     Inventory
 * @subpackage  Model
 */
class Inventory_Model_InventoryItemFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Inventory_Model_InventoryItemFilter';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Inventory';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Inventory_Model_InventoryItem';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name', /*'...'*/))),
        'container_id'   => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Inventory')),
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'type'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'inventory_item.id',
            'applicationName' => 'Inventory',
        )),
        'name'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'inventory_id'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'location'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'add_time'       => array('filter' => 'Tinebase_Model_Filter_Date'),
        'total_number'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'active_number'  => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
