<?php
/**
 * Console_Daemon
 * 
 * @package     Console
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */


/**
 * Base class for console daemons
 * 
 * @package     Console
 */
abstract class Console_Daemon
{
    /**
     * 
     * @var array
     */
    protected $_children = array();
    
    /**
     * @var Zend_Log
     */
    protected $_logger;
    
    /**
     * @var Zend_Config
     */
    protected $_config;

    protected $_isChild = false;
    
    /**
     * @var array $_defaultConfig
     */
    protected static $_defaultConfig = array(
        'general' => array(
            'configfile' => null, 
            'pidfile'    => null,
            'daemonize'  => 0,
            'logfile'    => null, //STDOUT
            'loglevel'   => 3
        )
    );
    
    /**
     * @var array
     */
    protected $_options = array(
        'help|h'        => 'Display this help Message',
        'config=s'      => 'path to configuration file',
        'daemonize|d'   => 'become a daemon (fork to background)',
        'pidfile|p=s'   => 'deamon pid file path',
    );
    
    /**
     * constructor
     * 
     * @param Zend_Config $config
     */
    public function __construct($config = NULL)
    {
        pcntl_signal(SIGCHLD, array($this, "handleSigCHLD"));
        pcntl_signal(SIGHUP,  array($this, "handleSigHUP"));
        pcntl_signal(SIGINT,  array($this, "handleSigINT"));
        pcntl_signal(SIGTERM, array($this, "handleSigTERM"));

        /** @noinspection PhpUndefinedFieldInspection */
        if ($this->_getConfig()->general->daemonize == 1) {
            $this->_becomeDaemon();
        }
        
        $this->_getLogger()->info(__METHOD__ . '::' . __LINE__ .    " Started with pid: " . posix_getpid());

        /** @noinspection PhpUndefinedFieldInspection */
        if (isset($this->_getConfig()->general) && isset($this->_getConfig()->general->user) && isset($this->_getConfig()->general->group)) {
            /** @noinspection PhpUndefinedFieldInspection */
            $this->_changeIdentity($this->_getConfig()->general->user, $this->_getConfig()->general->group);
        }
    }
    
    /**
     * get Zend_Config object
     * 
     * @return Zend_Config
     */
    public function getConfig()
    {
        return $this->_getConfig();
    }
    
    /**
     * get default config 
     * 
     * @return array
     */
    public static function getDefaultConfig()
    {
        return array_merge(self::$_defaultConfig, static::$_defaultConfig);
    }
    
    /**
     * get configured pidfile
     */
    public function getPidFile()
    {
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->_config->general->pidfile;
    }
    
    abstract public function run();
    
    /**
     * 
     * @param  string  $_username
     * @param  string  $_groupname
     * @throws RuntimeException
     */
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
     * handle terminated children
     *
     * @param string $pid
     * @param string $status
     */
    protected function _childTerminated($pid, /** @noinspection PhpUnusedParameterInspection */$status)
    {
        unset($this->_children[$pid]);
    }
    
    /**
     * @return Zend_Config
     */
    protected function _getConfig()
    {
        if (! $this->_config instanceof Zend_Config) {
            $this->_config = new Zend_Config(self::getDefaultConfig(), TRUE);
            
            $this->_parseOptions($this->_config);
            
            $this->_loadConfigFile($this->_config);
        }
        
        return $this->_config;
    }
    
    /**
     * return Zend_Log
     */
    protected function _getLogger()
    {
        if (! $this->_logger instanceof Zend_Log) {
            $config = $this->_getConfig();
            
            $this->_logger = new Zend_Log();
            /** @noinspection PhpUndefinedFieldInspection */
            $this->_logger->addWriter(new Zend_Log_Writer_Stream($config->general->logfile ? $config->general->logfile : STDOUT));
            /** @noinspection PhpUndefinedFieldInspection */
            $this->_logger->addFilter(new Zend_Log_Filter_Priority((int) $config->general->loglevel));
        }
        
        return $this->_logger;
    }
    
