<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id:JsonTest.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        add test for contract <-> timeaccount relations
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Timetracker_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Timetracker_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Timetracker_Frontend_Json
     */
    protected $_json = array();
    
    /**
     * timesheet/timeaccounts to delete
     * @var array
     */
    protected $_toDeleteIds = array(
        'ta'    => array(),
        'cf'    => array(),
    );
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Timetracker Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_json = new Timetracker_Frontend_Json();        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     * 
     * @todo use this for all ts/ta that are created in the tests
     */
    protected function tearDown()
    {
        $this->_json->deleteTimeaccounts($this->_toDeleteIds['ta']);
        foreach ($this->_toDeleteIds['cf'] as $cf) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cf);
        }
    }
    
    /**
     * try to add a Timeaccount
     *
     */
    public function testAddTimeaccount()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());
        
        // checks
        $this->assertEquals($timeaccount->description, $timeaccountData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timeaccountData['created_by']);
        $this->assertTrue(is_array($timeaccountData['container_id']));
        $this->assertEquals(Tinebase_Model_Container::TYPE_SHARED, $timeaccountData['container_id']['type']);
        $this->assertGreaterThan(0, count($timeaccountData['grants']));
        
        // cleanup
        $this->_json->deleteTimeaccounts($timeaccountData['id']);

        // check if it got deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Timetracker_Controller_Timeaccount::getInstance()->get($timeaccountData['id']);
    }
    
    /**
     * try to get a Timeaccount
     *
     */
    public function testGetTimeaccount()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());
        $timeaccountData = $this->_json->getTimeaccount($timeaccountData['id']);
        
        // checks
        $this->assertEquals($timeaccount->description, $timeaccountData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timeaccountData['created_by']);
        $this->assertTrue(is_array($timeaccountData['container_id']));
        $this->assertEquals(Tinebase_Model_Container::TYPE_SHARED, $timeaccountData['container_id']['type']);
                        
        // cleanup
        $this->_json->deleteTimeaccounts($timeaccountData['id']);
    }

    /**
     * try to update a Timeaccount
     *
     */
    public function testUpdateTimeaccount()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());
        
        // update Timeaccount
        $timeaccountData['description'] = "blubbblubb";
        $timeaccountUpdated = $this->_json->saveTimeaccount($timeaccountData);
        
        // check
        $this->assertEquals($timeaccountData['id'], $timeaccountUpdated['id']);
        $this->assertEquals($timeaccountData['description'], $timeaccountUpdated['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timeaccountUpdated['last_modified_by']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timeaccountData['id']);
    }
    
    /**
     * try to get a Timeaccount
     *
     */
    public function testSearchTimeaccounts()
    {
        // create
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());
        
        // search & check
        $search = $this->_json->searchTimeaccounts($this->_getTimeaccountFilter(), $this->_getPaging());
        $this->assertEquals($timeaccount->description, $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timeaccountData['id']);
    }
    
    /**
     * try to add a Timeaccount with grants
     *
     */
    public function testAddTimeaccountWithGrants()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $timeaccount->toArray();
        $grants = $this->_getGrants();
        $timeaccountData['grants'] = $this->_getGrants();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccountData);
        
        // checks
        $this->assertGreaterThan(0, count($timeaccountData['grants']));
        $this->assertEquals($grants[0]['account_type'], $timeaccountData['grants'][0]['account_type']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timeaccountData['id']);

        // check if it got deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Timetracker_Controller_Timeaccount::getInstance()->get($timeaccountData['id']);
    }
    
    /**
     * try to add a Timesheet
     *
     */
    public function testAddTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // checks
        $this->assertEquals($timesheet->description, $timesheetData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['created_by']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['account_id']['accountId'], 'account is not resolved');
        $this->assertEquals(Zend_Date::now()->toString('YYYY-MM-dd'),  $timesheetData['start_date']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
        
        // check if everything got deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Timetracker_Controller_Timesheet::getInstance()->get($timesheetData['id']);
    }

    /**
     * try to add a Timesheet with custom fields
     *
     */
    public function testAddTimesheetWithCustomFields()
    {
        $value = 'abcd';
        $cf = $this->_getCustomField();
                
        // create two timesheets with customfields
        $this->_addTsWithCf($cf, $value);
        $this->_addTsWithCf($cf, 'efgh');
        
        // search custom field values and check totalcount
        $tinebaseJson = new Tinebase_Frontend_Json();
        $cfValues = $tinebaseJson->searchCustomFieldValues(Zend_Json::encode($this->_getCfValueFilter($cf->getId())), '');
        $this->assertEquals($value, $cfValues['results'][0]['value'], 'value mismatch');
        $this->assertEquals(2, $cfValues['totalcount'], 'wrong totalcount');
    }
    
    /**
     * try to get a Timesheet
     *
     */
    public function testGetTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        $timesheetData = $this->_json->getTimesheet($timesheetData['id']);
        
        // checks
        $this->assertEquals($timesheet->description, $timesheetData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['created_by']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['account_id']['accountId'], 'account is not resolved');
        $this->assertEquals($timesheet['timeaccount_id'], $timesheetData['timeaccount_id']['id'], 'timeaccount is not resolved');
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
    }

    /**
     * try to update a Timesheet (with relations)
     *
     */
    public function testUpdateTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // update Timesheet
        $timesheetData['description'] = "blubbblubb";
        //$timesheetData['container_id'] = $timesheetData['container_id']['id'];
        $timesheetData['account_id'] = $timesheetData['account_id']['accountId'];
        $timesheetData['timeaccount_id'] = $timesheetData['timeaccount_id']['id'];
        
        $timesheetUpdated = $this->_json->saveTimesheet($timesheetData);
        
        // check
        $this->assertEquals($timesheetData['id'], $timesheetUpdated['id']);
        $this->assertEquals($timesheetData['description'], $timesheetUpdated['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetUpdated['last_modified_by']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetUpdated['account_id']['accountId'], 'account is not resolved');
        $this->assertEquals($timesheetData['timeaccount_id'], $timesheetUpdated['timeaccount_id']['id'], 'timeaccount is not resolved');
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']);
    }

    /**
     * try to update multiple Timesheets
     */
    public function testUpdateMultipleTimesheetsWithIds()
    {
        // create 2 timesheets
        $timesheet1 = $this->_getTimesheet();
        $timesheetData1 = $this->_json->saveTimesheet($timesheet1->toArray());
        $timesheet2 = $this->_getTimesheet($timesheetData1['timeaccount_id']['id']);
        $timesheetData2 = $this->_json->saveTimesheet($timesheet2->toArray());
        
        $this->assertEquals($timesheetData1['is_cleared'], 0);
        
        // update Timesheets
        $newValues = array('description' => 'argl', 'is_cleared' => 1);
        $filterData = array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($timesheetData1['id'], $timesheetData2['id']))
        );
        $result = $this->_json->updateMultipleTimesheets($filterData, $newValues);
        
        $changed1 = $this->_json->getTimesheet($timesheetData1['id']);
        $changed2 = $this->_json->getTimesheet($timesheetData2['id']);
                
        // check
        $this->assertEquals(2, $result['count']);
        $this->assertEquals($timesheetData1['id'], $changed1['id']);
        $this->assertEquals($changed1['description'], $newValues['description']);
        $this->assertEquals($changed2['description'], $newValues['description']);
        $this->assertEquals($changed1['is_cleared'], 1);
        $this->assertEquals($changed2['is_cleared'], 1);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData1['timeaccount_id']['id']);
    }
    
    /**
     * try to get a Timesheet
     *
     */
    public function testDeleteTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // delete
        $this->_json->deleteTimesheets($timesheetData['id']);
        
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->getTimesheetsByTimeaccountId($timesheetData['timeaccount_id']['id']);
        
        // checks
        $this->assertEquals(0, count($timesheets));
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
    }
    
    
    /**
     * try to search for Timesheets
     *
     */
    public function testSearchTimesheets()
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // search & check
        $search = $this->_json->searchTimesheets($this->_getTimesheetFilter(), $this->_getPaging());
        $this->assertEquals($timesheet->description, $search['results'][0]['description']);
        $this->assertType('array', $search['results'][0]['timeaccount_id'], 'timeaccount_id is not resolved');
        $this->assertType('array', $search['results'][0]['account_id'], 'account_id is not resolved');
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals(30, $search['totalsum']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
    }

    /**
     * try to search for Timesheets with date filtering
     *
     */
    public function testSearchTimesheetsWithDateFilter()
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // search & check
        $search = $this->_json->searchTimesheets($this->_getTimesheetDateFilter(), $this->_getPaging());
        $this->assertEquals($timesheet->description, $search['results'][0]['description']);
        $this->assertType('array', $search['results'][0]['timeaccount_id'], 'timeaccount_id is not resolved');
        $this->assertType('array', $search['results'][0]['account_id'], 'account_id is not resolved');
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
    }
    
    /**
     * try to search for Timesheets (with combined is_billable + cleared)
     *
     */
    public function testSearchTimesheetsWithCombinedIsBillableAndCleared()
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // update timeaccount -> is_billable = false
        $ta = Timetracker_Controller_Timeaccount::getInstance()->get($timesheetData['timeaccount_id']['id']);
        $ta->is_billable = 0;
        Timetracker_Controller_Timeaccount::getInstance()->update($ta);
        
        // search & check
        $search = $this->_json->searchTimesheets($this->_getTimesheetFilter(), $this->_getPaging());
        $this->assertEquals(0, $search['results'][0]['is_billable_combined']);
        $this->assertEquals(0, $search['results'][0]['is_cleared_combined']);
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals(30, $search['totalsum']);
        $this->assertEquals(0, $search['totalsumbillable']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
    }

    /******* export tests *****************/
    
    /**
     * try to export Timesheets
     * - this is no real json test
     * 
     * @todo move that to separate export test?
     */
    public function testExportTimesheetsCsv()
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // export & check
        $csvExportClass = new Timetracker_Export_Csv();
        $result = $csvExportClass->generate(new Timetracker_Model_TimesheetFilter($this->_getTimesheetFilter()));
        
        $this->assertTrue(file_exists($result));
        
        $file = implode('', file($result));
        $this->assertEquals(1, preg_match("/". $timesheetData['description'] ."/", $file), 'no description'); 
        $this->assertEquals(1, preg_match("/description/", $file), 'no headline'); 
        
        // cleanup / delete file
        unlink($result);
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
    }
    
    /**
     * try to export Timesheets (as ods)
     * - this is no real json test
     * 
     */
    public function testExportTimesheetsOds()
    {
        Tinebase_Core::getPreference('Timetracker')->setValue(Timetracker_Preference::TSODSEXPORTCONFIG, 'ts_default_ods');
        $this->_exportTsOds();
    }
    
    /**
     * try to export Timeaccounts (as ods)
     * - this is no real json test
     * 
     * @todo activate headline check again
     * @todo check if user is correctly resolved
     */
    public function testExportTimeaccountsOds()
    {
        // create
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());
        
        // export & check
        $odsExportClass = Tinebase_Export::factory(new Timetracker_Model_TimeaccountFilter($this->_getTimeaccountFilter()), 'ods');
        $result = $odsExportClass->generate();
        
        $this->assertTrue(file_exists($result));
        
        $xmlBody = $odsExportClass->getDocument()->asXML();
        //echo  $xmlBody;
        //$this->assertEquals(1, preg_match("/0.5/", $xmlBody), 'no duration'); 
        $this->assertEquals(1, preg_match("/". $timeaccountData['description'] ."/", $xmlBody), 'no description'); 
        //$this->assertEquals(1, preg_match("/". 'Description' ."/", $xmlBody), 'no headline'); 
        
        // cleanup / delete file
        unlink($result);
        $this->_json->deleteTimeaccounts($timeaccountData['id']);
    }

    
    /******* persistent filter tests *****************/
    
    /**
     * try to save and search persistent filter
     * 
     * @todo move this test to tinebase json tests?
     */
    public function testSavePersistentTimesheetFilter()
    {
        $persistentFiltersJson = new Tinebase_Frontend_Json_PersistentFilter();
        
        // create
        $filterName = Tinebase_Record_Abstract::generateUID();
        $persistentFiltersJson->savePersistentFilter(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationById('Timetracker')->getId(),
            'filters'           => $this->_getTimesheetFilter(), 
            'name'              => $filterName, 
            'model'             => 'Timetracker_Model_TimesheetFilter'
        ));
        
        // get
        $persistentFilters = $persistentFiltersJson->searchPersistentFilter($this->_getPersistentFilterFilter($filterName), NULL);
        //print_r($persistentFilters);
        
        //check
        $this->assertEquals(1, $persistentFilters['totalcount']); 
        $this->assertEquals($filterName, $persistentFilters['results'][0]['name']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $persistentFilters['results'][0]['created_by']);
        $this->assertEquals($persistentFilters['results'][0]['filters'], $this->_getTimesheetFilter());

        // cleanup / delete file
        $persistentFiltersJson->deletePersistentFilters($persistentFilters['results'][0]['id']);
    }

    /**
     * try to save/update and search persistent filter
     * 
     * @todo move this test to tinebase json tests?
     */
    public function testUpdatePersistentTimesheetFilter()
    {
        $persistentFiltersJson = new Tinebase_Frontend_Json_PersistentFilter();
        $tsFilter = $this->_getTimesheetFilter();
        
        // create
        $filterName = Tinebase_Record_Abstract::generateUID();
        $persistentFiltersJson->savePersistentFilter(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationById('Timetracker')->getId(),
            'filters'           => $tsFilter, 
            'name'              => $filterName, 
            'model'             => 'Timetracker_Model_TimesheetFilter'
        ));

        $persistentFilters = $persistentFiltersJson->searchPersistentFilter($this->_getPersistentFilterFilter($filterName), NULL);
        
        // update
        $updatedFilter = $persistentFilters['results'][0];
        $updatedFilter[0]['value'] = 'blubb';
        $persistentFiltersJson->savePersistentFilter($updatedFilter);
        
        // get
        $persistentFiltersUpdated = $persistentFiltersJson->searchPersistentFilter($this->_getPersistentFilterFilter($filterName), NULL);
        //print_r($persistentFiltersUpdated);
        
        //check
        $this->assertEquals(1, $persistentFiltersUpdated['totalcount']); 
        $this->assertEquals($filterName, $persistentFiltersUpdated['results'][0]['name']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $persistentFiltersUpdated['results'][0]['last_modified_by']);
        //$this->assertEquals($persistentFiltersUpdated['results'][0]['filters'], $updatedFilter);
        $this->assertEquals($persistentFilters['results'][0]['id'], $persistentFiltersUpdated['results'][0]['id']);

        // cleanup / delete file
        $persistentFiltersJson->deletePersistentFilters($persistentFiltersUpdated['results'][0]['id']);
    }

    /**
     * try to search timesheets with saved persistent filter id
     * 
     * @todo move this test to tinebase json tests?
     */
    public function testSearchTimesheetsWithPersistentFilter()
    {
        $persistentFiltersJson = new Tinebase_Frontend_Json_PersistentFilter();
        $tsFilter = $this->_getTimesheetFilter();
        
        // create
        $filterName = Tinebase_Record_Abstract::generateUID();
        $persistentFiltersJson->savePersistentFilter(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationById('Timetracker')->getId(),
            'filters'           => $tsFilter, 
            'name'              => $filterName, 
            'model'             => 'Timetracker_Model_TimesheetFilter'
        ));
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        $this->_toDeleteIds['ta'][] = $timesheetData['timeaccount_id']['id'];
        
        // search persistent filter
        $persistentFilters = $persistentFiltersJson->searchPersistentFilter($this->_getPersistentFilterFilter($filterName), NULL);
        //check
        $search = $this->_json->searchTimesheets($persistentFilters['results'][0]['id'], $this->_getPaging());
        $this->assertEquals($timesheet->description, $search['results'][0]['description']);
        $this->assertType('array', $search['results'][0]['timeaccount_id'], 'timeaccount_id is not resolved');
        $this->assertType('array', $search['results'][0]['account_id'], 'account_id is not resolved');
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals(30, $search['totalsum']);
        $this->assertEquals($tsFilter, $search['filter'], 'filters do not match');
        
        // cleanup / delete file
        $persistentFiltersJson->deletePersistentFilters($persistentFilters['results'][0]['id']);
    }
    
    /************ protected helper funcs *************/
    
    /**
     * get Timesheet
     *
     * @return Timetracker_Model_Timeaccount
     */
    protected function _getTimeaccount()
    {
        return new Timetracker_Model_Timeaccount(array(
            'title'         => Tinebase_Record_Abstract::generateUID(),
            'description'   => 'blabla',
        ), TRUE);
    }
    
    /**
     * get grants
     *
     * @return array
     */
    protected function _getGrants()
    {
        return array(
            array(
                'account_id' => 0,
                'account_type' => 'anyone',
                'book_own' => TRUE,
                'view_all' => TRUE,
                'book_all' => TRUE,
                'manage_billable' => TRUE,
                'manage_all' => TRUE
            )
        );        
    }
    
    /**
     * get Timesheet (create timeaccount as well)
     *
     * @return Timetracker_Model_Timesheet
     */
    protected function _getTimesheet($_taId = NULL)
    {
        if ($_taId === NULL) {
            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($this->_getTimeaccount());
            $taId = $timeaccount->getId();
        } else {
            $taId = $_taId;
        }
        
        return new Timetracker_Model_Timesheet(array(
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'timeaccount_id'    => $taId,
            'description'       => 'blabla',
            'start_date'        => Zend_Date::now()->toString('yyyy-MM-dd'),
            'duration'          => 30,
        ), TRUE);
    }

    /**
     * get custom field record
     *
     * @return Tinebase_Model_CustomField_Config
     */
    protected function _getCustomField()
    {
        $record = new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'label'             => Tinebase_Record_Abstract::generateUID(),        
            'model'             => 'Timetracker_Model_Timesheet',
            'type'              => Tinebase_Record_Abstract::generateUID(),
            'length'            => 10,        
        ));
        
        return Tinebase_CustomField::getInstance()->addCustomField($record);
    }
    
    /**
     * get paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'creation_time',
            'dir' => 'ASC',
        );
    }

    /**
     * get Timeaccount filter
     *
     * @return array
     */
    protected function _getTimeaccountFilter()
    {
        return array(
            array(
                'field' => 'description', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),     
            array(
                'field' => 'containerType', 
                'operator' => 'equals', 
                'value' => Tinebase_Model_Container::TYPE_SHARED
            ),     
        );        
    }
    
    
    /**
     * get Timesheet filter
     *
     * @return array
     */
    protected function _getTimesheetFilter()
    {
        return array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),
        );        
    }

    /**
     * get customfield value filter
     *
     * @param string $_cfid
     * @return array
     */
    protected function _getCfValueFilter($_cfid)
    {
        return array(
            array(
                'field' => 'customfield_id', 
                'operator' => 'equals', 
                'value' => $_cfid
            ),
            array(
                'field' => 'value', 
                'operator' => 'group', 
                'value' => ''
            ),
        );        
    }
    
    /**
     * get Timesheet filter with custom field
     *
     * @return array
     */
    protected function _getTimesheetFilterWithCustomField($_cfId, $_value)
    {
        return array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),
            array(
                'field' => 'customfield', 
                'operator' => 'equals', 
                'value' => array('cfId' => $_cfId, 'value' => $_value)
            ),
        );        
    }
    
    /**
     * get persistent filter filter
     *
     * @param string $_name
     * @return array
     */
    protected function _getPersistentFilterFilter($_name)
    {
        return array(
            array(
                'field'     => 'name', 
                'operator'  => 'equals', 
                'value'     => $_name
            ),
        );        
    }
    
    /**
     * get Timesheet filter with date
     *
     * @return array
     */
    protected function _getTimesheetDateFilter()
    {
        return array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),
            array(
                'field' => 'start_date', 
                'operator' => 'within', 
                'value' => 'weekThis'
            ),
            array(
                'field' => 'start_date', 
                'operator' => 'after', 
                'value' => '2008-12-12'
            ),
        );
    }
    
    /**
     * do ods export
     * 
     * @return void
     * 
     * @todo add table check again
     * @todo check custom fields
     */
    protected function _exportTsOds()
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        $this->_toDeleteIds['ta'][] = $timesheetData['timeaccount_id']['id'];
        
        // export & check
        $odsExportClass = Tinebase_Export::factory(new Timetracker_Model_TimesheetFilter($this->_getTimesheetFilter()), 'ods');
        $result = $odsExportClass->generate();
        
        $this->assertTrue(file_exists($result));
        
        $xmlBody = $odsExportClass->getDocument()->asXML();    
        //echo  $xmlBody;
        $this->assertEquals(1, preg_match("/0.5/", $xmlBody), 'no duration'); 
        $this->assertEquals(1, preg_match("/". $timesheetData['description'] ."/", $xmlBody), 'no description'); 
        $this->assertEquals(1, preg_match("/Description/", $xmlBody), 'no headline'); 
        //$this->assertEquals(2, $odsExportClass->getDocument()->getBody()->count(), 'table count mismatch');
        
        // cleanup / delete file
        unlink($result);
    }
    
    protected function _addTsWithCf($_customField1, $_cf1Value)
    {
        // create custom fields
        $customField2 = $this->_getCustomField();
        
        // create timesheet and add custom fields
        $timesheetArray = $this->_getTimesheet()->toArray();
        $timesheetArray[$_customField1->name] = $_cf1Value;
        $timesheetArray[$customField2->name] = Tinebase_Record_Abstract::generateUID();
        
        $timesheetData = $this->_json->saveTimesheet($timesheetArray);
        
        // tearDown settings
        $this->_toDeleteIds['ta'][] = $timesheetData['timeaccount_id']['id'];
        $this->_toDeleteIds['cf'] = array($_customField1->getId(), $customField2->getId());
        
        // checks
        $this->assertGreaterThan(0, count($timesheetData['customfields']));
        $this->assertEquals($timesheetArray[$_customField1->name], $timesheetData['customfields'][$_customField1->name]);
        $this->assertEquals($timesheetArray[$customField2->name], $timesheetData['customfields'][$customField2->name]);
        
        // check if custom fields are returned with search
        $searchResult = $this->_json->searchTimesheets($this->_getTimesheetFilter(), $this->_getPaging());
        $this->assertGreaterThan(0, count($searchResult['results'][0]['customfields']));
        foreach($searchResult['results'] as $result) {
            if ($result['id'] == $timesheetData['id']) {
                $ts = $result;
            }
        }
        $this->assertTrue(isset($ts));
        $this->assertEquals($timesheetArray[$_customField1->name], $ts['customfields'][$_customField1->name]);
        $this->assertEquals($timesheetArray[$customField2->name], $ts['customfields'][$customField2->name]);
        
        // test search with custom field filter
        $searchResult = $this->_json->searchTimesheets(
            $this->_getTimesheetFilterWithCustomField($_customField1->getId(), $_cf1Value), 
            $this->_getPaging()
        );
        $this->assertGreaterThan(0, $searchResult['totalcount'], 'cf filter not working');
        
    }
}
