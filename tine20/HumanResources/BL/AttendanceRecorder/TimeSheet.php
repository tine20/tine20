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
    /** @var Timetracker_Model_Timeaccount */
    protected $_staticTA;
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
            if ($_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA} instanceof Tinebase_Record_Interface) {
                $this->_staticTA = $_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA};
            } else {
                $this->_staticTAid = $_config->getIdFromProperty(HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA);
            }
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
                $this->_staticTA = HumanResources_Controller_WorkingTimeScheme::getInstance()
                    ->getWorkingTimeAccount($employee);
            }
            $accountData = $_data->data->filter(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID, $accountId);
            foreach (array_unique($accountData->{HumanResources_Model_AttendanceRecord::FLD_REFID}) as $refId) {
                $tsRs = null;
                $prevRecord = null;
                $refIdRecords = $accountData->filter(HumanResources_Model_AttendanceRecord::FLD_REFID, $refId);
                /** @var HumanResources_Model_AttendanceRecord $record */
                foreach ($refIdRecords as $record) {
                    if (!$tsRs && isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'])) {
                        try {
                            $tsRs = Timetracker_Controller_Timesheet::getInstance()->getMultiple($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id']);
                            $tsRs->sort(function(Timetracker_Model_Timesheet $ts1, Timetracker_Model_Timesheet $ts2): int {
                                return $ts1->start_date->compare($ts2->start_date);
                            });
                            Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($tsRs);
                            $prevRecord = $record;
                            continue;
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ts not found!');
                        }
                    }
                    if (!$tsRs) {
                        if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN !== $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                            // shouldn't happen, we create faulty ts and be done with it
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
                                        $absenceTS->end_time = '00:00:00';
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

                                    $absenceTS->end_time = $absenceClockIn->format('H:i:00');
                                    if (empty($absenceTS->notes) || is_array($absenceTS->notes)) {
                                        $absenceTS->notes = new Tinebase_Record_RecordSet(Tinebase_Model_Note::class,
                                            (array)$absenceTS->notes);
                                    }
                                    $absenceTS->notes->addRecord(new Tinebase_Model_Note([
                                        'note' => sprintf(Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)->_('Clock in: %1$s'), $absenceTS->end_time),
                                        'note_type_id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
                                    ]));
                                    if (empty($absenceTS->description)) {
                                        $absenceTS->description = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)->_('attendance recorder generated');
                                    }

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

                            $tsRs = new Tinebase_Record_RecordSet(Timetracker_Model_Timesheet::class,
                                [$this->createTimeSheet($record)]);
                            $prevRecord = $record;
                        }
                        continue;
                    } elseif (isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'])) {
                        if (empty(array_diff($tsRs->getArrayOfIds(), (array)$record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id']))) {
                            $prevRecord = $record;
                            continue;
                        }
                        unset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]);
                    }
                    if (!$this->updateTimeSheet($tsRs, $record, $prevRecord)) {
                        $this->calculateTS($tsRs, $refIdRecords, true);
                        $tsRs = null;
                        break;
                    } else {
                        $prevRecord = $record;
                    }
                }
                if ($tsRs) {
                    $this->calculateTS($tsRs, $refIdRecords);
                }
            }
        }

        unset($employeeRaii);
        unset($timesheetRaii);
        unset($timeaccountRaii);
    }

    protected function updateTimeSheet(Tinebase_Record_RecordSet $tsRs, HumanResources_Model_AttendanceRecord $record, HumanResources_Model_AttendanceRecord $prevRecord): bool
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
        $idsRaii = new Tinebase_RAII(function() use(&$record, &$tsRs) {
            $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'] =
                $tsRs->getArrayOfIds();
        });

        if (isset($prevRecord->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'])) {
            $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'] =
                $prevRecord->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'];
        }

        /** make record dirty */
        $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} =
            (bool)$record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED};

        if (HumanResources_Model_AttendanceRecord::STATUS_FAULTY === $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS} ||
            HumanResources_Model_AttendanceRecord::STATUS_FAULTY === $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_STATUS}) {
            $tsRs->need_for_clarification = true;
        }

        foreach ($tsRs as $ts) {
            if (empty($ts->description)) {
                $ts->description = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)->_('attendance recorder generated');
            }
            if (empty($ts->notes) || is_array($ts->notes)) {
                $ts->notes = new Tinebase_Record_RecordSet(Tinebase_Model_Note::class,
                    (array)$ts->notes);
            }
        }

        /** @var Timetracker_Model_Timesheet $lastTs */
        $lastTs = $tsRs->getLastRecord();

        $fillTsForDayJumps = function(HumanResources_Model_AttendanceRecord $record) use ($tsRs, &$lastTs) {
            while (strcmp($lastTs->start_date->format('Y-m-d'), $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('Y-m-d')) < 0) {
                $lastTs = clone $lastTs;
                $lastTs->setId(null);
                $lastTs->start_time = '00:00:00';
                $lastTs->end_time = null;
                $lastTs->duration = 0;
                $lastTs->start_date->addDay(1);
                $lastTs->description = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)->_('attendance recorder generated');
                $lastTs->notes = null;
                $tsRs->addRecord($lastTs = Timetracker_Controller_Timesheet::getInstance()->create($lastTs));
                $lastTs->notes = new Tinebase_Record_RecordSet(Tinebase_Model_Note::class);
            }
        };

        if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT === $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
            $fillTsForDayJumps($record);
            if ($record->{HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID}) {
                $fttId = $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID);
                $ftt = HumanResources_Controller_FreeTimeType::getInstance()->get($fttId);
                $lastTs->{HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON} = $fttId;
                // FIXME write test or check if test is available
                if ($ftt->enable_timetracking) {
                    if ($ftt->timeaccount) {
                        $fttTA = Timetracker_Controller_Timeaccount::getInstance()->get($ftt->getIdFromProperty('timeaccount'));
                    } else {
                        $fttTA = $this->getTA($record);
                    }
                    $startDate = $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->getClone()
                        ->setTimezone(Tinebase_Core::getUserTimezone());
                    $fttTS = Timetracker_Controller_Timesheet::getInstance()->create(new Timetracker_Model_Timesheet([
                        'account_id' => $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID),
                        'timeaccount_id' => $fttTA->getId(),
                        'is_billable' => $fttTA->is_billable,
                        'start_date' => $startDate,
                        'start_time' => $startDate->format('H:i:00'),
                        'end_time' => $startDate->format('H:i:00'),
                        'duration' => 0,
                        HumanResources_Model_FreeTimeType::TT_TS_SYSCF_ABSENCE_REASON => $fttId,
                        'description' => Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)->_('attendance recorder generated'),
                        'notes' => new Tinebase_Record_RecordSet(Tinebase_Model_Note::class, [
                            new Tinebase_Model_Note([
                                'note' => sprintf(Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)->_('Clock in: %1$s'), $startDate->format('H:i:s')),
                                'note_type_id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
                            ])]),
                    ], true));
                    $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['fttTS']['id'] = $fttTS->getId();
                }
                $lastTs->{HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON} = $fttId;
            }

            $lastTs->notes->addRecord(new Tinebase_Model_Note([
                'note' => sprintf($translate->_('Clock out: %1$s'),
                    $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s')),
                'note_type_id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            ]));

            return false;
        }

        if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN === $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {

            if (strcmp($lastTs->start_date->format('Y-m-d'), $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('Y-m-d')) < 0) {
                $tsRs->addRecord($lastTs = $this->createTimeSheet($record));
                $lastTs->notes = new Tinebase_Record_RecordSet(Tinebase_Model_Note::class);
            }

            $lastTs->notes->addRecord(new Tinebase_Model_Note([
                'note' => sprintf($translate->_('Clock in: %1$s'),
                    $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s')),
                'note_type_id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            ]));
            if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED !== $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                //this shouldn't happen ...
                $tsRs->need_for_clarification = true;
            }

        } else {
            // pause
            $fillTsForDayJumps($record);
            $lastTs->notes->addRecord(new Tinebase_Model_Note([
                'note' => sprintf($translate->_('Clock pause: %1$s'),
                    $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s')),
                'note_type_id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            ]));
            if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN !== $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                //this shouldn't happen ...
                $tsRs->need_for_clarification = true;
            }
        }

        unset($idsRaii);
        unset($tzRaii);
        return true;
    }

    protected function calculateTS(Tinebase_Record_RecordSet $tsRs, Tinebase_Record_RecordSet $records, bool $close = false)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        $tz = Tinebase_Core::getUserTimezone();
        $clockedIn = false;
        $slots = [];
        if ($close) {
            $records->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = 1;
        }
        $lastRecord = null;
        /** @var HumanResources_Model_AttendanceRecord $record */
        foreach ($records as $record) {
            if (isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq'])) {
                $lastRecord = $record;
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
        $tsRs->duration = 0;
        $processedIds = [];
        if (empty($slots)) {
            $startDate = $records->getFirstRecord()->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->getClone()->setTimezone($tz)->format('Y-m-d');
            /** @var Timetracker_Model_Timesheet $ts */
            $ts = $tsRs->find(function (Timetracker_Model_Timesheet $ts) use ($startDate) {
                return $ts->start_date->format('Y-m-d') === $startDate;
            }, null);
            $processedIds[] = $ts->getId();

        } else {
            reset($slots);
            $lastStartDate = null;
            foreach ($slots as $slot) {
                $startDate = $slot['start']->format('Y-m-d');
                if ($lastStartDate === $startDate) {
                    $sameDay = true;
                } else {
                    $sameDay = false;
                }
                /** @var Timetracker_Model_Timesheet $ts */
                $ts = $tsRs->find(function (Timetracker_Model_Timesheet $ts) use ($startDate) {
                    return $ts->start_date->format('Y-m-d') === $startDate;
                }, null);
                $processedIds[] = $ts->getId();

                if (!$sameDay) {
                    $ts->start_time = $slot['start']->format('H:i:00');
                } else {
                    $ts->duration += intval(ceil((((new Tinebase_DateTime($ts->start_date->format('Y-m-d ') . $ts->end_time))->getTimestamp()
                        - (new Tinebase_DateTime($ts->start_date->format('Y-m-d ') . $ts->start_time))->getTimestamp()) / 60)));
                    $ts->start_time = null;
                    $ts->end_time = null;
                }

                while (strcmp($startDate, $slot['end']->format('Y-m-d')) < 0) {
                    if (!$sameDay) {
                        $ts->end_time = '00:00:00';
                    } else {
                        $ts->duration += intval(ceil(((new Tinebase_DateTime($ts->start_date->format('Y-m-d ') . $slot['end']->format('H:i:00')))->getTimestamp()
                            - (new Tinebase_DateTime($ts->start_date->format('Y-m-d ') . $slot['start']->format('H:i:00')))->getTimestamp()) / 60));
                        $sameDay = false;
                    }
                    $startDate = $ts->start_date->getClone()->addDay(1)->format('Y-m-d');
                    $ts = $tsRs->find(function (Timetracker_Model_Timesheet $ts) use ($startDate) {
                        return $ts->start_date->format('Y-m-d') === $startDate;
                    }, null);
                    $processedIds[] = $ts->getId();
                    $ts->start_time = '00:00:00';
                }
                if (!$sameDay) {
                    $ts->end_time = $slot['end']->format('H:i:00');
                } else {
                    $ts->duration += intval(ceil(((new Tinebase_DateTime($ts->start_date->format('Y-m-d ') . $slot['end']->format('H:i:00')))->getTimestamp()
                        - (new Tinebase_DateTime($ts->start_date->format('Y-m-d ') . $slot['start']->format('H:i:00')))->getTimestamp()) / 60));
                }
                $lastStartDate = $startDate;
            }
        }

        $toDelete = array_diff($tsRs->getArrayOfIds(), $processedIds);
        foreach ($toDelete as $id) {
            $tsRs->removeById($id);
        }
        if (!empty($toDelete)) {
            Timetracker_Controller_Timesheet::getInstance()->delete($toDelete);
        }
        if ($lastRecord) {
            $changed = $lastRecord->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'] ?? [];
        } else {
            $changed = [];
        }
        foreach ($tsRs as $ts) {
            if ($lastRecord && isset($lastRecord->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq'][$ts->getId()])
                    && $ts->seq > $lastRecord->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq'][$ts->getId()]) {
                $changed[$ts->getId()] = true;
            }
            $updated = Timetracker_Controller_Timesheet::getInstance()->update($ts);
            $ts->seq = $updated->seq;
        }

        foreach ($records as $record) {
            $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'] =
                $tsRs->getArrayOfIds();
            $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq'] =
                $tsRs->getIdPropertyMap('seq');
            if (!empty($changed)) {
                $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'] = $changed;
            }
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
                    if (0 !== (int)$slot['end']->format('i')) {
                        $slot['end']->setMinute(
                            $end = ($this->_roundingToMin * ceil($slot['end']->format('i') / $this->_roundingToMin)) % 60);
                        if (0 === $end) {
                            $slot['end']->addHour(1);
                        }
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

    protected function getTA(HumanResources_Model_AttendanceRecord $record): Timetracker_Model_Timeaccount
    {
        // read config, not property here!
        if (!$this->_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA} &&
                isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timeaccount::class])) {
            /** @var Timetracker_Model_Timeaccount $ta */
            $ta = Timetracker_Controller_Timeaccount::getInstance()->get($record
                ->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timeaccount::class]);
            return $ta;
        } elseif ($this->_staticTA) {
            return $this->_staticTA;
        } else {
            $this->_staticTA = Timetracker_Controller_Timeaccount::getInstance()->get($this->_staticTAid);
            return $this->_staticTA;
        }
    }

    protected function createTimeSheet(HumanResources_Model_AttendanceRecord $record, ?string $type = null): Timetracker_Model_Timesheet
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        static $translate;
        if (null === $translate) {
            $translate = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME);
        }
        $ta = $this->getTA($record);

        /** @var Tinebase_DateTime $date */
        $date = $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->getClone()->setTimezone(Tinebase_Core::getUserTimezone());
        $ts = new Timetracker_Model_Timesheet([
            'account_id' => $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID),
            'timeaccount_id' => $ta->getId(),
            'is_billable' => $ta->is_billable,
            'start_date' => $date,
            'start_time' => $date->format('H:i:00'),
            'end_time' => $date->format('H:i:00'),
            'duration' => 0,
            'notes' => new Tinebase_Record_RecordSet(Tinebase_Model_Note::class),
            'description' => Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)
                ->_('attendance recorder generated'),
        ], true);

        $note = sprintf($translate->_($type ?
            (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT === $type ? 'Clock out: %1$s' : 'Clock pause: %1$s')
            : 'Clock in: %1$s'), $date->format('H:i:s'));
        $ts->notes->addRecord(new Tinebase_Model_Note([
            'note' => $note,
            'note_type_id' => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
        ]));
        if ($record->{HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID}) {
            $ts->{HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON} = $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID);
        }
        if (HumanResources_Model_AttendanceRecord::STATUS_FAULTY === $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS}) {
            $ts->need_for_clarification = true;
        }

        /** @var Timetracker_Model_Timesheet $ts */
        $ts = Timetracker_Controller_Timesheet::getInstance()->create($ts);
        // doesnt make the record dirty!
        $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'] =
            [$ts->getId()];
        $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq'] =
            [$ts->getId() => $ts->seq];
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
                foreach ((array)$record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'] as $id) {
                    if (isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'][$id])) {
                        $tsData[$id] = ['changed' => true];
                    } else {
                        $tsData[$id] = [
                            'seq' => $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq'][$id]
                        ];
                    }
                }
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
