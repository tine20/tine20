<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class HumanResources_Scheduler_Task extends Tinebase_Scheduler_Task 
{
    /**
     * add CalculateDailyWorkingTimeReportsTask task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addCalculateDailyWorkingTimeReportsTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask')) {
            return;
        }

        $task = self::_getPreparedTask('HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'HumanResources_Controller_DailyWTReport',
            self::METHOD_NAME   => 'calculateAllReports',
        ]]);
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask in scheduler.');
    }

    /**
     * remove CalculateDailyWorkingTimeReportsTask task from scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function removeUpdateDailyWTReportLifespanTask(Tinebase_Scheduler $_scheduler)
    {
        $_scheduler->removeTask('HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask');

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask from scheduler.');
    }
}
