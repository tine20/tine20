<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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

class Tinebase_Model_Converter_DateTime implements Tinebase_Model_Converter_Interface
{
    /**
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        if ($blob instanceof DateTime) {
            return $blob;
        }
        return (int)$blob === 0 ? null : new Tinebase_DateTime($blob);
    }

    /**
     * @param $fieldValue
     * @return string
     */
    public function convertToData($record, $key, $fieldValue)
    {
        if ($fieldValue instanceof DateTime) {
            return $fieldValue->format(Tinebase_Record_Abstract::ISO8601LONG);
        }
        return $fieldValue;
    }
}