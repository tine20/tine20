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
     * @param   HumanResources_Model_Contract $_record      the update record
     * @param   HumanResources_Model_Contract $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        // sanitize container_id
        if (is_array($_record->feast_calendar_id)) {
            $_record->feast_calendar_id = $_record->feast_calendar_id['id'];
        }
        
        $diff = $_record->diff($_oldRecord, array(
            'created_by', 'creation_time', 'last_modified_by', 'last_modified_time', 'notes', 'end_date', 'seq', 'tags',
            // see 0011962: contract end_date can't be changed if vacation has been added
            // TODO fix json encoded field diff - this is only a workaround
            // sadly, there is currently no test that breaks without this hotfix :(
        ))->diff;

        if (! empty($diff)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . " Contract diff:" . print_r($diff, true));

            if ($this->getFreeTimes($_record)->filter('type', 'vacation')->count() > 0) {
                throw new HumanResources_Exception_ContractNotEditable();
            }
        }
        
        $this->_checkDates($_record);

        if (!empty($_record->{HumanResources_Model_Contract::FLDS_WORKING_TIME_SCHEME})) {
            $oldWts = $_oldRecord->{HumanResources_Model_Contract::FLDS_WORKING_TIME_SCHEME} ?: null;
            $this->_inspectWorkingTimeScheme($_record, $oldWts);
        }
    }

    
    /**
     * returns freetimes of a contract
     * 
     * @param HumanResources_Model_Contract $contract
     */
    public function getFreeTimes($contract)
    {
        $freeTimeFilter = new HumanResources_Model_FreeTimeFilter(array(
            array('field' => 'firstday_date', 'operator' => 'after_or_equals', 'value' => $contract->start_date),
        ));
        
        if ($contract->end_date !== NULL) {
            $freeTimeFilter->addFilter(new Tinebase_Model_Filter_Date(
                array('field' => 'firstday_date', 'operator' => 'before_or_equals', 'value' => $contract->end_date)
            ));
        }
        
        $freeTimeFilter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'employee_id', 'operator' => 'equals', 'value' => $contract->employee_id)
        ));

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . " FreeTime filter:" . print_r($freeTimeFilter->toArray(), true));
        
        $freeTimeController = HumanResources_Controller_FreeTime::getInstance();
        $results = $freeTimeController->search($freeTimeFilter);
        
        return $results;
    }

    /**
     * checks the start_date and end_date
     * 
     * @param Tinebase_Record_Interface $_record
     * @throws HumanResources_Exception_ContractDates
     */
    protected function _checkDates(Tinebase_Record_Interface $_record)
    {
        // if no end_date is given, no validation has to be done
        if (! $_record->end_date || ! ($_record->end_date instanceof Tinebase_DateTime)) {
            return;
        }
        
        if ($_record->end_date->isEarlier($_record->start_date)) {
            throw new HumanResources_Exception_ContractDates();
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
        $filter = new HumanResources_Model_ContractFilter(array(
            array('field' => 'start_date', 'operator' => 'before', 'value' => $_createdRecord->start_date)
        ));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'end_date' , 'operator' => 'isnull', 'value' => TRUE)));
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
            $contract->end_date = $endDate->addSecond(1);
            $this->update($contract);
        }
    }
    
    /**
     * inspect creation of one record (before create)
     *
     * @param   HumanResources_Model_Contract $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_checkDates($_record);
        $this->_containerToId($_record);
        
        if (empty($_record->feast_calendar_id)) {
            $_record->feast_calendar_id = null;
        }
        
        // if a contract before this exists without having an end_date, this is set here
        $paging = new Tinebase_Model_Pagination(array('sort' => 'start_date', 'dir' => 'DESC', 'limit' => 1, 'start' => 0));
        $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->employee_id)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before', 'value' => $_record->start_date)));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'end_date', 'operator' => 'isnull', 'value' => TRUE)));
        
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

        if (!empty($_record->{HumanResources_Model_Contract::FLDS_WORKING_TIME_SCHEME})) {
            $this->_inspectWorkingTimeScheme($_record);
        }
    }

    protected function _inspectWorkingTimeScheme(HumanResources_Model_Contract $_record, $_oldWTS = null)
    {
        /** @var HumanResources_Model_WorkingTimeScheme $recordWts */
        $recordWts = $_record->{HumanResources_Model_Contract::FLDS_WORKING_TIME_SCHEME};
        $wtsId = null;
        if (is_array($recordWts)) {
            if (isset($recordWts['id'])) {
                $wtsId = $recordWts['id'];
            }
            $recordWts = new HumanResources_Model_WorkingTimeScheme($recordWts);
        } else {
            $wtsId = $recordWts instanceof Tinebase_Record_Interface ? $recordWts->getId() : $recordWts;
        }

        $wtsController = HumanResources_Controller_WorkingTimeScheme::getInstance();

        if (null !== $wtsId) {
            /** @var HumanResources_Model_WorkingTimeScheme $wts */
            $wts = $wtsController->get($wtsId);
            if (!$recordWts instanceof Tinebase_Record_Interface) {
                $recordWts = $wts;
            }
            if ($wts->{HumanResources_Model_WorkingTimeScheme::FLDS_TYPE} ===
                    HumanResources_Model_WorkingTimeScheme::TYPES_SHARED) {
                $_record->{HumanResources_Model_Contract::FLDS_WORKING_TIME_SCHEME} = $wtsId;
                $recordWts = null;
            } elseif ($wts->{HumanResources_Model_WorkingTimeScheme::FLDS_TYPE} ===
                    HumanResources_Model_WorkingTimeScheme::TYPES_TEMPLATE) {
                $recordWts->setId(null);
            }
        }

        if (null !== $recordWts) {
            $recordWts->{HumanResources_Model_WorkingTimeScheme::FLDS_TYPE} =
                HumanResources_Model_WorkingTimeScheme::TYPES_INDIVIDUAL;

            if ($recordWts->getId()) {
                $wtsController->update($recordWts);
            } else {
                $employee = $_record->employee_id;
                if (is_string($employee)) {
                    $employee = HumanResources_Controller_Employee::getInstance()->get($employee);
                }
                $recordWts->{HumanResources_Model_WorkingTimeScheme::FLDS_TITLE} = $employee['number'] . ' ' .
                    $employee['n_fn'] . ' ' . $_record->start_date->getClone()
                        ->setTimezone(Tinebase_Core::getUserTimezone())->format('Y-m-d');

                $recordWts = $wtsController->create($recordWts);
            }
            $_record->{HumanResources_Model_Contract::FLDS_WORKING_TIME_SCHEME} = $recordWts->getId();
        }

        if (null !== $_oldWTS && $_oldWTS !== $_record->{HumanResources_Model_Contract::FLDS_WORKING_TIME_SCHEME}) {
            $wts = $wtsController->get($_oldWTS);
            if ($wts->{HumanResources_Model_WorkingTimeScheme::FLDS_TYPE} ===
                    HumanResources_Model_WorkingTimeScheme::TYPES_INDIVIDUAL) {
                $wtsController->delete($_oldWTS);
            }
        }
    }
    
    /**
     * calculates the vacation days count of a contract for a period given by firstDate and lastDate. 
     * if the period exceeds the contracts' period, the contracts' period will be used
     * 
     * @param HumanResources_Model_Contract|Tinebase_Record_RecordSet $contracts
     * @param Tinebase_DateTime $firstDate
     * @param Tinebase_DateTime $lastDate
     * @return float
     */
    public function calculateVacationDays($contracts, Tinebase_DateTime $gFirstDate, Tinebase_DateTime $gLastDate)
    {
        $contracts = $this->_convertToRecordSet($contracts);
        
        $sum = 0;
        
        foreach($contracts as $contract) {
            $firstDate = $this->_getFirstDate($contract, $gFirstDate);
            $lastDate = $this->_getLastDate($contract, $gLastDate);
            
            // find out how many days the year does have
            $januaryFirst = Tinebase_DateTime::createFromFormat('Y-m-d H:i:s e', $firstDate->format('Y') . '-01-01 00:00:00 ' . Tinebase_Core::getUserTimezone());
            $decemberLast = Tinebase_DateTime::createFromFormat('Y-m-d H:i:s e', $firstDate->format('Y') . '-12-31 23:59:59 ' . Tinebase_Core::getUserTimezone());
            
            $daysOfTheYear = ($decemberLast->getTimestamp() - $januaryFirst->getTimestamp()) / 24 / 60 / 60;
            
            // find out how many days the contract does have
            $daysOfTheContract = ($lastDate->getTimestamp() - $firstDate->getTimestamp()) / 24 / 60 / 60;
            
            $correl = $daysOfTheContract / $daysOfTheYear;
            $sum = $sum + (($correl) * $contract->vacation_days);
        }
        
        return $sum;
    }
    
    /**
     * returns feast days as array containing Tinebase_DateTime objects
     * if the period exceeds the contracts' period(s), the contracts' period(s) will be used
     * 
     * @param HumanResources_Model_Contract|Tinebase_Record_RecordSet $contracts
     * @param Tinebase_DateTime $firstDate
     * @param Tinebase_DateTime $lastDate
     * @return array
     */
    public function getFeastDays($contracts, Tinebase_DateTime $firstDate, Tinebase_DateTime $lastDate)
    {
        $contracts = $this->_convertToRecordSet($contracts);
        
        $dates = array();
        foreach($contracts as $contract) {
        
            $fd = $this->_getFirstDate($contract, $firstDate);
            $ld = $this->_getLastDate($contract, $lastDate);
            
            // on calendar search we have to do this to get the right interval:
            $fd->subSecond(1);
            $ld->addSecond(1);
            
            $periodFilter = new Calendar_Model_PeriodFilter(
                array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $fd, 'until' => $ld)));
            
            $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
                array('field' => 'container_id', 'operator' => 'equals', 'value' =>
                    $contract->feast_calendar_id),
            )));
            
            Calendar_Model_Rrule::mergeRecurrenceSet($events, $fd, $ld);
            
            $events->setTimezone(Tinebase_Core::getUserTimezone());
            
            foreach($events as $event) {
                if (! $event->isInPeriod($periodFilter)) {
                    continue;
                }
                if ($event->is_all_day_event) {
                    $days = round(($event->dtend->getTimestamp() - $event->dtstart->getTimestamp()) / 86400);
                    $i=0;
                    
                    while ($i < $days) {
                        $dateOfEvent = clone $event->dtstart;
                        $dateOfEvent->addDay($i);
                        $dates[] = $dateOfEvent;
                        $i++;
                    }
                } else {
                    $dates[] = $event->dtstart;
                }
            }
        }
        
        return array_unique($dates);
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
     * if the period exceeds the contracts' period, the contracts' period will be used. 
     * freetimes are not respected here, if $respectTakenVacationDays is not set to TRUE
     * 
     * @param HumanResources_Model_Contract|Tinebase_Record_RecordSet $contracts
     * @param Tinebase_DateTime $firstDate
     * @param Tinebase_DateTime $lastDate
     * @param boolean $respectTakenVacationDays
     * 
     * @return array
     */
    public function getDatesToWorkOn($contracts, Tinebase_DateTime $firstDate, Tinebase_DateTime $lastDate, $respectTakenVacationDays = FALSE)
    {
        $contracts = $this->_convertToRecordSet($contracts);
        
        // find out feast days
        $feastDays = $this->getFeastDays($contracts, $firstDate, $lastDate);
        $freeDayStrings = array();
        
        
        foreach($feastDays as $feastDay) {
            $freeDayStrings[] = $feastDay->format('Y-m-d');
        }
        
        if ($respectTakenVacationDays) {
            $vacationTimes = new Tinebase_Record_RecordSet('HumanResources_Model_FreeTime');
            
            foreach($contracts as $contract) {
                $vacationTimes = $vacationTimes->merge($this->getFreeTimes($contract));
            }
            
            $filter = new HumanResources_Model_FreeDayFilter(array());
            $filter->addFilter(new Tinebase_Model_Filter_Text(
                array('field' => 'freetime_id','operator' => 'in', 'value' => $vacationTimes->id)
            ));
            $vacationDays = HumanResources_Controller_FreeDay::getInstance()->search($filter);
            foreach($vacationDays as $vDay) {
                $freeDayStrings[] = $vDay->date->format('Y-m-d');
            }
        }
        
        $hoursToWorkOn = 0;
        $results = array();
        $sumHours = 0;
        
        foreach($contracts as $contract) {
        
            $firstDate = $this->_getFirstDate($contract, $firstDate);
            $lastDate = $this->_getLastDate($contract, $lastDate);
            
            $date = clone $firstDate;
            $json = $contract->getWorkingTimeJson();
            $weekdays = $json['days'];
            
            // datetime format w uses day 0 as sunday
            $monday = array_pop($weekdays);
            array_unshift($weekdays, $monday);
            
            while ($date->isEarlier($lastDate)) {
                // if calculated working day is not a feast day, add to days to work on
                $ds = $date->format('Y-m-d');
                $weekday = $date->format('w');
                $hrs = $weekdays[$weekday];
                
                if (! in_array($ds, $freeDayStrings) && $hrs > 0) {
                    $results[] = clone $date;
                    $sumHours += $hrs;
                }
                
                $date->addDay(1);
            }
        }
        
        return array(
            'hours'   => $sumHours,
            'results' => $results
        );
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
        $subFilter22->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'end_date', 'operator' => 'isnull', 'value' => TRUE)));
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
        $filter = new HumanResources_Model_ContractFilter(array(
            array('field' => 'employee_id', 'operator' => 'equals', 'value' => $employeeId)
        ), 'AND');
        $pagination = new Tinebase_Model_Pagination(array('sort' => 'start_date'));
        $recs = $this->search($filter, $pagination);
        
        return $recs;
    }
    
    /**
     * resolves virtual field "is_editable"
     * 
     * @param array $resultSet
     */
    public function getEditableState($resultSet)
    {
        if (isset($resultSet['id'])) {
            $resultSet = [$resultSet];
        }
        for ($i = 0; $i < count($resultSet); $i++) {
            
            $sDate = new Tinebase_DateTime($resultSet[$i]['start_date']);
            $sDate->setTimezone(Tinebase_Core::getUserTimezone());
            
            $eDate = NULL;
            
            if ($resultSet[$i]['end_date']) {
                $eDate = new Tinebase_DateTime($resultSet[$i]['end_date']);
                $eDate->setTimezone(Tinebase_Core::getUserTimezone());
            }
            
            $freeTimeFilter = new HumanResources_Model_FreeTimeFilter(array(
                array('field' => 'firstday_date', 'operator' => 'after', 'value' => $sDate),
                array('field' => 'type', 'operator' => 'equals', 'value' => 'vacation'),
            ));
            
            if ($eDate) {
                $freeTimeFilter->addFilter(new Tinebase_Model_Filter_Date(
                    array('field' => 'firstday_date', 'operator' => 'before', 'value' => $eDate)
                ));
            }
            
            $freeTimeFilter->addFilter(new Tinebase_Model_Filter_Text(
                array('field' => 'employee_id', 'operator' => 'equals', 'value' => $resultSet[$i]['employee_id'])
            ));
            
            $freeTimeController = HumanResources_Controller_FreeTime::getInstance();
            $resultSet[$i]['is_editable'] = ($freeTimeController->search($freeTimeFilter)->count() > 0) ? FALSE : TRUE;
        }
        
        return $resultSet;
    }
}
