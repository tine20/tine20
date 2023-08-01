<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  BL
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * @package     HumanResources
 * @subpackage  BL
 */
class HumanResources_BL_DailyWTReport_PopulateReport implements Tinebase_BL_ElementInterface
{
    /** @var HumanResources_Model_BLDailyWTReport_PopulateReportConfig */
    protected HumanResources_Model_BLDailyWTReport_PopulateReportConfig $_config;

    protected array $wageTypes = [];

    public function __construct(HumanResources_Model_BLDailyWTReport_PopulateReportConfig $_config)
    {
        $this->_config = $_config;
    }

    /**
     * @param Tinebase_BL_PipeContext $_context
     * @param HumanResources_BL_DailyWTReport_Data $_data
     */
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        $timeWorked = 0;
        $timePaused = 0;
        /** @var HumanResources_BL_DailyWTReport_TimeSlot $lastSlot */
        $lastSlot = null;

        /** @var HumanResources_BL_DailyWTReport_BreakTime $someBreak */
        $someBreak = $_context->getLastElementOfClassBefore(HumanResources_BL_DailyWTReport_BreakTime::class,
            $_context->getCurrentExecutionOffset());

        $_data->result->working_times =
            new Tinebase_Record_RecordSet(HumanResources_Model_BLDailyWTReport_WorkingTime::class);

        /** @var HumanResources_BL_DailyWTReport_TimeSlot $timeSlot */
        $timeSlot = null;
        foreach ($_data->timeSlots as $timeSlot) {
            if (0 === $timeSlot->durationInSec()) {
                continue;
            }

            if (null !== $lastSlot && null !== $someBreak) {
                $timePaused += $someBreak->calculateTimePaused($lastSlot, $timeSlot, true);
            }
            $duration = $timeSlot->durationInSec();
            $_data->result->working_times->addRecord(new HumanResources_Model_BLDailyWTReport_WorkingTime([
                'id' => Tinebase_Record_Abstract::generateUID(),
                HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_WAGE_TYPE => HumanResources_Model_WageType::ID_SALARY,
                HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_DURATION => $duration,
                HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_START => $timeSlot->start->format('H:i:s'),
                HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_END => $timeSlot->end->format('H:i:s'),
            ]));

            $timeWorked += $duration;

            $lastSlot = $timeSlot;
        }
        if ($timeSlot && $lastSlot && $lastSlot !== $timeSlot && null !== $someBreak) {
            $timePaused += $someBreak->calculateTimePaused($lastSlot, $timeSlot, true);
        }

        $_data->result->working_time_actual = $timeWorked;
        $_data->result->working_time_total = $timeWorked + $_data->result->working_time_correction;
        $_data->result->break_time_net = $timePaused;

        $dayOfWeek = (int) $_data->date->format('w') - 1;
        if ($dayOfWeek === -1) $dayOfWeek = 6;
        $_data->result->working_time_target = $_data->workingTimeModel
            ->{HumanResources_Model_WorkingTimeScheme::FLDS_JSON}['days'][$dayOfWeek];
        $workingTimeTarget = (int) $_data->result->working_time_target +
            ($_data->result->working_time_target_correction ?: 0);

        if ($_data->bankHoliday) {
            if ($workingTimeTarget > 0) {
                $_data->result->working_times->addRecord(new HumanResources_Model_BLDailyWTReport_WorkingTime([
                    'id' => Tinebase_Record_Abstract::generateUID(),
                    HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_WAGE_TYPE =>
                        HumanResources_Model_WageType::ID_FEAST,
                    HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_DURATION => $workingTimeTarget,
                ]));
            }
            $_data->result->system_remark = $_data->bankHoliday->{Tinebase_Model_BankHoliday::FLD_NAME};
            $_data->result->working_time_total += $workingTimeTarget;
            $_data->result->working_time_actual += $workingTimeTarget;
        } elseif ($_data->freeTimes) {
            /** @var HumanResources_Model_FreeTime $freeTime */
            foreach ($_data->freeTimes as $freeTime) {
                if (!is_object($freeTime->type) || !$freeTime->type->wage_type) {
                    continue;
                }
                $strategy = $freeTime->type->workingTimeCalculationStrategy;
                if (!$strategy instanceof HumanResources_Model_WTCalcStrategy) {
                    $strategy = new HumanResources_Model_WTCalcStrategy();
                }
                $result = $strategy->apply($_data->result);
                if ($result > 0) {
                    $_data->result->working_times->addRecord(new HumanResources_Model_BLDailyWTReport_WorkingTime([
                        'id' => Tinebase_Record_Abstract::generateUID(),
                        HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_WAGE_TYPE => $freeTime->type->wage_type,
                        HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_DURATION => $result,
                    ]));
                    $_data->result->system_remark = $_data->result->system_remark .
                        ($_data->result->system_remark ? ', ' : '') .
                        $this->getWageType($freeTime->type->wage_type)->name;
                }
            }
        }

        /** @var HumanResources_BL_DailyWTReport_TimeSlot $absenceTimeSlot */
        foreach ($_data->absenceTimeSlots as $absenceTimeSlot) {
            if (!$absenceTimeSlot->absenceReason->wage_type) {
                continue;
            }
            $strategy = $absenceTimeSlot->absenceReason->workingTimeCalculationStrategy;
            if (!$strategy instanceof HumanResources_Model_WTCalcStrategy) {
                $strategy = new HumanResources_Model_WTCalcStrategy();
            }
            $result = $strategy->apply($_data->result);
            if ($result > 0) {
                $_data->result->working_times->addRecord(new HumanResources_Model_BLDailyWTReport_WorkingTime([
                    'id' => Tinebase_Record_Abstract::generateUID(),
                    HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_WAGE_TYPE => $absenceTimeSlot->absenceReason->wage_type,
                    HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_DURATION => $result,
                ]));
                $_data->result->system_remark = $_data->result->system_remark .
                    ($_data->result->system_remark ? ', ' : '') .
                    $this->getWageType($absenceTimeSlot->absenceReason->wage_type)->name;
            }
        }
    }

    /**
     * @param string $id
     * @return HumanResources_Model_WageType
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    protected function getWageType($id)
    {
        if (isset($this->wageTypes[$id])) {
            return $this->wageTypes[$id];
        }

        /** @var HumanResources_Model_WageType $wageType */
        $wageType = HumanResources_Controller_WageType::getInstance()->get($id);
        $this->wageTypes[$id] = $wageType;
        return $wageType;
    }
}
