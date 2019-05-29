<?php
/**
 * MonthlyWorkingTimeReport controller for HumanResources application
 * 
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * MonthlyWorkingTimeReport controller class for HumanResources application
 * 
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_MonthlyWTReport extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    const RC_JSON_REQUEST = 'jsonRequest';

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_modelName = HumanResources_Model_MonthlyWTReport::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => HumanResources_Model_MonthlyWTReport::TABLE_NAME,
            'modlogActive' => true
        ));

        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
        $this->_doContainerACLChecks = false;
    }

    /**
     * will recalculate the given monthly report and all reports that exist after the given one
     *
     * @param HumanResources_Model_MonthlyWTReport $_monthlyWTR
     */
    public function recalculateReport(HumanResources_Model_MonthlyWTReport $_monthlyWTR,
            HumanResources_Model_MonthlyWTReport $_previousMonthlyWTR = null)
    {
        $transaction = new Tinebase_TransactionManager_Handle();

        if (null === $_previousMonthlyWTR) {
            $_previousMonthlyWTR = $this->getPreviousMonthlyWTR($_monthlyWTR);
        }
        if (null !== $_previousMonthlyWTR) {
            $_monthlyWTR->working_time_balance_previous = $_previousMonthlyWTR->working_time_balance;
        } else {
            $_monthlyWTR->working_time_balance_previous = 0;
        }

        $rs = new Tinebase_Record_RecordSet(HumanResources_Model_MonthlyWTReport::class, [$_monthlyWTR]);
        Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($rs,
            HumanResources_Model_MonthlyWTReport::getConfiguration());

        $isTime = 0;
        $shouldTime = 0;
        /** @var HumanResources_Model_DailyWTReport $dailyWTR */
        foreach ($_monthlyWTR->dailywtreports as $dailyWTR) {
            $isTime += $dailyWTR->getIsWorkingTime();
            $shouldTime += $dailyWTR->getShouldWorkingTime();
        }

        $_monthlyWTR->working_time_actual = $isTime;
        $_monthlyWTR->working_time_target = $shouldTime;
        $_monthlyWTR->working_time_balance = $_monthlyWTR->working_time_balance_previous +
            $_monthlyWTR->working_time_actual - $_monthlyWTR->working_time_target +
            $_monthlyWTR->working_time_correction;

        $current = $this->_backend->update($_monthlyWTR);
        if (null !== ($_nextMonthlyWTR = $this->getNextMonthlyWTR($current))) {
            $this->recalculateReport($_nextMonthlyWTR, $current);
        }

        $transaction->commit();
    }

    /**
     * @param HumanResources_Model_MonthlyWTReport $_monthlyWTR
     * @return null|HumanResources_Model_MonthlyWTReport
     */
    public function getNextMonthlyWTR(HumanResources_Model_MonthlyWTReport $_monthlyWTR)
    {
        $date = new Tinebase_DateTime($_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_MONTH}, '-01 00:00:00');
        $date->addMonth(1);

        return $this->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_MonthlyWTReport::class, [
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID, 'operator' => 'equals',
                    'value' => $_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID}],
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_MONTH, 'operator' => 'equals',
                    'value' => $date->format('Y-m')],
            ]))->getFirstRecord();
    }

    /**
     * @param HumanResources_Model_MonthlyWTReport $_monthlyWTR
     * @return null|HumanResources_Model_MonthlyWTReport
     */
    public function getPreviousMonthlyWTR(HumanResources_Model_MonthlyWTReport $_monthlyWTR)
    {
        $date = new Tinebase_DateTime($_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_MONTH}, '-01 00:00:00');
        $date->subMonth(1);

        return $this->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_MonthlyWTReport::class, [
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID, 'operator' => 'equals',
                    'value' => $_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID}],
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_MONTH, 'operator' => 'equals',
                    'value' => $date->format('Y-m')],
            ]))->getFirstRecord();
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if (isset($this->_requestContext[self::RC_JSON_REQUEST])) {
            // _("monthly wt reports can't be created")
            throw new Tinebase_Exception_SystemGeneric("monthly wt reports can't be created");
        }
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   HumanResources_Model_MonthlyWTReport $_record      the update record
     * @param   HumanResources_Model_MonthlyWTReport $_oldRecord   the current persistent record
     * @return  void
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if (isset($this->_requestContext[self::RC_JSON_REQUEST])) {
            $allowedProperties = [
                HumanResources_Model_MonthlyWTReport::FLDS_IS_CLEARED => true,
                HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_CORRECTION => true,
            ];
            foreach ($_record->getFields() as $prop) {
                if (!isset($allowedProperties[$prop])) {
                    $_record->{$prop} = $_oldRecord->{$prop};
                }
            }
        }

        if ($_record->is_cleared && $_oldRecord->is_cleared) {
            // _('It is not allowed to update a cleared report')
            throw new Tinebase_Exception_SystemGeneric('It is not allowed to update a cleared report');
        }
    }

    /**
     * inspect update of one record (after update)
     *
     * @param   HumanResources_Model_MonthlyWTReport $updatedRecord   the just updated record
     * @param   HumanResources_Model_MonthlyWTReport $record          the update record
     * @param   HumanResources_Model_MonthlyWTReport $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        foreach ([
                    HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_CORRECTION,
                 ] as $prop) {
            if ($currentRecord->{$prop} !== $updatedRecord->{$prop}) {
                $this->recalculateReport($updatedRecord);
                break;
            }
        }

        // set is_cleared
        if ($updatedRecord->is_cleared && !$currentRecord->is_cleared) {
            // previous month needs to be cleared first!
            if (null !== ($prev = $this->getPreviousMonthlyWTR($updatedRecord)) && !$prev->is_cleared) {
                // _('previous months need to be cleared first')
                throw new Tinebase_Exception_SystemGeneric('previous months need to be cleared first');
            }

            $rs = new Tinebase_Record_RecordSet(HumanResources_Model_MonthlyWTReport::class, [$updatedRecord]);
            Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($rs,
                HumanResources_Model_MonthlyWTReport::getConfiguration());

            $dailyCtrl = HumanResources_Controller_DailyWTReport::getInstance();
            $dailyCtrl->setRequestContext([HumanResources_Controller_DailyWTReport::RC_ALLOW_IS_CLEARED => true]);
            try {
                foreach ($updatedRecord->dailywtreports as $dailyReport) {
                    $dailyReport->is_cleared = true;
                    $dailyCtrl->update($dailyReport);
                }
            } finally {
                $dailyCtrl->setRequestContext([]);
            }

        // unset is_cleared
        } elseif (!$updatedRecord->is_cleared && $currentRecord->is_cleared) {
            // next month must not be cleared!
            if (null !== ($next = $this->getNextMonthlyWTR($updatedRecord)) && $next->is_cleared) {
                // _('following months need to be uncleared first')
                throw new Tinebase_Exception_SystemGeneric('following months need to be uncleared first');
            }

            $rs = new Tinebase_Record_RecordSet(HumanResources_Model_MonthlyWTReport::class, [$updatedRecord]);
            Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($rs,
                HumanResources_Model_MonthlyWTReport::getConfiguration());

            $dailyCtrl = HumanResources_Controller_DailyWTReport::getInstance();
            $dailyCtrl->setRequestContext([HumanResources_Controller_DailyWTReport::RC_ALLOW_IS_CLEARED => true]);
            try {
                foreach ($updatedRecord->dailywtreports as $dailyReport) {
                    $dailyReport->is_cleared = false;
                    $dailyCtrl->update($dailyReport);
                }
            } finally {
                $dailyCtrl->setRequestContext([]);
            }
        }
    }
}
