<?php
/**
 * Account controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Account controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_Account extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_Account();
        $this->_modelName = 'HumanResources_Model_Account';
        $this->_purgeRecords = FALSE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
    }

    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_Account
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_Account
     */
    public static function getInstance()
    {
        if (static::$_instance === NULL) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    /**
     * creates missing accounts for all employees having a valid contract
     * if a year is given, missing accounts for this year will be built, otherwise the current and following year will be used
     *
     * @param integer $year
     * @param HumanResources_Model_Employee
     */
    public function createMissingAccounts($year = NULL, $employee = NULL)
    {
        // if no year is given, call myself with this year and just go on with the next year
        if (! $year) {
            $date = new Tinebase_DateTime();
            $year = (int) $date->format('Y');
            $this->createMissingAccounts($year, $employee);
            $date->addYear(1);
            $year = (int) $date->format('Y');
        }
    
        // tine20 should last a hundred years :)
        if ($year < 2006 || $year >= 2106 || ! is_int($year)) {
            throw new Tinebase_Exception_Data('The year must be between 2006 and 2106');
        }
    
        // borders
        $year_starts = new Tinebase_DateTime($year . '-01-01 00:00:00');
        $year_ends   = new Tinebase_DateTime($year . '-12-31 23:59:59');
        
        $validEmployeeIds = array_unique(HumanResources_Controller_Contract::getInstance()->getValidContracts($year_starts, $year_ends, $employee)->employee_id);
        
        $existingFilter = new HumanResources_Model_AccountFilter(array(
            array('field' => 'year', 'operator' => 'equals', 'value' => $year)
        ));
        $existingFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'in', 'value' => $validEmployeeIds)));
        
        $result = $this->search($existingFilter)->employee_id;
        
        $validEmployeeIds = array_diff($validEmployeeIds, $result);
        
        foreach($validEmployeeIds as $id) {
            $this->create(new HumanResources_Model_Account(array('employee_id' => $id, 'year' => $year)));
        }
    }
    
    /**
     * resolves all virtual fields for the account
     * 
     * @param HumanResources_Model_Account $account
     * @return array with property => value
     */
    public function resolveVirtualFields(HumanResources_Model_Account $account)
    {
        $yearBegins = new Tinebase_DateTime($account->year . '-01-01 00:00:00');
        $yearEnds   = new Tinebase_DateTime($account->year . '-12-31 23:59:59');
        
        $contractsController = HumanResources_Controller_Contract::getInstance();
        $contracts = $contractsController->getValidContracts($yearBegins, $yearEnds, $account->employee_id);
        $contracts->sort('start_date', 'ASC');
        
        // find out feast days by contract(s) of the accounts' year
        $feastDays = array();
        foreach ($contracts as $contract) {
            $feastDays = array_merge($contractsController->getFeastDays($contract, $yearBegins, $yearEnds), $feastDays);
        }
        
        // find out vacation days by contract and interval
        $possibleVacationDays = 0;
        foreach ($contracts as $contract) {
            $possibleVacationDays += $contractsController->calculateVacationDays($contract, $yearBegins, $yearEnds);
        }
        
        // add extra free times of this year (defined by account)
        if ($account->extra_free_times  && is_array($account->extra_free_times)) {
            foreach ($account->extra_free_times as $freeTime) {
                $possibleVacationDays += $freeTime['days'];
            }
        }
        
        // search freetimes also in last quarter of the year before, to get freedays starting in last year and ending in this year
        // the exact free days will be found out by searching the free days by date again
        $yearBeginsPlus3MonthsBefore = clone $yearBegins;
        $yearBeginsPlus3MonthsBefore->subMonth(3);
        
        // find out free days (vacation, sickness)
        $freetimeController = HumanResources_Controller_FreeTime::getInstance();
        $filter = new HumanResources_Model_FreeTimeFilter(array(
            array('field' => 'firstday_date', 'operator' => 'before', 'value' => $yearEnds),
            array('field' => 'firstday_date', 'operator' => 'after', 'value' => $yearBeginsPlus3MonthsBefore),
        ));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $account->employee_id->getId())));
        
        $freeTimes = $freetimeController->search($filter);
        
        $acceptedVacationTimes  = $freeTimes->filter('type', 'vacation')->filter('status', 'ACCEPTED');
        $unexcusedSicknessTimes = $freeTimes->filter('type', 'sickness')->filter('status', 'UNEXCUSED');
        $excusedSicknessTimes   = $freeTimes->filter('type', 'sickness')->filter('status', 'EXCUSED');
        
        $freedayController = HumanResources_Controller_FreeDay::getInstance();
        
        $filter = new HumanResources_Model_FreeDayFilter(array(
            array('field' => 'date', 'operator' => 'before', 'value' => $yearEnds),
            array('field' => 'date', 'operator' => 'after', 'value' => $yearBegins),
        ));

        $acceptedVacationFilter = clone $filter;
        $acceptedVacationFilter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $acceptedVacationTimes->id)));
        $acceptedVacationDays = $freedayController->search($acceptedVacationFilter);
        
        $unexcusedSicknessFilter = clone $filter;
        $unexcusedSicknessFilter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $unexcusedSicknessTimes->id)));
        $unexcusedSicknessDays = $freedayController->search($unexcusedSicknessFilter);

        $excusedSicknessFilter = clone $filter;
        $excusedSicknessFilter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $excusedSicknessTimes->id)));
        $excusedSicknessDays = $freedayController->search($excusedSicknessFilter);
        
        $workingDays = array();
        $sumHours = 0;
        
        foreach ($contracts as $contract) {
            $result = $contractsController->getDatesToWorkOn($contract, $yearBegins, $yearEnds);
            $workingDays = array_merge($result['results'], $workingDays);
            $sumHours += $result['hours'];
        }
        
        return array(
            'possible_vacation_days' => $possibleVacationDays,
            'remaining_vacation_days' => $possibleVacationDays - $acceptedVacationDays->count(),
            'taken_vacation_days' => $acceptedVacationDays->count(),
            'excused_sickness' => $excusedSicknessDays->count(),
            'unexcused_sickness' => $unexcusedSicknessDays->count(),
            'working_days' => count($workingDays) - $possibleVacationDays,
            'working_hours' => $sumHours
        );
    }
    
    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     * 
     * @todo $_record->contracts should be a Tinebase_Record_RecordSet
     * @todo use getMigration()
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    { 
        $_record->employee_id = $_oldRecord->employee_id;
        $_record->year = $_oldRecord->year;
        
        $config = $_record::getConfiguration()->recordsFields;
        
        foreach (array_keys($config) as $p) {
            $this->_updateDependentRecords($_record, $_oldRecord, $p, $config[$p]['config']);
        }
    }
}
