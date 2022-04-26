<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Inventory
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */

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
    public function setUp(): void
{
        parent::setUp();
        $this->_json = new Inventory_Frontend_Json();
    }
    
    /**
     * tests if model gets created properly
     */
    public function testModelCreation()
    {
        $fields = Inventory_Model_InventoryItem::getConfiguration()->getFields();
        $this->assertArrayHasKey('container_id', $fields);
        
        $filters = Inventory_Model_InventoryItem::getConfiguration()->getFilterModel();
        $this->assertArrayHasKey('container_id', $filters['_filterModel']);
    }
    
    /**
     * test creation of an InventoryItem
     *
     * @see 0012182: item price is not saved
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
        $this->assertTrue(isset($returnedGet['price']), 'price property missing from record ' . print_r($returnedGet, true));
        $this->assertEquals($inventoryItem['price'], $returnedGet['price']);

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
        unset($searchDefaultFilter[2]);
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
     * test autoComplete of an InventoryItem
     */
    public function testAutoCompleteInventoryItems()
    {
        $inventoryRecord = $this->testCreateInventoryItem();
        $inventoryRecordID = $inventoryRecord['id'];
        $json = new Tinebase_Frontend_Json();
        $returned = $json->autoComplete('Inventory', 'InventoryItem', 'name', 'mini');
        
        $this->assertEquals('minimal inventory item by PHPUnit::Inventory_JsonTest', $returned['results'][0]['name']);
    }
    
    /**
     * test testSearchInventoryItems for tags of an InventoryItem
     */
    public function testSearchInventoryItemsTags()
    {
        $inventoryRecordWithTag = $this->testCreateInventoryItem();
        $inventoryRecordWithoutTag = $this->testCreateInventoryItem();
        
        $inventoryRecordWithTag['tags'] = array(array(
            'name'    => 'supi',
            'type'    => Tinebase_Model_Tag::TYPE_PERSONAL,
        ));
        $inventoryRecordWithTag = $this->_json->saveInventoryItem($inventoryRecordWithTag);
        $inventoryRecordTagID = $inventoryRecordWithTag['tags'][0]['id'];
        
        $searchTagFilter = array(array('field' => 'tag', 'operator' => 'equals', 'value' => $inventoryRecordTagID));
        
        $returned = $this->_json->searchInventoryItems($searchTagFilter, $this->_getPaging());
        
        $this->assertEquals(1, $returned['totalcount']);
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
        
        $this->expectException('Tinebase_Exception_NotFound');
        $returnValueGet = $this->_json->getInventoryItem($inventoryRecordID);
    }
    
    /**
     * testSearchByCustomfield
     * 
     * @see 0009230: customfield search fails in MC apps
     */
    public function testSearchByCustomfield()
    {
        try {
            $cf = $this->_getCustomField();
            $invItem = $this->testCreateInventoryItem();
            $invItem['customfields']['invcustom'] = 'abc';
            $returnedRecord = $this->_json->saveInventoryItem($invItem);
            $this->assertEquals('abc', $returnedRecord['customfields']['invcustom']);

            Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
            $this->_transactionId = null;

            $filter = array(
                array(
                    'condition' => 'AND',
                    'filters' => array(
                        array(
                            'field' => 'customfield',
                            'operator' => 'contains',
                            'value' => array(
                                'cfId' => $cf->getId(),
                                'value' => 'abc',
                            )
                        )
                    ),
                    'id' => 'ext-comp-1222',
                    'label' => 'Inventargegenstände'
                )
            );

            $searchResult = $this->_json->searchInventoryItems($filter, array());
            $this->assertEquals(1, $searchResult['totalcount'], 'not found: ' . print_r($searchResult, true));
            $this->assertEquals($filter[0], $searchResult['filter'][0], print_r($searchResult['filter'], true));
        } finally {
            if (null !== $returnedRecord && isset($returnedRecord['id'])) {
                $this->_json->delete([$returnedRecord['id']]);
            }
            if (null !== $cf) {
                Tinebase_CustomField::getInstance()->deleteCustomField($cf);
            }
        }
    }
    

    /**
     * test InventoryItem - CostCenter filter
     * @see: 0009588: InventoryItem-CostCenter filter fails without rights on Sales-App
     *       https://forge.tine20.org/mantisbt/view.php?id=9588
     */
    public function testCostCenterFilter()
    {
        $cc = Sales_Controller_CostCenter::getInstance()->create(new Sales_Model_CostCenter(
            array('remark' => 'test123qwe', 'number' => 123)
        ));
        
        $inventoryItem = $this->_getInventoryItem();
        $inventoryItem->costcenter = $cc->getId();
        
        $this->_json->saveInventoryItem($inventoryItem->toArray());
    
        $inventoryItem = $this->_getInventoryItem();
        $this->_json->saveInventoryItem($inventoryItem->toArray());
        
        $filter = Zend_Json::decode('[{"condition":"OR","filters":[{"condition":"AND","filters":[{"field":"costcenter","operator":"AND","value":[{"field":":id","operator":"equals","value":"'.$cc->getId().'"}],"id":"ext-record-2"}],"id":"ext-comp-1135","label":"test"}]}]');
        
        $result = $this->_json->searchInventoryItems($filter, array());
    
        $this->assertEquals(1, $result['totalcount']);
    }
    
    /**
    * get custom field config record
    *
    * @return Tinebase_Model_CustomField_Config
    */
    protected function _getCustomField()
    {
        $cfData = new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId(),
            'name'              => 'invcustom',
            'model'             => 'Inventory_Model_InventoryItem',
            'definition'        => array(
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'string',
                'uiconfig' => array(
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                )
            )
        ));
        
        try {
            $result = Tinebase_CustomField::getInstance()->addCustomField($cfData);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // customfield already exists
            $cfs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication('Inventory');
            $result = $cfs->filter('name', 'invcustom')->getFirstRecord();
        }
        
        return $result;
    }

    /**
     * saveRecordWithImage
     */
    public function testSaveRecordWithImage()
    {
        // create TEMPFILE and save in inv item
        $imageFile = dirname(dirname(dirname(dirname(__FILE__)))) . '/tine20/images/cancel.gif';
        $tempImage = Tinebase_TempFile::getInstance()->createTempFile($imageFile);
        $imageUrl = Tinebase_Model_Image::getImageUrl('Tinebase', $tempImage->getId(), 'tempFile');

        $invItem = $this->_getInventoryItem()->toArray();
        $invItem['image'] = $imageUrl;
        $savedInvItem = $this->_json->saveInventoryItem($invItem);

        $this->assertTrue(! empty($savedInvItem['image']), 'image url is empty');
        $this->assertTrue(preg_match('/location=vfs&id=([a-z0-9]*)/', $savedInvItem['image']) == 1, print_r($savedInvItem, true));

        // check if favicon is delivered
        $image = Tinebase_Model_Image::getImageFromImageURL($savedInvItem['image']);
        $this->assertEquals(52, $image->width);

        // check in search result
        $filter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $savedInvItem['id']));
        $result = $this->_json->searchInventoryItems($filter, []);
        self::assertEquals(1, $result['totalcount']);
        self::assertTrue(isset($result['results'][0]['image']), 'image missing from result '
            . print_r($result['results'], true));
        $this->assertTrue(preg_match('/location=vfs&id=([a-z0-9]*)/', $result['results'][0]['image']) == 1,
            print_r($result['results'][0], true));
    }

    /**
     * testAttachMultipleTagsToMultipleRecord for mcv2 records
     */
    public function testAttachMultipleTagsToMultipleRecord()
    {
        $item = $this->testCreateInventoryItem();
        $tinebaseJson = new Tinebase_Frontend_Json();
        $tag = $this->_getTag();
        $tag = Tinebase_Tags::getInstance()->create($tag);

        $result = $tinebaseJson->attachTagToMultipleRecords(
            array(array('field' => 'id', 'operator' => 'equals', 'value' => $item['id'])),
            'Inventory_Model_InventoryItemFilter',
            $tag->getId()
        );
        self::assertTrue(isset($result['success']) && $result['success']);
    }
}
