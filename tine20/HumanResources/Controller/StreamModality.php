<?php
/**
 * StreamModality controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use HumanResources_Model_StreamModality as StreamModality;

/**
 * StreamModality controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_StreamModality extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_modelName = StreamModality::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME      => $this->_modelName,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME      => StreamModality::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE   => true,
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
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
        /** @var StreamModality $record */
        $record = parent::get($_id, $_containerId, $_getRelatedData, $_getDeleted);

        if ($_getRelatedData) {
            $expander = new Tinebase_Record_Expander(StreamModality::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    StreamModality::FLD_REPORTS  => [],
                ],
            ]);

            $expander->expand(new Tinebase_Record_RecordSet(StreamModality::class, [$record]));
        }

        return $record;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        $this->_inspectRecord($_record);
    }

    protected function _inspectRecord(StreamModality $modality)
    {
        if ($modality->{StreamModality::FLD_TRACKING_START} && ! $modality->{StreamModality::FLD_TRACKING_START}
                ->isEarlierOrEquals($modality->{StreamModality::FLD_END})) {
            throw new Tinebase_Exception_UnexpectedValue(StreamModality::FLD_TRACKING_START .
                ' needs to earlier or equal ' . StreamModality::FLD_END);
        }
        if ($modality->{StreamModality::FLD_TRACKING_START} && $modality->{StreamModality::FLD_TRACKING_END} &&
                ! $modality->{StreamModality::FLD_TRACKING_START}->isEarlierOrEquals($modality
                    ->{StreamModality::FLD_TRACKING_END})) {
            throw new Tinebase_Exception_UnexpectedValue(StreamModality::FLD_TRACKING_START .
                ' needs to earlier or equal ' . StreamModality::FLD_TRACKING_END);
        }
        if ($modality->{StreamModality::FLD_TRACKING_END} && ! $modality->{StreamModality::FLD_TRACKING_END}
                ->isAfterOrEquals($modality->{StreamModality::FLD_START})) {
            throw new Tinebase_Exception_UnexpectedValue(StreamModality::FLD_TRACKING_END . ' needs to after or equal '
                . StreamModality::FLD_START);
        }
    }

    /**
     * @param StreamModality $_record
     * @param StreamModality $_oldRecord
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        $this->_inspectRecord($_record);

        if ($_oldRecord->{StreamModality::FLD_REPORTS}->count() > 0) {
            foreach ([
                        StreamModality::FLD_START,
                        StreamModality::FLD_HOURS_INTERVAL,
                        StreamModality::FLD_INTERVAL,
                        StreamModality::FLD_TRACKING_START,
                        StreamModality::FLD_TRACKING_END,
                    ] as $prop) {
                if ($_record->{$prop} != $_oldRecord->{$prop}) {
                    if ($_record->{StreamModality::FLD_CLOSED}) {
                        throw new Tinebase_Exception_UnexpectedValue('stream modality is closed, you can\'t change it');
                    }
                    HumanResources_Controller_Stream::getInstance()->doRecreateReports();
                    break;
                }
            }
        }
    }
}
