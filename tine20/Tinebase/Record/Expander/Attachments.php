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

class Tinebase_Record_Expander_Attachments extends Tinebase_Record_Expander_Property
{
    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        try {
            $data = Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($_records);
            // TODO we should delay this expanding until the current run of \Tinebase_Record_Expander::_fetchData finished!
            $this->expand($data);
        } catch (Tinebase_Exception_Backend $teb) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not expand attachments. Exception: ' . $teb);
        }
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        throw new Tinebase_Exception_NotImplemented('do not call this method on ' . self::class);
    }
}
