<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class GDPR_Scheduler_Task extends Tinebase_Scheduler_Task 
{
    /**
     * add delete expired Data task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addDeleteExpiredDataTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('GDPR_Controller_DataIntendedPurposeRecord::deleteExpiredData')) {
            return;
        }

        $task = self::_getPreparedTask('GDPR_Controller_DataIntendedPurposeRecord::deleteExpiredData', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'GDPR_Controller_DataIntendedPurposeRecord',
            self::METHOD_NAME   => 'deleteExpiredData',
        ]]);
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task GDPR_Controller_DataIntendedPurposeRecord::deleteExpiredData in scheduler.');
    }

    /**
     * remove delete expired Data task from scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function removeDeleteExpiredDataTask(Tinebase_Scheduler $_scheduler)
    {
        $_scheduler->removeTask('GDPR_Controller_DataIntendedPurposeRecord::deleteExpiredData');

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task GDPR_Controller_DataIntendedPurposeRecord::deleteExpiredData from scheduler.');
    }
}
