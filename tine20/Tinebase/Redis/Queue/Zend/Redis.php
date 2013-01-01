<?php
/**
 * Tine 2.0
 *
 * @package     Redis
 * @subpackage  Queue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Zend_Queue Adapter to send messages to a redis Queue
 * 
 * NOTE: This class only supports sending jobs. The receiving part of Zend_Queue 
 *       is not appropriate and therefore not implemented here!
 *       
 * @requires    PhpRedis https://github.com/nicolasff/phpredis
 * @package     Redis
 * @subpackage  Queue
 */
class Tinebase_Redis_Queue_Zend_Redis extends Zend_Queue_Adapter_AdapterAbstract
{
    /** 
     * configurations for connecting with Redis-Queues
     * 
     */
    protected $_defaultConfig = array(
        'host'              => 'localhost',
        'port'              => 6379,
        'timeout'           => 5,
        'queueName'         => 'TinebaseQueue',
        'redisQueueSuffix'  => 'Queue',
        'redisDataSuffix'   => 'Data',
    );
    
    /**
     * @var Redis
     */
    protected $_redis = null;
    
    /**
     * @var string
     */
    protected $_queueStructName = null;
    
    /**
     * @var string
     */
    protected $_dataStructName = null;
    
    /**
     * Constructor
     *
     * @param  array|Zend_Config $config An array having configuration data
     * @param  Zend_Queue The Zend_Queue object that created this class
     * @return void
     */
    public function __construct($options, Zend_Queue $queue = null)
    {
        parent::__construct($options);

        $options = &$this->_options['driverOptions'];
        foreach($this->_defaultConfig as $key => $default) {
            if (!array_key_exists($key, $options)) {
                $options[$key] = $default;
            }
        }

        $this->_queueStructName = $options['queueName'] . $options['redisQueueSuffix'];
        $this->_dataStructName  = $options['queueName'] . $options['redisDataSuffix'];

        $this->_redis = new Redis;
        $this->_redis->connect($options['host'], $options['port'], $options['timeout']);
    }

    /**
     * Does a queue already exist?
     *
     * @param  string $name
     * @return boolean
     * @throws Zend_Queue_Exception (not supported)
     */
    public function isExists($name)
    {
        require_once 'Zend/Queue/Exception.php';
        throw new Zend_Queue_Exception('isExists() is not supported in this adapter');
    }

    /**
     * Create a new queue
     *
     * @param  string  $name    queue name
     * @param  integer $timeout default visibility timeout
     * @return void
     * @throws Zend_Queue_Exception
     */
    public function create($name, $timeout=null)
    {
        require_once 'Zend/Queue/Exception.php';
        throw new Zend_Queue_Exception('create() is not supported in ' . get_class($this));
    }

    /**
     * Delete a queue and all of its messages
     *
     * @param  string $name queue name
     * @return void
     * @throws Zend_Queue_Exception
     */
    public function delete($name)
    {
        require_once 'Zend/Queue/Exception.php';
        throw new Zend_Queue_Exception('delete() is not supported in ' . get_class($this));
    }

    /**
     * Get an array of all available queues
     *
     * @return void
     * @throws Zend_Queue_Exception
     */
    public function getQueues()
    {
        require_once 'Zend/Queue/Exception.php';
        throw new Zend_Queue_Exception('getQueues() is not supported in this adapter');
    }

    /**
     * Returns the length of the queue
     *
     * @param  Zend_Queue $queue
     * @return integer
     * @throws Zend_Queue_Exception (not supported)
     */
    public function count(Zend_Queue $queue=null)
    {
        require_once 'Zend/Queue/Exception.php';
        throw new Zend_Queue_Exception('count() is not supported in this adapter');
    }

    /********************************************************************
     * Messsage management functions
     *********************************************************************/

    /**
     * Send a message to the queue
     *
     * @param  mixed $message Message to send to the active queue
     * @param  Zend_Queue|null $queue
     * @return Zend_Queue_Message
     */
    public function send($message, Zend_Queue $queue = null)
    {
        if ($queue === null) {
            $queue = $this->_queue;
        }

        $id = Tinebase_Record_Abstract::generateUID();

        $data = array(
            'id'         => $id,
            'data'       => $message,
            'md5'        => md5($message),
            'time'       => date_create()->format(Tinebase_Record_Abstract::ISO8601LONG),
            'retry'      => 0,
            'status'     => 'inQueue',
        );

        $this->_redis->hMset($this->_dataStructName . ':' . $id, $data);
        $this->_redis->lPush($this->_queueStructName, $id);

        $options = array(
            'queue' => $queue,
            'data'  => $data
        );

        $classname = $queue->getMessageClass();
        if (!class_exists($classname)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($classname);
        }
        return new $classname($options);
    }

    /**
     * Get messages in the queue
     *
     * @param  integer|null $maxMessages Maximum number of messages to return
     * @param  integer|null $timeout Visibility timeout for these messages
     * @param  Zend_Queue|null $queue
     * @return Zend_Queue_Message_Iterator
     */
    public function receive($maxMessages = null, $timeout = null, Zend_Queue $queue = null)
    {
        require_once 'Zend/Queue/Exception.php';
        throw new Zend_Queue_Exception('count() is not supported in this adapter');
    }

    /**
     * Delete a message from the queue
     *
     * Return true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param  Zend_Queue_Message $message
     * @return boolean
     */
    public function deleteMessage(Zend_Queue_Message $message)
    {
        require_once 'Zend/Queue/Exception.php';
        throw new Zend_Queue_Exception('count() is not supported in this adapter');
    }

    /********************************************************************
     * Supporting functions
     *********************************************************************/

    /**
     * Return a list of queue capabilities functions
     *
     * $array['function name'] = true or false
     * true is supported, false is not supported.
     *
     * @param  string $name
     * @return array
     */
    public function getCapabilities()
    {
        return array(
            'create'        => false,
            'delete'        => false,
            'send'          => true,
            'receive'       => false,
            'deleteMessage' => false,
            'getQueues'     => false,
            'count'         => false,
            'isExists'      => false,
        );
    }
}