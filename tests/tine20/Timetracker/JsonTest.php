<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
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
     */
    protected function tearDown()
    {        
    }
    
    /**
     * try to add a Timeaccount
     *
     */
    public function testAddTimeaccount()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount(Zend_Json::encode($timeaccount->toArray()));
        
        // checks
        $this->assertEquals($timeaccount->description, $timeaccountData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timeaccountData['created_by']);
        $this->assertTrue(is_array($timeaccountData['container_id']));
        $this->assertEquals(Tinebase_Model_Container::TYPE_SHARED, $timeaccountData['container_id']['type']);
        
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
        $timeaccountData = $this->_json->saveTimeaccount(Zend_Json::encode($timeaccount->toArray()));
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
        $timeaccountData = $this->_json->saveTimeaccount(Zend_Json::encode($timeaccount->toArray()));
        
        // update Timeaccount
        $timeaccountData['description'] = "blubbblubb";
        $timeaccountUpdated = $this->_json->saveTimeaccount(Zend_Json::encode($timeaccountData));
        
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
        $timeaccountData = $this->_json->saveTimeaccount(Zend_Json::encode($timeaccount->toArray()));
        
        // search & check
        $search = $this->_json->searchTimeaccounts(Zend_Json::encode($this->_getTimeaccountFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($timeaccount->description, $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timeaccountData['id']);
    }
    
    /**
     * try to add a Timesheet
     *
     */
    public function testAddTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
        
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
     * try to add a Timesheet
     *
     */
    public function testAddTimesheetWithCustomFields()
    {
        // create custom fields
        $customField1 = $this->_getCustomField();
        $customField2 = $this->_getCustomField();
        
        // create timesheet and add custom fields
        $timesheetArray = $this->_getTimesheet()->toArray();
        $timesheetArray[$customField1->name] = Tinebase_Record_Abstract::generateUID();
        $timesheetArray[$customField2->name] = Tinebase_Record_Abstract::generateUID();
        
        $timesheetData = $this->_json->saveTimesheet(Zend_Json::encode($timesheetArray));
        
        // checks
        $this->assertGreaterThan(0, count($timesheetData['customfields']));
        $this->assertEquals($timesheetArray[$customField1->name], $timesheetData['customfields'][$customField1->name]);
        $this->assertEquals($timesheetArray[$customField2->name], $timesheetData['customfields'][$customField2->name]);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
        Tinebase_Config::getInstance()->deleteCustomField($customField1);
        Tinebase_Config::getInstance()->deleteCustomField($customField2);
    }
    
    /**
     * try to get a Timesheet
     *
     */
    public function testGetTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
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
        $timesheetData = $this->_json->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
        
        // update Timesheet
        $timesheetData['description'] = "blubbblubb";
        //$timesheetData['container_id'] = $timesheetData['container_id']['id'];
        $timesheetData['account_id'] = $timesheetData['account_id']['accountId'];
        $timesheetData['timeaccount_id'] = $timesheetData['timeaccount_id']['id'];
        
        $timesheetUpdated = $this->_json->saveTimesheet(Zend_Json::encode($timesheetData));
        
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
     * try to search for Timesheets
     *
     */
    public function testSearchTimesheets()
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
        
        // search & check
        $search = $this->_json->searchTimesheets(Zend_Json::encode($this->_getTimesheetFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($timesheet->description, $search['results'][0]['description']);
        $this->assertType('array', $search['results'][0]['timeaccount_id'], 'timeaccount_id is not resolved');
        $this->assertType('array', $search['results'][0]['account_id'], 'account_id is not resolved');
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
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
     * get Timesheet (create timeaccount as well)
     *
     * @return Timetracker_Model_Timesheet
     */
    protected function _getTimesheet()
    {
        $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($this->_getTimeaccount());
        
        return new Timetracker_Model_Timesheet(array(
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'timeaccount_id'    => $timeaccount->getId(),
            'description'       => 'blabla',
            'start_date'        => Zend_Date::now()->toString('YYYY-MM-dd')
        ), TRUE);
    }

    /**
     * get custom field record
     *
     * @return Tinebase_Model_CustomField
     */
    protected function _getCustomField()
    {
        $record = new Tinebase_Model_CustomField(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'label'             => Tinebase_Record_Abstract::generateUID(),        
            'model'             => 'Timetracker_Model_Timesheet',
            'type'              => Tinebase_Record_Abstract::generateUID(),
            'length'            => 10,        
        ));
        
        return Tinebase_Config::getInstance()->addCustomField($record);
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
}
