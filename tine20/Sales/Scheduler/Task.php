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
class Sales_Scheduler_Task extends Tinebase_Scheduler_Task 
{
    /**
     * add update product lifespan task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addUpdateProductLifespanTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Sales_Controller_Product::updateProductLifespan')) {
            return;
        }

        $task = self::_getPreparedTask('Sales_Controller_Product::updateProductLifespan', self::TASK_TYPE_HOURLY, [[
            self::CONTROLLER    => 'Sales_Controller_Product',
            self::METHOD_NAME   => 'updateProductLifespan',
        ]]);
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Sales_Controller_Product::updateProductLifespan in scheduler.');
    }

    /**
     * remove update product lifespan task from scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function removeUpdateProductLifespanTask(Tinebase_Scheduler $_scheduler)
    {
        $_scheduler->removeTask('Sales_Controller_Product::updateProductLifespan');

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task Sales_Controller_Product::updateProductLifespan from scheduler.');
    }
}
