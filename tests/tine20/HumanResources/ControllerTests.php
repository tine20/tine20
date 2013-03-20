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

        $this->setExpectedException('Tinebase_Exception_Data');

        $contract2->feast_calendar_id = $newCalendar->getId();
        $contract2 = $contractController->update($contract2);

        // no more assertions here!
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
        $employeeController = HumanResources_Controller_Employee::getInstance();
        
        $employee1 = $this->_getEmployee('pwulf');
        $employee1 = $employeeController->create($employee1);
        
        $employee2 = $this->_getEmployee('rwright');
        $employee2 = $employeeController->create($employee2);
        
        $filter = new HumanResources_Model_EmployeeFilter(array(
            array('field' => 'n_given', 'operator' => 'equals', 'value' => 'Paul')
        ));
        $result = $employeeController->search($filter);
        
        $this->assertEquals(1, $result->count());
        $this->assertEquals('Paul', $result->getFirstRecord()->n_given);
    }
}
