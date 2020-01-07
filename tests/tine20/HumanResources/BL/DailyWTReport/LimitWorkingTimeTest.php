<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  BL
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * @package     HumanResources
 * @subpackage  BL
 */
class HumanResources_BL_DailyWTReport_LimitWorkingTimeTest extends TestCase
{
    /**
     * @var Tinebase_BL_Pipe
     */
    protected static $_pipe;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $rs = new Tinebase_Record_RecordSet(Tinebase_Model_BLConfig::class, [
            [
                Tinebase_Model_BLConfig::FLDS_CLASSNAME     =>
                    HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::class,
                Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD => [
                    HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::FLDS_START_TIME    => '07:30:00',
                    HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::FLDS_END_TIME      => '16:25:00'
                ]
            ]
        ]);
        $rs->runConvertToRecord();
        static::$_pipe = new Tinebase_BL_Pipe($rs);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        static::$_pipe = null;
    }

    /**
     * @return HumanResources_BL_DailyWTReport_Data
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _getData()
    {
        $data = new HumanResources_BL_DailyWTReport_Data();
        $data->workingTimeModel = new HumanResources_Model_WorkingTimeScheme([
            'json'                      => ['days' => [8*3600,8*3600,8*3600,8*3600,(int)(5.5*3600),0,0]],
        ], true);
        $data->result = new HumanResources_Model_DailyWTReport([], true);

        return $data;
    }

    public function testRemoveToEarly()
    {
        $data = $this->_getData();
        $today = Tinebase_DateTime::now()->format('Y-m-d');
        $data->convertTimeSheetsToTimeSlots(new Tinebase_Record_RecordSet(Timetracker_Model_Timesheet::class, [
            [
                'timeaccount_id'    => 1,
                'start_date'        => $today,
                'start_time'        => '04:30:00',
                'duration'          => 40,
            ], [
                'timeaccount_id'    => 1,
                'start_date'        => $today,
                'start_time'        => '05:30:00',
                'duration'          => 120,
            ], [
                'timeaccount_id'    => 2,
                'start_date'        => $today,
                'start_time'        => '10:00:00',
                'duration'          => 120,
            ]
        ], true));

        static::$_pipe->execute($data);

        static::assertCount(1, $data->timeSlots);
        static::assertSame('2', current($data->timeSlots)->timeAccountId);


        $data->convertTimeSheetsToTimeSlots(new Tinebase_Record_RecordSet(Timetracker_Model_Timesheet::class, [
            [
                'timeaccount_id'    => 2,
                'start_date'        => $today,
                'start_time'        => '05:31:00',
                'duration'          => 120,
            ], [
                'timeaccount_id'    => 1,
                'start_date'        => $today,
                'start_time'        => '04:30:00',
                'duration'          => 40,
            ]
        ], true));

        static::$_pipe->execute($data);

        static::assertCount(1, $data->timeSlots);
        static::assertSame('2', current($data->timeSlots)->timeAccountId);
        static::assertSame('07:30:00', current($data->timeSlots)->start->format('H:i:s'));
        static::assertSame('07:31:00', current($data->timeSlots)->end->format('H:i:s'));
    }


    public function testRemoveToLate()
    {
        $data = $this->_getData();
        $today = Tinebase_DateTime::now()->format('Y-m-d');
        $data->convertTimeSheetsToTimeSlots(new Tinebase_Record_RecordSet(Timetracker_Model_Timesheet::class, [
            [
                'timeaccount_id' => 2,
                'start_date' => $today,
                'start_time' => '09:30:00',
                'duration' => 40,
            ],
            [
                'timeaccount_id' => 1,
                'start_date' => $today,
                'start_time' => '16:25:00',
                'duration' => 120,
            ],
            [
                'timeaccount_id' => 1,
                'start_date' => $today,
                'start_time' => '20:00:00',
                'duration' => 120,
            ]
        ], true));

        static::$_pipe->execute($data);

        static::assertCount(1, $data->timeSlots);
        static::assertSame('2', current($data->timeSlots)->timeAccountId);


        $data->convertTimeSheetsToTimeSlots(new Tinebase_Record_RecordSet(Timetracker_Model_Timesheet::class, [[
            'timeaccount_id' => 1,
            'start_date' => $today,
            'start_time' => '15:25:00',
            'duration' => 120,
        ]], true));

        static::$_pipe->execute($data);
        static::assertSame('15:25:00', current($data->timeSlots)->start->format('H:i:s'));
        static::assertSame('16:25:00', current($data->timeSlots)->end->format('H:i:s'));
    }
}