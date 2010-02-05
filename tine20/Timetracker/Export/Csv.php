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
     */
    public function generate(Timetracker_Model_TimesheetFilter $_filter) {
        
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($_filter, new Tinebase_Model_Pagination(array(
            'sort'  => 'start_date',
            'dir'   => 'DESC'
        )), FALSE, FALSE, 'export');
        if (count($timesheets) < 1) {
            throw new Timetracker_Exception_NotFound('No Timesheets found.');
        }

        // resolve timeaccounts
        $timeaccountIds = $timesheets->timeaccount_id;
        $timeaccounts = Timetracker_Controller_Timeaccount::getInstance()->getMultiple(array_unique(array_values($timeaccountIds)));
        
        Tinebase_User::getInstance()->resolveMultipleUsers($timesheets, 'account_id', true);
        
        foreach ($timesheets as $timesheet) {
            $timesheet->timeaccount_id = $timeaccounts[$timeaccounts->getIndexById($timesheet->timeaccount_id)]->title;
            $timesheet->account_id = $timesheet->account_id->accountDisplayName;
        }
                
        $filename = parent::exportRecords($timesheets);
        
        return $filename;
    }
}
