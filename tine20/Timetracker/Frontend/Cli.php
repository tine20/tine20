<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * cli server for timetracker
 *
 * This class handles cli requests for the timetracker
 *
 * @package     Timetracker
 * @subpackage  Frontend
 */
class Timetracker_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Timetracker';
    
    /**
     * help array with function names and param descriptions
     * 
     * @return void
     */
    protected $_help = array(
        'allBillable' => array(
            'description'   => 'give manage_billable to all users of all Timeaccounts',
            'params'        => array(
                //'filenames'   => 'Filename(s) of import file(s) [required]',
                //'format'     => 'Import file format (default: csv) [optional]',
                //'config'     => 'Mapping config file (default: importconfig.inc.php) [optional]',
            )
        ),
    );
    
    /**
     * add manage billable to all users of all timeaccounts
     * 
     * @return void
     */
    public function allBillable()
    {
        $containerController = Tinebase_Container::getInstance();
        
        $allTAs = Timetracker_Controller_Timeaccount::getInstance()->search(new Timetracker_Model_TimeaccountFilter(array(
            array('field' => 'query', 'operator' => 'contains', 'value' => '')
        )));
        foreach ($allTAs->container_id as $container_id) {
            $allGrants = $containerController->getGrantsOfContainer($container_id, true);
            
            foreach ($allGrants as $grants) {
                // set manage billable grant;
                $grants->{Tinebase_Model_Grants::GRANT_DELETE} = TRUE;
            }
            $containerController->setGrants($container_id, $allGrants, true);
            echo '.';
            
        }
        echo "done.\n";
    }

   /**
     * search and show duplicate timeaccounts
     * 
     * @return void
     */
    public function searchDuplicateTimeaccounts()
    {
        $filter = new Timetracker_Model_TimeaccountFilter(array(array(
            'field' => 'is_open', 
            'operator' => 'equals', 
            'value' => TRUE
        )));
        
        $duplicates = parent::_searchDuplicates(Timetracker_Controller_Timeaccount::getInstance(), $filter, 'title');
        
        echo 'Found ' . (count($duplicates) / 2) . ' Timeaccount duplicate(s):' . "\n";
        
        print_r($duplicates);
    }
    
    /**
     * transfers timesheets from one timeaccount (need id in params) to another
     * - params: timeaccountId=xxx, dryrun=0|1
     * 
     * @param Zend_Console_Getopt $_opts
     * @return boolean
     * 
     * @todo allow to configure mapping elsewhere
     */
    public function transferTimesheetsToDifferentTimeaccounts(Zend_Console_Getopt $_opts)
    {
        $params = $this->_parseArgs($_opts, array('timeaccountId'));
        
        // transfer timeaccounts depending on user group membership
        $mappingGroupToTimeaccount = array(
        // TODO add groupId => timeaccountId mapping here
//             'cc4c52b0d74b96306301dafa4c1d74c3c18ddd40' => '326d6fdbf1fc0749aabda7da6fb1834ba20ce4d1',
//             'af245ac36aa3a03f0c8ead5c6a432209f309b741' => 'eb6a97b9934bc7f4a69681fa14812175213fd722',
        );
        // do not transfer timesheets of this users
        $accountExceptions = array(
        // TODO add ids of user account whose timesheets should not be transfered 
//             'fa695d65edeead2f392b222d73f80bdd1e2ed353',
        );
        
        // get timesheets
        $filter = new Timetracker_Model_TimesheetFilter(array(
            array('field' => 'timeaccount_id', 'operator' => 'AND', 'value' => array(
                array('field' => 'id', 'operator' => 'equals', 'value' => $params['timeaccountId'])
            )),
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'start' => 0,
            'limit' => 100,
        ));
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($filter, $pagination);
        
        // loop timesheets
        $transferCount = 0;
        while (count($timesheets) > 0) {
            foreach ($timesheets as $timesheet) {
                if (in_array($timesheet->account_id, $accountExceptions)) {
                    echo "Skipping user {$timesheet->account_id}.\n";
                    continue;
                }
                
                // get user groups
                $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($timesheet->account_id);
                
                // transfer timesheet
                $found = FALSE;
                foreach ($mappingGroupToTimeaccount as $groupId => $timeaccountId) {
                    if (in_array($groupId, $groupMemberships)) {
                        echo 'Transfering timesheet of user ' . $timesheet->account_id . ' to timeaccount ' . $timeaccountId . ".\n";
                        
                        if (array_key_exists('dryrun', $params) && $params['dryrun'] == 0) {
                            $timesheet->timeaccount_id = $timeaccountId;
                            Timetracker_Controller_Timesheet::getInstance()->update($timesheet);
                            $transferCount++;
                        }
                        $found = TRUE;
                        break;
                    }
                }
                if (! $found) {
                    echo "No valid mapping found for timesheet.\n";
                }
            }
            
            $pagination->start = $pagination->start + 100;
            $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($filter, $pagination);
        }
        
        echo 'Transfered ' . $transferCount . " timesheets.\n";
        
        return TRUE;
    }
}
