<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

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
        $this->_uit = $this->_json = new HumanResources_Frontend_Json();
    }

    /**
     * Creates an employee with contracts and contact, account etc.
     * tests auto end_date of old contract
     */
    public function testEmployee()
    {
        $date = new Tinebase_DateTime();
        $date->subYear(1);
        $date->setDate($date->format('Y'), 2, 1);
        
        $firstDate = substr($date->toString(), 0, 10);
        $startDate = clone $date;
        
        $costCenter1 = $this->_getCostCenter($date);
        $savedEmployee = $this->_saveEmployee($costCenter1);

        $this->assertArrayHasKey('account_id', $savedEmployee);
        $this->assertTrue(is_array($savedEmployee['account_id']));
        
        $this->assertArrayHasKey('contracts', $savedEmployee);
        $this->assertArrayHasKey('costcenters', $savedEmployee);
        
        $this->assertEquals(1, count($savedEmployee['contracts']));
        static::assertTrue(is_array($savedEmployee['contracts'][0]['working_time_scheme']));
        $this->assertEquals(1, count($savedEmployee['costcenters']));

        // check if accounts has been created properly on aftercreate
        $filter = new HumanResources_Model_AccountFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $savedEmployee['id'])));
        $result = HumanResources_Controller_Account::getInstance()->search($filter);
        $this::assertGreaterThanOrEqual(2, $result->count(), 'should find 2 or more accounts: ' . print_r($result->toArray(), true));
       
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

        // FIXME this is not working on daylight saving boundaries
        //$this->assertEquals($date1->addDay(1)->toString(), $date2->toString());

        $freeTimes = $this->_json->getFeastAndFreeDays($savedEmployee['id'], $date2->format('Y'));
        
        $this->assertEquals($savedEmployee['id'], $freeTimes['results']['contracts'][0]['employee_id']);
        
        // 0009592: Adding a new cost center to a employee fails
        // https://forge.tine20.org/mantisbt/view.php?id=9592
        
        $accountInstance = HumanResources_Controller_Account::getInstance();
        $accountInstance->createMissingAccounts((int) $startDate->format('Y'));
        
        $accountFilter = new HumanResources_Model_AccountFilter(array());
        
        $accountFilter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'employee_id', 'operator' => 'equals', 'value' => $savedEmployee['id'])
        ));
        $myAccount = $accountInstance->search($accountFilter)->getFirstRecord();
        
        $firstDayDate = clone $startDate;
        $firstDayDate->addDay(3);
        
        while ($firstDayDate->format('N') != 1) {
            $firstDayDate->addDay(1);
        }
        
        $vacation = new HumanResources_Model_FreeTime(array(
            'status'        => 'ACCEPTED',
            'employee_id'   => $savedEmployee['id'],
            'account_id'    => $myAccount->getId(),
            'type'          => 'vacation',
            'freedays'      => array(
                array('date' => $firstDayDate, 'duration' => 1),
                array('date' => $firstDayDate->getClone()->addDay(1), 'duration' => 1),
                array('date' => $firstDayDate->getClone()->addDay(1), 'duration' => 1),
                array('date' => $firstDayDate->getClone()->addDay(1), 'duration' => 1),
                array('date' => $firstDayDate->getClone()->addDay(1), 'duration' => 1),
            )
        ));
        
        $vacation = HumanResources_Controller_FreeTime::getInstance()->create($vacation);
        
        $employee = $this->_json->getEmployee($savedEmployee['id']);
        
        
        $date->addMonth(2);
        $costCenter3 = $this->_getCostCenter($date);
        
        $employee['costcenters'][] = $costCenter3->toArray();
        
        $employee = $this->_json->saveEmployee($employee);
        
        $this->assertEquals(5, $employee['vacation'][0]['days_count']);
        $this->assertEquals(17, count($employee['vacation'][0]));
        $this->assertEquals(3, count($employee['costcenters']));
        
        // @see: 0010050: Delete last dependent record fails
        
        // if the property is set to null, no dependent record handling will be done
        $employee['costcenters'] = NULL;
        $employee = $this->_json->saveEmployee($employee);
        $this->assertEquals(3, count($employee['costcenters']));
        
        // if the property is set to an empty array, all dependent records will be removed
        $employee['costcenters'] = array();
        $employee = $this->_json->saveEmployee($employee);
        $this->assertEmpty($employee['costcenters']);
    }
    
    /**
     * save employee
     * 
     * @param HumanResources_Model_CostCenter $costCenter
     * @return array
     */
    protected function _saveEmployee($costCenter = null, $firstDate = NULL)
    {
        $e = $this->_getEmployee();
        $e->contracts = array($this->_getContract($firstDate)->toArray());
        
        if ($costCenter) {
            $e->costcenters = array($costCenter->toArray());
        }
        
        $savedEmployee = $this->_json->saveEmployee($e->toArray());
        
        $this->assertEquals($e->n_fn, $savedEmployee['n_fn']);
        
        return $savedEmployee;
    }

    /**
     * @see 0012228: employee bday should be saved as datetime
     */
    public function testBirthday()
    {
        $e = $this->_getEmployee();
        $datetime = new Tinebase_DateTime('2009-03-02 00:00:00');
        $e->bday = $datetime;
        $savedEmployee = $this->_json->saveEmployee($e->toArray());
        $this->assertEquals($datetime->toString(), $savedEmployee['bday']);
    }

    /**
     * Tests the duplicate check
     */
    public function testDuplicateException()
    {
        $e = $this->_getEmployee();
        $e->contracts = array($this->_getContract()->toArray());
        $e->tags = [[
            'type'          => Tinebase_Model_Tag::TYPE_PERSONAL,
            'name'          => 'hr test tag',
            'description'    => 'testDuplicateException',
            'color'         => '#009B31',
        ]];
        $this->_json->saveEmployee($e->toArray());

        try {
            $e = $this->_getEmployee();
            $e->contracts = array($this->_getContract()->toArray());
            $this->_json->saveEmployee($e->toArray());
            self::fail('duplicate exception expected');
        } catch (Tinebase_Exception_Duplicate $exception) {
            $duplicates = $exception->getData();
            self::assertGreaterThan(0, count($duplicates));
            self::assertEquals(1, count($duplicates->getFirstRecord()->tags));
            self::assertEquals(629, $exception->getCode());
        }
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
        
        $this->assertEquals($a->accountId, $e['account_id']['accountId']);
    }
    
    /**
     * remove employees helper
     */
    protected function _removeAllEmployees()
    {
        $es = $this->_json->searchEmployees(array(), array());
        $eIds = array();
        foreach ($es['results'] as $e) {
            $eIds[] = $e['id'];
        }
        $this->_json->deleteEmployees($eIds);
    }

    public function testContractDirect()
    {
        $e = $this->_getEmployee('rwright');
        $e = $this->_json->saveEmployee($e->toArray());

        $contract = $this->_getContract();
        $contract->employee_id = $e['id'];

        $savedContract = $this->_json->saveContract($contract->toArray(true));
        static::assertTrue(is_array($savedContract[0]['working_time_scheme']));

        HumanResources_Controller_WorkingTimeScheme::getInstance()
            ->delete($savedContract[0]['working_time_scheme']['id']);

        $savedContract = $this->_json->getContract($savedContract[0]['id']);
        static::assertTrue(is_array($savedContract[0]['working_time_scheme']), 'expect deleted WTS to be resolved');
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

        $wtscheme = $this->_getWorkingTimeScheme40();
        self::assertNotNull($wtscheme);
        $contracts = array(array(
            'start_date' => clone $sdate,
            'end_date'   => clone $edate,
            'vacation_days' => 23,
            'feast_calendar_id' => $fcId,
            'creation_time' => $now,
            'id' => 1234567891,
            'working_time_scheme' =>$wtscheme->getId(),
        ));
        
        $sdate->addMonth(1);
        $edate->addMonth(1);
        
        $contracts[] = array(
            'start_date' => clone $sdate,
            'end_date'   => clone $edate,
            'vacation_days' => 27,
            'feast_calendar_id' => $fcId,
            'creation_time' => $now,
            'id' => 1234567890,
            'working_time_scheme' => $wtscheme->getId(),
        );
        
        $employee = $this->_getEmployee(Tinebase_Core::getUser()->accountLoginName)->toArray();
        $employee['contracts'] = $contracts;
        
        $employee = $this->_json->saveEmployee($employee);
        $this->assertEquals(2, count($employee['contracts']));
        
        // get sure the ids are generated properly
        $this->assertEquals(40, strlen($employee['contracts'][1]['id']));
        $this->assertEquals(40, strlen($employee['contracts'][0]['id']));
        
        $this->_removeAllEmployees();
        
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
            'creation_time' => $now->toString(),
            'number' => 1,
            'working_time_scheme' => $wtscheme->getId(),
        );
        
        // doing this manually, this won't be the last assertion, and more assertions are needed
        // $this->setExpectedException('Tinebase_Exception_Data');
        
        $exception = new Exception('no exception has been thrown');
        
        try {
            $this->_json->saveEmployee($employee);
        } catch (HumanResources_Exception_ContractOverlap $exception) {
            // thrown in HR_Controller_Employee
        }
        
        $this->assertEquals('The contracts must not overlap!', $exception->getMessage());
        
        $this->_removeAllEmployees();
        
        // prevent duplicate exception
        $employee['account_id'] = $this->_getAccount('rwright')->getId();
        // test startdate after end_date
        $employee['contracts'][2] = array(
            'start_date' => $edate1->toString(),
            'end_date' => $sdate1->toString(),
            'vacation_days' => 22,
            'feast_calendar_id' => $fcId,
            'creation_time' => $now->toString(),
            'working_time_scheme' => $wtscheme->getId(),
        );

        try {
            $this->_json->saveEmployee($employee);
            $this->fail('HumanResources_Exception_ContractDates exception expected');
        } catch (HumanResources_Exception_ContractDates $exception) {
            // thrown in HR_Controller_Contract
            $this->assertEquals('The start date of the contract must be before the end date!', $exception->getMessage());
        } catch (Tinebase_Exception_Duplicate $ted) {
            $this->fail('got duplicate exception: ' . print_r($ted->toArray(), true));
        }
    }
    
    /**
     * test working time
     */
    public function testWorkingTimeTemplate()
    {
        $recordData = [
            HumanResources_Model_WorkingTimeScheme::FLDS_TITLE     => 'lazy worker',
            HumanResources_Model_WorkingTimeScheme::FLDS_TYPE      =>
                HumanResources_Model_WorkingTimeScheme::TYPES_TEMPLATE,
            HumanResources_Model_WorkingTimeScheme::FLDS_JSON      => '[3600,3600,3600,3600,3600,0,0]',
            HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE    => '[{"' .
                Tinebase_Model_BLConfig::FLDS_CLASSNAME . '":"' .
                HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::class . '","' .
                Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD . '":{"' .
                HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::FLDS_START_TIME . '":"07:30","' .
                HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::FLDS_END_TIME . '":"16:25"}}]'
        ];
        $savedWT = $this->_json->saveWorkingTime($recordData);

        static::assertSame($savedWT[HumanResources_Model_WorkingTimeScheme::FLDS_TITLE], 'lazy worker');
        static::assertSame($savedWT[HumanResources_Model_WorkingTimeScheme::FLDS_TYPE],
            HumanResources_Model_WorkingTimeScheme::TYPES_TEMPLATE);
        static::assertSame($savedWT[HumanResources_Model_WorkingTimeScheme::FLDS_JSON],
            '[3600,3600,3600,3600,3600,0,0]');
        $blpipe = $savedWT[HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE];
        static::assertTrue(isset($blpipe[0][Tinebase_Model_BLConfig::FLDS_CLASSNAME]), print_r($blpipe, true));
        static::assertSame($blpipe[0][Tinebase_Model_BLConfig::FLDS_CLASSNAME],
            HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::class);

         // test duplicate exception
         $this->setExpectedException('Tinebase_Exception_Duplicate');
         $this->_json->saveWorkingTime($recordData);
    }
    
    /**
     * tests account summary and getFeastAndFreeDays method calculation
     */
    public function testCalculation()
    {
        $employmentBegin  = new Tinebase_DateTime('2012-12-15');
        $employmentChange = new Tinebase_DateTime('2014-01-01');
        $employmentEnd    = new Tinebase_DateTime('2014-06-30');
    
        $referenceDate = new Tinebase_DateTime('2013-10-10');
        
        $contractController = HumanResources_Controller_Contract::getInstance();
        $employeeController = HumanResources_Controller_Employee::getInstance();
        $contractBackend = new HumanResources_Backend_Contract();
    
        $employee = $this->_getEmployee(Tinebase_Core::getUser()->accountLoginName);
        $employee->employment_begin = $employmentBegin;
        $employee->employment_end = $employmentEnd;
        
        $contract1 = $this->_getContract();
        $contract1->start_date = $employmentBegin;
        $contract1->vacation_days = 25;
        
        $contract2 = $this->_getContract();
        $contract2->start_date = $employmentChange;
        $contract2->end_date = $employmentEnd;
        $contract2->working_time_scheme = HumanResources_Controller_WorkingTimeScheme::getInstance()
            ->create(new HumanResources_Model_WorkingTimeScheme([
                HumanResources_Model_WorkingTimeScheme::FLDS_TITLE  => '7x4',
                HumanResources_Model_WorkingTimeScheme::FLDS_JSON   => ['days' => [4,4,4,4,4,4,4]],
            ]))->getId();
    
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
        // @see 0009114: Freetime edit dialog doesn't calculate recurring feast days
        //      https://forge.tine20.org/mantisbt/view.php?id=9114
        
        $this->_createRecurringFeastDay(new Tinebase_DateTime('2011-12-24'));
        
        $result = $json->searchAccounts($accountsFilter, array('sort' => 'year', 'dir' => 'DESC'));
        $this->assertEquals('3', $result['totalcount'], 'Three accounts should have been found!');
        
        $accountId2013 = $result['results'][1]['id'];
        $account2013 = $json->getAccount($accountId2013);
        
        $accountId2014 = $result['results'][0]['id'];
        $account2014 = $json->getAccount($accountId2014);
        
        $this->assertEquals(25, $account2013['possible_vacation_days']);
        $this->assertEquals(250, $account2013['working_days']);
        
        $this->assertEquals(15, $account2014['possible_vacation_days']);
        $this->assertEquals(175, $account2014['working_days']);
        
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
        $this->assertEquals(250, $account2013['working_days']);
        $this->assertEquals(3, $account2013['expired_vacation_days'], 'There should be 3 expired vacation days at first!');
        
        // the extra freetimes added to the account2013 should not affect account 2014
        $account2014 = $json->getAccount($accountId2014);
        $this->assertEquals(15, $account2014['possible_vacation_days']);
        $this->assertEquals(175, $account2014['working_days']);
        
        // now add 3 vacation days before the expiration day of the second extra free time
        // #8202: Allow to book remaining free days from last years' account, respect expiration
        $freetime = array(
            'account_id' => $accountId2013,
            'employee_id' => $employee->getId(),
            'type' => 'vacation',
            'status' => 'ACCEPTED',
            'firstday_date' => $yesterday->subWeek(1)->toString()
        );
        $nd = $referenceDate->subMonth(2);
        $freetime['freedays'] = array(
            array('duration' => '1', 'date' => $nd->toString()),
            array('duration' => '1', 'date' => $nd->addDay(1)->toString()),
            array('duration' => '1', 'date' => $nd->addDay(1)->toString()),
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
        $qsFilter = array(array('field' => "query", 'operator' => "contains", 'value' => Tinebase_Core::getUser()->accountFirstName));
        $result = $json->searchAccounts($qsFilter, array());
        $this->assertEquals(3, $result['totalcount'], 'should find exactly 3 accounts');
        
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
        $this->assertEquals(175, $account2014['working_days']);
        
        
        // now let's test the getFeastAndFreeTimes method with the same fixtures
        
        $result = $this->_json->getFeastAndFreeDays($employee->getId(), "2013");
        $res = $result['results'];
        $this->assertEquals(27, $res['remainingVacation']);
        $this->assertEquals(5, $res['extraFreeTimes']['remaining']);
//        $this->assertEquals(6, count($res['vacationDays'])); // not used in client
//        $this->assertEquals(0, count($res['sicknessDays'])); // not used in client
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
//        $this->assertEquals(9, count($res['vacationDays'])); // not used in client
        $this->assertEquals(24, $res['remainingVacation']);
        
        // overwrite last 2 days of previous vacation with sickness
        $day->subDay(1);
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
//        $this->assertEquals(7, count($res['vacationDays'])); // not used in client
//        $this->assertEquals(2, count($res['sicknessDays'])); // not used in client
        $this->assertEquals(26, $res['remainingVacation']);
    }
    
    /**
     * test HumanResources_Exception_NoAccount
     */
    public function testNoAccountException()
    {
        $employee = $this->_getEmployee(Tinebase_Core::getUser()->accountLoginName);
        
        $this->setExpectedException('HumanResources_Exception_NoAccount');
        
        $this->_json->getFeastAndFreeDays($employee->getId(), '2000');
    }
    
    /**
     * tests the correct values of the freetime record
     * @see 0009168: HR saving sickness days days_count failure
     *      https://forge.tine20.org/mantisbt/view.php?id=9168
     */
    public function testFirstAndLastDayOfFreetime()
    {
        $employmentBegin  = new Tinebase_DateTime('2012-12-15');
        $employmentEnd    = new Tinebase_DateTime('2014-06-30');
    
        $employeeController = HumanResources_Controller_Employee::getInstance();

        $employee = $this->_getEmployee(Tinebase_Core::getUser()->accountLoginName);
        $employee->employment_begin = $employmentBegin;
        $employee->employment_end = $employmentEnd;

        $wtscheme = $this->_getWorkingTimeScheme40();
        self::assertNotNull($wtscheme);
        
        $contract1 = $this->_getContract();
        $contract1->start_date = $employmentBegin;
        $contract1->working_time_scheme = $wtscheme->getId();
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
        
        $employee = $this->_getEmployee(Tinebase_Core::getUser()->accountLoginName);
        $employee->employment_begin = $employmentBegin;
        $employee->employment_end = $employmentEnd;
        
        $contract = $this->_getContract();
        $contract->start_date = $employmentBegin;
        $contract->vacation_days = 25;
        
        $employee->contracts = array($contract->toArray());
        $json = new HumanResources_Frontend_Json();
        $savedEmployee = $json->saveEmployee($json->saveEmployee($employee->toArray()));
        $this->assertStringEndsWith('12:00:00', $savedEmployee['employment_begin']);
        $this->assertStringEndsWith('12:00:00', $savedEmployee['contracts'][0]['start_date']);
    }

    /**
     * testSearchForEmptyEmploymentEnd
     * 
     * @see 0009362: allow to filter for empty datetimes
     */
    public function testSearchForEmptyEmploymentEnd()
    {
        $savedEmployee = $this->_saveEmployee();
        
        $result = $this->_json->searchEmployees(array(array(
            'field' => 'employment_end',
            'operator' => 'equals',
            'value' => '',
        )), array());
        
        $this->assertGreaterThan(0, $result['totalcount'], 'should find employee with no employment_end');
    }
    
    /**
     * @see: 0009574: vacation or sickness days can't be booked on the last working day
     *       https://forge.tine20.org/mantisbt/view.php?id=9574
     */
    public function testGetFeastAndFreeDays()
    {
            $employmentBegin = Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())
                ->setDate(2014, 1, 2)->setTime(0,0,0);
            $employmentEnd   = clone $employmentBegin;
            $employmentEnd->setDate(2014, 1, 30);
        
            $employee = $this->_getEmployee(Tinebase_Core::getUser()->accountLoginName);
            $employee->employment_begin = $employmentBegin;
            $employee->employment_end = $employmentEnd;
        
            $contract1 = $this->_getContract();
            $contract1->start_date = $employmentBegin;
            $contract1->end_date = $employmentEnd;
            $contract1->vacation_days = 25;
            $contract1->feast_calendar_id = $this->_getFeastCalendar()->getId();
            
            $recordData = $employee->toArray();
            $recordData['contracts'] = array($contract1->toArray());
            $recordData = $this->_json->saveEmployee($recordData);

            $this->_createFeastDay(Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())
                ->setDate(2014,1,6));
            
            $accountController = HumanResources_Controller_Account::getInstance();
            
            // should not be created, exist already
            $accountController->createMissingAccounts(2014, $employee);
            
            $result = $this->_json->getFeastAndFreeDays($recordData['id'], "2014");

            $res = $result['results'];
            $this->assertEquals(2, $res['remainingVacation']);
            $this->assertEquals(0, $res['extraFreeTimes']['remaining']);
