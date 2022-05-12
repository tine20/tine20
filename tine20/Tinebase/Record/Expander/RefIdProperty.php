<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as MCC;

class Tinebase_Record_Expander_RefIdProperty extends Tinebase_Record_Expander_Property
{
    protected $_mccCfg = null;
    protected $_singleRecord = false;

    public function __construct($_model, $_property, $_expanderDefinition, $_rootExpander,
                                $_prio = self::DATA_FETCH_PRIO_DEPENDENTRECORD, $_singleRecord = false)
    {
        $this->_mccCfg = $_expanderDefinition['fieldDefConfig'];
        $this->_singleRecord = $_singleRecord;

        parent::__construct($_model, $_property, $_expanderDefinition, $_rootExpander, $_prio);
    }

    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        $this->_addRecordsToProcess($_records);
        $ids = $_records->getArrayOfIds();
        if (!empty($ids)) {
            $self = $this; // we should do weak refs here to avoid circular references -> memory leak. ... this is one!
            $this->_rootExpander->_registerDataToFetch((new Tinebase_Record_Expander_DataRequest_FilterByProperty(
                $this->_prio, Tinebase_Core::getApplicationInstance($this->_mccCfg[MCC::RECORD_CLASS_NAME], '', true),
                $this->_mccCfg[MCC::REF_ID_FIELD], $ids,
                // workaround: [$this, '_setData'] doesn't work, even so it should!
                function($_data) use($self) {$self->_setData($_data);}, $this->_getDeleted))
                ->setAdditionalFilter(isset($this->_mccCfg[MCC::ADD_FILTERS]) ? $this->_mccCfg[MCC::ADD_FILTERS] : null)
                ->setPaging(isset($this->_mccCfg[MCC::PAGING]) ? new Tinebase_Model_Pagination($this->_mccCfg[MCC::PAGING]) : null)
                ->setFilterOptions(isset($this->_mccCfg[MCC::FILTER_OPTIONS]) ? $this->_mccCfg[MCC::FILTER_OPTIONS] : null)
            );
        }
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
       $expandData = new Tinebase_Record_RecordSet($_data->getRecordClassName());

        /** @var Tinebase_Record_Abstract $record */
        foreach ($this->_recordsToProcess as $record) {
            if (($record->{$this->_property} =
                    $_data->filter($this->_mccCfg[MCC::REF_ID_FIELD], $record->getId()))->count() > 0) {
                $expandData->mergeById($record->{$this->_property});
            }
            if ($this->_singleRecord) {
                $record->{$this->_property} = $record->{$this->_property}->getFirstRecord();
            }
        }

        // TODO we should delay this expanding until the current run of \Tinebase_Record_Expander::_fetchData finished!
        $this->expand($expandData);
    }
}
