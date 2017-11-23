<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Calendar
 * @subpackage  Scheduler
 */
class Calendar_Scheduler_Task extends Tinebase_Scheduler_Task
{
    /**
     * add update constraints exdates task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addUpdateConstraintsExdatesTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Calendar_Controller_Event::updateConstraintsExdates')) {
            return;
        }

        $task = self::_getPreparedTask('Calendar_Controller_Event::updateConstraintsExdates', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Calendar_Controller_Event',
            self::METHOD_NAME   => 'updateConstraintsExdates',
        ]]);
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Calendar_Controller_Event::updateConstraintsExdates in scheduler.');
    }

    /**
     * @param Tinebase_Scheduler $_scheduler
     * @return bool
     */
    public static function removeUpdateConstraintsExdatesTask(Tinebase_Scheduler $_scheduler)
    {
        return $_scheduler->removeTask('Calendar_Controller_Event::updateConstraintsExdates');
    }

    public static function addTentativeNotificationTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Calendar_Controller_Event::sendTentativeNotifications')) {
            return;
        }

        $task = self::_getPreparedTask('Calendar_Controller_Event::sendTentativeNotifications', self::TASK_TYPE_DAILY,
            [[
                self::CONTROLLER    => 'Calendar_Controller_Event',
                self::METHOD_NAME   => 'sendTentativeNotifications',
        ]]);
        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Calendar_Controller_Event::sendTentativeNotifications in scheduler.');
    }

    /**
     * @param Tinebase_Scheduler $_scheduler
     * @return bool
     */
    public static function removeTentativeNotificationTask(Tinebase_Scheduler $_scheduler)
    {
        return $_scheduler->removeTask('Calendar_Controller_Event::sendTentativeNotifications');
    }
}
