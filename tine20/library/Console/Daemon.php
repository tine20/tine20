<?php
/**
 * Console_Daemon
 * 
 * @package     Console
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

// TODO moved this to helper script, where is the right place for this?
/*
set_time_limit(0);
ob_implicit_flush();
declare(ticks = 1);
*/

/**
 * Base class for console daemons
 * 
 * @package     Console
 */
abstract class Console_Daemon
{
    protected $_defaultConfigPath;
    
    protected $_children = array();
    
    protected $_verbose = FALSE;
    
    /**
     * 
     * @var Zend_Config
     */
    protected $_config;
    
    /**
     * @var array
     */
    protected $_options = array(
        'help|h'        => 'Display this help Message',
        'verbose|v'     => 'Output messages',
        'config=s'      => 'path to configuration file',
        'daemonize|d'   => 'become a daemon (fork to background)'
    );
    
    /**
     * constructor
     * 
     * @param Zend_Config $config
     */
    public function __construct($config = NULL)
    {
        pcntl_signal(SIGTERM, array($this, "handleSigTERM"));
        pcntl_signal(SIGINT,  array($this, "handleSigINT"));
        pcntl_signal(SIGCHLD, array($this, "handleSigCHLD"));
        
        if ($config === NULL) {
            $options = $this->_getOptions();
            if (isset($options->d)) {
                $pid = $this->_becomeDaemon();
                // @todo write pid file
            }
            
            if (isset($options->v)) {
                $this->_verbose = TRUE;
            }
            
            $configPath = (isset($options->config)) ? $options->config : $this->_defaultConfigPath;
            $this->_config = $this->_loadConfig($configPath);
        } else {
            $this->_config = $config;
        }
        
        if ($this->_verbose) {
            fwrite(STDOUT, "Starting Console_Daemon ..." . PHP_EOL);
        }
        
        if (isset($this->_config->general) && isset($this->_config->general->user) && isset($this->_config->general->group)) {
            $this->_changeIdentity($this->_config->general->user, $this->_config->general->group);
        }
    }
    
    abstract public function run();
    
    public function getConfig()
    {
        return $this->_config;
    }
    
    protected function _changeIdentity($_username, $_groupname)
    {
        if(($userInfo = posix_getpwnam($_username)) === false) {
            throw new RuntimeException("user $_username not found");
        }
        
        if(($groupInfo = posix_getgrnam($_groupname)) === false) {
            throw new RuntimeException("group $_groupname not found");
        }

        if(posix_setgid($groupInfo['gid']) !== true) { 
            throw new RuntimeException("failed to change group to $_groupname");
        }
        
        if(posix_setuid($userInfo['uid']) !== true) { 
            throw new RuntimeException("failed to change user to $_username");        
        }
    }
    
    /**
     * 
     * @param string $_path
     * @return Zend_Config_Ini
     */
    protected function _loadConfig($_path)
    {
        try {
            $config = new Zend_Config_Ini($_path);
        } catch (Zend_Config_Exception $e) {
            fwrite(STDERR, "Error while parsing config file($_path) " .  $e->getMessage() . PHP_EOL);
            exit(1);
        }
        
        return $config;
    }
      
    protected function _forkChildren()
    {
        $childPid = pcntl_fork();
        
        if($childPid < 0) {
            #fwrite(STDERR, "Something went wrong while forking to background" . PHP_EOL);
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
        
        if ($childPid < 0) {
            #fwrite(STDERR, "Something went wrong while forking to background" . PHP_EOL);
            exit;
        }
        
        // fork was successfull
        // we can finish the main process
        if ($childPid > 0) {
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
    
    /**
     * handle signal SIGTERM
     * @param int $signal  the signal
     */
    public function handleSigTERM($signal)
    {
        if ($this->_verbose) {
            fwrite(STDOUT, "Sigterm received" . PHP_EOL);
        }
        
        foreach($this->_children as $pid) {
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
        while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
            unset($this->_children[$pid]);
        }
    }
}