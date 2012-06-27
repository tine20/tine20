<?php
/**
 * Tine 2.0
 *
 * @package     Redis
 * @subpackage  Queue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * redis queue
 *
 * @package     Redis
 * @subpackage  Queue
 */
class Tinebase_Redis_Queue
{
    /**
     * config
     * 
     * @var array
     */
    protected $_config = array(
        'host'    => 'localhost',
        'port'    => 6379,
        'timeout' => 5,
        'queue'   => 'queue.priority.high',
    );
    
    /**
     * Redis object
     *
     * @var Redis redis object
     */
    protected $_redis = null;

    /**
     * constructor
     * 
     * @param $additionalConfig
     */
    public function __construct($additionalConfig = array())
    {
        if (! extension_loaded('redis')) {
            throw new Tinebase_Exception('The redis extension must be loaded for using redis job queue!');
        }
        
        $config = Tinebase_Config::getInstance()->get('redis', NULL);
        
        if ($config === NULL) {
            throw new Tinebase_Exception('No redis config found!');
        } else {
            $this->_config = array_merge($this->_config, $config->toArray(), $additionalConfig);
        }
        
        $this->_redis = new Redis;
        $this->_redis->connect($this->_config['host'], $this->_config['port'], $this->_config['timeout']);
    }
    
    /**
     * push data to redis queue
     * 
     * @param array $data
     */
    public function push(array $data)
    {
        $this->_redis->rPush($this->_config['queue'], json_encode($data));
    }
    
    /**
     * return queue size
     * 
     * @return integer
     */
    public function getQueueSize()
    {
        return $this->_redis->lSize($this->_config['queue']);
    }

    /**
     * pop a queue item
     * 
     * @return array|NULL
     */
    public function pop()
    {
        try {
            $data = $this->_redis->blpop($this->_config['queue'], 1);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e);
            $data = NULL;
        }
        
        return ($data) ? json_decode($data[1]) : NULL;
    }
}
