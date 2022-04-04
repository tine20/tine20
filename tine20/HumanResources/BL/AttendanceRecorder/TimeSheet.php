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
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        foreach (array_unique($_data->data->{HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID}) as $accountId) {
            // read config, not property here!
            if (!$this->_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA}) {
                /** @var HumanResources_Model_Employee $employee */
                $employee = HumanResources_Controller_Employee::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                        ['field' => 'account_id', 'operator' => 'equals', 'value' => $accountId]
                    ]))->getFirstRecord();
                $this->_staticTAid = HumanResources_Controller_WorkingTimeScheme::getInstance()
                    ->getWorkingTimeAccount($employee);
            }
            $accountData = $_data->data->filter(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID, $accountId);
            foreach (array_unique($accountData->{HumanResources_Model_AttendanceRecord::FLD_REFID}) as $refId) {
                $tsId = null;
                $ts = null;
                $prevRecord = null;
                /** @var HumanResources_Model_AttendanceRecord $record */
                foreach ($accountData->filter(HumanResources_Model_AttendanceRecord::FLD_REFID, $refId) as $record) {
                    if (isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'])) {
                        $tsId = $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'];
                        $prevRecord = $record;
                        continue;
                    }
                    if (!$tsId) {
                        if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN !== $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                            // shouldn't happen, we create faulty ts and be done with it, no tsId, no prevRecord set!
                            $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
                            $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS} = HumanResources_Model_AttendanceRecord::STATUS_FAULTY;
                            $this->createTimeSheet($record);
                        } else {
                            $ts = $this->createTimeSheet($record);
                            $tsId = $ts->getId();
                            $prevRecord = $record;
                        }
                        continue;
                    }
                    if (null === $ts) {
                        $ts = Timetracker_Controller_Timesheet::getInstance()->get($tsId);
                    }
                    if (!$this->updateTimeSheet($ts, $prevRecord, $record)) {
                        $tsId = $ts = $prevRecord = null;
                    } else {
                        $prevRecord = $record;
                    }
                }
            }
        }
    }

    protected function updateTimeSheet(Timetracker_Model_Timesheet &$ts, HumanResources_Model_AttendanceRecord $prevRecord, HumanResources_Model_AttendanceRecord $record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        static $translate;
        if (null === $translate) {
            $translate = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME);
        }

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
                $ts->description = $ts->description . ' ' . sprintf($translate->translate('Clock out: %1$s'),
                    $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'));

                $ts = Timetracker_Controller_Timesheet::getInstance()->update($ts);
            }

            $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq'] =
                $ts->seq;
            $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = true;
            return false;
        }

        if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN === $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
            if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED === $prevRecord->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                $ts->start_time = null;
                $ts->end_time = null;
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
                $ts->description = $ts->description . ' ' . sprintf($translate->translate('Clock pause: %1$s'),
                        $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'));
            }
        }

        $ts = Timetracker_Controller_Timesheet::getInstance()->update($ts);
        // this doesnt make record dirty
        $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['seq']
            = $ts->seq;
        // this does
        $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = (bool)$record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED};

        return true;
    }

    protected function createTimeSheet(HumanResources_Model_AttendanceRecord $record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);

        static $translate;
        if (null === $translate) {
            $translate = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME);
        }

        // read config, not property here!
        if (!$this->_config->{HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_STATIC_TA} &&
                isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timeaccount::class])) {
            $taId = $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timeaccount::class];
        } else {
            $taId = $this->_staticTAid;
        }

        $ts = new Timetracker_Model_Timesheet([
            'account_id' => $record->getIdFromProperty(HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID),
            'timeaccount_id' => $taId,
            'start_date' => $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP},
            'start_time' => $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'),
            'end_time' => $record->{HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP}->format('H:i:s'),
            'duration' => 0,
        ], true);

        $ts->description = sprintf($translate->translate('Clock in: %1$s'), $ts->start_time);
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

        $ids = [];
        $tsData = [];
        /** @var HumanResources_Model_AttendanceRecord $record */
        foreach ($data as $record) {
            if ($record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED}) {
                $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = 0;
            }
            if (isset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'])) {
                $id = $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]['id'];
                $ids[$id] = $id;
                $tsData[$id] = $record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class];

                // xprops doesn't dirty!
                unset($record->xprops()[HumanResources_Model_AttendanceRecord::META_DATA][Timetracker_Model_Timesheet::class]);
                // this does:
                $record->{HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED} = false;
            }
        }

        foreach ($tsData as $id => $tsd) {
            $ts = null;
            if (isset($tsd['changed']) || (int)($ts = $tsCtrl->get($id))->seq > (int)$tsd['seq']) {
                unset($ids[$id]);
                if (!$ts) {
                    $ts = $tsCtrl->get($id);
                }
                if (!$ts->need_for_clarification) {
                    $ts->need_for_clarification = true;
                    $tsCtrl->update($ts);
                }
            }
        }

        if (!empty($ids)) {
            $tsCtrl->delete(array_values($ids));
        }

        unset($tsCtrlRaii);
    }
}
