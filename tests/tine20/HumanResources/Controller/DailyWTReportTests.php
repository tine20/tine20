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

        $this->_createBasicData();

        $this->_createTimesheets();

        static::assertSame(true, HumanResources_Controller_DailyWTReport::getInstance()->calculateAllReports());

        $days = Tinebase_DateTime::now()->diff($this->employee->contracts->getFirstRecord()->start_date, true)->days;

        $reportResult = HumanResources_Controller_DailyWTReport::getInstance()->lastReportCalculationResult;
        static::assertCount(1, $reportResult, 'expect reports being generated for one employee');
        $reportResult = current($reportResult);
        static::assertSame(0, $reportResult['updated']);
        static::assertSame(0, $reportResult['errors'], print_r($reportResult, true));
        static::assertSame($days, $reportResult['created']);

        $feJson = new HumanResources_Frontend_Json();
        $reportDaily = $feJson->searchDailyWTReports([
            ['field' => 'employee_id', 'operator' => 'in', 'value' => [$this->employee->getId()]]
        ], null);
        $this->assertArrayHasKey('results', $reportDaily);
        $this->assertArrayHasKey(0, $reportDaily['results']);
        $this->assertArrayHasKey('employee_id', $reportDaily['results'][0]);
        $this->assertArrayHasKey('id', $reportDaily['results'][0]['employee_id']);
        $reportMonthly = $feJson->searchMonthlyWTReports([
            ['field' => 'employee_id', 'operator' => 'in', 'value' => [$this->employee->getId()]]
        ], null);
        $this->assertArrayHasKey('results', $reportMonthly);
        $this->assertArrayHasKey(0, $reportMonthly['results']);
        $this->assertArrayHasKey('employee_id', $reportMonthly['results'][0]);
        $this->assertArrayHasKey('id', $reportMonthly['results'][0]['employee_id']);

        HumanResources_Controller_Employee::getInstance()->delete($this->employee->getId());

        $reportDailyDeleted = $feJson->searchDailyWTReports([
            ['field' => 'employee_id', 'operator' => 'in', 'value' => [$this->employee->getId()]]
        ], null);
        $this->assertArrayHasKey('results', $reportDailyDeleted);
        $this->assertArrayHasKey(0, $reportDailyDeleted['results']);
        $this->assertArrayHasKey('employee_id', $reportDailyDeleted['results'][0]);
        $this->assertArrayHasKey('id', $reportDailyDeleted['results'][0]['employee_id']);
        $reportMonthlyDeleted = $feJson->searchMonthlyWTReports([
            ['field' => 'employee_id', 'operator' => 'in', 'value' => [$this->employee->getId()]]
        ], null);
        $this->assertArrayHasKey('results', $reportMonthlyDeleted);
        $this->assertArrayHasKey(0, $reportMonthlyDeleted['results']);
        $this->assertArrayHasKey('employee_id', $reportMonthlyDeleted['results'][0]);
        $this->assertArrayHasKey('id', $reportMonthlyDeleted['results'][0]['employee_id']);
    }

    public function testUpdateDailyReport()
    {
        $this->_createBasicData();

        $this->_createTimesheets();

        // create report
        $start = new Tinebase_DateTime('2018-08-01 00:00:00');
        $end = new Tinebase_DateTime('2018-08-31 23:59:59');
        HumanResources_Controller_DailyWTReport::getInstance()->calculateReportsForEmployee($this->employee, $start, $end);

        $result = $this->_getReportsForEmployee($this->employee);
        /** @var HumanResources_Model_DailyWTReport $report */
        $report = $result->find('date', '2018-08-08 00:00:00');
        self::assertEquals(2 * 3600, $report->working_time_actual);

        HumanResources_Controller_DailyWTReport::getInstance()->calculateReportsForEmployee(
            HumanResources_Controller_Employee::getInstance()->get($report->employee_id), new Tinebase_DateTime('2018-08-08 00:00:00'),
            new Tinebase_DateTime('2018-08-08 00:00:00'));

        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(get_class($report), $report->getId(), 'Sql', false);
        // only one created note
        $this->assertCount(1, $notes);

        $ts = clone $this->_ts->filter('description', 'Gießen der Pflanzen')->getFirstRecord();
        unset($ts->id);
        $ts->start_time = '10:00:00';
        $ts->end_time = '12:00:00';
        Timetracker_Controller_Timesheet::getInstance()->create($ts);

        $ts = clone $this->_ts->filter('description', 'Gießen der Pflanzen')->getFirstRecord();
        unset($ts->id);
        $ts->start_time = '17:30:00';
        $ts->end_time = '19:30:00';
        Timetracker_Controller_Timesheet::getInstance()->create($ts);

        $report->working_time_target_correction = 3600;
        HumanResources_Controller_DailyWTReport::getInstance()->update($report);
        $result = $this->_getReportsForEmployee($this->employee);
        /** @var HumanResources_Model_DailyWTReport $report */
        $updatedReport = $result->find('date', '2018-08-08 00:00:00');
        self::assertEquals(6 * 3600, $updatedReport->working_time_actual);
        self::assertNotEquals($report->working_time_actual, $updatedReport->working_time_actual);

        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(get_class($report), $report->getId(), 'Sql', false);
        // update notes too now, one working time correction, one recalc
        $this->assertCount(3, $notes);
        $note = $notes->find('seq', 3);
        $this->assertNotNull($note, 'recalc note not found');
        $added = Tinebase_Translation::getTranslation(Tinebase_Config::APP_NAME, Tinebase_Core::getLocale())->_('added');
        $wt = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME, Tinebase_Core::getLocale())
            ->_('working_times');
        $this->assertStringContainsString(
            $wt . ' (2 ' . $added . ': 02:00 (10:00 - 12:00) - , 02:00 (17:30 - 19:30) - )', $note->note);
    }

    public function testCalculateReportsForEmployeeTimesheetsWithStartAndEnd()
    {
        $this->_createBasicData();

        $this->_createTimesheets();

        // create report
        $start = new Tinebase_DateTime('2018-08-01 00:00:00');
        $end = new Tinebase_DateTime('2018-08-31 23:59:59');
        $calcResult = HumanResources_Controller_DailyWTReport::getInstance()->calculateReportsForEmployee($this->employee, $start, $end);

        // assert!
        self::assertGreaterThanOrEqual(3, $calcResult['created'], print_r($calcResult, true));
        self::assertGreaterThanOrEqual(0, $calcResult['updated'], print_r($calcResult, true));
        self::assertEquals(0, $calcResult['errors']);

        $result = $this->_getReportsForEmployee($this->employee);
        self::assertGreaterThanOrEqual(3, count($result), 'should have more than (or equal) 3 daily reports');

        // check times
        foreach ([
                    '2018-08-02 00:00:00' => 6 * 3600 - 1800,
                    '2018-08-06 00:00:00' => 5 * 3600 - 1800,
                    '2018-08-07 00:00:00' => 5 * 3600 - 1800,
                    '2018-08-08 00:00:00' => 2 * 3600,
                 ] as $day => $workTime) {
            $report = $result->find('date', $day);
            self::assertNotNull($report);
            self::assertEquals($workTime, $report->working_time_actual);
            // @todo add more assertions (absence_time*, evaluation_period*, break_time*, working_time_target, working_time_correction, ...)
        }

        $jsonFE = new HumanResources_Frontend_Json();
        $saved_report = $jsonFE->getDailyWtReport($result->find('date', $day)->getId());
        static::assertTrue(is_array($saved_report['working_times'][0]['wage_type']), 'wage_type in working times is not resolved');


        return $this->employee;
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
        $ts = clone $this->_ts->filter('description', 'Probe')->getFirstRecord();
        unset($ts->id);
        $ts->start_date = '2018-08-01';
        $ts->start_time = '05:51:53';
        $ts->end_time = '15:30:23';
        $ts->duration = (string)(9*60 + 38);
        Timetracker_Controller_Timesheet::getInstance()->create($ts);

        // add a new TS
        /** @var Timetracker_Model_Timesheet $ts */
        $ts = clone $this->_ts->filter('description', 'Probe')->getFirstRecord();
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
                     '2018-08-02 00:00:00' => 6 * 3600 - 1800,
                     '2018-08-06 00:00:00' => 5 * 3600 - 1800,
                     '2018-08-07 00:00:00' => 5 * 3600 - 1800,
                     '2018-08-08 00:00:00' => 2 * 3600,
                 ] as $day => $workTime) {
            $report = $result->filter('date', $day)->getFirstRecord();

            self::assertNotNull($report);
            if ($report->date->format('Y-m-d') === $ts->start_date) {
                $workTime += 900;
                $this->assertEquals(5400, $report->break_time_net); // 30 min forced, 60 min natural
                $this->assertEquals(1800, $report->break_time_deduction); // 30 min forced
            }
            self::assertEquals($workTime, $report->working_time_actual, $report->date);
            // @todo add more assertions (absence_time*, evaluation_period*, break_time*, working_time_target, working_time_correction, ...)
        }
    }

    public function testCalculateReportsForEmployeeTimesheetsWithoutStartAndEnd()
    {
        // @todo implement
    }

    public function testCalculateReportsForEmployeeFeast()
    {
        $this->_createBasicData();

        $start = new Tinebase_DateTime('2018-08-01 00:00:00');

        $contract = $this->employee->getValidContract($start);
        Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event([
            'summary' => 'unittest feast',
            'container_id' => $contract->feast_calendar_id,
            'dtstart' => '2010-08-01 00:00:00',
            'dtend' => '2010-08-01 23:59:59',
            'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=1'
        ]));

        $end = new Tinebase_DateTime('2018-08-01 23:59:59');
        $calcResult = HumanResources_Controller_DailyWTReport::getInstance()->calculateReportsForEmployee($this->employee, $start, $end);
        self::assertEquals(1, $calcResult['created'], print_r($calcResult, true));
        self::assertEquals(0, $calcResult['updated'], print_r($calcResult, true));
        self::assertEquals(0, $calcResult['errors'], print_r($calcResult, true));

        $result = $this->_getReportsForEmployee($this->employee);
        self::assertEquals(1, count($result), 'should have 1 daily report');
        /** @var HumanResources_Model_DailyWTReport $result */
        $result = $result->getFirstRecord();
        self::assertCount(1, $result->working_times);
        self::assertEquals(8 * 3600, $result->working_times->getFirstRecord()->duration);
        self::assertEquals(8 * 3600, $result->working_time_total);
        self::assertEquals(HumanResources_Model_WageType::ID_FEAST, $result->working_times->getFirstRecord()->wage_type);
        self::assertEquals('unittest feast', $result->system_remark);
    }

    public function testCalculateReportsForEmployeeVacation()
    {
        $this->_createBasicData();

        HumanResources_Controller_FreeTime::getInstance()->create(
            new HumanResources_Model_FreeTime([
                'employee_id'       => $this->employee->getId(),
                'account_id'        => $this->employee->account_id,
                'type'              => HumanResources_Model_FreeTimeType::ID_VACATION,
                HumanResources_Model_FreeTime::FLD_PROCESS_STATUS => HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED,
                'freedays'          => [
                    ['date' => '2018-08-01']
                ]
            ])
        );

        $start = new Tinebase_DateTime('2018-08-01 00:00:00');
        $end = new Tinebase_DateTime('2018-08-01 23:59:59');
        $calcResult = HumanResources_Controller_DailyWTReport::getInstance()->calculateReportsForEmployee($this->employee, $start, $end);
        self::assertEquals(1, $calcResult['created'], print_r($calcResult, true));
        self::assertEquals(0, $calcResult['updated'], print_r($calcResult, true));
        self::assertEquals(0, $calcResult['errors'], print_r($calcResult, true));

        $result = $this->_getReportsForEmployee($this->employee);
        self::assertEquals(1, count($result), 'should have 1 daily report');
        $result = $result->getFirstRecord();
        self::assertCount(1, $result->working_times);
        self::assertEquals(8 * 3600, $result->working_times->getFirstRecord()->duration);
        self::assertEquals(8 * 3600, $result->working_time_total);
        self::assertEquals(HumanResources_Model_WageType::ID_VACATION, $result->working_times->getFirstRecord()->wage_type);
        self::assertEquals(HumanResources_Controller_WageType::getInstance()->get(
            HumanResources_Model_WageType::ID_VACATION)->name, $result->system_remark);
    }


    public function testCalculateReportsForEmployeeSickness()
    {
        $this->_createBasicData();

        HumanResources_Controller_FreeTime::getInstance()->create(
            new HumanResources_Model_FreeTime([
                'employee_id'       => $this->employee->getId(),
                'account_id'        => $this->employee->account_id,
                'type'              => HumanResources_Model_FreeTimeType::ID_SICKNESS,
                HumanResources_Model_FreeTime::FLD_PROCESS_STATUS => HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED,
                'freedays'          => [
                    ['date' => '2018-08-01']
                ]
            ])
        );

        $start = new Tinebase_DateTime('2018-08-01 00:00:00');
        $end = new Tinebase_DateTime('2018-08-01 23:59:59');
        $calcResult = HumanResources_Controller_DailyWTReport::getInstance()->calculateReportsForEmployee($this->employee, $start, $end);
        self::assertEquals(1, $calcResult['created'], print_r($calcResult, true));
        self::assertEquals(0, $calcResult['updated'], print_r($calcResult, true));
        self::assertEquals(0, $calcResult['errors'], print_r($calcResult, true));

        $result = $this->_getReportsForEmployee($this->employee);
        self::assertEquals(1, count($result), 'should have 1 daily report');
        $result = $result->getFirstRecord();
        self::assertCount(1, $result->working_times);
        self::assertEquals(8 * 3600, $result->working_times->getFirstRecord()->duration);
        self::assertEquals(HumanResources_Model_WageType::ID_SICK, $result->working_times->getFirstRecord()->wage_type);
    }

    protected function _createTimesheets()
    {
        // use TS importer (also creates TAs)
        $importer = new Timetracker_Import_TimesheetTest();
        $this->_ts = $importer->testImportDemoData([
            Tinebase_Setup_DemoData_Import::IMPORT_DIR => __DIR__,
            'demoData' => false,
        ]);
    }
}
