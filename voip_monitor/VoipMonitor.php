<?php
/**
 * VoIP Monitor Deamon
 * 
 * @package     VoipMonitor
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Exception.php 5149 2008-10-29 12:07:26Z p.schuele@metaways.de $
 */

/**
 * VoIP Monitor Deamon
 * 
 * @package     VoipMonitor
 */
class VoipMonitor extends VoipMonitor_Daemon
{
    protected $_hostname;
    protected $_port;
    protected $_username;
    protected $_password;
    protected $_frontend;
    
    /**
     * constructor
     *  
     * @param Zend_Config  $_config         the Zend_Config object
     * @param bool         $_becomeDaemon   if true forks to background
     */
    public function __construct(Zend_Config $_config, $_becomeDaemon = false)
    {
        $this->_hostname    = $_config->get('hostname', 'localhost');
        $this->_port        = $_config->get('port', null);
        $this->_username    = $_config->get('username', null);
        $this->_password    = $_config->get('password', null);
        $this->_frontend    = $_config->get('frontend');
        
        parent::__construct($_becomeDaemon);
    }

    /**
     * (non-PHPdoc)
     * @see VoipMonitor/VoipMonitor_Daemon#run()
     */
    public function run()
    {
        $frontend = VoipMonitor_Frontend_Factory::factory($this->_frontend);
        $frontend->connect($this->_hostname, $this->_port);
        $frontend->login($this->_username, $this->_password);
        $frontend->handleEvents();
    }  
}
