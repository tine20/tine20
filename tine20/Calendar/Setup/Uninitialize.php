<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase initialization
 *
 * @package     Sales
 */
class Calendar_Setup_Uninitialize extends Setup_Uninitialize
{
    /**
     * uninit scheduler tasks
     */
    protected function _uninitializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Calendar_Scheduler_Task::removeTentativeNotificationTask($scheduler);
        Calendar_Scheduler_Task::removeUpdateConstraintsExdatesTask($scheduler);
    }
}