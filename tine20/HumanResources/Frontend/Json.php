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
        'Employee',
        'Account',
        HumanResources_Model_FreeTimeType::MODEL_NAME_PART,
        'ExtraFreeTime',
        'FreeDay',
        'FreeTime',
        HumanResources_Model_BLDailyWTReport_WorkingTime::MODEL_NAME_PART,
        HumanResources_Model_DailyWTReport::MODEL_NAME_PART,
        HumanResources_Model_MonthlyWTReport::MODEL_NAME_PART,
        HumanResources_Model_WageType::MODEL_NAME_PART,
        HumanResources_Model_WorkingTimeScheme::MODEL_NAME_PART,
    ];

    protected $_defaultModel = 'Employee';
    
    /**
     * the constructor
     */
    public function __construct()
    {
        $this->_applicationName = 'HumanResources';
        if (! HumanResources_Config::getInstance()->featureEnabled(
            HumanResources_Config::FEATURE_WORKING_TIME_ACCOUNTING)
        ) {
            $this->_configuredModels = array_diff($this->_configuredModels, [
                HumanResources_Model_BLDailyWTReport_BreakTimeConfig::MODEL_NAME_PART,
                HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::MODEL_NAME_PART,
                HumanResources_Model_FreeTimeType::MODEL_NAME_PART,
                HumanResources_Model_BLDailyWTReport_WorkingTime::MODEL_NAME_PART,
                HumanResources_Model_DailyWTReport::MODEL_NAME_PART,
                HumanResources_Model_MonthlyWTReport::MODEL_NAME_PART,
                HumanResources_Model_WageType::MODEL_NAME_PART,
            ]);
        }
    }

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

    /**
     * calculate all daily working time reports
     *
     * @return void
     */
    public function calculateAllDailyWTReports()
    {
        // NOTE: this method calcs daily & monthly
        HumanResources_Controller_DailyWTReport::getInstance()->calculateAllReports();
    }

    /**
     * calculate all monthly working time reports
     *
     * @return void
     */
    public function calculateAllMonthlyWTReports()
    {
        // NOTE: this method calcs daily & monthly
        HumanResources_Controller_DailyWTReport::getInstance()->calculateAllReports();
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
            $cc = Sales_Controller_CostCenter::getInstance()->search(new Sales_Model_CostCenterFilter(array()));
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
        
        $aids = array();
        $fids = array();
        
        // TODO: resolve this in controller
        foreach(array('vacation', 'sickness') as $type) {
            if (! empty($employee[$type]) && is_array($employee[$type])) {
                foreach($employee[$type] as $v) {
                    $aids[] = $v['account_id'];
                    $fids[] = $v['id'];
                }
            }
        }
        
        $aids = array_unique($aids);
        $fids = array_unique($fids);
        $acs = HumanResources_Controller_Account::getInstance()->getMultiple($aids);
        $freedayFilter = new HumanResources_Model_FreeDayFilter(array());
        $freedayFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $fids)));
        $fds = HumanResources_Controller_FreeDay::getInstance()->search($freedayFilter);
        $fds->setTimezone(Tinebase_Core::getUserTimezone());
        
        foreach(array('vacation', 'sickness') as $type) {
            if (! empty($employee[$type]) && is_array($employee[$type])) {
                for ($i = 0; $i < count($employee[$type]); $i++) {
                    
                    $account = $acs->filter('id', $employee[$type][$i]['account_id'])->getFirstRecord();
                    
                    if ($account) {
                        $employee[$type][$i]['account_id'] = $account->toArray();
                    }
                    
                    $freedays = $fds->filter('freetime_id', $employee[$type][$i]['id']);
                    $employee[$type][$i]['freedays'] = $freedays->toArray();
                }
            }
        }
        
        return $employee;
    }

    /**
     * book remaining vacation days for the next year
     * 
     * @param array $ids
     */
    public function bookRemaining($ids) {
        return array('success' => HumanResources_Controller_Account::getInstance()->bookRemainingVacation($ids));
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
        
        foreach(array('vacation', 'sickness') as $prop) {
            if (! empty($recordData[$prop])) {
                for ($i = 0; $i < count($recordData[$prop]); $i++) {
                    // add account id by year if no account id is given (sickness)
                    if (! $recordData[$prop][$i]['account_id']) {
                        
                        $date = new Tinebase_DateTime($recordData[$prop][$i]['firstday_date']);
                        $year = $date->format('Y');
                        
                        if (! isset($this->_cachedAccountsOnSaveEmployee[$year])) {
                            $filter = new HumanResources_Model_AccountFilter(array(array('field' => 'year', 'operator' => 'equals', 'value' => $year)));
                            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $recordData['id'])));
                            $account = HumanResources_Controller_Account::getInstance()->search($filter)->getFirstRecord();
                            if (! $account) {
                                throw new HumanResources_Exception_NoAccount();
                            }
                            $this->_cachedAccountsOnSaveEmployee[$year] = $account;
                        } else {
                            $account = $this->_cachedAccountsOnSaveEmployee[$year];
                        }
                        
                        $recordData[$prop][$i]['account_id'] = $account->getId();
                    }
                    // flat account id
                    if (is_array($recordData[$prop][$i]['account_id'])) {
                        $recordData[$prop][$i]['account_id'] = $recordData[$prop][$i]['account_id']['id'];
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
     * @param string $_employeeId
     * @param integer $_year
     * @param string $_freeTimeId
     * @param string $_accountId
     */
    public function getFeastAndFreeDays($_employeeId, $_year = NULL, $_freeTimeId = NULL, $_accountId = NULL)
    {
        $cController = HumanResources_Controller_Contract::getInstance();
        $eController = HumanResources_Controller_Employee::getInstance();
        $aController = HumanResources_Controller_Account::getInstance();
        $ftController = HumanResources_Controller_FreeTime::getInstance();
        $fdController = HumanResources_Controller_FreeDay::getInstance();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' $_employeeId ' . $_employeeId . ' $_year ' . $_year . ' $_freeTimeId ' . $_freeTimeId . ' $_accountId ' . $_accountId);
        
        // validate employeeId
        $employee = $eController->get($_employeeId);
        $_freeTimeId = (strlen($_freeTimeId) == 40) ? $_freeTimeId : NULL;
        
        // set period to search for
        $minDate = Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        if ($_year && (! $_freeTimeId)) {
            $minDate->setDate($_year, 1, 1);
        } elseif ($_freeTimeId) {
            // if a freetime id is given, take the year of the freetime
            $myFreeTime = $ftController->get($_freeTimeId);
            $minDate->setDate($myFreeTime->firstday_date->format('Y'), 1, 1);
        } else {
            $minDate->setDate($minDate->format('Y'), 1, 1);
        }
        
        if (! $_accountId) {
            // find account
            $filter = new HumanResources_Model_AccountFilter(array(
                array('field' => 'year', 'operator' => 'equals', 'value' => intval($_year))
            ));
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
            
            $account = $aController->search($filter)->getFirstRecord();
        } else {
            try {
                $account = $aController->get($_accountId);
            } catch (Exception $e) {
                // throws a few lines later: HumanResources_Exception_NoAccount
            }
        }
        
        if (! $account) {
            throw new HumanResources_Exception_NoAccount();
        }
        
        $accountYear = $account->year;
        $minAccountDate = Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        $minAccountDate->setDate($accountYear, 1, 1);
        $maxAccountDate = clone $minAccountDate;
        $maxAccountDate->addYear(1)->subSecond(1);
        
        $maxDate = clone $minDate;
        $maxDate->addYear(1)->subSecond(1);

        // find contracts of the account year
        $contracts = $cController->getValidContracts($minAccountDate, $maxAccountDate, $_employeeId);
        $contracts->sort('start_date', 'ASC');
        
        if ($contracts->count() < 1) {
            throw new HumanResources_Exception_NoContract();
        }
        
        $remainingVacation = 0;
        
        $contracts->setTimezone(Tinebase_Core::getUserTimezone());
        
        // find out total amount of vacation days for the different contracts
        foreach ($contracts as $contract) {
            $remainingVacation += $cController->calculateVacationDays($contract, $minDate, $maxDate);
        }
        
        $remainingVacation = round($remainingVacation, 0);
        $allVacation = $remainingVacation;
        
        // find contracts of the year in which the vacation days will be taken
        $contracts = $cController->getValidContracts($minDate, $maxDate, $_employeeId);
        $contracts->sort('start_date', 'ASC');
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
                        while ($day->compare($stopDay) == -1) {
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
        
        // search free times for the account and the interval
        
        // prepare free time filter, add employee_id
        $freeTimeFilter = new HumanResources_Model_FreeTimeFilter(array(), 'AND');
        $freeTimeFilter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
        
        // don't search for freetimes belonging to the freetime handled itself
        if ($_freeTimeId) {
            $freeTimeFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'id', 'operator' => 'not', 'value' => $_freeTimeId)));
        }
        
        // prepare vacation times filter
        $vacationTimesFilter = clone $freeTimeFilter;
        $vacationTimesFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'type', 'operator' => 'equals', 'value' => 'vacation')));
        
        // search all vacation times belonging to the account, regardless which interval we want
        $accountFreeTimesFilter = clone $vacationTimesFilter;
        $accountFreeTimesFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'account_id', 'operator' => 'equals', 'value' => $account->getId())));
        $accountVacationTimeIds = $ftController->search($accountFreeTimesFilter)->id;
        
        // search all vacation times for the interval
        $fddMin = clone $minDate;
        $fddMin->subDay(1);
        $fddMax = clone $maxDate;
        $fddMax->addDay(1);
        
        $vacationTimesFilter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'firstday_date', 'operator' => 'after', 'value' => $fddMin)));
        $vacationTimesFilter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'firstday_date', 'operator' => 'before', 'value' => $fddMax)));
        $vacationTimes = $ftController->search($vacationTimesFilter);
        
        $acceptedVacationTimes = $vacationTimes->filter('status', 'ACCEPTED');
        
