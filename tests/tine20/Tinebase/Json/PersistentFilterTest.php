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
 * 
 * @todo test search -> filter resolving not yet implemented -> do we need this? -> filters need to cope with resolved values!
 */
class Tinebase_Json_PersistentFilterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Tinebase_Frontend_Json_PersistentFilter
     */
    protected $_uit;
    
    public function setUp()
    {
        $this->tearDown();
        $this->_uit = new Tinebase_Frontend_Json_PersistentFilter();
    }
    
    public function tearDown()
    {
        // purge
        $backend = new Tinebase_PersistentFilter_Backend_Sql();
        
        $toDeleteIds = $backend->search(new Tinebase_Model_PersistentFilterFilter(array(
            array('field' => 'name',   'operator' => 'startswith', 'value' => 'PHPUnit'),
        )), NULL, TRUE);
        
        $backend->delete($toDeleteIds);
    }
    
    /**
     * test to save a persistent filter
     */
    public function testSaveFilter($filterData = NULL)
    {
        $exampleFilterData = $filterData ? $filterData : self::getPersitentFilterData();
        $savedFilterData = $this->_uit->savePersistentFilter(self::getPersitentFilterData());
        
        $this->_assertSavedFilterData($exampleFilterData, $savedFilterData);
        
        return $savedFilterData;
    }
    
    public function testGetSimpleFilter()
    {
        $exampleFilterData = self::getPersitentFilterData();
        $savedFilterData = $this->testSaveFilter($exampleFilterData);
        $loadedFilterData = $this->_uit->getPersistentFilter($savedFilterData['id']);
        $this->_assertSavedFilterData($exampleFilterData, $loadedFilterData);
    }
    
    public function testTimezoneConversion()
    {
        $exampleFilterData = self::getPersitentFilterData();
        $savedFilterData = $this->testSaveFilter($exampleFilterData);
        
        $testUserTimezone = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, $testUserTimezone !== 'US/Pacific' ? 'US/Pacific' : 'UTC');
        
        $originalDueDateFilter = $this->_getFilter('due', $exampleFilterData);
        $convertedDueDataFilter = $this->_getFilter('due', $this->_uit->getPersistentFilter($savedFilterData['id']));

        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, $testUserTimezone);
        
        $this->assertNotEquals($originalDueDateFilter['value'], $convertedDueDataFilter['value']);
    }
    
    public function testSearchFilter()
    {
        $exampleFilterData = self::getPersitentFilterData();
        $savedFilterData = $this->testSaveFilter($exampleFilterData);
        
        $filterData = array(
            array('field' => 'model',   'operator' => 'equals', 'value' => 'Tasks_Model_TaskFilter'),
            array('field' => 'id',      'operator' => 'equals', 'value' => $savedFilterData['id'])
        );
        
        $searchResult = $this->_uit->searchPersistentFilter($filterData, NULL);

        $this->assertEquals(1, $searchResult['totalcount']);
        $this->_assertSavedFilterData($exampleFilterData, $searchResult['results'][0]);
    }

    public function testSearchIncludesSharedFavorites()
    {
        $sharedFavorite = self::getPersitentFilter();
        $sharedFavorite->name = 'PHPUnit shared filter';
        $sharedFavorite->account_id = NULL;
        
        $backend = new Tinebase_PersistentFilter_Backend_Sql();
        $persistentSharedFavirite = $backend->create($sharedFavorite);
        
        $exampleFilterData = self::getPersitentFilterData();
        $savedFilterData = $this->testSaveFilter($exampleFilterData);
        
        $filterData = array(
            array('field' => 'model',   'operator' => 'equals',     'value' => 'Tasks_Model_TaskFilter'),
            array('field' => 'name',    'operator' => 'startswith', 'value' => 'PHPUnit'),
        );
        
        $searchResult = $this->_uit->searchPersistentFilter($filterData, NULL);
        
        $this->assertGreaterThanOrEqual(2, $searchResult['totalcount']);
        
        $ids = array();
        foreach($searchResult['results'] as $filterData) {
            $ids[] = $filterData['id'];
        }
        $this->assertEquals(2, count(array_intersect($ids, array($persistentSharedFavirite->getId(), $savedFilterData['id']))));
    }
    
    public function testInitialRegistry()
    {
        $exampleFilterData = self::getPersitentFilterData();
        $savedFilterData = $this->testSaveFilter($exampleFilterData);
        
        $tfj = new Tinebase_Frontend_Json();
        $allRegData = $tfj->getRegistryData();
        
        $this->assertTrue(array_key_exists('persistentFilters', $allRegData), 'persistentFilters is missing in $allRegData');
        
        $ids = array();
        foreach($allRegData['persistentFilters']['results'] as $filterData) {
            $ids[] = $filterData['id'];
        }
        
        $this->assertEquals(1, count(array_intersect($ids, array($savedFilterData['id']))));
    }
