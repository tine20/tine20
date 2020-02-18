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

use Tinebase_ModelConfiguration_Const as MCC;

/**
 *
 * @package     HumanResources
 * @subpackage  BL
 */
class HumanResources_BL_DailyWTReport_BreakTime implements Tinebase_BL_ElementInterface
{
    /** @var HumanResources_Model_BLDailyWTReport_BreakTimeConfig */
    protected $_config;

    // this needs to be a shared config between all instances in one BLPipe
    protected $_minPauseDuration = 300; // 5 minutes

    public function __construct(HumanResources_Model_BLDailyWTReport_BreakTimeConfig $_config)
    {
        // important! clone! we modify the config below eventually!
        // this class may only be executed once! Like all BL Elements are supposed to.
        $this->_config = clone $_config;
    }

    /**
     * @param Tinebase_BL_PipeContext $_context
     * @param HumanResources_BL_DailyWTReport_Data $_data
     */
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        // first sum up break times, just get the break time before us and add it to ourselves
        /** @var HumanResources_BL_DailyWTReport_BreakTime $breakBefore */
        if (null !== ($breakBefore = $_context->getLastElementOfClassBefore(static::class, $_context
                ->getCurrentExecutionOffset()))) {
            $this->_config->break_time = $this->_config->break_time + $breakBefore->_config->break_time;
        }

        $this->_execute($_data);
    }

    /**
     * @param HumanResources_BL_DailyWTReport_TimeSlot $timeSlotLast
     * @param HumanResources_BL_DailyWTReport_TimeSlot $timeSlotNext
     * @return int
     */
    public function calculateTimePaused(HumanResources_BL_DailyWTReport_TimeSlot $timeSlotLast,
            HumanResources_BL_DailyWTReport_TimeSlot $timeSlotNext) {
        $timePaused = 0;
        $slotInterSpace = $timeSlotNext->start->getTimestamp() - $timeSlotLast->end->getTimestamp();
        if ($slotInterSpace >= $this->_minPauseDuration) { // minimum pause => 5 minutes;
            $timePaused += $slotInterSpace;

            // if there was a forced break time deduction at the end or start of the slots, we have to count it!
            // even if its less than the minPauseDuration!
        } else {
            if ($timeSlotLast->forcedBreakAtEnd) {
                $timePaused += $timeSlotLast->forcedBreakAtEnd;
            }
            if ($timeSlotNext->forcedBreakAtStart) {
                $timePaused += $timeSlotNext->forcedBreakAtStart;
            }
        }

        return $timePaused;
    }

    protected function _execute(Tinebase_BL_DataInterface $_data)
    {
        $timeWorked = 0;
        $timePaused = 0;
        /** @var HumanResources_BL_DailyWTReport_TimeSlot $lastSlot */
        $lastSlot = null;

        /** @var HumanResources_BL_DailyWTReport_TimeSlot $timeSlot */
        foreach ($_data->timeSlots as $key => $timeSlot) {
            if (0 === ($durationInSec = $timeSlot->durationInSec())) {
                // we do not set $lastSlot here!
                continue;
            }

            $lastPause = 0;
            if (null !== $lastSlot) {
                $lastPause = $this->calculateTimePaused($lastSlot, $timeSlot);
                $timePaused += $lastPause;
            }
            $timeWorked += $durationInSec;

            if ($timeWorked > $this->_config->time_worked) {
                if ($timePaused < $this->_config->break_time) {

                    // amount of missing break time
                    $forcedBreakTime = $this->_config->break_time - $timePaused;
                    // time worked over the current configured time_worked
                    $overWorked = $timeWorked - $this->_config->time_worked;

                    // rare but edge case!
                    // if the previous slotInterSpace was below minPauseDuration but should now count towards our
                    // new break, we need to take care of that
                    if ($timeWorked - $durationInSec == $this->_config->time_worked && 0 === $lastPause) {
                        $lastPause = $timeSlot->start->getTimestamp() - $lastSlot->end->getTimestamp();
                        $timePaused += $lastPause;
                        // amount of missing break time
                        $forcedBreakTime = $this->_config->break_time - $timePaused;
                        if (0 >= $forcedBreakTime) {
                            // done
                            return;
                        }
                    }

                    // rare, but easy
                    if ($overWorked === $forcedBreakTime) {
                        $_data->result->break_time_deduction += $forcedBreakTime;
                        $timeSlot->end->subSecond($forcedBreakTime);
                        $timeSlot->forcedBreakAtEnd = $forcedBreakTime;
                        // done
                        return;

                    } elseif ($overWorked > $forcedBreakTime) {
                        $_data->result->break_time_deduction += $forcedBreakTime;
                        // find start of forced break
                        $newEnd = $timeSlot->end->getClone()->subSecond($overWorked);
                        if ($newEnd != $timeSlot->start) {
                            $newTimeSlot = $timeSlot->getClone();
                            $timeSlot->end = $newEnd; // timeslot ends at start of forced break
                            $timeSlot->forcedBreakAtEnd = $forcedBreakTime;
                            // new timeslot starts at end of forced break
                            $newTimeSlot->start = $timeSlot->end->getClone()->addSecond($forcedBreakTime);

                            // insert new timeslot after current (key) (therefor we have to remove it and replace it)
                            // php -a $a = [1,2,3]; array_splice($a, array_search(1, array_keys($a)), 1, [2,10]);
                            // print_r($a); => [1,2,10,3]
                            array_splice($_data->timeSlots, array_search($key, array_keys($_data->timeSlots)), 1,
                                [$timeSlot, $newTimeSlot]);
                        } else {
                            $timeSlot->start->addSecond($forcedBreakTime);
                            $timeSlot->forcedBreakAtStart = $forcedBreakTime;
                        }

                        // done
                        return;

                    } else { // $overWorked < $forcedBreakTime
                        $_data->result->break_time_deduction += $overWorked;
                        $timeSlot->end->subSecond($overWorked);
                        $timeWorked -= $overWorked;

                        if ($timeSlot->durationInSec() > 0) {
                            $lastSlot = $timeSlot;
                            $timeSlot->forcedBreakAtEnd = $overWorked;
                        } else {
                            $timePaused -= $lastPause;
                        }
                        continue;
                    }
                }

                // done
                return;
            }

            $lastSlot = $timeSlot;
        }
    }
}