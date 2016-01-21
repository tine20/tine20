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
 * Tinebase_Model_Converter_Interface
 *
 * Converter Interface
 *
 * @package     Tinebase
 * @subpackage  Converter
 */


interface Tinebase_Model_Converter_Interface
{
    static function convertToRecord($blob);

    static function convertToData($fieldValue);
}