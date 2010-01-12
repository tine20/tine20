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
    protected $_frontend;
    protected $_frontendName;
    protected $_frontendConfig;
    protected $_backendConfig;
    
    /**
     * constructor
     *  
     * @param Zend_Config  $_config         the Zend_Config object
     * @param bool         $_becomeDaemon   if true forks to background
     */
    public function __construct(Zend_Config $_config, $_becomeDaemon = false)
    {
        foreach($_config as $section => $config) {
            $sectionUc = ucfirst(strtolower($section));
            if(is_null($this->_frontendConfig) && @class_exists('VoipMonitor_Frontend_' . $sectionUc)) {
                $this->_frontendName  = $sectionUc;
                $this->_frontendConfig = $config;
            } elseif(strtolower($section) == 'tine20') {
                $this->_backendConfig = $config;
            }
        } 
        
        parent::__construct($_becomeDaemon);
    }

    /**
     * (non-PHPdoc)
     * @see VoipMonitor/VoipMonitor_Daemon#run()
     */
    public function run()
    {
        $this->_frontend = VoipMonitor_Frontend_Factory::factory($this->_frontendName, $this->_frontendConfig);
        $this->_backend  = VoipMonitor_Backend_Factory::factory('Tine20', $this->_backendConfig);
        
        $this->_frontend->attach($this->_backend);
        
        $this->_frontend->handleEvents();
    }  
    
    /**
     * (non-PHPdoc)
     * @see VoipMonitor/VoipMonitor_Daemon#handleSigTERM($signal)
     */
    public function handleSigTERM($signal)
    {
        echo "Caught SigTERM/INT... " . PHP_EOL;
        $this->_frontend->stopHandleEvents();
        $this->_backend->logout();
        //exit(); 
    }
}
