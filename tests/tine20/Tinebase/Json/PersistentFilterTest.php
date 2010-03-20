<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Json_PersistentFilterTest::main');
}

/**
 * Test class for Tinebase_Frontend_Json_PersistentFilter
 */
class Tinebase_Json_PersistentFilterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Tinebase_Frontend_Json_PersistentFilter
     */
    protected $_uit;
    
    protected $_testFilterIds = array();
    
    public function setUp()
    {
        $this->_uit = new Tinebase_Frontend_Json_PersistentFilter();
        
        $this->_testFilterData = array(
            array('field' => 'query', 'operator' => 'contains', 'value' => 'test'),
            array('field' => 'organizer', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()),
            array('field' => 'due', 'operator' => 'after', 'value' => '2010-03-20 18:00:00'),
        );
    }
    
    public function tearDown()
    {
        foreach (array_unique($this->_testFilterIds) as $filterId) {
            $this->_uit->delete($filterId);
        }
    }
    
    public function testSaveFilter()
    {
        $savedFilter = $this->_uit->save($this->_testFilterData, 'testFilter', 'Tasks_Model_Task');
        $this->_testFilterIds[] = $savedFilter['id'];
        
        $this->_assertSavedFilter($savedFilter);
        
        return $savedFilter;
    }
    
    public function testGetSimpleFilter()
    {
        $savedFilter = $this->testSaveFilter();
        $loadedFilter = $this->_uit->get($savedFilter['id']);
        $this->_assertSavedFilter($loadedFilter);
    }
    
    public function testRenameFilter()
    {
        $savedFilter = $this->testSaveFilter();
        $this->_uit->rename($savedFilter['id'], 'renamedFilter');
        
        $loadedFilter = $this->_uit->get($savedFilter['id']);
        $this->assertEquals('renamedFilter', $loadedFilter['name'], 'filter renameing failed');
    }
    
    public function testOverwriteByName()
    {
        $givenQuery = $this->_getFilter('query', $this->_testFilterData);
        $givenQuery['value'] = 'changed';
        
        $savedFilter = $this->testSaveFilter();
        $overwrittenFilter = $this->_uit->save($this->_testFilterData, 'testFilter', 'Tasks_Model_Task');
        
        $loadedFilter = $this->_uit->get($savedFilter['id']);
        $this->_assertSavedFilter($loadedFilter);
    }
    
    public function testTimezoneConversion()
    {
        $savedFilter = $this->testSaveFilter();
        
        $testUserTimezone = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, $testUserTimezone !== 'US/Pacific' ? 'US/Pacific' : 'UTC');
        
        $originalDueDateFilter = $this->_getFilter('due', $this->_testFilterData);
        $convertedDueDataFilter = $this->_getFilter('due', array_value('filters', $this->_uit->get($savedFilter['id'])));
        
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, $testUserTimezone);
        
        $this->assertNotEquals($originalDueDateFilter['value'], $convertedDueDataFilter['value']);
    }
    
    /**
     * assert saved filer matches expections for $this->_testFilterData
     * 
     * @param array $savedFilter
     * @return void
     */
    protected function _assertSavedFilter($savedFilter)
    {
        $this->assertTrue(is_array($savedFilter), 'saved filter should be an array');
        $this->assertEquals('testFilter',  $savedFilter['name'], 'name does not match');
        
        $this->assertTrue(array_key_exists('filters', $savedFilter), 'saved filter data is not included');
        $this->assertTrue(array_key_exists('name', $savedFilter), 'saved filter name is not included');
        
        foreach($this->_testFilterData as $requestFilter) {
            $responseFilter = $this->_getFilter($requestFilter['field'], $savedFilter['filters']);
            $this->assertTrue(is_array($responseFilter), 'filter is missing in response');
            $this->assertEquals($requestFilter['operator'], $responseFilter['operator'], 'operator missmatch');
            
            switch ($requestFilter['field']) {
                case 'organizer':
                    $this->assertTrue(is_array($responseFilter['value']), 'user is not resolved');
                    $this->assertEquals($requestFilter['value'], $responseFilter['value']['accountId'], 'wrong accountId');
                    break;
                case 'due':
                    //echo date_default_timezone_get();
                    $this->assertEquals($requestFilter['value'], $responseFilter['value'], 'wrong due date');
                    break;
                default:
                    // do nothting
                    break;
            }
            
        }
    }
    
    /**
     * returns first filter of given field in filtersetData
     * 
     * @param string $_field
     * @param array $_filterData
     * @return array
     */
    protected function &_getFilter($_field, $_filterData)
    {
        foreach ($_filterData as &$filter) {
            if ($filter['field'] == $_field) {
                return $filter;
            }
        }
    }
}
    

if (PHPUnit_MAIN_METHOD == 'Tinebase_Json_PersistentFilterTest::main') {
    Tinebase_Json_PersistentFilterTest::main();
}
