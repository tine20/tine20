<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Timetracker_Frontent_Json
 */
class Timetracker_JsonTest extends Timetracker_AbstractTest
{
    protected $_testUser = NULL;
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
    }
    
    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // switch back to admin user
        if ($this->_testUser) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_testUser);
        }
        
        parent::tearDown();
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
     */
    public function testSearchTimeaccounts()
    {
        // create
        $timeaccount = $this->_getTimeaccount();
        $timeaccount->is_open = 0;
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());

        // search & check
        $timeaccountFilter = $this->_getTimeaccountFilter();
        $search = $this->_json->searchTimeaccounts($timeaccountFilter, $this->_getPaging());
        $this->assertEquals(0, $search['totalcount'], 'is_open filter not working');

        $search = $this->_json->searchTimeaccounts($this->_getTimeaccountFilter(TRUE), $this->_getPaging());
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals($timeaccount->description, $search['results'][0]['description']);
    }

    /**
     * try to get a Timeaccount with a TA filter
     * 
     * @see 0007946: error when searching for single timeaccount
     */
    public function testSearchTimeaccountsWithTAFilter()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());
        
        $searchFilter = '[{
            "field": "id",
            "id": "ext-record-869",
            "label": null,
            "operator": "equals",
            "value": "' . $timeaccountData['id'] . '"
        }]';
        $paging = '"paging": {
            "sort": "number",
            "dir": "DESC",
            "start": 0,
            "limit": 50
        }';
        $searchResult = $this->_json->searchTimeaccounts(Zend_Json::decode($searchFilter), Zend_Json::decode($paging));
        $this->assertEquals(1, $searchResult['totalcount']);
        $this->assertEquals(1, count($searchResult['filter']), 'did not get ta filter: ' . print_r($searchResult, TRUE));
        $this->assertEquals($timeaccountData['id'], $searchResult['filter'][0]['value']['id']);
    }
    
    /**
     * try to add a Timeaccount with grants
     */
    public function testAddTimeaccountWithGrants()
    {
        $grants = $this->_getGrants();
        $timeaccountData = $this->_saveTimeaccountWithGrants($grants);

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
     * save TA with grants
     * 
     * @return array
     */
    protected function _saveTimeaccountWithGrants($grants = NULL)
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $timeaccount->toArray();
        $timeaccountData['grants'] = ($grants !== NULL) ? $grants : $this->_getGrants();
        return $this->_json->saveTimeaccount($timeaccountData);
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
        $this->assertEquals(Tinebase_DateTime::now()->toString('Y-m-d'),  $timesheetData['start_date']);

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
        $value1 = 'abcd';
        $value2 = 'efgh';
        $cf = $this->_getCustomField();
        
        // create two timesheets with customfields
        $this->_addTsWithCf($cf, $value1);
        $this->_addTsWithCf($cf, $value2);

        // search custom field values and check totalcount
        $tinebaseJson = new Tinebase_Frontend_Json();
        $cfValues = $tinebaseJson->searchCustomFieldValues(Zend_Json::encode($this->_getCfValueFilter($cf->getId())), '');
        $this->assertEquals(2, $cfValues['totalcount'], 'wrong totalcount');

        $cfValueArray = array($cfValues['results'][0]['value'], $cfValues['results'][1]['value']);
        $this->assertTrue(in_array($value1, $cfValueArray));
        $this->assertTrue(in_array($value2, $cfValueArray));
    }

    /**
     * search Timesheet with empty custom fields
     */
    public function testSearchTimesheetWithEmptyCustomField()
    {
        $cf = $this->_getCustomField();

        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());

        $search = $this->_json->searchTimesheets($this->_getTimesheetFilter(array(
            'field'     => 'customfield',
            'operator'  => 'equals',
            'value'     => array(
                'cfId'  => $cf->getId(),
                'value' => '',
            )
        )), $this->_getPaging());
        $this->assertEquals(1, $search['totalcount']);
    }

    /**
     * try to add a Timesheet with custom fields (check grants)
     */
    public function testAddTimesheetWithCustomFieldGrants()
    {
        $value = 'test';
        $cf = $this->_getCustomField();

        $timesheetArray = $this->_getTimesheet()->toArray();
        $timesheetArray[$cf->name] = $value;
        $ts = $this->_json->saveTimesheet($timesheetArray);

        // test with default grants
        $this->assertTrue((isset($ts['customfields'][$cf->name]) || array_key_exists($cf->name, $ts['customfields'])), 'customfield should be readable');
        $this->assertEquals($value, $ts['customfields'][$cf->name]);

        // remove all grants
        Tinebase_CustomField::getInstance()->setGrants($cf, array());
        $ts = $this->_json->getTimesheet($ts['id']);

        $this->assertTrue(! (isset($ts['customfields']) || array_key_exists('customfields', $ts)), 'customfields should not be readable');
        $ts = $this->_updateCfOfTs($ts, $cf->name, 'try to update');

        // only read allowed
        Tinebase_CustomField::getInstance()->setGrants($cf, array(Tinebase_Model_CustomField_Grant::GRANT_READ));
        $ts = $this->_json->getTimesheet($ts['id']);
        $this->assertTrue((isset($ts['customfields'][$cf->name]) || array_key_exists($cf->name, $ts['customfields'])), 'customfield should be readable again');
        $this->assertEquals($value, $ts['customfields'][$cf->name], 'value should not have changed');
        $ts = $this->_updateCfOfTs($ts, $cf->name, 'try to update');
        $this->assertEquals($value, $ts['customfields'][$cf->name], 'value should still not have changed');
    }

    /**
     * update timesheet customfields and return saved ts
     *
     * @param array $_ts
     * @param string $_cfName
     * @param string $_cfValue
     * @return array
     */
    protected function _updateCfOfTs($_ts, $_cfName, $_cfValue)
    {
        $_ts[$_cfName] = $_cfValue;
        $_ts['timeaccount_id'] = $_ts['timeaccount_id']['id'];
        $_ts['account_id'] = $_ts['account_id']['accountId'];
        unset($_ts['customfields']);
        $ts = $this->_json->saveTimesheet($_ts);

        return $ts;
    }


    /**
     * this test is for Tinebase_Frontend_Json updateMultipleRecords with timesheet data in the timetracker app
     */
    public function testUpdateMultipleRecords()
    {
        $durations = array(75,90,105);
        $timeAccount = $this->_getTimeaccount(array('description' => 'blablub'),true);
        $lr = $this->_getLastCreatedRecord();

        $taId = $lr['id'];

        // create customfield
        $cf = $this->_getCustomField()->toArray();

        $changes = array( array('name' => 'duration',                   'value' => '111'),
                          array('name' => 'description',                'value' => 'PHPUNIT_multipleUpdate'),
                          array('name' => 'customfield_' . $cf['name'], 'value' => 'PHPUNIT_multipleUpdate' )
        );

        foreach ($durations as $duration) {
            $timeSheet = $this->_getTimesheet(array('timeaccount_id' => $taId, 'duration' => $duration),true);
            $lr = $this->_getLastCreatedRecord();
            $timesheetIds[] = $lr['id'];
        }

        $filter = array( array('field' => 'id',         'operator' => 'in',     'value' => $timesheetIds),
                         array('field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId())
        );

        $json = new Tinebase_Frontend_Json();

        $result = $json->updateMultipleRecords('Timetracker', 'Timesheet', $changes, $filter);

        // look if all 3 contacts are updated
        $this->assertEquals(3, $result['totalcount'],'Could not update the correct number of records');

        // check if default field duration value was found
        $sFilter = array( array('field' => 'duration',   'operator' => 'equals', 'value' => '111'),
                          array('field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId())
        );
        $searchResult = $this->_json->searchTimesheets($sFilter,$this->_getPaging());

        // look if all 3 contacts are found again by default field, and check if default field got properly updated
        $this->assertEquals(3, $searchResult['totalcount'],'Could not find the correct number of records by duration');

        $record = array_pop($searchResult['results']);

        // check if customfieldvalue was updated properly
        $this->assertEquals($record['customfields'][$cf['name']],'PHPUNIT_multipleUpdate','Customfield was not updated as expected');

        // check if other default field value was updated properly
        $this->assertEquals($record['duration'],'111','DefaultField "duration" was not updated as expected');
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

        $this->assertEquals('array', gettype($search['results'][0]['timeaccount_id']), 'timeaccount_id is not resolved');
        $this->assertEquals('array', gettype($search['results'][0]['account_id']), 'account_id is not resolved');

        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals(1, count($search['results']));
        $this->assertEquals(30, $search['totalsum'], 'totalsum mismatch');
    }

    /**
     * try to search for Timesheets with date filtering (using 'weekThis' filter)
     *
     */
    public function testSearchTimesheetsWithDateFilterWeekThis()
    {
        $this->_dateFilterTest();
    }

    /**
     * try to search for Timesheets with date filtering (using inweek operator)
     *
     */
    public function testSearchTimesheetsWithDateFilterInWeek()
    {
        $this->_dateFilterTest('inweek');
    }

    /**
     * try to search for Timesheets with date filtering (using monthLast operator)
     */
    public function testSearchTimesheetsWithDateMonthLast()
    {
        $today = Tinebase_DateTime::now();
        $lastMonth = $today->setDate($today->get('Y'), $today->get('m') - 1, 1);
        $this->_createTsAndSearch($lastMonth, 'monthLast');
    }

    /**
     * date filter test helper
     *
     * @param string $_type weekThis|inweek|monthLast
     */
    protected function _dateFilterTest($_type = 'weekThis')
    {
        $oldLocale = Tinebase_Core::getLocale();
        Tinebase_Core::set(Tinebase_Core::LOCALE, new Zend_Locale('en_US'));

        // date is last/this sunday (1. day of week in the US)
        $today = Tinebase_DateTime::now();
        $dayOfWeek = $today->get('w');
        $lastSunday = $today->subDay($dayOfWeek);

        $this->_createTsAndSearch($lastSunday, $_type);

        // change locale to de_DE -> timesheet should no longer be found because monday is the first day of the week
        Tinebase_Core::set(Tinebase_Core::LOCALE, new Zend_Locale('de_DE'));
        $search = $this->_json->searchTimesheets($this->_getTimesheetDateFilter($_type), $this->_getPaging());
        // if today is sunday -> ts should be found in german locale!
        $this->assertEquals(($dayOfWeek == 0) ? 1 : 0, $search['totalcount'], 'filter not working in german locale');

        Tinebase_Core::set(Tinebase_Core::LOCALE, $oldLocale);
    }

    /**
     * create timesheet and search with filter
     *
     * @param Tinebase_DateTime $_startDate
     * @param string $_filterType
     */
    protected function _createTsAndSearch($_startDate, $_filterType)
    {
        //$timesheet = $this->_getTimesheet(NULL, $_startDate);
        $timesheet = $this->_getTimesheet(array('timeaccount_id' => null, 'start_date' => $_startDate));
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());

        $result = $this->_json->searchTimesheets($this->_getTimesheetDateFilter($_filterType), $this->_getPaging());

        $this->assertEquals(1, $result['totalcount'], 'timesheet not found with ' . $_filterType . ' filter');
        $this->assertEquals($timesheet->description, $result['results'][0]['description']);
        $this->assertEquals('array', gettype($result['results'][0]['timeaccount_id']), 'timeaccount_id is not resolved');
        $this->assertEquals('array', gettype($result['results'][0]['account_id']), 'account_id is not resolved');
    }

    /**
     * try to search for Timesheets (with combined is_billable + cleared)
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
        $search = $this->_json->searchTimesheets($this->_getTimesheetFilter(array(
            'field' => 'is_cleared_combined',
            'operator' => 'equals',
            'value' => FALSE
        )), $this->_getPaging('is_billable_combined'));
        
        $this->assertGreaterThanOrEqual(1, count($search['results']));
        $this->assertEquals(0, $search['results'][0]['is_billable_combined'], 'is_billable_combined mismatch');
        $this->assertEquals(0, $search['results'][0]['is_cleared_combined'], 'is_cleared_combined mismatch');
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals(30, $search['totalsum']);
        $this->assertEquals(0, $search['totalsumbillable']);

        // search again with is_billable filter
        $search = $this->_json->searchTimesheets($this->_getTimesheetFilter(array(
            'field' => 'is_billable_combined',
            'operator' => 'equals',
            'value' => FALSE,
        )), $this->_getPaging('is_billable_combined'));
        $this->assertEquals(0, $search['results'][0]['is_billable_combined'], 'is_billable_combined mismatch');

        // search again with is_billable filter and no sorting
        $search = $this->_json->searchTimesheets($this->_getTimesheetFilter(array(
            'field' => 'is_billable_combined',
            'operator' => 'equals',
            'value' => FALSE,
        )), $this->_getPaging());
        $this->assertEquals(0, $search['results'][0]['is_billable_combined'], 'is_billable_combined mismatch');
    }
    
    /**
     * testSearchTimesheetsSumBillable
     */
    public function testSearchTimesheetsSumBillable()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        $timesheet = $this->_getTimesheet();
        $timesheet->is_billable = false;
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // search & check
        $search = $search = $this->_json->searchTimesheets($this->_getTimesheetFilter(), $this->_getPaging());
        $this->assertEquals(60, $search['totalsum']);
        $this->assertEquals(30, $search['totalsumbillable']);
    }
    
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

        // search persistent filter
        $persistentFilters = $persistentFiltersJson->searchPersistentFilter($this->_getPersistentFilterFilter($filterName), NULL);
        //check
        $search = $this->_json->searchTimesheets($persistentFilters['results'][0]['id'], $this->_getPaging());
        $this->assertEquals($timesheet->description, $search['results'][0]['description']);
        $this->assertEquals('array', gettype($search['results'][0]['timeaccount_id']), 'timeaccount_id is not resolved');
        $this->assertEquals('array', gettype($search['results'][0]['account_id']), 'account_id is not resolved');
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals(30, $search['totalsum']);
        $this->assertEquals($tsFilter, $search['filter'], 'filters do not match');

        // cleanup / delete file
        $persistentFiltersJson->deletePersistentFilters($persistentFilters['results'][0]['id']);
    }

    /**
     * create timesheet and search with explicite foreign filter
     */
    public function testSearchWithExpliciteForeignIdFilter()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());

        $filter = array(
            array('field' => 'timeaccount_id', 'operator' => 'AND', 'value' => array(
                array('field' => 'id', 'operator' => 'equals', 'value' => $timesheetData['timeaccount_id']['id'])
            ))
        );

        $result = $this->_json->searchTimesheets($filter, $this->_getPaging());

        $this->assertEquals(1, $result['totalcount'], 'timesheet not found with ExpliciteForeignIdFilter filter');
    }

    /**
     * create timesheet and search with explicite foreign left hand filter
     */
    public function testSearchWithExpliciteForeignIdLeftFilter()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());

        $anotherTimesheet = $this->_getTimesheet();
        $anotherTimesheetData = $this->_json->saveTimesheet($anotherTimesheet->toArray());

        $filter = array(
            array('field' => 'timeaccount_id', 'operator' => 'AND', 'value' => array(
                array('field' => ':id', 'operator' => 'equals', 'value' => $timesheetData['timeaccount_id']['id'])
            ))
        );

        $result = $this->_json->searchTimesheets($filter, $this->_getPaging());

        $this->assertEquals(1, $result['totalcount'], 'timesheet not found with ExpliciteForeignIdFilter filter');
        $this->assertEquals(':id', $result['filter'][0]['value'][0]['field']);
        $this->assertTrue(is_array($result['filter'][0]['value'][0]['value']), 'timeaccount should be resolved');
    }

    /**
     * try to search timesheets with or filter
     */
    public function testSearchTimesheetsWithOrFilter()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());

        $filterData = $this->_getTSFilterDataByUser(Tinebase_Core::getUser()->getId());
        $search = $this->_json->searchTimesheets($filterData, array());
        $this->assertEquals(1, $search['totalcount']);
    }
    
    /**
     * get ts filter array by user
     * 
     * @param string $userId
     * @return array
     */
    protected function _getTSFilterDataByUser($userId)
    {
        return $filterData = Zend_Json::decode('[{"condition":"OR","filters":[{"condition":"AND","filters":'
            . '[{"field":"start_date","operator":"within","value":"weekThis","id":"ext-record-1"},'
            . '{"field":"account_id","operator":"equals","value":"' . $userId . '","id":"ext-record-2"}]'
            . ',"id":"ext-comp-1076","label":"Stundenzettel"}]}]'
        );
    }
    
    /**
    * try to search timesheets with or filter + acl filtering (should find only 1 ts)
    * 
    * @see 0005684: fix timesheet acl filtering
    */
    public function testSearchTimesheetsWithOrAndAclFilter()
    {
        // add another ts that does not match the filter
        $timesheet = $this->_getTimesheet(array(
            'start_date' => Tinebase_DateTime::now()->subWeek(2)->toString('Y-m-d')
        ));
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        Timetracker_ControllerTest::removeManageAllRight();
        $this->testSearchTimesheetsWithOrFilter();
    }

    /**
    * try to search timesheets of another user with account filter + acl filtering (should find 1 ts)
    * 
    * @see 0006244: user filter does not work
    */
    public function testSearchTimesheetsOfAnotherUser()
    {
        $taData = $this->_saveTimeaccountWithGrants();
        $scleverId = Tinebase_User::getInstance()->getFullUserByLoginName('sclever')->getId();
        
        // add ts for sclever
        $timesheet = $this->_getTimesheet(array(
            'account_id'     => $scleverId,
            'timeaccount_id' => $taData['id'],
        ));
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
    
        Timetracker_ControllerTest::removeManageAllRight();
        
        $filterData = $this->_getTSFilterDataByUser($scleverId);
        $search = $this->_json->searchTimesheets($filterData, array());
        
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals($scleverId, $search['results'][0]['account_id']['accountId']);
    }
    
    /**
     * testUpdateMultipleTimesheets
     * 
     * @group longrunning
     * 
     * @see 0005878: multi update timeout and strange behaviour (server)
     */
    public function testUpdateMultipleTimesheets()
    {
        // create 100+ timesheets
        $first = $this->_getTimesheet(array(), TRUE);
        for ($i = 0; $i < 122; $i++) {
            $this->_getTimesheet(array(
                'timeaccount_id' => $first->timeaccount_id
            ), TRUE);
        }
        
        // update multi with filter
        $filterArray = $this->_getTimesheetDateFilter();
        $filterArray[] = array(
            'field'     => 'is_cleared_combined',
            'operator'  => 'equals',
            'value'     => 0
        );
        $tinebaseJson = new Tinebase_Frontend_Json();
        $result = $tinebaseJson->updateMultipleRecords('Timetracker', 'Timesheet', array(array('name' => 'is_cleared', 'value' => 1)), $filterArray);
        
        // check if all got updated
        $this->assertEquals($result['totalcount'], 123);
        $this->assertEquals($result['failcount'], 0);
        $this->assertEquals(1, $result['results'][0]['is_cleared'], print_r($result['results'][0], TRUE));
        $this->assertEquals(1, $result['results'][122]['is_cleared'], print_r($result['results'][122], TRUE));
    }
    
    /**
     * test if relation record gets deleted on both sides on deleting the relation on one side
     */
    public function testDeleteTimeaccountWitContractRelation()
    {
        $taContainer = Tinebase_Container::getInstance()->getDefaultContainer('Timetracker_Model_Timeaccount');
        $cContainer  = Tinebase_Container::getInstance()->getDefaultContainer('Sales_Model_Contract');
        $ta = new Timetracker_Model_Timeaccount(array('number' => 83209, 'title' => 'unitttest', 'container_id' => $taContainer->getId()));
        
        $contract = new Sales_Model_Contract(array('number' => 83209, 'title' => 'unittest', 'container_id' => $cContainer->getId()));
        $contract = Sales_Controller_Contract::getInstance()->create($contract);
        $ta = Timetracker_Controller_Timeaccount::getInstance()->create($ta);
        
        $r = new Tinebase_Model_Relation(array(
            'own_model' => 'Timetracker_Model_Timeaccount',
            'own_backend' => 'Sql',
            'own_degree' => 'sibling',
            'own_id' => $ta->getId(),
            'remark' => 'PHP UNITTEST',
            'related_model' => 'Sales_Model_Contract',
            'related_backend' => 'Sql',
            'related_id' => $contract->getId(),
            'type' => 'CONTRACT'
        ));
        
        $ta->relations = array($r);
        
        $ta = Timetracker_Controller_Timeaccount::getInstance()->update($ta);
        
        $feTa = new Timetracker_Frontend_Json();
        $feCo = new Sales_Frontend_Json();
        
        $jsonTa = $feTa->getTimeaccount($ta->getId());
        $jsonCo = $feCo->getContract($contract->getId());
        
        $this->assertEquals(1, count($jsonTa['relations']));
        $this->assertEquals(1, count($jsonCo['relations']));
        
        $feTa->deleteTimeaccounts(array($ta->getId()));
        
        $jsonCo = $feCo->getContract($contract->getId());
        $this->assertEquals(0, count($jsonCo['relations']));
    }
    
    /**
     * this test assures that relations, the user doesn't have the right to manage, won't be resolved anyway
     */
    public function testResolvingRelations()
    {
        $ta = $this->_getTimeaccount()->toArray();
        $ta['grants'] = $this->_getGrants(TRUE);
        
        $contractController = Sales_Controller_Contract::getInstance();
        $contactController  = Addressbook_Controller_Contact::getInstance();
        $taController       = Timetracker_Controller_Timeaccount::getInstance();
        
        // create timeaccount
        $ta = $this->_json->saveTimeaccount($ta);
        
        $contact  = $contactController->create(new Addressbook_Model_Contact(array('n_given' => 'Test', 'n_family' => 'Unit')));
        $contract = $contractController->create(new Sales_Model_Contract(array('number' => '123', 'title' => 'UnitTest')));
        
        Tinebase_Relations::getInstance()->setRelations('Timetracker_Model_Timeaccount', 'Sql', $ta['id'], array(
            array('related_backend' => 'Sql', 'type' => 'RESPONSIBLE', 'related_model' => 'Addressbook_Model_Contact', 'related_id' => $contact->getId(), 'own_degree' => 'sibling'),
            array('related_backend' => 'Sql', 'type' => 'TIME_ACCOUNT', 'related_model' => 'Sales_Model_Contract', 'related_id' => $contract->getId(), 'own_degree' => 'sibling'),
        ));
        
        // add 2 relations
        $ta = $this->_json->getTimeaccount($ta['id']);
        $this->assertEquals(2, count($ta['relations']));
        
        // fetch user group
        $group   = Tinebase_Group::getInstance()->getGroupByName('Users');
        $groupId = $group->getId();
        
        // create new user
        $user = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'testuser',
            'accountPrimaryGroup'   => $groupId,
            'accountDisplayName'    => 'Test User',
            'accountLastName'       => 'User',
            'accountFirstName'      => 'Test',
            'accountFullName'       => 'Test User',
            'accountEmailAddress'   => 'unittestx8@tine20.org',
        ));
        
        $user = Admin_Controller_User::getInstance()->create($user, 'pw', 'pw');
        
        // add tt-ta admin right to user role to allow user to update (manage) timeaccounts
        // user has no right to see sales contracts
        $fe = new Admin_Frontend_Json();
        $userRoles = $fe->getRoles('user', array(), array(), 0, 1);
        $userRole = $fe->getRole($userRoles['results'][0]['id']);
        
        $roleRights = $fe->getRoleRights($userRole['id']);
        $roleMembers = $fe->getRoleMembers($userRole['id']);
        $roleMembers['results'][] = array('name' => 'testuser', 'type' => 'user', 'id' => $user->accountId);
        
        $app = Tinebase_Application::getInstance()->getApplicationByName('Timetracker');
        
        $roleRights['results'][] = array('application_id' => $app->getId(), 'right' => Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS);
        $roleRights['results'][] = array('application_id' => $app->getId(), 'right' => Tinebase_Acl_Rights::ADMIN);
        $fe->saveRole($userRole, $roleMembers['results'], $roleRights['results']);
        
        // switch to other user
        $this->_testUser = Tinebase_Core::getUser();
        Tinebase_Core::set(Tinebase_Core::USER, $user);
        
        // get sure the user doesn't get relations not having the right for
        $ta = $this->_json->getTimeaccount($ta['id']);
        $this->assertEquals(1, count($ta['relations']), 'user should only get the related contact');
        
        // save timeaccount with reduced relations
        $ta = $this->_json->saveTimeaccount($ta);
        
        // switch user back
        Tinebase_Core::set(Tinebase_Core::USER, $this->_testUser);
        
        // get sure all relations will be returned
        $ta = $this->_json->getTimeaccount($ta['id']);
        $this->assertEquals(2, count($ta['relations']));
    }
}
