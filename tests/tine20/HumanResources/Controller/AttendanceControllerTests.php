<?php declare(strict_types=1);

/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for HumanResources Controller
 */
class HumanResources_Controller_AttendanceControllerTests extends HumanResources_TestCase
{

    public function testStartsStopsSetup()
    {
        $arDeviceCtrl = HumanResources_Controller_AttendanceRecorderDevice::getInstance();

        $tineWorkingTimeDevice = $arDeviceCtrl->create(new HumanResources_Model_AttendanceRecorderDevice([
            HumanResources_Model_AttendanceRecorderDevice::FLD_NAME => 'tineWT',
        ]));

        $tineProjectTimeDevice = $arDeviceCtrl->create(new HumanResources_Model_AttendanceRecorderDevice([
            HumanResources_Model_AttendanceRecorderDevice::FLD_NAME => 'tinePT',
            HumanResources_Model_AttendanceRecorderDevice::FLD_STARTS => [
                new HumanResources_Model_AttendanceRecorderDeviceRef([
                    HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => $tineWorkingTimeDevice->getId(),
                ], true),
            ],
        ]));

        $tineWorkingTimeDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_STOPS} =
            new Tinebase_Record_RecordSet(HumanResources_Model_AttendanceRecorderDeviceRef::class, [
                new HumanResources_Model_AttendanceRecorderDeviceRef([
                    HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => $tineProjectTimeDevice->getId(),
                ], true),
            ]);
        $tineWorkingTimeDevice = $arDeviceCtrl->update($tineWorkingTimeDevice);

        Tinebase_Record_Expander::expandRecord($tineWorkingTimeDevice);
        Tinebase_Record_Expander::expandRecord($tineProjectTimeDevice);

