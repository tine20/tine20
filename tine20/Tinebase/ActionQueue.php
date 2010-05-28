<?php
/**
 * Action Queue 
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Action Queue
 * 
 * Method queue for defered/async execution of Tine 2.0 application actions as defined 
 * in the application controllers 
 *
 * @package     Tinebase
 */
 class Tinebase_ActionQueue
 {
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
     * constructor
     * 
     */
    private function __construct()
    {
        if(isset(Tinebase_Core::getConfig()->actionqueue)) {
            $options = Tinebase_Core::getConfig()->actionqueue->toArray();
            
            $adapter = $options['adapter'];
            unset($options['adapter']);
            
            $options['name'] = $options['name'] ? $options['name'] : SQL_TABLE_PREFIX . 'actionqueue';
            
            $this->_queue = new Zend_Queue($adapter, $options);
        }
    }
    
    /**
     * returns queue
     * 
     * @return Zend_Queue
     */
    public function getAdapter()
    {
        return $this->_queue;
    }
    
    /**
     * queues an action
     * 
     * @param string $_action
     * @param mixed  $_arg1
     * @param mixed  $_arg2
     * ...
     * 
     * @return string
     */
    public function queueAction()
    {
        $params = func_get_args();
        $action = array_shift($params);
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " queuing action: '{$action}'");
        
        try {
            $message = serialize(array(
                'action' => $action,
                'params' => $params
            ));
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . $message);
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " could not create message for action: '{$action}'");
            return;
        }
        
        if ($this->_queue) {
            $this->_queue->send($message);
        } else {
            // execute action imideately if no queue service is available
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " no queue configured -> directly execute action: '{$action}'");
            $this->_executeAction($message);
        }
    }
    
    /**
     * execute action defined in queue message
     * 
     * @param string $_message JSON encoded string
     * @return void
     */
    protected function _executeAction($_message)
    {
        try {
            $decodedMessage = unserialize($_message);
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " could not decode message -> aboarting execution");
            return;
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " executing action: '{$decodedMessage['action']}'");
        
        try {
            list($appName, $actionName) = explode('.', $decodedMessage['action']);
            $controller = Tinebase_Core::getApplicationInstance($appName);
        
            if (! method_exists($controller, $actionName)) {
                throw new Exception('Could not execute action, requested action does not exist');
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " could not execute action :" . $e);
            return;
        }
        
        call_user_func_array(array($controller, $actionName), $decodedMessage['params']);
    }
 }