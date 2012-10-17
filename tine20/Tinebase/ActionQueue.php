<?php
/**
 * Action Queue 
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        move config options to config table
 */

/**
 * Action Queue
 * 
 * Method queue for deferred/async execution of Tine 2.0 application actions as defined 
 * in the application controllers 
 *
 * @package     Tinebase
 */
 class Tinebase_ActionQueue implements Tinebase_Controller_Interface
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
     * suspend processing of event
     */
    public function suspendEvents()
    {
    }

    /**
     * resume processing of events
     */
    public function resumeEvents()
    {
    }
    
    /**
     * constructor
     * 
     * @see http://framework.zend.com/manual/en/zend.queue.adapters.html for config options
     */
    private function __construct()
    {
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            $options = Tinebase_Core::getConfig()->actionqueue->toArray();
            
            $adapter = array_key_exists('adapter', $options) ? $options['adapter'] : 'Db';
            unset($options['adapter']);
            
            $options['name'] = array_key_exists('name', $options) ? $options['name'] : 'tine20actionqueue';
            
            switch ($adapter) {
                case 'Redis':
                    $options['adapterNamespace'] = 'Tinebase_Redis_Queue_Zend';
                    $options['driverOptions'] = (array_key_exists('driverOptions', $options)) ? $options['driverOptions'] : array ( 'namespace' => 'Application_' );
                    break;
                    
                case 'Db':
                    // use default db settings if empty
                    $options['driverOptions'] = (array_key_exists('driverOptions', $options)) ? $options['driverOptions'] : Tinebase_Core::getConfig()->database->toArray();
                    if (! array_key_exists('type', $options['driverOptions'])) {
                        $options['driverOptions']['type'] = (array_key_exists('adapter', $options['driverOptions'])) ? $options['driverOptions']['adapter'] : 'pdo_mysql';
                    }
                    
                    break;
            }
            
            try {
                $this->_queue = new Zend_Queue($adapter, $options);
            } catch (Zend_Db_Adapter_Exception $zdae) {
                throw new Tinebase_Exception_Backend_Database('Could not connect to queue DB: ' . $zdae->getMessage());
            }
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
        $decodedAction = array(
            'action' => $action,
            'params' => $params
        );
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " queuing action: '{$action}'");
        
        try {
            $message = serialize($decodedAction);
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . $message);
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " could not create message for action: '{$action}'");
            return;
        }
        
        if ($this->_queue) {
            $this->_queue->send($message);
        } else {
            // execute action immediately if no queue service is available
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " no queue configured -> directly execute action: '{$action}'");
            try {
                $this->executeAction($message);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " could not execute action :" . $e);
            }
        }
    }
    
    /**
     * process number of messages in queue
     * 
     * @param integer $_numberOfMessagesToProcess
     */
    public function processQueue($_numberOfMessagesToProcess = 5)
    {
        if ($this->_queue && count($this->_queue) > 0) {
            $numberToProcess = min(array($_numberOfMessagesToProcess, count($this->_queue)));
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' processing messages: ' . $numberToProcess . ' of ' . count($this->_queue));
            
            $messages = $this->_queue->receive($numberToProcess);
 
            foreach ($messages as $i => $message) {
                try {
                    $this->executeAction($message->body);
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " could not execute action :" . $e);
                }
                $this->_queue->deleteMessage($message);
            }
        }
    }
    
    /**
     * execute action defined in queue message
     * 
     * @param string $_message serialized action
     * @return void
     */
    public function executeAction($_message)
    {
        $decodedMessage = unserialize($_message);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " executing action: '{$decodedMessage['action']}'");
        
        return $this->_executeDecodedAction($decodedMessage);
    }
    
    /**
     * execute the decoded action
     * 
     * @param array $_decodedMessage
     * @throws Exception
     */
    protected function _executeDecodedAction($_decodedMessage)
    {
        list($appName, $actionName) = explode('.', $_decodedMessage['action']);
        $controller = Tinebase_Core::getApplicationInstance($appName);
    
        if (! method_exists($controller, $actionName)) {
            throw new Exception('Could not execute action, requested action does not exist');
        }
        
        call_user_func_array(array($controller, $actionName), $_decodedMessage['params']);
    }
}
