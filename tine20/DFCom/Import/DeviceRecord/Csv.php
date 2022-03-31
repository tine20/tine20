<?php
/**
 * Tine 2.0
 * 
 * @package     DFcom
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl <c.feitl@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the DFcom
 * 
 * @package     DFcom
 * @subpackage  Import
 *
 */
class DFCom_Import_DeviceRecord_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     * 
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id'      => '',
        'deviceName' => '',
    );

    protected $_device = null;

    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);

        $this->_device = $this->getDevice($result['deviceName']);

        if ($result['functionKey'] === 'begin') {
            $this->importAllWorkDay($result);
        } elseif (!$result['data']) {
            $result['data'] = $this->_deviceData([
                "cardId" => $this->getCardIdByUser($result['user']),
                "dateTime" => $result['dateTime'],
                "functionKey" => $result['functionKey'],
                "functionValue" => $result['functionValue']]);
        }

        $result['device_id'] = $this->_device->getId();

        foreach ($result as $key => $value)
        {
            if($value == '')
            {
                $result[$key] = null;;
            }
        }

        return $result;
    }

    public function getDevice($deviceName)
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('DFCom_Model_Device', [
            ['field' => 'name', 'operator' => 'equals', 'value' => $deviceName]
        ]);
        $result = DFCom_Controller_Device::getInstance()->search($filter)->getFirstRecord();

        if($result === null) {
            throw new Tinebase_Exception_NotFound('Device ' . $deviceName . ' not found!');
        }

        return $result;

    }

    public function getCardIdByUser($user)
    {
        $employee = $this->getEmployeeByUserId(Tinebase_FullUser::getInstance()->getUserByLoginName($user)->getId());
        return $employee['dfcom_id'];
    }

    public function getEmployeeByUserId($userId)
    {
        $employeeFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('HumanResources_Model_Employee', [
            ['field' => 'account_id', 'operator' => 'equals', 'value' => $userId]
        ]);

        $result = HumanResources_Controller_Employee::getInstance()->search($employeeFilter);

        if ($result === null) {
            throw new Tinebase_Exception_NotFound('User ' . $userId . ' not found!');
        }

        return $result;
    }

    public function _inspectAfterImport($importedRecord)
    {
        $this->timeAccountRecordHandler($importedRecord);
    }

    public function timeAccountRecordHandler($importedRecord) {
        $event = [
            'device' => $this->_device,
            'deviceResponse' => new DFCom_Model_DeviceResponse([]),
            'deviceRecord' => $importedRecord];
        $timeAccountingRecordHandler = new DFCom_RecordHandler_TimeAccounting($event);
        $timeAccountingRecordHandler->handle();
    }


    /**
     * @param $result
     * @return mixed
     */
    public function importAllWorkDay($result) {
        $time = 6;
        if(!isset($result['user'])) {
            throw new Tinebase_Exception_InvalidArgument('User require');
        }
        $employee = $this->getEmployeeByUserId(Tinebase_FullUser::getInstance()->getUserByLoginName($result['user'])->getId());

        $freeTimeFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('HumanResources_Model_FreeTime', [
            ['field' => 'employee_id', 'operator' => 'equals', 'value' => $employee->getId()]
        ]);

        $freeTimes = HumanResources_Controller_FreeTime::getInstance()->search($freeTimeFilter);

        $skipDays = [];

        foreach ($freeTimes as $freeTime) {
            $test = HumanResources_Controller_FreeDay::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    'HumanResources_Model_FreeDay', [
                        ['field' => 'freetime_id', 'operator' => 'equals', 'value' => $freeTime->getId()]]
                )
            );
            foreach ($test as $t) {
                $skipDays[] = $t['date']->format('Y-m-d');
            }
        }

        $begin = Tinebase_DateTime::now()->subMonth($time);

        while($begin->format('Y-m-d') < Tinebase_DateTime::now()->format('Y-m-d')) {
            if(!(in_array($begin->format('Y-m-d'),$skipDays)) &&
                date('N', strtotime($begin)) < 6) {
                $begin->setTime(8, 0);
                $this->addDeviceRecord($begin, $employee, 'CLIN');
                $begin->setTime(16, 45);
                $this->addDeviceRecord($begin, $employee, 'CLOT');
            }
            $begin->addDay(1);
        }
        $result['dateTime'] = Tinebase_DateTime::now();
        $result['functionKey'] = DFCom_RecordHandler_TimeAccounting::FUNCTION_KEY_INFO;
        return $result;
    }

    public function addDeviceRecord(Tinebase_DateTime $date, $employee, $functionkey)
    {
        $deviceRecord = new DFCom_Model_DeviceRecord([
            "device_id" => $result['device_id'] = $this->_device->getId(),
            "device_table" => 'timeAccounting',
            "data" => $this->_deviceData([
                "cardId" => $employee['dfcom_id'],
                "dateTime" => $date->toString(),
                "functionKey" => $functionkey,
            ])
        ]);


        DFCom_Controller_DeviceRecord::getInstance()->create($deviceRecord);
        $this->importAllWorkDay($deviceRecord);
    }

    protected function _deviceData($data)
    {
        return array_merge([
            "deviceString" => "EVO-Line 4.3",
            "serialNumber" => $this->_device['serialNumber'],
            "authKey" => $this->_device['authKey'],
            "fwVersion" =>  $this->_device['fwVersion'],
            "setupVersion"=>  $this->_device['setupVersion'],
            "functionValue" => ''
        ], $data);
    }
}
