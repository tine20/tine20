<?php
/**
 * class to hold calculate end in hydrateFromBackend
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold calculate end in hydrateFromBackend
 *
 * @package     HumanResources
 * @subpackage  Model
 */
use HumanResources_Model_StreamModality as StreamModality;

class HumanResources_Model_Converter_VirtualCalcPropEnd implements Tinebase_Model_Converter_Interface
{
    /**
     * @param StreamModality $record
     * @param string $fieldName
     * @param mixed $blob
     * @return mixed
     */
    function convertToRecord($record, $fieldName, $blob)
    {
        if ($record->{StreamModality::FLD_NUM_INTERVAL} && $record->{StreamModality::FLD_INTERVAL} && ($start = $record
                ->{StreamModality::FLD_START})) {
            $record->{StreamModality::FLD_START} = $start;
        }

        return $blob;
    }

    function convertToData($record, $fieldName, $fieldValue)
    {
        return $fieldValue;
    }
}
