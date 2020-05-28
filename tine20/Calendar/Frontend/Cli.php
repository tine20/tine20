<?php
/**
 * Tine 2.0
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * import demodata default definitions
     *
     * @var array
     */
    protected $_defaultDemoDataDefinition = [
        'Calendar_Model_Event' => 'cal_import_event_csv'
    ];
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
     * exports calendars as ICS (VCALENDAR)
     * examples:
     *      --method Calendar.exportVCalendar --username=USER -- container_id=CALID filename=/my/export/file.ics
     *      --method Calendar.exportVCalendar --username=USER -- type=personal path=/my/export/path/
     *
     * @param $_opts
     * @return boolean
     */
    public function exportVCalendar($_opts)
    {
        return $this->_exportVObject($_opts, Calendar_Model_Event::class, Calendar_Export_VCalendar::class);
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
        $this->_addOutputLogWriter(6);
        
        $args = $this->_parseArgs($opts);
        
        $be = new Calendar_Backend_Sql();
        $filterData = array(array(
            'field'    => 'dtstart',
            'operator' => 'after',
            'value'    => isset($args['dtstart']) ? new Tinebase_DateTime($args['dtstart']) : Tinebase_DateTime::now(),
        ));
        if (isset($args['organizer'])) {
            $filterData[] = array(
                'field'    => 'organizer',
                'operator' => 'equals',
                'value'    => $args['organizer'],
            );
        }
        if (isset($args['created_by'])) {
            $filterData[] = array(
                'field'    => 'created_by',
                'operator' => 'equals',
                'value'    => $args['created_by'],
            );
        }
        if (isset($args['summary'])) {
            $filterData[] = array(
                'field'    => 'summary',
                'operator' => 'contains',
                'value'    => $args['summary'],
            );
        }
        $filter = new Calendar_Model_EventFilter($filterData);
        
        $result = $be->deleteDuplicateEvents($filter, $opts->d);
        return 0;
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
        $until = Tinebase_DateTime::now();
        $from = Tinebase_DateTime::now()->subDay(1);
        
        // get all events for today for current user
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'attender', 'operator' => 'in', 'value' => array(array(
                'user_type' => 'user',
                'user_id'   => Tinebase_Core::getUser()->contact_id,
            ))),
            array('field' => 'attender_status', 'operator' => 'not',    'value' => Calendar_Model_Attender::STATUS_DECLINED),
            array('field' => 'period', 'operator' => 'within', 'value' =>
                array("from" => $from, "until" => $until)
            )
        ));
        $events = Calendar_Controller_Event::getInstance()->search($filter);
        Calendar_Model_Rrule::mergeAndRemoveNonMatchingRecurrences($events, $filter);
        Calendar_Controller_Event::getInstance()->getAlarms($events);
        
        echo "Reporting alarms for events of user " . Tinebase_Core::getUser()->accountLoginName
            . " (All times in UTC) from " . $from->toString() . ' until ' . $until->toString() . "...\n\n";
        
        // loop alarms and print alarm ack info
        foreach ($events as $event) {
            if (count($event->alarms) > 0) {
                $this->_printShortEvent($event);
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

    /**
     *
     * @param Zend_Console_Getopt $_opts
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     *
     */
    public function userCalendarReport(Zend_Console_Getopt $_opts)
    {
        $this->_checkAdminRight();
        $args = $this->_parseArgs($_opts, ['filename']);
        if (!isset($args['status'])) $args['status'] = false;
        $appId = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        $count = Tinebase_User::getInstance()->getUserCount();
        $headData = ['User', 'Number of Calendars', 'Required space in MB', 'Filemanager usage in MB'];

        echo $count . "\n";

        $fp = fopen($args['filename'], 'a');
        fputcsv($fp, $headData);
        fclose($fp);

        $i = 0;

        $users = Tinebase_User::getInstance()->getFullUsers(NULL, NULL, $_dir = 'ASC', $i, 100);
        while ($users->count() > 0) {
            $i += 100;
            echo 'Count: ' . $i . "\n";
            //$users = Tinebase_User::getInstance()->getFullUsers('metaways');
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Found ' . $users->count() . ' Users!');

            foreach ($users as $user) {
                Tinebase_Core::set(Tinebase_Core::USER, $user);
                echo 'Search Container from User: ' . $user['accountLoginName'] . "\n";
                $disabled = ($args['status'] || $user['accountStatus'] != 'disabled');
                if ($disabled) {
                    $containerFilter = [
                        ['field' => 'owner_id', 'operator' => 'equals', 'value' => $user->getId()],
                        ['field' => 'model', 'operator' => 'equals', 'value' => Calendar_Model_Event::class],
                        ['field' => 'application_id', 'operator' => 'equals',
                            'value' => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId()],
                    ];

                    $counContainers = Tinebase_Container::getInstance()->searchCount(
                        new Tinebase_Model_ContainerFilter($containerFilter)
                    );

                    
                    echo 'Found ' . $counContainers . ' Container!' . "\n";


                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Found ' . $counContainers .
                        ' Calendars for ' . $user['accountLoginName']);


                    $personalPath = [
                        ['field' => 'recursive', 'operator' => 'equals', 'value' => 1],
                    ];

                    $usage = 0;
                    $clUsage = 0;

                    try {
                        $personalNodes = Filemanager_Controller_Node::getInstance()->search(new Filemanager_Model_NodeFilter($personalPath));

                        foreach ($personalNodes as $node) {
                            if (preg_match('/^\/personal\/' . $user['accountLoginName'] . '.*/', $node['path'])) $usage += $node['size'];
                        }
                    } catch (Exception $e) {
                        echo $user['accountLoginName'] . ' has no rights for Filemanager!' . "\n";
                        $usage = 'no rights!';
                    }

                    $containers = Tinebase_Container::getInstance()->search(
                        new Tinebase_Model_ContainerFilter($containerFilter)
                    );

                    foreach ($containers as $container) {

                        echo 'Search Events from User: ' . $user['accountLoginName'] . ' and Container: ' . $container['name'] . "\n";

                        $attachments = [];

                        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
                            ['field' => 'container_id', 'operator' => 'equals', 'value' => $container->getId()]]));

                        echo 'Found ' . $events->count() . ' Events' . "\n";

                        Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($events);


                        foreach ($events as $event) {
                            $attachments = $event->attachments;
                            if ($attachments) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Found ' .
                                $attachments->count() . ' attachments for the Events of ' . $user['accountLoginName']);

                            if (isset($attachments)) {
                                foreach ($attachments as $attachment) {
                                    echo 'Found Attachments! ' . "\n";
                                    //print_r($attachment->toArray());
                                    $clUsage += $attachment['size'];
                                }
                            } else {
                                echo 'No Attachments found! ' . "\n";
                            }
                        }

                    }
                    $clUsage = Tinebase_Helper::formatBytes($clUsage, 'MB');
                    $usage = Tinebase_Helper::formatBytes($usage, 'MB');
                    $data = [$user['accountLoginName'], $counContainers, $clUsage, $usage];
                    $fp = fopen($args['filename'], 'a');
                    fputcsv($fp, $data);
                    fclose($fp);
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Save the result in ' .
                        $args['filename']);

                }
            }
            $users = Tinebase_User::getInstance()->getFullUsers(NULL, NULL, $_dir = 'ASC', $i, 100);
        }
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Report Success!');

        return 1;
    }

    /**
     * report of shared calendars
     *
     * @param Zend_Console_Getopt $_opts
     * @return integer
     *
     * @todo generalize for other apps?
     */
    public function sharedCalendarReport(Zend_Console_Getopt $_opts)
    {
        $this->_checkAdminRight();

        // get all personal containers of all users
        $filter = [
            ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Container::TYPE_PERSONAL],
            ['field' => 'model', 'operator' => 'equals', 'value' => Calendar_Model_Event::class],
            ['field' => 'application_id', 'operator' => 'equals',
                'value' => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId()],
        ];
        Tinebase_Container::getInstance()->doSearchAclFilter(false);
        $containers = Tinebase_Container::getInstance()->search(
            new Tinebase_Model_ContainerFilter($filter),
            new Tinebase_Model_Pagination(['sort' => 'owner_id'])
        );
        Tinebase_Container::getInstance()->doSearchAclFilter(true);

        $resultArray = [];
        $users = [];
        $groups = [];
        foreach ($containers as $container) {
            $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($container, true);
            $readGrants = $grants->filter('readGrant', true);
            foreach ($readGrants as $readGrant) {
                // remove all containers with only personal grants
                if ($readGrant->account_id === $container->owner_id) {
                    continue;
                }
                try {
                    if (! isset($users[$container->owner_id])) {
                        $users[$container->owner_id] = Tinebase_User::getInstance()->getFullUserById($container->owner_id)->accountLoginName;
                    }

                    $grantArray = $readGrant->toArray();
                    unset($grantArray['id']);

                    switch ($readGrant->account_type) {
                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                            if (!isset($users[$readGrant->account_id])) {
                                $users[$readGrant->account_id] = Tinebase_User::getInstance()->getFullUserById($readGrant->account_id)->accountLoginName;
                            }
                            $accountName = $users[$readGrant->account_id];
                            break;
                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                            if (!isset($groups[$readGrant->account_id])) {
                                $members = Tinebase_Group::getInstance()->getGroupMembers($readGrant->account_id);
                                $membernames = [];
                                foreach ($members as $member) {
                                    if (! isset($users[$member])) {
                                        $users[$member] = Tinebase_User::getInstance()->getFullUserById($member)->accountLoginName;
                                    }
                                    $membernames[] = $users[$member];
                                }
                                $groups[$readGrant->account_id] = [
                                    'name' => Tinebase_Group::getInstance()->getGroupById($readGrant->account_id)->name,
                                    'members' => $membernames
                                ];
                            }
                            $accountName = $groups[$readGrant->account_id];
                            break;
                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                            $accountName = Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE;
                            break;
                    }
                } catch (Tinebase_Exception_NotFound $tenf) {
                    Tinebase_Exception::log($tenf);
                    continue;
                } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                    Tinebase_Exception::log($ternd);
                    continue;
                }
                $grantArray['accountName'] = $accountName;
                $resultArray[$users[$container->owner_id]][$container->name][] = $grantArray;
            }
        }
        echo Zend_Json::encode($resultArray);

        return 1;
    }

    /**
     * @param $event
     */
    protected function _printShortEvent($event)
    {
        echo "Event '" . substr($event->summary, 0, 30) . "' (" . $event->dtstart->toString() . ' - ' . $event->dtend->toString() .")\n";
    }
    
    /**
     * compare two calendars and create report of missing/different events
     * 
     * @param Zend_Console_Getopt $_opts
     */
    public function compareCalendars(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('cal1', 'cal2'));
        
        $from = isset($args['from']) ? new Tinebase_DateTime($args['from']) : Tinebase_DateTime::now();
        $until = isset($args['until']) ? new Tinebase_DateTime($args['until']) : Tinebase_DateTime::now()->addYear(1);
        
        echo "Comparing Calendars with IDs " . $args['cal1'] . " / " . $args['cal2'] . " (" . $from . " - " . $until . ")...\n"
            . " (All times in UTC)\n";
        
        $result = Calendar_Controller_Event::getInstance()->compareCalendars($args['cal1'], $args['cal2'], $from, $until);
        
        // print results
        echo "-----------------------------\n";
        echo "Found " . count($result['matching']) . " matching Events\n";
        echo "-----------------------------\n";
        echo "Found " . count($result['changed']) . " changed Events (same summary but different time):\n";
        foreach ($result['changed'] as $event) {
            $this->_printShortEvent($event);
        }
        echo "-----------------------------\n";
        echo "Found " . count($result['missingInCal1']) . " missing Events from calendar " . $args['cal1'] . ":\n";
        foreach ($result['missingInCal1'] as $event) {
            $this->_printShortEvent($event);
        }
        echo "-----------------------------\n";
        echo "Found " . count($result['missingInCal2']) . " missing Events from calendar " . $args['cal2'] . ":\n";
        foreach ($result['missingInCal2'] as $event) {
            $this->_printShortEvent($event);
        }
        echo "-----------------------------\n";
    }
    
    public function repairAttendee(Zend_Console_Getopt $_opts)
    {
        $args = $this->_parseArgs($_opts, array('cal'));
        $from = isset($args['from']) ? new Tinebase_DateTime($args['from']) : Tinebase_DateTime::now();
        $until = isset($args['until']) ? new Tinebase_DateTime($args['until']) : Tinebase_DateTime::now()->addYear(1);
        $dry = $_opts->d;
        
        echo "Repairing Calendar with IDs " . $args['cal'] .  "(" . $from . " - " . $until . ")...\n";
        if ($dry) {
            echo "-- DRY RUN --\n";
        }
        
        $result = Calendar_Controller_Event::getInstance()->repairAttendee($args['cal'], $from, $until, $dry);
        echo "Repaired " . $result . " events\n";
    }

    /**
     * remove future fallout exdates for events in given calendars
     * 
     * @param $_opts
     */
    public function restoreFallouts($_opts)
    {
        $now = Tinebase_DateTime::now();
        $args = $this->_parseArgs($_opts, array('cal'));
        $events = Calendar_Controller_MSEventFacade::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $args['cal']),
            array('field' => 'is_deleted', 'operator' => 'equals', 'value' => 0),
        )));

        $events->sort('dtstart');
        foreach($events as $event) {
            if ($event->exdate instanceof Tinebase_Record_RecordSet) {
                $fallouts = $event->exdate->filter('id', null);
                if ($fallouts->count()) {
                    $dates = [];
                    foreach($fallouts as $fallout) {
                        if ($fallout->dtstart > $now) {
                            $dates[] = $fallout->dtstart->format('d.m.Y');
                            $event->exdate->removeRecord($fallout);
                        }
                    }
                    if (! empty($dates)) {
                        echo "restoring " . implode($dates,',') . ' for event ' . "'{$event->summary}' ({$event->id})\n";
                        Calendar_Controller_MSEventFacade::getInstance()->update($event);
                    }
                }
            }
        }
    }

    /**
     * update event locations (before 2018.11 there was no autoupdate on resource rename)
     * allowed params:
     *      --updatePastEvents (otherwise from now on)
     *      -d (dry run)
     */
    public function updateEventLocations(Zend_Console_Getopt $opts)
    {
        $args = $this->_parseArgs($opts);
        $updatePastEvents = isset($args['updatePastEvents']) ? !!$args['updatePastEvents'] : false;
        $dry = $opts->d;
        $this->_addOutputLogWriter(6);

        $resources = Calendar_Controller_Resource::getInstance()->getAll();

        foreach($resources as $resource) {
            // collect old names from history
            $names = [];
            $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getModifications(
                'Calendar',
                $resource->getId(),
                Calendar_Model_Resource::class,
                'Sql',
                $resource->creation_time
            )->filter('change_type', Tinebase_Timemachine_ModificationLog::UPDATED);

            /** @var Tinebase_Model_ModificationLog $modification */
            foreach($modifications as $modification) {
                $modified_attribute = $modification->modified_attribute;

                // legacy code
                if (!empty($modified_attribute) && $modified_attribute == 'name') {
                    $names[] = $modification->old_value;

                // new code modificationLog implementation
                } else {
                    /** @var Tinebase_Record_Diff $diff */
                    $diff = new Tinebase_Record_Diff(json_decode($modification->new_value, true));
                    if (isset($diff->oldData['name'])) {
                        $names[] = $diff->oldData['name'];
                    }
                }
            }

            if (!empty($names)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Ressource [{$resource->id}] '{$resource->name}' had this names before " . print_r($names, true));
                if (! $dry) {
                    Calendar_Controller_Resource::getInstance()->updateEventLocations($names, $resource->name, $updatePastEvents);
                }
            }

        }

    }

    /**
     * fetch data from ics file and put it into the matching tine20 events
     * example:
     *  php tine20.php --method=Calendar.fetchDataFromIcs -v -d --username test --password test my.ics
     *
     * @param $_opts
     * @return int
     *
     * NOTE: currently only alarms are supported
     *
     * TODO add more fields that can be processed / imported
     */
    public function fetchDataFromIcs($_opts)
    {
        // might not be necessary on your system
        ini_set('memory_limit', '1G');

        $args = $this->_parseArgs($_opts, array(), 'filename');

        if ($_opts->d) {
            $args['dryrun'] = 1;
            if ($_opts->v) {
                echo "Doing dry run.\n";
            }
        }

        if (! isset($args['filename'])) {
            if ($_opts->v) {
                echo "No filename given.\n";
            }
            return 1;
        }

        $now = Tinebase_DateTime::now();
        foreach ((array) $args['filename'] as $filename) {
            $resource = fopen($filename, 'r');

            // TODO allow to define the client
            $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX);
            $events = $converter->toTine20RecordSet($resource);

            if ($_opts->v) {
                echo "Got " . count($events) . " events to process from " . $filename . ".\n";
            }

            foreach ($events as $event) {
                if (count($event->alarms) > 0) {
                    $alarmsToSet = new Tinebase_Record_RecordSet(Tinebase_Model_Alarm::class);

                    foreach ($event->alarms as $alarm) {
                        // check if alarms are pending and alarm time is after NOW()
                        if ($alarm->sent_status === Tinebase_Model_Alarm::STATUS_PENDING && $alarm->alarm_time->isLater($now)) {
                            $alarmsToSet->addRecord($alarm);
                        }
                    }
                    if (count($alarmsToSet) > 0) {
                        // check if event is in db
                        $existingEventsFilter = new Calendar_Model_EventFilter(array(
                            array('field' => 'uid', 'operator' => 'in', 'value' => $event->uid),
                        ));
                        $existingEvents = Calendar_Controller_Event::getInstance()->search($existingEventsFilter);
                        if (count($existingEvents) == 1) {
                            if ($_opts->v) {
                                echo "Found matching event with uid" . $event->uid . "\n";
                            }
                            if (! $_opts->d) {
                                // save alarms!
                                $tine20Event = $existingEvents->getFirstRecord();
                                $tine20Event = Calendar_Controller_Event::getInstance()->get($tine20Event);
                                if ($tine20Event->alarms && count($tine20Event->alarms) > 0) {
                                    if ($_opts->v) {
                                        echo "Preserving existing alarms ...\n";
                                    }
                                } else {
                                    // TODO allow to configure this - currently alarms are only set for the current user attender
                                    $currentUserAttender = new Calendar_Model_Attender(array(
                                        'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                                        'user_id'   => Tinebase_Core::getUser()->contact_id
                                    ));
                                    $alarmsToSet->setOption('attendee', Calendar_Controller_Alarm::attendeeToOption($currentUserAttender));
                                    if ($_opts->v) {
                                        echo "Setting alarms: ...\n";
                                        print_r($alarmsToSet->toArray());
                                    }

                                    $tine20Event->alarms = $alarmsToSet;
                                    // TODO make this public - atm this needs to be done by hand
                                    // Calendar_Controller_Event::getInstance()->_saveAlarms($tine20Event);
                                }
                            }
                        } else if (count($existingEvents) > 1) {
                            if ($_opts->v) {
                                echo "Got " . count($existingEvents) . " events for uid" . $event->uid . ". skipping ...\n";
                            }
                        } else if ($_opts->v) {
                            echo "No matching event with uid" . $event->uid . " found.\n";
                        }
                    } else if ($_opts->v) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($event->alarms->toArray(), true));
                        echo "Event with " . $event->uid . " has no pending alarms in the future.\n";
                    }
                } else if ($_opts->v) {
                    echo "Event with " . $event->uid . " has no alarms.\n";
                }
            }

            fclose($resource);
        }

        return 0;
    }
}
