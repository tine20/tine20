<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Facade for Calendar_Controller_Event
 * 
 * Adopts Tine 2.0 internal event representation to the iTIP (RFC 5546) representations
 * 
 * In iTIP event exceptions are tranfered together/supplement with/to their baseEvents.
 * So with this facade event exceptions are part of the baseEvent and stored in their exdate property:
 * -> Tinebase_Record_RecordSet Calendar_Model_Event::exdate
 * 
 * deleted recur event instances (fall outs) have the property:
 * -> Calendar_Model_Event::is_deleted set to TRUE (MSEvents)
 * 
 * when creating/updating events, make sure to have the original start time (ExceptionStartTime)
 * of recur event instances stored in the property:
 * -> Calendar_Model_Event::recurid
 * 
 * In iTIP Event handling is based on the perspective of a certain user. This user is the 
 * current user per default, but can be switched with
 * Calendar_Controller_MSEventFacade::setCalendarUser(Calendar_Model_Attendee $_calUser)
 * 
 * @package     Calendar
 * @subpackage  Controller
 */
class Calendar_Controller_MSEventFacade implements Tinebase_Controller_Record_Interface
{
    /**
     * @var Calendar_Controller_Event
     */
    protected $_eventController = NULL;
    
    /**
     * @var Calendar_Model_Attender
     */
    protected $_calendarUser = NULL;
    
    /**
     * @var Calendar_Model_EventFilter
     */
    protected $_eventFilter = NULL;
    
    /**
     * @var Calendar_Controller_MSEventFacade
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_eventController = Calendar_Controller_Event::getInstance();
        
        // set default CU
        $this->setCalendarUser(new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => Tinebase_Core::getUser()->contact_id
        )));
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() 
    {
        
    }
    
    /**
     * singleton
     *
     * @return Calendar_Controller_MSEventFacade
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller_MSEventFacade();
        }
        return self::$_instance;
    }
    
    /**
     * get by id
     *
     * @param string $_id
     * @return Calendar_Model_Event
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function get($_id)
    {
        $event = $this->_eventController->get($_id);
        
        return $this->_toiTIP($event);
    }
    
    /**
     * Returns a set of events identified by their id's
     * 
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet of Calendar_Model_Event
     */
    public function getMultiple($_ids)
    {
        $events = $this->_eventController->getMultiple($_ids);
        
        return $this->_toiTIP($events);
    }  
    
    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet of Calendar_Model_Event
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        $events = $this->_eventController->getAll($_orderBy, $_orderDirection);
        
