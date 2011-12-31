<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * json interface for calendar
 * 
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_applicationName = 'Calendar';
    
    /**
     * creates an exception instance of a recurring event
     *
     * NOTE: deleting persistent exceptions is done via a normal delte action
     *       and handled in the controller
     * 
     * @param  array       $recordData
     * @param  bool        $deleteInstance
     * @param  bool        $deleteAllFollowing
     * @param  bool        $checkBusyConficts
     * @return array       exception Event | updated baseEvent
     */
    public function createRecurException($recordData, $deleteInstance, $deleteAllFollowing, $checkBusyConficts = FALSE)
    {
        $event = new Calendar_Model_Event(array(), TRUE);
        $event->setFromJsonInUsersTimezone($recordData);
        
        $returnEvent = Calendar_Controller_Event::getInstance()->createRecurException($event, $deleteInstance, $deleteAllFollowing, $checkBusyConficts);
        
        return $this->getEvent($returnEvent->getId());
    }
    
    /**
     * deletes existing events
     *
     * @param array $_ids 
     * @return string
     */
    public function deleteEvents($ids)
    {
        return $this->_delete($ids, Calendar_Controller_Event::getInstance());
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
        } catch (Exception $e) {
            // remove default cal pref
            Tinebase_Core::getPreference('Calendar')->deleteUserPref(Calendar_Preference::DEFAULTCALENDAR);
            $defaultCalendarArray = array();
        }
        
        return array(
            // registry setting is called defaultContainer to be compatible to the other apps
            'defaultContainer' => $defaultCalendarArray
        );
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
        
        $decodedPagination = is_array($paging) ? $paging : Zend_Json::decode($paging);
        $pagination = new Tinebase_Model_Pagination($decodedPagination);
        $clientFilter = $filter = $this->_decodeFilter($filter, 'Calendar_Model_EventFilter');
        
        // add fixed calendar on demand
        $fixedCalendars = Tinebase_Config::getInstance()->getConfigAsArray('fixedCalendars', 'Calendar');
        if (is_array($fixedCalendars) && ! empty($fixedCalendars)) {
            $fixed = new Calendar_Model_EventFilter(array(), 'AND');
            $fixed->addFilter( new Tinebase_Model_Filter_Text('container_id', 'in', $fixedCalendars));
            $periodFilter = $filter->getFilter('period');
            if ($periodFilter) {
                $fixed->addFilter($periodFilter);
            }
            
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
     * creates/updates an event
     *
     * @param   array   $recordData
     * @param   bool    $checkBusyConficts
     * @return  array   created/updated event
     */
    public function saveEvent($recordData, $checkBusyConficts = FALSE)
    {
        return $this->_save($recordData, Calendar_Controller_Event::getInstance(), 'Event', 'id', array($checkBusyConficts));
    }
    
    /**
     * creates/updates a Resource
     *
     * @param   array   $recordData
     * @return  array   created/updated Resource
     */
    public function saveResource($recordData)
    {
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
     * @param  bool  $checkBusyConficts
     * @noparamyet  JSONstring $returnPeriod NOT IMPLEMENTED YET
     * @return array 
     */
    public function updateRecurSeries($recordData, $checkBusyConficts = FALSE /*, $returnPeriod*/)
    {
        $recurInstance = new Calendar_Model_Event(array(), TRUE);
        $recurInstance->setFromJsonInUsersTimezone($recordData);
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(print_r($recurInstance->toArray(), true));
        
        $baseEvent = Calendar_Controller_Event::getInstance()->updateRecurSeries($recurInstance, $checkBusyConficts);
        
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
        $iMIPMessage->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        return $iMIPMessage->toArray();
    }
    
    /**
     * process an iMIP (RFC 6047) Message
     * 
     * @param array  $iMIP
     * @pram  string $status
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
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
    	if ($_record instanceof Calendar_Model_Event) {
	    	Calendar_Model_Attender::resolveAttendee($_record->attendee);
	        $this->_resolveRrule($_record);
	        $this->_resolveOrganizer($_record);
    	}
	        
        $recordData = parent::_recordToJson($_record);
        return $recordData;
    }
    
    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Abstract
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination needed for sorting
     * @return array data
     * 
     * @todo perhaps we need to resolveContainerTagsUsers() before  mergeAndRemoveNonMatchingRecurrences(), but i'm not sure about that
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = NULL)
    {
    	if ($_records->getRecordClassName() == 'Calendar_Model_Event') {
	    	if (is_null($_filter)) {
	    		throw new Tinebase_Exception_InvalidArgument('Required argument $_filter is missing');
	    	}
	        
	        Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($_records);
	        
	        Calendar_Model_Attender::resolveAttendee($_records->attendee);
	        $this->_resolveOrganizer($_records);
	        $this->_resolveRrule($_records);
            Calendar_Controller_Event::getInstance()->getAlarms($_records);
            
//            Tinebase_Frontend_Json_Abstract::resolveContainerTagsUsers($_records, $this->_resolveUserFields);
            Calendar_Model_Rrule::mergeAndRemoveNonMatchingRecurrences($_records, $_filter);
            
            $_records->sortByPagination($_pagination);
            
//             $_records->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
//             $_records->convertDates = true;
//             $eventsData = $_records->toArray();
            $eventsData = parent::_multipleRecordsToJson($_records);
	        foreach ($eventsData as $eventData) {
	            if (! array_key_exists(Tinebase_Model_Grants::GRANT_READ, $eventData) || ! $eventData[Tinebase_Model_Grants::GRANT_READ]) {
	                $eventData['notes'] = array();
	                $eventData['tags'] = array();
	            }
	        }
	        
	        return $eventsData;
    	}
          
        return parent::_multipleRecordsToJson($_records);
    }
    
    /**
     * resolves organizer of given event
     *
     * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
     */
    protected function _resolveOrganizer($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet ? $_events : array($_events);
        
        $organizerIds = array();
        foreach ($events as $event) {
            if ($event->organizer) {
                $organizerIds[] = $event->organizer;
            }
        }

        $organizers = Addressbook_Controller_Contact::getInstance()->getMultiple(array_unique($organizerIds), TRUE);
        
        foreach ($events as $event) {
            if ($event->organizer) {
                $idx = $organizers->getIndexById($event->organizer);
                if ($idx !== FALSE) {
                    $event->organizer = $organizers[$idx];
                }
            }
        }
    }
    
    /**
     * resolves rrule of given event
     *
     * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
     */
    protected function _resolveRrule($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet ? $_events : array($_events);
        
        foreach ($events as $event) {
            if ($event->rrule) {
                $event->rrule = Calendar_Model_Rrule::getRruleFromString($event->rrule);
            }
        }
    }
}
