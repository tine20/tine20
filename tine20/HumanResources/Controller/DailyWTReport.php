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
    protected $_reports = [];
    protected $_reportResult = [
        'created' => 0,
        'updated' => 0,
        'errors' => 0,
    ];

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
     * @todo implement
     * @todo add daily scheduler job for this
     */
    public function calculateAllReports()
    {
        // @todo loop active employees
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
        $this->_employee = $employee;
        $this->_startDate = $startDate ? $startDate : $this->_getStartDate();
        $this->_endDate = $endDate ? $endDate : $this->_getEndDate();

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Calculating Daily Reports for ' . $employee->getTitle()
            . ' (From: ' . $this->_startDate->toString()
            . ' Until: ' . $this->_endDate->toString() . ')'
        );

        $this->_calcTimesheets();

        // @todo fetch all sickness/holiday/... of an employee

        // @todo loop all days from first of last month to present day

        // @todo create/update WorkingTimeReports for each day

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

        if (isset($this->_reports[$day])) {
            return $this->_reports[$day];
        }

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_DailyWTReport::class, [
            ['field' => 'employee_id', 'operator' => 'in', 'value' => [$this->_employee->getId()]],
            ['field' => 'date', 'operator' => 'equals', 'value' => $day],
        ]);
        $result = HumanResources_Controller_DailyWTReport::getInstance()->search($filter);
        if ($result->getFirstRecord()) {
            return $result->getFirstRecord();
        } else {
            return new HumanResources_Model_DailyWTReport([
                'employee_id' => $this->_employee->getId(),
                'date' => $day,
            ]);
        }
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
            $this->_updateReportWithTimesheet($timesheet);
        }
    }

    protected function _updateReportWithTimesheet($timesheet)
    {
        $dailyReport = $this->_getReportForDay($timesheet->start_date);
        $dailyReport->working_time_actual += $timesheet->duration;

        $this->_saveReport($dailyReport);
    }

    protected function _saveReport($dailyReport)
    {
        if ($dailyReport->getId()) {
            $this->update($dailyReport);
            $this->_reportResult['updated']++;
        } else {
            $this->create($dailyReport);
            $this->_reportResult['created']++;
        }

        // @todo error?
    }
}
