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
    /**
     * the host to connect to
     * @var string
     */
    protected $_host;
    
    /**
     * the port to connect to
     * @var string
     */
    protected $_port;
    
    /**
     * the connectio to the voip system
     * @var resource
     */
    protected $_stream;
    
    /**
     * the constructor
     * 
     * @param string  $_host
     * @param int     $_port
     */
    public function __construct($_host = null, $_port = null)
    {
        if(!is_null($_host)) {
            $this->_host = $_host;
        }
        
        if(!is_null($_port)) {
            $this->_port = $_port;
        }
              
        if($this->_host !== NULL) {
            $this->connect($this->_host, $this->_port);
        }
    }
    
    /**
     * connect to host
     * 
     * @param string  $host
     * @param int     $port
     */
    public function connect($host = null, $port = null)
    {
        $host = !is_null($host) ? $host : $this->_host;
        $port = !is_null($port) ? $port : $this->_port;
        
        if(empty($host)) {
            throw new OutOfRangeException('$host can not be empty');
        }
        if(empty($port)) {
            throw new OutOfRangeException('$port can not be empty');
        }
        
        $stream = stream_socket_client("tcp://$host:$port", $errno, $errstr, 10);
        
        if($stream === FALSE) {
            throw new UnexpectedValueException("Failed to connect to host $host:$port");
        }
        
        $this->_stream = $stream;
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

