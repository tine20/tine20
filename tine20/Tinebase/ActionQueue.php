<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  ActionQueue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Action Queue
 * 
 * Method queue for deferred/async execution of Tine 2.0 application actions as defined 
 * in the application controllers 
 *
 * @package     Tinebase
 * @subpackage  ActionQueue
 *
 * @method int getQueueSize()
 * @method int getDaemonStructSize()
 * @method int waitForJob()
 * @method mixed send($message)
 * @method array receive(integer $jobId)
 * @method array getQueueKeys()
 * @method array getDaemonStructKeys()
 * @method array getData(string $key)
 * @method array iterateAllData()
 * @method void delete(integer $jobId)
 * @method void reschedule(string $jobId)
 * @method boolean hasAsyncBackend()
 * @method boolean|string peekJobId()
 *
 */
 class Tinebase_ActionQueue
 {
     const BACKEND_DIRECT = 'Direct';
     const BACKEND_REDIS  = 'Redis';
     const QUEUE_LONG_RUN = Tinebase_Config::ACTIONQUEUE_LONG_RUNNING;
     
     /**
      * holds queue instance
      * 
      * @var Tinebase_ActionQueue_Backend_Interface
      */
     protected $_queue = NULL;

     protected $_ququeName = null;
     protected $_config;

     /**
      * @var array
      */
     protected $_messageCache = [];

     /**
      * @var bool
      */
     public static $waitForTransactionManager = true;
     
    /**
     * holds the instances of the singleton
     *
     * @var array Tinebase_ActionQueue
     */
    private static $_instances = [];

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }

    /**
     * ATTENTION this needs to return really all instances! once we start introducing a 3rd and Nth queue...
     * FIXME make it generic
     * @return array<self>
     */
    public static function getAllInstances()
    {
        $instances = [static::getInstance()];
        if (($config = Tinebase_Core::getConfig()->{Tinebase_Config::ACTIONQUEUE}) &&
                $config->{Tinebase_Config::ACTIONQUEUE_ACTIVE}) {
            $longRunningFound = false;
            if ($config->{Tinebase_Config::ACTIONQUEUE_QUEUES}) {
                foreach ($config->{Tinebase_Config::ACTIONQUEUE_QUEUES} as $name => $subConfig) {
                    $instances[] = static::getInstance($name);
                    if (self::QUEUE_LONG_RUN === $name) {
                        $longRunningFound = true;
                    }
                }
            }
            if (!$longRunningFound && $config->{Tinebase_Config::ACTIONQUEUE_LONG_RUNNING}) {
                $instances[] = static::getInstance(self::QUEUE_LONG_RUN);
            }
        }
        return $instances;
    }

    /**
     * the singleton pattern
     *
     * @param string|null $_queue
     * @param string|null $_forceBackend
     * @return Tinebase_ActionQueue
     */
    public static function getInstance($_queue = null, $_forceBackend = null)
    {
        if (null === $_queue) $_queue = 0;
        if (!isset(self::$_instances[$_queue]) || null !== $_forceBackend) {
            self::$_instances[$_queue] = new Tinebase_ActionQueue($_queue ?: null, $_forceBackend);
        }
        
        return self::$_instances[$_queue];
    }
    
    /**
     * destroy instance of this class
     */
    public static function destroyInstance($_queue = null)
    {
        if (null === $_queue) $_queue = 0;
        unset(self::$_instances[$_queue]);
    }

     /**
      * returns queue status
      *
      * @todo use in \Tinebase_Frontend_Cli::monitoringCheckQueue
      */
    public static function getStatus()
    {
        $queueStatus = [
            'active' => false,
            'size' => 0,
            'problems' => [],
        ];
        $queueConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::ACTIONQUEUE);
        if ($queueConfig->{Tinebase_Config::ACTIONQUEUE_ACTIVE}) {
            $queueStatus['active'] = true;
            try {
                $queueStatus['size'] = static::getInstance()->getQueueSize();
            } catch (Exception $e) {
                $queueStatus['problems'][] = $e->getMessage();
            }
        }

        return $queueStatus;
    }
    
    /**
     * constructor
     *
     * @param string|null $_queue
     * @param string|null $_forceBackend
     */
    protected function __construct($_queue, $_forceBackend = null)
    {
        $options = [];
        $backend = null === $_forceBackend ? self::BACKEND_DIRECT : $_forceBackend;
        $this->_config = Tinebase_Core::getConfig()->{Tinebase_Config::ACTIONQUEUE};

        /** @noinspection PhpUndefinedFieldInspection */
        if ($this->_config && $this->_config->{Tinebase_Config::ACTIONQUEUE_ACTIVE}) {
            /** @noinspection PhpUndefinedFieldInspection */
            $options = $this->_config->toArray();

            if (null === $_forceBackend) {
                $backend = (isset($options[Tinebase_Config::ACTIONQUEUE_BACKEND]) || array_key_exists(Tinebase_Config::ACTIONQUEUE_BACKEND, $options)) ? ucfirst(strtolower($options[Tinebase_Config::ACTIONQUEUE_BACKEND])) : $backend;
            }
            unset($options[Tinebase_Config::ACTIONQUEUE_BACKEND]);
            unset($options[Tinebase_Config::ACTIONQUEUE_ACTIVE]);

            if (null !== $_queue) {
                if (!isset($options['queueName'])) {
                    $options['queueName'] = Tinebase_ActionQueue_Backend_Redis::QUEUE_NAME;
                }

                if (self::QUEUE_LONG_RUN !== $_queue ||
                        isset($this->_config->{Tinebase_Config::ACTIONQUEUE_QUEUES}[$_queue])) {
                    if (!isset($this->_config->{Tinebase_Config::ACTIONQUEUE_QUEUES}[$_queue])) {
                        throw new Tinebase_Exception($_queue . ' is not configured in ' .
                            Tinebase_Config::ACTIONQUEUE_QUEUES);
                    }
                    $options['queueName'] =
                        isset($this->_config->{Tinebase_Config::ACTIONQUEUE_QUEUES}[$_queue]['queueName']) ?
                            $this->_config->{Tinebase_Config::ACTIONQUEUE_QUEUES}[$_queue]['queueName'] : $_queue;
                } else {
                    $options['queueName'] .= $this->_config->{self::QUEUE_LONG_RUN} ?: '';
                }

                $this->_ququeName = $_queue;
            }
        }
        $className = 'Tinebase_ActionQueue_Backend_' . $backend;

        if (!class_exists($className)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . " Queue class name {$className} not found. Falling back to direct execution.");

            $className = Tinebase_ActionQueue_Backend_Direct::class;
        }
        $this->_queue = new $className($options);

        if (! $this->_queue instanceof Tinebase_ActionQueue_Backend_Interface) {
            throw new Tinebase_Exception_UnexpectedValue('backend does not implement Tinebase_ActionQueue_Backend_Interface');
        }
    }

     /**
      * execute action defined in queue message
      *
      * @param  array $message action
      * @return mixed
      * @throws Tinebase_Exception_AccessDenied
      * @throws Tinebase_Exception_NotFound
      */
    public function executeAction($message)
    {
        if (
            ! is_array($message)
            || ! isset($message['action'])
            || strpos($message['action'], '.') === FALSE
        ) {
            throw new Tinebase_Exception_NotFound('Could not execute action, invalid message/action param');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __LINE__ . '::' . __METHOD__ . " executing action: '{$message['action']}'");
        
        list($appName, $actionName) = explode('.', $message['action']);
        $controller = Tinebase_Core::getApplicationInstance($appName, '', true);
    
        if (! method_exists($controller, $actionName)) {
            throw new Tinebase_Exception_NotFound('Could not execute action, requested action does not exist: '
                . $message['action']);
        }
        
        return call_user_func_array(array($controller, $actionName), $message['params']);
    }
    
    /**
     * process all jobs in queue
     */
    public function processQueue()
    {
        // loop over all jobs
        while(false !== ($jobId = $this->_queue->waitForJob())) {
            $job = $this->_queue->receive($jobId);

            $this->executeAction($job);
            
            $this->_queue->delete($jobId);
        }
    }

     /** @noinspection PhpDocSignatureInspection */
     /**
     * queues an action
     *
     * @param string $_action
     * @param mixed  $_arg1
     * @param mixed  $_arg2
     * ...
     * 
     * @return string the job id
     */
    public function queueAction()
    {
        $params = func_get_args();
        $action = array_shift($params);
        $user = Tinebase_Core::getUser();
        if (! is_object($user)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . " Not Queueing action: '{$action}' because no valid user object found");
            return null;
        }

        $message = array(
            'action'     => $action,
            'account_id' => $user->getId(),
            'params'     => $params
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . " Queueing action: '{$action}'");

        if (true === static::$waitForTransactionManager && Tinebase_TransactionManager::getInstance()
                ->hasOpenTransactions()) {
            if (count($this->_messageCache) === 0) {
                Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback([$this, 'transactionCommit']);
                Tinebase_TransactionManager::getInstance()->registerOnRollbackCallback([$this, 'transactionRollback']);
            }
            $this->_messageCache[] = $message;
            return null;
        } else {
            return $this->_queue->send($message);
        }
    }

     /**
      * sends the outstanding messages to the queue once the transaction manager commits
      */
    public function transactionCommit()
    {
        foreach ($this->_messageCache as $message) {
            $this->_queue->send($message);
        }
        $this->_messageCache = [];
    }

     /**
      * in case of a rollback, all cached messages are dropped
      */
     public function transactionRollback()
     {
         $this->_messageCache = [];
     }
    
    /**
     * resume processing of events
     */
    public function resumeEvents()
    {
    }
    
    /**
     * suspend processing of event
     */
    public function suspendEvents()
    {
    }

    public function cleanDaemonStruct()
    {
        if (null !== $this->_ququeName && $this->_config->{Tinebase_Config::ACTIONQUEUE_CLEAN_DS . $this->_ququeName}) {
            $this->_queue->cleanDaemonStruct($this->_config->{Tinebase_Config::ACTIONQUEUE_CLEAN_DS . $this->_ququeName});
        } else {
            // 15 minutes
            $this->_queue->cleanDaemonStruct(15 * 60);
        }
    }

    /**
     * call function of queue backend
     * 
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->_queue, $name), $arguments);
    }

     /**
      * returns the class name of the used queue implementation
      *
      * @return string
      */
    public function getBackendType()
    {
        return get_class($this->_queue);
    }
}
