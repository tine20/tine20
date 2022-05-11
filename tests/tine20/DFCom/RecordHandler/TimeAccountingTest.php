<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     DFCom
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

require_once 'TestHelper.php';

class DFCom_RecordHandler_TimeAccountingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // have some demo data
        $HRTest = new HumanResources_Controller_ContractTests();
        $HRTest->testContract(true);
    }

    public function testInfo()
    {
        $this->markTestSkipped('not yet ready');
        $event = $this->getTestEvent([
            'deviceRecord' => [
                'data' => [
                    'dateTime' => '2018-10-24T17:43:58',
                    'functionKey' => DFCom_RecordHandler_TimeAccounting::FUNCTION_KEY_INFO,
                ]
            ]
        ]);
        
        // generate mwtr
        HumanResources_Controller_DailyWTReport::getInstance()->calculateReportsForEmployees(
            new Tinebase_Record_RecordSet(HumanResources_Model_Employee::class, 
                HumanResources_Controller_Employee::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                        ['condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_OR, 'filters' => [
                            ['field' => 'dfcom_id', 'operator' => 'equals', 'value' => (int)$event['deviceRecord']->deviceData['cardId']],
                            ['field' => 'dfcom_id', 'operator' => 'equals', 'value' => $event['deviceRecord']->deviceData['cardId']],
                        ]]
        ]))));

        $this->_uit = new DFCom_RecordHandler_TimeAccounting($event);
        $this->_uit->handle();

        $responseBody = $this->_uit->deviceResponse->getHTTPResponse()->getBody();
    }
    
    public function testStartTimesheet()
    {
        $event = $this->getTestEvent();
        $this->_uit = new DFCom_RecordHandler_TimeAccounting($event);
        $this->_uit->handle();

        HumanResources_Controller_AttendanceRecorder::runBLPipes();

        $timesheets = Timetracker_Controller_Timesheet::getInstance()->getAll()
            ->filter('start_time', '09:20:00');
        $this->assertCount(1, $timesheets, 'timesheet not created');
        $timesheet = $timesheets->getFirstRecord();
        $this->assertEquals(0, $timesheet->duration);
    }

    public function testEndTimesheet()
    {
        $this->testStartTimesheet();

        $event = $this->getTestEvent([
            'deviceRecord' => [
                'data' => [
                    'dateTime' => '2018-10-24T12:32:45',
                    'functionKey' => DFCom_RecordHandler_TimeAccounting::FUNCTION_KEY_CLOCKOUT,
                ]
            ]
        ]);
        $this->_uit = new DFCom_RecordHandler_TimeAccounting($event);
        $this->_uit->handle();

        HumanResources_Controller_AttendanceRecorder::runBLPipes();

        $timesheets = Timetracker_Controller_Timesheet::getInstance()->getAll()
            ->filter('start_time', '09:20:00');
        $this->assertCount(1, $timesheets, 'timesheet not created');
        $timesheet = $timesheets->getFirstRecord();
        $this->assertEquals(192, $timesheet->duration);
    }

    public function testConcurrentStart()
    {
        $this->testStartTimesheet();

        $event = $this->getTestEvent([
            'deviceRecord' => [
                'data' => [
                    'dateTime' => '2018-10-24T13:01:22',
                ]
            ]
        ]);

        $this->_uit = new DFCom_RecordHandler_TimeAccounting($event);
        $this->_uit->handle();

        HumanResources_Controller_AttendanceRecorder::runBLPipes();

        $timesheets = Timetracker_Controller_Timesheet::getInstance()->getAll();

        $orphaned = $timesheets
            ->filter('start_time', '09:20:00')
            ->getFirstRecord();

        $this->assertTrue(!!$orphaned->need_for_clarification);
        $this->assertEquals(221, $orphaned->duration);

        $new = $timesheets
            ->filter('start_time', '13:01:00')
            ->getFirstRecord();

        $this->assertEquals(0, $new->duration);
    }

    public function testDeferedStop()
    {
        $this->testConcurrentStart();

        $event = $this->getTestEvent([
            'deviceRecord' => [
                'data' => [
                    'dateTime' => '2018-10-24T17:43:58',
                    'functionKey' => DFCom_RecordHandler_TimeAccounting::FUNCTION_KEY_CLOCKOUT,
                ]
            ]
        ]);

        $this->_uit = new DFCom_RecordHandler_TimeAccounting($event);
        $this->_uit->handle();

        HumanResources_Controller_AttendanceRecorder::runBLPipes();

        $timesheets = Timetracker_Controller_Timesheet::getInstance()->getAll();

        $first = $timesheets
            ->filter('start_time', '09:20:00')
            ->getFirstRecord();

        $this->assertTrue(!!$first->need_for_clarification);
        $this->assertEquals(221, $first->duration);

        $second = $timesheets
            ->filter('start_time', '13:01:00')
            ->getFirstRecord();

        $this->assertEquals(282, $second->duration);

        $event = $this->getTestEvent([
            'deviceRecord' => [
                'data' => [
                    'dateTime' => '2018-10-24T12:32:45',
                    'functionKey' => DFCom_RecordHandler_TimeAccounting::FUNCTION_KEY_CLOCKOUT,
                ]
            ]
        ]);
        $this->_uit = new DFCom_RecordHandler_TimeAccounting($event);
        $this->_uit->handle();

        HumanResources_Controller_AttendanceRecorder::runBLPipes();

        $timesheets = Timetracker_Controller_Timesheet::getInstance()->getAll();

        $first = $timesheets
            ->filter('start_time', '09:20:00')
            ->getFirstRecord();

        $this->assertFalse(!!$first->need_for_clarification);
        $this->assertEquals(192, $first->duration);

        $second = $timesheets
            ->filter('start_time', '13:01:00')
            ->getFirstRecord();

        $this->assertEquals(282, $second->duration);
    }

    public static function getTestEvent($overrides = [])
    {
        $event = [
            'device' => new DFCom_Model_Device([
                'deviceString' => 'EVO-Line 4.3',
                'serialNumber' => 1111,
                'authKey' => Tinebase_Record_Abstract::generateUID(20),
                'fwVersion' => '04.03.10.18',
                'setupVersion' => '0.2',
                'timezone' => 'Europe/Berlin',
            ]),
            'deviceResponse' => new DFCom_Model_DeviceResponse([]),
            'deviceRecord' => new DFCom_Model_DeviceRecord(array_replace_recursive([
                'device_table' => 'timeaccounting',
                'data' => [
                    'dateTime' => '2018-10-24T09:20:16',
                    'cardId' => '36118993923739652',
                    'functionKey' => DFCom_RecordHandler_TimeAccounting::FUNCTION_KEY_CLOCKIN,
                ]
            ], (isset ($overrides['deviceRecord']) ? $overrides['deviceRecord'] : []))),
        ];

        return $event;
    }
}
