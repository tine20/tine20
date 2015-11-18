<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
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

    static public function convertToRecord($blob)
    {
        return Zend_Json::decode($blob);
    }

    static public function convertToData($fieldValue)
    {
        if (is_null($fieldValue)) {
            return $fieldValue;
        }
        return Zend_Json::encode($fieldValue);
    }
}