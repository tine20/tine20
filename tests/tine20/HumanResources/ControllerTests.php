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

        $employee = $employeeController->create($this->_getEmployee('unittest'));

        $now = new Tinebase_DateTime();

        $inAMonth = clone $now;
        $inAMonth->addMonth(1);

        $threeHrAgo = clone $now;
        $threeHrAgo->subHour(3);

        $startDate1 = clone $now;
        $startDate1->subMonth(2);

        $startDate2 = clone $now;
        $startDate2->subMonth(1);

        // contract1 in the past, but created a second ago
        $contract1 = $this->_getContract();
        $contract1->employee_id = $employee->getId();
        $contract1->start_date = $startDate1;
        $contract1->creation_time = $now;
        $contract1 = $contractBackend->create($contract1);

        // contract2 created more than 2 hrs ago, start date is in the past. update must fail
        $contract2 = $this->_getContract();
        $contract2->employee_id = $employee->getId();
        $contract2->creation_time = $threeHrAgo;
        $contract2->start_date    = $startDate2;
        $contract2 = $contractBackend->create($contract2);
        
        // contract3 created more than 3 hrs ago, but start date is in the future
        $contract3 = $this->_getContract();
        $contract3->employee_id = $employee->getId();
        $contract3->creation_time = $threeHrAgo;
        $contract3->start_date    = $inAMonth;
        $contract3 =  $contractBackend->create($contract3);

        // change calendar an update
        $newCalendar = $this->_getFeastCalendar(true);

        // no error should occur, the creation time is not older than 2 hours
        $contract1->feast_calendar_id = $newCalendar->getId();
        $contract1 = $contractController->update($contract1);

        // no error should occur, the start_date is in the future
        $contract3->feast_calendar_id = $newCalendar->getId();
        $contract3 = $contractController->update($contract3);

        // LAST ASSERTION, do not add assertions after a expected Exception, they won't be executed

        $this->setExpectedException('HumanResources_Exception_ContractTooOld');

        $contract2->feast_calendar_id = $newCalendar->getId();
        $contract2 = $contractController->update($contract2);
    
        // no more assertions here!
    }
    
    /**
     * some contract tests (more in jsontests)
     */
    public function testContract()
    {
        $contractController = HumanResources_Controller_Contract::getInstance();
        $contract = $this->_getContract();
        $contract->workingtime_json = '{"days": [8,8,8,8,8,0,0]}';
        
        // create feast days
        $feastDays2013 = array(
            '2013-01-01', '2013-03-29', '2013-04-01', '2013-05-01', '2013-05-09',
            '2013-05-20', '2013-10-03', '2013-12-25', '2013-12-26', '2013-12-31'
        );
        
        $feastCalendar = $this->_getFeastCalendar();
        $contract->feast_calendar_id = $feastCalendar->getId();
        
        foreach($feastDays2013 as $day) {
            $date = new Tinebase_DateTime($day . ' 12:00:00');
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
        
        $this->assertEquals(15, $contractController->calculateVacationDays($contract, $start, $stop));
        
        // test "getDatesToWorkOn"
        $contract->start_date = $start;
        
        // 2013 has 365 days, 52 Saturdays and 52 Sundays, all of the 10 feast days are at working days (a good year for an employee!)
        // so we expect 365-52-52-10 = 251 days
        $workingDates = $contractController->getDatesToWorkOn($contract, $start, $stop);
        $this->assertEquals(251, count($workingDates['results']));
        
        // test "getFeastDays"
        $feastDays = $contractController->getFeastDays($contract, $start, $stop);
        
        // we expect 10 here
        $this->assertEquals(10, count($feastDays));
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
