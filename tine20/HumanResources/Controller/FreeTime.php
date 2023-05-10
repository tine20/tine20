<?php
/**
 * FreeTime controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2023 Metaways Infosystems GmbH (http://www.metaways.de)
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
            case self::ACTION_DELETE:
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
     * @param DateTime $actualUntil | vacation days are computed as taken vacation until this date, null means forever (all scheduled days count as taken)
     * @param DateTime $expiryReferenceDate | reference day for vacation_expiary_date evaluation
     * @return int
     */
    public function getRemainingVacationDays($employeeId, DateTime $actualUntil = null, DateTime $expiryReferenceDate = null)
    {
        $accountController = HumanResources_Controller_Account::getInstance();
        $currentAccountYear = ($actualUntil ?: Tinebase_DateTime::now())->format('Y');
        $currentAccount = $accountController->getByEmployeeYear($employeeId, $currentAccountYear);
        $actualUntil = $actualUntil ?: Tinebase_DateTime::now()->addYear(100);
        $expiryReferenceDate = $expiryReferenceDate ?: Tinebase_DateTime::now();
        $currentVacations = $currentAccount ? $accountController->resolveVacation($currentAccount, $actualUntil) : ['actual_remaining_vacation_days' => 0];
        $remainingPreviousVacationDays = 0;
        $previousAccount = $accountController->getByEmployeeYear($employeeId, --$currentAccountYear);
        
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
        $this->_inspect($updatedRecord, $currentRecord);

        if ($updatedRecord->{HumanResources_Model_FreeTime::FLD_PROCESS_STATUS} !==
                $currentRecord->{HumanResources_Model_FreeTime::FLD_PROCESS_STATUS}) {
            foreach ($updatedRecord->freedays as $freeday) {
                HumanResources_Controller_FreeDay::getInstance()->inspectFreeDay($freeday);
            }
        }
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

    protected function _inspect(HumanResources_Model_FreeTime $record, ?HumanResources_Model_FreeTime $oldRecord = null)
    {
        if (!$record->freedays instanceof Tinebase_Record_RecordSet) {
            $record->freedays = HumanResources_Controller_FreeDay::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_FreeDay::class, [
                    ['field' => 'freetime_id', 'operator' => 'equals', 'value' => $record->getId()],
                ])
            );
        }
        $freeDays = $record->freedays;
        // update first and last date
        $freeDays->sort('date', 'ASC');
        $record->firstday_date = clone $freeDays->getFirstRecord()->date;
        $record->lastday_date = clone $freeDays->getLastRecord()->date;

        $ftt = HumanResources_Controller_FreeTimeType::getInstance()->get($record->getIdFromProperty('type'));
        if (in_array($ftt->getIdFromProperty('wage_type'), [
            HumanResources_Model_WageType::ID_SICK,
            HumanResources_Model_WageType::ID_SICK_CHILD,
            HumanResources_Model_WageType::ID_SICK_SICKPAY,
        ])) {
            $freeDays->filter('sickoverwrite', true)->sickoverwrite = false;
            $this->_handleOverwrittenVacation($record, $oldRecord);
        } elseif (!empty($freeDays)) {
            $this->_markSickOverwrites($record);
        }

        foreach ($freeDays as $freeDay) {
            if ($freeDay->isDirty()) {
                HumanResources_Controller_FreeDay::getInstance()->update($freeDay);
            }
        }

        $record->days_count = $freeDays->filter('sickoverwrite', false)->count();
        $this->_backend->update($record);

        $dwtrCtrl = HumanResources_Controller_DailyWTReport::getInstance();
        $dwtrRaii = new Tinebase_RAII($dwtrCtrl->assertPublicUsage());

        if (($oldRecord && $dwtrCtrl->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    HumanResources_Model_DailyWTReport::class,[
                    ['field' => 'date', 'operator' => 'after_or_equals', 'value' => $oldRecord->firstday_date],
                    ['field' => 'employee_id', 'operator' => 'equals', 'value' => $oldRecord->employee_id],
                    ['field' => 'is_cleared', 'operator' => 'equals', 'value' => true],
                    ['field' => 'date', 'operator' => 'before_or_equals', 'value' => $oldRecord->lastday_date],
                ])) > 0) ||
                $dwtrCtrl->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    HumanResources_Model_DailyWTReport::class,[
                    ['field' => 'date', 'operator' => 'after_or_equals', 'value' => $record->firstday_date],
                    ['field' => 'employee_id', 'operator' => 'equals', 'value' => $record->employee_id],
                    ['field' => 'is_cleared', 'operator' => 'equals', 'value' => true],
                    ['field' => 'date', 'operator' => 'before_or_equals', 'value' => $record->lastday_date],
                ])) > 0) {
            throw new Tinebase_Exception_SystemGeneric('wtr during affected time is already booked, no changes possible anymore');
        }

        unset($dwtrRaii);

        if ((null === $oldRecord && $record->{HumanResources_Model_FreeTime::FLD_PROCESS_STATUS} !==
                \HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED) || ($oldRecord &&
                $oldRecord->{HumanResources_Model_FreeTime::FLD_PROCESS_STATUS} !==
                    \HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED &&
                    $record->{HumanResources_Model_FreeTime::FLD_PROCESS_STATUS} !==
                    \HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED)) {
            return;
        }

        $event = new Tinebase_Event_Record_Update();
        $event->observable = clone $record;
        if ($oldRecord && $oldRecord->firstday_date && (!$record->firstday_date || $oldRecord->firstday_date->isEarlier($record->firstday_date))) {
            $event->observable->firstday_date = clone $oldRecord->firstday_date;
        }
        Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(function() use($event) {
            Tinebase_Record_PersistentObserver::getInstance()->fireEvent($event);
        });
    }

    protected function _inspectDelete(array $_ids)
    {
        $_ids = parent::_inspectDelete($_ids);

        $dwtrCtrl = HumanResources_Controller_DailyWTReport::getInstance();
        $dwtrRaii = new Tinebase_RAII($dwtrCtrl->assertPublicUsage());
        /** @var HumanResources_Model_FreeTime $freeTime */
        foreach ($this->getMultiple($_ids, true) as $freeTime) {
            if ($dwtrCtrl->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    HumanResources_Model_DailyWTReport::class,[
                        ['field' => 'date', 'operator' => 'after_or_equals', 'value' => $freeTime->firstday_date],
                        ['field' => 'employee_id', 'operator' => 'equals', 'value' => $freeTime->employee_id],
                        ['field' => 'is_cleared', 'operator' => 'equals', 'value' => true],
                        ['field' => 'date', 'operator' => 'before_or_equals', 'value' => $freeTime->lastday_date],
                    ])) > 0) {
                unset($_ids[array_search($freeTime->getId(), $_ids)]);
            }
        }
        unset($dwtrRaii);

        return $_ids;
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

   protected function _markSickOverwrites(HumanResources_Model_FreeTime $_record)
   {
       if (!$_record->freedays instanceof Tinebase_Record_RecordSet || $_record->freedays->count() === 0) {
           return;
       }

       $sickDays = HumanResources_Controller_FreeDay::getInstance()->search(
           Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_FreeDay::class, [
               ['field' => 'freetime_id', 'operator' => 'definedBy', 'value' => [
                   ['field' => 'type', 'operator' => 'definedBy', 'value' => [
                       ['field' => 'wage_type', 'operator' => 'in', 'value' => [
                           HumanResources_Model_WageType::ID_SICK,
                           HumanResources_Model_WageType::ID_SICK_CHILD,
                           HumanResources_Model_WageType::ID_SICK_SICKPAY,
                       ]],
                   ]],
                   ['field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->getIdFromProperty('employee_id')],
                   ['field' => 'firstday_date', 'operator' => 'before_or_equals', 'value' => $_record->lastday_date],
                   ['field' => 'lastday_date', 'operator' => 'after_or_equals', 'value' => $_record->firstday_date],
                   ['field' => 'id', 'operator' => 'not', 'value' => $_record->getId()],
               ]],
           ]), null, false, ['date']);
       foreach ($_record->freedays as $freeday) {
           if (isset($sickDays[$freeday->date->format('Y-m-d')])) {
               if (!$freeday->sickoverwrite) {
                   $freeday->sickoverwrite = true;
               }
           } elseif ($freeday->sickoverwrite) {
               $freeday->sickoverwrite = false;
           }
       }
   }

   /**
    * finds non sickness free days within the range of the given sickness records.
    * then updates them, that will mark their days properly as overwritten or not
    */
   protected function _handleOverwrittenVacation(HumanResources_Model_FreeTime $_record, ?HumanResources_Model_FreeTime $_oldRecord)
   {
       if (!$_oldRecord || !$_oldRecord->lastday_date ||
               ($_record->lastday_date && $_oldRecord->lastday_date < $_record->lastday_date)) {
           $lastday_date = $_record->lastday_date;
       } else {
           $lastday_date = $_oldRecord->lastday_date;
       }
       if (!$_oldRecord || !$_oldRecord->firstday_date ||
               ($_record->firstday_date && $_oldRecord->firstday_date > $_record->firstday_date)) {
           $firstday_date = $_record->firstday_date;
       } else {
           $firstday_date = $_oldRecord->firstday_date;
       }
       foreach ($this->search(
               Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_FreeTime::class, [
                   ['field' => 'type', 'operator' => 'definedBy', 'value' => [
                       ['field' => 'wage_type', 'operator' => 'notin', 'value' => [
                           HumanResources_Model_WageType::ID_SICK,
                           HumanResources_Model_WageType::ID_SICK_CHILD,
                           HumanResources_Model_WageType::ID_SICK_SICKPAY,
                       ]],
                   ]],
                   ['field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->getIdFromProperty('employee_id')],
                   ['field' => 'firstday_date', 'operator' => 'before_or_equals', 'value' => $lastday_date],
                   ['field' => 'lastday_date', 'operator' => 'after_or_equals', 'value' => $firstday_date],
                   ['field' => 'id', 'operator' => 'not', 'value' => $_record->getId()],
               ])) as $freeTime) {
           $this->update($freeTime);
       }
   }
}
