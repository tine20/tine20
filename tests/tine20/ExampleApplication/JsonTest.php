<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ExampleApplication
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2014 Metaways Infosystems GmbH (http://www.metaways.de)
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
    /**
     * Backend
     *
     * @var ExampleApplication_Frontend_Json
     */
    public function setUp()
    {
        Tinebase_Application::getInstance()->setApplicationState(array(
            Tinebase_Application::getInstance()->getApplicationByName('ExampleApplication')->getId()
        ), Tinebase_Application::ENABLED);
        
        parent::setUp();
        $this->_json = new ExampleApplication_Frontend_Json();
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
    public function testCreateExampleRecord()
    {
        $ExampleRecord = $this->_getExampleRecord();
        
        $this->assertTrue($ExampleRecord instanceof ExampleApplication_Model_ExampleRecord, 'We have no record the record is instance of wrong object');
        
        $ExampleRecordArray = $ExampleRecord->toArray();
        $this->assertTrue(is_array($ExampleRecordArray), '$ExampleRecordArray is not an array');
        
        $returnedRecord = $this->_json->saveExampleRecord($ExampleRecordArray);
        
        $returnedGet = $this->_json->getExampleRecord($returnedRecord['id'], 0 , '');
        $this->assertEquals($ExampleRecord['name'], $returnedGet['name']);
        
        return $returnedRecord;
    }
    
    /**
     * test search for ExampleRecords
     */
    public function testSearchExampleRecords()
    {
        $inventoryRecord = $this->testCreateExampleRecord();
        $inventoryRecordID = $inventoryRecord['id'];
        
        $searchIDFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $inventoryRecordID));
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
        $this->testCreateExampleRecord();
        
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
}
