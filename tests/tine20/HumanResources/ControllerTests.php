<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for HumanResources Controller(s)
 */
class HumanResources_ControllerTests extends HumanResources_TestCase
{
    /**
     * tests for the contract controller
     */
    public function testUpdateContract()
    {
        $contractController = HumanResources_Controller_Contract::getInstance();
        $employeeController = HumanResources_Controller_Employee::getInstance();
        $contractBackend = new HumanResources_Backend_Contract();

        $employee = $employeeController->create($this->_getEmployee('sclever'));

        $now = new Tinebase_DateTime();

        $inAMonth = clone $now;
        $inAMonth->addMonth(1);

        $threeHrAgo = clone $now;
        $threeHrAgo->subHour(3);

        $startDate1 = clone $now;
        $startDate1->subMonth(2);

        $startDate2 = clone $now;
        $startDate2->subMonth(1);

        $edate1 = clone $startDate2;
        $edate1->addYear(1);
        
        // contract1 in the past, but created a second ago
        $contract1 = $this->_getContract();
        $contract1->employee_id = $employee->getId();
        $contract1->start_date = $startDate1;
        $contract1->creation_time = $now;
        $contract1 = $contractBackend->create($contract1);

        $contract2 = $this->_getContract();
        $contract2->employee_id = $employee->getId();
        $contract2->start_date    = $startDate2;
        $contract2->end_date = $edate1;
        $contract2 = $contractBackend->create($contract2);
 
        // account

        $accountInstance = HumanResources_Controller_Account::getInstance();
        $accountInstance->createMissingAccounts();

        $accountFilter = new HumanResources_Model_AccountFilter(array(
            array('field' => 'year', 'operator' => 'equals', 'value' => $now->format('Y'))
        ));

        $accountFilter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'employee_id', 'operator' => 'equals', 'value' => $employee->getId())
        ));
        $myAccount = $accountInstance->search($accountFilter)->getFirstRecord();

        $firstDayDate = clone $startDate2;
        $firstDayDate->addDay(3);

        $vacation = new HumanResources_Model_FreeTime(array(
            'status'        => 'ACCEPTED',
            'employee_id'   => $employee->getId(),
            'account_id'    => $myAccount->getId(),
            'type'          => 'vacation',
            'freedays'      => array(
                array('date' => $firstDayDate, 'duration' => 1)
            )
        ));

        $vacation = HumanResources_Controller_FreeTime::getInstance()->create($vacation);
        
        $newCalendar = $this->_getFeastCalendar(true);

        // LAST ASSERTION, do not add assertions after an expected Exception, they won't be executed

        $this->setExpectedException('HumanResources_Exception_ContractNotEditable');

        $contract2->feast_calendar_id = $newCalendar->getId();
        $contract2 = $contractController->update($contract2);

        // no more assertions here!
    }

    /**
     * some contract tests (more in jsontests)
     */
    public function testContract()
    {
        $sdate = new Tinebase_DateTime('2013-01-01 00:00:00');
        $employee = $this->_getEmployee('rwright');
        
        $contractController = HumanResources_Controller_Contract::getInstance();
        $employeeController = HumanResources_Controller_Employee::getInstance();
        $employee = $employeeController->create($employee);
        $contract = $this->_getContract($sdate);
        $contract->workingtime_json = '{"days": [8,8,8,8,8,0,0]}';

        // create feast days
        $feastDays2013 = array(
            // two days after another in one date
            array('2013-12-25', '2013-12-26'),
            // a whole day event
            array('2013-04-01'),
            // normal dates
            '2013-05-01', '2013-05-09', '2013-05-20', '2013-10-03', '2013-01-01', '2013-03-29' , '2013-12-31',
            // additional date which has been accidentially inserted by the user (test filters in getFeastDays)
            '2009-12-31'
        );

        $feastCalendar = $this->_getFeastCalendar();
        $contract->feast_calendar_id = $feastCalendar->getId();

        foreach($feastDays2013 as $day) {
            if (is_array($day)) {
                $date = array();
                foreach($day as $dayQ) {
                    $date[] = new Tinebase_DateTime($dayQ . ' 06:00:00');
                }
            } else {
                $date = new Tinebase_DateTime($day . ' 06:00:00');
            }

            $this->_createFeastDay($date);
        }

        // test "calculateVacationDays"
        $start  = new Tinebase_DateTime('2013-01-01');
        $stop   = new Tinebase_DateTime('2013-12-31');

        $contract->start_date = $start;
        $contract->end_date   = $stop;

        $this->assertEquals(30, $contractController->calculateVacationDays($contract, $start, $stop));

        $newStartDate = new Tinebase_DateTime('2013-07-01');
        $contract->start_date = $newStartDate;
        
        $contract->employee_id = $employee->getId();
        
        $contractController->create($contract);
        $this->assertEquals(15, round($contractController->calculateVacationDays($contract, $start, $stop), 0));

        // test "getDatesToWorkOn"
        $contract->start_date = $start;

        // 2013 has 365 days, 52 Saturdays and 52 Sundays, all of the 10 feast days are at working days (a good year for an employee!)
        // so we expect 365-52-52-10 = 251 days
        $workingDates = $contractController->getDatesToWorkOn($contract, $start, $stop);
        $this->assertEquals(251, count($workingDates['results']));
        
        
        // test $respectTakenVacationDays parameter of getDatesToWorkOn 
        $accountController = HumanResources_Controller_Account::getInstance();
        $accounts = $accountController->createMissingAccounts(2013, $contract->employee_id);
        
        $account = $accounts->getFirstRecord();
        
        $refDate = clone $newStartDate;
        // get a monday
        $refDate->addWeek(1)->addDay(1);
        // now add 3 vacation days
        $freetime = array(
            'account_id' => $account->getId(),
            'employee_id' => $contract->employee_id,
            'type' => 'vacation',
            'status' => 'ACCEPTED',
            'firstday_date' => $refDate->toString()
        );
        
        $freetime['freedays'] = array(
            array('duration' => '1', 'date' => $refDate->toString()),
            array('duration' => '1', 'date' => $refDate->addDay(1)->toString()),
            array('duration' => '1', 'date' => $refDate->addDay(1)->toString()),
        );
        
        $json = new HumanResources_Frontend_Json();
        $freetime = $json ->saveFreeTime($freetime);
        
        $workingDates = $contractController->getDatesToWorkOn($contract, $start, $stop, TRUE);
        $this->assertEquals(248, count($workingDates['results']));
        
        // test "getFeastDays"
        $feastDays = $contractController->getFeastDays($contract, $start, $stop);

        // we expect 10 here
        $this->assertEquals(10, count($feastDays), '10 feast days should have been found!');
    }

    /**
     * tests if a special property exists in the record set
     */
    public function testRecordSet()
    {
        $recordSet = new Tinebase_Record_RecordSet('HumanResources_Model_Employee');
        $employee = $this->_getEmployee();
        $recordSet->addRecord($employee);
        $this->assertEquals(1, count($recordSet->supervisor_id));
    }

    /**
     * tests if the filter for the employee model gets created properly
     */
    public function testFilters()
    {

        // prepare dates
        $today = new Tinebase_DateTime();
        $oneMonthAgo = clone $today;
        $oneMonthAgo->subMonth(1);
        $oneMonthAhead = clone $today;
        $oneMonthAhead->addMonth(1);
        $twoMonthsAgo = clone $oneMonthAgo;
        $twoMonthsAgo->subMonth(1);

        $employeeController = HumanResources_Controller_Employee::getInstance();

        $employee1 = $this->_getEmployee('pwulf');
        $employee1->employment_begin = $oneMonthAgo;
        $employee1->employment_end = $oneMonthAhead;
        $employee1 = $employeeController->create($employee1);

        $employee2 = $this->_getEmployee('rwright');
        $employee2->employment_begin = $oneMonthAgo;
        $employee2 = $employeeController->create($employee2);

        $filter = new HumanResources_Model_EmployeeFilter(array(
            array('field' => 'n_given', 'operator' => 'equals', 'value' => 'Paul')
        ));
        $result = $employeeController->search($filter);

        $this->assertEquals(1, $result->count());
        $this->assertEquals('Paul', $result->getFirstRecord()->n_given);

        // test employed filter

        // employee3 is not yet employed
        $employee3 = $this->_getEmployee('jmcblack');
        $employee3->employment_begin = $oneMonthAhead;
        $employee3 = $employeeController->create($employee3);
        
        // employee4 has been employed
        $employee4 = $this->_getEmployee('jsmith');
        $employee4->employment_begin = $twoMonthsAgo;
        $employee4->employment_end = $oneMonthAgo;
        $employee4 = $employeeController->create($employee4);

        $this->assertEquals('Photographer', $employee4->position);
        
        $filter = new HumanResources_Model_EmployeeFilter(array(
            array('field' => 'is_employed', 'operator' => 'equals', 'value' => TRUE)
        ));
        $result = $employeeController->search($filter);
        $msg = 'rwright and pwulf should have been found';
        $this->assertEquals(2, $result->count(), $msg);

        $names = $result->n_fn;

        // just rwright and pwulf should have been found
        $this->assertContains('Roberta Wright', $names, $msg);
        $this->assertContains('Paul Wulf', $names, $msg);

        $filter = new HumanResources_Model_EmployeeFilter(array(
            array('field' => 'is_employed', 'operator' => 'equals', 'value' => FALSE)
        ));
        $result = $employeeController->search($filter);

        $msg = 'jsmith and jmcblack should have been found';
        $this->assertEquals(2, $result->count(), $msg);

        $names = $result->n_fn;

        // just jsmith and jmcblack should have been found
        $this->assertContains('John Smith', $names, $msg);
        $this->assertContains('James McBlack', $names, $msg);
    }
}
