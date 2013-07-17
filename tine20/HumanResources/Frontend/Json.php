<?php
/**
 * Tine 2.0
 * @package     HumanResources
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
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
     * @param DateTime $_firstDayDate
     * @param string $_freeTimeId
     */
    public function getFeastAndFreeDays($_employeeId, $_firstDayDate = NULL, $_freeTimeId = NULL)
    {
        $cController = HumanResources_Controller_Contract::getInstance();
        $eController = HumanResources_Controller_Employee::getInstance();
        // validate employeeId
        $employee = $eController->get($_employeeId);
        
        // set period to search for
        $minDate = new Tinebase_DateTime();
        $minDate->setDate($minDate->format('Y'), 1, 1)->setTime(0,0,0);
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
            $stopDay  = ($contract->end_date == NULL) ? $maxDate : ($contract->end_date > $maxDate)   ? $maxDate : $contract->end_date;

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
        
        // search free times for this interval
        $filter = new HumanResources_Model_FreeTimeFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'firstday_date', 'operator' => 'before', 'value' => $maxDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'firstday_date', 'operator' => 'after', 'value' => $minDate)));
        
        $freetimes = HumanResources_Controller_FreeTime::getInstance()->search($filter);

        $filter = new HumanResources_Model_FreeDayFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Int(array('field' => 'duration', 'operator' => 'equals', 'value' => 1)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'date', 'operator' => 'before', 'value' => $maxDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'date', 'operator' => 'after', 'value' => $minDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $freetimes->id)));
        
        $otherFreeDays = HumanResources_Controller_FreeDay::getInstance()->search($filter);
        
        $remainingVacation -= $otherFreeDays->count();
        
        if ($_freeTimeId) {
            $ownFreeDays   = $otherFreeDays->filter('freetime_id', $_freeTimeId);
            $otherFreeDays->removeRecords($ownFreeDays);
            $ownFreeDays = $ownFreeDays->toArray();
        } else {
            $ownFreeDays = NULL;
        }
        
        $excludeDates = array_merge($otherFreeDays->date, $excludeDates);
        $otherFreeDays = $otherFreeDays ? $otherFreeDays->toArray() : NULL;
        
        return array(
            'results' => array(
                // @todo: CALCULATE BY ACCOUNT
                'remainingVacation' => $remainingVacation,
                'otherFreeDays' => $otherFreeDays, 
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
        $calid = HumanResources_Config::getInstance()->get(HumanResources_Config::DEFAULT_FEAST_CALENDAR, NULL);
        $data['defaultFeastCalendar'] = $calid ? Tinebase_Container::getInstance()->get($calid)->toArray() : NULL;
        return $data;
    }
}
