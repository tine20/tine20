<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  ActionQueue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Züleyha Toptas <z.toptas@hotmail.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
            'executionMethod' => self::EXECUTION_METHOD_DISPATCH,
            'maxRetry'        => 10,
            'maxChildren'     => 10,
        )
    );
    
    /**
     * keeps mapping from process id to job id
     * 
     * @var array
     */
    protected $_jobScoreBoard = array();
    
    /**
     * infinite loop where daemon manages the execution of the jobs from the job queue
     */
    public function run()
    {
        while (true) {
            
            // manage the number of children
            if (count ($this->_children) >= $this->_getConfig()->tine20->maxChildren ) {
                $this->_getLogger()->crit(__METHOD__ . '::' . __LINE__ .    " reached max children limit: " . $this->_getConfig()->tine20->maxChildren);
                $this->_getLogger()->info(__METHOD__ . '::' . __LINE__ .    " number of pending jobs:" . Tinebase_ActionQueue::getInstance()->getQueueSize());
                usleep(100); // save some trees
                continue;
            }
            
            $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ .    " trying to fetch a job from queue");

            $jobId = Tinebase_ActionQueue::getInstance()->waitForJob();

            // no job found
            if ($jobId === FALSE) {
                continue;
            }
            
            try {
                $job = Tinebase_ActionQueue::getInstance()->receive($jobId);
            } catch (RuntimeException $re) {
                $this->_getLogger()->crit(__METHOD__ . '::' . __LINE__ . " failed to receive message: " . $re->getMessage());
                
                // we are unable to process the job
                Tinebase_ActionQueue::getInstance()->delete($jobId);
                
                continue;
            }
            
            $this->_getLogger()->info (__METHOD__ . '::' . __LINE__ . " forking to process job {$job['action']} with id $jobId");
            $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ . " process message: " . print_r($job, TRUE)); 
            
            $childPid = $this->_forkChild();
            
            if ($childPid == 0) { // executed in child process
                try {
                    $this->_executeAction($job);

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
    }

    /**
     * We have to destroy the Tinebase_ActionQueue instance before the process forks.
     * Otherwise the resource containing the connection to the queue backend will be
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
        parent::_childTerminated($pid, $status);
        
        $status = pcntl_wexitstatus($status);
        
        $jobId = $this->_jobScoreBoard[$pid];
        unset($this->_jobScoreBoard[$pid]);
        
        if ($status > 0) { // failure
            $this->_getLogger()->crit(__METHOD__ . '::' . __LINE__ .    " job $jobId did not finish successfully. Will be rescheduled!"); 
            
            Tinebase_ActionQueue::getInstance()->reschedule($jobId);
            
        } else {           // success
            $this->_getLogger()->info(__METHOD__ . '::' . __LINE__ .    " job $jobId finished successfully");
            
            Tinebase_ActionQueue::getInstance()->delete($jobId);
        }
    }
    
    /**
     * execute the action
     *
     * @param  string  $job
     */
    protected function _executeAction($job)
    {
        // execute in subprocess
        if ($this->_getConfig()->tine20->executionMethod === self::EXECUTION_METHOD_EXEC_CLI) {
            // @todo make self::EXECUTION_METHOD_EXEC_CLI working
            $output = system('php $paths ./../../tine20.php --method Tinebase.executeQueueJob message=' . escapeshellarg($job), $exitCode );
            if (exitCode != 0) {
                throw new Exception('Problem during execution with shell: ' . $output);
            }

        // execute in same process
        } else {
            Tinebase_Core::initFramework();
    
            Tinebase_Core::set(Tinebase_Core::USER, Tinebase_User::getInstance()->getFullUserById($job['account_id']));
            
            Tinebase_ActionQueue::getInstance()->executeAction($job);
        }
    }
}