    /**
     * @param  Zend_Config  $config
     * @return void
     */
    protected function _loadConfigFile(Zend_Config $config)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        if (file_exists($config->general->configfile)) {
            try {
                /** @noinspection PhpUndefinedFieldInspection */
                $configIniFile = new Zend_Config_Ini($config->general->configfile);
            } catch (Zend_Config_Exception $e) {
                /** @noinspection PhpUndefinedFieldInspection */
                fwrite(STDERR, "Error while parsing config file({$config->general->configfile}) " .  $e->getMessage() . PHP_EOL);
                exit(1);
            }
            
            $config->merge($configIniFile);
        }
    }
    
    /**
     * 
     * @return number
     */
    protected function _forkChild()
    {
        $this->_beforeFork();
        $childPid = pcntl_fork();
        
        if($childPid < 0) {
            fwrite(STDERR, "Something went wrong while forking new child" . PHP_EOL);
            exit(1);
        }
        
        // fork was successful
        $this->_afterFork($childPid);
        
        // add childPid to internal scoreboard
        if($childPid > 0) {
            $this->_children[$childPid] = $childPid;
        } else {
            // a child has no children
            $this->_children = array();
            $this->_isChild = true;
        }
        
        return $childPid;
    }
    
    /**
     * template function intended to do cleanups before forking (e.g. disconnect database)
     */
    protected function _beforeFork()
    {
        
    }
    
    /**
     * template function intended to do init after forking (e.g. reconnect database)
     * 
     * @param $childPid
     */
    protected function _afterFork($childPid)
    {
        
    }
    
    /**
     * function fork into background (become a daemon)
     * @return void|number
     */
    protected function _becomeDaemon()
    {
        $pidFile = $this->getPidFile();
        
        if ( $pidFile !== null && ! is_writable(dirname($pidFile))) {
            fwrite(STDERR, "cannot write pidfile '{$pidFile}'" . PHP_EOL);
            exit(1);
        }
        
        $childPid = pcntl_fork();
        
        if ($childPid < 0) {
            fwrite(STDERR, "Something went wrong while forking to background" . PHP_EOL);
            exit;
        }
        
        // fork was successfull
        // we can finish the main process
        if ($childPid > 0) {
            #echo "We are master. Exiting main process now..." . PHP_EOL;
            if ($pidFile !== null) {
                file_put_contents($pidFile, $childPid);
            }
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
     * @param Zend_Config $config
     * @return Zend_Console_Getopt
     */
    protected function _parseOptions(Zend_Config $config)
    {
        try {
            $opts = new Zend_Console_Getopt($this->_options);
            $opts->parse();
        } catch (Zend_Console_Getopt_Exception $e) {
           fwrite(STDOUT, $e->getUsageMessage());
           exit(1);
        }

        /** @noinspection PhpUndefinedFieldInspection */
        if ($opts->h) {
            fwrite(STDOUT, $opts->getUsageMessage());
            
            exit(0);
        }

        // pid file path
        if (isset($opts->p)) {
            /** @noinspection PhpUndefinedFieldInspection */
            $config->general->pidfile = $opts->p;
        }

        // config file path
        if (isset($opts->config)) {
            /** @noinspection PhpUndefinedFieldInspection */
            $config->general->configfile = $opts->config;
        }

        // become daemon
        if (isset($opts->d)) {
            /** @noinspection PhpUndefinedFieldInspection */
            $config->general->daemonize = 1;
        }
        
        return $opts;
    }
    
    /**
     * handle signal SIGTERM
     */
    public function handleSigTERM()
    {
        $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ .    " SIGTERM received, is child: " . var_export($this->_isChild, true) . " " . microtime(true));

        // do we want to gracefully shut down?
        if (true === $this->_gracefulShutDown()) {
            return;
        }

        $this->_shutDown();
    }

    protected function _shutDown()
    {
        foreach($this->_children as $pid) {
            $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ .    " send SIGTERM to child " . $pid);
            posix_kill($pid, SIGTERM);
        }

        $pidFile = $this->getPidFile();

        if ($pidFile) {
            @unlink($pidFile);
        }

        usleep(1000);
        pcntl_signal_dispatch();

        exit(0);
    }

    protected function _gracefulShutDown()
    {
        return false;
    }

    /**
     * handle signal SIGCHILD
     */
    public function handleSigCHLD()
    {
        while (($pid = pcntl_waitpid(0, $status, WNOHANG)) > 0) {
            $this->_childTerminated($pid, $status);
        }
    }
    
    /**
     * handle signal SIGHUP
     */
    public function handleSigHUP()
    {
        $this->_getLogger()->debug(__METHOD__ . '::' . __LINE__ .    " SIGHUP received, but we don't do anything in this case");
    }
    
    /**
     * handle signal SIGINT
     */
    public function handleSigINT()
    {
        $this->handleSigTERM();
    }
}
