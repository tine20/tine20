<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Record (De)Hydrator Factory
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Hydration_Factory
{
    const TYPE_JSON = 'json';
    const TYPE_ARRAY = 'array';
    //const TYPE_XML = 'xml';
    //const TYPE_DB = 'db';
    //const TYPE_YAML = 'yaml';

    /**
     * @param string $_type
     * @param string $_model
     * @param string|null $_strategyDefinition
     * @return Tinebase_Record_Dehydrator_Abstract
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function createDehydrator($_type, $_model, $_strategyDefinition = null)
    {
        if (null !== $_strategyDefinition) {
            $_strategyDefinition = new Tinebase_Record_Dehydrator_Strategy($_type, $_strategyDefinition);
        }
        switch ($_type) {
            case self::TYPE_JSON:
                return new Tinebase_Record_Dehydrator_Json($_model, $_strategyDefinition);
            case self::TYPE_ARRAY:
                return new Tinebase_Record_Dehydrator_Array($_model, $_strategyDefinition);
            default:
                throw new Tinebase_Exception_InvalidArgument('type unknown: ' . print_r($_type, true));
        }
    }
}