<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for HumanResources Controller
 */
class HumanResources_Controller_DailyWTReportTests extends HumanResources_TestCase
{
    public function testCalculateReportsForEmployeeTimesheetsWithStartAndEnd()
    {
        // create employee & contract
        // @todo generalize?
        $employee = $this->_getEmployee(Tinebase_Core::getUser()->accountLoginName);
        $employee->dfcom_id = '36118993923739652';

        $contractController = HumanResources_Controller_Contract::getInstance();
        $employeeController = HumanResources_Controller_Employee::getInstance();
        $employee = $employeeController->create($employee, false);
        $contract = $this->_getContract();
        $contract->workingtime_json = '{"days": [8,8,8,8,8,0,0]}';
        $contract->employee_id = $employee->getId();
        //  @todo add more contract properties ?
        $contractController->create($contract);

        $this->_createTimesheets();

        // create report
        $start = new Tinebase_DateTime('2018-08-01 00:00:00');
        $end = new Tinebase_DateTime('2018-08-31 23:59:59');
        $result = HumanResources_Controller_DailyWTReport::getInstance()->calculateReportsForEmployee($employee, $start, $end);

        // assert!
        self::assertGreaterThanOrEqual(3, $result['created'], print_r($result, true));
        self::assertGreaterThanOrEqual(1, $result['updated'], print_r($result, true));
        self::assertEquals(0, $result['errors']);
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_DailyWTReport::class, [
            ['field' => 'employee_id', 'operator' => 'in', 'value' => [$employee->getId()]]
        ]);
        $result = HumanResources_Controller_DailyWTReport::getInstance()->search($filter);
        self::assertGreaterThanOrEqual(3, count($result), 'should have more than (or equal) 3 daily reports');

        // check times
        foreach (['2018-08-02 00:00:00' => 360, '2018-08-08 00:00:00' => 120, '2018-08-07 00:00:00' => 600] as $day => $workTime) {
            $report = $result->filter('date', $day)->getFirstRecord();
            self::assertNotNull($report);
            self::assertEquals($workTime, $report->working_time_actual);
            // @todo add more assertions (absence_time*, evaluation_period*, break_time*, working_time_target, working_time_correction, ...)
        }
    }

    public function testCalculateReportsForEmployeeTimesheetsWithoutStartAndEnd()
    {
        // @todo implement
    }

    public function testCalculateReportsForEmployeeVacation()
    {
        // @todo implement
    }

    public function testCalculateReportsForEmployeeSickness()
    {
        // @todo implement
    }

    protected function _createTimesheets()
    {
        // use TS importer (also creates TAs)
        $importer = new Timetracker_Import_TimesheetTest();
        $importer->testImportDemoData();
    }
}
