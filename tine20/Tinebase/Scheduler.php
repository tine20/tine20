<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Scheduler Controller
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 */

class Tinebase_Scheduler extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'Tinebase';
        $this->_backend = new Tinebase_Backend_Scheduler();
        $this->_modelName = 'Tinebase_Model_SchedulerTask';
        $this->_purgeRecords = true;
        $this->_omitModLog = true;
        $this->_doContainerACLChecks = false;
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Scheduler
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Tinebase_Scheduler
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * overwrite this function to check rights / don't forget to call parent
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_SecondFactorRequired
     */
    protected function _checkRight(/** @noinspection PhpUnusedParameterInspection */$_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        //second factor? No, not really please, CLI job...
        //parent::_checkRight($_action);

        $this->checkRight('admin');
    }

    /**
     * @return bool
     */
    public function run()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' Scheduler started');
        $db = Tinebase_Core::getDb();
        $transactionManager = Tinebase_TransactionManager::getInstance();
        $this->_backend->cleanZombieTasks();
        $started = time();
        $tasks = [];

        do {
            // first get and mark a task as running (within a transaction)
            $transactionId = $transactionManager->startTransaction($db);
            if (null === ($task = $this->_backend->getDueTask())) {
                // nothing to do, stop work
                $transactionManager->commitTransaction($transactionId);
                break;
            }

            // in case a task fails quickly, we may run by here every few milli seconds... so better keep count
            if (!isset($tasks[$task->getId()])) {
                $tasks[$task->getId()] = 1;
            } else {
                if (++$tasks[$task->getId()] > 5) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                        . __LINE__ . ' task ' . $task->name . ' already passed by 5 times, aborting now');
                    $transactionManager->commitTransaction($transactionId);
                    return false;
                }
            }

            if (true !== $this->_backend->markTaskRunning($task)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' failed to mark task running.');
                $transactionManager->rollBack();
                return false;
            }
            $transactionManager->commitTransaction($transactionId);

            // then run the task
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' run task ' . $task->name);
            try {
                if (true === $task->config->run()) {
                    $task->config->markSuccess($task);
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
                        __LINE__ . ' task ' . $task->name . ' succeeded');
                } else {
                    $task->config->markFailed($task);
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                        __LINE__ . ' task ' . $task->name . ' failed gracefully');
                }
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' task ' . $task->name .
                    ' failed with exception');
                Tinebase_Exception::log($e);
                $task->config->markFailed($task);
            }

            // clean up again
            $lockId = $task->lock_id;
            $task->lock_id = null;
            $this->_backend->update($task);
            Tinebase_Core::releaseMultiServerLock($lockId);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' running task ' . $task->name . ' finished');

        } while (time() - $started < 90);

        return true;
    }

    /**
     * @param string $_name
     * @return bool
     */
    public function hasTask($_name)
    {
        return $this->_backend->hasTask($_name);
    }

    /**
     * @param $_name
     * @return bool
     */
    public function removeTask($_name)
    {
        return 1 === $this->_backend->deleteByProperty($_name, 'name');
    }

    /**
     * @return null|Tinebase_Model_SchedulerTask
     */
    public function getLastRun()
    {
        return $this->_backend->getLastRun();
    }
}