        $this->assertInstanceOf(Tinebase_Record_RecordSet::class, $tineWorkingTimeDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_STOPS});
        $this->assertInstanceOf(Tinebase_Record_RecordSet::class, $tineWorkingTimeDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_STARTS});
        $this->assertInstanceOf(Tinebase_Record_RecordSet::class, $tineProjectTimeDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_STOPS});
        $this->assertInstanceOf(Tinebase_Record_RecordSet::class, $tineProjectTimeDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_STARTS});

        $this->assertSame(1, $tineWorkingTimeDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_STOPS}->count());
        $this->assertSame(1, $tineProjectTimeDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_STARTS}->count());

        $this->assertSame($tineProjectTimeDevice->getId(), $tineWorkingTimeDevice
            ->{HumanResources_Model_AttendanceRecorderDevice::FLD_STOPS}->getFirstRecord()
            ->getIdFromProperty(HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID));
        $this->assertSame($tineWorkingTimeDevice->getId(), $tineProjectTimeDevice
            ->{HumanResources_Model_AttendanceRecorderDevice::FLD_STARTS}->getFirstRecord()
            ->getIdFromProperty(HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID));
    }

    public function testClockOutOfSequenceTsUpdated()
    {
        $taId = HumanResources_Controller_WorkingTimeScheme::getInstance()->getWorkingTimeAccount(null)->getId();
        $ts = Timetracker_Controller_Timesheet::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                    ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $taId],
                ]
            ));
        $this->assertSame(0, $ts->count());
        $device = HumanResources_Controller_AttendanceRecorderDevice::getInstance()->get(HumanResources_Model_AttendanceRecorderDevice::SYSTEM_WORKING_TIME_ID);
        $dt = Tinebase_DateTime::now()->setTime(12, 0);

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($device)
            ->setTimeStamp($dt)
        );

        HumanResources_Controller_AttendanceRecorder::runBLPipes();
        $ts = Timetracker_Controller_Timesheet::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                    ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $taId],
                ]
            ));
        $this->assertSame(1, $ts->count());
        $ts = $ts->getFirstRecord();
        $this->assertSame(0, (int)$ts->duration);

        $ts->description = $ts->description . ' unittest';
        $ts = Timetracker_Controller_Timesheet::getInstance()->update($ts);
        $this->assertFalse((bool)$ts->need_for_clarification);

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockOut((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($device)
            ->setTimeStamp($dt->getClone()->addHour(2))
        );

        HumanResources_Controller_AttendanceRecorder::runBLPipes();
        $tsUpdated = Timetracker_Controller_Timesheet::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                    ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $taId],
                ]
            ));
        $this->assertSame(1, $tsUpdated->count());
        $tsUpdated = $tsUpdated->getFirstRecord();
        $this->assertSame(120, (int)$tsUpdated->duration);
        $this->assertSame($ts->getId(), $tsUpdated->getId());
        $this->assertSame($ts->description, $tsUpdated->description);
        $this->assertFalse((bool)$tsUpdated->need_for_clarification);

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockOut((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($device)
            ->setTimeStamp($dt->getClone()->addHour(1))
        );

        HumanResources_Controller_AttendanceRecorder::runBLPipes();
        $tsOutOfOrder = Timetracker_Controller_Timesheet::getInstance()->get($ts->getId());
        $this->assertTrue((bool)$tsOutOfOrder->need_for_clarification);
        $this->assertSame($ts->description, $tsOutOfOrder->description);

        $result = Timetracker_Controller_Timesheet::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                    ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $taId],
                ]
            ));

        $this->assertSame(3, $result->count());
        $result->removeById($ts->getId());
        $this->assertSame(1, $result->filter('need_for_clarification', true)->count());
        $this->assertSame(1, $result->filter('need_for_clarification', false)->count());
        $ts = $result->filter('need_for_clarification', false)->getFirstRecord();
        $this->assertSame(60, (int)$ts->duration);
        $ts = $result->filter('need_for_clarification', true)->getFirstRecord();
        $this->assertSame(0, (int)$ts->duration);
    }

    public function testClockInOutOfSequenceTsUpdated()
    {
        $taId = HumanResources_Controller_WorkingTimeScheme::getInstance()->getWorkingTimeAccount(null)->getId();
        $ts = Timetracker_Controller_Timesheet::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                    ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $taId],
                ]
            ));
        $this->assertSame(0, $ts->count());
        $device = HumanResources_Controller_AttendanceRecorderDevice::getInstance()->get(HumanResources_Model_AttendanceRecorderDevice::SYSTEM_WORKING_TIME_ID);
        $dt = Tinebase_DateTime::now()->setTime(12, 0);

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($device)
            ->setTimeStamp($dt)
        );
        HumanResources_Controller_AttendanceRecorder::getInstance()->clockOut((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($device)
            ->setTimeStamp($dt->getClone()->addHour(2))
        );

        HumanResources_Controller_AttendanceRecorder::runBLPipes();
        $ts = Timetracker_Controller_Timesheet::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                    ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $taId],
                ]
            ));
        $this->assertSame(1, $ts->count());
        $ts = $ts->getFirstRecord();
        $this->assertSame(120, (int)$ts->duration);

        $ts->description = $ts->description . ' unittest';
        $ts = Timetracker_Controller_Timesheet::getInstance()->update($ts);
        $this->assertFalse((bool)$ts->need_for_clarification);

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockOut((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($device)
            ->setTimeStamp($dt->getClone()->addHour(1))
        );

        HumanResources_Controller_AttendanceRecorder::runBLPipes();
        $ts = Timetracker_Controller_Timesheet::getInstance()->get($ts->getId());
        $this->assertTrue((bool)$ts->need_for_clarification);
        $this->assertStringContainsString(' unittest', $ts->description);

        $result = Timetracker_Controller_Timesheet::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                    ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $taId],
                ]
            ));

        $this->assertSame(3, $result->count());
        $result->removeById($ts->getId());
        $this->assertSame(1, $result->filter('need_for_clarification', true)->count());
        $this->assertSame(1, $result->filter('need_for_clarification', false)->count());
        $ts = $result->filter('need_for_clarification', false)->getFirstRecord();
        $this->assertSame(60, (int)$ts->duration);
        $ts = $result->filter('need_for_clarification', true)->getFirstRecord();
        $this->assertSame(0, (int)$ts->duration);
    }

    public function testClockInOutOfSequence()
    {
        $taId = HumanResources_Controller_WorkingTimeScheme::getInstance()->getWorkingTimeAccount(null)->getId();
        $ts = Timetracker_Controller_Timesheet::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                    ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $taId],
                ]
            ));
        $this->assertSame(0, $ts->count());
        $ta = Timetracker_Controller_Timeaccount::getInstance()->create(new Timetracker_Model_Timeaccount([
            'title' => 'unittest',
        ]));
        $ptDevice = HumanResources_Controller_AttendanceRecorderDevice::getInstance()->get(HumanResources_Model_AttendanceRecorderDevice::SYSTEM_PROJECT_TIME_ID);
        $device = HumanResources_Controller_AttendanceRecorderDevice::getInstance()->get(HumanResources_Model_AttendanceRecorderDevice::SYSTEM_WORKING_TIME_ID);
        $dt = Tinebase_DateTime::now()->setTime(12, 0);

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt)
        );
        HumanResources_Controller_AttendanceRecorder::getInstance()->clockOut((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($device)
            ->setTimeStamp($dt->getClone()->addHour(1))
        );
        HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($device)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt)
        );
        HumanResources_Controller_AttendanceRecorder::getInstance()->clockOut((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($device)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->addHour(1))
        );

        HumanResources_Controller_AttendanceRecorder::runBLPipes();
        $ts = Timetracker_Controller_Timesheet::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                    ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $taId],
                ]
            ));
        $this->assertSame(1, $ts->count());
        $this->assertEquals(60, (int)$ts->getFirstRecord()->duration);

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($device)
            ->setTimeStamp($dt->getClone()->subHour(1))
        );

        HumanResources_Controller_AttendanceRecorder::runBLPipes();
        $newTs = Timetracker_Controller_Timesheet::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                    ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $taId],
                ]
            ));
        $this->assertSame(1, $newTs->count());
        $this->assertNotSame($ts->getFirstRecord()->getId(), $newTs->getFirstRecord()->getId());
        $this->assertSame(120, (int)$newTs->getFirstRecord()->duration);
    }

    public function testMultiDayClocking()
    {
        if (Tinebase_DateTime::today('Europe/Berlin')->format('I') !== Tinebase_DateTime::today('Europe/Berlin')->addDay(5)->format('I')) {
            $this->markTestSkipped('DST change... twice a year we skip a round or two');
        }

        $ta = Timetracker_Controller_Timeaccount::getInstance()->create(new Timetracker_Model_Timeaccount([
            'title' => 'unittest',
        ]));
        /** @var HumanResources_Model_AttendanceRecorderDevice $ptDevice */
        $ptDevice = HumanResources_Controller_AttendanceRecorderDevice::getInstance()
            ->get(HumanResources_Model_AttendanceRecorderDevice::SYSTEM_PROJECT_TIME_ID);

        $dt = Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())->setTime(15, 3, 10, 0);
        $result = HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->setTimezone('UTC'))
        );
        /** @var HumanResources_Model_AttendanceRecord $aRecord */
        $aRecord = $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_INS}
            ->find(HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID, $ptDevice->getId());

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockPause((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->addHour(33)->setTimezone('UTC'))
            ->setRefId($aRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID})
        );

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->addHour(34)->setTimezone('UTC'))
            ->setRefId($aRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID})
        );

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockPause((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->addHour(35)->setTimezone('UTC'))
            ->setRefId($aRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID})
        );

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->addHour(35 + 48)->setTimezone('UTC'))
            ->setRefId($aRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID})
        );

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockOut((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->addHour(35 + 48)->addMinute(17)->setTimezone('UTC'))
            ->setRefId($aRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID})
        );

        HumanResources_Controller_AttendanceRecorder::runBLPipes();

        $tsRs = Timetracker_Controller_Timesheet::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Timetracker_Model_Timesheet::class, [
            ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $ta->getId()],
        ]), new Tinebase_Model_Pagination(['sort' => 'start_date']));

        $this->assertSame(4, $tsRs->count());

        $tsRs->removeRecord($ts = $tsRs->getFirstRecord());
        $this->assertSame($ts->start_date->format('Y-m-d'), $dt->format('Y-m-d'));
        $this->assertSame($ts->start_time, '15:03:00');
        $this->assertSame($ts->end_time, '00:00:00');
        $this->assertSame(9 * 60 - 3, (int)$ts->duration);

        $tsRs->removeRecord($ts = $tsRs->getFirstRecord());
        $this->assertSame($ts->start_date->format('Y-m-d'), $dt->getClone()->addDay(1)->format('Y-m-d'));
        $this->assertSame($ts->start_time, '00:00:00');
        $this->assertSame($ts->end_time, '00:00:00');
        $this->assertSame(24 * 60, (int)$ts->duration);

        $tsRs->removeRecord($ts = $tsRs->getFirstRecord());
        $this->assertSame($ts->start_date->format('Y-m-d'), $dt->getClone()->addDay(2)->format('Y-m-d'));
        $this->assertNull($ts->start_time);
        $this->assertNull($ts->end_time);
        $this->assertSame(63, (int)$ts->duration);

        $tsRs->removeRecord($ts = $tsRs->getFirstRecord());
        $this->assertSame($ts->start_date->format('Y-m-d'), $dt->getClone()->addDay(4)->format('Y-m-d'));
        $this->assertSame($ts->start_time, '02:03:00');
        $this->assertSame($ts->end_time, '02:20:00');
        $this->assertSame(17, (int)$ts->duration);

        $this->assertSame(0, $tsRs->count());
    }

    public function testRounding()
    {
        /** @var HumanResources_Model_AttendanceRecorderDevice $ptDevice */
        $ptDevice = HumanResources_Controller_AttendanceRecorderDevice::getInstance()
            ->get(HumanResources_Model_AttendanceRecorderDevice::SYSTEM_PROJECT_TIME_ID);
        /** @var HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig $blCfg */
        $blCfg = $ptDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_BLPIPE}->getFirstRecord()
            ->{HumanResources_Model_BLAttendanceRecorder_Config::FLDS_CONFIG_RECORD};
        $blCfg->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_ROUNDING_TO_MIN} = 15;
        $blCfg->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_ROUNDING_BY_CLOCK} = true;
        $blCfg->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_ROUNDING_PAUSE_THRESHOLD} = 55;
        /** @var HumanResources_Model_AttendanceRecorderDevice $ptDevice */
        $ptDevice = HumanResources_Controller_AttendanceRecorderDevice::getInstance()->update($ptDevice);

        $ta = Timetracker_Controller_Timeaccount::getInstance()->create(new Timetracker_Model_Timeaccount([
            'title' => 'unittest',
        ]));

        $dt = Tinebase_DateTime::now()->setTime(15, 3, 10, 0);
        $result = HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone())
        );
        /** @var HumanResources_Model_AttendanceRecord $aRecord */
        $aRecord = $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_INS}
            ->find(HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID, $ptDevice->getId());

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockPause((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->setSecond(11))
            ->setRefId($aRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID})
        );

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->addHour(1))
            ->setRefId($aRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID})
        );

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockPause((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->addHour(1))
            ->setRefId($aRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID})
        );

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->addHour(1)->addMinute(2))
            ->setRefId($aRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID})
        );

        HumanResources_Controller_AttendanceRecorder::getInstance()->clockOut((new HumanResources_Config_AttendanceRecorder())
            ->setDevice($ptDevice)
            ->setAccount($this->_personas['sclever'])
            ->setMetaData([Timetracker_Model_Timeaccount::class => $ta->getId()])
            ->setTimeStamp($dt->getClone()->addHour(1)->addMinute(12))
            ->setRefId($aRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID})
        );

        HumanResources_Controller_AttendanceRecorder::runBLPipes();

        $ts = Timetracker_Controller_Timesheet::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Timetracker_Model_Timesheet::class, [
            ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $ta->getId()],
        ]));
        $this->assertSame(1, $ts->count());
        $ts = $ts->getFirstRecord();
        $ts->notes = Tinebase_Notes::getInstance()->getNotesOfRecord(Timetracker_Model_Timesheet::class, $ts->getId());
        $t = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME);
        $localHour = (int)$dt->getClone()->setTimezone(Tinebase_Core::getUserTimezone())->format('H');
        $this->assertSame(6, $ts->notes->count());
        $this->assertNotNull($ts->notes->find('note', sprintf($t->_('Clock in: %1$s'), $localHour . ':03:10')));
        $this->assertNotNull($ts->notes->find('note', sprintf($t->_('Clock pause: %1$s'), $localHour . ':03:11')));
        $this->assertNotNull($ts->notes->find('note', sprintf($t->_('Clock in: %1$s'), ($localHour + 1) . ':03:10')));
        $this->assertNotNull($ts->notes->find('note', sprintf($t->_('Clock pause: %1$s'), ($localHour + 1). ':03:10')));
        $this->assertNotNull($ts->notes->find('note', sprintf($t->_('Clock in: %1$s'), ($localHour + 1) . ':05:10')));
        $this->assertNotNull($ts->notes->find('note', sprintf($t->_('Clock out: %1$s'), ($localHour + 1) . ':15:10')));
        $this->assertSame($t->_('attendance recorder generated'), $ts->description);
        $this->assertSame(45, (int)$ts->duration);
    }
}
