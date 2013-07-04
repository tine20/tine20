<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  ActionQueue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Action Queue
 * 
 * Method queue for deferred/async execution of Tine 2.0 application actions as defined 
 * in the application controllers 
 *
 * @package     Tinebase
 * @subpackage  ActionQueue
 */
 class Tinebase_ActionQueue implements Tinebase_Controller_Interface
 {
     const BACKEND_DIRECT = 'Direct';
     const BACKEND_REDIS  = 'Redis';
     
     /**
      * holds queue instance
      * 
      * @var Zend_Queue
      */
     protected $_queue = NULL;
     
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_ActionQueue
     */
    private static $_instance = NULL;

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_ActionQueue
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_ActionQueue();
        }
        
        return self::$_instance;
    }
    
    /**
     * destroy instance of this class
     */
    public static function destroyInstance()
    {
        self::$_instance = NULL;
    }
    
    /**
     * constructor
     */
    private function __construct()
    {
        $options = null;
        $backend = self::BACKEND_DIRECT;
        
        if (isset(Tinebase_Core::getConfig()->actionqueue) && Tinebase_Core::getConfig()->actionqueue->active) {
            $options = Tinebase_Core::getConfig()->actionqueue->toArray();
            
            $backend = array_key_exists('backend', $options) ? ucfirst(strtolower($options['backend'])) : $backend;
            unset($options['backend']);
        }
        
        $className = 'Tinebase_ActionQueue_Backend_' . $backend;
        
        if (!class_exists($className)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                __METHOD__ . '::' . __LINE__ . " Queue class name {$className} not found. Falling back to direct execution.");
            
            $className = 'Tinebase_ActionQueue_Backend_Direct';
        }
    
        $this->_queue = new $className($options); 

        if (! $this->_queue instanceof Tinebase_ActionQueue_Backend_Interface) {
            throw new Tinebase_Exception_UnexpectedValue('backend does not implement Tinebase_ActionQueue_Backend_Interface');
        }
    }
    
    /**
     * execute action defined in queue message
     * 
     * @param  array  $message  action
     * @return mixed
     */
    public function executeAction($message)
    {
        if (! is_array($message) || ! array_key_exists('action', $message) || strpos($message['action'], '.') === FALSE) {
            throw new Tinebase_Exception_NotFound('Could not execute action, invalid message/action param');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . " executing action: '{$message['action']}'");
        
        list($appName, $actionName) = explode('.', $message['action']);
        $controller = Tinebase_Core::getApplicationInstance($appName);
    
        if (! method_exists($controller, $actionName)) {
            throw new Tinebase_Exception_NotFound('Could not execute action, requested action does not exist');
        }
        
        return call_user_func_array(array($controller, $actionName), $message['params']);
    }
    
    /**
     * check if the backend is async
     *  
     * @return boolean true if queue backend is async
     */
    public function hasAsyncBackend()
    {
        return ! $this->_queue instanceof Tinebase_ActionQueue_Backend_Direct;
    }
    
    /**
     * process all jobs in queue
     */
    public function processQueue()
    {
        // loop over all jobs
        while($jobId = Tinebase_ActionQueue::getInstance()->waitForJob()) {
            $job = $this->receive($jobId);
            
            $this->executeAction($job);
            
            $this->delete($jobId);
        }
    }
    
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
        $message = array(
            'action'     => $action,
            'account_id' => Tinebase_Core::getUser()->getId(),
            'params'     => $params
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . " queueing action: '{$action}'");
        
        return $this->_queue->send($message);
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
}
