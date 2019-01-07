<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * json interface for calendar
 * 
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * app name
     * 
     * @var string
     */
    protected $_applicationName = 'Calendar';
    
    /**
     * creates an exception instance of a recurring event
     *
     * NOTE: deleting persistent exceptions is done via a normal delete action
     *       and handled in the controller
     * 
     * @param  array       $recordData
     * @param  bool        $deleteInstance
     * @param  bool        $deleteAllFollowing
     * @param  bool        $checkBusyConflicts
     * @return array       exception Event | updated baseEvent
     * 
     * @todo replace $_allFollowing param with $range
     * @deprecated replace with create/update/delete
     */
    public function createRecurException($recordData, $deleteInstance, $deleteAllFollowing, $checkBusyConflicts = FALSE)
    {
        $event = new Calendar_Model_Event(array(), TRUE);
        $event->setFromJsonInUsersTimezone($recordData);

        /** @noinspection PhpDeprecationInspection */
        $returnEvent = Calendar_Controller_Event::getInstance()->createRecurException($event, $deleteInstance, $deleteAllFollowing, $checkBusyConflicts);
        
        return $this->getEvent($returnEvent->getId());
    }
    
    /**
     * deletes existing events
     *
     * @param array $ids
     * @param string $range
     * @return string
     */
    public function deleteEvents($ids, $range = Calendar_Model_Event::RANGE_THIS)
    {
        return $this->_delete($ids, Calendar_Controller_Event::getInstance(), array($range));
    }
    
    /**
     * deletes existing resources
     *
     * @param array $ids
     * @return string
     */
    public function deleteResources($ids)
    {
        return $this->_delete($ids, Calendar_Controller_Resource::getInstance());
    }
    
    /**
     * deletes a recur series
     *
     * @param  array $recordData
     * @return array
     */
    public function deleteRecurSeries($recordData)
    {
        $event = new Calendar_Model_Event(array(), TRUE);
        $event->setFromJsonInUsersTimezone($recordData);
        
        Calendar_Controller_Event::getInstance()->deleteRecurSeries($event);
        return array('success' => true);
    }
    
    /**
     * Return a single event
     *
     * @param   string $id
     * @return  array record data
     */
    public function getEvent($id)
    {
        return $this->_get($id, Calendar_Controller_Event::getInstance());
    }
    
    /**
     * Returns registry data of the calendar.
     *
     * @return mixed array 'variable name' => 'data'
     * 
     * @todo move exception handling (no default calender found) to another place?
     */
    public function getRegistryData()
    {
        $defaultCalendarId = Tinebase_Core::getPreference('Calendar')->getValue(Calendar_Preference::DEFAULTCALENDAR);
        try {
            $defaultCalendarArray = Tinebase_Container::getInstance()->getContainerById($defaultCalendarId)->toArray();
            $defaultCalendarArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultCalendarId)->toArray();
            if ($defaultCalendarArray['type'] != Tinebase_Model_Container::TYPE_SHARED) {
                $defaultCalendarArray['ownerContact'] = Addressbook_Controller_Contact::getInstance()->getContactByUserId($defaultCalendarArray['owner_id'])->toArray();
            }
        } catch (Exception $e) {
            // remove default cal pref
            Tinebase_Core::getPreference('Calendar')->deleteUserPref(Calendar_Preference::DEFAULTCALENDAR);
            $defaultCalendarArray = array();
        }
        
        $importDefinitions = $this->_getImportDefinitions();
        $allCalendarResources = Calendar_Controller_Resource::getInstance()->getAll()->toArray();
        
        $registryData = array(
            'defaultContainer'          => $defaultCalendarArray,
            'defaultImportDefinition'   => $importDefinitions['default'],
            'importDefinitions'         => $importDefinitions,
            'calendarResources'         => $allCalendarResources
        );
        
        return $registryData;
    }
    
    /**
     * get default addressbook
     * 
     * @return array
     */
    public function getDefaultCalendar() 
   {
        $defaultCalendar = Calendar_Controller_Event::getInstance()->getDefaultCalendar();
        $defaultCalendarArray = $defaultCalendar->toArray();
        $defaultCalendarArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultCalendar->getId())->toArray();
        Tinebase_Core::getLogger()->notice(print_r($defaultCalendar, true));
        return $defaultCalendarArray;
    }
    
    /**
     * import contacts
     * 
     * @param string $tempFileId to import
     * @param string $definitionId
     * @param array $importOptions
     * @param array $clientRecordData
     * @return array
     */
    public function importEvents($tempFileId, $definitionId, $importOptions, $clientRecordData = array())
    {
        return $this->_import($tempFileId, $definitionId, $importOptions, $clientRecordData);
    }
    
    /**
     * creates a scheduled import
     * 
     * @param string $remoteUrl
     * @param string $interval
     * @param string $importOptions
     * @return array
     */
    public function importRemoteEvents($remoteUrl, $interval, $importOptions)
    {
        // Determine which plugin should be used to import
        switch ($importOptions['sourceType']) {
            case 'remote_caldav':
                $plugin = 'Calendar_Import_CalDAV';
                break;
            default:
                $plugin = 'Calendar_Import_Ical';
        }

        $record = Tinebase_Controller_ScheduledImport::getInstance()->create( new Tinebase_Model_Import(array(
            'source'            => $remoteUrl,
            'sourcetype'        => Tinebase_Model_Import::SOURCETYPE_REMOTE,
            'interval'          => $interval,
            'options'           => array_replace($importOptions, array(
                'plugin' => $plugin,
                'importFileByScheduler' => $importOptions['sourceType'] != 'remote_caldav',
            )),
            'model'             => 'Calendar_Model_Event',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        ), true));

        $result = $this->_recordToJson($record);

        return $result;
    }
    
    /**
     * get addressbook import definitions
     * 
     * @return array
     * 
     * @todo generalize this
     */
    protected function _getImportDefinitions()
    {
        $filter = new Tinebase_Model_ImportExportDefinitionFilter(array(
            array('field' => 'application_id',  'operator' => 'equals', 'value' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()),
            array('field' => 'type',            'operator' => 'equals', 'value' => 'import'),
        ));
        
        $definitionConverter = new Tinebase_Convert_ImportExportDefinition_Json();
        
        try {
            $importDefinitions = Tinebase_ImportExportDefinition::getInstance()->search($filter);
            $defaultDefinition = $this->_getDefaultImportDefinition($importDefinitions);
            $result = array(
                'results'               => $definitionConverter->fromTine20RecordSet($importDefinitions),
                'totalcount'            => count($importDefinitions),
                'default'               => ($defaultDefinition) ? $definitionConverter->fromTine20Model($defaultDefinition) : array(),
            );
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            $result = array(
                array(
                    'results'               => array(),
                    'totalcount'            => 0,
                    'default'               => array(),
                )
            );
        }
        
        return $result;
    }
    
    /**
     * get default definition
     * 
     * @param Tinebase_Record_RecordSet $_importDefinitions
     * @return Tinebase_Model_ImportExportDefinition
     * 
     * @todo generalize this
     */
    protected function _getDefaultImportDefinition($_importDefinitions)
    {
        try {
            $defaultDefinition = Tinebase_ImportExportDefinition::getInstance()->getByName('cal_import_ical');
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (count($_importDefinitions) > 0) {
                $defaultDefinition = $_importDefinitions->getFirstRecord();
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' No import definitions found for Calendar');
                $defaultDefinition = NULL;
            }
        }
        
        return $defaultDefinition;
    }
    
    /**
     * Return a single resouece
     *
     * @param   string $id
     * @return  array record data
     */
    public function getResource($id)
    {
        return $this->_get($id, Calendar_Controller_Resource::getInstance());
    }

    /**
     * @param array $_event
     *   attendee to find free timeslot for
     *   dtstart, dtend -> to calculate duration
     *   rrule optional
     * @param array $_options
     *  'from'         datetime (optional, defaults event->dtstart) from where to start searching
     *  'until'        datetime (optional, defaults 2 years) until when to giveup searching
     *  'constraints'  array    (optional, defaults 8-20 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,TU,WE,TH,FR') array of timespecs to limit the search with
     *     timespec:
     *       dtstart,
     *       dtend,
     *       rrule ... for example "work days" -> 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,TU,WE,TH,FR'
     * @return array
     */
    public function searchFreeTime($_event, $_options)
    {
        $eventRecord = new Calendar_Model_Event(array(), TRUE);
        $eventRecord->setFromJsonInUsersTimezone($_event);

        if (isset($_options['from']) || isset($_options['until'])) {
            $tmpData = array();
            if (isset($_options['from'])) {
                $tmpData['dtstart'] = $_options['from'];
            }
            if (isset($_options['until'])) {
                $tmpData['dtend'] = $_options['until'];
            }
            $tmpEvent = new Calendar_Model_Event(array(), TRUE);
            $tmpEvent->setFromJsonInUsersTimezone($tmpData);
            if (isset($_options['from'])) {
                $_options['from'] = $tmpEvent->dtstart;
            }
            if (isset($_options['until'])) {
                $_options['until'] = $tmpEvent->dtend;
            }
        }

        $timeSearchStopped = null;
        try {
            $records = Calendar_Controller_Event::getInstance()->searchFreeTime($eventRecord, $_options);
        } catch (Calendar_Exception_AttendeeBusy $ceab) {
            $event = $this->_recordToJson($ceab->getEvent());
            $timeSearchStopped = $event['dtend'];
            $records = new Tinebase_Record_RecordSet('Calendar_Model_Event', array());
        }

        $records->attendee = array();
        $result = $this->_multipleRecordsToJson($records, null, null);

        return array(
            'results'           => $result,
            'totalcount'        => count($result),
            'filter'            => array(),
            'timeSearchStopped' => $timeSearchStopped,
        );
    }
    
    /**
     * Search for events matching given arguments
     *
     * @param array $filter
     * @param array $paging
     * @param boolean $addFixedCalendars
     * @return array
     */
    public function searchEvents($filter, $paging, $addFixedCalendars = true)
    {
        $controller = Calendar_Controller_Event::getInstance();
        
        $decodedPagination = $this->_prepareParameter($paging);
        $pagination = new Tinebase_Model_Pagination($decodedPagination);
        $clientFilter = $filter = $this->_decodeFilter($filter, 'Calendar_Model_EventFilter');

        if ($addFixedCalendars) {
            // find out if fixed calendars should be used
            $fixedCalendarIds = Calendar_Controller_Event::getInstance()->getFixedCalendarIds();
            $useFixedCalendars = is_array($fixedCalendarIds) && !empty($fixedCalendarIds);
        } else {
            $useFixedCalendars = false;
        }
        
        $periodFilter = $filter->getFilter('period');
        
        // add period filter per default to prevent endless search
        if (! $periodFilter) {
            $periodFilter = $this->_getDefaultPeriodFilter();
            // periodFilter will be added to fixed filter when using fixed calendars
            if (! $useFixedCalendars) {
                $filter->addFilter($periodFilter);
            }
        }
        
        // add fixed calendar on demand
        if ($useFixedCalendars) {
            $fixed = new Calendar_Model_EventFilter(array(), 'AND');
            $fixed->addFilter( new Tinebase_Model_Filter_Text('container_id', 'in', $fixedCalendarIds));
            
            $fixed->addFilter($periodFilter);
            
            $og = new Calendar_Model_EventFilter(array(), 'OR');
            $og->addFilterGroup($fixed);
            $og->addFilterGroup($clientFilter);
            
            $filter = new Calendar_Model_EventFilter(array(), 'AND');
            $filter->addFilterGroup($og);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' events filter: ' . print_r($filter->toArray(), true));

        $records = $controller->search($filter, $pagination, FALSE);
        
        $result = $this->_multipleRecordsToJson($records, $clientFilter, $pagination);
        
        return array(
            'results'       => $result,
            'totalcount'    => count($result),
            'filter'        => $clientFilter->toArray(TRUE),
        );
    }

    /**
     * get default period filter
     * 
     * @return Calendar_Model_PeriodFilter
     */
    protected function _getDefaultPeriodFilter()
    {
        $now = Tinebase_DateTime::now()->setTime(0,0,0);
        
        $from = $now->getClone()->subMonth(Calendar_Config::getInstance()->get(Calendar_Config::MAX_JSON_DEFAULT_FILTER_PERIOD_FROM, 0));
        $until = $now->getClone()->addMonth(Calendar_Config::getInstance()->get(Calendar_Config::MAX_JSON_DEFAULT_FILTER_PERIOD_UNTIL, 1));
        $periodFilter = new Calendar_Model_PeriodFilter(array(
            'field' => 'period',
            'operator' => 'within',
            'value' => array("from" => $from, "until" => $until)
        ));
        
        return $periodFilter;
    }
    
    /**
     * Search for resources matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchResources($filter, $paging)
    {
        return $this->_search($filter, $paging, Calendar_Controller_Resource::getInstance(), 'Calendar_Model_ResourceFilter', true);
    }
    
    /**
     * creates/updates an event / recur
     *
     * WARNING: the Calendar_Controller_Event::create method is not conform to the regular interface!
     *          The parent's _save method doesn't work here!
     *
     * @param   array   $recordData
     * @param   bool    $checkBusyConflicts
     * @param   string  $range
     * @return  array   created/updated event
     */
    public function saveEvent($recordData, $checkBusyConflicts = FALSE, $range = Calendar_Model_Event::RANGE_THIS)
    {
        $record = new Calendar_Model_Event([], true);
        $record->setFromJsonInUsersTimezone($recordData);

        // if there are dependent records, set the timezone of them and add them to a recordSet
        $this->_dependentRecordsFromJson($record);

        if ((empty($record->id))) {
            $savedRecord = Calendar_Controller_Event::getInstance()->create($record, $checkBusyConflicts, false);
        } else {
            $savedRecord = Calendar_Controller_Event::getInstance()->update($record, $checkBusyConflicts, $range, false);
        }

        return $this->_recordToJson($savedRecord);
    }

    /**
     * creates/updates a Resource
     *
     * @param   array   $recordData
     * @return  array   created/updated Resource
     */
    public function saveResource($recordData)
    {
        $recordData['grants'] = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', $recordData['grants']);
        if(array_key_exists ('max_number_of_people', $recordData) && $recordData['max_number_of_people'] == '') {
           $recordData['max_number_of_people'] = null;
        }
        
        return $this->_save($recordData, Calendar_Controller_Resource::getInstance(), 'Resource');
    }
    
    /**
     * sets attendee status for an attender on the given event
     * 
     * NOTE: for recur events we implicitly create an exceptions on demand
     *
     * @param  array         $eventData
     * @param  array         $attenderData
     * @param  string        $authKey
     * @return array         complete event
     */
    public function setAttenderStatus($eventData, $attenderData, $authKey)
    {
        $event    = new Calendar_Model_Event($eventData);
        $attender = new Calendar_Model_Attender($attenderData);
        
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($event, $attender, $authKey);
        
        return $this->getEvent($event->getId());
    }
    
    /**
     * updated a recur series
     *
     * @param  array $recordData
     * @param  bool  $checkBusyConflicts
     * @noparamyet  JSONstring $returnPeriod NOT IMPLEMENTED YET
     * @return array 
     */
    public function updateRecurSeries($recordData, $checkBusyConflicts = FALSE /*, $returnPeriod*/)
    {
        $recurInstance = new Calendar_Model_Event(array(), TRUE);
        $recurInstance->setFromJsonInUsersTimezone($recordData);
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(print_r($recurInstance->toArray(), true));
        
        $baseEvent = Calendar_Controller_Event::getInstance()->updateRecurSeries($recurInstance, $checkBusyConflicts);
        
        return $this->getEvent($baseEvent->getId());
    }
    
    /**
     * prepares an iMIP (RFC 6047) Message
     * 
     * @param array|Calendar_Model_iMIP $iMIP
     * @return array prepared iMIP part
     */
    public function iMIPPrepare($iMIP)
    {
        $iMIPMessage = $iMIP instanceof Calendar_Model_iMIP ? $iMIP : new Calendar_Model_iMIP($iMIP);
        $iMIPFrontend = new Calendar_Frontend_iMIP();
        
        $iMIPMessage->preconditionsChecked = FALSE;
        $iMIPFrontend->prepareComponent($iMIPMessage);
        $iMIPMessage->setTimezone(Tinebase_Core::getUserTimezone());
        return $iMIPMessage->toArray();
    }
    
    /**
     * process an iMIP (RFC 6047) Message
     * 
     * @param array  $iMIP
     * @param string $status
     * @return array prepared iMIP part
     */
    public function iMIPProcess($iMIP, $status=null)
    {
        $iMIPMessage = new Calendar_Model_iMIP($iMIP);
        $iMIPFrontend = new Calendar_Frontend_iMIP();
        
        $iMIPFrontend->process($iMIPMessage, $status);
        
        return $this->iMIPPrepare($iMIPMessage);
    }

    /**
     * @param array $attendee
     * @return array
     */
    public function resolveGroupMembers($attendee)
    {
        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $attendee);
        Calendar_Model_Attender::resolveGroupMembers($attendee);
        Calendar_Model_Attender::resolveAttendee($attendee,false);

        return $attendee->toArray();
    }

    /**
     * @param array $attendee
     * @param array $events single event or set of events
     * @param array $ignoreUIDs
     * @return array single fbInfo or array of eventid => fbinfo
     */
    public function getFreeBusyInfo($attendee, $events = [], $ignoreUIDs = array())
    {
        $events = array_filter($events);

        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $attendee);
        $calendarController = Calendar_Controller_Event::getInstance();
        $fbInfo = [];
        $periods = [];
        $aggregatedPeriods = [];

        foreach($events as $event) {
            $eventRecord = new Calendar_Model_Event([], TRUE);
            $eventRecord->setFromJsonInUsersTimezone($event);

            if ($eventRecord->dtstart === null || empty($eventRecord->getId())) {
                continue;
            }

            if (empty($eventRecord->uid)) {
                $eventRecord->uid = Tinebase_Record_Abstract::generateUID();
            }

            $eventPeriods = $calendarController->getBlockingPeriods($eventRecord, [
                'from'  => $eventRecord->dtstart,
                'until' => $eventRecord->dtstart->getClone()->addMonth(2)
            ], true);
            usort($eventPeriods, function ($a, $b) {
                return $a['from']->compare($b['from']);
            });
            $fbInfo[$eventRecord->getId()] = [];
            $periods[$eventRecord->getId()] = $eventPeriods;
            $aggregatedPeriods = array_merge($aggregatedPeriods, $eventPeriods);

            /*$periods = $calendarController->getBlockingPeriods($eventRecord, [
                'from'  => $eventRecord->dtstart,
                'until' => $eventRecord->dtstart->getClone()->addMonth(2)
            ]);

            $fbInfo[$eventRecord->getId()] = $calendarController->getFreeBusyInfo($periods, $attendee, $ignoreUIDs)->toArray();*/
        }

        if (count($aggregatedPeriods = $this->_reduceAggregatePeriods($aggregatedPeriods)) === 0) {
            return $fbInfo;
        }

        $allFb = $calendarController->getFreeBusyInfo($this->_createPeriodFilter($aggregatedPeriods), $attendee,
            $ignoreUIDs);
        if ($allFb->count() === 0) {
            return $fbInfo;
        }
        $allFb->sort(function (Calendar_Model_FreeBusy $a, Calendar_Model_FreeBusy $b) {
            return $a->dtstart->compare($b->dtstart);
        });
        $userTimeZone = Tinebase_Core::getUserTimezone();
        $allFb->setTimezone($userTimeZone);

        foreach ($periods as $eventId => $eventPeriods) {
            $seekTo = 0;
            foreach ($eventPeriods as $period) {
                $period['from']->setTimezone($userTimeZone);
                $period['until']->setTimezone($userTimeZone);
                if ($period['from']->compare($allFb->getLastRecord()->dtend) !== -1) {
                    break;
                }
                /** @var ArrayIterator $iterator */
                $iterator = $allFb->getIterator();
                $iterator->seek($seekTo);
                $i = $seekTo;
                $lastDtStart = null;
                while ($iterator->valid()) {
                    /** @var Calendar_Model_FreeBusy $fb */
                    $fb = $iterator->current();
                    if ($period['from']->compare($fb->dtend) === -1) {
                        if ($period['until']->compare($fb->dtstart) === 1) {
                            // cache toArray result?
                            $fbInfo[$eventId][] = $fb->toArray();
                        } else {
                            continue 2;
                        }
                        ++$i;
                    } elseif ($lastDtStart === null) {
                        $lastDtStart = $fb->dtstart;
                    } elseif ($lastDtStart->compare($fb->dtstart) !== 0) {
                        break;
                    }
                    $iterator->next();
                }
                $seekTo = $i;
                if ($seekTo >= $allFb->count()) {
                    break;
                }
            }
        }

        return $fbInfo;
    }

    /**
     * @param array $periods
     * @return Calendar_Model_EventFilter
     */
    protected function _createPeriodFilter(array $periods)
    {
        $periodFilters = [];
        foreach ($periods as $period) {
            $periodFilters[] = [
                'field' => 'period',
                'operator' => 'within',
                'value' => $period,
            ];
        }
        return new Calendar_Model_EventFilter($periodFilters, Tinebase_Model_Filter_FilterGroup::CONDITION_OR);
    }

    /**
     * @param array $periods
     * @return array
     */
    protected function _reduceAggregatePeriods(array $periods)
    {
        if (count($periods) === 0) {
            return [];
        }
        usort($periods, function ($a, $b) {
            return $a['from']->compare($b['from']);
        });

        reset($periods);
        $result = [current($periods)];
        $i = 0;
        foreach ($periods as $period) {
            if ($result[$i]['until']->compare($period['from']) !== -1) {
                if ($result[$i]['until']->compare($period['until']) === -1) {
                    $result[$i]['until'] = $period['until'];
                }
            } else {
                $result[++$i] = $period;
            }
        }

        return $result;
    }

    /**
     * @param array $filter
     * @param array $paging
     * @param array $events
     * @param array $ignoreUIDs
     * @return array
     */
    public function searchAttenders($filter = [], $paging = [], $events = [], $ignoreUIDs = [])
    {
        // Might contain an empty value, array filter should clean it up
        $events = array_filter($events);

        $filters = array();
        foreach($filter as $f) {
            switch($f['field']) {
                case 'query':
                    $filters['query'] = $f;
                    break;
                default:
                    $filters[$f['field']] = $f['value'];
                    break;
            }
        }
        if (!isset($filters['query'])) {
            $filters['query'] = array('field' => 'query', 'operator' => 'contains', 'value' => '');
        }

        $result = array();
        $addressBookFE = new Addressbook_Frontend_Json();

        if (!isset($filters['type']) || in_array(Calendar_Model_Attender::USERTYPE_USER, $filters['type'])) {
            $contactFilter = array(array('condition' => 'OR', 'filters' => array(
                $filters['query'],
                array('field' => 'path', 'operator' => 'contains', 'value' => $filters['query']['value'])
            )));
            if (isset($filters['userFilter'])) {
                $contactFilter[] = $filters['userFilter'];
            }
            $contactPaging = $paging;
            $contactPaging['sort'] = array('type', 'n_fileas');
            $contactPaging['dir'] = array('DESC', 'ASC');
            $result[Calendar_Model_Attender::USERTYPE_USER] = $addressBookFE->searchContacts($contactFilter, $contactPaging);
        }

        if (!isset($filters['type']) || in_array(Calendar_Model_Attender::USERTYPE_GROUP, $filters['type'])) {
            $groupFilter = array(array('condition' => 'OR', 'filters' => array(
                $filters['query'],
                array('field' => 'path', 'operator' => 'contains', 'value' => $filters['query']['value'])
            )));
            if (isset($filters['groupFilter'])) {
                $groupFilter[] = $filters['groupFilter'];
            }
            $groupFilter[] = array('field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_List::LISTTYPE_GROUP);
            $groupPaging = $paging;
            $groupPaging['sort'] = 'name';
            $result[Calendar_Model_Attender::USERTYPE_GROUP] = $addressBookFE->searchLists($groupFilter, $groupPaging);
        }

        if (!isset($filters['type']) || in_array(Calendar_Model_Attender::USERTYPE_RESOURCE, $filters['type'])) {
            $resourceFilter = array($filters['query']);
            if (isset($filters['resourceFilter'])) {
                $resourceFilter[] = $filters['resourceFilter'];
            }
            $resourcePaging = $paging;
            $resourcePaging['sort'] = 'name';
            $result[Calendar_Model_Attender::USERTYPE_RESOURCE] = $this->searchResources($resourceFilter, $resourcePaging);
        }

        if (empty($events)) {
            $result['freeBusyInfo'] = array();
        } else {
            $attendee = array();
            foreach ($result as $type => $res) {
                foreach ($res['results'] as $r) {
                    if ($type === Calendar_Model_Attender::USERTYPE_GROUP) {
                        if (empty($r['group_id'])) {
                            continue;
                        }
                        $attendee[] = array(
                            'user_id' => $r['group_id'],
                            'user_type' => $type
                        );
                    } else {
                        $attendee[] = array(
                            'user_id' => $r['id'],
                            'user_type' => $type
                        );
                    }
                }
            }

            if (empty($attendee)) {
                $result['freeBusyInfo'] = array();
            } else {
                $result['freeBusyInfo'] = $this->getFreeBusyInfo($attendee, $events, $ignoreUIDs);
            }
        }

        return $result;
    }
}
