<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe g.ciyiltepe@metaways.de>
 * @version     $Id$
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Scheduler_Task extends Zend_Scheduler_Task 
{
    /**
     * minutely task (default)
     * 
     * @var string
     */
    const TASK_TYPE_MINUTELY = 'minutely';
    
    /**
     * static task getter
     * 
     * @param  string $_type
     * @param  array $_options
     * @return Tinebase_Scheduler_Task
     */
    public static function getPreparedTask($_type = self::TASK_TYPE_MINUTELY, array $_options = array())
    {
        $task = new Tinebase_Scheduler_Task($_options);
        if ($_type == self::TASK_TYPE_MINUTELY) {
            $task->setMonths("Jan-Dec");
            $task->setWeekdays("Sun-Sat");
            $task->setDays("1-31");
            $task->setHours("0-23");
            $task->setMinutes("0/1");
        }
        
        return $task;
    }
    
    /**
     * add alarm task to scheduler
     * 
     * @param Zend_Scheduler $_scheduler
     */
    public static function addAlarmTask(Zend_Scheduler $_scheduler)
    {
        $request = new Zend_Controller_Request_Simple(); 
        $request->setControllerName('Tinebase_Alarm');
        $request->setActionName('sendPendingAlarms');
        $request->setParam('eventName', 'Tinebase_Event_Async_Minutely');
        
        $task = self::getPreparedTask();
        $task->setRequest($request);
        
        $_scheduler->addTask('Tinebase_Alarm', $task);
        $_scheduler->saveTask();
    }
    
    /**
     * add queue task to scheduler
     * 
     * @param Zend_Scheduler $_scheduler
     */
    public static function addQueueTask(Zend_Scheduler $_scheduler)
    {
        $request = new Zend_Controller_Request_Simple(); 
        $request->setControllerName('Tinebase_ActionQueue');
        $request->setActionName('processQueue');
        
        $task = self::getPreparedTask();
        $task->setRequest($request);
        
        $_scheduler->addTask('Tinebase_ActionQueue', $task);
        $_scheduler->saveTask();
    }
    
    /**
     * run requests
     * 
     * @see tine20/Zend/Scheduler/Zend_Scheduler_Task::run()
     */
    public function run()
    {
        foreach ($this->getRequests() as $request) {
            $controller = Tinebase_Controller_Abstract::getController($request->getControllerName());
            return call_user_func_array(array($controller, $request->getActionName()), $request->getUserParams());
        }
    }
}
