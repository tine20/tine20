<?php
/**
 * VoIP Monitor Deamon
 * 
 * @package     VoipMonitor
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * abstract class for VoipMonitor frontends
 * 
 * @package     VoipMonitor
 */
abstract class VoipMonitor_Frontend_Abstract
{
    protected $_frontendDefaultSocket;
    
    protected $_frontendConfig;
    
    // Stores all observers
    protected $_observers = array();
    
    /**
     * the connection to the voip system
     * @var resource
     */
    protected $_stream;
    
    /**
     * the constructor
     * 
     * @param string  $_host
     * @param int     $_port
     */
    public function __construct(Zend_Config $_frontendConfig)
    {
        $this->_frontendConfig = $_frontendConfig;
              
        $socket = $_frontendConfig->get('socket', $this->_frontendDefaultSocket);
        if($socket !== NULL) {
            $this->connect($socket);
        }
        
        $username = $_frontendConfig->get('username', NULL);
        $password = $_frontendConfig->get('password', NULL);
        if($username !== NULL) {
            $this->login($username, $password);
        }
    }
    
    /**
     * connect to host
     * 
     * @param string  $_socket
     */
    public function connect($_socket)
    {
        if(empty($_socket)) {
            throw new OutOfRangeException('$_socket can not be empty');
        }
        
        $stream = stream_socket_client($_socket, $errno, $errstr, 10);
        
        if($stream === FALSE) {
            throw new UnexpectedValueException("Failed to connect to host $_socket");
        }
        
        $this->_stream = $stream;
    }
    
    public function stopHandleEvents()
    {
        $this->_stream = null;
    }
    
    /**
     * Needed to register observers
     * 
     * Note the typehinting here.
     */
    public function attach(VoipMonitor_Backend_Abstract $observer) {
        $this->_observers[]= $observer;
    }

    /**
     * Needed to unregister observers
     * 
     * Note the typehinting here.
     */
    public function detach(VoipMonitor_Backend_Abstract $observer) {
        // Not implemented here
    }

    /**
     * Notify all Observers that we have changed
     */
    public function notify($_event) {
        foreach ($this->_observers as $obj) {
            $obj->update($_event);
        }
    }
    
    /**
     * authenticate 
     *  
     * @param string $_username
     * @param string $_password
     */
    abstract public function login($_username, $_password);
    
    /**
     * handle events from VoIP system
     */
    abstract public function handleEvents();
}

