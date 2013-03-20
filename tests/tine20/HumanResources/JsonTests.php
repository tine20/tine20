<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class HumanResources_JsonTests extends HumanResources_TestCase
{
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_json = new HumanResources_Frontend_Json();
    }
    
    /**
     * Creates an employee with contracts and contact, account etc.
     * tests auto end_date of old contract
     */
    public function testEmployee()
    {
        $e = $this->_getEmployee();
        
        $date = new Tinebase_DateTime();
        $date->subMonth(5);
        
        $firstDate = substr($date->toString(), 0, 10);
        
        $costCenter1 = $this->_getCostCenter($date);
        
        $e->contracts = array($this->_getContract()->toArray());
        $e->costcenters = array($costCenter1->toArray());
        
        $savedEmployee = $this->_json->saveEmployee($e->toArray());

        $this->assertArrayHasKey('account_id', $savedEmployee);
        $this->assertTrue(is_array($savedEmployee['account_id']));
        
        $this->assertArrayHasKey('contracts', $savedEmployee);
        $this->assertArrayHasKey('costcenters', $savedEmployee);
        
        $this->assertEquals($e->n_fn, $savedEmployee['n_fn']);
        
        $this->assertEquals(1, count($savedEmployee['contracts']));
        $this->assertEquals(1, count($savedEmployee['costcenters']));

        
        $date->addMonth(2);
        $costCenter2 = $this->_getCostCenter($date);
        
        $newContract = $this->_getContract();
        $newContract->start_date->addMonth(5);
        
        $savedEmployee['contracts'][]   = $newContract->toArray();
        $savedEmployee['costcenters'][] = $costCenter2->toArray();
        
        $savedEmployee = $this->_json->saveEmployee($savedEmployee);
        
        $this->assertEquals(2, count($savedEmployee['contracts']),   'There should be 2 Contracts');
        $this->assertEquals(2, count($savedEmployee['costcenters']), 'There should be 2 CostCenters');
        
        $this->assertEquals(null, $savedEmployee['contracts'][1]['end_date'], 'The end_date should have a null value.');
        
        $this->assertEquals($firstDate, substr($savedEmployee['costcenters'][0]['start_date'], 0, 10));
        
        $date1 = new Tinebase_DateTime($savedEmployee['contracts'][0]['end_date']);
        $date2 = new Tinebase_DateTime($savedEmployee['contracts'][1]['start_date']);

        $this->assertEquals($date1->addDay(1)->toString(), $date2->toString());

        $freeTimes = $this->_json->getFeastAndFreeDays($savedEmployee['id']);
        
        $this->assertEquals($savedEmployee['id'], $freeTimes['results']['contracts'][0]['employee_id']);
    }

    /**
     * Tests the duplicate check
     */
    public function testDuplicateException()
    {
        $e = $this->_getEmployee();
        $e->contracts = array($this->_getContract()->toArray());
        $savedEmployee = $this->_json->saveEmployee($e->toArray());

        $exception = new Tinebase_Exception();

        try {
            $e = $this->_getEmployee();
            $e->contracts = array($this->_getContract()->toArray());
            $savedEmployee = $this->_json->saveEmployee($e->toArray());
        } catch (Tinebase_Exception_Duplicate $exception) {
        }

        $this->assertEquals($exception->getCode(), 629);
    }

    /**
     * Tests if multiple records get resolved properly
     *
     * #6600: generic foreign record resolving method
     * https://forge.tine20.org/mantisbt/view.php?id=6600
     */
    public function testResolveMultiple()
    {
        $e = $this->_getEmployee('rwright');
        $e->contracts = array($this->_getContract()->toArray());
        $savedEmployee = $this->_json->saveEmployee($e->toArray());

        
        $r = $this->_json->searchEmployees(
            array(array('field' => 'id', 'operator' => 'equals', 'value' => $savedEmployee['id']))
        , NULL);

        $this->assertEquals($r['results'][0]['contracts'][0]['employee_id'], $savedEmployee['id']);
        
        $r = $this->_json->getEmployee($savedEmployee['id']);
        
        $this->assertTrue(is_array($r['contracts'][0]['feast_calendar_id']));
    }
    
    /**
     * test employee creation/update with contracts
     */
    public function testContract()
    {
        $sdate = new Tinebase_DateTime();
        $sdate->subMonth(4);
        $edate = new Tinebase_DateTime();
        $edate->subMonth(3)->subDay(1);
        
        $now = new Tinebase_DateTime();
        $now->subHour(3);
        
        $nextMonth = clone $now;
        $nextMonth->addMonth(1); 
        
        $fcId = $this->_getFeastCalendar();
        
        $contracts = array(array(
            'start_date' => clone $sdate,
            'end_date'   => clone $edate,
            'vacation_days' => 23,
            'feast_calendar_id' => $fcId,
            'creation_time' => $now
        ));
        
        $sdate->addMonth(1);
        $edate->addMonth(1);
        
        $contracts[] = array(
            'start_date' => clone $sdate,
            'end_date'   => clone $edate,
            'vacation_days' => 27,
            'feast_calendar_id' => $fcId,
            'creation_time' => $now
        );
        
        $es = $this->_json->searchEmployees(array(), array());
        $eIds = array();
        foreach ($es['results'] as $e) {
            $eIds = $e['id'];
        }
        $this->_json->deleteEmployees($eIds);
        
        $employee = $this->_getEmployee('unittest')->toArray();
        $employee['contracts'] = $contracts;
        
        $employee = $this->_json->saveEmployee($employee);
        $this->assertEquals(2, count($employee['contracts']));
        
        $es = $this->_json->searchEmployees(array(), array());
        $eIds = array();
        foreach ($es['results'] as $e) {
            $eIds = $e['id'];
        }
        $this->_json->deleteEmployees($eIds);
        
        // remove ids
        unset($employee['contracts'][0]['id']);
        unset($employee['contracts'][0]['employee_id']);
        unset($employee['contracts'][1]['id']);
        unset($employee['contracts'][1]['employee_id']);
        unset($employee['id']);
        
        // test overlapping
        
        // create overlapping contract
        $sdate1 = clone $sdate;
        $edate1 = clone $edate;
        $sdate1->addDay(3);
        $edate1->addMonth(1);
        
        $employee['contracts'][] = array(
            'start_date' => $sdate1,
            'end_date' => $nextMonth,
            'vacation_days' => 22,
            'feast_calendar_id' => $fcId,
            'creation_time' => $now->toString()
        );
        
        // doing this manually, this won't be the last assertion, and more assertions are needed
        // $this->setExpectedException('Tinebase_Exception_Data');
        
        $exception = new Exception('no exception has been thrown');
        
        try {
            $this->_json->saveEmployee($employee);
        } catch (Tinebase_Exception_Data $exception) {
            // thrown in HR_Controller_Employee
        }
        
        $this->assertEquals('The contracts must not overlap!', $exception->getMessage());
        
        // test startdate after end_date
        
        $employee['contracts'][2] = array(
            'start_date' => $edate1->toString(),
            'end_date' => $sdate1->toString(),
            'vacation_days' => 22,
            'feast_calendar_id' => $fcId,
            'creation_time' => $now->toString()
        );

        try {
            $this->_json->saveEmployee($employee);
        } catch (Tinebase_Exception_Record_Validation $exception) {
            // thrown in HR_Controller_Contract
        }
        
        $this->assertEquals('The start date of the contract must be before the end date!', $exception->getMessage());
    }
    
    /**
     * test working time
     */
    public function testWorkingTimeTemplate()
    {
         $recordData = array('title' => 'lazy worker', 'type' => 'static', 'json' => '{"days":[1,1,1,1,1,0,0]}', 'working_hours' => 5);
         $savedWT = $this->_json->saveWorkingTime($recordData);

         $this->assertEquals($savedWT['title'], 'lazy worker');

         // test duplicate exception
         $this->setExpectedException('Tinebase_Exception_Duplicate');
         $this->_json->saveWorkingTime($recordData);
    }

    
    /**
     * tests account summary
     */
    public function testAccount()
    {
        $employmentBegin  = new Tinebase_DateTime('2012-12-15');
        $employmentChange = new Tinebase_DateTime('2014-01-01');
        $employmentEnd    = new Tinebase_DateTime('2014-06-30');
    
        $contractController = HumanResources_Controller_Contract::getInstance();
        $employeeController = HumanResources_Controller_Employee::getInstance();
        $contractBackend = new HumanResources_Backend_Contract();
    
        $employee = $this->_getEmployee('unittest');
        $employee->employment_begin = $employmentBegin;
        $employee->employment_end = $employmentEnd;
        
        $contract1 = $this->_getContract();
        $contract1->start_date = $employmentBegin;
        $contract1->workingtime_json = '{"days": [8,8,8,8,8,0,0]}';
        $contract1->vacation_days = 25;
        
        $contract2 = $this->_getContract();
        $contract2->start_date = $employmentChange;
        $contract2->end_date = $employmentEnd;
        $contract2->workingtime_json = '{"days": [4,4,4,4,4,4,4]}';
    
        $employee->contracts = array($contract1->toArray(), $contract2->toArray());
    
        $employee = $employeeController->create($employee);
    
        $accountController = HumanResources_Controller_Account::getInstance();
        $accountController->createMissingAccounts(2013, $employee);
        $accountController->createMissingAccounts(2014, $employee);
        
        $filter = array(array('field' => "employee_id", 'operator' => "AND", 'value' => array(
            array('field' => ':id', 'operator' => 'equals', 'value' => $employee->getId())
        )));
        
        // create feast days
        $feastDays2013 = array(
            '2013-01-01', '2013-03-29', '2013-04-01', '2013-05-01', '2013-05-09',
            '2013-05-20', '2013-10-03', '2013-12-25', '2013-12-26', '2013-12-31'
        );
        
        foreach($feastDays2013 as $day) {
            $date = new Tinebase_DateTime($day . ' 12:00:00');
            $this->_createFeastDay($date);
        }
        
        $json = new HumanResources_Frontend_Json();
        
        $result = $json->searchAccounts($filter, array());
        $this->assertEquals(2, $result['totalcount']);
        
        $accountId2013 = $result['results'][0]['year'] == 2013 ? $result['results'][0]['id'] : $result['results'][1]['id'];
        $account2013 = $json->getAccount($accountId2013);
        
        $this->assertEquals(25, $account2013['possible_vacation_days']);
        $this->assertEquals(226, $account2013['working_days']);
        
        // add 5 extra free days to the account
        $eft1 = new HumanResources_Model_ExtraFreeTime(array('days' => 2, 'account_id' => $accountId2013));
        $eft2 = new HumanResources_Model_ExtraFreeTime(array('days' => 3, 'account_id' => $accountId2013));
        
        $eftController = HumanResources_Controller_ExtraFreeTime::getInstance();
        $eftController->create($eft1);
        $eftController->create($eft2);
        
        $account2013 = $json->getAccount($accountId2013);
        $this->assertEquals(30, $account2013['possible_vacation_days']);
        $this->assertEquals(221, $account2013['working_days']);
    }
}
