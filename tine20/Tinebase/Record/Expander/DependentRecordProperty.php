<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

class Tinebase_Record_Expander_DependentRecordProperty extends Tinebase_Record_Expander_Property
{
    protected $_fieldDef = null;

    public function __construct($_fieldDef, $_model, $_property, $_expanderDefinition, $_rootExpander,
                                $_prio = self::DATA_FETCH_PRIO_DEPENDENTRECORD)
    {
        $this->_fieldDef = $_fieldDef;
        parent::__construct($_model, $_property, $_expanderDefinition, $_rootExpander, $_prio);
    }

    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        $this->_addRecordsToProcess($_records);
        $ids = $_records->getArrayOfIds();
        if (!empty($ids)) {
            $self = $this;
            $this->_rootExpander->_registerDataToFetch(new Tinebase_Record_Expander_DataRequest_FilterByProperty(
                $this->_prio, Tinebase_Core::getApplicationInstance($this->_fieldDef[TMCC::CONFIG][TMCC::RECORD_CLASS_NAME], '', true),
                $this->_fieldDef[TMCC::CONFIG][TMCC::REF_ID_FIELD], $ids,
                // workaround: [$this, '_setData'] doesn't work, even so it should!
                function($_data) use($self) {$self->_setData($_data);}, $this->_getDeleted));
        }
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        $expandData = new Tinebase_Record_RecordSet($_data->getRecordClassName());

        /** @var Tinebase_Record_Abstract $record */
        foreach ($this->_recordsToProcess as $record) {
            if ($record->{$this->_property} =
                    $_data->find($this->_fieldDef[TMCC::CONFIG][TMCC::REF_ID_FIELD], $record->getId())) {
                $expandData->addRecord($record->{$this->_property});
            }
        }

        // TODO we should delay this expanding until the current run of \Tinebase_Record_Expander::_fetchData finished!
        $this->expand($expandData);
    }
}
