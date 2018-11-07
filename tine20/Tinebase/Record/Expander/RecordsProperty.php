<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
/*
class Tinebase_Record_Expander_RecordsProperty extends Tinebase_Record_Expander_Property
{
    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        $this->_recordsToProcess = $_records;
        // ??? TODO think about this? getIdFromProperty? we never know which state the records are in?
        // TODO if there is already an object, we don't want it from the database again!
        // FIXME guess we have to add an optional parameter to getIdFromProperty
        $ids = array_filter($_records->getIdFromProperty($this->_property));
        if (!empty($ids)) {
            $self = $this;
            $this->_rootExpander->_registerDataToFetch(new Tinebase_Record_Expander_DataRequest(
                self::DATA_FETCH_PRIO_USER, Tinebase_Core::getApplicationInstance($this->_model), $ids,
                // workaround: [$this, '_setData'] doesn't work, even so it should!
                function($_data) use($self) {$self->_setData($_data);}));
        }
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        $expandData = new Tinebase_Record_RecordSet($_data->getRecordClassName());

        /** @var Tinebase_Record_Abstract $record *
        foreach ($this->_recordsToProcess as $record) {
            // ??? TODO think about this? getIdFromProperty? we never know which state the records are in?
            // TODO if there is already an object, we don't want it from the database again!
            // FIXME guess we have to add an optional parameter to getIdFromProperty
            if (null !== ($id = $record->getIdFromProperty($this->_property)) && false !== ($subRecord =
                    $_data->getById($id))) {
                $record->{$this->_property} = $subRecord;
                $expandData->addRecord($subRecord);
            }
        }
        // clean up
        $this->_recordsToProcess = null;

        $this->expand($expandData);
    }
}*/