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
    protected $_contractController = NULL;
    
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
        $this->_contractController = HumanResources_Controller_Contract::getInstance();
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
        
        $validEmployeeIds = array_unique($this->_contractController->getValidContracts($year_starts, $year_ends, $employee)->employee_id);
        
        $existingFilter = new HumanResources_Model_AccountFilter(array(
            array('field' => 'year', 'operator' => 'equals', 'value' => $year)
        ));
        $existingFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'in', 'value' => $validEmployeeIds)));
        
        $result = $this->search($existingFilter)->employee_id;
        
        $validEmployeeIds = array_diff($validEmployeeIds, $result);
        $createdAccounts = new Tinebase_Record_RecordSet('HumanResources_Model_Account');
        foreach($validEmployeeIds as $id) {
            $createdAccounts->addRecord($this->create(new HumanResources_Model_Account(array('employee_id' => $id, 'year' => $year))));
        }
        
        return $createdAccounts;
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
        
        $contracts = $this->_contractController->getValidContracts($yearBegins, $yearEnds, $account->employee_id);
        $contracts->sort('start_date', 'ASC');
        
        // find out feast days by contract(s) of the accounts' year
        $feastDays = $this->_contractController->getFeastDays($contracts, $yearBegins, $yearEnds);
        
        // find out vacation days by contract(s) and interval
        $possibleVacationDays = $this->_contractController->calculateVacationDays($contracts, $yearBegins, $yearEnds);
        
        // find out free days (vacation, sickness)
        $freetimeController = HumanResources_Controller_FreeTime::getInstance();
        $employeeId = is_object($account->employee_id) ? $account->employee_id->getId() : $account->employee_id;
        
        $filter = new HumanResources_Model_FreeTimeFilter(array(
            array('field' => 'firstday_date', 'operator' => 'before', 'value' => $yearEnds),
            array('field' => 'firstday_date', 'operator' => 'after',  'value' => $yearBegins)
        ), 'AND');
        
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $employeeId)));
        
        $freeTimes = $freetimeController->search($filter);
    
        $filter = new HumanResources_Model_FreeTimeFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_DateTime(array('field' => 'firstday_date', 'operator' => 'isnull', 'value' => TRUE)));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'account_id', 'operator' => 'equals', 'value' => $account->getId())));

        $rebookedVacationTimes  = $freetimeController->search($filter);
        $acceptedVacationTimes  = $freeTimes->filter('type', 'vacation')->filter('status', 'ACCEPTED');
        $unexcusedSicknessTimes = $freeTimes->filter('type', 'sickness')->filter('status', 'UNEXCUSED');
        $excusedSicknessTimes   = $freeTimes->filter('type', 'sickness')->filter('status', 'EXCUSED');
        
        $freedayController = HumanResources_Controller_FreeDay::getInstance();
        
        $filter = new HumanResources_Model_FreeDayFilter(array(
            array('field' => 'date', 'operator' => 'before', 'value' => $yearEnds),
            array('field' => 'date', 'operator' => 'after',  'value' => $yearBegins),
        ), 'AND');

        $acceptedVacationFilter = clone $filter;
        $acceptedVacationFilter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $acceptedVacationTimes->id)));
        $acceptedVacationDays = $freedayController->search($acceptedVacationFilter);
        
        $unexcusedSicknessFilter = clone $filter;
        $unexcusedSicknessFilter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $unexcusedSicknessTimes->id)));
        $unexcusedSicknessDays = $freedayController->search($unexcusedSicknessFilter);

        $excusedSicknessFilter = clone $filter;
        $excusedSicknessFilter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $excusedSicknessTimes->id)));
        $excusedSicknessDays = $freedayController->search($excusedSicknessFilter);
        
        $filter = new HumanResources_Model_FreeDayFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $rebookedVacationTimes->id)));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'date', 'operator' => 'isnull', 'value' => TRUE)));
        $rebookedVacationDays = $freedayController->search($filter);
        
        $datesToWorkOn = $this->_contractController->getDatesToWorkOn($contracts, $yearBegins, $yearEnds);
        
        $expiredVacationDays = 0;
        
        // add extra free times of this year, if not expired (defined by account)
        if ($account->extra_free_times) {
            $extraFreeTimes = $this->_calculateExtraFreeTimes($account, $acceptedVacationDays);
            $possibleVacationDays += $extraFreeTimes['remaining'];
        }
        
        return array(
            'possible_vacation_days'  => $possibleVacationDays,
            'expired_vacation_days'   => isset($extraFreeTimes) ? $extraFreeTimes['expired'] : 0,
            'rebooked_vacation_days'  => $rebookedVacationDays->count(),
            'remaining_vacation_days' => $possibleVacationDays - $acceptedVacationDays->count() - $rebookedVacationDays->count(),
            'taken_vacation_days'     => $acceptedVacationDays->count(),
            'excused_sickness'        => $excusedSicknessDays->count(),
            'unexcused_sickness'      => $unexcusedSicknessDays->count(),
            'working_days'            => count($datesToWorkOn['results']) - $possibleVacationDays,
            'working_hours'           => $datesToWorkOn['hours']
        );
    }
    
    /**
     * calculates remaining and expired extra free times for an account 
     * combined with all accepted vacation days for an account
     * 
     * @param HumanResources_Model_Account $account
     * @param unknown $acceptedVacationDays
     * @return multitype:number
     */
    protected function _calculateExtraFreeTimes($account, $acceptedVacationDays)
    {
        // clone to find out if vacation was booked before expiration date of eft
        $tempAVD = clone $acceptedVacationDays;
        $now = Tinebase_DateTime::now();
        
        $extraFreeTimes = array('remaining' => 0, 'expired' => 0);
        
        foreach ($account->extra_free_times as $eft) {
            // if eft expires in future, or there is no expiration date, always add them to pvd
            if ($eft['expires'] == NULL || $eft['expires'] > $now) {
                $extraFreeTimes['remaining'] += (int) $eft['days'];
            } else {
                // if not, show if there are booked vacation days before expiration date of this eft
                $daysLeft = (int) $eft['days'];
                foreach ($tempAVD as $freeday) {
                    if ($daysLeft == 0) {
                        break;
                    }
                    if ($freeday->date < $eft['expires']) {
                        $daysLeft--;
                        $tempAVD->removeRecord($freeday);
                    }
                }
                // left days are efts which have been expired before any vacation day has been booked
                $extraFreeTimes['remaining'] += ((int) $eft['days'] - $daysLeft);
                $extraFreeTimes['expired']   += $daysLeft;
            }
        }
        
        return $extraFreeTimes;
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
    
    /**
     * book remaining vacation days for the next year
     * 
     * @param array $accountIds
     * @return booleam
     */
    public function bookRemainingVacation($accountIds)
    {
        $filter = new HumanResources_Model_AccountFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => $accountIds)
        ));
        
        $accounts = $this->search($filter);
        $freeTimeController = HumanResources_Controller_FreeTime::getInstance();
        $freeDayController = HumanResources_Controller_FreeDay::getInstance();
        $configInstance = HumanResources_Config::getInstance();
        $extraFreeTimeController = HumanResources_Controller_ExtraFreeTime::getInstance();
        $db = (method_exists($this->_backend, 'getAdapter')) ? $this->_backend->getAdapter() : Tinebase_Core::getDb();
        $thisYear = (int) Tinebase_DateTime::now()->format('Y');
        
        try {
            foreach($accounts as $account) {
                $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
                
                if ($account->year >= $thisYear) {
                    throw new HumanResources_Exception_RemainingNotBookable();
                }
                
                $data = $this->resolveVirtualFields($account);
                
                // do nothing if there are no remaining vacation days
                if ($data['remaining_vacation_days'] <= 0) {
                    continue;
                }
                
                $year = intval($account->year) + 1;
                
                // get account of next year
                $filter = new HumanResources_Model_AccountFilter(array(
                    array('field' => 'year', 'operator' => 'equals', 'value' => $year),
                ));
                
                $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $account->employee_id)));
                
                $result = $this->search($filter);
                
                if ($result->count() == 0) {
                    $ca = $this->createMissingAccounts($year, $account->employee_id);
                    $newAccount = $ca->getFirstRecord();
                } elseif ($result->count() > 1) {
                    throw new Tinebase_Exception_Record_NotAllowed('There is more than one account for the year ' . $year . '!');
                } else {
                    $newAccount = $result->getFirstRecord();
                }
                
                // create new extraFreetime for the new year
                $extraFreeTime = new HumanResources_Model_ExtraFreeTime(array(
                    'days'       => $data['remaining_vacation_days'],
                    'account_id' => $newAccount->getId(),
                    'type'       => 'PAYED',
                    'description' => 'Booked from last year',
                    'expires'     => $configInstance->getVacationExpirationDate()
                ));
                
                $extraFreeTimeController->create($extraFreeTime);
                
                // create freetimes for old year
                $freetime = $freeTimeController->create(new HumanResources_Model_FreeTime(array(
                    'type' => 'vacation',
                    'description' => 'Booked as extra freetime for next year.',
                    'status' => 'ACCEPTED',
                    'firstday_date' => NULL,
                    'employee_id' => $account->employee_id,
                    'account_id' => $account->getId(),
                )));
                
                $i=0;
                while($i < $data['remaining_vacation_days']) {
                    $freeDay = $freeDayController->create(new HumanResources_Model_FreeDay(array(
                        'freetime_id' => $freetime->getId(),
                        'duration' => 1,
                        'date' => null
                    )));
                    $i++;
                }
            }
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            return true;
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }
}
