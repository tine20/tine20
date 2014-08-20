<?php
/**
 * Tine 2.0
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Cli frontend for Tasks
 *
 * This class handles cli requests for the Tasks
 *
 * @package     Tasks
 */
class Tasks_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Tasks';
    
    /**
     * import calendars from a CalDav source
     *
     * param Zend_Console_Getopt $_opts
     */
    public function importCalDavCalendars(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile'));
        
        $this->_addOutputLogWriter(4);
        
        $caldavCli = new Tasks_Frontend_CalDAV_Cli($_opts, $args);
        $caldavCli->importAllCalendars();
    }
    
    /**
     * import calendars and calendar events from a CalDav source using multiple parallel processes
     *
     * param Zend_Console_Getopt $_opts
     */
    public function importCalDavMultiProc(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile', 'numProc'));
        
        $this->_addOutputLogWriter(4);
        
        $caldavCli = new Tasks_Frontend_CalDAV_Cli($_opts, $args);
        $caldavCli->runImportUpdateMultiproc('import');
    }
    
    /**
     * update calendar events from a CalDav source using multiple parallel processes
     *
     * param Zend_Console_Getopt $_opts
     */
    public function updateCalDavMultiProc(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile', 'numProc'));
        
        $this->_addOutputLogWriter(4);
        
        $caldavCli = new Tasks_Frontend_CalDAV_Cli($_opts, $args);
        $caldavCli->runImportUpdateMultiproc('update');
    }
    
    /**
     * import calendar events from a CalDav source for one user
     *
     * param Zend_Console_Getopt $_opts
     */
    public function importCalDavDataForUser(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile', 'line', 'run'));
        
        $this->_addOutputLogWriter(4);
        
        $caldavCli = new Tasks_Frontend_CalDAV_Cli($_opts, $args);
        $caldavCli->importAllCalendarData();
    }
    
    /**
     * update calendar/events from a CalDav source using etags for one user
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function updateCalDavDataForUser(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile', 'line', 'run'));
        
        $this->_addOutputLogWriter(4);
        
        $caldavCli = new Tasks_Frontend_CalDAV_Cli($_opts, $args);
        $caldavCli->updateAllCalendarData();
    }
}
