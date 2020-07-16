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
class HumanResources_Convert_Stream_Json extends Tinebase_Convert_Json
{
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        if ($multiple) return;

        $expander = new Tinebase_Record_Expander(HumanResources_Model_Stream::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES       => [
                HumanResources_Model_Stream::FLD_STREAM_MODALITIES  => [
                    Tinebase_Record_Expander::PROPERTY_CLASS_USER       => [],
                ],
                HumanResources_Model_Stream::FLD_RESPONSIBLES       => [],
                HumanResources_Model_Stream::FLD_TIME_ACCOUNTS      => [],
            ],
            Tinebase_Record_Expander::PROPERTY_CLASS_USER       => [],
        ]);

        $expander->expand($records);
    }

    /**
     * resolves child records after converting the record set to an array
     *
     * @param array $result
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     *
     * @return array
     */
    protected function _resolveAfterToArray($result, $modelConfiguration, $multiple = false)
    {
        return $result;
    }
}
