<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @param Zend_Scheduler $_scheduler
     */
    public static function addUpdateConstraintsExdatesTask(Zend_Scheduler $_scheduler)
    {
        $task = self::getPreparedTask(self::TASK_TYPE_DAILY, array(
            'controller'    => 'Calendar_Controller_Event',
            'action'        => 'updateConstraintsExdates',
        ));
        $_scheduler->addTask('Calendar_Controller_Event::updateConstraintsExdates', $task);
        $_scheduler->saveTask();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Calendar_Controller_Event::updateConstraintsExdates in scheduler.');
    }
}
