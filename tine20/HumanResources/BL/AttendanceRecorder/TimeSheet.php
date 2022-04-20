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

    public function __construct(HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig $_config)
    {
        $this->_config = $_config;
        if ($_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA}) {
            $this->_staticTAid = $_config->getIdFromProperty(HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA);
        }
        $this->_allowOtherTAs = (bool)$_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_ALLOW_OTHER_TA};

        $this->_tsPublicRAII = new Tinebase_RAII(Timetracker_Controller_Timesheet::getInstance()->assertPublicUsage());
    }

    /**
     * @param Tinebase_BL_PipeContext $_context
     * @param Tinebase_BL_DataInterface $_data
     */
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        /** @var HumanResources_BL_AttendanceRecorder_Data $_data */
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        $oldUser = Tinebase_Core::getUser();
        $userRaii = new Tinebase_RAII(function() use ($oldUser) {
            Tinebase_Core::setUser($oldUser);
        });

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
                $tsId = null;
                $ts = null;
                $prevRecord = null;
                /** @var HumanResources_Model_AttendanceRecord $record */
                foreach ($accountData->filter(HumanResources_Model_AttendanceRecord::FLD_REFID, $refId) as $record) {
                    if (isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'])) {
                        if ($prevRecord) {
                            // this shouldnt happen
                            $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
                        }
                        $tsId = $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'];
                        $prevRecord = $record;
                        continue;
                    }
                    if (!$tsId) {
                        if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN !== $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                            // shouldn't happen, we create faulty ts and be done with it, no tsId, no prevRecord set!
                            $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
                            $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS} = HumanResources_Model_AttendanceRecord::STATUS_FAULTY;
                            $this->createTimeSheet($record, $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE});
                            continue;
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
                            $tsId = $ts->getId();
                            $prevRecord = $record;
                        }
                        continue;
                    }
                    if (null === $ts) {
                        try {
                            $ts = Timetracker_Controller_Timesheet::getInstance()->get($tsId);
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            // shouldn't happen, we create faulty ts and be done with it, no tsId, no prevRecord set!
                            $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
                            $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS} = HumanResources_Model_AttendanceRecord::STATUS_FAULTY;
                            $this->createTimeSheet($record, $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE});
                            continue;
                        }
                    }
                    if (!$this->updateTimeSheet($ts, $prevRecord, $record)) {
                        $tsId = $ts = $prevRecord = null;
                    } else {
                        $prevRecord = $record;
                    }
                }
            }
        }

        unset($userRaii);
    }

    protected function updateTimeSheet(Timetracker_Model_Timesheet &$ts, HumanResources_Model_AttendanceRecord $prevRecord, HumanResources_Model_AttendanceRecord $record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        static $translate;
        if (null === $translate) {
            $translate = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME);
        }

        $tz = Tinebase_Core::getUserTimezone();
        $prevRecord->setTimezone($tz, false);
        $record->setTimezone($tz, false);
        $tzRaii = new Tinebase_RAII(function() use($prevRecord, $record) {
            $prevRecord->setTimezone('UTC', false);
            $record->setTimezone('UTC', false);
        });

        $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
        $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'] =
            $ts->getId();
        if (isset($prevRecord->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'])) {
            $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'] =
                $prevRecord->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'];
        }

        if ((int)$ts->seq > (int)$prevRecord->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq']) {
            $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['changed'] = true;
        }

        if (HumanResources_Model_AttendanceRecord::STATUS_FAULTY === $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS} ||
                HumanResources_Model_AttendanceRecord::STATUS_FAULTY === $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_STATUS}) {
            $ts->need_for_clarification = true;
        }

        if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT === $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
            if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED !== $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                $ts->end_time = null;
                $ts->duration = $ts->duration + (int)(($record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}
                    ->getTimestamp() -
                        $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->getTimestamp()) / 60);
            }

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
            $ts = Timetracker_Controller_Timesheet::getInstance()->update($ts);

            $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq'] =
                $ts->seq;
            $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
            return false;
        }

        if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN === $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
            if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED === $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                $ts->start_time = null;
                $ts->end_time = null;
                $ts->duration = $ts->duration + (int)(($record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}
                                ->getTimestamp() -
                            $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->getTimestamp()) / 60);
                $ts->description = $ts->description . ' ' . sprintf($translate->_('Clock in: %1$s'),
                        $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'));
            } else {
                //this shouldn't happen .... we just ignore this clockin...
                $ts->need_for_clarification = true;
            }

        } else {
            // pause
            if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN !== $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                //this shouldn't happen .... we just ignore this clockpause...
                $ts->need_for_clarification = true;
            } else {
                if ($ts->end_time) {
                    $ts->end_time = $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s');
                }
                $ts->duration = $ts->duration + (int)(($record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}
                                ->getTimestamp() -
                            $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->getTimestamp()) / 60);
                $ts->description = $ts->description . ' ' . sprintf($translate->_('Clock pause: %1$s'),
                        $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'));
            }
        }

        $ts = Timetracker_Controller_Timesheet::getInstance()->update($ts);
        // this doesnt make record dirty
        $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq']
            = $ts->seq;
        // this does
        $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = (bool)$record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED};

        unset($tzRaii);
        return true;
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

        $record->setTimezone(Tinebase_Core::getUserTimezone(), false);
        $tzRaii = new Tinebase_RAII(function() use($record) {
            $record->setTimezone('UTC', false);
        });

        $taId = $this->getTAId($record);

        $ts = new Timetracker_Model_Timesheet([
            'account_id' => $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID),
            'timeaccount_id' => $taId,
            'start_date' => $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP},
            'start_time' => $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'),
            'end_time' => $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'),
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

        unset($tzRaii);
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
