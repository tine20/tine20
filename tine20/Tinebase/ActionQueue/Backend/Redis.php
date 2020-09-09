<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  ActionQueue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2012-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Zend_RedisProxy as Redis;

/**
 * @package     Tinebase
 * @subpackage  ActionQueue
 */
class Tinebase_ActionQueue_Backend_Redis implements Tinebase_ActionQueue_Backend_Interface
{
    const QUEUE_NAME = 'TinebaseQueue';

    /** 
     * configurations for connecting with Redis-Queues
     * 
     */
    public static $defaultConfig = array(
        'host'              => 'localhost',
        'port'              => 6379,
        'timeout'           => 5,
        'maxRetry'          => 10,
        'queueName'         => self::QUEUE_NAME,
        'redisQueueSuffix'  => 'Queue',
        'redisDataSuffix'   => 'Data',
        'redisDaemonSuffix' => 'Daemon',
        'redisDeadLetterSuffix' => 'DeadLetter',
    );
    
    /**
     * 
     * @var array
     */
    protected $_options = array();
    
    /**
     * @var Redis
     */
    protected $_redis;
    
    /**
     * @var string
     */
    protected $_queueStructName;
    
    /**
     * @var string
     */
    protected $_dataStructName;

    protected $_dataStructIterator = null;
    
    /**
     * @var string
     */
    protected $_daemonStructName;

    /**
     * @var string
     */
    protected $_deadLetterStructName;
    
    /**
     * Constructor
     *
     * @param  array  $options  An array having configuration data
     */
    public function __construct($options)
    {
        if (! extension_loaded('redis') ) {
            throw new Tinebase_Exception_UnexpectedValue('Redis extension not found, but required!');
        }
        
        $this->_options = self::$defaultConfig;
        
        $this->_options = array_merge($this->_options, $options);

        $this->_queueStructName  = $this->_options['queueName'] . $this->_options['redisQueueSuffix'];
        $this->_dataStructName   = $this->_options['queueName'] . $this->_options['redisDataSuffix'];
        $this->_daemonStructName = $this->_options['queueName'] . $this->_options['redisDaemonSuffix'];
        $this->_deadLetterStructName = $this->_options['queueName'] . $this->_options['redisDeadLetterSuffix'];

        // Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' options: ' . print_r($this->_options, true));

        $this->connect();
    }

