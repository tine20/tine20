<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe g.ciyiltepe@metaways.de>
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
     * hourly task
     * 
     * @var string
     */
    const TASK_TYPE_HOURLY = 'hourly';

    /**
     * daily task
     * 
     * @var string
     */
    const TASK_TYPE_DAILY = 'daily';
    
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
        $task->setMonths("Jan-Dec");
        $task->setWeekdays("Sun-Sat");
        $task->setDays("1-31");
        
        switch ($_type) {
            case self::TASK_TYPE_MINUTELY:
                $task->setHours("0-23");
                $task->setMinutes("0/1");
                break;
            case self::TASK_TYPE_HOURLY:
                $task->setHours("0-23");
                $task->setMinutes("0");
                break;
            case self::TASK_TYPE_DAILY:
                $task->setHours("0");
                $task->setMinutes("0");
                break;
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Saved task Tinebase_Alarm::sendPendingAlarms in scheduler.');
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Saved task Tinebase_ActionQueue::processQueue in scheduler.');
    }
    
    /**
     * add cache cleanup task to scheduler
     * 
     * @param Zend_Scheduler $_scheduler
     */
    public static function addCacheCleanupTask(Zend_Scheduler $_scheduler)
    {
        $request = new Zend_Controller_Request_Simple(); 
        $request->setControllerName('Tinebase_Controller');
        $request->setActionName('cleanupCache');
        
        $task = self::getPreparedTask(self::TASK_TYPE_HOURLY);
        $task->setRequest($request);
        
        $_scheduler->addTask('Tinebase_CacheCleanup', $task);
        $_scheduler->saveTask();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Saved task Tinebase_Controller::cleanupCache in scheduler.');
    }
    
    /**
     * add credential cache cleanup task to scheduler
     * 
     * @param Zend_Scheduler $_scheduler
     */
    public static function addCredentialCacheCleanupTask(Zend_Scheduler $_scheduler)
    {
        $request = new Zend_Controller_Request_Simple(); 
        $request->setControllerName('Tinebase_Auth_CredentialCache');
        $request->setActionName('clearCacheTable');
        
        $task = self::getPreparedTask(self::TASK_TYPE_DAILY);
        $task->setRequest($request);
        
        $_scheduler->addTask('Tinebase_CredentialCacheCleanup', $task);
        $_scheduler->saveTask();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Saved task Tinebase_Auth_CredentialCache::clearCacheTable in scheduler.');
    }
    
    /**
     * run requests
     * 
     * @see tine20/Zend/Scheduler/Zend_Scheduler_Task::run()
     */
    public function run()
    {
        foreach ($this->getRequests() as $request) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Running request: ' . $request->getControllerName() . '::' . $request->getActionName());
            
            $controller = Tinebase_Controller_Abstract::getController($request->getControllerName());
            
            // strange: only the first request is process because of this return 
            // @todo remove the loop? can there be multiple requests?)
            return call_user_func_array(array($controller, $request->getActionName()), $request->getUserParams());
        }
    }
}
