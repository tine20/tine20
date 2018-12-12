<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class ActiveSync_Scheduler_Task extends Tinebase_Scheduler_Task 
{
    /**
     * add monitorDeviceLastPing task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addMonitorDeviceLastPingTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('ActiveSync_Controller_Device::monitorDeviceLastPingTask')) {
            return;
        }

        $task = self::_getPreparedTask('ActiveSync_Controller_Device::monitorDeviceLastPingTask', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'ActiveSync_Controller_Device',
            self::METHOD_NAME   => 'monitorDeviceLastPing',
        ]]);
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task ActiveSync_Controller_Device::monitorDeviceLastPingTask in scheduler.');
    }

    /**
     * remove update product lifespan task from scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function removeUpdateDeviceLifespanTask(Tinebase_Scheduler $_scheduler)
    {
        $_scheduler->removeTask('ActiveSync_Controller_Device::monitorDeviceLastPingTask');

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task ActiveSync_Controller_Device::monitorDeviceLastPingTask from scheduler.');
    }
}
