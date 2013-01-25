<?php
/**
 * Tine 2.0
 * @package     Redis
 * @subpackage  Worker/Daemon
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Züleyha Toptas <z.toptas@hotmail.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require_once(__DIR__ . '/../../../bootstrap.php');

/**
 * Daemon checks the message queue and manages the execution
 */
class Tinebase_Redis_Worker_Daemon extends Console_Daemon
{
    const EXECUTION_METHOD_DISPATCH = 'dispatch';
    const EXECUTION_METHOD_EXEC_CLI = 'exec_cli';
    
    protected $_pidFile = '/var/run/tine20/actionqueuedaemon.pid';
    
    /**
     * default config path for ini-file
     *
     * @var Console_Daemon
     */
    protected $_defaultConfigPath = '/etc/tine20/actionQueue.ini';

    /**
     * @var Redis
     */
    protected $_redis;

    /**
     * Redis object Queue(message queue)
     *
     * @var DaemonSide
     */
    protected $_redisDaemonQueueStructName;

    /**
     * Redis object Data(data queue for saving hashes)
     *
     * @var DaemonSide
     */
    protected $_redisDaemonDataStructName;

    /**
     * Redis object Daemon(backup queue)
     *
     * @var DaemonSide
     */
    protected $_redisDaemonDaemonStructName;

    /** 
     * default configurations of this daemon
     * 
     * @var array
     */
    protected static $_defaultConfig = array(
        'host'              => 'localhost',
        'port'              => 6379,
        'timeout'           => 5,
        
        'queueName'         => 'TinebaseQueue',
        'redisQueueSuffix'  => 'Queue',
        'redisDataSuffix'   => 'Data',
        'redisDaemonSuffix' => 'Daemon',
        'redisDaemonNumber' => 1,
        
        'executionMethod'   => self::EXECUTION_METHOD_DISPATCH,
        'maxRetry'          => 10,
        'maxChildren'       => 5,
        
        'loglevel'          => 4,
        'logfile'           => NULL, //STDOUT
    );

    /**
     * constructor
     * 
     * @param Zend_Config $config
     */
    public function __construct($config = NULL)
    {
        parent::__construct();

        // assemble data struct names
        $this->_redisDaemonQueueStructName  = $this->_config->queueName . $this->_config->redisQueueSuffix;
        $this->_redisDaemonDataStructName   = $this->_config->queueName . $this->_config->redisDataSuffix;
        $this->_redisDaemonDaemonStructName = $this->_config->queueName . $this->_config->redisDaemonSuffix . $this->_config->redisDaemonNumber;

        $this->_setupRedis();
        $this->_setupLogger();
    }

    /**
     * infinite loop where daemon manages the execution of the messages from the Message Queue
     * 
     */
    public function run()
    {
        while (true) {
            // manage the number of children
            if (count ($this->_children) < $this->_config->maxChildren ) {
                $this->_logger->info(__CLASS__ . '::' . __LINE__ .    " trying to get job");

                // pop id from message queue, push it to the daemon queue, return the id
                $id = $this->_redis->brpoplpush($this->_redisDaemonQueueStructName, $this->_redisDaemonDaemonStructName, 1);

                // previous versions of phpredis return *-1 when brpoplpush timed out
                // current version returns false on timeout
                if ($id !== FALSE && $id != '*-1') {
                    $this->_logger->info(__CLASS__ . '::' . __LINE__ .    " forking to process job with id $id");
                    $child_pid = $this->_forkChildren();
                    
                    if ($child_pid == 0) {
                        $message = $this->_redis->hGet($this->_redisDaemonDataStructName . ":" . $id, 'data');
                        $this->_logger->info(__CLASS__ . '::' . __LINE__ .    " child got data: " . print_r($message, TRUE)); 

                        try {
                            $this->_executeAction($message);
                            $this->_logger->info(__CLASS__ . '::' . __LINE__ .    " job $id executed successfully");

                        } catch (Exception $e) {
                            $this->_logger->warn(__METHOD__ . '::' . __LINE__ .    " could not execute action : " . $e);
                            $this->_logger->debug(__CLASS__ . '::' . __LINE__ .    " job $id could not be executed "); 

                            $retryCount = (int) $this->_redis->hGet($this->_redisDaemonDataStructName . ":" . $id, 'retry' ) + 1;

                            if ($retryCount < $this->_config->maxRetry) {
                                $this->_logger->debug(__CLASS__ . '::' . __LINE__ .    " requeue job $id ... ");
                                
                                // NOTE: status duplicate is not yet evaluated. We need to evaluate it
                                //       if we implement worker queue pickup 
                                $this->_redis->hIncrBy($this->_redisDaemonDataStructName . ":" . $id,'retry' , 1);
                                $this->_redis->hSet($this->_redisDaemonDataStructName . ":" . $id, 'status', "duplicate");
                                $this->_redis->lPush($this->_redisDaemonQueueStructName, $id);
                                $this->_redis->lRemove($this->_redisDaemonDaemonStructName , $id);
                                $this->_redis->hSet($this->_redisDaemonDataStructName . ":" . $id, 'status', "inQueue");
                                exit(0);
                            }

                            $errMessage = "job $id could not be executed. giving up after $retryCount retries. \n$message";
                            fwrite(STDERR, "$errMessage\n");
                            $this->_logger->err(__METHOD__ . '::' . __LINE__ . " $errMessage");
                        }

                        // remove from redis
                        $this->_redis->lRemove($this->_redisDaemonDaemonStructName , $id);
                        $this->_redis->delete($this->_redisDaemonDataStructName . ":" . $id);
                        exit(0);
                    }
                    
                    continue;
                }
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see Console_Daemon::_beforeFork()
     */
    protected function _beforeFork()
    {
        unset ($this->_redis);
    }

    /**
     * @see Console_Daemon::_afterFork()
     */
    protected function _afterFork($childPid)
    {
        $this->_setupRedis();
    }

    protected function _setupRedis()
    {
        $this->_redis = new Redis;
        $this->_redis->connect($this->_config->host, $this->_config->port, $this->_config->timeout);
    }
    
    protected function _setupLogger()
    {
        $this->_logger = new Zend_Log();
        $this->_logger->addWriter(new Zend_Log_Writer_Stream($this->_config->logfile ? $this->_config->logfile : STDOUT));
        $this->_logger->addFilter(new Zend_Log_Filter_Priority((int) $this->_config->loglevel));
    }
    
    /**
     * execute the action
     *
     * @param string $_decodedMessage
     *
     */
    protected function _executeAction($message)
    {
        // execute in subprocess
        if ($this->_config->executionMethod === self::EXECUTION_METHOD_EXEC_CLI) {
            $output = system('php $paths ./../../tine20.php --method Tinebase.executeQueueJob message=' . escapeshellarg($message), $exitCode );
            if (exitCode != 0) {
                throw new Exception('Problem during execution with shell: ' . $output);
            }

        // execute in same process
        } else {
            $_SERVER['argc'] = 4;
            $_SERVER['argv'] = array('tine20.php',
                '--method', 'Tinebase.executeQueueJob',
                'message=' . $message
            );
            $opts = new Zend_Console_Getopt(array(
                 'verbose|v' => 'Output messages',
                 'method=s'  => 'Method to call [required]',
                 'message=s' => 'Message to process [required]',
            ));
            Tinebase_Core::set('opts', $opts);
            Tinebase_Core::dispatchRequest();
        }
    }
}

// auto run daemon if file is called via cli
if (strstr(implode(' ', $_SERVER['argv']), basename(__FILE__))) {
    $daemon = new Tinebase_Redis_Worker_Daemon();
    $daemon->run();
}
