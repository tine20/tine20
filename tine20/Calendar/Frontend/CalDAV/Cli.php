<?php
/**
 * Tine 2.0
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * CalDAV import helper functions for CLI
 *
 * @package     Calendar
 */
class Calendar_Frontend_CalDAV_Cli
{
    /**
     * caldav users / single user data
     * 
     * @var array
     */
    protected $_users = array();
    
    /**
     * @var Calendar_Import_CalDav_Client
     */
    protected $_caldavClient = null;
    
    /**
     * CLI opts
     * 
     * @var Zend_Console_Getopt
     */
    protected $_opts;
    
    /**
     * parsed CLI arguments
     * 
     * @var array
     */
    protected $_args;
    
    protected $_numberOfImportRuns = 2;
    protected $_caldavClientClass = 'Calendar_Import_CalDav_Client';
    protected $_appName = 'Calendar';
    
    /**
     * the constructor
     */
    public function __construct(Zend_Console_Getopt $opts, $args)
    {
        $this->_opts = $opts;
        $this->_args = $args;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Init caldav cli helper with params: ' . print_r($args, true));
        
        $this->_readCalDavUserFile($args['caldavuserfile'], isset($args['line']) ? $args['line'] : 0);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
             Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' User(s): '
                . (isset($this->_users['username']) ? $this->_users['username'] : count($this->_users)));
        
