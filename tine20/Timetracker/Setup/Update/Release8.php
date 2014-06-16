<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
class Timetracker_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     */
    public function update_0()
    {
        $this->_backend->dropCol('timetracker_timesheet', 'cleared_time');
        $this->setTableVersion('timetracker_timesheet', 4);
        $this->setApplicationVersion('Timetracker', '8.1');
    }
    
    /**
     * update to 8.2
     * - update 256 char fields
     * 
     * @see 0008070: check index lengths
     */
    public function update_1()
    {
        $columns = array("timetracker_timeaccount" => array(
                            "billed_in" => "false",
                            "deadline" => "false",
                            "title" => "true"
                            ),
                        "timetracker_timesheet" => array(
                            "billed_in" => "false"
                            )
                        );
        
        $this->truncateTextColumn($columns, 255);
        $this->setTableVersion('timetracker_timeaccount', 10);
        $this->setTableVersion('timetracker_timesheet', 5);
        $this->setApplicationVersion('Timetracker', '8.2');
    }
}
