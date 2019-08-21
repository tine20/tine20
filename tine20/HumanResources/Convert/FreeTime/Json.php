<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     HumanResources
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     HumanResources
 * @subpackage  Convert
 */
class HumanResources_Convert_FreeTime_Json extends Tinebase_Convert_Json
{
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);

        if (!$multiple) return;

        $expander = new Tinebase_Record_Expander(HumanResources_Model_FreeTime::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES       => [
                'freedays'  => [],
            ],
        ]);

        $expander->expand($records);
    }
}
