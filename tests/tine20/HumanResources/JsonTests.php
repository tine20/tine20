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
     * the frontend
     * 
     * @var HumanResources_Frontend_Json
     */
    protected $_json = NULL;
    
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

        // check if accounts has been created properly on aftercreate
        $filter = new HumanResources_Model_AccountFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $savedEmployee['id'])));
        $result = HumanResources_Controller_Account::getInstance()->search($filter);
        $this->assertEquals(2, $result->count());
       
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
        
        $this->assertEquals($firstDate, substr($savedEmployee['costcenters'][0]['start_date'], 0, 10), 'The start_date of the first costcenter should begin with the first date of the employee!');
        
        $date1 = new Tinebase_DateTime($savedEmployee['contracts'][0]['end_date']);
        $date2 = new Tinebase_DateTime($savedEmployee['contracts'][1]['start_date']);

        $this->assertEquals($date1->addDay(1)->toString(), $date2->toString());

        $freeTimes = $this->_json->getFeastAndFreeDays($savedEmployee['id'], $date2->format('Y'));
        
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
     * test if no account is found, id should stay
     * 
     * 0008608: After an account got deleted, opening of the corresponding employee fails
     * rt111840: https://service.metaways.net/Ticket/Display.html?id=111840
     */
    public function testResolveDeactivatedAccounts()
    {
        $e = $this->_getEmployee('rwright');
        $e = $this->_json->saveEmployee($e->toArray());
        
        $this->assertTrue(is_array($e['account_id']));
        
        $ui = Tinebase_User::getInstance();
        $a = $ui->getFullUserById($e['account_id']['accountId']);
        
        $a->accountStatus = 'disabled';
        $ui->updateUser($a);
        
        $e = $this->_json->getEmployee($e['id']);
        
        $this->assertFalse(is_array($e['account_id']));
        $this->assertEquals($a->accountId, $e['account_id']);
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
     * tests account summary and getFeastAndFreeDays method
     */
    public function testAccount()
    {
        $employmentBegin  = new Tinebase_DateTime('2012-12-15');
        $employmentChange = new Tinebase_DateTime('2014-01-01');
        $employmentEnd    = new Tinebase_DateTime('2014-06-30');
    
        $referenceDate = new Tinebase_DateTime('2013-10-10');
        
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
    
        $rs = new Tinebase_Record_RecordSet('HumanResources_Model_Contract');
        $rs->addRecord($contract1);
        $rs->addRecord($contract2);
        
        $employee->contracts = $rs;
    
        $employee = $employeeController->create($employee);
    
        $json = new HumanResources_Frontend_Json();
        $accountController = HumanResources_Controller_Account::getInstance();
        $accountsFilter = array(array('field' => "employee_id", 'operator' => "AND", 'value' => array(
            array('field' => ':id', 'operator' => 'equals', 'value' => $employee->getId())
        )));

        // should not be created, exist already
        $accountController->createMissingAccounts(2013, $employee);
        $accountController->createMissingAccounts(2014, $employee);
        
        // create feast days
        $feastDays = array(
            '01-01', '03-29', '04-01', '05-01', '05-09',
            '05-20', '10-03', '12-25', '12-26', '12-31'
        );
        
        foreach ($feastDays as $day) {
            $date = new Tinebase_DateTime('2013-' . $day . ' 12:00:00');
            $this->_createFeastDay($date);
            $date = new Tinebase_DateTime('2014-' . $day . ' 12:00:00');
            $this->_createFeastDay($date);
        }
        
        // what about the holy evening? it's recurring
        // @see 0009114: Freeetime edit dialog doesn't calculate recurring feast days
        //      https://forge.tine20.org/mantisbt/view.php?id=9114
        
        $this->_createRecurringFeastDay(new Tinebase_DateTime('2011-12-24'));
        
        $result = $json->searchAccounts($accountsFilter, array('sort' => 'year', 'dir' => 'DESC'));
        $this->assertEquals('3', $result['totalcount'], 'Three accounts should have been found!');
        
        $accountId2013 = $result['results'][1]['id'];
        $account2013 = $json->getAccount($accountId2013);
        
        $accountId2014 = $result['results'][0]['id'];
        $account2014 = $json->getAccount($accountId2014);
        
        $this->assertEquals(25, $account2013['possible_vacation_days']);
        $this->assertEquals(225, $account2013['working_days']);
        
        $this->assertEquals(15, $account2014['possible_vacation_days']);
        $this->assertEquals(160, $account2014['working_days']);
        
        // add 5 extra free days to the account with different expiration dates, 2 days aren't expired already
        $tomorrow = Tinebase_DateTime::now();
        $tomorrow->addDay(1);
        $yesterday = Tinebase_DateTime::now();
        $yesterday->subDay(1);
        
        $eft1 = new HumanResources_Model_ExtraFreeTime(array('days' => 2, 'account_id' => $accountId2013, 'expires' => $tomorrow));
        $eft2 = new HumanResources_Model_ExtraFreeTime(array('days' => 3, 'account_id' => $accountId2013, 'expires' => $yesterday));
        
        $eftController = HumanResources_Controller_ExtraFreeTime::getInstance();
        $eftController->create($eft1);
        $eftController->create($eft2);
        
        $account2013 = $json->getAccount($accountId2013);
        $this->assertEquals(27, $account2013['possible_vacation_days']);
        $this->assertEquals(27, $account2013['remaining_vacation_days']);
        $this->assertEquals(223, $account2013['working_days']);
        $this->assertEquals(3, $account2013['expired_vacation_days'], 'There should be 3 expired vacation days at first!');
        
        // the extra freetimes added to the account2013 should not affect account 2014
        $account2014 = $json->getAccount($accountId2014);
        $this->assertEquals(15, $account2014['possible_vacation_days']);
        $this->assertEquals(160, $account2014['working_days']);
        
        // now add 3 vacation days before the expiration day of the second extra free time
        // #8202: Allow to book remaining free days from last years' account, respect expiration
        $freetime = array(
            'account_id' => $accountId2013,
            'employee_id' => $employee->getId(),
            'type' => 'vacation',
            'status' => 'ACCEPTED',
            'firstday_date' => $yesterday->subWeek(1)->toString()
        );
        
        $freetime['freedays'] = array(
            array('duration' => '1', 'date' => $yesterday->toString()),
            array('duration' => '1', 'date' => $yesterday->addDay(1)->toString()),
            array('duration' => '1', 'date' => $yesterday->addDay(1)->toString()),
        );
        
        $freetime = $this->_json->saveFreeTime($freetime);
        
        // so the 3 days haven't been expired, because 3 vacation days have been booked before
        $account2013 = $json->getAccount($accountId2013);
        $this->assertEquals(30, $account2013['possible_vacation_days'], 'There should be 30 possible vacation days after all!');
        $this->assertEquals(27, $account2013['remaining_vacation_days'], 'There should be 27 remaining vacation days after all!');
        $this->assertEquals(0, $account2013['expired_vacation_days'], 'There should be no expired vacation days after all!');
        $this->assertEquals(3, $account2013['taken_vacation_days'], 'He took 3 vacation days');
        
        
        // test account filter for: employee_id and year
        $accountsFilter = array(array('field' => "employee_id", 'operator' => "AND", 'value' => array(
            array('field' => ':id', 'operator' => 'equals', 'value' => $employee->getId())
        )), array('field' => 'year', 'operator' => 'equals', 'value' => $account2013['year']));
        
        $result = $json->searchAccounts($accountsFilter, array());
        $this->assertEquals(1, $result['totalcount']);
        
        // test account quicksearch filter
        $qsFilter = array(array('field' => "query", 'operator' => "contains", 'value' => 'Admin'));
        $result = $json->searchAccounts($qsFilter, array());
        $this->assertEquals(3, $result['totalcount']);
        
        $qsFilter = array(array('field' => "query", 'operator' => "contains", 'value' => 'Adsmin'));
        $result = $json->searchAccounts($qsFilter, array());
        $this->assertEquals(0, $result['totalcount']);

        $refdate = clone $referenceDate;
        
        // now we test if adding a vacation with dates of 2013 but the account of 2014 works as expected
        $freetime = array(
            'account_id' => $accountId2014,
            'employee_id' => $employee->getId(),
            'type' => 'vacation',
            'status' => 'ACCEPTED',
            'firstday_date' => $refdate,
            'lastday_date'  => $refdate->addDay(3)->toString(),
            'days_count' => 3
        );
        
        $refdate = clone $referenceDate;
        
        $freetime['freedays'] = array(
            array('duration' => '1', 'date' => $referenceDate->toString()),
            array('duration' => '1', 'date' => $referenceDate->addDay(1)->toString()),
            array('duration' => '1', 'date' => $referenceDate->addDay(1)->toString()),
        );
        
        $freetime = $this->_json->saveFreeTime($freetime);
        
        // the extra freetimes added to the account2014 should not affect account 2013
        $account2013 = $json->getAccount($accountId2013);
        $this->assertEquals(30, $account2013['possible_vacation_days']);
        $this->assertEquals(27, $account2013['remaining_vacation_days']);
        
        // but possible vacation days of the 2014 account should be reduced by 3
        $account2014 = $json->getAccount($accountId2014);
        $this->assertEquals(15, $account2014['possible_vacation_days']);
        $this->assertEquals(12, $account2014['remaining_vacation_days']);
        $this->assertEquals(160, $account2014['working_days']);
        
        
        // now let's test the getFeastAndFreeTimes method with the same fixtures
        
        $result = $this->_json->getFeastAndFreeDays($employee->getId(), "2013");
        $res = $result['results'];
        $this->assertEquals(27, $res['remainingVacation']);
        $this->assertEquals(5, $res['extraFreeTimes']['remaining']);
        $this->assertEquals(6, count($res['vacationDays']));
        $this->assertEquals(0, count($res['sicknessDays']));
        $this->assertEquals(104, count($res['excludeDates']));
        $this->assertEquals(NULL, $res['ownFreeDays']);
        $this->assertEquals(11, count($res['feastDays']));
        $this->assertEquals(1, count($res['contracts']));
        $this->assertEquals($employee->getId(), $res['employee']['id']);
        $this->assertEquals('2013-01-01 00:00:00', $res['firstDay']->toString());
        $this->assertEquals('2013-12-31 23:59:59', $res['lastDay']->toString());
        
        $day = Tinebase_DateTime::now()->setDate(2013, 9, 23)->setTime(0,0,0);
        $newFreeTime = array(
            'account_id' => $accountId2013,
            'employee_id' => $employee->getId(),
            'type' => 'vacation',
            'status' => 'ACCEPTED',
            'firstday_date' => $day->toString()
        );
        
        $newFreeTime['freedays'] = array(
            array('duration' => '1', 'date' => $day->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
        );
        
        $newFreeTime['days_count']   = 3;
        $newFreeTime['lastday_date'] = $day->toString();
        
        $this->_json->saveFreeTime($newFreeTime);
        
        $result = $this->_json->getFeastAndFreeDays($employee->getId(), "2013");
        $res = $result['results'];
        $this->assertEquals(9, count($res['vacationDays']));
        $this->assertEquals(24, $res['remainingVacation']);
        
        // overwrite last day of previous vacation with sickness
        $newFreeTime = array(
            'account_id' => $accountId2013,
            'employee_id' => $employee->getId(),
            'type' => 'sickness',
            'status' => 'ACCEPTED',
            'firstday_date' => $day->toString()
        );
        
        $newFreeTime['freedays'] = array(
            array('duration' => '1', 'date' => $day->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
        );
        
        $newFreeTime['days_count']   = 2;
        $newFreeTime['lastday_date'] = $day->toString();
        
        $this->_json->saveFreeTime($newFreeTime);
        
        $result = $this->_json->getFeastAndFreeDays($employee->getId(), "2013");
        $res = $result['results'];
        $this->assertEquals(8, count($res['vacationDays']));
        $this->assertEquals(2, count($res['sicknessDays']));
        $this->assertEquals(25, $res['remainingVacation']);
    }
    
    /**
     * test HumanResources_Exception_NoAccount
     */
    public function testNoAccountException()
    {
        $employee = $this->_getEmployee('unittest');
        
        $this->setExpectedException('HumanResources_Exception_NoAccount');
        
        $this->_json->getFeastAndFreeDays($employee->getId(), '2000');
    }
    
    /**
     * tests the correct values of the freetime record
     * @see 0009168: HR saving sickness days days_count failure
     *      https://forge.tine20.org/mantisbt/view.php?id=9168
     */
    public function testFirstAndLastDayOfFreetime() {
        $employmentBegin  = new Tinebase_DateTime('2012-12-15');
        $employmentEnd    = new Tinebase_DateTime('2014-06-30');
    
        $referenceDate = new Tinebase_DateTime('2013-10-10');
        
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
        
        $rs = new Tinebase_Record_RecordSet('HumanResources_Model_Contract');
        $rs->addRecord($contract1);
        
        $employee->contracts = $rs;
    
        $employee = $employeeController->create($employee);
        $accountController = Humanresources_Controller_Account::getInstance();
        $accountController->createMissingAccounts(2013, $employee);
        $accountsFilter = array(array('field' => "employee_id", 'operator' => "AND", 'value' => array(
            array('field' => ':id', 'operator' => 'equals', 'value' => $employee->getId())
        )));
        $result = $this->_json->searchAccounts($accountsFilter, array('sort' => 'year', 'dir' => 'DESC'));
        $this->assertEquals('3', $result['totalcount'], 'One accounts should have been found!');
        
        $accountId2013 = $result['results'][2]['id'];
        
        $day = Tinebase_DateTime::now()->setTimezone('UTC')->setDate(2013, 9, 23)->setTime(16,0,0);
        $newFreeTime = array(
            'account_id' => $accountId2013,
            'employee_id' => $employee->getId(),
            'type' => 'vacation',
            'status' => 'ACCEPTED',
        );
        
        $newFreeTime['freedays'] = array(
            array('duration' => '1', 'date' => $day->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
        );
        
        $freetime = $this->_json->saveFreeTime($newFreeTime);
        
        $this->assertEquals(3, count($freetime['freedays']));
        $this->assertEquals('2013-09-23', substr($freetime['firstday_date'], 0, 10));
        $this->assertEquals('2013-09-25', substr($freetime['lastday_date'], 0, 10));
    }
    
    /**
     * tests datetime conversion of dependent records
     */
    public function testDateTimeConversion()
    {
        $employmentBegin  = new Tinebase_DateTime('2012-12-15');
        $employmentEnd    = new Tinebase_DateTime('2014-06-30');
        $employmentBegin->setTime(12,0,0);
        
        $employee = $this->_getEmployee('unittest');
        $employee->employment_begin = $employmentBegin;
        $employee->employment_end = $employmentEnd;
        
        $contract = $this->_getContract();
        $contract->start_date = $employmentBegin;
        $contract->workingtime_json = '{"days": [8,8,8,8,8,0,0]}';
        $contract->vacation_days = 25;
        
        $employee->contracts = array($contract->toArray());
        $json = new HumanResources_Frontend_Json();
        $savedEmployee = $json->saveEmployee($json->saveEmployee($employee->toArray()));
        $this->assertStringEndsWith('12:00:00', $savedEmployee['employment_begin']);
        $this->assertStringEndsWith('12:00:00', $savedEmployee['contracts'][0]['start_date']);
    }
}
