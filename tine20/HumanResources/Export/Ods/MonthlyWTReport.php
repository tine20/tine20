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

use ZipStream\ZipStream;

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

    protected $_orgController;
    protected $_orgOptions;
    protected $_isMultiWTR = false;
    protected $_multiExports = [];

    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        $this->_orgController = $_controller;
        $this->_orgOptions = $_additionalOptions;

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

    public function getDownloadContentType()
    {
        if ($this->_isMultiWTR) {
            return 'application/zip';
        }
        return parent::getDownloadContentType();
    }

    public function getDownloadFilename($_appName = null, $_format = null)
    {
        if ($this->_isMultiWTR) {
            return $this->_translate->translate(HumanResources_Model_MonthlyWTReport::getConfiguration()->recordName)
                . '_export.zip';
        }
        $name = parent::getDownloadFilename($_appName, $_format);
        $name .= " {$this->_monthlyWTR->month} {$this->_monthlyWTR->employee_id->n_fn}.ods";
        return preg_replace(['/^export_humanresources_/', '/\.ods(.+?)/', '/\s/'], ['', '', '_'], $name);
    }

    public function write($_target = 'php://output')
    {
        if (!$this->_isMultiWTR) {
            parent::write($_target);
            return;
        }

        $targetRaii = null;
        if (!is_resource($_target)) {
            if (false === ($_target = fopen($_target, 'w'))) {
                throw new Tinebase_Exception_Backend('can not open write target');
            }
            $targetRaii = new Tinebase_RAII(function() use($_target) { fclose($_target); });
        }
        $options = new \ZipStream\Option\Archive();
        $options->setOutputStream($_target);
        $zip = new ZipStream($this->getDownloadFilename(), $options);

        /** @var self $export */
        foreach ($this->_multiExports as $export) {
            if (false === ($fh = fopen('php://temp', 'w+'))) {
                throw new Tinebase_Exception_Backend('can not open temp stream');
            }
            $raii = new Tinebase_RAII(function() use($fh) { fclose($fh); });
            $export->write($fh);
            if (!rewind($fh)) {
                throw new Tinebase_Exception_Backend('can not rewind temp stream');
            }
            $zip->addFileFromStream($export->getDownloadFilename(), $fh);
            unset($raii);
        }

        $zip->finish();
        unset($targetRaii);
    }

    protected function _onBeforeExportRecords()
    {
        parent::_onBeforeExportRecords();

        $mWTRs = HumanResources_Controller_MonthlyWTReport::getInstance()->search($this->_filter);
        if ($mWTRs->count() > 1) {
            $this->_isMultiWTR = true;
            foreach ($mWTRs as $mWTR) {
                $export = new self(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    HumanResources_Model_MonthlyWTReport::class, [
                        ['field' => 'id', 'operator' => 'equals', 'value' => $mWTR->getId()],
                ]), $this->_orgController, $this->_orgOptions);
                $export->generate();
                $this->_multiExports[] = $export;
            }
            // that will export 0 records
            $this->_records = [];
            return;
        }

        $this->_monthlyWTR = $mWTRs->getFirstRecord();

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
            if($dailyWTR->working_times instanceof Tinebase_Record_RecordSet) {
                $this->_monthlyWTR->working_times->merge($dailyWTR->working_times);
            }
        }
    }

    /**
     * @param array $context
     * @return array
     */
    protected function _getTwigContext(array $context)
    {
        if ($this->_isMultiWTR) return parent::_getTwigContext($context);
        
        $context['monthlyWTR'] = $this->_monthlyWTR;
        $context['weekSummary'] = $this->_weekSummary;
        
        $context['scheduledRemainingVacationDays'] = HumanResources_Controller_FreeTime::getInstance()
            ->getRemainingVacationDays($this->_monthlyWTR->employee_id, null, $this->_monthlyWTR->getPeriod()['until']);

        $context['actualRemainingVacationDays'] = HumanResources_Controller_FreeTime::getInstance()
            ->getRemainingVacationDays($this->_monthlyWTR->employee_id, $this->_monthlyWTR->getPeriod()['until'], $this->_monthlyWTR->getPeriod()['until']);

        $context['takenVactionsCurrentPeriod'] = HumanResources_Controller_FreeTime::getInstance()
            ->getTakenVacationDays($this->_monthlyWTR->employee_id, $this->_monthlyWTR->getPeriod());
        
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
            if ($dailyWTR->working_times instanceof Tinebase_Record_RecordSet) {
                $weekSummary->working_times->merge($dailyWTR->working_times);
            }
        }

        $this->_weekSummary = $weekSummary;

        parent::_endGroup();
    }
}
