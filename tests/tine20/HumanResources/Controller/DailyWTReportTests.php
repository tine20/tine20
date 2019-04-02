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
    protected $_ts;

    public function testCalculateAllReports()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        HumanResources_Config::getInstance()->{Tinebase_Config::ENABLED_FEATURES}
            ->{HumanResources_Config::FEATURE_CALCULATE_DAILY_REPORTS} = true;

        // create employee & contract
        // @todo generalize?
        $employee = $this->_getEmployee(Tinebase_Core::getUser()->accountLoginName);
        $employee->dfcom_id = '36118993923739652';

        $contractController = HumanResources_Controller_Contract::getInstance();
        $employeeController = HumanResources_Controller_Employee::getInstance();
        $employee = $employeeController->create($employee, false);
        $contract = $this->_getContract(new Tinebase_DateTime('2018-07-01 00:00:00'));
        $contract->employee_id = $employee->getId();
        //  @todo add more contract properties ?
        $contractController->create($contract);

        $this->_createTimesheets();

        static::assertSame(true, HumanResources_Controller_DailyWTReport::getInstance()->calculateAllReports());

        $days = (int)Tinebase_Model_Filter_Date::getFirstDayOf(Tinebase_Model_Filter_Date::MONTH_LAST)->format('t');
        $days += (int)Tinebase_Model_Filter_Date::getEndOfYesterday()->format('j');

        $reportResult = HumanResources_Controller_DailyWTReport::getInstance()->lastReportCalculationResult;
        static::assertCount(1, $reportResult, 'expect reports being generated for one employee');
        $reportResult = current($reportResult);
        static::assertSame(0, $reportResult['updated']);
        static::assertSame(0, $reportResult['errors']);
        static::assertSame($days, $reportResult['created']);
    }

    public function testCalculateReportsForEmployeeTimesheetsWithStartAndEnd()
    {
        // create employee & contract
        // @todo generalize?
        $employee = $this->_getEmployee(Tinebase_Core::getUser()->accountLoginName);
        $employee->dfcom_id = '36118993923739652';

        $contractController = HumanResources_Controller_Contract::getInstance();
        $employeeController = HumanResources_Controller_Employee::getInstance();
        $employee = $employeeController->create($employee, false);
        $contract = $this->_getContract(new Tinebase_DateTime('2018-07-01 00:00:00'));
        $contract->employee_id = $employee->getId();
        //  @todo add more contract properties ?
        $contractController->create($contract);

        $this->_createTimesheets();

        // create report
        $start = new Tinebase_DateTime('2018-08-01 00:00:00');
        $end = new Tinebase_DateTime('2018-08-31 23:59:59');
        $calcResult = HumanResources_Controller_DailyWTReport::getInstance()->calculateReportsForEmployee($employee, $start, $end);

        // assert!
        self::assertGreaterThanOrEqual(3, $calcResult['created'], print_r($calcResult, true));
        self::assertGreaterThanOrEqual(0, $calcResult['updated'], print_r($calcResult, true));
        self::assertEquals(0, $calcResult['errors']);

        $result = $this->_getReportsForEmployee($employee);
        self::assertGreaterThanOrEqual(3, count($result), 'should have more than (or equal) 3 daily reports');

        // check times
        foreach ([
                    '2018-08-02 02:00:00' => 6 * 3600 - 1800,
                    '2018-08-06 02:00:00' => 5 * 3600 - 1800,
                    '2018-08-07 02:00:00' => 5 * 3600 - 1800,
                    '2018-08-08 02:00:00' => 2 * 3600,
                 ] as $day => $workTime) {
            $report = $result->find('date', $day);
            self::assertNotNull($report);
            self::assertEquals($workTime, $report->working_time_actual);
            // @todo add more assertions (absence_time*, evaluation_period*, break_time*, working_time_target, working_time_correction, ...)
        }

        $jsonFE = new HumanResources_Frontend_Json();
        $saved_report = $jsonFE->getDailyWtReport($result->find('date', $day)->getId());
        static::assertTrue(is_array($saved_report['working_times'][0]['wage_type']), 'wage_type in working times is not resolved');


        return $employee;
    }

    protected function _getReportsForEmployee($employee)
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_DailyWTReport::class, [
            ['field' => 'employee_id', 'operator' => 'in', 'value' => [$employee->getId()]]
        ]);
        return HumanResources_Controller_DailyWTReport::getInstance()->search($filter);
    }

    public function testCalculateReportsForEmployeeTimesheetsWithStartAndEndUpdate()
    {
        $employee = $this->testCalculateReportsForEmployeeTimesheetsWithStartAndEnd();

        // add a new TS
        /** @var Timetracker_Model_Timesheet $ts */
        $ts = $this->_ts->filter('description', 'Probe')->getFirstRecord();
        unset($ts->id);
        $ts->start_time = '16:00:00';
        $ts->end_time = '16:15:00';
        $ts->duration = '15';
        Timetracker_Controller_Timesheet::getInstance()->create($ts);

        $start = new Tinebase_DateTime('2018-08-01 00:00:00');
        $end = new Tinebase_DateTime('2018-08-31 23:59:59');
        $calcResult = HumanResources_Controller_DailyWTReport::getInstance()->calculateReportsForEmployee($employee, $start, $end);
        self::assertGreaterThanOrEqual(0, $calcResult['created'], print_r($calcResult, true));
        self::assertGreaterThanOrEqual(1, $calcResult['updated'], print_r($calcResult, true));
        self::assertEquals(0, $calcResult['errors']);

        $result = $this->_getReportsForEmployee($employee);
        self::assertGreaterThanOrEqual(3, count($result), 'should have more than (or equal) 3 daily reports');

        // check times
        foreach ([
                     '2018-08-02 02:00:00' => 6 * 3600 - 1800,
                     '2018-08-06 02:00:00' => 5 * 3600 - 1800,
                     '2018-08-07 02:00:00' => 5 * 3600 - 1800,
                     '2018-08-08 02:00:00' => 2 * 3600,
                 ] as $day => $workTime) {
            $report = $result->filter('date', $day)->getFirstRecord();
            if ($report->date->format('Y-m-d') === $ts->start_date) {
                $workTime += 900;
            }
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
        $this->_ts = $importer->testImportDemoData();
    }
}
