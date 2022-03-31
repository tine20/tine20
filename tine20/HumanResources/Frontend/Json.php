<?php
/**
 * Tine 2.0
 * 
 * @package     HumanResources
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 *
 * This class handles all Json requests for the HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Frontend
 */
class HumanResources_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var HumanResources_Controller_Employee
     */
    protected $_controller = NULL;
    
    /**
     * holds cached accounts by year but for one employee only!
     * 
     * @var array
     */
    protected $_cachedAccountsOnSaveEmployee = NULL;
    
    /**
     * All full configured models
     * 
     * @var array
     */
    protected $_configuredModels = [
        HumanResources_Model_BLDailyWTReport_BreakTimeConfig::MODEL_NAME_PART,
        HumanResources_Model_BLDailyWTReport_Config::MODEL_NAME_PART,
        HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::MODEL_NAME_PART,
        'Contract',
        'CostCenter',
        HumanResources_Model_Division::MODEL_NAME_PART,
        HumanResources_Model_DivisionGrants::MODEL_NAME_PART,
        'Employee',
        'Account',
        HumanResources_Model_FreeTimeType::MODEL_NAME_PART,
        'FreeDay',
        HumanResources_Model_BLDailyWTReport_WorkingTime::MODEL_NAME_PART,
        HumanResources_Model_DailyWTReport::MODEL_NAME_PART,
        HumanResources_Model_MonthlyWTReport::MODEL_NAME_PART,
        HumanResources_Model_WTRCorrection::MODEL_NAME_PART,
        HumanResources_Model_Stream::MODEL_NAME_PART,
        HumanResources_Model_StreamModality::MODEL_NAME_PART,
        HumanResources_Model_StreamModalReport::MODEL_NAME_PART,
        HumanResources_Model_WageType::MODEL_NAME_PART,
        HumanResources_Model_WorkingTimeScheme::MODEL_NAME_PART,
        HumanResources_Model_FreeTime::MODEL_NAME_PART,
    ];

    protected $_defaultModel = 'Employee';
    
    /**
     * the constructor
     */
    public function __construct()
    {
        $this->_applicationName = 'HumanResources';
        if (!HumanResources_Config::getInstance()->featureEnabled(
            HumanResources_Config::FEATURE_WORKING_TIME_ACCOUNTING)
        ) {
            $this->_configuredModels = array_diff($this->_configuredModels, [
                HumanResources_Model_BLDailyWTReport_BreakTimeConfig::MODEL_NAME_PART,
                HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::MODEL_NAME_PART,
                HumanResources_Model_BLDailyWTReport_WorkingTime::MODEL_NAME_PART,
                HumanResources_Model_DailyWTReport::MODEL_NAME_PART,
                HumanResources_Model_MonthlyWTReport::MODEL_NAME_PART,
                HumanResources_Model_WTRCorrection::MODEL_NAME_PART,
                HumanResources_Model_WageType::MODEL_NAME_PART,
            ]);
        }
        if (!HumanResources_Config::getInstance()->featureEnabled(
            HumanResources_Config::FEATURE_STREAMS)
        ) {
            $this->_configuredModels = array_diff($this->_configuredModels, [
                HumanResources_Model_Stream::MODEL_NAME_PART,
                HumanResources_Model_StreamModality::MODEL_NAME_PART,
                HumanResources_Model_StreamModalReport::MODEL_NAME_PART,
            ]);
        }

        if (!Tinebase_Acl_Roles::getInstance()->hasRight(
            $this->_applicationName, Tinebase_Core::getUser()->getId(), HumanResources_Acl_Rights::MANAGE_STREAMS)
        ) {
            $this->_configuredModels = array_diff($this->_configuredModels, [
                HumanResources_Model_Stream::MODEL_NAME_PART,
                HumanResources_Model_StreamModality::MODEL_NAME_PART,
                HumanResources_Model_StreamModalReport::MODEL_NAME_PART
            ]);
        }
    }

    /**
     * @param $filter
     * @param $paging
     * @return array
     */
    public function searchStreams($filter, $paging)
    {
        return $this->_search($filter, $paging, HumanResources_Controller_Stream::getInstance(),
            HumanResources_Model_Stream::class);
    }

    /**
     * Return a single stream
     *
     * @param   string $id
     * @return  array stream data
     */
    public function getStream($id)
    {
        return $this->_get($id, HumanResources_Controller_Stream::getInstance());
    }

    /**
     * creates/updates a stream
     *
     * @param  array $recordData
     * @return array created/updated stream
     */
    public function saveStream($recordData)
    {
        return $this->_save($recordData, HumanResources_Controller_Stream::getInstance(), HumanResources_Model_Stream::class);
    }

    /**
     * @param $streamId
     * @return array
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function generateStreamReport($streamId)
    {
        $stremCtrl = HumanResources_Controller_Stream::getInstance();
        $stream = $stremCtrl->get($streamId);

        $expander = new Tinebase_Record_Expander(HumanResources_Model_Stream::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                HumanResources_Model_Stream::FLD_STREAM_MODALITIES  => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        HumanResources_Model_StreamModality::FLD_REPORTS => []
                    ],
                ],
            ],
        ]);
        $expander->expand(new Tinebase_Record_RecordSet(HumanResources_Model_Stream::class, [$stream]));

        $stremCtrl->createReports($stream);

        return $this->_recordToJson($stremCtrl->get($streamId));
    }

    /**
     * @param $data
     * @return array
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    public function saveMonthlyWTReport($data)
    {
        if (!isset($data['id']) || empty($data['id'])) {
            throw new Tinebase_Exception_Record_NotAllowed('monthly wt reports can\'t be created');
        }

        $mwtrCtrl = HumanResources_Controller_MonthlyWTReport::getInstance();
        $oldContext = $mwtrCtrl->getRequestContext() ?: [];

        try {
            $mwtrCtrl->setRequestContext([HumanResources_Controller_MonthlyWTReport::RC_JSON_REQUEST => true]);
            return $this->_save($data, $mwtrCtrl, HumanResources_Model_MonthlyWTReport::class);

        } finally {
            $mwtrCtrl->setRequestContext($oldContext);
        }
    }

    public function saveDailyWTReport($data)
    {
        if (!isset($data['id']) || empty($data['id'])) {
            throw new Tinebase_Exception_Record_NotAllowed('daily wt reports can\'t be created');
        }

        $dwtrCtrl = HumanResources_Controller_DailyWTReport::getInstance();
        $oldContext = $dwtrCtrl->getRequestContext() ?: [];

        try {
            $dwtrCtrl->setRequestContext([HumanResources_Controller_DailyWTReport::RC_JSON_REQUEST => true]);
            return $this->_save($data, $dwtrCtrl, HumanResources_Model_DailyWTReport::class);

        } finally {
            $dwtrCtrl->setRequestContext($oldContext);
        }
    }

    public function recalculateEmployeesWTReports(string $employeeId, bool $force = false)
    {
        /** @noinspection PhpParamsInspection */
        HumanResources_Controller_DailyWTReport::getInstance()->calculateReportsForEmployee(
            HumanResources_Controller_Employee::getInstance()->get($employeeId), null, null, $force
        );

        return true;
    }

    /**
     * calculate all daily working time reports
     *
     * @return void
     */
    public function calculateAllDailyWTReports(bool $force = false)
    {
        // NOTE: this method calcs daily & monthly
        HumanResources_Controller_DailyWTReport::getInstance()->calculateAllReports($force);
    }

    /**
     * calculate all monthly working time reports
     *
     * @return void
     */
    public function calculateAllMonthlyWTReports(bool $force = false)
    {
        // NOTE: this method calcs daily & monthly
        HumanResources_Controller_DailyWTReport::getInstance()->calculateAllReports($force);
    }

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchEmployees($filter, $paging)
    {
        return $this->_search($filter, $paging, HumanResources_Controller_Employee::getInstance(), 'HumanResources_Model_EmployeeFilter');
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getEmployee($id)
    {
        $employee = $this->_get($id, HumanResources_Controller_Employee::getInstance());

        if ((isset($employee['account_id']) || array_key_exists('account_id', $employee)) && ! empty($employee['account_id']['contact_id'])) {
            try {
                $employee['account_id']['contact_id'] = Addressbook_Controller_Contact::getInstance()->get($employee['account_id']['contact_id']);
            } catch (Tinebase_Exception_NotFound $e) {
                // do nothing
            }
        }
        
        // TODO: resolve this in controller
        if (! empty($employee['costcenters']) && is_array($employee['costcenters'])) {
            $cc = Tinebase_Controller_CostCenter::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_CostCenter::class, []));
            for ($i = 0; $i < count($employee['costcenters']); $i++) {
                $costCenter = $cc->filter('id', $employee['costcenters'][$i]['cost_center_id'])->getFirstRecord();
                if ($costCenter) {
                    $employee['costcenters'][$i]['cost_center_id'] = $costCenter->toArray();
                }
            }
        }
        
        // TODO: resolve this in controller
        // add feast calendars
        if (! empty($employee['contracts'])) {
            for ($i = 0; $i < count($employee['contracts']); $i++) {
                if (! $employee['contracts'][$i]['feast_calendar_id']) {
                    continue;
                }
                try { 
                    $cal =  Tinebase_Container::getInstance()->get($employee['contracts'][$i]['feast_calendar_id']);
                    $employee['contracts'][$i]['feast_calendar_id'] = $cal->toArray();
                } catch (Tinebase_Exception_NotFound $e) {
                    $employee['contracts'][$i]['feast_calendar_id'] = NULL;
                }
            }
        }
        
        return $employee;
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveEmployee($recordData)
    {
        // sanitize costcenters
        if (! empty($recordData['costcenters'])) {
            for ($i = 0; $i < count($recordData['costcenters']); $i++) {
                if (is_array($recordData['costcenters'][$i]['cost_center_id'])) {
                    // flat costcenter id
                    $recordData['costcenters'][$i]['cost_center_id'] = $recordData['costcenters'][$i]['cost_center_id']['id'];
                    if ($i == 0) {
                        // set start date of first costcenter to employee start date if none is given
                        if (empty($recordData['costcenters'][0]['start_date'])) {
                            $recordData['costcenters'][0]['start_date'] = $recordData['employment_begin'];
                        }
                    }
                }
            }
        }

        // auto set dates of the first contract to dates of the employee, if none are given
        if (! empty($recordData['contracts'])) {
            
            for ($i = 0; $i < count($recordData['contracts']); $i++) {
                if (isset($recordData['contracts'][$i]) && is_array($recordData['contracts'][$i]['feast_calendar_id'])) {
                    $recordData['contracts'][$i]['feast_calendar_id'] = $recordData['contracts'][$i]['feast_calendar_id']['id'];
                }
            }
            
            if (! empty($recordData['employment_begin']) && empty($recordData['contracts'][0]['start_date'])) {
                $recordData['contracts'][0]['start_date'] = $recordData['employment_begin'];
            }
            if (! empty($recordData['employment_end']) && empty($recordData['contracts'][0]['end_date'])) {
                $recordData['contracts'][0]['end_date'] = $recordData['employment_end'];
            }
        }
        
        return $this->_save($recordData, HumanResources_Controller_Employee::getInstance(), 'Employee');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteEmployees($ids)
    {
        return $this->_delete($ids, HumanResources_Controller_Employee::getInstance());
    }
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveAccount($recordData)
    {
        return $this->_save($recordData, HumanResources_Controller_Account::getInstance(), 'Account');
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchAccounts($filter, $paging)
    {
        return $this->_search($filter, $paging, HumanResources_Controller_Account::getInstance(), 'HumanResources_Model_AccountFilter');
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getAccount($id)
    {
        $accountController = HumanResources_Controller_Account::getInstance();
        $accountRecord = $accountController->get($id);
        $account = $this->_recordToJson($accountRecord);
        $account = array_merge($account, $accountController->resolveVirtualFields($accountRecord));
        return $account;
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchWorkingTimes($filter, $paging)
    {
        return $this->_search($filter, $paging, HumanResources_Controller_WorkingTimeScheme::getInstance(),
            'HumanResources_Model_WorkingTimeSchemeFilter');
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getWorkingTime($id)
    {
        return $this->_get($id, HumanResources_Controller_WorkingTimeScheme::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveWorkingTime($recordData)
    {
        return $this->_save($recordData, HumanResources_Controller_WorkingTimeScheme::getInstance(), 'WorkingTimeScheme');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteWorkingTime($ids)
    {
        return $this->_delete($ids, HumanResources_Controller_WorkingTimeScheme::getInstance());
    }

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchFreeTimes($filter, $paging)
    {
        return $this->_search($filter, $paging, HumanResources_Controller_FreeTime::getInstance(), 'HumanResources_Model_FreeTimeFilter');
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getFreeTime($id)
    {
        return $this->_get($id, HumanResources_Controller_FreeTime::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveFreeTime($recordData)
    {
        return $this->_save($recordData, HumanResources_Controller_FreeTime::getInstance(), 'FreeTime');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteFreeTimes($ids)
    {
        return $this->_delete($ids, HumanResources_Controller_FreeTime::getInstance());
    }

    /**
     * returns feast days and freedays of an employee for the freetime edit dialog
     * 
     * @param string  $_employeeId
     * @param integer $_year
     * @param string  $_freeTimeId deprecated do not used anymore!
     * @param string  $_accountId used for vacation calculations (account period might differ from $_year)
     */
    public function getFeastAndFreeDays($_employeeId, $_year = NULL, $_freeTimeId = NULL, $_accountId = NULL)
    {
        $cController = HumanResources_Controller_Contract::getInstance();
        $eController = HumanResources_Controller_Employee::getInstance();
        $aController = HumanResources_Controller_Account::getInstance();
        $ftController = HumanResources_Controller_FreeTime::getInstance();
        $fdController = HumanResources_Controller_FreeDay::getInstance();
        
        // validate employeeId
        $employee = $eController->get($_employeeId);
        
        // set period to search for
        $minDate = Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        if ($_year) {
            $minDate->setDate($_year, 1, 1);
        } else {
            $minDate->setDate($minDate->format('Y'), 1, 1);
        }
        
        /* vacation computation -> shoud be extra call!*/
        $account = $_accountId ? $aController->get($_accountId) : $aController->getByEmployeeYear($_employeeId, $_year);
        $vacation = HumanResources_Controller_Account::getInstance()->resolveVacation($account);
        /* end vacation computation */
        
        $maxDate = clone $minDate;
        $maxDate->addYear(1)->subSecond(1);

        // find contracts of the year in which the vacation days will be taken
        $contracts = $cController->getValidContracts([
            'from' => $minDate,
            'until' => $maxDate
        ], $_employeeId);
        $excludeDates = array();
        
        if ($contracts->count() < 1) {
            throw new HumanResources_Exception_NoContract();
        }
        
        $first = TRUE;
        
        $feastDays = array();
        
        $contracts->setTimezone(Tinebase_Core::getUserTimezone());
        
        // find out disabled days for the different contracts
        foreach ($contracts as $contract) {
            $json = $contract->getWorkingTimeJson();
            $startDay = ($contract->start_date == NULL) ? $minDate : (($contract->start_date < $minDate) ? $minDate : $contract->start_date);
            $stopDay  = ($contract->end_date == NULL)   ? $maxDate : (($contract->end_date > $maxDate)   ? $maxDate : $contract->end_date);

            if ($first) {
                $firstDay = clone $startDay;
                $first = FALSE;
            }
            
            // find out weekdays to disable
            if (is_array($json)) {
                foreach($json['days'] as $index => $hours) {
                    $hours = intval($hours);
                    if ($hours === 0) {
                        $day = clone $startDay;
                        $day->setWeekDay(($index+1));
                        if ($day->compare($startDay) < 0) $day->addWeek(1);
                        while ($day->compare($stopDay) < 1) {
                            $exdate = clone $day;
                            $exdate->setTimezone(Tinebase_Core::getUserTimezone());
                            $excludeDates[] = $exdate;
                            $day->addWeek(1);
                        }
                    }
                }
            }
            // search feast days
            $feastDays = array_merge($cController->getFeastDays($contract, $startDay, $stopDay), $feastDays);
        }
        
        // set time to 0
        foreach($feastDays as &$feastDay) {
            $feastDay->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        }
        
        // prepare free time filter, add employee_id
        // @TODO limit freetimes/freedays to given period (be aware might be multiple accounts)
        $freeTimeFilter = new HumanResources_Model_FreeTimeFilter(array(), 'AND');
        $freeTimeFilter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
        
        // prepare free day filter
        $freeDayFilter = new HumanResources_Model_FreeDayFilter(array(), 'AND');
        $freeDayFilter->addFilter(new Tinebase_Model_Filter_Int(array('field' => 'duration', 'operator' => 'equals', 'value' => 1)));
        
        $allFreeTimes = $ftController->search($freeTimeFilter);
        $allFreeDayFilter = clone $freeDayFilter;
        $allFreeDayFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $allFreeTimes->id)));
        $allFreeDays = $fdController->search($allFreeDayFilter);
        $freeTimeTypes = HumanResources_Controller_FreeTimeType::getInstance()->getAll();

        // TODO: remove results property, just return results array itself
        return array(
            'results' => array(
                'vacation'          => $vacation,
                'remainingVacation' => intval(floor($vacation['scheduled_remaining_vacation_days'])),
                'excludeDates'      => $excludeDates,
                'freeTimeTypes'     => $freeTimeTypes->toArray(),
                'allFreeTimes'      => $allFreeTimes->toArray(),
                'allFreeDays'       => $allFreeDays->toArray(),
                'feastDays'         => $feastDays,
                'contracts'         => $contracts->toArray(),
                'employee'          => $employee->toArray(),
                'firstDay'          => $firstDay,
                'lastDay'           => $stopDay,
             )
        );
    }

    public function clockIn(array $deviceRecord): array
    {
        /** @var HumanResources_Model_AttendanceRecord $record */
        $record = $this->_jsonToRecord($deviceRecord, HumanResources_Model_AttendanceRecord::class);
        // if there are dependent records, set the timezone of them and add them to a recordSet
        $this->_dependentRecordsFromJson($record);

        /** @var HumanResources_Model_AttendanceRecorderDevice $device */
        $device = HumanResources_Controller_AttendanceRecorderDevice::getInstance()->get($record->getIdFromProperty(
            HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID
        ));
        $cfg = (new HumanResources_Config_AttendanceRecorder())
            ->setMetaData(array_merge(isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA]) ?
                $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA] : [], [
                HumanResources_Config_AttendanceRecorder::METADATA_SOURCE => __METHOD__
            ]))
            ->setDevice($device)
            ->setRefId($record->{HumanResources_Model_AttendanceRecord::FLD_REFID} ?: null)
            ->setFreetimetypeId($record->{HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID} ?
                $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID) : null)
            ->setThrowOnFaultyAction(true);

        $result = HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn($cfg);

        HumanResources_Controller_AttendanceRecorder::runBLPipes(Tinebase_Core::getUser()->getId());
        $result->reloadData();

        return $result->toArray();
    }

    public function clockOut(array $deviceRecord): array
    {
        /** @var HumanResources_Model_AttendanceRecord $record */
        $record = $this->_jsonToRecord($deviceRecord, HumanResources_Model_AttendanceRecord::class);
        // if there are dependent records, set the timezone of them and add them to a recordSet
        $this->_dependentRecordsFromJson($record);

        /** @var HumanResources_Model_AttendanceRecorderDevice $device */
        $device = HumanResources_Controller_AttendanceRecorderDevice::getInstance()->get($record->getIdFromProperty(
            HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID
        ));
        $cfg = (new HumanResources_Config_AttendanceRecorder())
            ->setMetaData(array_merge(isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA]) ?
                $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA] : [], [
                HumanResources_Config_AttendanceRecorder::METADATA_SOURCE => __METHOD__
            ]))
            ->setDevice($device)
            ->setRefId($record->{HumanResources_Model_AttendanceRecord::FLD_REFID} ?: null)
            ->setFreetimetypeId($record->{HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID} ?
                $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID) : null)
            ->setThrowOnFaultyAction(true);

        $result = HumanResources_Controller_AttendanceRecorder::getInstance()->clockOut($cfg);

        HumanResources_Controller_AttendanceRecorder::runBLPipes(Tinebase_Core::getUser()->getId());
        $result->reloadData();

        return $result->toArray();
    }

    public function clockPause(array $deviceRecord): array
    {
        /** @var HumanResources_Model_AttendanceRecord $record */
        $record = $this->_jsonToRecord($deviceRecord, HumanResources_Model_AttendanceRecord::class);
        // if there are dependent records, set the timezone of them and add them to a recordSet
        $this->_dependentRecordsFromJson($record);

        /** @var HumanResources_Model_AttendanceRecorderDevice $device */
        $device = HumanResources_Controller_AttendanceRecorderDevice::getInstance()->get($record->getIdFromProperty(
            HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID
        ));
        $cfg = (new HumanResources_Config_AttendanceRecorder())
            ->setMetaData(array_merge(isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA]) ?
                $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA] : [], [
                HumanResources_Config_AttendanceRecorder::METADATA_SOURCE => __METHOD__
            ]))
            ->setDevice($device)
            ->setRefId($record->{HumanResources_Model_AttendanceRecord::FLD_REFID} ?: null)
            ->setFreetimetypeId($record->{HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID} ?
                $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID) : null)
            ->setThrowOnFaultyAction(true);

        $result = HumanResources_Controller_AttendanceRecorder::getInstance()->clockPause($cfg);

        HumanResources_Controller_AttendanceRecorder::runBLPipes(Tinebase_Core::getUser()->getId());
        $result->reloadData();

        return $result->toArray();
    }

    public function getAttendanceRecorderDeviceStates()
    {
        return $this->_search([
                ['field' => HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()],
                ['field' => HumanResources_Model_AttendanceRecord::FLD_STATUS,  'operator' => 'equals', 'value' => HumanResources_Model_AttendanceRecord::STATUS_OPEN],
            ], [
                'sort' => HumanResources_Model_AttendanceRecord::FLD_SEQUENCE,
                'dir' => 'ASC'
            ], HumanResources_Controller_AttendanceRecord::getInstance(), HumanResources_Model_AttendanceRecord::class);
    }

    public function wtInfo()
    {
        $employeeController = HumanResources_Controller_Employee::getInstance();
        $freeTimeController = HumanResources_Controller_FreeTime::getInstance();
        $monthlyWTReportController = HumanResources_Controller_MonthlyWTReport::getInstance();

        $releaseACLUsageCallbacks = [
            $employeeController->assertPublicUsage(),
            $freeTimeController->assertPublicUsage(),
            $monthlyWTReportController->assertPublicUsage(),
        ];

        try {
            $employee = $employeeController->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                ['field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()],
            ]))->getFirstRecord();

            $allRemainingVacationsDays = $freeTimeController->getRemainingVacationDays($employee);
            $remainingVacations = "{$allRemainingVacationsDays} Tage";

            $monthlyWTR = $monthlyWTReportController->getByEmployeeMonth($employee);
            $balanceTS = $monthlyWTR ?
                $monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE} : 0;
            $balanceTime = (string)round($balanceTS / 3600) . ':' .
                str_pad((string)abs($balanceTS / 60) % 60, 2, "0", STR_PAD_LEFT);
            $balance = ($balanceTS >= 0 ? "+{$balanceTime} (haben)" : "{$balanceTime} (soll)");

        } finally {
            foreach(array_reverse($releaseACLUsageCallbacks) as $releaseACLUsageCallback) {
                $releaseACLUsageCallback();
            }

        }

        return "Zeitsaldo: {$balance}\n Resturlaub: {$remainingVacations}";
    }

    /**
     * Sets the config for HR
     * 
     * @param array $config
     */
    public function setConfig($config) {
        return HumanResources_Controller::getInstance()->setConfig($config);
    }
    
    /**
     * Returns registry data of the application.
     *
     * Each application has its own registry to supply static data to the client.
     * Registry data is queried only once per session from the client.
     *
     * This registry must not be used for rights or ACL purposes. Use the generic
     * rights and ACL mechanisms instead!
     *
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $data = parent::getRegistryData();
        $ci = HumanResources_Config::getInstance();
        $calid = $ci->get($ci::DEFAULT_FEAST_CALENDAR, NULL);
        try {
            $data[$ci::DEFAULT_FEAST_CALENDAR] = $calid ? Tinebase_Container::getInstance()->get($calid)->toArray() : null;
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Exception::log($tenf);
            $data[$ci::DEFAULT_FEAST_CALENDAR] = null;
        }
        $data[$ci::VACATION_EXPIRES] = $ci->get($ci::VACATION_EXPIRES);
        return $data;
    }
    
    /**
     * 
     * @param integer $year
     */
    public function createMissingAccounts($year = NULL)
    {
        $year = intval($year);
        
        if ($year < 2006 || $year >= 2106 || ! is_int($year)) {
            throw new HumanResources_Exception_NeedsYear();
        }
        
        $results = HumanResources_Controller_Account::getInstance()->createMissingAccounts($year);
        
        return array('success' => TRUE, 'year' => $year, 'totalcount' => $results->count(), 'results' => $results->toArray());
    }
}
