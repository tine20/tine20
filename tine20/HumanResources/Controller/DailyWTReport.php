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
    /**
     * @var HumanResources_Model_Employee
     */
    protected $_employee = null;

    protected $_startDate = null;
    protected $_endDate = null;
    protected $_reportsByDay = [];
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_oldReports = null;
    protected $_reportResult = null;

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
     *  reports are created and all reports which from this and the last month which
     *  don't have their is_cleared flag set get updated. Older reports can be
     *  created/updated manually in the UI
     *
     * @return boolean
     */
    public function calculateAllReports()
    {
        // @todo filter some more? for example only current employees with active contracts?
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class);
        $iterator = new Tinebase_Record_Iterator(array(
            'iteratable' => $this,
            'controller' => $this,
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
        return $result;
    }

    /**
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
        // init some member vars
        $this->_employee = $employee;
        $this->_startDate = $startDate ? $startDate : $this->_getStartDate();
        $this->_endDate = $endDate ? $endDate : $this->_getEndDate();
        $this->_reportsByDay = [];
        $this->_reportResult = [
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];;
        $this->_oldReports = new Tinebase_Record_RecordSet(HumanResources_Model_DailyWTReport::class);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Calculating Daily Reports for ' . $employee->getTitle()
            . ' (From: ' . $this->_startDate->toString()
            . ' Until: ' . $this->_endDate->toString() . ')'
        );

        $this->_calcTimesheets();

        // @todo fetch all sickness/holiday/... of an employee

        // @todo loop all days from first of last month to present day

        return $this->_reportResult;
    }

    protected function _getStartDate()
    {
        return Tinebase_Model_Filter_Date::getFirstDayOf(Tinebase_Model_Filter_Date::MONTH_LAST);
    }

    protected function _getEndDate()
    {
        return Tinebase_Model_Filter_Date::getLastDayOf(Tinebase_Model_Filter_Date::MONTH_THIS);
    }

    protected function _getReportForDay(Tinebase_DateTime $dayDT)
    {
        $day = $dayDT->format('Y-m-d');

        if (isset($this->_reportsByDay[$day])) {
            return $this->_reportsByDay[$day];
        }

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_DailyWTReport::class, [
            ['field' => 'employee_id', 'operator' => 'AND', 'value' => [
                ['field' => ':id', 'operator' => 'equals', 'value' => $this->_employee->getId()]
            ]],
            ['field' => 'date', 'operator' => 'equals', 'value' => $day . ' 00:00:00'],
        ]);
        $result = HumanResources_Controller_DailyWTReport::getInstance()->search($filter);

        if ($result->getFirstRecord()) {
            $report = $result->getFirstRecord();
            $this->_oldReports->addRecord($report);
            $newReport = clone($report);

            // @todo reset all time fields
            $newReport->working_time_actual = 0;
        } else {
            $newReport = new HumanResources_Model_DailyWTReport([
                'employee_id' => $this->_employee->getId(),
                'date' => $day,
            ]);
        }

        $this->_reportsByDay[$day] = $newReport;
        return $newReport;
    }

    protected function _calcTimesheets()
    {
        $filterData = [
            ['field' => 'account_id', 'operator' => 'equals', 'value' => $this->_employee->account_id],
            ['field' => 'start_date', 'operator' => 'after_or_equals', 'value' => $this->_startDate],
            ['field' => 'start_date', 'operator' => 'before_or_equals', 'value' => $this->_endDate],
            // @todo check why this is not working
//            ['field' => 'start_date', 'operator' => 'within', 'value' => [
//                'from' => $this->_startDate,
//                'until' => $this->_endDate,
//            ]],
        ];

        // fetch all timesheets of an employee of the current and last month
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Timetracker_Model_Timesheet::class,
            $filterData
        );
        // @todo use paging/order by?
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($filter);

        // calc
        foreach ($timesheets as $timesheet) {
            $dailyReport = $this->_getReportForDay($timesheet->start_date);
            $dailyReport->working_time_actual += $timesheet->duration;
        }
        $this->_updateReports();
    }

    /**
     * update the reports
     */
    protected function _updateReports()
    {
        foreach ($this->_reportsByDay as $dailyReport) {
            if ($dailyReport->getId()) {

                // get current report
                $currentReport = $this->_oldReports->getById($dailyReport->getId());

                // @todo check diff of all timefields
                if ($currentReport->working_time_actual != $dailyReport->working_time_actual) {
                    $this->update($dailyReport);
                    $this->_reportResult['updated']++;
                }
            } else {
                $this->create($dailyReport);
                $this->_reportResult['created']++;
            }
        }

        // @todo do something on error?
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     * @throws Tinebase_Exception_Record_NotAllowed
     *
     * @todo use Tinebase_ModelConfiguration_Const::CONTROLLER_HOOK_BEFORE_UPDATE ?
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if ($_oldRecord->is_cleared == 1) {
            throw new Tinebase_Exception_Record_NotAllowed('It is not allowed to update a cleared report');
        }
    }
}