//            $this->assertEquals(0, count($res['vacationDays'])); // not used in client
//            $this->assertEquals(0, count($res['sicknessDays'])); // not used in client
            $this->assertEquals(8, count($res['excludeDates']));
            $this->assertEquals(NULL, $res['ownFreeDays']);
            
            $this->assertEquals(1, count($res['feastDays']));
            $this->assertEquals(Tinebase_Core::getUserTimezone(), $res['feastDays'][0]->getTimezone()->getName());
            $this->assertEquals('2014-01-06 00:00:00', $res['feastDays'][0]->toString());
            
            $this->assertEquals(1, count($res['contracts']));
            $this->assertEquals($recordData['id'], $res['employee']['id']);
            $this->assertEquals('2014-01-02 00:00:00', $res['firstDay']->toString());
            $this->assertEquals('2014-01-30 23:59:59', $res['lastDay']->toString());
            
            $this->setExpectedException('HumanResources_Exception_NoAccount');
            
            $this->_json->getFeastAndFreeDays($employee->getId(), "2013");
    }
    
    /**
     * test contract dates on update dependent
     * must not throw the HumanResources_Exception_ContractNotEditable exception
     */
    public function testContractDates()
    {
        $employmentBegin = Tinebase_DateTime::now()->setDate(2014, 1, 2)->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        $employmentEnd   = clone $employmentBegin;
        $employmentEnd->setDate(2014, 1, 30);
        
        $employee = $this->_getEmployee(Tinebase_Core::getUser()->accountLoginName);
        $employee->employment_begin = $employmentBegin;
        $employee->employment_end = $employmentEnd;
        
        $contract1 = $this->_getContract();
        $contract1->start_date = $employmentBegin;
        $contract1->end_date = $employmentEnd;
        $contract1->vacation_days = 25;
        
        $recordData = $employee->toArray();
        $recordData['contracts'] = array($contract1->toArray());
        $recordData = $this->_json->saveEmployee($recordData);
        
        $recordData['bday'] = '2014-04-01 00:00:00';
        
        // no exception may be thrown
        $recordData = $this->_json->saveEmployee($recordData);
        
        $this->assertEquals('2014-04-01 00:00:00', $recordData['bday']);
    }
    
    /**
     * test adding a contract with manually setting the end_date of the contract before
     *
     * @see 0011962: contract end_date can't be changed if vacation has been added
     */
    public function testAddContract()
    {
        $sdate = new Tinebase_DateTime('2013-01-01 00:00:00');
        $sdate->setTimezone(Tinebase_Core::getUserTimezone());
        $employee = $this->_getEmployee('rwright');
    
        $contractController = HumanResources_Controller_Contract::getInstance();
        $employeeController = HumanResources_Controller_Employee::getInstance();
        $employee = $employeeController->create($employee);
        $contract = $this->_getContract($sdate);
        $contract->employee_id = $employee->getId();
        
        $feastCalendar = $this->_getFeastCalendar();
        $contract->feast_calendar_id = $feastCalendar->getId();
        
        $contract->start_date = $sdate;
        $contractController->create($contract);
    
        $employeeJson = $this->_json->getEmployee($employee->getId());
        
        $accountController = HumanResources_Controller_Account::getInstance();
        
        // should not be created, exist already
        $accountController->createMissingAccounts(2013, $employee);
        $account = $accountController->getAll()->getFirstRecord();
        
        $employeeJson['vacation'] = array(array(
            'account_id' => $account->getId(),
            'type' => 'vacation',
            'status' => 'ACCEPTED',
            'freedays' => array(array('duration' => '1', 'date' => '2013-01-11 00:00:00')),
        ));
        
        $employeeJson = $this->_json->saveEmployee($employeeJson);
        
        $this->assertEquals(1, count($employeeJson['vacation']));

        $wtscheme = $this->_getWorkingTimeScheme40();
        self::assertNotNull($wtscheme);

        // manually set the end date and add a new contract
        $employeeJson['contracts'][0]['end_date'] = '2013-05-31 00:00:00';
        $employeeJson['contracts'][1] = array(
            'start_date' => '2013-06-01 00:00:00',
            'vacation_days' => 27,
            'feast_calendar_id' => $feastCalendar->getId(),
            'working_time_scheme' => $wtscheme->getId(),
        );
        
        // no exception should be thrown
        $employeeJson = $this->_json->saveEmployee($employeeJson);
        $this->assertEquals(2, count($employeeJson['contracts']));

        $endDate = '2013-05-30 00:00:00';
        $employeeJson['contracts'][0]['end_date'] = $endDate;
        $recordData = $this->_json->saveEmployee($employeeJson);
        $this->assertEquals($endDate, $recordData['contracts'][0]['end_date']);
        
        $employeeJson['contracts'][0]['vacation_days'] = 31;
        try {
            $employeeJson = $this->_json->saveEmployee($employeeJson);
            $this->fail('an exception should be thrown');
        } catch (Exception $e) {
            $this->assertTrue($e instanceof HumanResources_Exception_ContractNotEditable);
        }
    }
    
    /**
     * @see: https://forge.tine20.org/mantisbt/view.php?id=10122
     */
    public function testAlternatingContracts()
    {
        $date = Tinebase_DateTime::now()->setDate(2014, 1, 1)->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        $employee = $this->_getEmployee(Tinebase_Core::getUser()->accountLoginName);
        
        $employee->employment_begin = clone $date;
        
        $contract1 = $this->_getContract();
        $contract1->start_date = clone $date; // 1.1.2014
        $date->addMonth(7)->subDay(1); 
        $contract1->end_date = clone $date; // 31.7.2014 
        $contract1->vacation_days = 27;
        $date->addDay(1); // 1.8.2014
        $contract2 = $this->_getContract();
        $contract2->start_date = clone $date;
        $contract2->vacation_days = 30;
        
        $recordData = $employee->toArray();
        $recordData['contracts'] = array($contract1->toArray(), $contract2->toArray());
        $recordData = $this->_json->saveEmployee($recordData);
        
        $recordData['vacation'] = array(
            array()
        );
        
        $res = $this->_json->searchAccounts(array(
            array('field' => 'year', 'operator' => 'equals', 'value' => '2014')
        ), array());
        
        $account = $res['results'][0];
        $date->subDay(1); // 31.7.2014
        
        $extraFreeTime = HumanResources_Controller_ExtraFreeTime::getInstance()->create(new HumanResources_Model_ExtraFreeTime(array(
            'account_id' => $account['id'],
            'days' => 4,
            'expires' => clone $date,
            'type' => 'payed'
        )));
        
        $res = $this->_json->getFeastAndFreeDays($recordData['id'], 2014);
        
        // at this point, vacation days are not created, so the extra freetime is expired
        $this->assertEquals(28, $res['results']['remainingVacation']);
        
        // create vacation days
        $day = Tinebase_DateTime::now()->setDate(2014, 1, 2)->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        $newFreeTime = array(
            'account_id' => $account['id'],
            'employee_id' => $recordData['id'],
            'type' => 'vacation',
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
        
        // create vacation days
        $day = Tinebase_DateTime::now()->setDate(2014, 6, 10)->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        $newFreeTime = array(
            'account_id' => $account['id'],
            'employee_id' => $recordData['id'],
            'type' => 'vacation',
            'status' => 'ACCEPTED',
            'firstday_date' => $day->toString()
        );
        
        $newFreeTime['freedays'] = array(
            array('duration' => '1', 'date' => $day->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
        );
        
        $newFreeTime['days_count']   = 4;
        $newFreeTime['lastday_date'] = $day->toString();
        
        $this->_json->saveFreeTime($newFreeTime);
        
        
        // create vacation days
        $day = Tinebase_DateTime::now()->setDate(2014, 7, 28)->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        $newFreeTime = array(
            'account_id' => $account['id'],
            'employee_id' => $recordData['id'],
            'type' => 'vacation',
            'status' => 'ACCEPTED',
            'firstday_date' => $day->toString()
        );
        
        $newFreeTime['freedays'] = array(
            array('duration' => '1', 'date' => $day->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
            array('duration' => '1', 'date' => $day->addDay(1)->toString()),
        );
        
        $newFreeTime['days_count']   = 5;
        $newFreeTime['lastday_date'] = $day->toString();
        
        $this->_json->saveFreeTime($newFreeTime);
        
        // create sickness days
        $day = Tinebase_DateTime::now()->setDate(2014, 1, 21)->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        $newFreeTime = array(
            'account_id' => $account['id'],
            'employee_id' => $recordData['id'],
            'type' => 'sickness',
            'status' => "EXCUSED",
            'firstday_date' => $day->toString()
        );
        
        $newFreeTime['freedays'] = array(
                array('duration' => '1', 'date' => $day->toString()),
                array('duration' => '1', 'date' => $day->addDay(1)->toString()),
                array('duration' => '1', 'date' => $day->addDay(1)->toString()),
                array('duration' => '1', 'date' => $day->addDay(1)->toString()),
        );
        
        $day->addDay(2);
        
        $newFreeTime['freedays'][] = array('duration' => '1', 'date' => $day->addDay(1)->toString());
        $newFreeTime['freedays'][] = array('duration' => '1', 'date' => $day->addDay(1)->toString());
        $newFreeTime['freedays'][] = array('duration' => '1', 'date' => $day->addDay(1)->toString());
        $newFreeTime['freedays'][] = array('duration' => '1', 'date' => $day->addDay(1)->toString());
        $newFreeTime['freedays'][] = array('duration' => '1', 'date' => $day->addDay(1)->toString());
        
        $day->addDay(2);
        
        $newFreeTime['freedays'][] = array('duration' => '1', 'date' => $day->addDay(1)->toString());
        $newFreeTime['freedays'][] = array('duration' => '1', 'date' => $day->addDay(1)->toString());
        $newFreeTime['freedays'][] = array('duration' => '1', 'date' => $day->addDay(1)->toString());
        $newFreeTime['freedays'][] = array('duration' => '1', 'date' => $day->addDay(1)->toString());
        $newFreeTime['freedays'][] = array('duration' => '1', 'date' => $day->addDay(1)->toString());
        
        $newFreeTime['days_count']   = 14;
        $newFreeTime['lastday_date'] = $day->toString();
        
        $this->_json->saveFreeTime($newFreeTime);
        
        // create sickness days
        $day = Tinebase_DateTime::now()->setDate(2014, 1, 6)->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        $newFreeTime = array(
                'account_id' => $account['id'],
                'employee_id' => $recordData['id'],
                'type' => 'sickness',
                'status' => "UNEXCUSED",
                'firstday_date' => $day->toString()
        );
        
        $newFreeTime['freedays'] = array(
                array('duration' => '1', 'date' => $day->toString()),
        );
        
        $this->_json->saveFreeTime($newFreeTime);
        
        $res = $this->_json->getFeastAndFreeDays($recordData['id'], 2014);
        
        // at this point the extra freetime has been taken and is not expired
        // 28 + 4 - 11 = 21
        $this->assertEquals(21, $res['results']['remainingVacation']);
        
        $account = $this->_json->getAccount($account['id']);
        
        $this->assertEquals(32, $account['possible_vacation_days']);
        $this->assertEquals(0, $account['expired_vacation_days']);
        $this->assertEquals(21, $account['remaining_vacation_days']);
        $this->assertEquals(11, $account['taken_vacation_days']);
        $this->assertEquals(14, $account['excused_sickness']);
        $this->assertEquals(1, $account['unexcused_sickness']);
    }
    
    /**
     * @see: https://forge.tine20.org/mantisbt/view.php?id=10176
     */
    public function testSavingRelatedRecord()
    {
        $date = new Tinebase_DateTime();
        $e = $this->_getEmployee();
        $c = $this->_getContract($date);
        // in fe the record gets an id, to allow stores crud and sort actions. this must be set to a 40 length ssha key
        $c->id = '1234567890';
        
        $employeeJson = $e->toArray();
        $employeeJson['contracts'] = array($c->toArray());
        
        $employeeJson = $this->_json->saveEmployee($employeeJson);
        
        $id = $employeeJson['contracts'][0]['id'];
        // the id should be set to a 40 length ssha key
        $this->assertEquals(40, strlen($id));
        
        $employeeJson = $this->_json->saveEmployee($employeeJson);
        $this->assertEquals($id, $employeeJson['contracts'][0]['id']);
    }

    public function testDailyWtReportApi($delete = true)
    {
        $e = HumanResources_Controller_Employee::getInstance()->create($this->_getEmployee());
        $mwtr = HumanResources_Controller_MonthlyWTReport::getInstance()->create(new HumanResources_Model_MonthlyWTReport([
            HumanResources_Model_MonthlyWTReport::FLDS_MONTH => '2018-08',
            HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID => $e->getId(),
        ]));
        $dwtr = HumanResources_Controller_DailyWTReport::getInstance()->create(new HumanResources_Model_DailyWTReport([
            'employee_id' => $e->getId(),
            'monthlywtreport' => $mwtr->getId(),
            'date' => '2018-08-01',
        ]));
        return $this->_testSimpleRecordApi(
            'DailyWTReport',
            null,
            null,
            $delete,
            [
                'id' => $dwtr->getId(),
                'employee_id' => $e->toArray(),
                'monthlywtreport' => $mwtr->toArray(),
                'date' => '2018-08-01',
            ],
            false
        );
    }

    /**
     * disallow to edit clearance
     */
    public function testUpdateClearedDailyWtReport()
    {
        $report = $this->testDailyWtReportApi(false);
        $report['is_cleared'] = 1;
        $saved_report = $this->_json->saveDailyWtReport($report);
        static::assertFalse((bool) $saved_report['is_cleared'], 'is_cleared should not be set');
    }

    /**
     * @see: https://forge.tine20.org/mantisbt/view.php?id=10176
     */
    public function testSavingRelatedRecordWithCorruptId()
    {
        $date = new Tinebase_DateTime();
        $e = $this->_getEmployee();
        $c = $this->_getContract($date);
        $c->id = '1234567890';
    
        $employeeJson = $e->toArray();
        $employeeJson = $this->_json->saveEmployee($employeeJson);
    
        $c->employee_id = $employeeJson['id'];
        
        $c = HumanResources_Controller_Contract::getInstance()->create($c);
        $this->assertEquals('1234567890', $c->getId());

        $employeeJson['contracts'] = array($c->toArray());
        $employeeJson = $this->_json->saveEmployee($employeeJson);
        
        // if it has been corrupted before this change was committed, the corrupted id should stay
        $this->assertEquals('1234567890', $employeeJson['contracts'][0]['id']);
    }
}
