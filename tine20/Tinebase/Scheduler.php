<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
    private function __construct()
    {
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
     */
    protected function _checkRight(/** @noinspection PhpUnusedParameterInspection */$_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        $this->checkRight(Tinebase_Acl_Rights::ADMIN);
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
            // first get a random task due to execute (without a transaction to avoid deadlocks!)
            if (null === ($dueTask = $this->_backend->getDueTask())) {
                // nothing to do, stop work
                break;
            }

            // in case a task fails quickly, we may run by here every few milli seconds... so better keep count
            if (!isset($tasks[$dueTask->getId()])) {
                $tasks[$dueTask->getId()] = 1;
            } else {
                if (++$tasks[$dueTask->getId()] > 3) {
                    // same task for the third time, we abort now
                    return false;
                }
                if (++$tasks[$dueTask->getId()] > 2) {
                    // lets try one more time, maybe there are other tasks to work on
                    continue;
                }
            }

            // now get the task transaction and deadlock safe and mark it running
            $transactionId = $transactionManager->startTransaction($db);

            if (null === ($task = $this->_backend->getTaskForUpdate($dueTask->getId()))) {
                // this may happen legally, though very unlikely, in case the task was deleted
                // in a parallel process after getting it in the first select statement
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                    . __LINE__ . ' task ' . $dueTask->name . ' ' . $dueTask->getId() . ' disappeared');
                $transactionManager->commitTransaction($transactionId);
                continue;
            }

            if (!empty($task->lock_id)) {
                // somebody else snatched it away, no worry, just continue and maybe get another task
                $transactionManager->commitTransaction($transactionId);
                continue;
            }

            if (true !== $this->_backend->markTaskRunning($task)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' failed to mark task running.');
                $transactionManager->rollBack();
                return false;
            }
            $lockId = $task->lock_id;
            try {
                $transactionManager->commitTransaction($transactionId);

                // then run the task
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' run task ' . $task->name);
                }
                try {
                    if (true === $task->config->run()) {
                        $task->config->markSuccess($task);
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
                                __LINE__ . ' task ' . $task->name . ' succeeded');
                        }
                    } else {
                        $task->config->markFailed($task);
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                                __LINE__ . ' task ' . $task->name . ' failed gracefully');
                        }
                    }
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' task ' . $task->name .
                        ' failed with exception');
                    Tinebase_Exception::log($e);
                    $task->config->markFailed($task);
                }

                // clean up again
                $task->lock_id = null;
                $this->_backend->update($task);
            } finally {
                Tinebase_Core::releaseMultiServerLock($lockId);
            }
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