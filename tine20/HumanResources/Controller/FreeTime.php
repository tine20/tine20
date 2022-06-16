<?php
/**
 * FreeTime controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * FreeTime controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_FreeTime extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_FreeTime();
        $this->_modelName = 'HumanResources_Model_FreeTime';
        $this->_purgeRecords = TRUE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
    }

    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_FreeTime
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_FreeTime
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new HumanResources_Controller_FreeTime();
        }

        return self::$_instance;
    }
    
    /**
     * returns remaining vacation days for given employee (mixed accounts)
     * 
     * @param string|HumanResources_Model_Employee $employeeId
     * @param DateTime $actualUntil | vacation days are computed as taken vacation until this date, null means forever (all scheduled days count as taken)
     * @param DateTime $expiryReferenceDate | reference day for vacation_expiary_date evaluation
     * @return int
     */
    public function getRemainingVacationDays($employeeId, DateTime $actualUntil = null, DateTime $expiryReferenceDate = null)
    {
        $accountController = HumanResources_Controller_Account::getInstance();
        $currentAccount = $accountController->getByEmployeeYear($employeeId, ($actualUntil ?: Tinebase_DateTime::now())->format('Y'));
        $actualUntil = $actualUntil ?: Tinebase_DateTime::now()->addYear(100);
        $expiryReferenceDate = $expiryReferenceDate ?: Tinebase_DateTime::now();
        $currentVacations = $accountController->resolveVacation($currentAccount, $actualUntil);
        $remainingPreviousVacationDays = 0;
        $previousAccount = $accountController->getByEmployeeYear($employeeId, $currentAccount->year-1);
        
        if ($previousAccount) {
            $previousVacations = $accountController->resolveVacation($previousAccount, $actualUntil);
            $remainingPreviousVacationDays = ($previousVacations['vacation_expiary_date']
                > $expiryReferenceDate ? $previousVacations['actual_remaining_vacation_days'] : 0);
        }
        return $currentVacations['actual_remaining_vacation_days'] + $remainingPreviousVacationDays;
    }

    /**
     * returns taken vacation days for given period & employee (mixed accounts)
     * 
     * @param string|HumanResources_Model_Employee $employeeId
     * @param DateTime[] $period [from => ..., until => ...]
     * @return Tinebase_Record_RecordSet of HumanResources_Model_FreeDay
     */
    public function getTakenVacationDays($employeeId, $period)
    {
        $employeeId = $employeeId instanceof HumanResources_Model_Employee ? $employeeId->getId() : $employeeId;
        $freeTimes = $this->search(new HumanResources_Model_FreeTimeFilter([
            ['field' => 'employee_id', 'operator' => 'equals', 'value' => $employeeId],
            ['field' => 'type',        'operator' => 'equals', 'value' => HumanResources_Model_FreeTimeType::ID_VACATION],
        ]));
        
        return HumanResources_Controller_FreeDay::getInstance()->search(new HumanResources_Model_FreeDayFilter([
            ['field' => 'freetime_id', 'operator' => 'equals', 'value' => $freeTimes->getId()],
            ['field' => 'date',        'operator' => 'within', 'value' => $period]
        ]));
    }
    
    /**
     * inspect update of one record (after setReleatedData)
     *
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord)
    {
        $this->_inspect($updatedRecord);
    }

    /**
     * inspect create of one record (after setReleatedData)
     *
     * @param   Tinebase_Record_Interface $createdRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @return  void
     */
    protected function _inspectAfterSetRelatedDataCreate($createdRecord, $record)
    {
        $this->_inspect($createdRecord);
    }

    protected function _inspect($record)
    {
        if (empty($freeDays = $record->freedays)) {
            return;
        }

        // update first and last date
        $freeDays->sort('date', 'ASC');
        $record->firstday_date = $freeDays->getFirstRecord()->date;
        $freeDays->sort('date', 'DESC');
        $record->lastday_date = $freeDays->getFirstRecord()->date;
        $record->days_count = $freeDays->count();

        $this->_backend->update($record);

        if ($record->type == 'sickness') {
            $this->_handleOverwrittenVacation($record);
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
       if (is_array($_record->employee_id)) {
           $_record->employee_id = $_record->employee_id['id'];
       }
   }
   
   /**
    * finds overwritten by sickness days overwritten vacation days. 
    * deletes the overwritten vacation day and the vacation itself if days_count = 0
    *
    * @param Tinebase_Record_Interface $_record
    */
   protected function _handleOverwrittenVacation($_record) {
       
       $fdController = HumanResources_Controller_FreeDay::getInstance();
       
       $changedFreeTimes = array();
       
       foreach($_record->freedays as $freeday) {
           
           $vacationTimeFilter = new HumanResources_Model_FreeTimeFilter(array(
               array('field' => 'type', 'operator' => 'equals', 'value' => 'vacation')
           ));
           $vacationTimeFilter->addFilter(new Tinebase_Model_Filter_Text(
               array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->employee_id)
           ));
           
           $vacationTimes = $this->search($vacationTimeFilter);
           
           $filter = new HumanResources_Model_FreeDayFilter(array(
               array('field' => 'date', 'operator' => 'equals', 'value' => $freeday['date'])
           ));
           
           $filter->addFilter(new Tinebase_Model_Filter_Text(
               array('field' => 'freetime_id', 'operator' => 'not', 'value' => $_record->getId())
               ));
           $filter->addFilter(new Tinebase_Model_Filter_Text(
               array('field' => 'freetime_id', 'operator' => 'in', 'value' => $vacationTimes->id)
           ));
           
           $vacationDay = $fdController->search($filter)->getFirstRecord();
           
           if ($vacationDay) {
               $fdController->delete($vacationDay->getId());
               
               $freeTime = $this->get($vacationDay->freetime_id);
               
               if (! isset($changedFreeTimes[$vacationDay->freetime_id])) {
                   $changedFreeTimes[$vacationDay->freetime_id] = $freeTime;
               }
               
               $count = (int) $changedFreeTimes[$vacationDay->freetime_id]->days_count - 1;
               $changedFreeTimes[$vacationDay->freetime_id]->days_count = $count;
           }
       }
       
       foreach($changedFreeTimes as $freeTimeId => $freetime) {
           if ($freetime->days_count == 0) {
               $this->delete($freetime->getId());
           } else {
               $freeTime->days_count = $count;
               $this->update($freetime);
           }
       }
   }
}
