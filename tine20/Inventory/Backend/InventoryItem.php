<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for InventoryItems
 *
 * @package     Inventory
 * @subpackage  Backend
 */
class Inventory_Backend_InventoryItem extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'inventory_item';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Inventory_Model_InventoryItem';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;

    /************************ overwritten functions *******************/  
    
    /************************ helper functions ************************/
}
