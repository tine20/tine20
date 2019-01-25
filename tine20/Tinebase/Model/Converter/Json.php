<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_Model_Converter_Json
 *
 * Json Converter
 *
 * @package     Tinebase
 * @subpackage  Converter
 */

class Tinebase_Model_Converter_Json implements Tinebase_Model_Converter_Interface
{
    /**
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        return json_decode($blob, true);
    }

    /**
     * @param $fieldValue
     * @return string
     */
    public function convertToData($record, $key, $fieldValue)
    {
        if (is_null($fieldValue)) {
            return $fieldValue;
        }
        return json_encode($fieldValue);
    }
}