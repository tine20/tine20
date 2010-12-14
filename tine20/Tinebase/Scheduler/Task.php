<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe g.ciyiltepe@metaways.de>
 * @version     $Id: Task.php 13654 2010-03-23 16:06:19Z g.ciyiltepe@metaways.de $
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
    public static function getTask($_type = self::TASK_TYPE_MINUTELY, array $_options = array())
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
