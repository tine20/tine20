<?php
/**
 * Timetracker Ods generation class
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
 * Timetracker Ods generation class
 * 
 * @package     Timetracker
 * @subpackage	Export
 * 
 */
class Timetracker_Export_Ods extends Tinebase_Export_Ods
{
    /**
     * export timesheets to Ods file
     *
     * @param Timetracker_Model_TimesheetFilter $_filter
     * @return string filename
     * 
     * @todo add specific export values
     * @todo add fields array to preferences
     */
    public function exportTimesheets($_filter) {
        
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
        
        // build export array
        list($fields, $headline) = $this->_getExportFields();
        
        $exportArray = array();
        foreach ($timesheets as $timesheet) {
            $row = array();
            foreach ($fields as $key => $params) {
                switch($params['type']) {
                    case 'timeaccount':
                        $value = $timeaccounts[$timeaccounts->getIndexById($timesheet->timeaccount_id)]->$params['field'];
                        $row[] = $value;
                        break;
                    case 'account':
                        $value = $accounts[$accounts->getIndexById($timesheet->account_id)]->$params['field'];
                        $row[] = $value;
                        break;
                    default:
                        $row[] = preg_replace('/"/', "'", $timesheet->$key);
                }
            }
            $exportArray[] = $row;
            
            //$timesheet->timeaccount_id = $timeaccounts[$timeaccounts->getIndexById($timesheet->timeaccount_id)]->title;
            //$timesheet->account_id = $accounts[$accounts->getIndexById($timesheet->account_id)]->accountDisplayName;
        }
        
        //$filename = parent::exportRecords($timesheets);
        $filename = parent::exportArray($exportArray, $headline);
        
        return $filename;
    }
    
    /**
     * get export fields
     * - record fieldname => headline (translated)
     *
     * @return array
     */
    protected function _getExportFields()
    {
        $translate = Tinebase_Translation::getTranslation('Timetracker');
        $fields = array(
            'start_date' => array('type' => 'default'),
            'description' => array('type' => 'default'),
            'timeaccount_id' => array('type' => 'timeaccount', 'field' => 'title'),
            'account_id' => array('type' => 'account', 'field' => 'accountDisplayName'),
            'duration' => array('type' => 'default'),
        );
        $headline = array(
            $translate->_('Date'),
            $translate->_('Description'),
            $translate->_('Site'),
            $translate->_('Staff Member'),
            $translate->_('Duration'),
        );
        
        return array($fields, $headline);
    }
}
