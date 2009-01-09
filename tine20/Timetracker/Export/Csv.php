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
     * @todo add specific export values
     * @todo add ods (open office spreadsheet) creation
     * @todo save in special download path
     * @todo perhaps we can make this more generic (move it to Tinebase_Export_Csv)
     * @todo save skipped fields elsewhere (preferences?)
     */
    public function exportTimesheets($_filter) {
        
        $filename = '/tmp/' . date('Y-m-d') . '_timesheet_export_' . time() . '.csv';
        
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($_filter);
        if (count($timesheets) < 1) {
            throw new Timetracker_Exception_NotFound('No Timesheets found.');
        }

        // resolve timeaccounts
        $timeaccountIds = $timesheets->timeaccount_id;
        $timeaccounts = Timetracker_Controller_Timeaccount::getInstance()->getMultiple(array_unique(array_values($timeaccountIds)));
        
        // resolve accounts
        $accountIds = $timesheets->account_id;
        $accounts = Tinebase_User::getInstance()->getMultiple(array_unique(array_values($accountIds)));
                
        // to ensure the order of fields we need to sort it ourself!
        $fields = array();
        $skipFields = array(
            'id'                    ,
            'created_by'            ,
            'creation_time'         ,
            'last_modified_by'      ,
            'last_modified_time'    ,
            'is_deleted'            ,
            'deleted_time'          ,
            'deleted_by'            ,
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
            $timesheet->timeaccount_id = $timeaccounts[$timeaccounts->getIndexById($timesheet->timeaccount_id)]->title;
            $timesheet->account_id = $accounts[$accounts->getIndexById($timesheet->account_id)]->accountDisplayName;
            
            $timesheetArray = array();
            foreach ($fields as $fieldName) {
                $timesheetArray[] = '"' . $timesheet->$fieldName . '"';
            }
            self::fputcsv($filehandle, $timesheetArray);
        }
        
        fclose($filehandle);
        
        return $filename;
    }
}
