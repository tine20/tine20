<?php
/**
 * HumanResources MonthlyWTReport Ods generation class
 *
 * @package     HumanResources
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * HumanResources MonthlyWTReport Ods generation class
 *
 * @package     HumanResources
 * @subpackage  Export
 *
 */
class HumanResources_Export_Ods_MonthlyWTReport extends Tinebase_Export_Ods
{
    /**
     * @var string application of this export class
     */
    protected $_applicationName = 'HumanResources';

    /**
     * @var HumanResources_Model_MonthlyWTReport
     */
    protected $_monthlyWTR;

    /**
     * @var HumanResources_Model_DailyWTReport
     */
    protected $_weekSummary;

    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        parent::__construct($_filter, $_controller, $_additionalOptions);

        // group by week (Kalender Woche)
        /** @var Tinebase_DateTime $value */
        $this->_groupByProcessor = function(&$value) {
            $value = $value->format('W');
        };
    }

    public function getFormat()
    {
        return 'newOds';
    }

    protected function _onBeforeExportRecords()
    {
        parent::_onBeforeExportRecords();

        $this->_monthlyWTR = HumanResources_Controller_MonthlyWTReport::getInstance()->search($this->_filter)
            ->getFirstRecord();

        if (null === $this->_monthlyWTR) {
            // that will export 0 records
            $this->_records = [];
            return;
        }

        $rs = new Tinebase_Record_RecordSet(HumanResources_Model_MonthlyWTReport::class, [$this->_monthlyWTR]);
        Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($rs,
            HumanResources_Model_MonthlyWTReport::getConfiguration());

        $expander = new Tinebase_Record_Expander(HumanResources_Model_MonthlyWTReport::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'division_id' => [],
                    ],
                ],
                HumanResources_Model_MonthlyWTReport::FLDS_DAILY_WT_REPORTS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'working_times' => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                'wage_type' => [],
                            ],
                        ],
                    ],
                ]
            ],
        ]);
        $expander->expand($rs);

        $this->_monthlyWTR->working_times = new Tinebase_Record_RecordSet(HumanResources_Model_BLDailyWTReport_WorkingTime::class);
        $this->_records = $this->_monthlyWTR->dailywtreports;
        $this->_records->sort('date');
        foreach ($this->_records as $dailyWTR) {
            $this->_monthlyWTR->working_times->merge($dailyWTR->working_times);
        }
    }

    /**
     * @param array $context
     * @return array
     */
    protected function _getTwigContext(array $context)
    {
        $context['monthlyWTR'] = $this->_monthlyWTR;
        $context['weekSummary'] = $this->_weekSummary;
        return parent::_getTwigContext($context);
    }

    protected function _endGroup()
    {
        $week = $this->_lastGroupValue;

        $weekSummary = new HumanResources_Model_DailyWTReport([], true);
        $weekSummary->working_times = new Tinebase_Record_RecordSet(HumanResources_Model_BLDailyWTReport_WorkingTime::class);
        /** @var HumanResources_Model_DailyWTReport $dailyWTR */
        foreach ($this->_records->filter(function($dailyWTR) use ($week) {
                    /** @var HumanResources_Model_DailyWTReport $dailyWTR */
                    return $dailyWTR->date->format('W') === $week;
                }) as $dailyWTR) {
            $weekSummary->break_time_deduction = $weekSummary->break_time_deduction + $dailyWTR->break_time_deduction;
            $weekSummary->break_time_net = $weekSummary->break_time_net + $dailyWTR->break_time_net;
            $weekSummary->working_time_actual = $weekSummary->working_time_actual + $dailyWTR->working_time_actual;
            $weekSummary->working_time_correction = $weekSummary->working_time_correction + $dailyWTR->working_time_correction;
            $weekSummary->working_time_total = $weekSummary->working_time_total + $dailyWTR->working_time_total;
            $weekSummary->working_time_target = $weekSummary->working_time_target + $dailyWTR->working_time_target;
            $weekSummary->working_time_target_correction = $weekSummary->working_time_target_correction + $dailyWTR->working_time_target_correction;
            $weekSummary->working_times->merge($dailyWTR->working_times);
        }

        $this->_weekSummary = $weekSummary;

        parent::_endGroup();
    }
}
