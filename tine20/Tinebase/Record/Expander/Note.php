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

class Tinebase_Record_Expander_Note extends Tinebase_Record_Expander_Property
{
    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        if (null !== ($data = Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($_records, $this->_property))) {
            // TODO we should delay this expanding until the current run of \Tinebase_Record_Expander::_fetchData finished!
            $this->expand($data);
        }
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        throw new Tinebase_Exception_NotImplemented('do not call this method on ' . self::class);
    }
}