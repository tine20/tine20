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
}
