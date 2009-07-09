<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * json interface for calendar
 * @package     Calendar
 */
class Calendar_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_applicationName = 'Calendar';
    
    /**
     * creates an exception instance of a recuring evnet
     *
     * NOTE: deleting persistent exceptions is done via a normal delte action
     *       and handled in the controller
     * 
     * @param  JSONstring  $recordData
     * @param  bool        $deleteInstance
     * @param  bool        $deleteAllFollowing
     * @return array       exception Event | updated baseEvent
     */
    public function createRecurException($recordData, $deleteInstance, $deleteAllFollowing)
    {
        $event = new Calendar_Model_Event(array(), TRUE);
        $event->setFromJsonInUsersTimezone($recordData);
        
        $returnEvent = Calendar_Controller_Event::getInstance()->createRecurException($event, $deleteInstance, $deleteAllFollowing);
        
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
     * deletes a recur series
     *
     * @param  JSONstring $recordData
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
        } catch (Tinebase_Exception_NotFound $tenf) {
            // remove default cal pref
            Tinebase_Core::getPreference('Calendar')->deleteUserPref(Calendar_Preference::DEFAULTCALENDAR);
            $defaultCalendarArray = array();
        }
        
        return array(
            'defaultCalendar' => $defaultCalendarArray
        );
    }
    
    /**
     * Search for events matching given arguments
     *
     * @param string $_filter json encoded
     * @param string $_paging json encoded
     * @return array
     */
    public function searchEvents($filter, $paging)
    {
        return $this->_search($filter, $paging, Calendar_Controller_Event::getInstance(), 'Calendar_Model_EventFilter');
    }
    
    /**
     * creates/updates a event
     *
     * @param   $recordData
     * @return  array created/updated event
     */
    public function saveEvent($recordData)
    {
        return $this->_save($recordData, Calendar_Controller_Event::getInstance(), 'Event');
    }
    
    /**
     * sets attendee status for an attender on the given event
     * 
     * NOTE: for recur events we implicitly create an exceptions on demand
     *
     * @param  JSONstring    $_event
     * @param  JSONstring    $_attendee
     * @param  string        $_authKey
     * @return array         complete event
     */
    public function setAttenderStatus($_event, $_attendee, $_authKey)
    {
        $eventData    = is_array($_event) ? $_event : Zend_Json::decode($_event);
        $attenderData = is_array($_attendee) ? $_attendee : Zend_Json::decode($_attendee);
        
        $event    = new Calendar_Model_Event($eventData);
        $attender = new Calendar_Model_Attender($attenderData);
        
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($event, $attender, $_authKey);
        
        return $this->getEvent($event->getId());
    }
    
    /**
     * updated a recur series
     *
     * @param  JSONstring $recordData
     * @noparamyet  JSONstring $returnPeriod NOTE IMPLMENTED YET
     * @return array 
     */
    public function updateRecurSeries($recordData/*, $returnPeriod*/)
    {
        $recurInstance = new Calendar_Model_Event(array(), TRUE);
        $recurInstance->setFromJsonInUsersTimezone($recordData);
        
        //Tinebase_Core::getLogger()->debug(print_r($recurInstance->toArray(), true));
        
        $baseEvent = Calendar_Controller_Event::getInstance()->updateRecurSeries($recurInstance);
        
        return $this->getEvent($baseEvent->getId());
    }
    
    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        $this->_resolveAttendee($_record->attendee);
        $this->_resolveRrule($_record);
        
        $eventData = parent::_recordToJson($_record);
        return $eventData;
    }
    
    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Abstract
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter)
    {
        Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($_records);
        Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($_records);
        $this->_resolveAttendee($_records->attendee);
        $this->_resolveRrule($_records);
        
        //Tinebase_Core::getLogger()->debug(print_r($_records->toArray(), true));
        
        //compute recurset
         $candidates = $_records->filter('rrule', "/^FREQ.*/", TRUE);
         $period = $_filter->getFilter('period');
         
         $fakeId = microtime();
         foreach ($candidates as $candidate) {
             $exceptions = $_records->filter('recurid', "/^{$candidate->uid}-.*/", TRUE);
             $recurSet = Calendar_Model_Rrule::computeRecuranceSet($candidate, $exceptions, $period->getFrom(), $period->getUntil());
             //Tinebase_Core::getLogger()->debug(print_r($recurSet->toArray(), true));
             //$_records->merge($recurSet);
             foreach ($recurSet as $event) {
                 $_records->addRecord($event);
                 $event->setId('fakeid' . $candidate->uid . $fakeId++);
             }
         }
          
        //Tinebase_Core::getLogger()->debug(print_r($_records->toArray(), true));
        return parent::_multipleRecordsToJson($_records);
    }
    
    /**
     * resolves given attendee for json representation
     *
     * @param array|Tinebase_Record_RecordSet $_attendee 
     * @param unknown_type $_idProperty
     * @param unknown_type $_typeProperty
     */
    protected function _resolveAttendee($_eventAttendee, $_idProperty='user_id', $_typeProperty='user_type') {
        $eventAttendee = $_eventAttendee instanceof Tinebase_Record_RecordSet ? array($_eventAttendee) : $_eventAttendee;
        
        // build type map 
        $typeMap = array();
        
        $tinebaseUser = Tinebase_User::getInstance();
        
        foreach ($eventAttendee as $attendee) {
            // resolve displaycontainers
            Tinebase_Container::getInstance()->getGrantsOfRecords($attendee, Tinebase_Core::getUser(), 'displaycontainer_id');
            
            foreach ($attendee as $attender) {
                $type = $attender->$_typeProperty;
                if ($type === 'user') {
                	$tinebaseUser->resolveUsers($attender, $_idProperty);
                }
                
                // remove status_authkey when editGrant for displaycontainer_id is missing
                if (! is_array($attender->displaycontainer_id) || ! (bool) $attender['displaycontainer_id']['account_grants']['editGrant']) {
                    $attender->status_authkey = NULL;
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