<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ExampleApplication
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for ExampleApplication_JsonTest
 */
class ExampleApplication_JsonTest extends ExampleApplication_TestCase
{
    protected $_recordsToDelete = array();

    public function setUp()
    {
        Tinebase_Application::getInstance()->setApplicationStatus(array(
            Tinebase_Application::getInstance()->getApplicationByName('ExampleApplication')->getId()
        ), Tinebase_Application::ENABLED);
        
        parent::setUp();
        $this->_json = $this->_getUit();
        $this->_recordsToDelete = array();
    }

    protected function tearDown()
    {
        if (count($this->_recordsToDelete) > 0)
        {
            $this->_json->deleteExampleRecords(array_keys($this->_recordsToDelete));

            foreach($this->_recordsToDelete as $record) {
                $className = get_class($record);
                $configuration = $record->getConfiguration();
                foreach ($configuration->getAutoincrementFields() as $fieldDef) {
                    $numberable = Tinebase_Numberable::getNumberable($className, $fieldDef['fieldName'], $fieldDef);
                    $numberable->free($record->{$fieldDef['fieldName']});
                }
            }
        }

        parent::tearDown();
    }

    /**
     * @see 0013754: get numbearable config from container xprops
     */
    public function testNumberableConfigByContainer()
    {
        Tinebase_Numberable::clearCache();

        $container = $this->_getTestContainer(ExampleApplication_Config::APP_NAME,
            ExampleApplication_Model_ExampleRecord::class);
        // add xprops to container
        $container->xprops()[Tinebase_Numberable::CONFIG_XPROPS] = [
            Tinebase_Numberable::STEPSIZE => 10,
            // TODO create this automatically?
            Tinebase_Numberable::BUCKETKEY => ExampleApplication_Model_ExampleRecord::class .
                '#number_int#' . $container->getId(),
            Tinebase_Numberable::START => 100,
        ];
        $updatedContainer = Tinebase_Container::getInstance()->update($container);
        self::assertTrue(isset($updatedContainer->xprops()[Tinebase_Numberable::CONFIG_XPROPS]), 'xprops not set'
            . print_r($updatedContainer->xprops(), true));
        self::assertSame(100, $updatedContainer->xprops()[Tinebase_Numberable::CONFIG_XPROPS][Tinebase_Numberable::START], 'xprops not set right '
            . print_r($updatedContainer->xprops(), true));
        $exampleRecord = $this->_getExampleRecord();
        $exampleRecord->container_id = $updatedContainer->getId();
        $savedExampleRecord = $this->_json->saveExampleRecord($exampleRecord->toArray());
        self::assertGreaterThanOrEqual(100, $savedExampleRecord['number_int'], 'number_int should be greater than 100'
            . print_r($savedExampleRecord, true));

        // create another record to test STEPSIZE inc
        $savedExampleRecord2 = $this->_json->saveExampleRecord($exampleRecord->toArray());
        self::assertEquals($savedExampleRecord['number_int'] + 10, $savedExampleRecord2['number_int']);
    }

    /**
     * tests if model gets created properly
     */
    public function testModelCreation()
    {
        $fields = ExampleApplication_Model_ExampleRecord::getConfiguration()->getFields();
        $this->assertArrayHasKey('container_id', $fields);
        
        $filters = ExampleApplication_Model_ExampleRecord::getConfiguration()->getFilterModel();
        $this->assertArrayHasKey('container_id', $filters['_filterModel']);
    }
    
    /**
     * test creation of an ExampleRecord
     */
    public function testCreateExampleRecord($expectedNumber = 1)
    {
        $exampleRecord = $this->_getExampleRecord();
        
        $this->assertTrue($exampleRecord instanceof ExampleApplication_Model_ExampleRecord, 'We have no record the record is instance of wrong object');
        
        $exampleRecordArray = $exampleRecord->toArray();
        $this->assertTrue(is_array($exampleRecordArray), '$exampleRecordArray is not an array');
        
        $returnedRecord = $this->_json->saveExampleRecord($exampleRecordArray);
        
        $returnedGet = $this->_json->getExampleRecord($returnedRecord['id'], 0 , '');
        $this->assertEquals($exampleRecord['name'], $returnedGet['name']);
        $this->assertTrue(isset($returnedGet['number_str']), 'number_str missing');
        $this->assertEquals('ER-' . $expectedNumber, $returnedGet['number_str']);
        $this->assertEquals('some words in the description for the fulltext search', $returnedGet['description']);
        
        return $returnedRecord;
    }

