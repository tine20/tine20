<?php
/**
 * DailyWorkingTimeReport controller for HumanResources application
 * 
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * DailyWorkingTimeReport controller class for HumanResources application
 * 
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_DailyWTReport extends Tinebase_Controller_Record_Abstract
{
    const RC_ALLOW_IS_CLEARED = 'allowIsCleared';
    const RC_JSON_REQUEST = 'jsonRequest';

    /**
     * @var HumanResources_Model_Employee
     */
    protected $_employee = null;

    /**
     * @var Tinebase_DateTime
     */
    protected $_startDate = null;
    /**
     * @var Tinebase_DateTime
     */
    protected $_endDate = null;
    /**
     * @var Tinebase_DateTime
     */
    protected $_currentDate = null;
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_oldReports = null;
    protected $_reportResult = null;

    protected $_wtsBLPipes = [];
    protected $_feastDays = [];

    protected $_monthlyWTR = [];

    public $lastReportCalculationResult = null;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'HumanResources';
        $this->_modelName = HumanResources_Model_DailyWTReport::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => 'humanresources_wt_dailyreport',
            'modlogActive' => true
        ));

        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
        $this->_doContainerACLChecks = false;
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_DailyWTReport
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_DailyWTReport
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    /**
     * DailyWorkingTimeReports are calculated once a day by a scheduler job. New
     *  reports are created and all reports from this and the last month which
     *  don't have their is_cleared flag set get updated. Older reports can be
     *  created/updated manually in the UI
     *
     * All employees that have employment_end IS NULL or emplyoment_end AFTER now() - 2 months will be included
     * in the calculation. Only days during which the employees have a valid contract will create a DailyWTReport
     *
     * @return boolean
     */
    public function calculateAllReports()
    {
        if (! HumanResources_Config::getInstance()->featureEnabled(
            HumanResources_Config::FEATURE_CALCULATE_DAILY_REPORTS) &&
            ! HumanResources_Config::getInstance()->featureEnabled(
                HumanResources_Config::FEATURE_WORKING_TIME_ACCOUNTING)
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' FEATURE_WORKING_TIME_ACCOUNTING/FEATURE_CALCULATE_DAILY_REPORTS disabled - Skipping.'
            );
            return true;
        }

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
            ['field' => 'employment_end', 'operator' => 'after', 'value' => Tinebase_DateTime::now()->subMonth(2)]
        ], '', [Tinebase_Model_Filter_Date::AFTER_OR_IS_NULL => true]);
        $iterator = new Tinebase_Record_Iterator(array(
            'iteratable' => $this,
            'controller' => HumanResources_Controller_Employee::getInstance(),
            'filter'     => $filter,
            'function'   => 'calculateReportsForEmployees',
        ));
        $iterator->iterate();

        return true;
    }

    /**
     * @param Tinebase_Record_RecordSet $_records
     * @return array
     */
    public function calculateReportsForEmployees(Tinebase_Record_RecordSet $_records)
    {
        $result = [];
        foreach ($_records as $employee) {
            $result[$employee->getId()] = $this->calculateReportsForEmployee($employee);
        }

        $this->lastReportCalculationResult = $result;
        return $result;
    }

    /**
     * iterates over the dates provided or defaulting to beginning of last month until end of yesterday
     * gets the existing reports for each day. If the report of a day is_cleared, the day will be skipped
     * if the employee does not have a valid contract for that day, the day will be skipped
     * the BLPipe from the valid contracts working time scheme will be created and fed with data
     * if there was no existing report or if the report changed, it will be persisted
     *
     * @param HumanResources_Model_Employee $employee
     * @param null|Tinebase_DateTime $startDate
     * @param null|Tinebase_DateTime $endDate
     * @return array
     *
     * @todo use an result object as return value?
     */
    public function calculateReportsForEmployee(
        HumanResources_Model_Employee $employee,
        Tinebase_DateTime $startDate = null,
        Tinebase_DateTime $endDate = null
    ) {
        // we should never run in FE context, so we reset the RC and use RAII to restate it
        $oldRC = $this->_requestContext;
        $that = $this;
        $this->_requestContext = [];
        $rcRaii = new Tinebase_RAII(function() use ($oldRC, $that) {
            $that->_requestContext = $oldRC;
        });

        // init some member vars
        $this->_monthlyWTR = [];
        $this->_employee = $employee;
        $this->_startDate = $startDate ? $startDate : $this->_getStartDate();
        $this->_endDate = $endDate ? $endDate : $this->_getEndDate();
        $this->_reportResult = [
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];;

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Calculating Daily Reports for ' . $employee->getTitle()
            . ' (From: ' . $this->_startDate->toString()
            . ' Until: ' . $this->_endDate->toString() . ')'
        );


        // first we get all data. We do this in a transaction to get a proper snapshot
        $dataReadTransaction = new Tinebase_TransactionManager_Handle();

        $rs = new Tinebase_Record_RecordSet(HumanResources_Model_Employee::class, [$employee]);
        Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($rs,
            HumanResources_Model_Employee::getConfiguration());
        // expand required properties
        $expander = new Tinebase_Record_Expander(HumanResources_Model_Employee::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'contracts' => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'working_time_scheme' => [Tinebase_Record_Expander::GET_DELETED => true]
                    ],
                ],
            ],
        ]);
        $expander->expand($rs);

        $existingReports = $this->_getEmployeesReports();
        $timeSheets = $this->_getEmployeesTimesheets();
        $freeTimes = $this->_getEmployeesFreeTimes();

        $dataReadTransaction->commit();


        for ($this->_currentDate = clone $this->_startDate; $this->_endDate->isLaterOrEquals($this->_currentDate);
                $this->_currentDate->addDay(1)) {

            // we need those two also in an error case
            $dateStr = $this->_currentDate->format('Y-m-d');
            $monthlyWTR = null;

            // then we calculate each day in a transaction, see dailyTransaction
            try {
                $monthlyWTR = $this->_getOrCreateMonthlyWTR();

                $dailyTransaction = new Tinebase_TransactionManager_Handle();

                /** @var HumanResources_Model_DailyWTReport $oldReport */
                $oldReport = null;
                if (isset($existingReports[$dateStr])) {
                    $oldReport = $existingReports[$dateStr];
                    if ($oldReport->is_cleared) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' old report for day ' .
                                $this->_currentDate->toString() . ' is already cleared, skipping');
                        $dailyTransaction->commit();
                        continue;
                    }
                }

                if (null === ($contract = $this->_employee->getValidContract($this->_currentDate))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 'employee ' .
                            $employee->getId() . ' ' . $employee->getTitle() . ' has no valid contract at ' .
                            $this->_currentDate->toString());

                    if (isset($existingReports[$dateStr])) {
                        $oldReport = $existingReports[$dateStr]->getCleanClone();
                        $oldReport->calculation_failure = 1;
                        $oldReport->system_remark =
                            Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)
                                ->_('No valid contract for this date');

                        $this->update($oldReport);
                        $this->_reportResult['errors'] += 1;
                    }

                    $dailyTransaction->commit();
                    continue;
                }

                if (false === ($blPipe = $this->_getBLPipe($contract->working_time_scheme))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ .
                            '::' . __LINE__ . 'employees ' . $employee->getId() . ' ' . $employee->getTitle() .
                            ' contract has no valid working time scheme for dailyreporting at ' .
                            $this->_currentDate->toString());

                    if (isset($existingReports[$dateStr])) {
                        $oldReport = $existingReports[$dateStr]->getCleanClone();
                        $oldReport->calculation_failure = 1;
                        $oldReport->system_remark =
                            Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)
                                ->_('No valid blpipe for the working time scheme of this contract for this date');

                        $this->update($oldReport);
                        $this->_reportResult['errors'] += 1;
                    }

                    $dailyTransaction->commit();
                    continue;
                }
                
                $blPipeData = new HumanResources_BL_DailyWTReport_Data();
                $blPipeData->workingTimeModel = $contract->working_time_scheme;
                $blPipeData->date = $this->_currentDate->getClone();
                if (isset($freeTimes[$dateStr])) {
                    $blPipeData->freeTimes = $freeTimes[$dateStr];
                }
                $blPipeData->feastTimes = $this->_getFeastTimes($dateStr, $contract->feast_calendar_id);

                $blPipeData->result = $oldReport ? $oldReport->getCleanClone() :
                    new HumanResources_Model_DailyWTReport([
                        'employee_id' => $employee,
                        'monthlywtreport' => $monthlyWTR->getId(),
                        'date' => clone $this->_currentDate,
                    ]);
                $blPipeData->allowTimesheetOverlap = true;
                if (isset($timeSheets[$dateStr])) {
                    if ($blPipe->hasInstanceOf(HumanResources_BL_DailyWTReport_BreakTime::class) ||
                        $blPipe->hasInstanceOf(HumanResources_BL_DailyWTReport_LimitWorkingTime::class)) {
                        $blPipeData->allowTimesheetOverlap = false;
                    }
                    $blPipeData->convertTimeSheetsToTimeSlots($timeSheets[$dateStr]);
                }

                $blPipe->execute($blPipeData);

                if (null === $oldReport) {
                    $this->create($blPipeData->result);
                    $dailyTransaction->commit();
                    $this->_reportResult['created'] += 1;
                } else {
                    if (!$blPipeData->result->diff($oldReport)->isEmpty()) {
                        $this->update($blPipeData->result);
                        $dailyTransaction->commit();
                        $this->_reportResult['updated'] += 1;
                    } else {
                        // just cleanup
                        $dailyTransaction->commit();
                    }
                }
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not create daily wt report for '
                    . $this->_currentDate->toString());
                $this->_reportResult['errors'] += 1;

                if (isset($existingReports[$dateStr])) {
                    $oldReport = $existingReports[$dateStr]->getCleanClone();
                    $oldReport->calculation_failure = 1;
                    $oldReport->system_remark =
                        Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)
                            ->_('unexpected error: ') . $e->getMessage();

                    $this->update($oldReport);
                } else {
                    if (null !== $monthlyWTR) {
                        $this->create(new HumanResources_Model_DailyWTReport([
                            'employee_id' => $employee,
                            'monthlywtreport' => $monthlyWTR->getId(),
                            'date' => clone $this->_currentDate,
                            'calculation_failure' => 1,
                            'system_remark' =>
                                Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)
                                    ->_('unexpected error: ') . $e->getMessage(),
                        ]));
                    } else {
                        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' no monthly WTR available!');
                    }
                }

                $dailyTransaction->commit();
            }

            unset($dailyTransaction); // will trigger a rollback if not committed
        }

        if (count($this->_monthlyWTR) > 0) {
            // import sort first! we need to recalculate the oldest month (that will trigger recalculation of all following months!)
            ksort($this->_monthlyWTR);
            HumanResources_Controller_MonthlyWTReport::getInstance()->recalculateReport(current($this->_monthlyWTR));
        }

        // to satifisfy unused variable check
        unset($rcRaii);

        return $this->_reportResult;
    }

    /**
     * @param HumanResources_Model_WorkingTimeScheme $_wts
     * @return Tinebase_BL_Pipe | false
     */
    protected function _getBLPipe(HumanResources_Model_WorkingTimeScheme $_wts)
    {
        if (!isset($this->_wtsBLPipes[$_wts->getId()])) {
            if (! $_wts->blpipe instanceof  Tinebase_Record_RecordSet || $_wts->blpipe->count() === 0) {
                return $this->_wtsBLPipes[$_wts->getId()] = false; // assignment on purpose
            }
            $rs = $_wts->blpipe->getClone(true);
            $record = new HumanResources_Model_BLDailyWTReport_Config([
                HumanResources_Model_BLDailyWTReport_Config::FLDS_CLASSNAME =>
                    HumanResources_Model_BLDailyWTReport_PopulateReportConfig::class,
                HumanResources_Model_BLDailyWTReport_Config::FLDS_CONFIG_RECORD => [],
            ]);
            $record->runConvertToRecord();
            $rs->addRecord($record);
            $this->_wtsBLPipes[$_wts->getId()] = new Tinebase_BL_Pipe($rs);
        }
        return $this->_wtsBLPipes[$_wts->getId()];
    }

    /**
     * @param string $dateStr
     * @param string $feastCalendarId
     * @return Tinebase_Record_RecordSet|null
     */
    protected function _getFeastTimes($dateStr, $feastCalendarId)
    {
        if (!isset($this->_feastDays[$feastCalendarId])) {
            $this->_feastDays[$feastCalendarId] = [];
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Calendar_Model_Event::class, [
                ['field' => 'container_id', 'operator' => 'equals', 'value' => $feastCalendarId],
                ['field' => 'dtstart', 'operator' => 'before_or_equals', 'value' => $this->_endDate],
                ['field' => 'dtend', 'operator' => 'after_or_equals', 'value' => $this->_startDate],

            ]);
            // turn off acl?
            /** @var Calendar_Model_Event $event */
            foreach (Calendar_Controller_Event::getInstance()->search($filter) as $event) {
                $event->dtstart->setTimezone($event->originator_tz);
                $day = $event->dtstart->format('Y-m-d');
                if (!isset($this->_feastDays[$feastCalendarId][$day])) {
                    $this->_feastDays[$feastCalendarId][$day] =
                        new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
                }
                $this->_feastDays[$feastCalendarId][$day]->addRecord($event);
            }
        }

        return isset($this->_feastDays[$feastCalendarId][$dateStr]) ? $this->_feastDays[$feastCalendarId][$dateStr]
            : null;
    }

    /**
     * @return Tinebase_DateTime
     */
    protected function _getStartDate()
    {
        return Tinebase_Model_Filter_Date::getFirstDayOf(Tinebase_Model_Filter_Date::MONTH_LAST);
    }

    /**
     * @return Tinebase_DateTime
     */
    protected function _getEndDate()
    {
        return Tinebase_DateTime::now()->setTime(23,59,59);
    }

    /**
     * @return HumanResources_Model_MonthlyWTReport
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _getOrCreateMonthlyWTR()
    {
        $month = $this->_currentDate->format('Y-m');

        if (!isset($this->_monthlyWTR[$month])) {
            $monthlyWTR = HumanResources_Controller_MonthlyWTReport::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_MonthlyWTReport::class, [
                    ['field' => HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID, 'operator' => 'equals',
                        'value' => $this->_employee->getId()],
                    ['field' => HumanResources_Model_MonthlyWTReport::FLDS_MONTH, 'operator' => 'equals',
                        'value' => $month],
                ]))->getFirstRecord();
            if (null === $monthlyWTR) {
                $monthlyWTR = HumanResources_Controller_MonthlyWTReport::getInstance()->create(
                    new HumanResources_Model_MonthlyWTReport([
                        HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID => $this->_employee->getId(),
                        HumanResources_Model_MonthlyWTReport::FLDS_MONTH => $month,
                    ]));
            }
            $this->_monthlyWTR[$month] = $monthlyWTR;
        }

        return $this->_monthlyWTR[$month];
    }

    /**
     * returns the employees DailyWTReports within interval as an array indexed by Y-m-d
     *
     * @return array
     */
    protected function _getEmployeesReports()
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_DailyWTReport::class, [
            ['field' => 'employee_id', 'operator' => 'AND', 'value' => [
                ['field' => ':id', 'operator' => 'equals', 'value' => $this->_employee->getId()]
            ]],
            ['field' => 'date', 'operator' => 'after_or_equals', 'value' => $this->_startDate->format('Y-m-d')],
            ['field' => 'date', 'operator' => 'before_or_equals', 'value' => $this->_endDate->format('Y-m-d')],

        ]);

        $result = [];
        /** @var HumanResources_Model_DailyWTReport $dwtr */
        foreach (HumanResources_Controller_DailyWTReport::getInstance()->search($filter) as $dwtr) {
            $dwtr->relations = Tinebase_Relations::getInstance()->getRelations(
                HumanResources_Model_DailyWTReport::class,
                Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                $dwtr->getId());
            $result[$dwtr->date->format('Y-m-d')] = $dwtr;
        }
        return $result;
    }

    /**
     * returns the employees timesheets within interval as an array of RecordSets indexed by Y-m-d
     *
     * @return array
     */
    protected function _getEmployeesTimesheets()
    {
        $filterData = [
            ['field' => 'account_id', 'operator' => 'equals', 'value' => $this->_employee->account_id],
            ['field' => 'start_date', 'operator' => 'after_or_equals', 'value' => $this->_startDate->format('Y-m-d')],
            ['field' => 'start_date', 'operator' => 'before_or_equals', 'value' => $this->_endDate->format('Y-m-d')],
        ];

        // fetch all timesheets of an employee of the current and last month
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Timetracker_Model_Timesheet::class,
            $filterData
        );

        $result = [];
        /** @var Timetracker_Model_Timesheet $ts */
        foreach (Timetracker_Controller_Timesheet::getInstance()->search($filter) as $ts) {
            $day = $ts->start_date->format('Y-m-d');
            if (!isset($result[$day])) {
                $result[$day] = new Tinebase_Record_RecordSet(Timetracker_Model_Timesheet::class, []);
            }
            $result[$day]->addRecord($ts);
        }
        return $result;
    }

    /**
     * returns the employees timesheets within interval as an array of RecordSets indexed by Y-m-d
     *
     * @return array
     */
    protected function _getEmployeesFreeTimes()
    {
        $start = $this->_startDate->format('Y-m-d');
        $end = $this->_endDate->format('Y-m-d');
        $filterData = [
            ['field' => 'employee_id', 'operator' => 'equals', 'value' => $this->_employee->getId()],
            ['field' => 'lastday_date', 'operator' => 'after_or_equals', 'value' => $start],
            ['field' => 'firstday_date', 'operator' => 'before_or_equals', 'value' => $end],
        ];

        // fetch all freetime of an employee between start and end
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            HumanResources_Model_FreeTimeFilter::class,
            $filterData
        );
        $freeTimes = HumanResources_Controller_FreeTime::getInstance()->search($filter);
        if ($freeTimes->count() === 0) return [];

        Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($freeTimes,
            HumanResources_Model_FreeTime::getConfiguration());
        // expand required properties
        $expander = new Tinebase_Record_Expander(HumanResources_Model_FreeTime::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'type' => [],
            ],
        ]);
        $expander->expand($freeTimes);

        $result = [];
        /** @var HumanResources_Model_FreeTime $ft */
        foreach ($freeTimes as $ft) {
            /** @var HumanResources_Model_FreeDay $fd */
            foreach ($ft->freedays as $fd) {
                $day = $fd->date->format('Y-m-d');
                if ($day >= $start && $day <= $end) {
                    if (!isset($result[$day])) {
                        $result[$day] = new Tinebase_Record_RecordSet(HumanResources_Model_FreeTime::class, []);
                    }
                    $result[$day]->addRecord($ft);
                }
            }
        }
        return $result;
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if (isset($this->_requestContext[self::RC_JSON_REQUEST])) {
            // _("daily wt reports can't be created")
            throw new Tinebase_Exception_SystemGeneric("daily wt reports can't be created");
        }
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if (isset($this->_requestContext[self::RC_JSON_REQUEST])) {
            $allowedProperties = [
                'evaluation_period_start_correction' => true,
                'evaluation_period_end_correction' => true,
                'working_time_correction' => true,
                'working_time_target_correction' => true,
                'user_remark' => true,
            ];
            foreach ($_record->getFields() as $prop) {
                if (!isset($allowedProperties[$prop])) {
                    $_record->{$prop} = $_oldRecord->{$prop};
                }
            }
        }

        if (($_record->is_cleared || $_oldRecord->is_cleared) && (!isset($this->_requestContext[self::RC_ALLOW_IS_CLEARED]) ||
                !$this->_requestContext[self::RC_ALLOW_IS_CLEARED])) {
            // _('It is not allowed to update a cleared report')
            throw new Tinebase_Exception_SystemGeneric('It is not allowed to update a cleared report');
        }
    }

    /**
     * inspect update of one record (after update)
     *
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        foreach (['evaluation_period_start_correction',
                  'evaluation_period_end_correction',
                  'working_time_correction',
                  'working_time_target_correction'] as $prop) {
            if ($currentRecord->{$prop} !== $updatedRecord->{$prop}) {
                $employee = HumanResources_Controller_Employee::getInstance()->get($updatedRecord->employee_id);
                $this->calculateReportsForEmployee($employee, $updatedRecord->date, $updatedRecord->date);
                break;
            }
        }
    }
}