        $caldavClientOptions = array(
            'baseUri' => $args['url']
        );
        if (isset($this->_users['username'])) {
            $caldavClientOptions = array_merge($caldavClientOptions, array(
                'userName' => $this->_users['username'],
                'password' => $this->_users['password'],
            ));
        }
        $this->_caldavClient = new $this->_caldavClientClass($caldavClientOptions, 'MacOSX');
        $this->_caldavClient->setVerifyPeer(false);
    }
    
    /**
     * import all calendars
     */
    public function importAllCalendars()
    {
        $this->_caldavClient->importAllCalendarsForUsers($this->_users);
    }
    
    /**
     * import all data for users
     */
    public function importAllCalendarDataForUsers()
    {
        $this->_caldavClient->importAllCalendarDataForUsers($this->_users);
    }

    /**
     * import all data for single user
     */
    public function importAllCalendarData()
    {
        $this->_caldavClient->importAllCalendarData($this->_args['run'] == 1 ? true : false);
    }
    
    /**
     * update all data for users
     */
    public function updateAllCalendarDataForUsers()
    {
        $this->_caldavClient->updateAllCalendarDataForUsers($this->_users);
    }
    
    /**
     * update all data for single user
     */
    public function updateAllCalendarData()
    {
        $this->_caldavClient->updateAllCalendarData($this->_args['run'] == 1 ? true : false);
    }
    
    /**
     * run import/update with multiple processes
     * 
     * @param string $mode
     * @throws Exception
     */
    public function runImportUpdateMultiproc($mode)
    {
        $numProc = intval($this->_args['numProc']);
        $this->_validateNumProc($numProc);
        
        if (empty($this->_opts->passwordfile)) {
            throw new Exception('Passwordfile required for this method');
        }
        
        if ($mode === 'import' && (empty($this->_args['dataonly']) || $this->_args['dataonly'] == false)) {
            // first import the calendars, serial sadly
            $this->importAllCalendars();
        }
        
        $cliParams = '--username ' . $this->_opts->username . ' --passwordfile ' . $this->_opts->passwordfile
        . ' --method ' . $this->_appName . '.' . $mode . 'CalDavDataForUser'
                . ' url=' .  $this->_args['url'] . ' caldavuserfile=' . $this->_args['caldavuserfile'];
        
        for ($run = 1; $run <= $this->_numberOfImportRuns; ++$run) {
            $this->_runMultiProcessImportUpdate($numProc, $cliParams, $run);
        }
    }
    
    /**
     * validate num procs
     * 
     * @param string $numProc
     * @throws Exception
     */
    protected function _validateNumProc($numProc)
    {
        if ($numProc < 1) {
            throw new Exception('numProc: ' . $numProc . ' needs to be at least 1');
        }
        if ($numProc > 32) {
            throw new Exception('numProc: ' . $numProc . ' needs to be lower than 33');
        }
    }
    
    /**
     * run multi process command
     * 
     * do multiprocess part, no system resources may be used as of here, 
     * like file handles, db resources... see pcntl_fork man page!
     * 
     * @todo add pids to processes array to allow better control 
     * @todo generalize and move to Tinebase_CalDAV_Cli_Abstract
     * 
     * @param integer $numProc
     * @param string $cliParams
     * @param integer $run
     */
    protected function _runMultiProcessImportUpdate($numProc, $cliParams, $run = 1)
    {
        // $processes = array();
        $processes = 0;
        $line = 0;
        foreach ($this->_users as $user => $pwd)
        {
            ++$line;
            //if (count($processes) >= $numProc) {
            if ($processes >= $numProc) {
                $pid = pcntl_wait($status);
    
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                        . ' pid: ' . $pid);
    
                // debug
                //                     echo '1' . (int) pcntl_wexitstatus($status);
                //                     echo '2' . (int) pcntl_wifexited($status);
                //                     echo '3' . (int) pcntl_wifsignaled($status);
                //                     echo '4' . (int) pcntl_wifstopped($status);
                //                     echo '5' . (int) pcntl_wstopsig($status);
                //                     echo '6' . (int) pcntl_wtermsig($status);
    
                if (pcntl_wifexited($status) === false ) {
                    exit('pcntl_wait return value was not found in process list: ' . $pid . ' status: ' . $status . PHP_EOL);
                }
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                    . ' Child exited');
                
                // unset($processes[$pid]);
                --$processes;
            }
    
            $pid = pcntl_fork();
            if ($pid == -1) {
                exit('could not fork' . PHP_EOL);
            } else if ($pid) {
                // we are the parent
                // $processes[$pid] = true;
                ++$processes;
            } else {
                // we are the child
                $config = ($this->_opts->config) ? '--config=' . $this->_opts->config . ' ' : '';
                $command = dirname(dirname(dirname(dirname(__FILE__)))) . '/tine20.php ' . $config . $cliParams . ' run=' . $run . ' line=' . $line;
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                     . ' Spawning new child with command: ' . $command);
                
                exec($command);
                exit();
            }
        }
        // wait for childs to finish
        // while (count($processes) > 0) {
        while ($processes > 0) {
            $pid = pcntl_wait($status);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                    . ' pid: ' . $pid);
    
            if (pcntl_wifexited($status) === false) {
                exit('pcntl_wait return value was not found in process list: ' . $pid . ' status: ' . $status . PHP_EOL);
            }
            // unset($processes[$pid]);
            --$processes;
        }
    }
    
    /**
     * read caldav user credentials file
     * 
     * - file should have the following format (CSV):
     * USERNAME1;PASSWORD1
     * USERNAME2;PASSWORD2
     * 
     * @param string $file
     * @throws Exception
     */
    protected function _readCalDavUserFile($file, $line = 0)
    {
        if (!($fh = fopen($file, 'r'))) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Couldn\'t open file: '.$file);
            throw new Exception('Couldn\'t open file: '.$file);
        }
        $this->_users = array();
        $i = 0;
        while ($row = fgetcsv($fh, 2048, ';'))
        {
            if ($line > 0 && ++$i == $line) {
                // only fetch single user
                $this->_users = array('username' => $row[0], 'password' => $row[1]);
                return;
            }
            $this->_users[$row[0]] = $row[1];
        }
        if (count($this->_users) < 1) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' No users found in: '.$file);
            throw new Exception('No users found in: '.$file);
        }
        if ($line > 0) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Line: '.$line. ' out of bounds');
            throw new Exception('No user found, line: '.$line. ' out of bounds');
        }
    }
}
