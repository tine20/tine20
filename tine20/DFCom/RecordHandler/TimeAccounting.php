<?php
/**
 * @package     DFCom
 * @subpackage  RecordHandler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * handler for device records of type timeaccounting
 *
 * @TODO: parts of this class might be moved to a generalized
 *        class in timetracker
 *
 * Class DFCom_RecordHandler_TimeAccounting
 */
class DFCom_RecordHandler_TimeAccounting
{
    const FUNCTION_KEY_INFO = 'INFO';
    const FUNCTION_KEY_CLOCKIN = 'CLIN';
    const FUNCTION_KEY_CLOCKOUT = 'CLOT';
    const FUNCTION_KEY_ABSENCE = 'ASCE';

    const XPROP_TIMESHEET_ID = self::class . '::timesheet_id';
    const XPROP_UNKNOWN_CARD_ID = self::class . '::unknown_card_id';

    protected $device;
    protected $deviceResponse;
    protected $deviceRecord;
    protected $deviceData;
    protected $employeeController;
    protected $accountController;
    protected $freeTimeController;
    protected $timeaccountController;
    protected $monthlyWTReportController;
    protected $timesheetController;
    protected $i18n;
    protected $user;
    protected $currentUser;
    /** @var HumanResources_Model_Employee */
    protected $employee;

    
    public function __construct($event)
    {
        $this->device = $event['device'];
        $this->deviceResponse = $event['deviceResponse'];
        $this->deviceRecord = $event['deviceRecord'];
        $this->deviceData = $this->deviceRecord->xprops('data');
        $this->employeeController = HumanResources_Controller_Employee::getInstance();
        $this->accountController = HumanResources_Controller_Account::getInstance();
        $this->freeTimeController = HumanResources_Controller_FreeTime::getInstance();
//        $this->workingTimeSchemaController = HumanResources_Controller_WorkingTimeScheme::getInstance();
        $this->timeaccountController = Timetracker_Controller_Timeaccount::getInstance();
        $this->monthlyWTReportController = HumanResources_Controller_MonthlyWTReport::getInstance();
        $this->timesheetController = Timetracker_Controller_Timesheet::getInstance();

        $this->i18n = Tinebase_Translation::getTranslation('DFCom');
    }

    public function handle()
    {
        // order of execution matters here, because of the many get/setUsers!
        // ATTENTION do not change order of these lines unless you understand why the order matters
        $assertACLUsageCallbacks = [
            $this->employeeController->assertPublicUsage(),
            $this->accountController->assertPublicUsage(),
    //        $this->workingTimeSchemaController->assertPublicUsage(),
            $this->timeaccountController->assertPublicUsage(),
            $this->monthlyWTReportController->assertPublicUsage(),
            $this->timesheetController->assertPublicUsage(),
        ];
        // end attention

        $dateTime = new Tinebase_DateTime($this->deviceData['dateTime'], $this->device->timezone);

        $this->currentUser = null;
        $result = false;
        try {
            $this->employee = $this->employeeController->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                ['condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_OR, 'filters' => [
                    ['field' => 'dfcom_id', 'operator' => 'equals', 'value' => (int)$this->deviceData['cardId']],
                    ['field' => 'dfcom_id', 'operator' => 'equals', 'value' => $this->deviceData['cardId']],
                ]]
            ]))->getFirstRecord();

            if (!$this->employee) {
                $this->deviceRecord->xprops()[self::XPROP_UNKNOWN_CARD_ID] =  $this->deviceData['cardId'];
                Tinebase_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__ . " unknown card_id '{$this->deviceData['cardId']}'");
                return false;
            }

            // switch to current user identified by card
            $this->currentUser = Tinebase_Core::getUser();
            $this->user = Tinebase_Core::setUser(Tinebase_User::getInstance()->getUserById($this->employee->account_id, Tinebase_Model_FullUser::class));

            switch ($this->deviceData['functionKey']) {
                case self::FUNCTION_KEY_INFO:
                    $employeeName = $this->user->accountDisplayName;
                    array_push($assertACLUsageCallbacks, HumanResources_Controller_Contract::getInstance()->assertPublicUsage());
                    try {
                        $allRemainingVacationsDays = $this->freeTimeController->getRemainingVacationDays($this->employee);
                        $remainingVacations = "{$allRemainingVacationsDays} Tage";
                        
                        $monthlyWTR = $this->monthlyWTReportController->getByEmployeeMonth($this->employee);
                        $balanceTS = $monthlyWTR ? 
                            $monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE} : 0;
                        $balanceTime = (string)round($balanceTS/3600) .':' .  
                            str_pad((string) abs($balanceTS/60)%60, 2, "0", STR_PAD_LEFT);
                        $balance = ($balanceTS >= 0 ? "+{$balanceTime} (haben)" : "{$balanceTime} (soll)");
                        
                        $message = "Zeitsaldo: {$balance}\n Resturlaub: {$remainingVacations}";
                    } catch (Exception $e) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                            __METHOD__ . '::' . __LINE__ . " " . $e->getMessage() . "\n" . $e->getTraceAsString());
                        $message = "Es liegen keine Informationen vor.";
                    }
                    $this->deviceResponse->displayMessage("{$employeeName}\n {$message}");
                    // lets just do this, hopefully the deviceRecord will not be persisted ... but if it would, we dont want it again, so lets say we processed it
                    if (!in_array(self::class, $this->deviceRecord->xprops(DFCom_Model_DeviceRecord::FLD_PROCESSED))) {
                        $this->deviceRecord->xprops(DFCom_Model_DeviceRecord::FLD_PROCESSED)[] = self::class;
                    }
                    $result = true;
                    break;

                case self::FUNCTION_KEY_CLOCKIN:
                case self::FUNCTION_KEY_CLOCKOUT:
                case self::FUNCTION_KEY_ABSENCE:

                    /** @var HumanResources_Model_AttendanceRecorderDevice $device */
                    $device = HumanResources_Controller_AttendanceRecorderDevice::getInstance()->get(
                        HumanResources_Model_AttendanceRecorderDevice::SYSTEM_WORKING_TIME_ID);
                    $cfg = (new HumanResources_Config_AttendanceRecorder())
                        ->setMetaData([
                            HumanResources_Config_AttendanceRecorder::METADATA_SOURCE => __METHOD__,
                            Timetracker_Model_Timeaccount::class =>
                                HumanResources_Controller_WorkingTimeScheme::getInstance()
                                    ->getWorkingTimeAccount($this->employee)->getId(),
                        ])
                        ->setDevice($device)
                        ->setEmployee($this->employee)
                        ->setAccount(Tinebase_User::getInstance()->getFullUserById($this->employee->account_id))
                        ->setTimeStamp($dateTime);
                    if (self::FUNCTION_KEY_CLOCKIN === $this->deviceData['functionKey']) {
                        HumanResources_Controller_AttendanceRecorder::getInstance()->clockIn($cfg);
                    } elseif (self::FUNCTION_KEY_CLOCKOUT === $this->deviceData['functionKey']) {
                        HumanResources_Controller_AttendanceRecorder::getInstance()->clockOut($cfg);
                    } elseif (self::FUNCTION_KEY_ABSENCE === $this->deviceData['functionKey']) {
                        HumanResources_Controller_AttendanceRecorder::getInstance()->clockPause($cfg);
                    }

                    if (!in_array(self::class, $this->deviceRecord->xprops(DFCom_Model_DeviceRecord::FLD_PROCESSED))) {
                        $this->deviceRecord->xprops(DFCom_Model_DeviceRecord::FLD_PROCESSED)[] = self::class;
                    }

                    break;

                default:
                    // think about whether we want to flag this as processed or not

                    // @TODO implement me
                    // check absence reason
                    // end open timesheet (like leave)
                    // create new timesheet on absence timeaccount
                    // $this->endTimesheet $reason
                    // evalute special conditions like "till end of day"
