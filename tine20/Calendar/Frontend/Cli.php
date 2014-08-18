<?php
/**
 * Tine 2.0
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Cli frontend for Calendar
 *
 * This class handles cli requests for the Calendar
 *
 * @package     Calendar
 */
class Calendar_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Calendar';
    
    /**
     * help array with function names and param descriptions
     * 
     * @return void
     */
    protected $_help = array(
        'updateCalDavData' => array(
            'description'    => 'update calendar/events from a CalDav source using etags',
            'params'         => array(
                'url'        => 'CalDav source URL',
                'caldavuserfile' => 'CalDav user file containing utf8 username;pwd',
             )
        ),
        'importCalDavData' => array(
            'description'    => 'import calendar/events from a CalDav source',
            'params'         => array(
                'url'        => 'CalDav source URL',
                'caldavuserfile' => 'CalDav user file containing utf8 username;pwd',
             )
        ),
        'importCalDavCalendars' => array(
            'description'    => 'import calendars without events from a CalDav source',
            'params'         => array(
                'url'        => 'CalDav source URL',
                'caldavuserfile' => 'CalDav user file containing utf8 username;pwd',
             )
        ),
        'importegw14' => array(
            'description'    => 'imports calendars/events from egw 1.4',
            'params'         => array(
                'host'       => 'dbhost',
                'username'   => 'username',
                'password'   => 'password',
                'dbname'     => 'dbname'
            )
        ),
        'exportICS' => array(  
            'description'    => "export calendar as ics", 
            'params'         => array('container_id') 
        ),
    );
    
    /**
     * return anonymous methods
     * 
     * @return array
     */
    public static function getAnonymousMethods()
    {
        return array('Calendar.repairDanglingDisplaycontainerEvents');
    }
    
    /**
     * import events
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function import($_opts)
    {
        parent::_import($_opts);
    }
    
    /**
     * exports calendars as ICS
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function exportICS($_opts)
    {
        $opts = $_opts->getRemainingArgs();
        $container_id = $opts[0];
        $filter = new Calendar_Model_EventFilter(array(
            array(
                'field'     => 'container_id',
                'operator'  => 'equals',
                'value'     => $container_id
            )

        ));
        $result = Calendar_Controller_MSEventFacade::getInstance()->search($filter, null, false, false, 'get');
        if ($result->count() == 0) {
            throw new Tinebase_Exception('this calendar does not contain any records.');
        }
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory("generic");
        $result = $converter->fromTine20RecordSet($result);
        print $result->serialize();
    }
    
    /**
     * delete duplicate events
     * 
     * @see 0008182: event with lots of exceptions breaks calendar sync
     * 
     * @todo allow user to set params
     */
    public function deleteDuplicateEvents()
    {
        $writer = new Zend_Log_Writer_Stream('php://output');
        $writer->addFilter(new Zend_Log_Filter_Priority(6));
        Tinebase_Core::getLogger()->addWriter($writer);
        
        $be = new Calendar_Backend_Sql();
        $filter = new Calendar_Model_EventFilter(array(array(
            'field'    => 'dtstart',
            'operator' => 'after',
            'value'    => Tinebase_DateTime::now(),
        ), array(
            'field'    => 'organizer',
            'operator' => 'equals',
            'value'    => 'contactid', // TODO: set correct contact_id or use container_id filter
        )));
        $dryrun = TRUE;
        $be->deleteDuplicateEvents($filter, $dryrun);
    }
    
    /**
     * repair dangling attendee records (no displaycontainer_id)
     * 
     * @see https://forge.tine20.org/mantisbt/view.php?id=8172
     */
    public function repairDanglingDisplaycontainerEvents()
    {
        $writer = new Zend_Log_Writer_Stream('php://output');
        $writer->addFilter(new Zend_Log_Filter_Priority(5));
        Tinebase_Core::getLogger()->addWriter($writer);
        
        $be = new Calendar_Backend_Sql();
        $be->repairDanglingDisplaycontainerEvents();
    }
    
    /**
     * import calendars from a CalDav source
     * 
     * param Zend_Console_Getopt $_opts
     */
    public function importCalDavCalendars(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile'));
        
        $this->_addOutputLogWriter(4);
        
        $users = $this->_readCalDavUserFile($args['caldavuserfile']);
        
        $this->_importAllCalendars($users, $args['url']);
    }
    
    protected function _importAllCalendars($users, $uri)
    {
        $client = new Calendar_Import_CalDav_Client(array('baseUri' => $uri), 'MacOSX');
        $client->setVerifyPeer(false);
        $client->importAllCalendarsForUsers($users);
    }
    
    /**
     * import calendar events from a CalDav source
     * 
     * param Zend_Console_Getopt $_opts
     */
    public function importCalDavData(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile'));
        
        $writer = new Zend_Log_Writer_Stream('php://output');
        $writer->addFilter(new Zend_Log_Filter_Priority(4));
        Tinebase_Core::getLogger()->addWriter($writer);
        
        $users = $this->_readCalDavUserFile($args['caldavuserfile']);
        
        $client = new Calendar_Import_CalDav_Client(array('baseUri' => $args['url']), 'MacOSX');
        $client->setVerifyPeer(false);
        
        $client->importAllCalendarDataForUsers($users);
    }
    
    /**
     * import calendars and calendar events from a CalDav source using multiple parallel processes
     * 
     * param Zend_Console_Getopt $_opts
     */
    public function importCalDavMultiProc(Zend_Console_Getopt $_opts)
    {
        $this->_runImportUpdateMultiproc($_opts, 'import');
    }
    
    protected function _runImportUpdateMultiproc(Zend_Console_Getopt $_opts, $mode)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile', 'numProc'));
        
        $numProc = intval($args['numProc']);
        $this->_validateNumProc($numProc);
        
        if (empty($_opts->passwordfile)) {
            throw new Exception('Passwordfile required for this method');
        }
        
        $this->_addOutputLogWriter(4);
        
        $users = $this->_readCalDavUserFile($args['caldavuserfile']);
        
        if ($mode === 'import') {
            // first import the calendars, serial sadly
            $this->_importAllCalendars($users, $args['url']);
        }
        
        $cliParams = '--username ' . $_opts->username . ' --passwordfile ' . $_opts->passwordfile
        . ' --method Calendar.' . $mode . 'CalDavDataForUser'
                . ' url=' .  $args['url'] . ' caldavuserfile=' . $args['caldavuserfile'];
        
        $numberOfRuns = 2;
        for ($run = 1; $run <= $numberOfRuns; ++$run)
        {
            $this->_runMultiProcessImportUpdate($numProc, $cliParams, $users, $run);
        }
    }
    
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
     * @todo generalize and move to Tinebase_Frontend_Cli_Abstract
     * 
     * @param integer $numProc
     * @param string $cliParams
     * @param array $users
     * @param integer $run
     */
    protected function _runMultiProcessImportUpdate($numProc, $cliParams, $users, $run = 1)
    {
        // $processes = array();
        $processes = 0;
        $line = 0;
        foreach ($users as $user => $pwd)
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
                $command = './tine20.php ' . $cliParams . ' run=' . $run . ' line=' . $line;
                
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
     * update calendar events from a CalDav source using multiple parallel processes
     * 
     * param Zend_Console_Getopt $_opts
     */
    public function updateCalDavMultiProc(Zend_Console_Getopt $_opts)
    {
        $this->_runImportUpdateMultiproc($_opts, 'update');
    }
    
    /**
     * import calendar events from a CalDav source for one user
     * 
     * param Zend_Console_Getopt $_opts
     */
    public function importCalDavDataForUser(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile', 'line', 'run'));
        
        $writer = new Zend_Log_Writer_Stream('php://output');
        $writer->addFilter(new Zend_Log_Filter_Priority(4));
        Tinebase_Core::getLogger()->addWriter($writer);
        
        $user = $this->_readCalDavUserFile($args['caldavuserfile'], $args['line']);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' user: ' . $user['username']);
        
        $client = new Calendar_Import_CalDav_Client(array(
                'baseUri' => $args['url'],
                'userName' => $user['username'],
                'password' => $user['password'],
                ), 'MacOSX');
        $client->setVerifyPeer(false);
        
        $client->importAllCalendarData($args['run']==1 ? true : false);
    }
    
    /**
     * update calendar/events from a CalDav source using etags for one user
     * 
     * @param Zend_Console_Getopt $_opts
     */
    public function updateCalDavDataForUser(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile', 'line', 'run'));
        
        $writer = new Zend_Log_Writer_Stream('php://output');
        $writer->addFilter(new Zend_Log_Filter_Priority(4));
        Tinebase_Core::getLogger()->addWriter($writer);
        
        $user = $this->_readCalDavUserFile($args['caldavuserfile'], $args['line']);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' user: ' . $user['username']);
        
        $client = new Calendar_Import_CalDav_Client(array(
                'baseUri' => $args['url'],
                'userName' => $user['username'],
                'password' => $user['password'],
                ), 'MacOSX');
        $client->setVerifyPeer(false);
        
        $client->updateAllCalendarData($args['run']==1 ? true : false);
    }
   
    /**
     * update calendar/events from a CalDav source using etags
     * 
     * param Zend_Console_Getopt $_opts
     */
    public function updateCalDavData(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile'));
        
        $writer = new Zend_Log_Writer_Stream('php://output');
        $writer->addFilter(new Zend_Log_Filter_Priority(4));
        Tinebase_Core::getLogger()->addWriter($writer);
        
        $users = $this->_readCalDavUserFile($args['caldavuserfile']);
        
        $client = new Calendar_Import_CalDav_Client(array('baseUri' => $args['url']), 'MacOSX');
        $client->setVerifyPeer(false);
        
        $client->updateAllCalendarDataForUsers($users);
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
        $users = array();
        $i = 0;
        while ($row = fgetcsv($fh, 2048, ';'))
        {
            if ($line > 0 && ++$i == $line) {
                return array('username' => $row[0], 'password' => $row[1]);
            }
            $users[$row[0]] = $row[1];
        }
        if (count($users) < 1) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' No users found in: '.$file);
            throw new Exception('No users found in: '.$file);
        }
        if ($line > 0) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Line: '.$line. ' out of bounds');
            throw new Exception('No user found, line: '.$line. ' out of bounds');
        }
        return $users;
    }
}
