<?php
/**
 * Contract controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Contract controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_Contract extends Tinebase_Controller_Record_Abstract
{
    /**
     * true if sales is installed
     * 
     * @var boolean
     */
    protected $_useSales = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_Contract();
        $this->_modelName = 'HumanResources_Model_Contract';
        $this->_purgeRecords = FALSE;
        $this->_useSales = Tinebase_Application::getInstance()->isInstalled('Sales', TRUE);
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
    }

    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_Contract
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_Contract
     */
    public static function getInstance()
    {
        if (static::$_instance === NULL) {
            static::$_instance = new self();
        }

        return static::$_instance;
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if (! $_record->start_date) {
            return;
        }
        // do not update if the start_date is in the past and the creation time is older than 2 hours
        // disable fields in edit dialog if the record was created 2 hours before and the start_date is in the past
        $now = new Tinebase_DateTime();
        $created = clone $_record->creation_time;
        $crP2h = $created->addHour(2);

        // match = 1 if record was created 2 hrs ago
        $match = $now->compare($crP2h);
        
        // match = 1 if start date is in the past
        $match += $now->compare($_record->start_date);
        $this->_containerToId($_record);
        
        // both matches
        if ($match === 2) {
            foreach(array('start_date', 'end_date', 'employee_id', 'feast_calendar_id', 'workingtime_json') as $key) {
                if ($_record->{$key} != $_oldRecord->{$key}) {
                    // but allow if change is just the end_date which must be in the future
                    if (! ($key == 'end_date' && ($now->compare($_record->end_date) > -1))) {
                        throw new Tinebase_Exception_Data("You are not allowed to change the record if it's older than 2 hours and the start_date is in the past!");
                    }
                }
            }
        }
        
        $this->_checkDates($_record);
    }

    
    
    /**
     * checks the start_date and end_date
     * 
     * @param Tinebase_Record_Interface $_record
     */
    protected function _checkDates(Tinebase_Record_Interface $_record)
    {
        // if no end_date is given, no validation has to be done
        if (! $_record->end_date || ! ($_record->end_date instanceof Tinebase_DateTime)) {
            return;
        }
        
        if ($_record->end_date->isEarlier($_record->start_date)) {
            throw new Tinebase_Exception_Record_Validation('The start date of the contract must be before the end date!');
        }
    }
    /**
     * resolves the container array to the corresponding id
     * 
     * @param Tinebase_Record_Interface $_record
     */
    protected function _containerToId(Tinebase_Record_Interface $_record)
    {
        if (is_array($_record->feast_calendar_id)) {
            $_record->feast_calendar_id = $_record->feast_calendar_id['id'];
        }
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     *
     * @todo $_record->contracts should be a Tinebase_Record_RecordSet
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        if (! $_createdRecord->start_date) {
            return;
        }
        // find contract before, set end_date one day before the new contracts' start_date, if needed
        $filter = new HumanResources_Model_ContractFilter(array(array('field' => 'start_date', 'operator' => 'before', 'value' => $_createdRecord->start_date)));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'end_date' , 'operator' => 'equals', 'value' => NULL)));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'id' , 'operator' => 'not', 'value' => $_createdRecord->getId())));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id' , 'operator' => 'equals', 'value' => $_createdRecord->employee_id)));
        $contracts = $this->search($filter);
        
        if ($contracts->count() > 1) {
            throw new Tinebase_Exception_Data('There are more than 1 contracts before the new one without an end_date. Please terminate them before!');
        }
        // if a contract was found, terminate it
        if ($contracts->count()) {
            $contract = $contracts->getFirstRecord();
            $endDate = clone $_createdRecord->start_date;
            $endDate->subDay(1);
            $contract->end_date = $endDate;
            $this->update($contract);
        }
    }
    
    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_checkDates($_record);
        $this->_containerToId($_record);
        
        if (empty($_record->feast_calendar_id)) {
            $_record->feast_calendar_id = null;
        }
        
        // show if a contract before this exists
        $paging = new Tinebase_Model_Pagination(array('sort' => 'start_date', 'dir' => 'DESC', 'limit' => 1, 'start' => 0));
        $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->employee_id)));
        $lastRecord = $this->search($filter, $paging)->getFirstRecord();
        
        // if there is a contract already
        if ($lastRecord) {
            // terminate last contract one day before the new contract starts
            if (empty($lastRecord->end_date) && $_record->start_date) {
                $date = clone $_record->start_date;
                $lastRecord->end_date = $date->subDay(1);
                $lastRecord = $this->update($lastRecord, FALSE);
            }
            // set start day of the new contract one day after the last contracts' end day, if no date is given
            if (empty($_record->start_date) && $lastRecord->end_date) {
                $_record->start_date = $lastRecord->end_date->addDay(1);
            }
        }
    }
    
    /**
     * calculates the vacation days count of a contract for a period given by firstDate and lastDate. 
     * if the period exceeds the contracts' period, the contracts' period will be used
     * 
     * @param HumanResources_Model_Contract $contract
     * @param Tinebase_DateTime $firstDate
     * @param Tinebase_DateTime $lastDate
     * @return integer
     */
    public function calculateVacationDays(HumanResources_Model_Contract $contract, Tinebase_DateTime $firstDate, Tinebase_DateTime $lastDate)
    {
        $firstDate = $this->_getFirstDate($contract, $firstDate);
        $lastDate = $this->_getLastDate($contract, $lastDate);
        
        // find out how many days the year does have
        $januaryFirst = new Tinebase_DateTime($firstDate->format('Y') . '-01-01 00:00:00');
        $decemberLast = new Tinebase_DateTime($firstDate->format('Y') . '-12-31 23:59:59');
        
        $daysOfTheYear = ceil(($decemberLast->getTimestamp() - $januaryFirst->getTimestamp()) / 24 / 60 / 60);
        
        // find out how many days the contract does have
        $daysOfTheContract = ceil(($lastDate->getTimestamp() - $firstDate->getTimestamp()) / 24 / 60 / 60);
        
        return (int) round(($daysOfTheContract / $daysOfTheYear) * $contract->vacation_days);
    }
    
    /**
     * returns feast days as array containing Tinebase_DateTime objects
     * if the period exceeds the contracts' period, the contracts' period will be used
     * 
     * @param HumanResources_Model_Contract $contract
     * @param Tinebase_DateTime $firstDate
     * @param Tinebase_DateTime $lastDate
     * @return array
     */
    public function getFeastDays(HumanResources_Model_Contract $contract, Tinebase_DateTime $firstDate, Tinebase_DateTime $lastDate)
    {
        $firstDate = $this->_getFirstDate($contract, $firstDate);
        $lastDate  = $this->_getLastDate($contract, $lastDate);
        
        // on calendar search we have to do this to get the right interval:
        $firstDate->subSecond(1);
        $lastDate->addSecond(1);
        
        $filter = new Calendar_Model_EventFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'container_id', 'operator' => 'equals', 'value' => $contract->feast_calendar_id)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'dtstart', 'operator' => 'after', 'value' => $firstDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'dtstart', 'operator' => 'before', 'value' => $lastDate)));
        
        $dates = Calendar_Controller_Event::getInstance()->search($filter);
        
        $dates->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        // TODO: allow whole days and dates with more days
        return $dates->dtstart;
    }
    
    /**
     * calculates the first date by date and contract. the contract date is used if the start date is earlier
     * 
     * @param HumanResources_Model_Contract $contract
     * @param Tinebase_DateTime $firstDate
     * @return Tinebase_DateTime
     */
    protected function _getFirstDate(HumanResources_Model_Contract $contract, Tinebase_DateTime $firstDate)
    {
        $date = $contract->start_date ? $firstDate < $contract->start_date ? $contract->start_date : $firstDate : $firstDate;
        return clone $date;
    }
    
    /**
     * calculates the last date by date and contract. the contract date is used if the end date is later
     * 
     * @param HumanResources_Model_Contract $contract
     * @param Tinebase_DateTime $lastDate
     * @return Tinebase_DateTime
     */
    protected function _getLastDate(HumanResources_Model_Contract $contract, Tinebase_DateTime $lastDate)
    {
        $date = $contract->end_date ? $lastDate > $contract->end_date ? $contract->end_date : $lastDate : $lastDate;
        $date->setTime(23, 59, 59);
        return clone $date;
    }
    
    
    /**
     * returns all dates the employee have to work on by contract. the feast days are removed already
     * if the period exceeds the contracts' period, the contracts' period will be used. freetimes are not respected here
     * 
     * @param HumanResources_Model_Contract $contract
     * @param Tinebase_DateTime $firstDate
     * @param Tinebase_DateTime $lastDate
     * 
     * @return array
     */
    public function getDatesToWorkOn(HumanResources_Model_Contract $contract, Tinebase_DateTime $firstDate, Tinebase_DateTime $lastDate)
    {
        $firstDate = $this->_getFirstDate($contract, $firstDate);
        $lastDate = $this->_getLastDate($contract, $lastDate);
        
        $date = clone $firstDate;
        $json = $contract->getWorkingTimeJson();
        $weekdays = $json->days;

        // find out feast days
        $feastDays = $this->getFeastDays($contract, $firstDate, $lastDate);
        $feastDayStrings = array();
        
        foreach($feastDays as $feastDay) {
            $feastDayStrings[] = $feastDay->format('Y-m-d');
        }
        
        // datetime format w uses day 0 as sunday
        $monday = array_pop($weekdays);
        array_unshift($weekdays, $monday);

        $hoursToWorkOn = 0;
        $daysToWorkOn = array();
        $sumHours = 0;
        
        while ($date->isEarlier($lastDate)) {
            // if calculated working day is not a feast day, add to days to work on
            $ds = $date->format('Y-m-d');
            $weekday = $date->format('w');
            $hrs = $weekdays[$weekday];
            
            if (! in_array($ds, $feastDayStrings) && $hrs > 0) {
                $daysToWorkOn['results'][] = clone $date;
                $sumHours += $hrs;
            }
            
            $date->addDay(1);
        }
        $daysToWorkOn['hours'] = $sumHours;
        return $daysToWorkOn;
    }
    
    /**
     * Get valid contracts for the period specified
     * 
     * @param mixed $employeeId
     * @param Tinebase_DateTime $firstDate
     * @param Tinebase_DateTime $lastDate
     */
    public function getValidContracts($firstDate = NULL, $lastDate = NULL, $employeeId = NULL)
    {
        if (! ($firstDate && $lastDate)) {
            throw new Tinebase_Exception_InvalidArgument('All params are needed!');
        }
        
        if (is_array($employeeId)) {
            $employeeId = $employeeId['id'];
        } elseif (is_object($employeeId) && get_class($employeeId) == 'HumanResources_Model_Employee') {
            $employeeId = $employeeId->getId();
        }
        
        
        $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
        if ($employeeId) {
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $employeeId)));
        }
        $subFilter2 = new HumanResources_Model_ContractFilter(array(), 'OR');
        $subFilter21 = new HumanResources_Model_ContractFilter(array(), 'AND');
        $subFilter21->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before', 'value' => $lastDate)));
        $subFilter21->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'end_date', 'operator' => 'after', 'value' =>  $firstDate)));
        $subFilter22 = new HumanResources_Model_ContractFilter(array(), 'AND');
        $subFilter22->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before', 'value' => $lastDate)));
        $subFilter22->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'end_date', 'operator' => 'equals', 'value' => NULL)));
        $subFilter2->addFilterGroup($subFilter21);
        $subFilter2->addFilterGroup($subFilter22);
        $filter->addFilterGroup($subFilter2);
        
        return $this->search($filter);
    }
    
    /**
     * returns the active contract for the given employee and date or now, when no date is given
     * 
     * @param string $_employeeId
     * @param Tinebase_DateTime $_firstDayDate
     * @throws Tinebase_Exception_InvalidArgument
     * @throws HumanResources_Exception_NoCurrentContract
     * @throws Tinebase_Exception_Duplicate
     * @return HumanResources_Model_Contract
     */
    public function getValidContract($_employeeId, $_firstDayDate = NULL)
    {
        if (! $_employeeId) {
            throw new Tinebase_Exception_InvalidArgument('You have to set an account id at least');
        }
        $_firstDayDate = $_firstDayDate ? new Tinebase_DateTime($_firstDayDate) : new Tinebase_DateTime();
        
        $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before', 'value' => $_firstDayDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
        $endDate = new Tinebase_Model_Filter_FilterGroup(array(), 'OR');
        $endDate->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'end_date', 'operator' => 'after', 'value' =>  $_firstDayDate)));
        $filter->addFilterGroup($endDate);
        
        $contracts = $this->search($filter);
        
        if ($contracts->count() < 1) {
            $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
            $contracts = $this->search($filter);
            if ($contracts->count() > 0) {
                $e = new HumanResources_Exception_NoCurrentContract();
                $e->addRecord($contracts->getFirstRecord());
                throw $e;
            } else {
                throw new HumanResources_Exception_NoContract();
            }
        } else if ($contracts->count() > 1) {
            throw new Tinebase_Exception_Duplicate('There are more than one valid contracts for this employee!');
        }

        return $contracts->getFirstRecord();
    }
    
    /**
     * returns the contracts for an employee sorted by the start_date
     * 
     * @param string $employeeId
     * @return Tinebase_Record_RecordSet
     */
    public function getContractsByEmployeeId($employeeId)
    {
        $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
        $pagination = new Tinebase_Model_Pagination(array('sort' => 'start_date'));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $employeeId)));
        $recs = $this->search($filter, $pagination);
        
        return $recs;
    }
}
