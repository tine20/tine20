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

class Tinebase_Model_Converter_Date extends Tinebase_Model_Converter_DateTime
{
    /**
     * @param $fieldValue
     * @return string
     */
    public function convertToData($record, $key, $fieldValue)
    {
        if ($fieldValue instanceof DateTime) {
            return $fieldValue->format('Y-m-d');
        }
        return $fieldValue;
    }
}