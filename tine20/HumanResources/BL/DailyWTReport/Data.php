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
class HumanResources_BL_DailyWTReport_Data implements Tinebase_BL_DataInterface
{
    public $allowTimesheetOverlap = false;

    /**
     * @var Tinebase_DateTime
     */
    public $date;

    /**
     * @var Tinebase_Record_RecordSet of HumanResources_Model_FreeTime
     */
    public $freeTimes = null;

    /**
     * @var Tinebase_Record_RecordSet of Calendar_Model_Events;
     */
    public $feastTimes = null;

    /**
     * @var HumanResources_Model_WorkingTimeScheme
     */
    public $workingTimeModel;

    /**
     * @var array of HumanResources_BL_DailyWTReport_TimeSlot
     */
    public $timeSlots = [];

    /**
     * @var HumanResources_Model_DailyWTReport
     */
    public $result;

    public function toArray()
    {
        return [
            'timeSlots' => $this->timeSlots,
            'result' => $this->result->toArray(),
        ];
    }
    /**
     * @param Tinebase_Record_RecordSet $_timeSheets
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function convertTimeSheetsToTimeSlots(Tinebase_Record_RecordSet $_timeSheets)
    {
        $this->timeSlots = [];
        $_timeSheets->sort('start_time');
        $relations = new Tinebase_Record_RecordSet(Tinebase_Model_Relation::class);
        $relation = new Tinebase_Model_Relation(
            [
                'own_model'         => HumanResources_Model_DailyWTReport::class,
                'own_backend'       => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'own_id'            => $this->result->getId(),
                'related_model'     => Timetracker_Model_Timesheet::class,
                'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_degree'    => Tinebase_Model_Relation::DEGREE_SIBLING,
                'type'              => HumanResources_Model_DailyWTReport::MODEL_NAME_PART,
            ], true);

        /** @var Timetracker_Model_Timesheet $timeSheet */
        foreach ($_timeSheets as $timeSheet) {
            $timeSlot = new HumanResources_BL_DailyWTReport_TimeSlot();
            $timeSlot->start = new Tinebase_DateTime($timeSheet->start_date->format('Y-m-d ') . $timeSheet->start_time);
            $timeSlot->end = $timeSlot->start->getClone()->addMinute($timeSheet->duration);
            $timeSlot->timeAccountId = $timeSheet->getIdFromProperty('timeaccount_id');
            $timeSlot->timeSheetId = $timeSheet->getId();

            // TODO add same day assertions? which TZ?
            if (!$this->allowTimesheetOverlap && false !== ($lastSlot = end($this->timeSlots))) {
                if ($timeSlot->start->isEarlier($lastSlot->end)) {
                    throw new Tinebase_Exception_BL('timesheets must not overlap');
                }
            }
            $newRelation = clone $relation;
            $newRelation->related_id = $timeSheet->getId();
            $relations->addRecord($newRelation);

            $this->timeSlots[] = $timeSlot;
        }

        $this->result->relations = $relations;
    }
}