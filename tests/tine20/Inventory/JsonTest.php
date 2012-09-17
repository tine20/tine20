<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Inventory
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Inventory_JsonTest
 */
class Inventory_JsonTest extends Inventory_TestCase
{
    /**
     * Backend
     *
     * @var Inventory_Frontend_Json
     */
    public function setUp()
    {
        parent::setUp();
        $this->_json = new Inventory_Frontend_Json();
    }
    
    /**
     * test creation of a InventoryItem
     */
    public function testCreateInventoryItem()
    {
        $inventoryItem = $this->_getInventoryItem();
        
        $this->assertTrue(is_object($inventoryItem), 'We have no inventory item');
        
        $inventoryItemArray = $inventoryItem->toArray();
        
        $this->assertTrue(is_array($inventoryItemArray), '$inventoryItemArray is not an array');
        
        $returned = $this->_json->saveInventoryItem($inventoryItemArray);
        
        $returnedGet = $this->_json->getInventoryItem($returned['id'], 0 , '');
        $this->assertEquals($inventoryItem['description'], $returnedGet['description']);
    }
}