        return $this->_toiTIP($events);
    }
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional    $_filter
     * @param Tinebase_Model_Pagination|optional            $_pagination
     * @param bool                                          $_getRelations
     * @param boolean                                       $_onlyIds
     * @param string                                        $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $eventIds = $this->_getEventIds($_filter, $_action);
        
        if ($_pagination instanceof Tinebase_Model_Pagination) {
            $numEvents = count($eventIds);
            
            $offset = min($_pagination->start, $numEvents);
            $length = min($_pagination->limit, $offset+$numEvents);
            
            $eventIds = array_slice($eventIds, $offset, $length);
        }
        
        if (! $_onlyIds) {
            
            $events =  $this->_eventController->search(new Calendar_Model_EventFilter(array(
                array('field' => 'id', 'operator' => 'in', 'value' => $eventIds)
            )), NULL, FALSE, FALSE, $_action);
            
            // NOTE: it would be correct to wrap this with the search filter, BUT
            //       this breaks webdasv as it fetches its events with a search id OR uid.
            //       ActiveSync sets its syncfilter generically so it's not problem either
//             $oldFilter = $this->setEventFilter($_filter);
            $events = $this->_toiTIP($events);
//             $this->setEventFilter($oldFilter);
        }
        
        return $_onlyIds ? $eventIds : $events;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * NOTE: we don't count exceptions where the user has no access to base event here
     *       so the result might not be precise
     *       
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get') 
    {
        $eventIds = $this->_getEventIds($_filter, $_action);
        
        return count ($eventIds);
    }
    
    /**
     * fetches all eventids for given filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string                            $action
     */
    protected function _getEventIds($_filter, $_action)
    {
        if (! $_filter instanceof Calendar_Model_EventFilter) {
            $_filter = new Calendar_Model_EventFilter();
        }
        
        $recurIdFilter = new Tinebase_Model_Filter_Text('recurid', 'isnull', null);
        $_filter->addFilter($recurIdFilter);
        $baseEventIds = $this->_eventController->search($_filter, NULL, FALSE, TRUE, $_action);
        $_filter->removeFilter($recurIdFilter);

        $baseEventUIDs =  $this->_eventController->search(new Calendar_Model_EventFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => $baseEventIds)
        )), NULL, FALSE, 'uid', $_action);
        
        // add exceptions where the user has no access to the base event as baseEvents
        $uidFilter = new Tinebase_Model_Filter_Text('uid', 'notin', $baseEventUIDs);
        $recurIdFilter = new Tinebase_Model_Filter_Text('recurid', 'notnull', null);
        $_filter->addFilter($uidFilter);
        $_filter->addFilter($recurIdFilter);
        $baselessExceptionIds = $this->_eventController->search($_filter, NULL, FALSE, TRUE, $_action);
        $_filter->removeFilter($uidFilter);
        $_filter->removeFilter($recurIdFilter);
        
        return array_unique(array_merge($baseEventIds, $baselessExceptionIds));
    }
    
   /**
     * (non-PHPdoc)
     * @see Calendar_Controller_Event::lookupExistingEvent()
     */
    public function lookupExistingEvent($_event)
    {
        $event = $this->_eventController->lookupExistingEvent($_event);
        
        return $event? $this->_toiTIP($event) : NULL;
    }
    
    /*************** add / update / delete *****************/    

    /**
     * add one record
     *
     * @param   Calendar_Model_Event $_record
     * @return  Calendar_Model_Event
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_event)
    {
        if ($_event->recurid) {
            throw new Tinebase_Exception_UnexpectedValue('recur event instances must be saved as part of the base event');
        }
        
        $this->_fromiTIP($_event, new Calendar_Model_Event(array(), TRUE));
        
        $exceptions = $_event->exdate;
        $_event->exdate = NULL;
        
        $_event->assertCurrentUserAsAttendee();
        $savedEvent = $this->_eventController->create($_event);
        
        if ($exceptions instanceof Tinebase_Record_RecordSet) {
            foreach($exceptions as $exception) {
                $exception->assertCurrentUserAsAttendee();
                $this->_prepareException($savedEvent, $exception);
                $this->_eventController->createRecurException($exception, !!$exception->is_deleted);
            }
        }
        
        return $this->_toiTIP($savedEvent);
    }
    
    /**
     * update one record
     *
     * @param   Calendar_Model_Event $_record
     * @param   bool                 $_checkBusyConflicts
     * @return  Calendar_Model_Event
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_event, $_checkBusyConflicts = FALSE)
    {
        if ($_event->recurid) {
            throw new Tinebase_Exception_UnexpectedValue('recur event instances must be saved as part of the base event');
        }
        $currentOriginEvent = $this->_eventController->get($_event->getId());
        $this->_fromiTIP($_event, $currentOriginEvent);
        
        $exceptions = $_event->exdate instanceof Tinebase_Record_RecordSet ? $_event->exdate : new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $exceptions->addIndices(array('is_deleted'));
        $_event->exdate = $exceptions->getOriginalDtStart();
        
        $currentPersistentExceptions = $_event->rrule ? $this->_eventController->getRecurExceptions($_event, FALSE) : new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $newPersistentExceptions = $exceptions->filter('is_deleted', 0);
        
        $migration = $this->_getExceptionsMigration($currentPersistentExceptions, $newPersistentExceptions);
        
        $this->_eventController->delete($migration['toDelete']->getId());
        
        $updatedBaseEvent = $this->_eventController->update($_event, $_checkBusyConflicts);
        
        foreach($migration['toCreate'] as $exception) {
            $this->_prepareException($updatedBaseEvent, $exception);
            $this->_eventController->createRecurException($exception, !!$exception->is_deleted);
        }
        
        foreach($migration['toUpdate'] as $exception) {
            $this->_prepareException($updatedBaseEvent, $exception);
            $this->_eventController->update($exception, $_checkBusyConflicts);
        }
        
        return $this->_toiTIP($updatedBaseEvent);
    }
    
    /**
     * updates an attender status of a event
     *
     * @param  Calendar_Model_Event    $_event
     * @param  Calendar_Model_Attender $_attendee
     * @return Calendar_Model_Event    updated event
     */
    public function attenderStatusUpdate($_event, $_attendee)
    {
        if ($_event->recurid) {
            throw new Tinebase_Exception_UnexpectedValue('recur event instances must be saved as part of the base event');
        }
        
        $exceptions = $_event->exdate instanceof Tinebase_Record_RecordSet ? $_event->exdate : new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $_event->exdate = $exceptions->getOriginalDtStart();
        
        // update base event status
        $attendeeFound = Calendar_Model_Attender::getAttendee($_event->attendee, $_attendee);
        if (!isset($attendeeFound)) {
            throw new Tinebase_Exception_UnexpectedValue('not an attendee');
        }
        $attendeeFound->displaycontainer_id = $_attendee->displaycontainer_id;
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($_event, $attendeeFound, $attendeeFound->status_authkey);
        
        // update exceptions
        foreach($exceptions as $exception) {
            // do not attemt to set status of an deleted instance
            if ($exception->is_deleted) continue;
            
            $exceptionAttendee = Calendar_Model_Attender::getAttendee($exception->attendee, $_attendee);
            
            if (! $exception->getId()) {
                if (! $exceptionAttendee) {
                    // set user status to DECLINED
                    $exceptionAttendee = clone $attendeeFound;
                    $exceptionAttendee->status = Calendar_Model_Attender::STATUS_DECLINED;
                }
                $exceptionAttendee->displaycontainer_id = $_attendee->displaycontainer_id;
                Calendar_Controller_Event::getInstance()->attenderStatusCreateRecurException($exception, $exceptionAttendee, $exceptionAttendee->status_authkey);
            } else {
                if (! $exceptionAttendee) {
                    // we would need to find out the users authkey to decline him -> not allowed!?
                    if (!isset($attendeeFound)) {
                        throw new Tinebase_Exception_UnexpectedValue('not an attendee');
                    }
                }
                $exceptionAttendee->displaycontainer_id = $_attendee->displaycontainer_id;
                Calendar_Controller_Event::getInstance()->attenderStatusUpdate($exception, $exceptionAttendee, $exceptionAttendee->status_authkey);
            }
        }
        
        return $this->get($_event->getId());
    }
    
    /**
     * update multiple records
     * 
     * @param   Tinebase_Model_Filter_FilterGroup $_filter
     * @param   array $_data
     * @return  integer number of updated records
     */
    public function updateMultiple($_what, $_data)
    {
        throw new Tinebase_Exception_NotImplemented('Calendar_Conroller_MSEventFacade::updateMultiple not yet implemented');
    }
    
    /**
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet
     */
    public function delete($_ids)
    {
        $ids = array_unique((array)$_ids);
        $events = $this->getMultiple($ids);
        
        foreach($events as $event) {
            if ($event->exdate !== null) {
                foreach ($event->exdate as $exception) {
                    $exceptionId = $exception->getId();
                    if ($exceptionId) {
                        $ids[] = $exceptionId;
                    }
                }
            }
        }
        
        $this->_eventController->delete($ids);
        return $events;
    }
    
    /**
     * get and resolve all alarms of given record(s)
     * 
     * @param  Tinebase_Record_Interface|Tinebase_Record_RecordSet $_record
     */
    public function getAlarms($_record)
    {
        $events = $_record instanceof Tinebase_Record_RecordSet ? $_record : new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_record));
        
        foreach($events as $event) {
            if ($event->exdate instanceof Tinebase_Record_RecordSet) {
//                 $event->exdate->addIndices(array('is_deleted'));
                $events->merge($event->exdate->filter('is_deleted', 0));
            }
        }
        
        $this->_eventController->getAlarms($events);
    }
    
    /**
     * set displaycontainer for given attendee 
     * 
     * @param Calendar_Model_Event    $_event
     * @param string                  $_container
     * @param Calendar_Model_Attender $_attendee    defaults to calendarUser
     */
    public function setDisplaycontainer($_event, $_container, $_attendee = NULL)
    {
        if ($_event->exdate instanceof Tinebase_Record_RecordSet) {
            foreach ($_event->exdate as $idx => $exdate) {
                self::setDisplaycontainer($exdate, $_container, $_attendee);
            }
        }
        
        $attendeeRecord = Calendar_Model_Attender::getAttendee($_event->attendee, $_attendee ? $_attendee : $this->getCalendarUser());
        
        if ($attendeeRecord) {
            $attendeeRecord->displaycontainer_id = $_container;
        }
    }
    
    /**
     * sets current calendar user
     * 
     * @param Calendar_Model_Attender $_calUser
     * @return Calendar_Model_Attender oldUser
     */
    public function setCalendarUser(Calendar_Model_Attender $_calUser)
    {
        $oldUser = $this->_calendarUser;
        $this->_calendarUser = $_calUser;
        
        return $oldUser;
    }
    
    /**
     * get current calendar user
     * 
     * @return Calendar_Model_Attender
     */
    public function getCalendarUser()
    {
        return $this->_calendarUser;
    }
    
    /**
     * set current event filter for exdate computations
     * 
     * @param  Calendar_Model_EventFilter
     * @return Calendar_Model_EventFilter
     */
    public function setEventFilter($_filter)
    {
        $oldFilter = $this->_eventFilter;
        
        if ($_filter !== NULL) {
            if (! $_filter instanceof Calendar_Model_EventFilter) {
                throw new Tinebase_Exception_UnexpectedValue('not a valid filter');
            }
            $this->_eventFilter = clone $_filter;
            
            $periodFilters = $this->_eventFilter->getFilter('period', TRUE, TRUE);
            foreach((array) $periodFilters as $periodFilter) {
                $periodFilter->setDisabled();
            }
        } else {
            $this->_eventFilter = NULL;
        }
        
        return $oldFilter;
    }
    
    /**
     * get current event filter
     * 
     * @return Calendar_Model_EventFilter
     */
    public function getEventFilter()
    {
        return $this->_eventFilter;
    }
    
    /**
     * filters given eventset for events with matching dtstart
     * 
     * @param Tinebase_Record_RecordSet $_events
     * @param array                     $_dtstarts
     */
    protected function _filterEventsByDTStarts($_events, $_dtstarts)
    {
        $filteredSet = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $allDTStarts = $_events->getOriginalDtStart();
        
        $existingIdxs = array_intersect($allDTStarts, $_dtstarts);
        
        foreach($existingIdxs as $idx => $dtstart) {
            $filteredSet->addRecord($_events[$idx]);
        }
        
        return $filteredSet;
    }

    /**
     * converts a tine20 event to an iTIP event
     * 
     * @param  Calendar_Model_Event $_event
     * @return Calendar_Model_Event 
     */
    protected function _toiTIP($_event)
    {
        if ($_event instanceof Tinebase_Record_RecordSet) {
            foreach($_event as $idx => $event) {
                try {
                    $_event[$idx] = $this->_toiTIP($event);
                } catch (Tinebase_Exception_AccessDenied $ade) {
                    // if we don't have permissions for the exdates, this is likely a freebusy info only -> remove from set
                    $_event->removeRecord($event);
                } catch (Exception $e) {
                    $event->exdate = new Tinebase_Record_RecordSet('Calendar_Model_Event');
                }
            }
            
            return $_event;
        }
        
        // get exdates
        if ($_event->getId() && $_event->rrule) {
            $_event->exdate = $this->_eventController->getRecurExceptions($_event, TRUE, $this->getEventFilter());
            $this->getAlarms($_event);
            
            foreach ($_event->exdate as $exdate) {
                $this->_toiTIP($exdate);
            }
        }
        
        // get alarms for baseEvents w.o. exdate
        if (! $_event->isRecurException() && ! $_event->exdate) {
            $this->getAlarms($_event);
        }
        
        $CUAttendee = Calendar_Model_Attender::getAttendee($_event->attendee, $this->_calendarUser);
        $isOrganizer = $_event->isOrganizer($this->_calendarUser);
        
        // apply perspective
        if ($CUAttendee && !$isOrganizer) {
            $_event->transp = $CUAttendee->transp ? $CUAttendee->transp : $_event->transp;
        }
        
        if ($_event->alarms instanceof Tinebase_Record_RecordSet) {
            foreach($_event->alarms as $alarm) {
                if (! Calendar_Model_Attender::isAlarmForAttendee($this->_calendarUser, $alarm, $_event)) {
                    $_event->alarms->removeRecord($alarm);
                }
            }
        }
        
        return $_event;
    }
    
    /**
     * converts an iTIP event to a tine20 event
     * 
     * @param Calendar_Model_Event $_event
     * @param Calendar_Model_Event $_currentEvent (not iTIP!)
     */
    protected function _fromiTIP($_event, $_currentEvent)
    {
        if (! $_event->rrule) {
            $_event->exdate = NULL;
        }
        
        if ($_event->exdate instanceof Tinebase_Record_RecordSet) {
            
            try{
                $currExdates = $this->_eventController->getRecurExceptions($_event, TRUE);
                $this->getAlarms($currExdates);
                $currClientExdates = $this->_eventController->getRecurExceptions($_event, TRUE, $this->getEventFilter());
                $this->getAlarms($currClientExdates);
            } catch (Tinebase_Exception_NotFound $e) {
                $currExdates = NULL;
                $currClientExdates = NULL; 
            }
            
            foreach ($_event->exdate as $idx => $exdate) {
                try {
                    $this->_prepareException($_event, $exdate);
                } catch (Exception $e){}

                $currExdate = $currExdates instanceof Tinebase_Record_RecordSet ? $currExdates->filter('recurid', $exdate->recurid)->getFirstRecord() : NULL;
                
                
                if ($exdate->is_deleted) {
                    // reset implicit filter fallouts and mark as don't touch (seq = -1)
                    $currClientExdate = $currClientExdates instanceof Tinebase_Record_RecordSet ? $currClientExdates->filter('recurid', $exdate->recurid)->getFirstRecord() : NULL;
                    if ($currClientExdate && $currClientExdate->is_deleted) {
                        $_event->exdate[$idx] = $currExdate;
                        $currExdate->seq = -1;
                        continue;
                    }
                }
                $this->_fromiTIP($exdate, $currExdate ? $currExdate : clone $_currentEvent);
            }
        }
        
        $CUAttendee = Calendar_Model_Attender::getAttendee($_event->attendee, $this->_calendarUser);
        $currentCUAttendee  = Calendar_Model_Attender::getAttendee($_currentEvent->attendee, $this->_calendarUser);
        $isOrganizer = $_event->isOrganizer($this->_calendarUser);
        
        // remove perspective 
        if ($CUAttendee && !$isOrganizer) {
            $CUAttendee->transp = $_event->transp;
            $_event->transp = $_currentEvent->transp ? $_currentEvent->transp : $_event->transp;
        }
        
        // apply changes to original alarms
        $_currentEvent->alarms  = $_currentEvent->alarms instanceof Tinebase_Record_RecordSet ? $_currentEvent->alarms : new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        $_event->alarms  = $_event->alarms instanceof Tinebase_Record_RecordSet ? $_event->alarms : new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        
        foreach($_currentEvent->alarms as $currentAlarm) {
            if (Calendar_Model_Attender::isAlarmForAttendee($this->_calendarUser, $currentAlarm)) {
                $alarmUpdate = Calendar_Controller_Alarm::getMatchingAlarm($_event->alarms, $currentAlarm);
                
                if ($alarmUpdate) {
                    // we could map the alarm => no change required
                    $_event->alarms->removeRecord($alarmUpdate);
                } else {
                    // alarm is to be skiped/deleted
                    if (! $currentAlarm->getOption('attendee')) {
                        Calendar_Controller_Alarm::skipAlarm($currentAlarm, $this->_calendarUser);
                    } else {
                        $_currentEvent->alarms->removeRecord($currentAlarm);
                    }
                }
            }
        }
        if (! $isOrganizer) {
            $_event->alarms->setOption('attendee', Calendar_Controller_Alarm::attendeeToOption($this->_calendarUser));
        }
        $_event->alarms->merge($_currentEvent->alarms);
        
        // in MS world only cal_user can do status updates
        if ($CUAttendee) {
            $CUAttendee->status_authkey = $currentCUAttendee ? $currentCUAttendee->status_authkey : NULL;
        }
    }
    
    /**
     * computes an returns the migration for event exceptions
     * 
     * @param Tinebase_Record_RecordSet $_currentPersistentExceptions
     * @param Tinebase_Record_RecordSet $_newPersistentExceptions
     */
    protected function _getExceptionsMigration($_currentPersistentExceptions, $_newPersistentExceptions)
    {
        $migration = array();
        
        // add indices and sort to speedup things
        $_currentPersistentExceptions->addIndices(array('dtstart'))->sort('dtstart');
        $_newPersistentExceptions->addIndices(array('dtstart'))->sort('dtstart');
        
        // get dtstarts
        $currDtStart = $_currentPersistentExceptions->getOriginalDtStart();
        $newDtStart = $_newPersistentExceptions->getOriginalDtStart();
        
        // compute migration in terms of dtstart
        $toDeleteDtStart = array_diff($currDtStart, $newDtStart);
        $toCreateDtStart = array_diff($newDtStart, $currDtStart);
        $toUpdateDtSTart = array_intersect($currDtStart, $newDtStart);
        
        $migration['toDelete'] = $this->_filterEventsByDTStarts($_currentPersistentExceptions, $toDeleteDtStart);
        $migration['toCreate'] = $this->_filterEventsByDTStarts($_newPersistentExceptions, $toCreateDtStart);
        $migration['toUpdate'] = $this->_filterEventsByDTStarts($_newPersistentExceptions, $toUpdateDtSTart);
        
        // get ids for toUpdate
        $idxIdMap = $this->_filterEventsByDTStarts($_currentPersistentExceptions, $toUpdateDtSTart)->getId();
        $migration['toUpdate']->setByIndices('id', $idxIdMap);
        
        // filter exceptions marked as don't touch 
        foreach($migration['toUpdate'] as $toUpdate) {
            if ($toUpdate->seq === -1) {
                $migration['toUpdate']->removeRecord($toUpdate);
            }
        }
        
        return $migration;
    }
    
    /**
     * prepares an exception instance for persitence
     * 
     * @param  Calendar_Model_Event $_baseEvent
     * @param  Calendar_Model_Event $_exception
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _prepareException($_baseEvent, $_exception)
    {
        if (! $_baseEvent->uid) {
            throw new Tinebase_Exception_InvalidArgument('base event has no uid');
        }
        
        if ($_exception->is_deleted == false) {
            $_exception->container_id = $_baseEvent->container_id;
        }
        $_exception->uid = $_baseEvent->uid;
        $_exception->recurid = $_baseEvent->uid . '-' . $_exception->getOriginalDtStart()->format(Tinebase_Record_Abstract::ISO8601LONG);
        
        // NOTE: we always refetch the base event as it might be touched in the meantime
        $currBaseEvent = $this->_eventController->getRecurBaseEvent($_exception);
        $_exception->last_modified_time = $currBaseEvent->last_modified_time;
    }
}
