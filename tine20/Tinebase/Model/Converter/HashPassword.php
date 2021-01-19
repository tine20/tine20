<?php declare(strict_types=1);
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
 * Tinebase_Model_Converter_HashPassword
 *
 * Json Converter
 *
 * @package     Tinebase
 * @subpackage  Converter
 */

class Tinebase_Model_Converter_HashPassword implements Tinebase_Model_Converter_Interface
{
    public function convertToData($record, $key, $fieldValue)
    {
        if (!empty($fieldValue)) {
            $record->{$record::getConfiguration()->_fields[$key][Tinebase_ModelConfiguration::REF_MODEL_FIELD]} =
                Hash_Password::generate('SSHA256', $fieldValue);
        }
        return '';
    }

    function convertToRecord($record, $fieldName, $blob)
    {
        return $blob;
    }
}
