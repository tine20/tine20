<?php
/**
 * FreeTime controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
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
    use Tinebase_Controller_SingletonTrait;
    use HumanResources_Controller_CheckFilterACLEmployeeTrait;

    protected $_getMultipleGrant = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLget = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLupdate  = [HumanResources_Model_DivisionGrants::UPDATE_CHANGE_REQUEST];
    protected $_requiredFilterACLsync  = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLexport  = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct() {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_FreeTime();
        $this->_modelName = HumanResources_Model_FreeTime::class;
        $this->_purgeRecords = TRUE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = true;
        $this->_traitGetOwnGrants = [
            HumanResources_Model_DivisionGrants::READ_OWN_DATA,
            HumanResources_Model_DivisionGrants::CREATE_OWN_CHANGE_REQUEST
        ];
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        /** @var HumanResources_Model_FreeTime $_record */
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        // if we have manage_employee right, we have all grants
        if (Tinebase_Core::getUser()->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::MANAGE_EMPLOYEE)) {
            return true;
        }

        if (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::UPDATE_EMPLOYEE_DATA, false) ||
                parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::GRANT_ADMIN, false)) {
            return true;
        }
        if (self::ACTION_DELETE !== $_action &&
                parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::UPDATE_CHANGE_REQUEST, false)) {
            return true;
        }

        switch ($_action) {
            case self::ACTION_GET:
                if (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST, false) ||
                        ($this->_checkOwnEmployee($_record) &&
                            (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::CREATE_OWN_CHANGE_REQUEST, false) ||
                                parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::READ_OWN_DATA, false)
                            ))) {
                    return true;
                }
                break;
            case self::ACTION_CREATE:
            case self::ACTION_UPDATE:
                if (HumanResources_Config::FREE_TIME_PROCESS_STATUS_REQUESTED === $_record->{HumanResources_Model_FreeTime::FLD_PROCESS_STATUS} &&
                        (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::CREATE_CHANGE_REQUEST, false) ||
                            (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::CREATE_OWN_CHANGE_REQUEST, false) &&
                            $this->_checkOwnEmployee($_record)))) {
                    return true;
                }
                break;
            case self::ACTION_DELETE:
                break;
        }

        throw new Tinebase_Exception_AccessDenied($_errorMessage);
    }

    protected function _checkOwnEmployee(HumanResources_Model_FreeTime $record): bool
    {
        $ctrl = HumanResources_Controller_Employee::getInstance();
        $oldValue = $ctrl->doContainerACLChecks(false);

        $raii = new Tinebase_RAII(function() use($oldValue, $ctrl) { $ctrl->doContainerACLChecks($oldValue); });
        $result = $ctrl->get($record->getIdFromProperty('employee_id'))->account_id === Tinebase_Core::getUser()->getId();
        unset($raii);
        return $result;
    }

    /**
     * returns remaining vacation days for given employee (mixed accounts)
     * 
     * @param string|HumanResources_Model_Employee $employeeId
     * @param DateTime $actualUntil | vacation days are computed as taken vacation until this date, null means forever/scheduled
     * @return int
     */
    public function getRemainingVacationDays($employeeId, DateTime $actualUntil = null)
    {
        $accountController = HumanResources_Controller_Account::getInstance();
        $currentAccount = $accountController->getByEmployeeYear($employeeId, ($actualUntil ?: Tinebase_DateTime::now())->format('Y'));
        $actualUntil = $actualUntil ?: Tinebase_DateTime::now()->addYear(100);
        $currentVacations = $accountController->resolveVacation($currentAccount, $actualUntil);
        $remainingPreviousVacationDays = 0;
        $previousAccount = $accountController->getByEmployeeYear($employeeId, $currentAccount->year-1);
        
        if ($previousAccount) {
            $previousVacations = $accountController->resolveVacation($previousAccount, $actualUntil);
            $remainingPreviousVacationDays = ($previousVacations['vacation_expiary_date']
                > Tinebase_DateTime::now() ? $previousVacations['actual_remaining_vacation_days'] : 0);
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
            ['field' => HumanResources_Model_FreeTime::FLD_PROCESS_STATUS, 'operator' => 'equals', 'value' => HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED],
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
               array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->getIdFromProperty('employee_id'))
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
