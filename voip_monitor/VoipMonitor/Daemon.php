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
 * Base class for daemons
 * 
 * @package     VoipMonitor
 */
abstract class VoipMonitor_Daemon
{
    /**
     * constructor
     * 
     * @param bool $_becomeDaemon  if true forks to background
     */
    public function __construct($_becomeDaemon = false)
    {
        set_time_limit (0);
        ob_implicit_flush ();
        declare(ticks = 1);
        
        pcntl_signal(SIGUSR1, array($this, "handleSigUSR1"));
        
        if($_becomeDaemon === true) {
            $pid = $this->_becomeDaemon();
            // write pidfile
        }
    }
    
    /**
     * this function handles to main logic of the application
     */
    abstract public function run();
  
    /**
     * function fork into background (become a daemon)
     * @return void|number
     */
    protected function _becomeDaemon()
    {
        $pid = pcntl_fork();
        
        if($pid < 0) {
            echo "Something went wrong" . PHP_EOL;
            exit;
        }
        
        // pid returned. We are the master process
        if($pid > 0) {
            echo "We are master. Exiting main process now..." . PHP_EOL;
            exit;
        }
        
        //echo "We are child now!" . PHP_EOL;
        //  become session leader
        posix_setsid();
        
        return posix_getpid();
    }
  
    /**
     * handle signal SIGUSR1
     * @param int $signal  the signal
     */
    public function handleSigUSR1($signal)
    {
        $this->_handleSignal($signal);
    }

    /**
     * stub helper function
     * @param int $signal  the signal
     * @return unknown_type
     */
    protected function _handleSignal($signal)
    {
        echo "Caught signal $signal ..." . PHP_EOL;
    }
}

