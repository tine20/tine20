<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     Timetracker
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Timetracker
 * @subpackage  Convert
 */
class Timetracker_Convert_Timeaccount_Json extends Tinebase_Convert_Json
{
    /**
     * resolve multiple record fields (Tinebase_ModelConfiguration._recordsFields)
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param Tinebase_ModelConfiguration $modelConfiguration
     */
    protected function _resolveMultipleRecordFields(Tinebase_Record_RecordSet $_records, $modelConfiguration = NULL)
    {
        // grants cannnot be resolved the default way, other records fields must not be resolved
    }
}
