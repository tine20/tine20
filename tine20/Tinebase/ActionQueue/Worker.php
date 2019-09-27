<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  ActionQueue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Züleyha Toptas <z.toptas@hotmail.de>
 * @copyright   Copyright (c) 2012-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * Daemon to check the job queue and process jobs in separate processes
 * 
 * @package     Tinebase
 * @subpackage  ActionQueue
 */
class Tinebase_ActionQueue_Worker extends Console_Daemon
{
    const EXECUTION_METHOD_DISPATCH = 'dispatch';
    const EXECUTION_METHOD_EXEC_CLI = 'exec_cli';

    protected $_stopped = false;

    protected $_isLongRunning = false;

    /** 
     * default configurations of this daemon
     * 
     * @var array
     */
    protected static $_defaultConfig = array(
        'general' => array(
            'configfile' => '/etc/tine20/actionQueue.ini', 
            'pidfile'    => '/var/run/tine20/actionQueue.pid',
        ),
        'tine20' => array (
            'tine20php'       => __DIR__ . '/../../tine20.php',
            'executionMethod' => self::EXECUTION_METHOD_DISPATCH,
            'maxRetry'        => 10,
            'maxChildren'     => 10,
            'shutDownWait'    => 60,
            'longRunning'     => false,
        )
    );
    
    /**
     * keeps mapping from process id to job id
     * 
     * @var array
     */
    protected $_jobScoreBoard = array();

    /**
     * php script to execute tine
     *
     * @var string
     */
    protected $_tineExecutable = null;

    /**
     * constructor
     *
     * @param Zend_Config $config
     */
    public function __construct($config = NULL)
    {
        if (!is_file(static::$_defaultConfig['tine20']['tine20php'])) {
            if (is_file('/usr/sbin/tine20-cli')) {
                static::$_defaultConfig['tine20']['tine20php'] = '/usr/sbin/tine20-cli';
            }
        }
        parent::__construct($config);

        Tinebase_Core::setupSentry();
    }

    /**
     * infinite loop where daemon manages the execution of the jobs from the job queue
     */
    public function run()
    {
        // setup proper logging
        Tinebase_Core::set(Tinebase_Core::LOGGER, $this->_getLogger());

        if ($this->_getConfig()->tine20->longRunning) {
            $actionQueue = Tinebase_ActionQueueLongRun::getInstance();
            $this->_isLongRunning = true;
        } else {
            $actionQueue = Tinebase_ActionQueue::getInstance();
        }
        if ($actionQueue->getBackendType() !== 'Tinebase_ActionQueue_Backend_Redis') {
            $this->_getLogger()->crit(__METHOD__ . '::' . __LINE__
                . ' not Tinebase_ActionQueue_Backend_Redis used. There is nothing to do for the worker!'
                . ' Configure Redis backend if you want to make use of the worker.');
            exit(1);
        }

        $this->_tineExecutable = $this->_getConfig()->tine20->tine20php;
        $maxChildren = $this->_getConfig()->tine20->maxChildren;
        $lastMaxChildren = 0;

        while (!$this->_stopped) {

            // manage the number of children
            if (count ($this->_children) >=  $maxChildren) {

                // log only every minute
                if (time() - $lastMaxChildren > 60) {
                    $this->_getLogger()->crit(__METHOD__ . '::' . __LINE__ . " reached max children limit: " . $maxChildren);
                    $this->_getLogger()->info(__METHOD__ . '::' . __LINE__ . " number of pending jobs:" . $actionQueue->getQueueSize());
                    $lastMaxChildren = time();
                }
                usleep(1000); // save some trees
                pcntl_signal_dispatch();
                continue;
            }
            
            $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ .    " trying to fetch a job from queue " . microtime(true));

            $jobId = $actionQueue->waitForJob();

            $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ .    " signal dispatch " . microtime(true));

            pcntl_signal_dispatch();

            // no job found
            if ($jobId === FALSE || $this->_stopped) {
                continue;
            }

            // check for maintenance mode
            Tinebase_Core::getConfig()->clearMemoryCache(Tinebase_Config::MAINTENANCE_MODE);
            while (Tinebase_Core::inMaintenanceModeAll()) {
                usleep(10000); // save some trees
                pcntl_signal_dispatch();
                Tinebase_Core::getConfig()->clearMemoryCache(Tinebase_Config::MAINTENANCE_MODE);
            }
            
            try {
                $job = $actionQueue->receive($jobId);
            } catch (RuntimeException $re) {
                $this->_getLogger()->crit(__METHOD__ . '::' . __LINE__ . " failed to receive message: " . $re->getMessage());
                
                // we are unable to process the job
                // probably the retry count is exceeded
                // TODO push message to dead letter queue
                $actionQueue->delete($jobId);
                
                continue;
            }
            