    /**
     * testAutoIncrementNumber
     *
     * @see 0012004: add numberable property for containers
     */
    public function testAutoIncrementNumber()
    {
        $this->testCreateExampleRecord();
        $exampleRecord2 = $this->_getExampleRecord();
        $returnedRecord = $this->_json->saveExampleRecord($exampleRecord2->toArray());
        $this->assertEquals('ER-2', $returnedRecord['number_str']);
    }
    
    /**
     * test search for ExampleRecords
     */
    public function testSearchExampleRecords()
    {
        $inventoryRecord = $this->testCreateExampleRecord();
        $inventoryRecordID = $inventoryRecord['id'];
        $this->_recordsToDelete[$inventoryRecordID] = ExampleApplication_Controller_ExampleRecord::getInstance()->get($inventoryRecordID);

        // commit transaction for full text to work
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
            $this->_transactionId = null;
        }

        $searchIDFilter = array(
            array('field' => 'id', 'operator' => 'equals', 'value' => $inventoryRecordID),
            array('field' => 'description', 'operator' => 'contains', 'value' => 'description fulltext words search')
        );
        $searchDefaultFilter = $this->_getFilter();
        $mergedSearchFilter = array_merge($searchIDFilter, $searchDefaultFilter);
        
        $returned = $this->_json->searchExampleRecords($mergedSearchFilter, $this->_getPaging());
        
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
     * test testSearchExampleRecords for tags of an ExampleRecord
     */
    public function testSearchExampleRecordsTags()
    {
        $exampleRecordWithTag = $this->testCreateExampleRecord();
        // create a second record with no tag
        $this->testCreateExampleRecord(2);
        
        $exampleRecordWithTag['tags'] = array(array(
            'name'    => 'supi',
            'type'    => Tinebase_Model_Tag::TYPE_PERSONAL,
        ));
        $exampleRecordWithTag = $this->_json->saveExampleRecord($exampleRecordWithTag);
        $exampleRecordTagID = $exampleRecordWithTag['tags'][0]['id'];
        
        $searchTagFilter = array(array('field' => 'tag', 'operator' => 'equals', 'value' => $exampleRecordTagID));
        
        $returned = $this->_json->searchExampleRecords($searchTagFilter, $this->_getPaging());
        
        $this->assertEquals(1, $returned['totalcount']);
    }
    
    /**
     * test deletetion of an ExampleRecord
     */
    public function testDeleteExampleRecords()
    {
        $exampleRecord = $this->testCreateExampleRecord();
        $exampleRecordID = $exampleRecord['id'];
        
        $returnValueDeletion = $this->_json->deleteExampleRecords($exampleRecordID);
        $this->assertEquals($returnValueDeletion['status'], 'success');
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_json->getExampleRecord($exampleRecordID);
    }

    /**
     * testCustomFields
     */
    public function testCustomFields()
    {
        $this->_createCustomField($name = 'YomiName', $model = 'ExampleApplication_Model_ExampleRecord');
        $exampleRecord = $this->testCreateExampleRecord();
        $exampleRecord['customfields']['YomiName'] = 'lalala';

        $savedRecord = $this->_json->saveExampleRecord($exampleRecord);
        self::assertEquals($exampleRecord['customfields']['YomiName'], $savedRecord['customfields']['YomiName']);

        $record = $this->_json->getExampleRecord($exampleRecord['id']);
        self::assertEquals($exampleRecord['customfields']['YomiName'], $record['customfields']['YomiName']);
    }

    /**
     * testNumberableQuerySearch
     */
    public function testNumberableQuerySearch()
    {
        if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            static::markTestSkipped('pgsl doesnt support query filter on integer numberables');
        }
        $exRecord = $this->testCreateExampleRecord();
        $filter = [[
            'field' => 'query',
            'operator' => 'contains',
            'value' => $exRecord['number_str']
        ]];
        $result = $this->_json->searchExampleRecords($filter, []);
        self::assertGreaterThanOrEqual(1, $result['totalcount']);

        $filter[0]['value'] = $exRecord['number_int'];
        $result = $this->_json->searchExampleRecords($filter, []);
        self::assertGreaterThanOrEqual(1, $result['totalcount']);
    }
}
