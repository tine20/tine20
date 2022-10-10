<?php
/**
 * HumanResources MonthlyWTReport Ods generation class
 *
 * @package     HumanResources
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * HumanResources MonthlyWTReport Ods generation class
 *
 * @package     HumanResources
 * @subpackage  Export
 *
 */
class HumanResources_Export_Ods_MonthlyWTReportClockTime extends HumanResources_Export_Ods_MonthlyWTReport
{
    protected function _onBeforeExportRecords()
    {
        parent::_onBeforeExportRecords();

        if (null === $this->_monthlyWTR) {
            return;
        }

        $dailyWTRs = $this->_records;
        $this->_records = new Tinebase_Record_RecordSet(HumanResources_Export_Ods_Helper_DailyWTR::class);
        $tsCtrl = Timetracker_Controller_Timesheet::getInstance();
        $raii = new Tinebase_RAII($tsCtrl->assertPublicUsage());
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
            ['field' => 'account_id', 'operator' => 'equals', 'value' => $this->_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID}->account_id],
            ['field' => 'process_status', 'operator' => 'equals', 'value' => Timetracker_Config::TS_PROCESS_STATUS_ACCEPTED],
            ['field' => 'start_date', 'operator' => 'equals', 'value' => ''],
        ]);

        foreach ($dailyWTRs as $dailyWTR) {
            $helper = new HumanResources_Export_Ods_Helper_DailyWTR($dailyWTR->toArray(false));
            $filter->getFilter('start_date')->setValue($dailyWTR->date->format('Y-m-d'));

            $timeSheets = $tsCtrl->search($filter);
            (new Tinebase_Record_Expander(Timetracker_Model_Timesheet::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    HumanResources_Model_FreeTimeType::TT_TS_SYSCF_ABSENCE_REASON => [],
                ],
            ]))->expand($timeSheets);
            // remove timesheets that have an absence reason with no wage_type assigned
            $helper->clock_times = $timeSheets->filter(function(Timetracker_Model_Timesheet $ts) {
                return empty($ts->{HumanResources_Model_FreeTimeType::TT_TS_SYSCF_ABSENCE_REASON}) ||
                    !empty($ts->{HumanResources_Model_FreeTimeType::TT_TS_SYSCF_ABSENCE_REASON}->wage_type);
            });
            $helper->clock_times->sort('start_time', 'ASC');

            $this->_records->addRecord($helper);
        }

        unset($raii);
    }
}
