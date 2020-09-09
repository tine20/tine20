<?php
/**
 * Tine 2.0
 * 
 * @package     ActiveSync
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 *
 */

/**
 * class for ActiveSync initialization
 * 
 * @package     ActiveSync
 * @subpackage  Setup
 */
class ActiveSync_Setup_Initialize extends Setup_Initialize
{
    /**
     * init scheduler tasks
     */
    protected function _initializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        ActiveSync_Scheduler_Task::addMonitorDeviceLastPingTask($scheduler);
    }
}
