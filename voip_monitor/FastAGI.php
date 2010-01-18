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
 * VoIP Monitor Deamon
 * 
 * @package     VoipMonitor
 */
class FastAGI extends VoipMonitor_Daemon
{
    protected $_frontend;
    protected $_frontendName;
    protected $_frontendConfig;
    protected $_backendConfig;
    
    protected $_configPath = '/etc/fastAGI.ini';
    
    protected $_currentCommand;
        
    /**
     * (non-PHPdoc)
     * @see VoipMonitor/VoipMonitor_Daemon#run()
     */
    public function run()
    {
        #$this->_backend  = VoipMonitor_Backend_Factory::factory('Tine20', $this->_backendConfig);
        $this->_serverSocket = $this->_createServerSocket('tcp://localhost:8000');
        $this->_handleServerConnections($this->_serverSocket);
        //$this->_closeSocket();
    }  
        
    public function speak($_text)
    {
        $this->_execute('Flite "' . $_text. '"');
    }
    
    public function busy()
    {
        $this->_command('busy');
    }
    
    public function answer()
    {
        $this->_command('answer');
    }
    
    public function sayTime($_time, $_digits = 0)
    {
        $result = $this->_command('say time '. $_time . ' ' . $_digits);
    }
    
    public function setCallerId($_callerId)
    {
        $result = $this->_command('set callerid '. $_callerId);
    }
    
    public function verbose($_text, $_level=1)
    {
        $result = $this->_command('verbose ' . $_text . ' ' . $_level);
    }
    
    
    protected function _command($_command)
    {
        $result = $this->_writeSocket($this->_clientConnection, $_command);
        
        $response = $this->_readSocket($this->_clientConnection);
        
        echo $response . PHP_EOL;
    }
    
    protected function _execute($_command)
    {
        $command = 'exec ' . $_command;
        $result = $this->_writeSocket($this->_clientConnection, $command);
        
        $response = $this->_readSocket($this->_clientConnection);
        
        echo $response . PHP_EOL;
    }
    
    protected function _handleClient()
    {
        $variables = $this->_loadAGIVariables();

        if(!array_key_exists('agi_network_script', $variables)) {
          return;
        }

        $className = 'FastAGI_' . $variables['agi_network_script'];

        if(!@class_exists($className)) {
          echo "class $className not found" . PHP_EOL;
          return;
        }

        $application = new $className($this, $variables);
    }
    
    /**
     * load agi variables from stream
     * 
     * @return array
     */
    protected function _loadAGIVariables()
    {
        $variables = array();
        $request   = $this->_readSocket($this->_clientConnection);
        $rows      = explode("\n", trim($request));

        foreach($rows as $row) {
            list($key, $value) = explode(': ', $row);
            $variables[$key] = $value;
        }
        
        return $variables;
    }
    
    /**
     * (non-PHPdoc)
     * @see VoipMonitor/VoipMonitor_Daemon#handleSigTERM($signal)
     */
    #public function handleSigTERM($signal)
    #{
    #    echo "Caught SigTERM/INT... " . PHP_EOL;
    #    $this->_frontend->stopHandleEvents();
    #    $this->_backend->logout();
    #    //exit(); 
    #}
}
