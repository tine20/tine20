<?php
/**
 * Tine 2.0
 * 
 * @package     HumanResources
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected $_configuredModels = array('Employee', 'Account', 'ExtraFreeTime', 'Contract', 'FreeDay', 'FreeTime', 'CostCenter');
    protected $_defaultModel = 'Employee';
    
    /**
     * the constructor
     */
    public function __construct()
    {
        $this->_applicationName = 'HumanResources';
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

        if (array_key_exists('account_id', $employee) && ! empty($employee['account_id']['contact_id'])) {
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
        $ids = array();
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
        
        // TODO: resolve this in controller
        foreach(array('vacation', 'sickness') as $type) {
            if (! empty($employee[$type]) && is_array($employee[$type])) {
                foreach($employee[$type] as $v) {
                    $ids[] = $v['account_id'];
                }
            }
        }
        
        $ids = array_unique($ids);
        $acs = HumanResources_Controller_Account::getInstance()->getMultiple($ids);
        
        foreach(array('vacation', 'sickness') as $type) {
            if (! empty($employee[$type]) && is_array($employee[$type])) {
                for ($i = 0; $i < count($employee[$type]); $i++) {
                    $account = $acs->filter('id', $employee[$type][$i]['account_id'])->getFirstRecord();
                    if ($account) {
                        $employee[$type][$i]['account_id'] = $account->toArray();
                    }
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
        return $this->_search($filter, $paging, HumanResources_Controller_WorkingTime::getInstance(), 'HumanResources_Model_WorkingTimeFilter');
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getWorkingTime($id)
    {
        return $this->_get($id, HumanResources_Controller_WorkingTime::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveWorkingTime($recordData)
    {
        return $this->_save($recordData, HumanResources_Controller_WorkingTime::getInstance(), 'WorkingTime');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteWorkingTime($ids)
    {
        return $this->_delete($ids, HumanResources_Controller_WorkingTime::getInstance());
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
        
        // validate employeeId
        $employee = $eController->get($_employeeId);
        $_freeTimeId = (strlen($_freeTimeId) == 40) ? $_freeTimeId : NULL;
        
        // set period to search for
        $minDate = new Tinebase_DateTime();
        if ($_year && (! $_freeTimeId)) {
            $minDate->setDate($_year, 1, 1);
        } elseif ($_freeTimeId) {
            $myFreeTime = HumanResources_Controller_FreeTime::getInstance()->get($_freeTimeId);
            $minDate->setDate($myFreeTime->firstday_date->format('Y'), 1, 1);
        } else {
            $minDate->setDate($minDate->format('Y'), 1, 1);
        }
        
        // find account
        $filter = new HumanResources_Model_AccountFilter(array(
            array('field' => 'year', 'operator' => 'equals', 'value' => intval($_year))
        ));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
        
        $account = $aController->search($filter)->getFirstRecord();
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
        
        $minDate->setTime(0,0,0);
        $maxDate = clone $minDate;
        $maxDate->addYear(1)->subSecond(1);

        // find contracts
        $contracts = $cController->getValidContracts($minDate, $maxDate, $_employeeId);
        $contracts->sort('start_date', 'ASC');
        $excludeDates = array();
        
        if ($contracts->count() < 1) {
            throw new HumanResources_Exception_NoContract();
        }
        
        $first = TRUE;
        
        $remainingVacation = 0;
        
        // find out disabled days for the different contracts
        foreach ($contracts as $contract) {
            $json = $contract->getWorkingTimeJson();
            $startDay = ($contract->start_date == NULL) ? $minDate : ($contract->start_date < $minDate) ? $minDate : $contract->start_date;
            $stopDay  = ($contract->end_date == NULL)   ? $maxDate : ($contract->end_date > $maxDate)   ? $maxDate : $contract->end_date;

            if ($first) {
                $firstDay = clone $startDay;
                $first = FALSE;
            }
            
            $remainingVacation += $cController->calculateVacationDays($contract, $minDate, $maxDate);
            
            if (is_object($json)) {
                foreach($json->days as $index => $hours) {
                    if ($hours === 0) {
                        $day = clone $startDay;
                        $day->setWeekDay(($index+1));
                        
                        while ($day->compare($stopDay) == -1) {
                            $excludeDates[] = clone $day;
                            $day->addWeek(1);
                        }
                    }
                }
            }
            // search feast days
            $excludeDates = array_merge($cController->getFeastDays($contract, $startDay, $stopDay), $excludeDates);
        }
        
        // search vacation days for this interval
        $filter = new HumanResources_Model_FreeTimeFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
        
        $freetimes = HumanResources_Controller_FreeTime::getInstance()->search($filter);
        
        $accountFreeTimes = $freetimes->filter('account_id', $account->getId());
        
        $filter = new HumanResources_Model_FreeDayFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Int(array('field' => 'duration', 'operator' => 'equals', 'value' => 1)));
        
        $filter1 = clone $filter;
        $filter1->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $freetimes->id)));
        $otherVacationDays = HumanResources_Controller_FreeDay::getInstance()->search($filter1);
        
        $filter2 = clone $filter;
        $filter2->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $accountFreeTimes->id)));
        
        $accountVacationDays = HumanResources_Controller_FreeDay::getInstance()->search($filter2);
        
        $ownFreeDays = NULL;
        
        if ($_freeTimeId) {
            $filter3 = clone $filter;
            $filter3->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => array($_freeTimeId))));
            $ownFreeDays = HumanResources_Controller_FreeDay::getInstance()->search($filter3);
            $otherVacationDays->removeRecords($ownFreeDays);
            $ownFreeDays = $ownFreeDays->toArray();
        }
        
        $remainingVacation -= $accountVacationDays->count();
        
        $excludeDates      = array_merge($otherVacationDays->date, $excludeDates);
        $otherVacationDays = $otherVacationDays ? $otherVacationDays->toArray() : NULL;
        
        return array(
            'results' => array(
                'remainingVacation' => $remainingVacation,
                'otherVacationDays' => $otherVacationDays, 
                'ownFreeDays'   => $ownFreeDays,
                'excludeDates'  => $excludeDates,
                'contracts'     => $contracts->toArray(),
                'employee'      => $employee->toArray(),
                'firstDay'      => $firstDay,
                'lastDay'       => $stopDay,
             ), 
            'totalcount' => count($excludeDates), 
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
        $data[$ci::DEFAULT_FEAST_CALENDAR] = $calid ? Tinebase_Container::getInstance()->get($calid)->toArray() : NULL;
        $data[$ci::VACATION_EXPIRES] = $ci->get($ci::VACATION_EXPIRES);
        return $data;
    }
}
