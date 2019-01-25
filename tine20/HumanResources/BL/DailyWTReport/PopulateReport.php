<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  BL
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected $_config;

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

        /** @var HumanResources_BL_DailyWTReport_TimeSlot $timeSlot */
        foreach ($_data->timeSlots as $timeSlot) {
            if (0 === $timeSlot->durationInSec()) {
                continue;
            }

            if (null !== $lastSlot && null !== $someBreak) {
                $timePaused = $someBreak->calculateTimePaused($lastSlot, $timeSlot);
            }
            $timeWorked += $timeSlot->durationInSec();

            $lastSlot = $timeSlot;
        }

        $_data->result->working_time_actual = $timeWorked;
        $timeWorked += $_data->result->working_time_correction;
        $_data->result->break_time_net = $timePaused;

        $dayOfWeek = $_data->date->format('w') - 1;
        if ($dayOfWeek === -1) $dayOfWeek = 6;
        $_data->result->working_time_target = $_data->workingTimeModel
            ->{HumanResources_Model_WorkingTimeScheme::FLDS_JSON}['days'][$dayOfWeek];
        $workingTimeTarget = $_data->result->working_time_target_correction !== null ?
            $_data->result->working_time_target_correction : $_data->result->working_time_target;

        $_data->result->working_times =
            new Tinebase_Record_RecordSet(HumanResources_Model_BLDailyWTReport_WorkingTime::class);
        if ($timeWorked > 0) {
            $_data->result->working_times->addRecord(new HumanResources_Model_BLDailyWTReport_WorkingTime([
                HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_WAGE_TYPE => HumanResources_Model_WageType::ID_SALARY,
                HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_DURATION => $workingTimeTarget,
            ]));
        }
        if ($_data->feastTimes) {
            $_data->result->working_times->addRecord(new HumanResources_Model_BLDailyWTReport_WorkingTime([
                HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_WAGE_TYPE => HumanResources_Model_WageType::ID_FEAST,
                HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_DURATION => $workingTimeTarget,
            ]));
        } elseif ($_data->freeTimes) {
            $_data->result->working_times->addRecord(new HumanResources_Model_BLDailyWTReport_WorkingTime([
                HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_WAGE_TYPE =>
                    $_data->freeTimes->getFirstRecord()->freedays->type->wage_type,
                HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_DURATION => $workingTimeTarget,
            ]));
        }
    }
}