<?php
/**
 * Stream controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use HumanResources_Model_StreamModality as StreamModality;
use HumanResources_Model_StreamModalReport as Report;

/**
 * Stream controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_Stream extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected $recreateReports = false;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     *
     * @throws Tinebase_Exception_Backend
     */
    protected function __construct()
    {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_modelName = HumanResources_Model_Stream::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME      => $this->_modelName,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME      => HumanResources_Model_Stream::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE   => true,
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
        $this->_handleVirtualRelationProperties = true;
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @param bool $_getRelatedData
     * @param bool $_getDeleted
     * @return HumanResources_Model_Stream
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function get($_id, $_containerId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE)
    {
        /** @var HumanResources_Model_Stream $record */
        $record = parent::get($_id, $_containerId, $_getRelatedData, $_getDeleted);

        if ($_getRelatedData) {
            $expander = new Tinebase_Record_Expander(HumanResources_Model_Stream::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    HumanResources_Model_Stream::FLD_STREAM_MODALITIES  => [],
                    HumanResources_Model_Stream::FLD_RESPONSIBLES       => [],
                    HumanResources_Model_Stream::FLD_TIME_ACCOUNTS      => [],
                ],
            ]);

            $expander->expand(new Tinebase_Record_RecordSet(HumanResources_Model_Stream::class, [$record]));
        }

        return $record;
    }

    /**
     * @param HumanResources_Model_Stream $_record
     * @noinspection PhpDocSignatureInspection
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        $this->_inspectRecord($_record);
        $this->recreateReports = false;
    }

    /**
     * @param HumanResources_Model_Stream $_record
     * @param HumanResources_Model_Stream $_oldRecord
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        $this->_inspectRecord($_record);
        $this->recreateReports = false;
    }

    protected function _inspectRecord(HumanResources_Model_Stream $stream)
    {
        if (null !== $stream->{HumanResources_Model_Stream::FLD_STREAM_MODALITIES}) {
            if (is_array($stream->{HumanResources_Model_Stream::FLD_STREAM_MODALITIES})) {
                $rs = new Tinebase_Record_RecordSet(StreamModality::class);
                foreach ($stream->{HumanResources_Model_Stream::FLD_STREAM_MODALITIES} as $streamModalityArr) {
                    $streamModality = new StreamModality([], true);
                    $streamModality->setFromJsonInUsersTimezone($streamModalityArr);
                    $rs->addRecord($streamModality);
                }
                $stream->{HumanResources_Model_Stream::FLD_STREAM_MODALITIES} = $rs;
            }

            $stream->{HumanResources_Model_Stream::FLD_STREAM_MODALITIES}->sort(function($a, $b) {
                return $b->{StreamModality::FLD_START}->isEarlier($a->{StreamModality::FLD_START});
            });
            $prev = null;
            foreach ($stream->{HumanResources_Model_Stream::FLD_STREAM_MODALITIES} as $streamModality) {
                if (null !== $prev) {
                    if (!$prev->{StreamModality::FLD_END}->isEarlier($streamModality
                            ->{StreamModality::FLD_START})) {
                        throw new Tinebase_Exception_UnexpectedValue('modalities may not overlap');
                    }
                    $prevEnd = $prev->{StreamModality::FLD_TRACKING_END} ?: $prev->{StreamModality::FLD_END};
                    $start = $streamModality->{StreamModality::FLD_TRACKING_START} ?: $streamModality
                        ->{StreamModality::FLD_START};
                    if (!$prevEnd->isEarlier($start)) {
                        throw new Tinebase_Exception_UnexpectedValue('modalities may not overlap');
                    }
                    if ($streamModality->{StreamModality::FLD_CLOSED} && !$prev->{StreamModality::FLD_CLOSED}) {
                        throw new Tinebase_Exception_UnexpectedValue(
                            'modalities need to be closed consecutive starting with the first one');
                    }
                }
                $prev = $streamModality;
            }
        }
    }

    public function doRecreateReports()
    {
        $this->recreateReports = true;
    }

    /**
     * inspect update of one record (after setReleatedData)
     *
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord)
    {
        if ($this->recreateReports) {
            $this->createReports($updatedRecord);
            $this->recreateReports = false;
        }
    }

    public function createReports(HumanResources_Model_Stream $stream)
    {
        $timeSheetCtr = Timetracker_Controller_Timesheet::getInstance();
        $prevReport = null;

        /** @var StreamModality $streamModality */
        foreach ($stream->{HumanResources_Model_Stream::FLD_STREAM_MODALITIES} as $streamModality) {
            if ($streamModality->{StreamModality::FLD_CLOSED}) {
                $prevReport = $streamModality->{StreamModality::FLD_REPORTS}->lastRecord();
                continue;
            }

            $start = $streamModality->{StreamModality::FLD_TRACKING_START} ?: $streamModality->{StreamModality::FLD_START};
            $end = $streamModality->{StreamModality::FLD_TRACKING_END} ?: $streamModality->{StreamModality::FLD_END};

            $streamModality->generateMissingReportsEmpty();
            $isDirty = false;
            /** @var Report $report */
            foreach ($streamModality->{StreamModality::FLD_REPORTS} as $report) {
                if (!$this->recreateReports && $report->{Report::FLD_CLOSED}) {
                    $prevReport = $report;
                    continue;
                }

                if ($end->isEarlier($report->{Report::FLD_START}) || $start->isLater($report->{Report::FLD_END})) {
                    $ts = [];
                } else {
                    $ts = $timeSheetCtr->search(
                        Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                            ['field' => 'timeaccount_id', 'operator' => 'in', 'value' => $stream->{HumanResources_Model_Stream::FLD_TIME_ACCOUNTS}->getArrayOfIds()],
                            ['field' => 'is_billable', 'operator' => 'equals', 'value' => true],
                            ['field' => 'start_date', 'operator' => 'after_or_equals', 'value' =>
                                $report->{Report::FLD_INTERVAL} == 0 ? $start :
                                    ($report->{Report::FLD_START}->isEarlier($start) ? $start : $report->{Report::FLD_START})],
                            ['field' => 'start_date', 'operator' => 'before_or_equals', 'value' => $report->{Report::FLD_END}],
                        ]),
                        null, false, [Tinebase_Backend_Sql_Abstract::IDCOL, 'duration']
                    );
                }

                $report->{Report::FLD_TIMESHEETS} = array_keys($ts);
                $report->{Report::FLD_IS} = array_sum($ts);
                $report->{Report::FLD_OVERFLOW_IN} = $prevReport ? $prevReport->{Report::FLD_OVERFLOW_OUT} : 0;
                $report->{Report::FLD_OVERFLOW_OUT} = ($report->{Report::FLD_IS} + $report->{Report::FLD_OVERFLOW_IN}) -
                    $streamModality->{StreamModality::FLD_HOURS_INTERVAL};

                $prevReport = $report;
                $isDirty = true;
            }

            if ($isDirty) {
                HumanResources_Controller_StreamModality::getInstance()->update($streamModality);
            }
        }
    }
}
