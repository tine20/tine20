<?php
/**
 * InventoryItem controller for Inventory application
 * 
 * @package     Inventory
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * InventoryItem controller class for Inventory application
 * 
 * @package     Inventory
 * @subpackage  Controller
 */
class Inventory_Controller_InventoryItem extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'Inventory';
        $this->_backend = new Inventory_Backend_InventoryItem();
        $this->_modelName = 'Inventory_Model_InventoryItem';
        $this->_purgeRecords = FALSE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
        $this->_resolveCustomFields = TRUE;
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Inventory_Controller_InventoryItem
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Inventory_Controller_InventoryItem
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Inventory_Controller_InventoryItem();
        }
        
        return self::$_instance;
    }

    /**
     * returns the default inventory
     *
     * @return Tinebase_Model_Container
     */
    public function getDefaultInventory()
    {
        return Tinebase_Container::getInstance()->getDefaultContainer($this->_applicationName, NULL, Inventory_Preference::DEFAULT_INVENTORYITEM_CONTAINER);
    }
    
    /****************************** overwritten functions ************************/
}
