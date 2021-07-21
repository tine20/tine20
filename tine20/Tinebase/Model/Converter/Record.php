<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_Model_Converter_Record
 *
 * Json DynamicRecord Converter
 *
 * @package     Tinebase
 * @subpackage  Converter
 */

class Tinebase_Model_Converter_Record implements Tinebase_Model_Converter_Interface
{
    /**
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        return $blob;
    }

    /**
     * @param $fieldValue
     * @return mixed
     */
    public function convertToData($record, $key, $fieldValue)
    {
        if ($fieldValue instanceof Tinebase_Record_Interface) {
            return $fieldValue->getId();
        } elseif (is_array($fieldValue)) {
            return isset($fieldValue['id']) ? $fieldValue['id'] : null;
        } elseif ('' === $fieldValue) {
            return null;
        }
        return $fieldValue;
    }
}
