<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_Model_Converter_StringFakeNull
 *
 * StringFakeNull Converter
 *
 * @package     Tinebase
 * @subpackage  Converter
 */

class Tinebase_Model_Converter_StringFakeNull implements Tinebase_Model_Converter_RunOnNullInterface
{
    /**
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        return $blob === '' ? null : $blob;
    }

    /**
     * @param $fieldValue
     * @return string
     */
    public function convertToData($record, $key, $fieldValue)
    {
        if (null === $fieldValue) {
            return '';
        }
        return $fieldValue;
    }
}