    /**
     * connect to redis server
     * 
     * @param string $host
     * @param integer $port
     * @param integer $timeout
     * @throws Tinebase_Exception_Backend
     */
    public function connect($host = null, $port = null, $timeout = null)
    {
        if ($this->_redis instanceof Redis) {
            $this->_redis->close();
        }
        
        $host    = $host    ? $host    : $this->_options['host'];
        $port    = $port    ? $port    : $this->_options['port'];
        $timeout = $timeout ? $timeout : $this->_options['timeout'];
        
        $this->_redis = new Redis;
        if (! $this->_redis->connect($host, $port, $timeout)) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Could not connect to redis server at ' . $host);
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' options: ' . print_r($this->_options, true));
            throw new Tinebase_Exception_Backend('Could not connect to redis server at ' . $host);
        }
    }
    
    /**
     * Delete a job from the queue
     *
     * @param  string  $jobId  the id of the job
     * @param  boolean $deadLetter wether to push msg to dead letter queue, defaults false
     */
    public function delete($jobId, $deadLetter = false)
    {
        if ($deadLetter) {
            $data = $this->_redis->dump($this->_dataStructName . ":" . $jobId);
            if (false !== $data) {
                $this->_redis->restore($this->_deadLetterStructName . ":" . $jobId, 0, $data);
            }
        }
        // remove from redis
        $this->_redis->lRemove($this->_daemonStructName ,     $jobId, 0);
        $this->_redis->delete ($this->_dataStructName . ":" . $jobId);
    }
    
    /**
     * return queue length
     * @return int the queue length
     */
    public function getQueueSize()
    {
        return $this->_redis->lLen($this->_queueStructName);
    }

    /**
     * return daemon struct length
     * @return int the daemon struct length
     */
    public function getDaemonStructSize()
    {
        return $this->_redis->lLen($this->_daemonStructName);
    }

    public function cleanDaemonStruct($maxTime)
    {
        foreach ($this->_redis->lRange($this->_daemonStructName, 0, 1000) as $jobId) {
            $data = $this->_redis->hMGet(
                $this->_dataStructName . ':' . $jobId, ['time']
            );

            if (!is_array($data) || !isset($data['time'])) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ .
                    ' hMGet did not return valid data, deleting, possibly deadlettering job');
                $this->delete($jobId, true);
            }
            if ($data['time'] + $maxTime < time()) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . 'job timed out in daemon struct!');
                $this->reschedule($jobId);
            }
        }
    }

    public function iterateAllData()
    {
        if (null !== $this->_dataStructIterator && 1 > $this->_dataStructIterator) {
            $this->_dataStructIterator = null;
            return false;
        }

        while (false === ($result = $this->_redis->scan($this->_dataStructIterator, $this->_dataStructName . '*')) &&
                $this->_dataStructIterator > 0) ;

        return $result;
    }

    public function getData($jobId)
    {
        return $this->_redis->hgetall($this->_dataStructName . ':'. $jobId);
    }

    public function getQueueKeys($max = 10000)
    {
        return $this->_redis->lRange($this->_dataStructName, 0, $max);
    }

    public function getDaemonStructKeys($max = 10000)
    {
        return $this->_redis->lRange($this->_daemonStructName, 0, $max);
    }
    
    /**
     * get one job from the queue
     *
     * @param  integer  $jobId  the id of the job
     * @throws RuntimeException
     * @return array           the job
     */
    public function receive($jobId)
    {
        $data = $this->_redis->hMGet(
            $this->_dataStructName . ':' . $jobId,
            array('data', 'sha1', 'retry', 'status')
        );
        
        if (sha1($data['data']) != $data['sha1']) {
            $e = new RuntimeException('sha1 checksum mismatch');
            Tinebase_Exception::log($e, null, $data);
            throw $e;
        }
        
        if ($data['retry'] >= $this->_options['maxRetry']) {
            throw new RuntimeException('max retry count reached');
        }
        
        $message = unserialize($data['data']);
        
        return $message;
    }
    
    /**
     * reschedule a job
     * 
     * @param string $jobId
     */
    public function reschedule($jobId)
    {
        /** @noinspection PhpInternalEntityUsedInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        $this->_redis->multi()
            ->hIncrBy($this->_dataStructName . ":" . $jobId, 'retry' , 1)
            ->hSet($this->_dataStructName . ':' . $jobId, 'time', time())
            ->lRemove($this->_daemonStructName , $jobId)
            ->lPush  ($this->_queueStructName,   $jobId)
            ->exec();
    }
    
    /**
     * Send a message to the queue
     *
     * @param  mixed   $message  Message to send to the active queue
     * @return string|null     job id
     */
    public function send($message)
    {
        try {
            $encodedMessage = serialize($message);
            
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . " failed to serialize message: '{$message}'");
            Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . " exception message: " . $e->getMessage());
            Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . " exception stacktrace: " . $e->getTraceAsString());
            
            return null;
        }
        
        $jobId = Tinebase_Record_Abstract::generateUID();

        $data = array(
            'data'       => $encodedMessage,
            'sha1'       => sha1($encodedMessage),
            'time'       => time(),
            'retry'      => 0,
            'status'     => 'inQueue',
        );

        // run in transaction
        /** @noinspection PhpInternalEntityUsedInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        $this->_redis->multi()
            ->hMset($this->_dataStructName . ':' . $jobId, $data)
            ->lPush($this->_queueStructName, $jobId)
            ->exec();

        Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . " queued job " . $jobId . " on queue " . $this->_queueStructName
            . " (datastructname: " . $this->_dataStructName . ")"
        );

        return $jobId;
    }
    
    /**
     * wait for a new job in queue
     *
     * @param  integer  $timeout  
     * @return mixed              false on timeout or job id
     */
    public function waitForJob($timeout = 1)
    {
        $jobId = $this->_redis->brpoplpush($this->_queueStructName, $this->_daemonStructName, $timeout);
        
        // did we run into a timeout
        if ($jobId === FALSE || $jobId === '*-1') {
            return FALSE;
        }

        $this->_redis->hSet($this->_dataStructName . ':' . $jobId, 'time', time());
        
        return $jobId;
    }

    /**
     * @return boolean|false
     * @throws RedisException
     */
    public function peekJobId()
    {
        return $this->_redis->lGet($this->_queueStructName, -1);
    }

    /**
     * check if the backend is async
     *
     * @return boolean true if queue backend is async
     */
    public function hasAsyncBackend()
    {
        return true;
    }
}
