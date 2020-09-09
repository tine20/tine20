<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_Record_Expander_JsonStorageProperty extends Tinebase_Record_Expander_Property
{
    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        foreach ($_records as $record) {
            $data = $record->{$this->_property};
            if ($data instanceof Tinebase_Record_RecordSet && $data->count() > 0) {
                // TODO we should delay this expanding until the current run of \Tinebase_Record_Expander::_fetchData finished!
                // ?? really, this too? ... probably not
                $this->expand($data);
            }
        }
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' must never be called');
    }
}