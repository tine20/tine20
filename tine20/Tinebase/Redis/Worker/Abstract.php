<?php
/**
 * Tine 2.0
 *
 * @package     Redis
 * @subpackage  Worker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * event hooks
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Queue.php';

/**
 * redis worker
 *
 * @package     Redis
 * @subpackage  Worker
 * 
 * @todo add test
 * @todo save/update status in redis
 * @todo finish implementation
 */
abstract class Tinebase_Redis_Worker_Abstract extends Console_Daemon
{
    protected $_jobsHandled = 0;
    
    /**
     * Redis object
     *
     * @var Tinebase_Redis_Queue redis object
     */
    protected $_redisQueue = null;

    /**
     * run worker
     */
    public function run()
    {
        while ($this->_runCondition()) {
            $job = $this->_redisQueue->pop();
            if ($job) {
                $this->doJob($job);
                $this->_jobsHandled++;
            }
        }
    }
    
    /**
     * the run condition / put something here to make the worker stop at some point
     */
    protected function _runCondition()
    {
        return TRUE;
    }
    
    /**
     * do the job
     * 
     * @param mixed $job
     */
    abstract public function doJob($job);
    
    /**
     * constructor
     * 
     * @param Zend_Config $config
     */
    public function __construct($config = NULL)
    {
        parent::__construct($config);
        
        if (! extension_loaded('redis')) {
            fwrite(STDERR, 'The redis extension must be loaded for using redis worker!' . PHP_EOL);
            exit(1);
        }
        
        if (! isset($this->_config->redis)) {
            fwrite(STDERR, 'No redis configuration found' . PHP_EOL);
            exit(1);
        }
        
        $this->_redisQueue = new Tinebase_Redis_Queue($this->_config->redis->toArray());
    }
}
