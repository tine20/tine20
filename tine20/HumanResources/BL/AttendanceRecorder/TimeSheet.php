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
class HumanResources_BL_AttendanceRecorder_TimeSheet implements Tinebase_BL_ElementInterface,
    HumanResources_BL_AttendanceRecorder_UndoInterface
{
    /** @var HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig */
    protected $_config;
    protected $_staticTAid;
    protected $_allowOtherTAs;
    protected $_fillOtherDevices;
    protected $_tsPublicRAII;
    protected $_doRounding;
    protected $_roundingToMin;
    protected $_roundingPauseThreshold;
    protected $_roundingByClock;

    public function __construct(HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig $_config)
    {
        $this->_config = $_config;
        if ($_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA}) {
            $this->_staticTAid = $_config->getIdFromProperty(HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA);
        }
        $this->_allowOtherTAs = (bool)$_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_ALLOW_OTHER_TA};

        $this->_tsPublicRAII = new Tinebase_RAII(Timetracker_Controller_Timesheet::getInstance()->assertPublicUsage());

        $this->_roundingToMin = intval($_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_ROUNDING_TO_MIN});
        $this->_doRounding = $this->_roundingToMin > 0;
        $this->_roundingByClock = (bool)$_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_ROUNDING_BY_CLOCK};
        $this->_roundingPauseThreshold = intval($_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_ROUNDING_PAUSE_THRESHOLD}) * 60;
    }

    /**
     * @param Tinebase_BL_PipeContext $_context
     * @param Tinebase_BL_DataInterface $_data
     */
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        /** @var HumanResources_BL_AttendanceRecorder_Data $_data */
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        $employeeRaii = new Tinebase_RAII(HumanResources_Controller_Employee::getInstance()->assertPublicUsage());
        $timesheetRaii = new Tinebase_RAII(Timetracker_Controller_Timesheet::getInstance()->assertPublicUsage());
        $timeaccountRaii = new Tinebase_RAII(Timetracker_Controller_Timeaccount::getInstance()->assertPublicUsage());

        foreach (array_unique($_data->data->{HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID}) as $accountId) {
            if (Tinebase_Core::getUser()->getId() !== $accountId) {
                Tinebase_Core::setUser(Tinebase_User::getInstance()->getFullUserById($accountId));
            }

            // read config, not property here!
            if (!$this->_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA}) {
                /** @var HumanResources_Model_Employee $employee */
                $employee = HumanResources_Controller_Employee::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                        ['field' => 'account_id', 'operator' => 'equals', 'value' => $accountId]
                    ]))->getFirstRecord();
                $this->_staticTAid = HumanResources_Controller_WorkingTimeScheme::getInstance()
                    ->getWorkingTimeAccount($employee)->getId();
            }
            $accountData = $_data->data->filter(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID, $accountId);
            foreach (array_unique($accountData->{HumanResources_Model_AttendanceRecord::FLD_REFID}) as $refId) {
                $ts = null;
                $prevRecord = null;
                $refIdRecords = $accountData->filter(HumanResources_Model_AttendanceRecord::FLD_REFID, $refId);
                /** @var HumanResources_Model_AttendanceRecord $record */
                foreach ($refIdRecords as $record) {
                    if (!$ts && isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'])) {
                        try {
                            /** @var Timetracker_Model_Timesheet $ts */
                            $ts = Timetracker_Controller_Timesheet::getInstance()->get($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id']);
                            $prevRecord = $record;
                            continue;
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ts not found!');
                        }
                    }
                    if (!$ts) {
                        if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN !== $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                            // shouldn't happen, we create faulty ts and be done with it, no tsId set!
                            $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
                            $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS} = HumanResources_Model_AttendanceRecord::STATUS_FAULTY;
                            $this->createTimeSheet($record, $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE});
                            $refIdRecords->removeById($record->getId());
                            $prevRecord = null;
                        } else {
                            // check if we need to close an absence TS for WT
                            if (HumanResources_Model_AttendanceRecorderDevice::SYSTEM_WORKING_TIME_ID ===
                                    $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID)) {
                                $lastClose = HumanResources_Controller_AttendanceRecord::getInstance()->search(
                                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                                        HumanResources_Model_AttendanceRecord::class, [
                                            ['field' => HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID, 'operator' => 'equals', 'value' => HumanResources_Model_AttendanceRecorderDevice::SYSTEM_WORKING_TIME_ID],
                                            ['field' => HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID)],
                                            ['field' => HumanResources_Model_AttendanceRecord::FLD_TYPE, 'operator' => 'equals', 'value' => HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT],
                                            ['field' => HumanResources_Model_AttendanceRecord::FLD_STATUS, 'operator' => 'equals', 'value' => HumanResources_Model_AttendanceRecord::STATUS_CLOSED],
                                    ]), new Tinebase_Model_Pagination(['sort' => HumanResources_Model_AttendanceRecord::FLD_SEQUENCE, 'dir' => 'DESC', 'limit' => 1]))->getFirstRecord();
                                while ($lastClose && isset($lastClose->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['fttTS']['id'])) {
                                    $ids = [];
                                    try {
                                        /** @var Timetracker_Model_Timesheet $absenceTS */
                                        $absenceTS = Timetracker_Controller_Timesheet::getInstance()
                                            ->get($lastClose->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['fttTS']['id']);
                                    } catch (Tinebase_Exception_NotFound $tenf) {
                                        break;
                                    }
                                    /** @var Tinebase_DateTime $absenceClockIn */
                                    $absenceClockIn = $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}
                                        ->getClone()->setTimezone(Tinebase_Core::getUserTimezone());
                                    $absenceClockOut = new Tinebase_DateTime($absenceTS->start_date . ' ' . $absenceTS->start_time, Tinebase_Core::getUserTimezone());

                                    while ($absenceClockOut->format('Y-m-d') !== $absenceClockIn->format('Y-m-d')) {
                                        $absenceTS->end_time = '23:59:59';
                                        if ($absenceTS->getId()) {
                                            $absenceTS = Timetracker_Controller_Timesheet::getInstance()->update($absenceTS);
                                            $ids[$absenceTS->getId()] = $absenceTS->seq == 2 ? 2 : false;
                                        } else {
                                            $absenceTS = Timetracker_Controller_Timesheet::getInstance()->create($absenceTS);
                                            $ids[$absenceTS->getId()] = 1;
                                        }
                                        $absenceTS->setId(null);
                                        $absenceTS->start_time = '00:00:00';
                                        $absenceTS->start_date = $absenceClockOut->addDay(1)->format('Y-m-d');
                                        $absenceTS->description = '';
                                    }

                                    $absenceTS->end_time = $absenceClockIn->format('H:i:s');
                                    $absenceTS->description = $absenceTS->description . ' ' .
                                        sprintf(Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)->_('Clock in: %1$s'),
                                            $absenceTS->end_time);
                                    if ($absenceTS->getId()) {
                                        $absenceTS = Timetracker_Controller_Timesheet::getInstance()->update($absenceTS);
                                        $ids[$absenceTS->getId()] = $absenceTS->seq == 2 ? 2 : false;
                                    } else {
                                        $absenceTS = Timetracker_Controller_Timesheet::getInstance()->create($absenceTS);
                                        $ids[$absenceTS->getId()] = 1;
                                    }
                                    $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['fttTS']['ids'] = $ids;
                                    break;
                                }
                            }

                            $ts = $this->createTimeSheet($record);
                            $prevRecord = $record;
                        }
                        continue;
                    } elseif (isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'])) {
                        if ($ts->getId() === $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id']) {
                            $prevRecord = $record;
                            continue;
                        }
                        unset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]);
                    }
                    if (!$this->updateTimeSheet($ts, $record, $prevRecord)) {
                        $this->calculateTS($ts, $refIdRecords, true);
                        $ts = null;
                        $prevRecord = null;
                    } else {
                        $prevRecord = $record;
                    }
                }
                if ($ts) {
                    $this->calculateTS($ts, $refIdRecords);
                }
            }
        }

        unset($employeeRaii);
        unset($timesheetRaii);
        unset($timeaccountRaii);
    }

    protected function updateTimeSheet(Timetracker_Model_Timesheet $ts, HumanResources_Model_AttendanceRecord $record, HumanResources_Model_AttendanceRecord $prevRecord): bool
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        static $translate;
        if (null === $translate) {
            $translate = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME);
        }

        $tz = Tinebase_Core::getUserTimezone();
        $record->setTimezone($tz, false);
        $tzRaii = new Tinebase_RAII(function() use($record) {
            $record->setTimezone('UTC', false);
        });

        $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'] =
            $ts->getId();
        if (isset($prevRecord->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'])) {
            $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'] =
                $prevRecord->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'];
        }

        if (HumanResources_Model_AttendanceRecord::STATUS_FAULTY === $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS} ||
            HumanResources_Model_AttendanceRecord::STATUS_FAULTY === $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_STATUS}) {
            $ts->need_for_clarification = true;
        }

        if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT === $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
            if ($record->{HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID}) {
                $fttId = $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID);
                $ftt = HumanResources_Controller_FreeTimeType::getInstance()->get($fttId);
                if ($ftt->enable_timetracking) {
                    if ($ftt->timeaccount) {
                        $fttTAId = $ftt->getIdFromProperty('timeaccount');
                    } else {
                        $fttTAId = $this->getTAId($record);
                    }
                    $startDate = $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->getClone()
                        ->setTimezone(Tinebase_Core::getUserTimezone());
                    $fttTS = Timetracker_Controller_Timesheet::getInstance()->create(new Timetracker_Model_Timesheet([
                        'account_id' => $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID),
                        'timeaccount_id' => $fttTAId,
                        'start_date' => $startDate,
                        'start_time' => $startDate->format('H:i:s'),
                        'end_time' => $startDate->format('H:i:s'),
                        'duration' => 0,
                        HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON => $fttId,
                        'description' => sprintf($translate->_('Clock out: %1$s'), $startDate->format('H:i:s')),
                    ], true));
                    $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['fttTS'] = $fttTS->getId();
                }
            }
            $ts->description = $ts->description . ' ' . sprintf($translate->_('Clock out: %1$s'),
                    $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'));

            return false;
        }

        if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN === $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
            $ts->description = $ts->description . ' ' . sprintf($translate->_('Clock in: %1$s'),
                    $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'));
            if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED !== $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                //this shouldn't happen ...
                $ts->need_for_clarification = true;
            }

        } else {
            // pause
            $ts->description = $ts->description . ' ' . sprintf($translate->_('Clock pause: %1$s'),
                    $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'));
            if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN !== $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                //this shouldn't happen ...
                $ts->need_for_clarification = true;
            }
        }

        unset($tzRaii);
        return true;
    }

    protected function calculateTS(Timetracker_Model_Timesheet $ts, Tinebase_Record_RecordSet $records, bool $close = false)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        $tz = Tinebase_Core::getUserTimezone();
        $clockedIn = false;
        $slots = [];
        /** @var HumanResources_Model_AttendanceRecord $record */
        foreach ($records as $record) {
            if ($close) {
                $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = 1;
            }
            switch ($record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                case HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN:
                    if ($clockedIn) break;
                    $clockedIn = true;
                    $slots[] = ['start' => $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->getClone()->setTimezone($tz)];
                    break;
                case HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT:
                    if ($clockedIn) {
                        $slots[count($slots)-1]['end'] = $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->getClone()->setTimezone($tz);
                        $clockedIn = false;
                    }
                    break 2;
                case HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED:
                    if ($clockedIn) {
                        $slots[count($slots)-1]['end'] = $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->getClone()->setTimezone($tz);
                        $clockedIn = false;
                    }
                    break;
                default:
                    throw new Tinebase_Exception_NotImplemented('haiiiya uncle roger puts his foot down!');
            }
        }

        if ($clockedIn) {
            unset($slots[count($slots)-1]);
        }
        if ($this->_doRounding) {
            $this->roundSlots($slots);
        }
        if (empty($slots)) {
            return;
        }
        reset($slots);
        $slot = current($slots);
        $ts->start_date = $slot['start']->format('Y-m-d');
        if (count($slots) === 1) {
            $ts->start_time = $slot['start']->format('H:i:s');
            $ts->end_time = $slot['end']->format('H:i:s');
            $ts->duration = 0;
        } else {
            $ts->start_time = null;
            $ts->end_time = null;
            $duration = 0;
            foreach ($slots as $slot) {
                $duration += $slot['end']->getTimestamp() - $slot['start']->getTimestamp();
            }
            $ts->duration = $duration ? ceil($duration / 60) : 0;
        }

        $ts = Timetracker_Controller_Timesheet::getInstance()->update($ts);

        foreach ($records as $record) {
            $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'] =
                $ts->getId();
            $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq'] =
                $ts->seq;
            if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT === $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                break;
            }
        }
    }

    protected function roundSlots(array &$slots)
    {
        foreach ($slots as $idx => $slot) {
            $slot['start']->setSecond(0);
            if (($sec = $slot['end']->format('s')) > 0) {
                $slot['end']->addSecond(60 - $sec);
            }
            if ($slot['end']->getTimestamp() === $slot['start']->getTimestamp()) {
                unset($slots[$idx]);
            }
        }
        if ($this->_roundingPauseThreshold) {
            foreach ($slots as $idx => &$slot) {
                if (0 === $idx) continue;
                if ($slot['start']->getTimestamp() - $slots[$idx-1]['end']->getTimestamp() < $this->_roundingPauseThreshold) {
                    $slot['start'] = $slots[$idx-1]['start'];
                    unset($slots[$idx-1]);
                }
            }
            unset($slot);
        }
        foreach ($slots as $slot) {
            if ($this->_roundingByClock) {
                if ($this->_roundingToMin > 59) {
                    $slot['start']->setMinute(0);
                    $durationMin = ceil(($slot['end']->getTimestamp() - $slot['start']->getTimestamp()) / 60);
                    if (($modMin = $durationMin % $this->_roundingToMin) > 0) {
                        $slot['end']->addMinute($this->_roundingToMin - $modMin);
                    }
                } else {
                    $slot['start']->setMinute(
                        $this->_roundingToMin * floor($slot['start']->format('i') / $this->_roundingToMin));
                    $slot['end']->setMinute(
                        $end = ($this->_roundingToMin * ceil($slot['end']->format('i') / $this->_roundingToMin)) % 60);
                    if (0 === $end) {
                        $slot['end']->addHour(1);
                    }
                }
            } else {
                $durationMin = ceil(($slot['end']->getTimestamp() - $slot['start']->getTimestamp()) / 60);
                if (($modMin = $durationMin % $this->_roundingToMin) > 0) {
                    $slot['end']->addMinute($this->_roundingToMin - $modMin);
                }
            }
        }
    }

    protected function getTAId(HumanResources_Model_AttendanceRecord $record): string
    {
        // read config, not property here!
        if (!$this->_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA} &&
                isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timeaccount::class])) {
            return $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timeaccount::class];
        } else {
            return $this->_staticTAid;
        }
    }

    protected function createTimeSheet(HumanResources_Model_AttendanceRecord $record, ?string $type = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        static $translate;
        if (null === $translate) {
            $translate = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME);
        }
        $taId = $this->getTAId($record);

        /** @var Tinebase_DateTime $date */
        $date = $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->getClone()->setTimezone(Tinebase_Core::getUserTimezone());
        $ts = new Timetracker_Model_Timesheet([
            'account_id' => $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID),
            'timeaccount_id' => $taId,
            'start_date' => $date,
            'start_time' => $date->format('H:i:s'),
            'end_time' => $date->format('H:i:s'),
            'duration' => 0,
        ], true);

        $ts->description = sprintf($translate->_($type ?
            (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT === $type ? 'Clock out: %1$s' : 'Clock pause: %1$s')
            : 'Clock in: %1$s'), $ts->start_time);
        if ($record->{HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID}) {
            $ts->{HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON} = $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID);
        }
        if (HumanResources_Model_AttendanceRecord::STATUS_FAULTY === $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS}) {
            $ts->need_for_clarification = true;
        }

        $ts = Timetracker_Controller_Timesheet::getInstance()->create($ts);
        // doesnt make the record dirty!
        $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'] =
            $ts->getId();
        $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq'] =
            $ts->seq;
        // this does
        $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS} = (string)$record->{HumanResources_Model_AttendanceRecord::FLD_STATUS};

        return $ts;
    }

    public function undo(Tinebase_Record_RecordSet $data): void
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        $tsCtrl = Timetracker_Controller_Timesheet::getInstance();
        $tsCtrlRaii = new Tinebase_RAII($tsCtrl->assertPublicUsage());
        $tsData = [];

        /** @var HumanResources_Model_AttendanceRecord $record */
        foreach ($data as $record) {
            // this makes the record dirty
            $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = 0;
            if (isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'])) {
                $tsData[$record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id']] =
                    $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class];
            }
            if (isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['fttTS']['id'])) {
                $tsData[$record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['fttTS']['id']] = ['seq' => 1];
            }
            if (isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['fttTS']['ids'])) {
                foreach ($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['fttTS']['ids'] as $id => $val) {
                    if ($val) {
                        $tsData[$id] = ['seq' => $val];
                    } else {
                        $tsData[$id] = ['changed' => true];
                    }
                }
            }

            // xprops doesn't dirty!
            unset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]);
        }

        foreach ($tsData as $id => $tsd) {
            $ts = null;
            try {
                if (isset($tsd['changed']) || (int)($ts = $tsCtrl->get($id))->seq > (int)$tsd['seq']) {
                    unset($tsData[$id]);
                    if (!$ts) {
                        $ts = $tsCtrl->get($id);
                    }
                    if (!$ts->need_for_clarification) {
                        $ts->need_for_clarification = true;
                        $tsCtrl->update($ts);
                    }
                }
            } catch (Tinebase_Exception_NotFound $tenf) {}
        }

        if (!empty($ids = array_keys($tsData))) {
            $tsCtrl->delete($ids);
        }

        unset($tsCtrlRaii);
    }
}
