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
class HumanResources_BL_DailyWTReport_CalculateBreakTimeTest extends TestCase
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
                Tinebase_Model_BLConfig::FLDS_CLASSNAME => HumanResources_Model_BLDailyWTReport_BreakTimeConfig::class,
                Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD => [
                    HumanResources_Model_BLDailyWTReport_BreakTimeConfig::FLDS_TIME_WORKED    => 4 * 3600, // 4 hours
                    HumanResources_Model_BLDailyWTReport_BreakTimeConfig::FLDS_BREAK_TIME     => 1800, // 30 minutes
                ]
            ], [
                Tinebase_Model_BLConfig::FLDS_CLASSNAME => HumanResources_Model_BLDailyWTReport_BreakTimeConfig::class,
                Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD => [
                    HumanResources_Model_BLDailyWTReport_BreakTimeConfig::FLDS_TIME_WORKED    => 6 * 3600, // 6 hours
                    HumanResources_Model_BLDailyWTReport_BreakTimeConfig::FLDS_BREAK_TIME     => 900, // 15 minutes
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
    protected function _getData(array $_rawTSData = [])
    {
        $data = new HumanResources_BL_DailyWTReport_Data();
        $data->workingTimeModel = new HumanResources_Model_WorkingTimeScheme([
            'json'                      => ['days' => [8*3600,8*3600,8*3600,8*3600,(int)(5.5*3600),0,0]],
        ], true);
        $data->result = new HumanResources_Model_DailyWTReport([], true);

        if (!empty($_rawTSData)) {
            $rs = new Tinebase_Record_RecordSet(Timetracker_Model_Timesheet::class);
            $today = Tinebase_DateTime::now()->format('Y-m-d');
            foreach ($_rawTSData as $ts) {
                $rs->addRecord(new Timetracker_Model_Timesheet([
                        'timeaccount_id'    => 1,
                        'start_date'        => $today,
                        'start_time'        => $ts['start_time'],
                        'duration'          => $ts['duration'],
                    ], true));
            }
            $data->convertTimeSheetsToTimeSlots($rs);
        }

        return $data;
    }

    protected function _executePipe(array $_rawTSData)
    {
        $data = $this->_getData($_rawTSData);

        static::$_pipe->execute($data);

        return $data;
    }

    const TIME_SLOTS = 'timeSlots';
    const COUNT = 'count';
    const DURATION_IN_SEC = 'durationInSec';
    const RESULT_PROPERTIES = 'resultProperties';
    protected function _checkResult(HumanResources_BL_DailyWTReport_Data $_data, $checks)
    {
        $debugOut = print_r($_data->toArray(), true);
        if (isset($checks[self::TIME_SLOTS])) {
            if (isset($checks[self::TIME_SLOTS][self::COUNT])) {
                static::assertCount($checks[self::TIME_SLOTS][self::COUNT], $_data->timeSlots, $debugOut);
            }

            if (isset($checks[self::TIME_SLOTS][self::DURATION_IN_SEC])) {
                foreach ($checks[self::TIME_SLOTS][self::DURATION_IN_SEC] as $key => $val) {
                    static::assertSame($val, $_data->timeSlots[$key]->durationInSec(), $debugOut);
                }
            }
        }

        if (isset($checks[self::RESULT_PROPERTIES])) {
            foreach ($checks[self::RESULT_PROPERTIES] as $prop => $val) {
                static::assertSame($val, $_data->result->{$prop}, $debugOut);
            }
        }
    }

    public function testSumUp()
    {
        $data = $this->_executePipe([
            [
                'start_time'        => '09:00:00',
                'duration'          => 241,
            ], [
                'start_time'        => '13:30:00', // this means 29 minutes natural break
                'duration'          => 121, // this means we exceed total work time of 6 hours by 1 minute
            ],
        ]);

        $this->_checkResult($data, [
            self::TIME_SLOTS            => [
                self::COUNT                 => 2,
                self::DURATION_IN_SEC       => [
                    0 => 4 * 3600,
                    1 => 2 * 3600,
                ],
            ],
            self::RESULT_PROPERTIES     => [
                'break_time_deduction'      => 120,
            ],
        ]);
    }

    public function testTimeWorkedEqualsTimeWorkedEdgeCase()
    {
        $data = $this->_executePipe([
            [
                'start_time'        => '09:00:00',
                'duration'          => 240,
            ], [
                'start_time'        => '13:03:00', // this means 3 minutes natural break, which is below minimum break
                'duration'          => 27, // this means this timeslot should vanish completely
            ], [
                'start_time'        => '15:00:00', // this means a lot of break, no more break needed today
                'duration'          => 240,
            ],
        ]);

        $this->_checkResult($data, [
            self::TIME_SLOTS            => [
                self::COUNT                 => 3,
                self::DURATION_IN_SEC       => [
                    0 => 4 * 3600,
                    1 => 0,
                    2 => 4 * 3600,
                ],
            ],
            self::RESULT_PROPERTIES     => [
                'break_time_deduction'      => 27 * 60,
            ],
        ]);
    }

    public function testTimeWorkedEqualsTimeWorkedEdgeCase2()
    {
        $data = $this->_executePipe([
            [
                'start_time'        => '09:00:00',
                'duration'          => 240,
            ], [
                'start_time'        => '13:03:00', // this means 3 minutes natural break, which is below minimum break
                'duration'          => 27, // this means this timeslot should vanish completely
            ], [
                'start_time'        => '13:30:00', // this means only 30 min break, we expect a split here
                'duration'          => 240,
            ],
        ]);

        $this->_checkResult($data, [
            self::TIME_SLOTS            => [
                self::COUNT                 => 4,
                self::DURATION_IN_SEC       => [
                    0 => 4 * 3600,
                    1 => 0,
                    2 => 2 * 3600,
                    3 => 2 * 3600 - 15 * 60,
                ],
            ],
            self::RESULT_PROPERTIES     => [
                'break_time_deduction'      => 27 * 60 + 15 * 60,
            ],
        ]);
    }

    public function testTimeWorkedEqualsTimeWorkedEdgeCase3()
    {
        $data = $this->_executePipe([
            [
                'start_time'        => '09:00:00',
                'duration'          => 240,
            ], [
                'start_time'        => '13:03:00', // this means 3 minutes natural break, which is below minimum break
                'duration'          => 20, // this means this timeslot should vanish completely
            ], [
                'start_time'        => '13:25:00', // this means only 25 min break once we get here, cut and split
                'duration'          => 240,
            ],
        ]);

        $this->_checkResult($data, [
            self::TIME_SLOTS            => [
                self::COUNT                 => 4,
                self::DURATION_IN_SEC       => [
                    0 => 4 * 3600,
                    1 => 0,
                    2 => 2 * 3600,
                    3 => 2 * 3600 - 20 * 60,
                ],
            ],
            self::RESULT_PROPERTIES     => [
                'break_time_deduction'      => 40 * 60,
            ],
        ]);
    }

    public function testTimeWorkedEqualsTimeWorkedEdgeCase4()
    {
        $data = $this->_executePipe([
            [
                'start_time'        => '09:00:00',
                'duration'          => 241,
            ], [
                'start_time'        => '13:03:00', // this means 2 minutes natural break, which is below minimum break
                'duration'          => 20, // this means this timeslot should vanish completely
            ], [
                'start_time'        => '13:25:00', // this means only 25 min break once we get here, cut and split
                'duration'          => 240,
            ],
        ]);

        $this->_checkResult($data, [
            self::TIME_SLOTS            => [
                self::COUNT                 => 4,
                self::DURATION_IN_SEC       => [
                    0 => 4 * 3600,
                    1 => 0,
                    2 => 2 * 3600,
                    3 => 2 * 3600 - 20 * 60,
                ],
            ],
            self::RESULT_PROPERTIES     => [
                'break_time_deduction'      => 41 * 60,
            ],
        ]);
    }

    public function testTimeWorkedEqualsTimeWorkedEdgeCase5()
    {
        $data = $this->_executePipe([
            [
                'start_time'        => '09:00:00',
                'duration'          => 241,
            ], [
                'start_time'        => '13:03:00', // this means 2 minutes natural break, which is below minimum break
                'duration'          => 27, // this means this timeslot should vanish completely
            ], [
                'start_time'        => '13:30:00', // this means only 30 min break once we get here, split
                'duration'          => 240,
            ],
        ]);

        $this->_checkResult($data, [
            self::TIME_SLOTS            => [
                self::COUNT                 => 4,
                self::DURATION_IN_SEC       => [
                    0 => 4 * 3600,
                    1 => 0,
                    2 => 2 * 3600,
                    3 => 2 * 3600 - 15 * 60,
                ],
            ],
            self::RESULT_PROPERTIES     => [
                'break_time_deduction'      => 43 * 60,
            ],
        ]);
    }

    public function testTimeWorkedEqualsTimeWorkedEdgeCase6()
    {
        $data = $this->_executePipe([
            [
                'start_time'        => '09:00:00',
                'duration'          => 241,
            ], [
                'start_time'        => '13:03:00', // this means 2 minutes natural break, which is below minimum break
                'duration'          => 26, // this means this timeslot should vanish completely
            ], [
                'start_time'        => '13:30:00', // this means only 30 min break once we get here, split
                'duration'          => 240,
            ],
        ]);

        $this->_checkResult($data, [
            self::TIME_SLOTS            => [
                self::COUNT                 => 4,
                self::DURATION_IN_SEC       => [
                    0 => 4 * 3600,
                    1 => 0,
                    2 => 2 * 3600,
                    3 => 2 * 3600 - 15 * 60,
                ],
            ],
            self::RESULT_PROPERTIES     => [
                'break_time_deduction'      => 42 * 60,
            ],
        ]);
    }

    public function testTimeWorkedEqualsTimeWorkedEdgeCase7()
    {
        $data = $this->_executePipe([
            [
                'start_time'        => '09:00:00',
                'duration'          => 241,
            ], [
                'start_time'        => '13:03:00', // this means 2 minutes natural break, which is below minimum break
                'duration'          => 240, // this means this means we cut the front and split the other part
            ],
        ]);

        $this->_checkResult($data, [
            self::TIME_SLOTS            => [
                self::COUNT                 => 3,
                self::DURATION_IN_SEC       => [
                    0 => 4 * 3600,
                    1 => 2 * 3600,
                    2 => 2 * 3600 - (15+27) * 60,
                ],
            ],
            self::RESULT_PROPERTIES     => [
                'break_time_deduction'      => 43 * 60,
            ],
        ]);
    }

    public function testTimeWorkedEqualsTimeWorkedEdgeCase8()
    {
        $data = $this->_executePipe([
            [
                'start_time'        => '09:00:00',
                'duration'          => 241,
            ], [
                'start_time'        => '13:03:00', // this means 2 minutes natural break, which is below minimum break
                'duration'          => 3, // this means this timeslot should vanish completely
            ], [
                'start_time'        => '13:07:00', // this means 1 minutes natural break, which is below minimum break
                'duration'          => 10, // this means this timeslot should vanish completely
            ], [
                'start_time'        => '13:22:00', // this means 4 minutes natural break, which is below minimum break
                'duration'          => 8, // this means this timeslot should vanish completely
            ], [
                'start_time'        => '13:30:00', // this means only 30 min break once we get here, split
                'duration'          => 240,
            ],
        ]);

        $this->_checkResult($data, [
            self::TIME_SLOTS            => [
                self::COUNT                 => 6,
                self::DURATION_IN_SEC       => [
                    0 => 4 * 3600,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 2 * 3600,
                    5 => 2 * 3600 - 15 * 60,
                ],
            ],
            self::RESULT_PROPERTIES     => [
                'break_time_deduction'      => 37 * 60,
            ],
        ]);
    }

    public function testTimeWorkedEqualsTimeWorkedEdgeCase9()
    {
        $data = $this->_executePipe([
            [
                'start_time'        => '09:00:00',
                'duration'          => 241,
            ], [
                'start_time'        => '13:03:00', // this means 2 minutes natural break, which is below minimum break
                'duration'          => 3, // this means this timeslot should vanish completely
            ], [
                'start_time'        => '13:07:00', // this means 1 minutes natural break, which is below minimum break
                'duration'          => 10, // this means this timeslot should vanish completely
            ], [
                'start_time'        => '13:22:00', // this means 4 minutes natural break, which is below minimum break
                'duration'          => 10, // this means 2 minutes remain
            ], [
                'start_time'        => '13:34:00', // this means only 30 min break once we get here, split
                'duration'          => 240,
            ],
        ]);

        $this->_checkResult($data, [
            self::TIME_SLOTS            => [
                self::COUNT                 => 6,
                self::DURATION_IN_SEC       => [
                    0 => 4 * 3600,
                    1 => 0,
                    2 => 0,
                    3 => 120,
                    4 => 2 * 3600 - 120,
                    5 => 2 * 3600 - 15 * 60 + 120,
                ],
            ],
            self::RESULT_PROPERTIES     => [
                'break_time_deduction'      => 37 * 60,
            ],
        ]);
    }
}