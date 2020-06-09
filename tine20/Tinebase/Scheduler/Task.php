<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Scheduler_Task
{
    /**
     * minutely task
     * 
     * @var string
     */
    const TASK_TYPE_MINUTELY = '* * * * *';
    
    /**
     * hourly task
     * 
     * @var string
     */
    const TASK_TYPE_HOURLY = '0 * * * *';

    /**
     * daily task
     * 
     * @var string
     */
    const TASK_TYPE_DAILY = '0 0 * * *';

    /**
     * weekly task (thursdays)
     *
     * @var string
     */
    const TASK_TYPE_WEEKLY = '0 1 * * 4';

    const CLASS_NAME = 'class';
    const CONTROLLER = 'controller';
    const METHOD_NAME = 'method';
    const ARGS = 'args';

    /**
     * measures the time spend in run() method in seconds
     *
     * @var int
     */
    protected $_runDuration = null;

    /**
     * the cron expression as string
     *
     * @var string
     */
    protected $_cron = null;

    /**
     * @var \Cron\CronExpression
     */
    protected $_cronObject = null;

    /**
     * @var array
     */
    protected $_callables = null;

    /**
     * Tinebase_Scheduler_Task constructor.
     * @param array $options
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __construct(array $options)
    {
        if (!isset($options['cron'])) {
            throw new Tinebase_Exception_InvalidArgument('options needs to contain key cron with a cron expression');
        }
        if (!isset($options['callables'])) {
            throw new Tinebase_Exception_InvalidArgument('options needs to contain callables');
        }
        foreach ($options['callables'] as $callable) {
            if (!isset($callable[self::CLASS_NAME]) && !isset($callable[self::CONTROLLER])) {
                throw new Tinebase_Exception_InvalidArgument('callables need to contain class oder controller');
            }
            if (!isset($callable[self::METHOD_NAME])) {
                throw new Tinebase_Exception_InvalidArgument('callables need to contain a method');
            }
        }
        $this->_cron = $options['cron'];
        $this->_cronObject = Cron\CronExpression::factory($this->_cron);
        $this->_callables = $options['callables'];
    }

    public function toArray()
    {
        return [
            'cron'              => $this->_cron,
            'callables'         => $this->_callables,
        ];
    }

    /**
     * @return string
     */
    public function getCron()
    {
        return $this->_cron;
    }

    public function setCron($cron)
    {
        $this->_cron = $cron;
        $this->_cronObject = Cron\CronExpression::factory($this->_cron);
    }

    /**
     * @param Tinebase_Model_SchedulerTask $task
     */
    public function markSuccess(Tinebase_Model_SchedulerTask $task)
    {
        $task->last_run = $task->server_time;
        $task->last_duration = $this->_runDuration;
        $task->next_run = $this->_cronObject->getNextRunDate($task->server_time->format('Y-m-d H:i:s'))
            ->format('Y-m-d H:i:s');
    }

    /**
     * @param Tinebase_Model_SchedulerTask $task
     */
    public function markFailed(Tinebase_Model_SchedulerTask $task)
    {
        $task->last_failure = $task->server_time;
        $task->failure_count = $task->failure_count + 1;

        // if the next run is more than 1 hour away, set it to one hour
        // if the next run is less than 5 minutes away, set it to 5 minutes
        // otherwise accept the next run time
        $nextRun = $this->_cronObject->getNextRunDate($task->server_time->format('Y-m-d H:i:s'));
        $interval = $nextRun->diff($task->server_time);
        if ($interval->h > 0 || $interval->d > 0 || $interval->m > 0 || $interval->y > 0) {
            $task->next_run = clone $task->server_time->getClone()->addHour(1);
        } elseif ($interval->i < 5) {
            $task->next_run = clone $task->server_time->getClone()->addMinute(5);
        } else {
            $task->next_run = $nextRun->format('Y-m-d H:i:s');
        }
    }

    /**
     * @return bool
     */
    public function run()
    {
        $startTime = time();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' starting .... ');

        $aggResult = is_array($this->_callables) && count($this->_callables) > 0;
        foreach ($this->_callables as $callable) {
            try {
                if (isset($callable[self::CONTROLLER])) {
                    $class = $callable[self::CONTROLLER];
                } else {
                    $class = $callable[self::CLASS_NAME];
                }

                list($appName) = explode('_', $class);
                if (true !== Tinebase_Application::getInstance()->isInstalled($appName)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                        . __LINE__ . ' Application ' . $appName . ' is not installed for scheduler job');
                    $aggResult = false;
                    continue;
                }

                if (isset($callable[self::CONTROLLER])) {
                    $class = Tinebase_Controller_Abstract::getController($callable[self::CONTROLLER]);
                }
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not get controller for scheduler job: ' . $e->getMessage());
                Tinebase_Exception::log($e, false);
                $aggResult = false;
                continue;
            }

            $result = call_user_func_array([$class, $callable[self::METHOD_NAME]], isset($callable[self::ARGS]) ?
                $callable[self::ARGS] : []);

            $aggResult = $aggResult && $result;
        }

        $this->_runDuration = time() - $startTime;
        if (0 === $this->_runDuration) {
            $this->_runDuration = 1;
        }

        return $aggResult;
    }

    /**
     * @param string $name
     * @param string $cron
     * @param array $callAbles
     * @return Tinebase_Model_SchedulerTask
     */
    protected static function _getPreparedTask($name, $cron, array $callAbles)
    {
        return new Tinebase_Model_SchedulerTask([
            'name'          => $name,
            'config'        => new Tinebase_Scheduler_Task([
                'cron'      => $cron,
                'callables' => $callAbles
            ]),
            // TODO think about this! daily jobs will be executed soon after...
            'next_run'      => new Tinebase_DateTime('2001-01-01 01:01:01')
        ]);
    }

    /**
     * add alarm task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addAlarmTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_Alarm')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_Alarm', self::TASK_TYPE_MINUTELY, [[
            self::CONTROLLER    => 'Tinebase_Alarm',
            self::METHOD_NAME   => 'sendPendingAlarms',
            self::ARGS          => [
                'eventName' => 'Tinebase_Event_Async_Minutely'
            ],
        ]]);
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_Alarm::sendPendingAlarms in scheduler.');
    }
    
    /**
     * add scheduled import task
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addImportTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_Controller_ScheduledImport')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_Controller_ScheduledImport', self::TASK_TYPE_MINUTELY, [[
            self::CONTROLLER    => 'Tinebase_Controller_ScheduledImport',
            self::METHOD_NAME   => 'runNextScheduledImport',
        ]]);
        
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Saved task Tinebase_Controller_ScheduledImport::runNextScheduledImport in scheduler.');
    }
    
    /**
     * add cache cleanup task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addCacheCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_CacheCleanup')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_CacheCleanup', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_Controller',
            self::METHOD_NAME   => 'cleanupCache',
        ]]);
        
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Saved task Tinebase_Controller::cleanupCache in scheduler.');
    }
    
    /**
     * add sessions cleanup task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addSessionsCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_cleanupSessions')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_cleanupSessions', self::TASK_TYPE_HOURLY, [[
            self::CONTROLLER    => 'Tinebase_Controller',
            self::METHOD_NAME   => 'cleanupSessions',
        ]]);
        
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Saved task Tinebase_Controller::cleanupSessions in scheduler.');
    }
    
    /**
     * add credential cache cleanup task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addCredentialCacheCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_CredentialCacheCleanup')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_CredentialCacheCleanup', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_Auth_CredentialCache',
            self::METHOD_NAME   => 'clearCacheTable',
        ]]);
        
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Saved task Tinebase_Auth_CredentialCache::clearCacheTable in scheduler.');
    }
    
    /**
     * add temp_file table cleanup task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addTempFileCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_TempFileCleanup')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_TempFileCleanup', self::TASK_TYPE_HOURLY, [[
            self::CONTROLLER    => 'Tinebase_TempFile',
            self::METHOD_NAME   => 'clearTableAndTempdir',
        ]]);
        
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Saved task Tinebase_TempFile::clearTableAndTempdir in scheduler.');
    }
    
    /**
     * add deleted file cleanup task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addDeletedFileCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_DeletedFileCleanup')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_DeletedFileCleanup', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'clearDeletedFiles',
        ]]);
        
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Saved task Tinebase_FileSystem::clearDeletedFiles in scheduler.');
    }

    /**
     * add access log cleanup task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addAccessLogCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_AccessLogCleanup')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_AccessLogCleanup', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_AccessLog',
            self::METHOD_NAME   => 'clearTable',
        ]]);
        
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Saved task Tinebase_AccessLog::clearTable in scheduler.');
    }

    /**
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addAccountSyncTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_User/Group::syncUsers/Groups')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_User/Group::syncUsers/Groups', self::TASK_TYPE_HOURLY, [[
            self::CLASS_NAME    => 'Tinebase_User',
            self::METHOD_NAME   => 'syncUsers',
            self::ARGS          => [
                'options'           => [Tinebase_User::SYNC_WITH_CONFIG_OPTIONS => true],
            ]
        ],[
            self::CLASS_NAME    => 'Tinebase_Group',
            self::METHOD_NAME   => 'syncGroups',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_User/Group::syncUsers/Groups in scheduler.');
    }

    /**
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addReplicationTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('readModificationLogFromMaster')) {
            return;
        }

        $task = self::_getPreparedTask('readModificationLogFromMaster', self::TASK_TYPE_HOURLY, [[
            self::CONTROLLER    => 'Tinebase_Timemachine_ModificationLog',
            self::METHOD_NAME   => 'readModificationLogFromMaster'
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_Timemachine_ModificationLog::readModificationLogFromMaster in scheduler.');
    }

    /**
     * add file revision cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addFileRevisionCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_FileRevisionCleanup')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_FileRevisionCleanup', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'clearFileRevisions',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_FileSystem::clearFileRevisions in scheduler.');
    }

    /**
     * add file objects cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addFileObjectsCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_FileSystem::clearFileObjects')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_FileSystem::clearFileObjects', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'clearFileObjects',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_FileSystem::clearFileObjects in scheduler.');
    }

    /**
     * add file system index checking task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addFileSystemCheckIndexTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_FileSystemCheckIndex')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_FileSystemCheckIndex', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'checkIndexing',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_FileSystem::checkIndexing in scheduler.');
    }

    /**
     * add file system preview checking task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addFileSystemSanitizePreviewsTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_FileSystemSanitizePreviews')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_FileSystemSanitizePreviews', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'sanitizePreviews',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_FileSystem::sanitizePreviews in scheduler.');
    }

    /**
     * add file system index checking task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addFileSystemNotifyQuotaTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_FileSystemNotifyQuota')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_FileSystemNotifyQuota', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'notifyQuota',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_FileSystem::notifyQuota in scheduler.');
    }

    public static function addFileSystemRepairDeleteTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_FileSystem::repairTreeIsDeletedState')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_FileSystem::repairTreeIsDeletedState', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'repairTreeIsDeletedState',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_FileSystem::repairTreeIsDeletedState in scheduler.');
    }

    /**
     * add file system av scan task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addFileSystemAVScanTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_FileSystem::avScan')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_FileSystem::avScan', self::TASK_TYPE_WEEKLY, [[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'avScan',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_FileSystem::avScan in scheduler.');
    }

    /**
     * add file system size recalculation task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addFileSystemSizeRecalculation(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_FileSystemSizeRecalculation')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_FileSystemSizeRecalculation', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'recalculateRevisionSize',
        ],[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'recalculateFolderSize',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_FileSystem::recalculateRevisionSize and recalculateFolderSize in scheduler.');
    }

    /**
     * add acl tables cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addAclTableCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_AclTablesCleanup')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_AclTablesCleanup', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_Controller',
            self::METHOD_NAME   => 'cleanAclTables',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_Controller::cleanAclTables in scheduler.');
    }

    /**
     * add hourly action queue integrity check task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addActionQueueConsistencyCheckTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_ActionQueueConsistencyCheck')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_ActionQueueConsistencyCheck', self::TASK_TYPE_HOURLY, [[
            self::CONTROLLER    => 'Tinebase_Controller',
            self::METHOD_NAME   => 'actionQueueConsistencyCheck',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_Controller::actionQueueConsistencyCheck in scheduler.');
    }

    /**
     * add action queue constant monitoring task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addActionQueueMonitoringTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_ActionQueueActiveMonitoring')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_ActionQueueActiveMonitoring', self::TASK_TYPE_MINUTELY, [[
            self::CONTROLLER    => 'Tinebase_Controller',
            self::METHOD_NAME   => 'actionQueueActiveMonitoring',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_Controller::actionQueueActiveMonitoring in scheduler.');
    }

    /**
     * add filter sync token cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addFilterSyncTokenCleanUpTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_FilterSyncToken::cleanUp')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_FilterSyncToken::cleanUp', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_FilterSyncToken',
            self::METHOD_NAME   => 'cleanUp',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_FilterSyncToken::cleanUp in scheduler.');
    }
}
