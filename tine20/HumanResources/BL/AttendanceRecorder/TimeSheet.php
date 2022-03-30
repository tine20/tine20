<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  BL
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as MCC;

/**
 *
 * @package     HumanResources
 * @subpackage  BL
 */
class HumanResources_BL_AttendanceRecorder_TimeSheet implements Tinebase_BL_ElementInterface
{
    /** @var HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig */
    protected $_config;
    protected $_staticTAid;
    protected $_allowOtherTAs;
    protected $_fillOtherDevices;

    public function __construct(HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig $_config)
    {
        $this->_config = $_config;
        if ($_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA}) {
            $this->_staticTAid = $_config->getIdFromProperty(HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA);
        }
        $this->_allowOtherTAs = (bool)$_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_ALLOW_OTHER_TA};
    }

    /**
     * @param Tinebase_BL_PipeContext $_context
     * @param Tinebase_BL_DataInterface $_data
     */
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        /** @var HumanResources_BL_AttendanceRecorder_Data $_data */
        $lastAccountId = null;
        $lastClockIn = null;
        /** @var HumanResources_Model_AttendanceRecord $record */
        foreach ($_data->data as $record) {
            if ($lastAccountId !== $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID)) {
                if ($lastAccountId && $lastClockIn) {
                    $this->treatOpenClockIn($lastClockIn);
                    $lastClockIn = null;
                }
                $lastAccountId = $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID);
                // read config, not property here!
                if (!$this->_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA}) {
                    /** @var HumanResources_Model_Employee $employee */
                    $employee = HumanResources_Controller_Employee::getInstance()->search(
                        Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                            ['field' => 'account_id', 'operator' => 'equals', 'value' => $lastAccountId]
                        ]))->getFirstRecord();
                    $this->_staticTAid = HumanResources_Controller_WorkingTimeScheme::getInstance()
                        ->getWorkingTimeAccount($employee);
                }
            }

            if (null === $lastClockIn) {
                if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN !== $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                    $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
                    continue; // shouldn't happen
                }
                $lastClockIn = $record;
                continue;
            } elseif (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT !== $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                $lastClockIn->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
                $lastClockIn = $record;
                continue; // shouldn't happen
            }

            $lastClockIn->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
            $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
            $this->createTimeSheet($lastClockIn, $record);

            $lastClockIn = null;
        }

        if ($lastClockIn) {
            $this->treatOpenClockIn($lastClockIn);
        }
    }

    protected function treatOpenClockIn(HumanResources_Model_AttendanceRecord $clockIn)
    {
        // config to close it anyway?
    }

    protected function createTimeSheet(HumanResources_Model_AttendanceRecord $clockIn, HumanResources_Model_AttendanceRecord $clockOut)
    {
        static $translate;
        if (null === $translate) {
            $translate = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME);
        }

        // read config, not property here!
        if (!$this->_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA} &&
                isset($clockIn->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timeaccount::class])) {
            $taId = $clockIn->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timeaccount::class];
        } else {
            $taId = $this->_staticTAid;
        }

        $ts = new Timetracker_Model_Timesheet([
            'account_id' => $clockIn->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID),
            'timeaccount_id' => $taId,
            'start_date' => $clockIn->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP},
            'start_time' => $clockIn->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'),
            'end_time' => $clockOut->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'),
            'duration' => 0,
        ], true);

        $ts->description = sprintf($translate->translate('Clock in: %1$s'), $ts->start_time) . ' ' .
            sprintf($translate->translate('Clock out: %1$s'), $ts->end_time);
        if ($clockOut->{HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID}) {
            $ts->{HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON} = $clockOut->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID);
        }
        if (HumanResources_Model_AttendanceRecord::STATUS_FAULTY === $clockOut->{HumanResources_Model_AttendanceRecord::FLD_STATUS}) {
            $ts->need_for_clarification = true;
        }

        Timetracker_Controller_Timesheet::getInstance()->create($ts);
    }
}
