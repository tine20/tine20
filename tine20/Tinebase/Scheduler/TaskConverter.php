<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Scheduler Task Converter - converts the configuration property to/from Tinebase_Scheduler_Task
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 */

class Tinebase_Scheduler_TaskConverter implements Tinebase_Model_Converter_Interface
{

    /**
     * @param string $blob
     * @return Tinebase_Scheduler_Task
     */
    static function convertToRecord($blob)
    {
        $data = json_decode($blob, true);
        return new Tinebase_Scheduler_Task($data);
    }

    /**
     * @param Tinebase_Scheduler_Task $fieldValue
     * @return string
     */
    static function convertToData($fieldValue)
    {
        return json_encode($fieldValue->toArray());
    }
}