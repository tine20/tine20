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
        
        $returnEvent = Calendar_Controller_Event::getInstance()->createRecurException($event, $deleteInstance, $deleteAllFollowing, $checkBusyConflicts);
        
        return $this->getEvent($returnEvent->getId());
    }
    
    /**
     * deletes existing events
     *
     * @param array $_ids
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
     * @param array $_ids 
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
     * @return void
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
            $defaultCalendarArray['ownerContact'] = Addressbook_Controller_Contact::getInstance()->getContactByUserId($defaultCalendarArray['owner_id'])->toArray();
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

        $credentialCache = Tinebase_Auth_CredentialCache::getInstance();
        $credentials = $credentialCache->cacheCredentials(
            $importOptions['username'],
            $importOptions['password'],
            null,
            /* persist */       true,
            /* valid until */   Tinebase_DateTime::now()->addYear(100)
        );

        $record = Tinebase_Controller_ScheduledImport::getInstance()->createRemoteImportEvent(array(
            'source'            => $remoteUrl,
            'interval'          => $interval,
            'options'           => array_replace($importOptions, array(
                'plugin' => $plugin,
                'importFileByScheduler' => $importOptions['sourceType'] != 'remote_caldav',
                'cid' => $credentials->getId(),
                'ckey' => $credentials->key
            )),
            'model'             => 'Calendar_Model_Event',
            'user_id'           => Tinebase_Core::getUser()->getId(),
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
        ));

        $result = $this->_recordToJson($record);
        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 'container_id:'  .  print_r($result['container_id'], true));

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
     * Search for events matching given arguments
     *
     * @param  array $_filter
     * @param  array $_paging
     * @return array
     */
    public function searchEvents($filter, $paging)
    {
        $controller = Calendar_Controller_Event::getInstance();
        
        $decodedPagination = $this->_prepareParameter($paging);
        $pagination = new Tinebase_Model_Pagination($decodedPagination);
        $clientFilter = $filter = $this->_decodeFilter($filter, 'Calendar_Model_EventFilter');

        // find out if fixed calendars should be used
        $fixedCalendars = Calendar_Config::getInstance()->get(Calendar_Config::FIXED_CALENDARS);
        $useFixedCalendars = is_array($fixedCalendars) && ! empty($fixedCalendars);
        
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
            $fixed->addFilter( new Tinebase_Model_Filter_Text('container_id', 'in', $fixedCalendars));
            
            $fixed->addFilter($periodFilter);
            
            $og = new Calendar_Model_EventFilter(array(), 'OR');
            $og->addFilterGroup($fixed);
            $og->addFilterGroup($clientFilter);
            
            $filter = new Calendar_Model_EventFilter(array(), 'AND');
            $filter->addFilterGroup($og);
        }
        
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
     * @param  array $_filter
     * @param  array $_paging
     * @return array
     */
    public function searchResources($filter, $paging)
    {
        return $this->_search($filter, $paging, Calendar_Controller_Resource::getInstance(), 'Calendar_Model_ResourceFilter');
    }
    
    /**
     * creates/updates an event / recur
     *
     * @param   array   $recordData
     * @param   bool    $checkBusyConflicts
     * @param   string  $range
     * @return  array   created/updated event
     */
    public function saveEvent($recordData, $checkBusyConflicts = FALSE, $range = Calendar_Model_Event::RANGE_THIS)
    {
        return $this->_save($recordData, Calendar_Controller_Event::getInstance(), 'Event', 'id', array($checkBusyConflicts, $range));
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
     * @param array $iMIP
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
}