//        // search all sickness times for the interval
//        $sicknessTimesFilter = clone $freeTimeFilter;
//        $sicknessTimesFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'type', 'operator' => 'equals', 'value' => 'sickness')));
//        $sicknessTimesFilter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'firstday_date', 'operator' => 'after', 'value' => $fddMin)));
//        $sicknessTimesFilter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'firstday_date', 'operator' => 'before', 'value' => $fddMax)));
//        $sicknessTimes = $ftController->search($sicknessTimesFilter);
        
        // search free days belonging the found free times
        
        // prepare free day filter
        $freeDayFilter = new HumanResources_Model_FreeDayFilter(array(), 'AND');
        $freeDayFilter->addFilter(new Tinebase_Model_Filter_Int(array('field' => 'duration', 'operator' => 'equals', 'value' => 1)));
        
        // find vacation days belonging to the account (date doesn't matter, may be from another year, just count the days)
        if (count($accountVacationTimeIds)) {
            $accountFreeDayFilter = clone $freeDayFilter;
            $accountFreeDayFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $accountVacationTimeIds)));
            $remainingVacation = $remainingVacation - $fdController->search($accountFreeDayFilter)->count();
        }
        
//        // find all vacation days of the period
//        $vacationDayFilter = clone $freeDayFilter;
//        $vacationDayFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $vacationTimes->id)));
//
//        $vacationDays = $fdController->search($vacationDayFilter);
        
        // find out accepted vacation days. Vacation days will be substracted from remainingVacation only if they are accepted,
        // but they will be shown in the freetime edit dialog
        // TODO: discuss this
        $acceptedVacationDayFilter = clone $freeDayFilter;
        $acceptedVacationDayFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $acceptedVacationTimes->id)));
        $acceptedVacationDays = $fdController->search($acceptedVacationDayFilter);
        
        // calculate extra vacation days
        if ($account) {
            $filter = new HumanResources_Model_ExtraFreeTimeFilter(array());
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'account_id', 'operator' => 'equals', 'value' => $account->getId())));
            $account->extra_free_times = HumanResources_Controller_ExtraFreeTime::getInstance()->search($filter);
            $extraFreeTimes = $aController->calculateExtraFreeTimes($account, $acceptedVacationDays);
            $allVacation = $allVacation + $extraFreeTimes['remaining'];
            $remainingVacation = $remainingVacation + $extraFreeTimes['remaining'];
        } else {
            $extraFreeTimes = NULL;
        }
        