//                Tinebase_Core::getLogger()->ERR(__METHOD__ . '::' . __LINE__ . " unknown function key '{$this->deviceData['functionKey']}'");

            }
        } finally {
            // order of execution matters here, because of the many get/setUsers!
            // we need to do it in reverse order of the initalization!
            if (null !== $this->currentUser) {
                Tinebase_Core::setUser($this->currentUser);
            }
            foreach(array_reverse($assertACLUsageCallbacks) as $assertACLUsageCallback) {
                $assertACLUsageCallback();
            }
        }

        return $result;
    }

    public function createTimesheet($date, $functionKey = self::FUNCTION_KEY_CLOCKIN)
    {
        return $this->timesheetController->create(new Timetracker_Model_Timesheet([
            'account_id' => $this->employee->account_id,
            'timeaccount_id' => HumanResources_Controller_WorkingTimeScheme::getInstance()->getWorkingTimeAccount($this->employee),
            'start_date' => $date,
            'start_time' => $date->format('H:i:s'),
            'end_time' => $date->format('H:i:s'),
            'duration' => 0,
            'description' => sprintf($functionKey === self::FUNCTION_KEY_CLOCKIN ? 
                $this->i18n->translate('Clock in: %1$s') : 
                $this->i18n->translate('Clock out: %1$s'), $date->format('H:i:s')),
        ]));
    }

    public function startTimesheet($timesheet, $start)
    {
        $timesheet->start_time = $start->format('H:i:s');
        $timesheet->description =
            sprintf($this->i18n->translate('Clock in: %1$s'), $start->format('H:i:s')) .
            ' ' . $timesheet->description;

        return $this->timesheetController->update($timesheet);
    }

    /**
     * @param $timesheet
     * @param $end
     * @param string|HumanResources_Model_FreeTimeType $reason
     * @return Timetracker_Model_Timesheet
     * @throws Tinebase_Exception_AccessDenied
     */
    public function endTimesheet($timesheet, $end, $reason='')
    {
        $timesheet->end_time = $end->format('H:i:s');
        $timesheet->{HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON} = $reason;
        $timesheet->description = $timesheet->description . ' ' .
            sprintf($this->i18n->translate('Clock out: %1$s'), $end->format('H:i:s'));

        return $this->timesheetController->update($timesheet);
    }


    public function getOrphanedTimesheets($date)
    {
        $wtAccountId = HumanResources_Controller_WorkingTimeScheme::getInstance()
            ->getWorkingTimeAccount($this->employee)->getId();

        return Timetracker_Controller_Timesheet::getInstance()->search(new Timetracker_Model_TimesheetFilter([
            ['field' => 'account_id', 'operator' => 'equals', 'value' => $this->employee->account_id],
            ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $wtAccountId],
            ['field' => 'start_date', 'operator' => 'equals', 'value' => $date->format('Y-m-d')],
            ['field' => 'start_time', 'operator' => 'before', 'value' => $date->format('H:i:s')],
            ['field' => 'duration', 'operator' => 'equals', 'value' => 0],
        ]));
    }
}
