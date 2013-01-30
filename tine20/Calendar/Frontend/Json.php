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
        $fixedCalendars = Calendar_Config::getInstance()->get(Calendar_Config::FIXED_CALENDARS, new Tinebase_Config_Struct(array()))->toArray();
        if (is_array($fixedCalendars) && ! empty($fixedCalendars)) {
            $fixed = new Calendar_Model_EventFilter(array(), 'AND');
            $fixed->addFilter( new Tinebase_Model_Filter_Text('container_id', 'in', $fixedCalendars));
            
            $periodFilter = $filter->getFilter('period');
            
            // add period filter per default to prevent endless search
            if (!$periodFilter) {
                $now = new Tinebase_DateTime();
                $inAmonth = clone $now;
                $inAmonth->addMonth(1);
                $periodFilter = new Calendar_Model_PeriodFilter(array('field' => 'period', 'operator' => 'within', 'value' => array("from" => $now, "until" => $inAmonth)));
            }
            
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
     * @return  array   created/updated event
     */
    public function saveEvent($recordData, $checkBusyConflicts = FALSE)
    {
        return $this->_save($recordData, Calendar_Controller_Event::getInstance(), 'Event', 'id', array($checkBusyConflicts));
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
        $iMIPMessage->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
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
