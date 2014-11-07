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
        //If old param is used add the new one
        foreach ( $_opts->getRemainingArgs() as $key => $opt) {
            if (strpos($opt, 'importContainerId=') !== false) {
                $_opts->addArguments(array('container_id='. substr($opt, 18, strlen($opt))));
            }
        }
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
     *  - allowed params: 
     *      organizer=ORGANIZER_CONTACTID (equals)
     *      created_by=USER_ID (equals)
     *      dtstart="2014-10-28" (after)
     *      summary=EVENT_SUMMARY (contains)
     *      -d (dry run)
     * 
     * @param Zend_Console_Getopt $opts
     * 
     * @see 0008182: event with lots of exceptions breaks calendar sync
     * 
     * @todo allow to pass json encoded filter
     */
    public function deleteDuplicateEvents($opts)
    {
        $writer = new Zend_Log_Writer_Stream('php://output');
        $writer->addFilter(new Zend_Log_Filter_Priority(6));
        Tinebase_Core::getLogger()->addWriter($writer);
        
        $params = $this->_parseArgs($opts);
        
        $be = new Calendar_Backend_Sql();
        $filterData = array(array(
            'field'    => 'dtstart',
            'operator' => 'after',
            'value'    => isset($params['dtstart']) ? new Tinebase_DateTime($params['dtstart']) : Tinebase_DateTime::now(),
        ));
        if (isset($params['organizer'])) {
            $filterData[] = array(
                'field'    => 'organizer',
                'operator' => 'equals',
                'value'    => $params['organizer'],
            );
        }
        if (isset($params['created_by'])) {
            $filterData[] = array(
                'field'    => 'created_by',
                'operator' => 'equals',
                'value'    => $params['created_by'],
            );
        }
        if (isset($params['summary'])) {
            $filterData[] = array(
                'field'    => 'summary',
                'operator' => 'contains',
                'value'    => $params['summary'],
            );
        }
        $filter = new Calendar_Model_EventFilter($filterData);
        
        $result = $be->deleteDuplicateEvents($filter, $opts->d);
        exit($result > 0 ? 1 : 0);
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
        
        $caldavCli = new Calendar_Frontend_CalDAV_Cli($_opts, $args);
        $caldavCli->importAllCalendars();
    }
    
    /**
     * import calendar events from a CalDav source
     * 
     * param Zend_Console_Getopt $_opts
     */
    public function importCalDavData(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile'));
        
        $this->_addOutputLogWriter(4);
        
        $caldavCli = new Calendar_Frontend_CalDAV_Cli($_opts, $args);
        $caldavCli->importAllCalendarDataForUsers();
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
        
        $caldavCli = new Calendar_Frontend_CalDAV_Cli($_opts, $args);
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
        
        $caldavCli = new Calendar_Frontend_CalDAV_Cli($_opts, $args);
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
        
        $caldavCli = new Calendar_Frontend_CalDAV_Cli($_opts, $args);
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
        
        $caldavCli = new Calendar_Frontend_CalDAV_Cli($_opts, $args);
        $caldavCli->updateAllCalendarData();
    }
    
    /**
     * update calendar/events from a CalDav source using etags
     * 
     * param Zend_Console_Getopt $_opts
     */
    public function updateCalDavData(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('url', 'caldavuserfile'));
        
        $this->_addOutputLogWriter(4);
        
        $caldavCli = new Calendar_Frontend_CalDAV_Cli($_opts, $args);
        $caldavCli->updateAllCalendarDataForUsers();
    }

    /**
     * print alarm acknowledgement report (when, ip, client, user, ...)
     * 
     * @param Zend_Console_Getopt $_opts
     */
    public function alarmAckReport(Zend_Console_Getopt $_opts)
    {
        // get all events for today for current user
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'attender', 'operator' => 'in', 'value' => array(array(
                'user_type' => 'user',
                'user_id'   => Tinebase_Core::getUser()->contact_id,
            ))),
            array('field' => 'attender_status', 'operator' => 'not',    'value' => Calendar_Model_Attender::STATUS_DECLINED),
            array('field' => 'period', 'operator' => 'within', 'value' =>
                array("from" => Tinebase_DateTime::now()->subDay(1), "until" => Tinebase_DateTime::now()->toString())
            )
        ));
        $events = Calendar_Controller_Event::getInstance()->search($filter);
        Calendar_Controller_Event::getInstance()->getAlarms($events);
        
        echo "Reporting alarms for events of user " . Tinebase_Core::getUser()->accountLoginName . " (All times in UTC) ...\n\n";
        
        // loop alarms and print alarm ack info
        foreach ($events as $event) {
            if (count($event->alarms) > 0) {
                echo "Event '" . substr($event->summary, 0, 30) . "' (" . $event->dtstart->toString() . ' - ' . $event->dtend->toString() .")\n";
                foreach ($event->alarms as $alarm) {
                    echo "  Alarm " . $alarm->alarm_time . "\n";
                    $ackTime = Calendar_Controller_Alarm::getAcknowledgeTime($alarm);
                    if (empty($ackTime)) {
                        echo "    not acknowledged!";
                    } else {
                        if (is_array($ackTime)) {
                            $ackTime = array_pop($ackTime);
                        }
                        echo "    acknowledged " . $ackTime->toString()
                            . "\n    IP -> ". $alarm->getOption(Tinebase_Model_Alarm::OPTION_ACK_IP)
                            . "\n    Client ->". $alarm->getOption(Tinebase_Model_Alarm::OPTION_ACK_CLIENT) . "\n";
                    }
                    echo "\n";
                }
                echo "\n";
            }
        }
    }
}
