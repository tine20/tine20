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
        $socketPath = $this->_config->general->get('socket', 'tcp://localhost:4573');
        $this->_serverSocket = $this->_createServerSocket($socketPath);
        $this->_handleServerConnections($this->_serverSocket);
        #$this->_closeSocket();
    }  

    /**
     * execute agi busy command
     */
    public function busy()
    {
        $this->_command('busy');
    }

    /*
     * execute agi answer command
     */
    public function answer()
    {
        $this->_command('answer');
    }
    
    /*
     * execute agi hangup command
     */
    public function hangup()
    {
        $this->_command('HANGUP');
    }
    
    /**
     * execute agi get data command
     * 
     * @param $_filename
     * @param $_timeout
     * @param $_maxDigits
     * @return unknown_type
     */
    public function getData($_filename, $_timeout=2000, $_maxDigits = null)
    {
        $result = $this->_command('GET DATA '. $_filename . ' ' . $_timeout . ' '. $_maxDigits);
    }
    
    /**
     * execute agi send text command
     * 
     * @param $_text
     * @return unknown_type
     */
    public function sendText($_text)
    {
        $result = $this->_command('SEND TEXT "'. $_text . '"');
    }
    
    /**
     * execute agi noop command
     * 
     * @param $_text
     * @return unknown_type
     */
    public function noop($_text)
    {
        $result = $this->_command('NOOP "'. $_text . '"');
    }
    
    /**
     * 
     * @param $_filename
     * @param $_format
     * @param $_escapeDigits
     * @param $_timeout
     * @param $_beep
     * @param $_offset
     * @param $_silence
     * @return unknown_type
     */
    public function recordFile($_filename, $_format, $_escapeDigits, $_timeout, $_beep = false, $_offset = 0, $_silence = false)
    {
        $beep = $_beep !== false ? 'BEEP' : '';
        
        $result = $this->_command("RECORD FILE $_filename $_format $_escapeDigits $_timeout $_offset $beep");
    }
    
    /**
     * 
     * @param $_variable
     * @param $_value
     * @return unknown_type
     */
    public function setVariable($_variable, $_value)
    {
        $result = $this->_command('SET VARIABLE '. $_variable . ' "' . $_value . '"');
    }
    
    /**
     * 
     * @param $_filename
     * @param $_escapeDigits
     * @param $_offset
     * @return unknown_type
     */
    public function streamFile($_filename, $_escapeDigits = null, $_offset = null)
    {
        $escapeDigits = $_escapeDigits !== null ? $_escapeDigits : '""';
        $offset       = $_offset       !== null ? " $_offset"    : '';
        
        $result = $this->_command("STREAM FILE $_filename $escapeDigits$offset");
    }
    
    /**
     * 
     * @param $_digits
     * @param $_escapeDigits
     * @return unknown_type
     */
    public function sayDigits($_digits, $_escapeDigits = null)
    {
        $escapeDigits = $_escapeDigits !== null ? $_escapeDigits : '""';
        
        $result = $this->_command('SAY DIGITS '. $_digits . ' ' . $escapeDigits);
    }
    
    /**
     * 
     * @param $_time
     * @param $_digits
     * @return unknown_type
     */
    public function sayTime($_time, $_digits = 0)
    {
        $result = $this->_command('SAY TIME '. $_time . ' ' . $_digits);
    }
    
    /**
     * 
     * @param $_callerId
     * @return unknown_type
     */
    public function setCallerId($_callerId)
    {
        $result = $this->_command('set callerid '. $_callerId);
    }
    
    /**
     * 
     * @param $_text
     * @param $_level
     * @return unknown_type
     */
    public function verbose($_text, $_level=1)
    {
        $result = $this->_command('verbose "' . $_text . '" ' . $_level);
    }
    
    /**
     * waits until users pressed a digit
     * 
     * @param int $_timeout timeout in miliseconds
     * @return string
     */
    public function waitForDigit($_timeout)
    {
        $response = $this->_command('WAIT FOR DIGIT ' . $_timeout, $_timeout + 1000);
        
        $responseCode = substr($response, 7);
        
        if($responseCode == 0) {
            // timeout
            $result = null;
        } else {
            $result = chr(substr($response, 7));
        }
        
        return $result;
    }
    
    /**
     * 
     * @param unknown_type $_command
     * @param unknown_type $_timeout
     * @return unknown_type
     */
    protected function _command($_command, $_timeout = 2000)
    {
        $result = $this->_writeSocket($this->_clientConnection, $_command);
        
        $response = $this->_readSocket($this->_clientConnection, $_timeout);
        
        if(substr($response, 0 ,3) != '200') {
            throw new Exception('Error during command: ' . $_command . PHP_EOL . 'Result: ' . $response);
        }
        
        $result = substr($response, 4);
        echo $result . PHP_EOL;
        
        return $result;
    }
    
    /**
     * 
     * @param unknown_type $_command
     * @return unknown_type
     */
    protected function _execute($_command)
    {
        $command = 'EXEC ' . $_command;
        $result = $this->_writeSocket($this->_clientConnection, $command);
        
        $response = $this->_readSocket($this->_clientConnection);
        
        echo $response . PHP_EOL;
    }
    
    /**
     * (non-PHPdoc)
     * @see VoipMonitor/VoipMonitor_Daemon#_handleClient()
     */
    protected function _handleClient()
    {
        try {
            $variables = $this->_loadAGIVariables();
    
            $className = $variables['className'];
            $method    = $variables['method'];
        
            if(!@class_exists($className)) {
                throw new UnexpectedValueException("class $className not found");
            }
            
            $reflectionClass = new Zend_Reflection_Class($className);
            if(!$reflectionClass->hasMethod($method)) {
                throw new UnexpectedValueException("method $method not found in class $className");
            }
            
            $params = array();
            $reflectionMethod = $reflectionClass->getMethod($method);
            foreach($reflectionMethod->getParameters() as $parameter) {
                if(array_key_exists($parameter->name, $variables)) {
                    $params[$parameter->name] = $variables[$parameter->name];
                }
            }
    
            $application = new $className($this, $variables);
            
            call_user_func_array(array($application, $method), $params);
        } catch (Exception $e) {
            $this->_writeSocket($this->_clientConnection, $e->getMessage() . PHP_EOL);
        } 
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

        $rows = explode("\n", trim($request));
        foreach($rows as $row) {
            list($key, $value) = explode(': ', $row);
            $variables[$key] = $value;
        }

        if(!array_key_exists('agi_request', $variables)) {
            throw new UnexpectedValueException('AGI variable agi_request not found');
        }
        
        $requestInfo = parse_url($variables['agi_request']);
        $variables['className'] = 'FastAGI_' . substr($requestInfo['path'], 1);
        
        $rows = explode("&", $requestInfo['query']);
        foreach($rows as $row) {
            list($key, $value) = explode('=', $row);
            $variables[$key] = $value;
        }
        
        if(!array_key_exists('method', $variables)) {
            $variables['method'] = 'processRequest';
        }
        
        return $variables;
    }    
}