//        // find all sickness days of the period
//        $sicknessDayFilter = clone $freeDayFilter;
//        $sicknessDayFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $sicknessTimes->id)));
//        $sicknessDays = $fdController->search($sicknessDayFilter);
        
        $ownFreeDays = NULL;

        // "own" means the freeDays of the currently loaded freeTime!
        if ($_freeTimeId) {
            $ownFreeDaysFilter = clone $freeDayFilter;
            $ownFreeDaysFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => array($_freeTimeId))));
            $ownFreeDays = $fdController->search($ownFreeDaysFilter);
            $remainingVacation = $remainingVacation - $ownFreeDays->count();
            $ownFreeDays = $ownFreeDays->toArray();
        }

        $allFreeTimes = $ftController->search($freeTimeFilter);
        $allFreeDayFilter = clone $freeDayFilter;
        $allFreeDayFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $allFreeTimes->id)));
        $allFreeDays = $fdController->search($allFreeDayFilter);
        $freeTimeTypes = HumanResources_Controller_FreeTimeType::getInstance()->getAll();

        // TODO: remove results property, just return results array itself
        return array(
            'results' => array(
                'remainingVacation' => floor($remainingVacation),
                'extraFreeTimes'    => $extraFreeTimes,
//                'vacationDays'      => $vacationDays->toArray(),
//                'sicknessDays'      => $sicknessDays->toArray(),
                'excludeDates'      => $excludeDates,
                'ownFreeDays'       => $ownFreeDays,
                'allVacation'       => $allVacation,
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
