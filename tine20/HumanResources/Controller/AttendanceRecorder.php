<?php declare(strict_types=1);
/**
 * AttendanceRecorder Controller
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * AttendanceRecorder Controller
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_AttendanceRecorder
{
    use Tinebase_Controller_SingletonTrait;

    protected $backend;

    protected function __construct()
    {
        $this->backend = HumanResources_Controller_AttendanceRecord::getInstance();
    }

    protected function checkOutOfSequence(HumanResources_Config_AttendanceRecorder $config): ?Closure
    {
        $aheadRecords = $this->backend->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_AttendanceRecord::class, [
            ['field' => HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => $config->getAccount()->getId()],
            ['field' => HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP,  'operator' => 'after', 'value' => $config->getTimeStamp()],
        ]), new Tinebase_Model_Pagination(['sort' => HumanResources_Model_AttendanceRecord::FLD_SEQUENCE, 'dir' => 'ASC']));

        if (0 === $aheadRecords->count()) {
            return null;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' out of sequence clock action encountered.'
        );
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($config->__serialize(), true)
        );

        // shallow clone
        $blUndo = $aheadRecords->getClone(true);
        $refIdsToOpen = array_unique($aheadRecords->filter(HumanResources_Model_AttendanceRecord::FLD_TYPE,
            HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT)->{HumanResources_Model_AttendanceRecord::FLD_REFID});
        if (!empty($refIdsToOpen)) {
            $refIdsToOpen = $this->backend->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_AttendanceRecord::class, [
                ['field' => HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => $config->getAccount()->getId()],
                ['field' => HumanResources_Model_AttendanceRecord::FLD_REFID, 'operator' => 'in', 'value' => $refIdsToOpen],
                ['field' => 'id', 'operator' => 'notin', 'value' => $aheadRecords->getArrayOfIds()],
            ]));
            $blUndo->merge($refIdsToOpen);
            $blUndo->sort(HumanResources_Model_AttendanceRecord::FLD_SEQUENCE, 'ASC', 'asort',  SORT_NUMERIC);
        }

        // undo BL
        $deviceIds = array_unique($aheadRecords->{HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID});
        foreach ($deviceIds as $deviceId) {
            $device = HumanResources_Controller_AttendanceRecorderDevice::getInstance()->get($deviceId);
            if (empty($device->{HumanResources_Model_AttendanceRecorderDevice::FLD_BLPIPE}) ||
                    0 === $device->{HumanResources_Model_AttendanceRecorderDevice::FLD_BLPIPE}->count()) {
                continue;
            }
            $deviceRecords = $blUndo->filter(HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID, $deviceId);
            if ($deviceRecords->count() > 0) {
                foreach ($device->{HumanResources_Model_AttendanceRecorderDevice::FLD_BLPIPE} as $blpipe) {
                    $blElem = $blpipe->configRecord->getNewBLElement();
                    if ($blElem instanceof HumanResources_BL_AttendanceRecorder_UndoInterface) {
                        $blElem->undo($deviceRecords);
                    }
                }
            }
        }

        $blUndo->removeRecordsById($aheadRecords);
        // delete
        foreach ($aheadRecords as $record) {
            $this->backend->getBackend()->delete($record->getId());
        }
        if ($refIdsToOpen instanceof Tinebase_Record_RecordSet) {
            foreach ($refIdsToOpen as $record) {
                if (HumanResources_Model_AttendanceRecord::STATUS_OPEN === $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS}) {
                    continue;
                }
                $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS} = HumanResources_Model_AttendanceRecord::STATUS_OPEN;
                $this->backend->getBackend()->update($record);
                $blUndo->removeById($record->getId());
            }
        }
        foreach ($blUndo as $record) {
            if ($record->isDirty()) {
                $this->backend->getBackend()->update($record);
            }
        }

        // return and do this api call
        // then replay deleted api calls!
        return function() use ($aheadRecords) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' replay API actions due to out of sequence clock action.'
            );
            /** @var HumanResources_Model_AttendanceRecord $record */
            foreach($aheadRecords->filter(HumanResources_Model_AttendanceRecord::FLD_AUTOGEN, false) as $record) {
                $cfg = $record->getConfig();
                $cfg->setThrowOnFaultyAction(false);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' ' . $record->{HumanResources_Model_AttendanceRecord::FLD_TYPE} .
                    ' ' . print_r($cfg->__serialize(), true)
                );
                switch($record->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                    case HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN:
                        $this->clockIn($cfg);
                        break;
                    case HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT:
                        $this->clockOut($cfg);
                        break;
                    case HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED:
                        $this->clockPause($cfg);
                        break;
                }
            }
        };
    }

    public function clockIn(HumanResources_Config_AttendanceRecorder $config): HumanResources_Model_AttendanceRecorderClockInOutResult
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($config->__serialize(), true)
        );

        $raii = Tinebase_RAII::getTransactionManagerRAII();

        $this->prepareConfig($config);
        if (null === $config->getStatus()) {
            $config->setStatus(HumanResources_Model_AttendanceRecord::STATUS_OPEN);
        } elseif (HumanResources_Model_AttendanceRecord::STATUS_OPEN !== $config->getStatus()) {
            throw new Tinebase_Exception_UnexpectedValue('can\'t clockIn with other status than ' . HumanResources_Model_AttendanceRecord::STATUS_OPEN);
        }

        $this->registerAfterCommitAsyncBLPipeRun();
        $result = $this->getClockInOutResult();

        $outOfSequenceClosure = $this->checkOutOfSequence($config);

        $lastRecord = null;
        $openRecords = null;
        if ($config->getDevice()->{HumanResources_Model_AttendanceRecorderDevice::FLD_ALLOW_MULTI_START} &&
                isset($config->getMetaData()[HumanResources_Model_AttendanceRecord::CLOCK_OUT_OTHERS]) &&
                $config->getMetaData()[HumanResources_Model_AttendanceRecord::CLOCK_OUT_OTHERS]) {
            $openRecords = $this->getOpenRecords($config->getAccount()->getId(), $config->getDevice()->getId());
            foreach ($openRecords as $openRecord) {
                if ($openRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID} === $config->getRefId()) {
                    continue;
                }
                $subConfig = clone $config;
                $subConfig->setRefId($openRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID});
                $subConfig->setStatus(HumanResources_Model_AttendanceRecord::STATUS_CLOSED);
                $subConfig->setAutogen(true);
                $subConfig->setMetaData(array_filter($subConfig->getMetaData() ?: [], function($key) {
                    return $key === HumanResources_Config_AttendanceRecorder::METADATA_SOURCE;
                }, ARRAY_FILTER_USE_KEY));
                $subResult = $this->clockOut($subConfig);
                $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_INS}->mergeById(
                    $subResult->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_INS});
                $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_OUTS}->mergeById(
                    $subResult->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_OUTS});
                $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_PAUSES}->mergeById(
                    $subResult->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_PAUSES});
                $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_FAULTY_CLOCKS}->mergeById(
                    $subResult->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_FAULTY_CLOCKS});
            }
            $openRecords = null;
        }

        if (null === $config->getRefId()) {
            if ($config->getDevice()->{HumanResources_Model_AttendanceRecorderDevice::FLD_ALLOW_MULTI_START}) {
                $config->setRefId(Tinebase_Record_Abstract::generateUID());
            } else {
                if ($lastRecord = ($openRecords = $this->getOpenRecords($config->getAccount()->getId(), $config->getDevice()->getId()))->getLastRecord()) {
                    $config->setRefId($lastRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID});
                } else {
                    $config->setRefId(Tinebase_Record_Abstract::generateUID());
                }
            }
        } else {
            $openRecords = $this->getOpenRecords($config->getAccount()->getId(), $config->getDevice()->getId());
            if ($config->getDevice()->{HumanResources_Model_AttendanceRecorderDevice::FLD_ALLOW_MULTI_START}) {
                $lastRecord = $openRecords->filter(HumanResources_Model_AttendanceRecord::FLD_REFID, $config->getRefId())->getLastRecord();
            } else {
                $lastRecord = $openRecords->getLastRecord();
            }
        }

        if ($lastRecord && $lastRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID} !== $config->getRefId()) {
            if ($config->getThrowOnFaultyAction()) {
                throw new Tinebase_Exception_UnexpectedValue('refId mismatch');
            }
        }
        if ($lastRecord && HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED !==
                $lastRecord->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
            // graceful close conditions
            $graceful = isset($config->getMetaData()[HumanResources_Model_AttendanceRecord::CLOCK_OUT_GRACEFULLY]);

            if (!$graceful && $config->getThrowOnFaultyAction()) {
                throw new Tinebase_Exception_UnexpectedValue('can\'t clock in, open attendance record found');
            }

            $config->setRefId(Tinebase_Record_Abstract::generateUID());
            $subConfig = clone $config;
            $subConfig->setRefId($lastRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID});
            if (!$graceful) {
                $subConfig->setStatus(HumanResources_Model_AttendanceRecord::STATUS_FAULTY);
                foreach ($openRecords as $openRecord) {
                    if (HumanResources_Model_AttendanceRecord::STATUS_FAULTY !==
                            $openRecord->{HumanResources_Model_AttendanceRecord::FLD_STATUS}) {
                        $openRecord->{HumanResources_Model_AttendanceRecord::FLD_STATUS} = HumanResources_Model_AttendanceRecord::STATUS_FAULTY;
                        HumanResources_Controller_AttendanceRecord::getInstance()->update($openRecord);
                    }
                }
            } else {
                $subConfig->setStatus(HumanResources_Model_AttendanceRecord::STATUS_CLOSED);
            }
            $subConfig->setAutogen(true);
            $subConfig->setMetaData(array_filter($subConfig->getMetaData() ?: [], function($key) {
                return $key === HumanResources_Config_AttendanceRecorder::METADATA_SOURCE;
            }, ARRAY_FILTER_USE_KEY));
            $subResult = $this->clockOut($subConfig);
            $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_INS}->mergeById(
                $subResult->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_INS});
            $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_OUTS}->mergeById(
                $subResult->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_OUTS});
            $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_PAUSES}->mergeById(
                $subResult->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_PAUSES});
            $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_FAULTY_CLOCKS}->mergeById(
                $subResult->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_FAULTY_CLOCKS});
        }

        /** @var HumanResources_Model_AttendanceRecorderDeviceRef $device */
        foreach ($config->getDevice()->{HumanResources_Model_AttendanceRecorderDevice::FLD_STARTS} as $device) {
            /** @var HumanResources_Model_AttendanceRecorderDevice $device */
            $device = $device->{HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID};
            if ($device->{HumanResources_Model_AttendanceRecorderDevice::FLD_ALLOW_MULTI_START}) {
                // TODO fixme shouldn't happen... at least log a warn?
                continue;
            }
            if (!($lastRecord = $this->getOpenRecords($config->getAccount()->getId(), $device->getId())->getLastRecord()) ||
                    HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED === $lastRecord->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                $tmpCfg = clone $config;
                $tmpCfg->setMetaData(array_filter($tmpCfg->getMetaData() ?: [], function($key) {
                    return $key === HumanResources_Config_AttendanceRecorder::METADATA_SOURCE;
                }, ARRAY_FILTER_USE_KEY));
                $tmpCfg->setDevice($device);
                $tmpCfg->setAutogen(true);
                $tmpCfg->setRefId($lastRecord ? $lastRecord->{HumanResources_Model_AttendanceRecord::FLD_REFID}
                    : Tinebase_Record_Abstract::generateUID());
                $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_INS}->addRecord(
                    $this->backend->create(
                        $this->createAttendanceRecord($tmpCfg, HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN)
                    )
                );
            }
        }

        if ($lastRecord && HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED ===
                $lastRecord->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
            /** @var HumanResources_Model_AttendanceRecorderDeviceRef $device */
            foreach ($config->getDevice()->{HumanResources_Model_AttendanceRecorderDevice::FLD_UNPAUSES} as $device) {
                /** @var HumanResources_Model_AttendanceRecorderDevice $device */
                $device = $device->{HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID};
                $unpauseOpenRecords = $this->getOpenRecords($config->getAccount()->getId(), $device->getId());
                $refIds = [];
                /** @var HumanResources_Model_AttendanceRecord $record */
                foreach ($unpauseOpenRecords as $record) {
                    if (isset($refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}])) {
                        continue;
                    }
                    $refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}] = true;
                    $record = $unpauseOpenRecords->filter(HumanResources_Model_AttendanceRecord::FLD_REFID,
                            $record->{HumanResources_Model_AttendanceRecord::FLD_REFID})->getLastRecord();
                    if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED !== $record
                            ->{HumanResources_Model_AttendanceRecord::FLD_TYPE} ||
                            !$record->{HumanResources_Model_AttendanceRecord::FLD_AUTOGEN}) {
                        continue;
                    }
                    $tmpCfg = clone $config;
                    $tmpCfg->setMetaData(array_filter($tmpCfg->getMetaData() ?: [], function ($key) {
                        return $key === HumanResources_Config_AttendanceRecorder::METADATA_SOURCE;
                    }, ARRAY_FILTER_USE_KEY));
                    $tmpCfg->setDevice($device);
                    $tmpCfg->setAutogen(true);
                    $tmpCfg->setRefId($record->{HumanResources_Model_AttendanceRecord::FLD_REFID});
                    $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_INS}->addRecord(
                        $this->backend->create(
                            $this->createAttendanceRecord($tmpCfg, HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN)
                        )
                    );
                }
            }
        }

        $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_INS}->addRecord(
            $this->backend->create(
                $this->createAttendanceRecord($config, HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN)
            )
        );

        if ($outOfSequenceClosure) {
            $outOfSequenceClosure();
        }

        $raii->release();

        return $result;
    }

    public function clockPause(HumanResources_Config_AttendanceRecorder $config): HumanResources_Model_AttendanceRecorderClockInOutResult
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($config->__serialize(), true)
        );

        $raii = Tinebase_RAII::getTransactionManagerRAII();

        $this->prepareConfig($config);
        if (null === $config->getStatus()) {
            $config->setStatus(HumanResources_Model_AttendanceRecord::STATUS_OPEN);
        } elseif (HumanResources_Model_AttendanceRecord::STATUS_OPEN !== $config->getStatus()) {
            throw new Tinebase_Exception_UnexpectedValue('can\'t clockPause with other status than ' . HumanResources_Model_AttendanceRecord::STATUS_OPEN);
        }

        $result = $this->getClockInOutResult();
        if (!$config->getDevice()->{HumanResources_Model_AttendanceRecorderDevice::FLD_ALLOW_PAUSE}) {
            if ($config->getThrowOnFaultyAction()) {
                throw new Tinebase_Exception_UnexpectedValue('device doesn\'t allow to clock pauses');
            }
            return $result;
        }

        $this->registerAfterCommitAsyncBLPipeRun();

        $outOfSequenceClosure = $this->checkOutOfSequence($config);

        $openRecords = $this->getOpenRecords($config->getAccount()->getId(), $config->getDevice()->getId());
        if ($config->getRefId()) {
            $openRecords = $openRecords->filter(HumanResources_Model_AttendanceRecord::FLD_REFID, $config->getRefId());
        }
        $refIds = [];
        $toBePaused = new Tinebase_Record_RecordSet(HumanResources_Model_AttendanceRecord::class);
        /** @var HumanResources_Model_AttendanceRecord $record */
        foreach ($openRecords as $record) {
            if (isset($refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}])) {
                continue;
            }
            $refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}] = true;
            if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN !== $openRecords->filter(
                    HumanResources_Model_AttendanceRecord::FLD_REFID,
                    $record->{HumanResources_Model_AttendanceRecord::FLD_REFID})->getLastRecord()
                    ->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                continue;
            }
            $toBePaused->addRecord($record);
        }

        if (0 === $toBePaused->count()) {
            if ($config->getThrowOnFaultyAction()) {
                throw new Tinebase_Exception_UnexpectedValue('can\'t clock pause, no open records found for refId: ' .
                    $config->getRefId());
            }
            $config->setStatus(HumanResources_Model_AttendanceRecord::STATUS_FAULTY);
            $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_FAULTY_CLOCKS}->addRecord(
                $this->backend->create(
                    $this->createAttendanceRecord($config, HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED)
                )
            );
        }

        /** @var HumanResources_Model_AttendanceRecorderDeviceRef $device */
        foreach ($config->getDevice()->{HumanResources_Model_AttendanceRecorderDevice::FLD_PAUSES} as $device) {
            /** @var HumanResources_Model_AttendanceRecorderDevice $device */
            $device = $device->{HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID};
            $pauseOpenRecords = $this->getOpenRecords($config->getAccount()->getId(), $device->getId());
            $refIds = [];
            /** @var HumanResources_Model_AttendanceRecord $record */
            foreach ($pauseOpenRecords as $record) {
                if (isset($refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}])) {
                    continue;
                }
                $refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}] = true;
                if (HumanResources_Model_AttendanceRecord::TYPE_CLOCK_IN !== $pauseOpenRecords->filter(
                        HumanResources_Model_AttendanceRecord::FLD_REFID,
                        $record->{HumanResources_Model_AttendanceRecord::FLD_REFID})->getLastRecord()
                        ->{HumanResources_Model_AttendanceRecord::FLD_TYPE}) {
                    continue;
                }
                $tmpCfg = clone $config;
                $tmpCfg->setMetaData(array_filter($tmpCfg->getMetaData() ?: [], function($key) {
                    return $key === HumanResources_Config_AttendanceRecorder::METADATA_SOURCE;
                }, ARRAY_FILTER_USE_KEY));
                $tmpCfg->setDevice($device);
                $tmpCfg->setAutogen(true);
                $tmpCfg->setRefId($record->{HumanResources_Model_AttendanceRecord::FLD_REFID});
                $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_PAUSES}->addRecord(
                    $this->backend->create(
                        $this->createAttendanceRecord($tmpCfg, HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED)
                    )
                );
            }
        }

        $refIds = [];
        /** @var HumanResources_Model_AttendanceRecord $record */
        foreach ($toBePaused as $record) {
            if (isset($refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}])) {
                continue;
            }
            $refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}] = true;
            $config->setRefId($record->{HumanResources_Model_AttendanceRecord::FLD_REFID});
            $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_PAUSES}->addRecord(
                $this->backend->create(
                    $this->createAttendanceRecord($config, HumanResources_Model_AttendanceRecord::TYPE_CLOCK_PAUSED)
                )
            );
        }

        if ($outOfSequenceClosure) {
            $outOfSequenceClosure();
        }

        $raii->release();

        return $result;
    }

    public function clockOut(HumanResources_Config_AttendanceRecorder $config): HumanResources_Model_AttendanceRecorderClockInOutResult
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($config->__serialize(), true)
        );

        $raii = Tinebase_RAII::getTransactionManagerRAII();

        $this->prepareConfig($config);
        if (null === $config->getStatus()) {
            $config->setStatus(HumanResources_Model_AttendanceRecord::STATUS_CLOSED);
        } elseif (HumanResources_Model_AttendanceRecord::STATUS_CLOSED !== $config->getStatus() &&
                HumanResources_Model_AttendanceRecord::STATUS_FAULTY !== $config->getStatus()) {
            throw new Tinebase_Exception_UnexpectedValue('can\'t clockOut with status other than ' .
                HumanResources_Model_AttendanceRecord::STATUS_CLOSED . ' or ' .
                HumanResources_Model_AttendanceRecord::STATUS_FAULTY);
        }

        $this->registerAfterCommitAsyncBLPipeRun();
        $result = $this->getClockInOutResult();

        $outOfSequenceClosure = $this->checkOutOfSequence($config);

        $openRecords = $this->getOpenRecords($config->getAccount()->getId(), $config->getDevice()->getId());
        if ($config->getRefId()) {
            $toBeClosed = $openRecords->filter(HumanResources_Model_AttendanceRecord::FLD_REFID, $config->getRefId());
        } else {
            $toBeClosed = $openRecords;
        }

        if (0 === $toBeClosed->count()) {
            if ($config->getThrowOnFaultyAction()) {
                throw new Tinebase_Exception_UnexpectedValue('can\'t clock out, no open records found for refId: ' .
                    $config->getRefId());
            }
            $config->setStatus(HumanResources_Model_AttendanceRecord::STATUS_FAULTY);
            $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_FAULTY_CLOCKS}->addRecord(
                $this->backend->create(
                    $this->createAttendanceRecord($config, HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT)
                )
            );
        }

        /** @var HumanResources_Model_AttendanceRecorderDeviceRef $device */
        foreach ($config->getDevice()->{HumanResources_Model_AttendanceRecorderDevice::FLD_STOPS} as $device) {
            /** @var HumanResources_Model_AttendanceRecorderDevice $device */
            $device = $device->{HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID};
            $stopOpenRecords = $this->getOpenRecords($config->getAccount()->getId(), $device->getId());
            $refIds = [];
            /** @var HumanResources_Model_AttendanceRecord $record */
            foreach ($stopOpenRecords as $record) {
                $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS} = HumanResources_Model_AttendanceRecord::STATUS_CLOSED;
                $this->backend->update($record);
                if (isset($refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}])) {
                    continue;
                }
                $refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}] = true;
                $tmpCfg = clone $config;
                $tmpCfg->setMetaData(array_filter($tmpCfg->getMetaData() ?: [], function($key) {
                    return $key === HumanResources_Config_AttendanceRecorder::METADATA_SOURCE;
                }, ARRAY_FILTER_USE_KEY));
                $tmpCfg->setDevice($device);
                $tmpCfg->setAutogen(true);
                $tmpCfg->setRefId($record->{HumanResources_Model_AttendanceRecord::FLD_REFID});
                $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_OUTS}->addRecord(
                    $this->backend->create(
                        $this->createAttendanceRecord($tmpCfg, HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT)
                    )
                );
            }
        }

        $refIds = [];
        $orgConfig = clone $config;
        /** @var HumanResources_Model_AttendanceRecord $record */
        foreach ($toBeClosed as $record) {
            $record->{HumanResources_Model_AttendanceRecord::FLD_STATUS} = HumanResources_Model_AttendanceRecord::STATUS_CLOSED;
            $this->backend->update($record);
            if (isset($refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}])) {
                continue;
            }
            $refIds[$record->{HumanResources_Model_AttendanceRecord::FLD_REFID}] = true;
            $config->setRefId($record->{HumanResources_Model_AttendanceRecord::FLD_REFID});
            $result->{HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_OUTS}->addRecord(
                $this->backend->create(
                    $this->createAttendanceRecord($config, HumanResources_Model_AttendanceRecord::TYPE_CLOCK_OUT, $orgConfig)
                )
            );
        }

        if ($outOfSequenceClosure) {
            $outOfSequenceClosure();
        }

        $raii->release();

        return $result;
    }

    protected function getClockInOutResult(): HumanResources_Model_AttendanceRecorderClockInOutResult
    {
        return new HumanResources_Model_AttendanceRecorderClockInOutResult([
            HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_INS =>
                new Tinebase_Record_RecordSet(HumanResources_Model_AttendanceRecord::class, []),
            HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_OUTS =>
                new Tinebase_Record_RecordSet(HumanResources_Model_AttendanceRecord::class, []),
            HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_CLOCK_PAUSES =>
                new Tinebase_Record_RecordSet(HumanResources_Model_AttendanceRecord::class, []),
            HumanResources_Model_AttendanceRecorderClockInOutResult::FLD_FAULTY_CLOCKS =>
                new Tinebase_Record_RecordSet(HumanResources_Model_AttendanceRecord::class, []),
        ]);
    }

    protected function registerAfterCommitAsyncBLPipeRun()
    {
        Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(function() {
            Tinebase_ActionQueue::getInstance()->queueAction(HumanResources_Controller_AttendanceRecorder::class .
                '.runBLPipes');
        });
    }

    protected function createAttendanceRecord(HumanResources_Config_AttendanceRecorder $config, string $type, ?HumanResources_Config_AttendanceRecorder $orgConfig = null): HumanResources_Model_AttendanceRecord
    {
        return new HumanResources_Model_AttendanceRecord([
            HumanResources_Model_AttendanceRecord::FLD_TIMESTAMP => $config->getTimeStamp(),
            HumanResources_Model_AttendanceRecord::FLD_TYPE => $type,
            HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID => $config->getAccount()->getId(),
            HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID => $config->getDevice()->getId(),
            HumanResources_Model_AttendanceRecord::FLD_STATUS => $config->getStatus(),
            HumanResources_Model_AttendanceRecord::FLD_REFID => $config->getRefId() ?: Tinebase_Record_Abstract::generateUID(),
            HumanResources_Model_AttendanceRecord::FLD_FREETIMETYPE_ID => $config->getFreetimetypeId(),
            HumanResources_Model_AttendanceRecord::FLD_CREATION_CONFIG => serialize($orgConfig ?: $config),
            HumanResources_Model_AttendanceRecord::FLD_AUTOGEN => $config->getAutogen(),
            'xprops' => [HumanResources_Model_AttendanceRecord::META_DATA => $config->getMetaData()],
        ]);
    }

    protected function getOpenRecords(string $accountId, string $deviceId, array $type = []): Tinebase_Record_RecordSet
    {
        return $this->backend->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_AttendanceRecord::class, array_merge([
                ['field' => HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => $accountId],
                ['field' => HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID,  'operator' => 'equals', 'value' => $deviceId],
                ['field' => HumanResources_Model_AttendanceRecord::FLD_STATUS,  'operator' => 'equals', 'value' => HumanResources_Model_AttendanceRecord::STATUS_OPEN],
            ], (empty($type) ? [] :
                [['field' => HumanResources_Model_AttendanceRecord::FLD_TYPE,  'operator' => 'in', 'value' => $type]])
        )), new Tinebase_Model_Pagination(['sort' => HumanResources_Model_AttendanceRecord::FLD_SEQUENCE, 'dir' => 'ASC']));
    }

    protected function prepareConfig(HumanResources_Config_AttendanceRecorder $config): void
    {
        if (null === $config->getDevice()) {
            throw new Tinebase_Exception_UnexpectedValue('device needs to be give');
        }
        Tinebase_Record_Expander::expandRecord($config->getDevice());

        if ($e = $config->getEmployee()) {
            if ($config->getAccount()) {
                if ($config->getAccount()->getId() !== $e->account_id) {
                    throw new Tinebase_Exception_UnexpectedValue(HumanResources_Config_AttendanceRecorder::class .
                        ' employee and account mismatch');
                }
            } else {
                $config->setAccount(Tinebase_User::getInstance()->getFullUserById($e->account_id));
            }
        } else {
            if (!$config->getAccount()) {
                $config->setAccount(Tinebase_Core::getUser());
            }
            $employeeCtrl = HumanResources_Controller_Employee::getInstance();
            $employeeCtrlRaii = new Tinebase_RAII($employeeCtrl->assertPublicUsage());
            /** @var ?HumanResources_Model_Employee $e */
            $e = $employeeCtrl->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                    ['field' => 'account_id', 'operator' => 'equals', 'value' => $config->getAccount()->getId()],
                ])
            )->getFirstRecord();
            unset($employeeCtrlRaii);
            $config->setEmployee($e);
        }

        if (null === $config->getTimeStamp()) {
            $config->setTimeStamp(Tinebase_DateTime::now());
        }
    }

    public static function runBLPipes($accountId = null): void
    {
        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        $deviceCtrl = HumanResources_Controller_AttendanceRecorderDevice::getInstance();
        $recordCtrl = HumanResources_Controller_AttendanceRecord::getInstance();

        $devices = $deviceCtrl->getAll();
        /** @var HumanResources_Model_AttendanceRecorderDevice $device */
        foreach ($devices as $device) {
            if (0 === $device->{HumanResources_Model_AttendanceRecorderDevice::FLD_BLPIPE}->count()) {
                continue;
            }

            /** @var Tinebase_Backend_Sql_Abstract $backend */
            $backend = $recordCtrl->getBackend();
            $selectForUpdate = Tinebase_Backend_Sql_SelectForUpdateHook::getRAII($backend);

            $data = $recordCtrl->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                HumanResources_Model_AttendanceRecord::class, array_merge([
                ['field' => HumanResources_Model_AttendanceRecord::FLD_DEVICE_ID, 'operator' => 'equals', 'value' => $device->getId()],
                ['field' => HumanResources_Model_AttendanceRecord::FLD_BLPROCESSED, 'operator' => 'equals', 'value' => false],
            ], $accountId ? [
                ['field' => HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID, 'operator' => 'equals', 'value' => $accountId],
            ] : [])), new Tinebase_Model_Pagination(['sort' => [HumanResources_Model_AttendanceRecord::FLD_ACCOUNT_ID, HumanResources_Model_AttendanceRecord::FLD_SEQUENCE], 'dir' => 'ASC']));

            unset($selectForUpdate);

            if (0 === $data->count()) {
                continue;
            }
            $data = new HumanResources_BL_AttendanceRecorder_Data($data);

            $bl = new Tinebase_BL_Pipe($device->{HumanResources_Model_AttendanceRecorderDevice::FLD_BLPIPE});
            $bl->execute($data);

            /** @var HumanResources_Model_AttendanceRecord $record */
            foreach ($data->data as $record) {
                if ($record->isDirty()) {
                    $recordCtrl->update($record);
                }
            }
        }

        $transaction->release();
    }
}
