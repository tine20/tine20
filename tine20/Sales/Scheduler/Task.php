<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @param Zend_Scheduler $_scheduler
     */
    public static function addUpdateProductLifespanTask(Zend_Scheduler $_scheduler)
    {
        $task = self::getPreparedTask(self::TASK_TYPE_HOURLY, array(
            'controller'    => 'Sales_Controller_Product',
            'action'        => 'updateProductLifespan',
        ));
        $_scheduler->addTask('Sales_Controller_Product::updateProductLifespan', $task);
        $_scheduler->saveTask();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Sales_Controller_Product::updateProductLifespan in scheduler.');
    }
}
