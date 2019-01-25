<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_ModelConfiguration_Const as MCC;

/**
 * Tinebase_Model_Converter_JsonRecordSet
 *
 * Json RecordSet Converter
 *
 * @package     Tinebase
 * @subpackage  Converter
 */

class Tinebase_Model_Converter_JsonRecordSet implements Tinebase_Model_Converter_Interface
{
    /**
     * @param Tinebase_Record_Interface $record
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        if ($blob instanceof Tinebase_Record_RecordSet) {
            return $blob;
        }

        if (is_string($blob)) {
            $data = json_decode($blob, true);
        }
        if (is_array($data)) {
            $rs = new Tinebase_Record_RecordSet($record::getConfiguration()
                ->recordsFields[$key][MCC::CONFIG][MCC::RECORD_CLASS_NAME], $data);
            $rs->runConvertToRecord();
            return $rs;
        }
        return null;
    }

    /**
     * @param $fieldValue
     * @return string
     */
    public function convertToData($record, $key, $fieldValue)
    {
        if (! $fieldValue instanceof Tinebase_Record_RecordSet) {
            if (empty($fieldValue)) {
                return null;
            } else {
                return $fieldValue;
            }
        }
        $fieldValue->runConvertToData();
        return json_encode($fieldValue->toArray());
    }
}