// obsolete tests
//    public function testRenameFilter()
//    {
//        $savedFilter = $this->testSaveFilter();
//        $this->_uit->rename($savedFilter['id'], 'renamedFilter');
//        
//        $loadedFilter = $this->_uit->get($savedFilter['id']);
//        $this->assertEquals('renamedFilter', $loadedFilter['name'], 'filter renameing failed');
//    }
// 
//    public function testOverwriteByName()
//    {
//        $givenQuery = $this->_getFilter('query', $this->_testFilterData);
//        $givenQuery['value'] = 'changed';
//        
//        $savedFilter = $this->testSaveFilter();
//        $overwrittenFilter = $this->_uit->savePersistentFilter($this->_testFilterData, 'testFilter', 'Tasks_Model_Task');
//        
//        $loadedFilter = $this->_uit->get($savedFilter['id']);
//        $this->_assertSavedFilter($loadedFilter);
//    }

    /**
     * assert saved filer matches expections for $this->_testFilterData
     * 
     * @param array $savedFilter
     * @return void
     */
    protected function _assertSavedFilterData($expectedFilterData, $savedFilterData)
    {
        
        $this->assertTrue(is_array($savedFilterData), 'saved filter should be an array');
        $this->assertEquals($expectedFilterData['name'],  $savedFilterData['name'], 'name does not match');
        
        $this->assertTrue(array_key_exists('filters', $savedFilterData), 'saved filter data is not included');
        $this->assertTrue(array_key_exists('name', $savedFilterData), 'saved filter name is not included');
        
        foreach($expectedFilterData['filters'] as $requestFilter) {
            $responseFilter = $this->_getFilter($requestFilter['field'], $savedFilterData);
            $this->assertTrue(is_array($responseFilter), 'filter is missing in response');
            $this->assertEquals($requestFilter['operator'], $responseFilter['operator'], 'operator missmatch');
            
            switch ($requestFilter['field']) {
                case 'container_id':
                    $this->assertTrue(is_array($responseFilter['value']), 'container is not resolved');
                    $this->assertEquals($requestFilter['value'], $responseFilter['value']['id'], 'wrong containerId');
                    break;
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
     * returns data for an example persisten filter
     * 
     * @return array
     */
    public static function getPersitentFilterData()
    {
        return array(
            'name'              => 'PHPUnit testFilter',
            'description'       => 'a test filter created by PHPUnit',
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'model'             => 'Tasks_Model_TaskFilter',
            'filters'           => array(
                array('field' => 'query',        'operator' => 'contains',  'value' => 'test'),
                array('field' => 'container_id', 'operator' => 'equals',    'value' => Tasks_Controller::getInstance()->getDefaultContainer()->getId()),
                array('field' => 'organizer',    'operator' => 'equals',    'value' => Tinebase_Core::getUser()->getId()),
                array('field' => 'due',          'operator' => 'after',     'value' => '2010-03-20 18:00:00'),
            )
        );
    }
    
    /**
     * returns an example persisten filter
     * 
     * @return Tinebase_Model_PersistentFilter
     */
    public static function getPersitentFilter()
    {
        return new Tinebase_Model_PersistentFilter(self::getPersitentFilterData());
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
        foreach ($_filterData['filters'] as &$filter) {
            if ($filter['field'] == $_field) {
                return $filter;
            }
        }
    }
}
    

if (PHPUnit_MAIN_METHOD == 'Tinebase_Json_PersistentFilterTest::main') {
    Tinebase_Json_PersistentFilterTest::main();
}