            $this->_getLogger()->info (__METHOD__ . '::' . __LINE__ . " forking to process job {$job['action']} with id $jobId");
            $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ . " process message: " . print_r($job, TRUE)); 


            // TODO fork may not work!!!
            $childPid = $this->_forkChild();
            
            if ($childPid == 0) { // executed in child process
                try {
                    $this->_executeAction($jobId);

                    $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ . " exiting...");
                    exit(0); // message will be deleted in parent process
                    
                } catch (Exception $e) {
                    $this->_getLogger()->crit(__METHOD__ . '::' . __LINE__ .    " could not execute job : " . $job['action']);
                    $this->_getLogger()->crit(__METHOD__ . '::' . __LINE__ .    " could not execute job : " . $e->getMessage());
                    $this->_getLogger()->crit(__METHOD__ . '::' . __LINE__ .    " could not execute job : " . $e->getTraceAsString());

                    exit(1); // message will be rescheduled in parent process
                }
                
            } else { // executed in parent process
                $this->_jobScoreBoard[$childPid] = $jobId;
            }
        }

        $this->_shutDown();
    }

    protected function _shutDown()
    {
        $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ .    " shutting down... " . microtime(true));

        $timeStart = time();
        $timeElapsed = 0;
        $shutDownWait = (int)($this->_getConfig()->tine20->shutDownWait);

        while ($timeElapsed < $shutDownWait) {

            pcntl_signal_dispatch();

            if (count($this->_children) === 0) {
                break;
            }

            // 10ms
            usleep(10000);

            $timeElapsed = time() - $timeStart;
        }

        $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ .    " parent shut down... " . microtime(true));

        parent::_shutDown();
    }

    protected function _gracefulShutDown()
    {
        $this->_stopped = true;

        return true;
    }

    /**
     * We have to destroy the Tinebase_ActionQueue instance before the process forks.
     * Otherwise the resource holding the connection to the queue backend will be
     * shared between the parent and the child which leads to strange problems
     * 
     * @see Console_Daemon::_beforeFork()
     */
    protected function _beforeFork()
    {
        Tinebase_ActionQueue::destroyInstance();
    }
    
    /**
     * handle terminated processes
     * either delete or reschedule the job
     * 
     * @param  string  $pid     the pid of the process
     * @param  string  $status  the exit status of the process 
     * @return void
     */
    protected function _childTerminated($pid, $status)
    {
        $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ .    " with pid $pid");

        $jobId = $this->_jobScoreBoard[$pid];
        unset($this->_jobScoreBoard[$pid]);

        if (true !== pcntl_wifexited($status)) {
            $this->_getLogger()->crit(__METHOD__ . '::' . __LINE__ .    " child $pid did not finish successfully!");

            Tinebase_ActionQueue::getInstance()->reschedule($jobId);

            return;
        }
        parent::_childTerminated($pid, $status);
        
        $status = pcntl_wexitstatus($status);
        

        if ($status > 0) { // failure
            $this->_getLogger()->crit(__METHOD__ . '::' . __LINE__ .    " job $jobId in pid $pid did not finish successfully. Will be rescheduled!");
            
            Tinebase_ActionQueue::getInstance()->reschedule($jobId);
            
        } else {           // success
            $this->_getLogger()->info(__METHOD__ . '::' . __LINE__ .    " job $jobId in pid $pid finished successfully");
            
            Tinebase_ActionQueue::getInstance()->delete($jobId);
        }
    }
    
    /**
     * execute the action
     *
     * @param  string  $jobId
     * @throws Exception
     */
    protected function _executeAction($jobId)
    {
        $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ . " with isChild: " . var_export($this->_isChild, true));

        // execute in subprocess
        //if ($this->_getConfig()->tine20->executionMethod === self::EXECUTION_METHOD_EXEC_CLI) {

            exec(PHP_BINARY . ' -d include_path=' . escapeshellarg(get_include_path()) .
                ' ' . $this->_tineExecutable . ' --method Tinebase.executeQueueJob jobId=' . escapeshellarg($jobId)
                . ($this->_isLongRunning ? ' longRunning=true':''), $output, $exitCode);
            if ($exitCode != 0) {
                throw new Exception('Problem during execution with shell: ' . join(PHP_EOL, $output));
            }

        // execute in same process
        /*} else {
            Zend_Registry::_unsetInstance();

            Tinebase_Core::initFramework();
    
            Tinebase_Core::set(Tinebase_Core::USER, Tinebase_User::getInstance()->getFullUserById($job['account_id']));

        if (true !== ($result = Tinebase_ActionQueue::getInstance()->executeAction($job))) {
            throw new Tinebase_Exception_UnexpectedValue('action queue job execution did not return true: ' . var_export($result, true));
        }*/
        //}

        //$this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ . " result: " . var_export($result, true));
        $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ . " output: " . var_export($output, true));
    }
}
