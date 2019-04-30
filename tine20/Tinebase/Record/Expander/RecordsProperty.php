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

class Tinebase_Record_Expander_RecordsProperty extends Tinebase_Record_Expander_Property
{
    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        $ids = [];
        $this->_recordsToProcess = $_records;
        foreach ($_records->{$this->_property} as $data) {
            if (is_array($data)) {
                $ids = array_merge($ids, $data);
            }
        }
        if (!empty($ids) || !empty($this->_subExpanders)) {
            $ids = array_unique($ids);
            $self = $this;
            $this->_rootExpander->_registerDataToFetch(new Tinebase_Record_Expander_DataRequest(
                $this->_prio, Tinebase_Core::getApplicationInstance($this->_model, '', true), $ids,
                // workaround: [$this, '_setData'] doesn't work, even so it should!
                function ($_data) use ($self) {
                    $self->_setData($_data);
                }));
        }
    }

    /** this will not clone the records.... they are the same instance in different parents! */
    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        /** @var Tinebase_Record_Interface $record */
        foreach ($this->_recordsToProcess as $record) {
            $data = $record->{$this->_property};
            if (!is_array($data)) {
                if ($data instanceof Tinebase_Record_RecordSet) {
                    $_data->mergeById($data);
                }
                continue;
            }
            $result = new Tinebase_Record_RecordSet([], $this->_model);
            foreach ($data as $id) {
                if (null !== ($r = $_data->getById($id))) {
                    $result->addRecord($r);
                }
            }
            $record->{$this->_property} = $result;
        }

        $this->expand($_data);
    }
}