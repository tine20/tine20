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
     * test creation of an InventoryItem
     */
    public function testCreateInventoryItem()
    {
        $inventoryItem = $this->_getInventoryItem();
        
        $this->assertTrue($inventoryItem instanceof Inventory_Model_InventoryItem, 'We have no inventory item or inventory item is instance of wrong object');
        
        $inventoryItemArray = $inventoryItem->toArray();
        $this->assertTrue(is_array($inventoryItemArray), '$inventoryItemArray is not an array');
        
        $returnedRecord = $this->_json->saveInventoryItem($inventoryItemArray);
        
        $returnedGet = $this->_json->getInventoryItem($returnedRecord['id'], 0 , '');
        $this->assertEquals($inventoryItem['description'], $returnedGet['description']);
        
        return $returnedRecord;
    }
    
    /**
     * test search for InventoryItems
     */
    public function testSearchInventoryItems()
    {
        $inventoryRecord = $this->testCreateInventoryItem();
        $inventoryRecordID = $inventoryRecord['id'];
        
        $searchIDFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $inventoryRecordID));
        $searchDefaultFilter = $this->_getFilter();
        $mergedSearchFilter = array_merge($searchIDFilter, $searchDefaultFilter);
        
        $returned = $this->_json->searchInventoryItems($searchDefaultFilter, $this->_getPaging());
        
        $this->assertEquals($returned['totalcount'], 1);
        
        $count = 0;
        foreach ($returned as $value => $key) {
            if (is_array($key)) {
                foreach ($key as $result) {
                    if (is_array($result) && key_exists('id', $result)) {
                        if ($result['id'] == $inventoryRecordID) {
                            $count++;
                        }
                    }
                }
            }
        }
        $this->assertEquals($count, 1);
    }
    
    /**
     * test deletetion of an InventoryItem
     */
    public function testDeleteInventoryItems()
    {
        $inventoryRecord = $this->testCreateInventoryItem();
        $inventoryRecordID = $inventoryRecord['id'];
        
        $returnValueDeletion = $this->_json->deleteInventoryItems($inventoryRecordID);
        $this->assertEquals($returnValueDeletion['status'], 'success');
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $returnValueGet = $this->_json->getInventoryItem($inventoryRecordID);
    }
}
