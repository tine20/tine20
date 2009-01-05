<?php
/**
 * Timetracker csv generation class
 *
 * @package     Timetracker
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * Timetracker csv generation class
 * 
 * @package     Timetracker
 * @subpackage	Export
 * 
 */
class Timetracker_Export_Csv extends Tinebase_Export_Csv
{
    /**
     * export timesheets to csv file
     *
     * @param Timetracker_Model_TimesheetFilter $_filter
     * @return string filename
     * 
     * @todo resolve timeaccount and account
     * @todo add specific export values
     * @todo add ods (open office spreadsheet) creation
     * @todo save in special download path
     * @todo perhaps we can make this more generic (move it to Tinebase_Export_Csv)
     */
    public function exportTimesheets($_filter) {
        
        $filename = date('Y-m-d') . '_timesheet_export.csv';
        
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($_filter);
        if (count($timesheets) < 1) {
            throw new Timetracker_Exception_NotFound('No Timesheets found.');
        }
        
        // to ensure the order of fields we need to sort it ourself!
        $fields = array();
        $skipFields = array(
        );
        foreach ($timesheets[0] as $fieldName => $value) {
            if (! in_array($fieldName, $skipFields)) {
                $fields[] = $fieldName;
            }
        }
        
        $filehandle = fopen($filename, 'w');
        
        self::fputcsv($filehandle, $fields);
        
        // fill file with records
        foreach ($timesheets as $timesheet) {
            $timesheetArray = array();
            foreach ($fields as $fieldName) {
                $timesheetArray[] = $timesheet->$fieldName;
            }
            self::fputcsv($filehandle, $timesheetArray);
        }
        
        fclose($filehandle);
        
        return $filename;
    }
}
