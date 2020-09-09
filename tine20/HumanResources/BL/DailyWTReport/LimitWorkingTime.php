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
class HumanResources_BL_DailyWTReport_LimitWorkingTime implements Tinebase_BL_ElementInterface
{
    /** @var HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig */
    protected $_config;

    public function __construct(HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig $_config)
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
        $_data->result->evaluation_period_start = $this->_config->start_time;
        $_data->result->evaluation_period_end = $this->_config->end_time;

        if ($_data->result->evaluation_period_start_correction instanceof Tinebase_DateTime) {
            $this->_config->start_time = $_data->result->evaluation_period_start_correction;
        }
        if ($_data->result->evaluation_period_end_correction instanceof Tinebase_DateTime) {
            $this->_config->end_time = $_data->result->evaluation_period_end_correction;
        }

        $this->_checkEvaluationPeriodStart($_data);
        $this->_checkEvaluationPeriodEnd($_data);
    }

    /**
     * @param HumanResources_BL_DailyWTReport_Data $_data
     */
    protected function _checkEvaluationPeriodStart(HumanResources_BL_DailyWTReport_Data $_data)
    {
        if (!$this->_config->start_time) {
            return;
        }

        // loop until the first time slot starts >= evaluation_period_start or none remains
        reset($_data->timeSlots);
        do {
            /** @var HumanResources_BL_DailyWTReport_TimeSlot $timeSlot */
            if (false === ($timeSlot = current($_data->timeSlots)) ||
                    $timeSlot->start->format('H:i:s') >= $this->_config->start_time) {
                return;
            }

            $evalPeriod = new Tinebase_DateTime($timeSlot->start->format('Y-m-d ') . $this->_config->start_time);

            if ($timeSlot->end->isEarlierOrEquals($evalPeriod)) {
                array_shift($_data->timeSlots);
            } else {
                $timeSlot->start = $evalPeriod;
                return;
            }
        } while (true);
    }

    /**
     * @param HumanResources_BL_DailyWTReport_Data $_data
     */
    protected function _checkEvaluationPeriodEnd(HumanResources_BL_DailyWTReport_Data $_data)
    {
        if (!$this->_config->end_time) {
            return;
        }

        // loop until the last time slot ends <= evaluation_period_end or none remains
        do {
            /** @var HumanResources_BL_DailyWTReport_TimeSlot $timeSlot */
            if (false === ($timeSlot = end($_data->timeSlots)) ||
                    $timeSlot->end->format('H:i:s') <= $this->_config->end_time) {
                return;
            }

            $evalPeriod = new Tinebase_DateTime($timeSlot->end->format('Y-m-d ') . $this->_config->end_time);

            if ($timeSlot->start->isLaterOrEquals($evalPeriod)) {
                array_pop($_data->timeSlots);
            } else {
                $timeSlot->end = $evalPeriod;
                return;
            }
        } while (true);
    }
}