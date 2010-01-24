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
set_time_limit(0);
ob_implicit_flush();
declare(ticks = 1);

/**
 * Base class for daemons
 * 
 * @package     VoipMonitor
 */
abstract class VoipMonitor_Daemon
{
    protected $_configPath;
    protected $_children = array();
    
    protected $_serverSocket;
    
    protected $_clientConnection;
    
    /**
     * 
     * @var Zend_Config_Ini
     */
    protected $_config;
    
    /**
     * @var array
     */
    protected $_options = array(
        'help|h'                => 'Display this help Message',
        'verbose|v'             => 'Output messages',
        'config=s'              => 'path to configuration file',
        'daemon|d'              => 'become a daemon'              
    );
    
    /**
     * constructor
     * 
     * @param bool $_becomeDaemon  if true forks to background
     */
    public function __construct()
    {                
        pcntl_signal(SIGTERM, array($this, "handleSigTERM"));
        pcntl_signal(SIGINT,  array($this, "handleSigINT"));
        pcntl_signal(SIGCHLD, array($this, "handleSigCHLD"));
        
        $options = $this->_getOptions();
        
        if(isset($options->daemon)) {
            $pid = $this->_becomeDaemon();
            // @todo write pid file
        }        
        
        $configPath = (isset($options->config)) ? $options->config : $this->_configPath;
        $this->_config = $this->_getConfig($configPath);
        
        // $this->_changeIdentity($uid, $gid);
    }
    
    protected function _changeIdentity($uid, $gid)
    {
        if( !posix_setgid( $gid )) { 
            print "Unable to setgid to " . $gid . "!\n";    
            exit;
        }
        
        if( !posix_setuid( $uid )) { 
            print "Unable to setuid to " . $uid . "!\n";    
            exit;
        }
    }
    
    /**
     * 
     * @param string $_path
     * @return Zend_Config_Ini
     */
    protected function _getConfig($_path)
    {
        try {
            $config = new Zend_Config_Ini($_path);
        } catch(Zend_Config_Exception $e) {
            fwrite(STDERR, "Error while parsing config file($_path) " .  $e->getMessage() . PHP_EOL);
            exit(1);
        }
        
        return $config;
    }
    
    /**
     * this function handles to main logic of the application
     */
    abstract public function run();
  
    protected function _forkChildren()
    {
        $childPid = pcntl_fork();
        
        if($childPid < 0) {
            fwrite(STDERR, "Something went wrong while forking to background" . PHP_EOL);
            exit(1);
        }
        
        // fork was successfull
        // add childPid to internal scoreboard
        if($childPid > 0) {
            $this->_children[$childPid] = $childPid;
        } else {
            // a child has no children
            $this->_children = array();
        }
        
        return $childPid;
    }
    
    /**
     * function fork into background (become a daemon)
     * @return void|number
     */
    protected function _becomeDaemon()
    {
        $childPid = pcntl_fork();
        
        if($childPid < 0) {
            fwrite(STDERR, "Something went wrong while forking to background" . PHP_EOL);
            exit;
        }
        
        // fork was successfull
        // we can finish the main process
        if($childPid > 0) {
            echo "We are master. Exiting main process now..." . PHP_EOL;
            exit;
        }
        
        // this is the code processed by the forked child
        
        //  become session leader
        posix_setsid();
        
        # chdir('/');
        # umask(0);
        
        return posix_getpid();
    }
    
    /**
     * parse commandline options
     * 
     * @return Zend_Console_Getopt
     */
    protected function _getOptions()
    {
        try {
            $opts = new Zend_Console_Getopt($this->_options);
            $opts->parse();
        } catch (Zend_Console_Getopt_Exception $e) {
           fwrite(STDOUT, $e->getUsageMessage());
           exit(1);
        }
        
        if ($opts->h) {
            fwrite(STDOUT, $opts->getUsageMessage());
            exit(0);
        }        
        
        return $opts;
    }
    
    protected function _createServerSocket($_socketName)
    {
        $socket = stream_socket_server($_socketName, $errno, $errstr);
        if (!$socket) {
            fwrite(STDERR, "Failed to open socket: $errstr ($errno)" . PHP_EOL);
            exit(1);
        }
        
        return $socket;
    }
    
    /**
     * read from socket until fgets returns false
     * 
     * @param unknown_type $_connection
     * @param int $_timeout timeout in miliseconds
     * @return string
     */
    protected function _readSocket($_connection, $_timeout = 2000)
    {
        $result = null;
        $counter = $_timeout / 100;
        
        while (is_resource($_connection) && !feof($_connection)) {
            $line = fgets($_connection);
            
            if($line === false) {
                $counter++;
                
                if($result === null && $counter < $_timeout) {
                    usleep(100000);
                    continue;
                } else {
                    break;
                }
            }        

            $result .= $line;
        }
        
        #fwrite(STDERR, 'RESULT: ' . $result . PHP_EOL);
        
        return $result;
    }
    
    protected function _writeSocket($_connection, $_data)
    {
        #fwrite(STDERR, $_data . PHP_EOL);
        
        if(!is_resource($_connection)) {
            return false;
        }
        
        $result = fwrite($_connection, $_data);
        
        return $result;
    }
    
    protected function _handleServerConnections($socket)
    {
        while(is_resource($socket)) {
            $connection = @stream_socket_accept($socket, 1);
            if($connection !== false) {
                $childPid = $this->_forkChildren();
                
                // fork was successfull
                
                // we can return to main loop if we are the master process
                if($childPid > 0) {
                    fclose($connection);
                    continue;
                }
                
                // not needed in child process
                if(is_resource($socket)) {
                    fclose($socket);
                    unset($socket);
                }
                
                stream_set_blocking($connection, 0);
                
                $this->_clientConnection = $connection;
                
                $this->_handleClient();
                
                fclose($this->_clientConnection);                
            }
        }
    }
    
    /**
     * handle signal SIGTERM
     * @param int $signal  the signal
     */
    public function handleSigTERM($signal)
    {
        fwrite(STDERR, "Handle SigTERM" . PHP_EOL);
        #var_dump($this->_children);
        foreach($this->_children as $pid) {
            #echo "Kill $pid" . PHP_EOL;
            posix_kill($pid, SIGTERM);
        }
        exit(0);
    }
    
    /**
     * handle signal SIGINT
     * @param int $signal  the signal
     */
    public function handleSigINT($signal)
    {
        $this->handleSigTERM($signal);
    }
    
    /**
     * handle signal SIGCHILD
     * @param int $signal  the signal
     */
    public function handleSigCHLD($signal)
    {
        echo posix_getpid() .  " Handle SIGCHILD" . PHP_EOL;
        while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
            echo "Child with PID $pid returned " . pcntl_wexitstatus($status) . PHP_EOL;
            unset($this->_children[$pid]);
        }
    }
}

