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
        $this->assertSame('Clock in: 17:03:10 Clock pause: 17:03:11 Clock in: 18:03:10 Clock pause: 18:03:10 Clock in: 18:05:10 Clock out: 18:15:10', $ts->description);
        $this->assertSame(45, (int)$ts->duration);
    }
}
