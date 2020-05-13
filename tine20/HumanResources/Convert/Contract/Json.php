<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     HumanResources
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     HumanResources
 * @subpackage  Convert
 */
class HumanResources_Convert_Contract_Json extends Tinebase_Convert_Json
{
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);

        $expanderDef = [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                HumanResources_Model_Contract::FLDS_WORKING_TIME_SCHEME => [
                    Tinebase_Record_Expander::GET_DELETED => true,
                ],
            ],
        ];
        $expander = new Tinebase_Record_Expander(HumanResources_Model_Contract::class, $expanderDef);
        $expander->expand($records);
    }
}