<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  ActionQueue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * the class does not queue the message but executes them immediately
 * 
 * @package     Tinebase
 * @subpackage  ActionQueue
 */
class Tinebase_ActionQueue_Backend_Direct implements Tinebase_ActionQueue_Backend_Interface
{
    protected $_options;
    
    /**
     * Constructor
     *
     * @param  array  $config  An array having configuration data
     * @return void
     */
    public function __construct($options)
    {
        $this->_options = $options;
    }

    /**
     * Send a message to the queue
     *
     * @param  mixed $message Message to send to the active queue
     * @return Zend_Queue_Message
     */
    public function send($message)
    {
        Tinebase_ActionQueue::getInstance()->executeAction($message);
    }
    
    /**
     * return queue length
     * @return int the queue length
     */
    public function getQueueSize()
    {
        return 0;
    }
    
    /**
     * wait for a new job in queue
     *
     * @param  integer  $timeout  
     * @return mixed              false on timeout or job id
     */
    public function waitForJob($timeout = 1)
    {
        return FALSE;
    }
}