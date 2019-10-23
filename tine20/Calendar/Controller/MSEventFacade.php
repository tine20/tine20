<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2016 Metaways Infosystems GmbH (http://www.metaways.de)
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
 * Calendar_Controller_MSEventFacade::setCalendarUser(Calendar_Model_Attender $_calUser)
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
    
    protected static $_attendeeEmailCache = array();

    protected $_currentEventFacadeContainer;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_eventController = Calendar_Controller_Event::getInstance();
        
        // set default CU
        $this->setCalendarUser(new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => self::getCurrentUserContactId()
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

    public static function unsetInstance()
    {
        self::$_instance = null;
    }
    
    /**
     * get user contact id
     * - NOTE: creates a new user contact on the fly if it did not exist before
     * 
     * @return string
     */
    public static function getCurrentUserContactId()
    {
        $currentUser = Tinebase_Core::getUser();

        if (empty($currentUser->contact_id)) {
            if ($currentUser instanceof Tinebase_Model_FullUser) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Creating user contact for ' . $currentUser->accountDisplayName . ' on the fly ...');
                $contact = Admin_Controller_User::getInstance()->createOrUpdateContact($currentUser);
                $currentUser->contact_id = $contact->getId();
                Tinebase_User::getInstance()->updateUserInSqlBackend($currentUser);
            }
        }

        return $currentUser->contact_id;
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
        $this->_resolveData($event);
        
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
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => $_ids)
        ));
        return $this->search($filter);
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
        $filter = new Calendar_Model_EventFilter();
        $pagination = new Tinebase_Model_Pagination(array(
            'sort' => $_orderBy,
            'dir'  => $_orderDirection
        ));
        return $this->search($filter, $pagination);
    }
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup             $_filter
     * @param Tinebase_Model_Pagination                     $_pagination
     * @param bool                                          $_getRelations
     * @param boolean                                       $_onlyIds
     * @param string                                        $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $events = $this->_getEvents($_filter, $_action);

        if ($_pagination instanceof Tinebase_Model_Pagination && ($_pagination->start || $_pagination->limit) ) {
            $eventIds = $events->id;
            $numEvents = count($eventIds);
            
            $offset = min($_pagination->start, $numEvents);
            $length = min($_pagination->limit, $offset+$numEvents);
            
            $eventIds = array_slice($eventIds, $offset, $length);
            $eventSlice = new Tinebase_Record_RecordSet('Calendar_Model_Event');
            foreach($eventIds as $eventId) {
                $eventSlice->addRecord($events->getById($eventId));
            }
            $events = $eventSlice;
        }
        
        if (! $_onlyIds) {
            // NOTE: it would be correct to wrap this with the search filter, BUT
            //       this breaks webdasv as it fetches its events with a search id OR uid.
            //       ActiveSync sets its syncfilter generically so it's not problem either
//             $oldFilter = $this->setEventFilter($_filter);
            $events = $this->_toiTIP($events);
//             $this->setEventFilter($oldFilter);
        }
        
        return $_onlyIds ? $events->id : $events;
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
        $eventIds = $this->_getEvents($_filter, $_action);
        
        return count ($eventIds);
    }
    
    /**
     * fetches all events and sorts exceptions into exdate prop for given filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string                            $action
     */
    protected function _getEvents($_filter, $_action)
    {
        if (! $_filter instanceof Calendar_Model_EventFilter) {
            $_filter = new Calendar_Model_EventFilter();
        }

        $events = $this->_eventController->search($_filter, NULL, FALSE, FALSE, $_action);

        // if an id filter is set, we need to fetch exceptions in a second query
        if ($_filter->getFilter('id', true, true)) {
            $events->merge($this->_eventController->search(new Calendar_Model_EventFilter(array(
                array('field' => 'base_event_id', 'operator' => 'in',      'value' => $events->id),
                array('field' => 'id',            'operator' => 'notin',   'value' => $events->id),
                array('field' => 'recurid',       'operator' => 'notnull', 'value' => null),
            )), NULL, FALSE, FALSE, $_action));
        }

        $this->_eventController->getAlarms($events);
        Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($events);

        $baseEventMap = array(); // id => baseEvent
        $exceptionSets = array(); // id => exceptions
        $exceptionMap = array(); // idx => event

        foreach($events as $event) {
            if ($event->rrule) {
                $eventId = $event->id;
                $baseEventMap[$eventId] = $event;
                $exceptionSets[$eventId] = new Tinebase_Record_RecordSet('Calendar_Model_Event');
            } else if ($event->recurid) {
                $exceptionMap[] = $event;
            }
        }

        foreach($exceptionMap as $exception) {
            $baseEventId = $exception->base_event_id;
            $baseEvent = array_key_exists($baseEventId, $baseEventMap) ? $baseEventMap[$baseEventId] : false;
            if ($baseEvent) {
                $exceptionSet = $exceptionSets[$baseEventId];
                $exceptionSet->addRecord($exception);
                $events->removeRecord($exception);
            }
        }

        foreach($baseEventMap as $id => $baseEvent) {
            $exceptionSet = $exceptionSets[$id];
            $this->_eventController->fakeDeletedExceptions($baseEvent, $exceptionSet);
            $baseEvent->exdate = $exceptionSet;
        }

        return $events;
    }

    /*************** add / update / delete *****************/    

    /**
     * add one record
     *
     * @param   Calendar_Model_Event $_event
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
        
        $_event->assertAttendee($this->getCalendarUser());
        $savedEvent = $this->_eventController->create($_event);
        
        if ($exceptions instanceof Tinebase_Record_RecordSet) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' About to create ' . count($exceptions) . ' exdates for event ' . $_event->summary . ' (' . $_event->dtstart . ')');
            
            foreach ($exceptions as $exception) {
                $exception->assertAttendee($this->getCalendarUser());
                $this->_prepareException($savedEvent, $exception);
                $this->_preserveMetaData($savedEvent, $exception, true);
                $this->_eventController->createRecurException($exception, !!$exception->is_deleted);
            }
        }

        // NOTE: exdate creation changes baseEvent, so we need to refetch it here
        return $this->get($savedEvent->getId());
    }
    
    /**
     * update one record
     * 
     * NOTE: clients might send their original (creation) data w.o. our adoptions for update
     *       therefore we need reapply them
     *       
     * @param   Tinebase_Record_Interface $_event
     * @param   bool                 $_checkBusyConflicts
     * @return  Calendar_Model_Event
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_event, $_checkBusyConflicts = FALSE)
    {
        if (! $_event->dtstart || ! $_event->dtend) {
            throw new Tinebase_Exception_Record_Validation('dtstart or dtend missing from event!');
        }

        $currentOriginEvent = $this->_eventController->get($_event->getId());
        $this->_fromiTIP($_event, $currentOriginEvent);

        // NOTE:  create an update must be handled equally as apple devices do not fetch events after creation.
        //        an update from the creating device would change defaults otherwise
        // NOTE2: Being organizer without attending is not possible when sync is in use as every update
        //        from a sync device of the organizer adds the organizer as attendee :-(
        //        -> in the sync world this is scenario is called delegation and handled differently
        //        -> it might be consequent to have the same behavior (organizer is always attendee with role chair)
        //           in tine20 in general. This is how Thunderbird handles it as well
        $_event->assertAttendee($this->getCalendarUser());
        
        $exceptions = $_event->exdate instanceof Tinebase_Record_RecordSet ? $_event->exdate : new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $exceptions->addIndices(array('is_deleted'));
        
        $currentPersistentExceptions = $_event->rrule ? $this->_eventController->getRecurExceptions($_event, FALSE) : new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $newPersistentExceptions = $exceptions->filter('is_deleted', 0);

        $dtStartDiff = $_event->dtstart->getClone()->setTimezone($_event->originator_tz)
            ->diff($currentOriginEvent->dtstart->getClone()->setTimezone($currentOriginEvent->originator_tz));
        $migration = $this->_getExceptionsMigration($currentPersistentExceptions, $newPersistentExceptions, $dtStartDiff);

        $this->_eventController->delete($migration['toDelete']->getId());

        // NOTE: we need to exclude the toCreate exdates here to not confuse computations in createRecurException!
        $_event->exdate = array_diff($exceptions->getOriginalDtStart(), $migration['toCreate']->getOriginalDtStart($dtStartDiff));

        $skipRecurAdoptions = $this->_eventController->skipRecurAdoptions(true);
        try {
            $updatedBaseEvent = $this->_eventController->update($_event, $_checkBusyConflicts);
        } finally {
            $this->_eventController->skipRecurAdoptions($skipRecurAdoptions);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Found ' . count($migration['toCreate']) . ' exceptions to create and ' . count($migration['toUpdate']) . ' to update.');
        
        foreach ($migration['toCreate'] as $exception) {
            $exception->assertAttendee($this->getCalendarUser());
            $this->_prepareException($updatedBaseEvent, $exception);
            $this->_preserveMetaData($updatedBaseEvent, $exception, true);
            $this->_eventController->createRecurException($exception, !!$exception->is_deleted);
        }

        $updatedExceptions = array();
        foreach ($migration['toUpdate'] as $exception) {

            if (in_array($exception->getId(),$updatedExceptions )) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                    . ' Exdate ' . $exception->getId() . ' already updated');
                continue;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                . ' Update exdate ' . $exception->getId() . ' at ' . $exception->dtstart->toString());
            
            $exception->assertAttendee($this->getCalendarUser());
            $this->_prepareException($updatedBaseEvent, $exception);
            $this->_preserveMetaData($updatedBaseEvent, $exception, false);
            $this->_addStatusAuthkeyForOwnAttender($exception);
            
            // skip concurrency check here by setting the seq of the current record
            $currentException = $currentPersistentExceptions->getById($exception->getId());
            $exception->seq = $currentException->seq;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' Updating exception: ' . print_r($exception->toArray(), TRUE));
            $this->_eventController->update($exception, $_checkBusyConflicts);
            $updatedExceptions[] = $exception->getId();
        }
        
        // NOTE: we need to refetch here, otherwise eTag fail's as exception updates change baseEvents seq
        return $this->get($updatedBaseEvent->getId());
    }
    
    /**
     * add status_authkey for own attender
     * 
     * @param Calendar_Model_Event $event
     */
    protected function _addStatusAuthkeyForOwnAttender($event)
    {
        if (! $event->attendee instanceof Tinebase_Record_RecordSet) {
            return;
        }
        $ownAttender = Calendar_Model_Attender::getOwnAttender($event->attendee);
        if ($ownAttender) {
            $currentEvent = $this->_eventController->get($event->id);
            $currentAttender = Calendar_Model_Attender::getAttendee($currentEvent->attendee, $ownAttender);
            if ($currentAttender) {
                $ownAttender->status_authkey = $currentAttender->status_authkey;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' currentAttender not found in currentEvent: ' . print_r($currentEvent->toArray(), true));
            }
        }
    }
    
    /**
     * asserts correct event filter and calendar user in MSEventFacade
     * 
     * NOTE: this is nessesary as MSEventFacade is a singleton and in some operations (e.g. move) there are 
     *       multiple instances of self
     */
    public function assertEventFacadeParams(Tinebase_Model_Container $container, $setEventFilter=true)
    {
        if (!$this->_currentEventFacadeContainer ||
             $this->_currentEventFacadeContainer->getId() !== $container->getId()
        ) {
            $this->_currentEventFacadeContainer = $container;

            try {
                $calendarUserId = $container->type == Tinebase_Model_Container::TYPE_PERSONAL ?
                Addressbook_Controller_Contact::getInstance()->getContactByUserId($container->getOwner(), true)->getId() :
                Tinebase_Core::getUser()->contact_id;
            } catch (Exception $e) {
                $calendarUserId = Calendar_Controller_MSEventFacade::getCurrentUserContactId();
            }
            
            $calendarUser = new Calendar_Model_Attender(array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $calendarUserId,
            ));
            

            $this->setCalendarUser($calendarUser);

            if ($setEventFilter) {
                $eventFilter = new Calendar_Model_EventFilter(array(
                    array('field' => 'container_id', 'operator' => 'equals', 'value' => $container->getId())
                ));
                $this->setEventFilter($eventFilter);
            }
        }
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
        $attendeeFound->xprops = $_attendee->xprops;
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($_event, $attendeeFound, $attendeeFound->status_authkey);
        
        // update exceptions
        foreach($exceptions as $exception) {
            // do not attempt to set status of an deleted instance
            if ($exception->is_deleted) continue;
            
            $exceptionAttendee = Calendar_Model_Attender::getAttendee($exception->attendee, $_attendee);
            
            if (! $exception->getId()) {
                if (! $exceptionAttendee) {
                    // set user status to DECLINED
                    $exceptionAttendee = clone $attendeeFound;
                    $exceptionAttendee->status = Calendar_Model_Attender::STATUS_DECLINED;
                }
                $exceptionAttendee->displaycontainer_id = $_attendee->displaycontainer_id;
                $exceptionAttendee->xprops = $_attendee->xprops;
                Calendar_Controller_Event::getInstance()->attenderStatusCreateRecurException($exception, $exceptionAttendee, $exceptionAttendee->status_authkey);
            } else {
                /*if (! $exceptionAttendee) {
                    // we would need to find out the users authkey to decline him -> not allowed!?
                    if (!isset($attendeeFound)) {
                        throw new Tinebase_Exception_UnexpectedValue('not an attendee');
                    }
                }*/
                if (! $exceptionAttendee) {
                    // set user status to DECLINED
                    $exceptionAttendee = clone $attendeeFound;
                    $exceptionAttendee->status = Calendar_Model_Attender::STATUS_DECLINED;
                }
                $exceptionAttendee->displaycontainer_id = $_attendee->displaycontainer_id;
                $exceptionAttendee->xprops = $_attendee->xprops;
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
        
        foreach ($events as $event) {
            if ($event->exdate !== null) {
                foreach ($event->exdate as $exception) {
                    $exceptionId = $exception->getId();
                    if ($exceptionId) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Found exdate to be deleted (id: ' . $exceptionId . ')');
                        array_unshift($ids, $exceptionId);
                    }
                }
            }
        }
        
        $this->_eventController->delete($ids);
        return $events;
    }
    
    /**
     * Delete Attendees from event
     *
     * @param Calendar_Model_Event $_event
     * @param Calendar_Model_Event $_iMIP
     *
     */
    public function deleteAttendees($_event, $_iMIP) {

        foreach ($_iMIP->attendee as $attendeeToDelete) {
            $attenderToRemove = Calendar_Model_Attender::getAttendee($_event->attendee, $attendeeToDelete);
            if ($attenderToRemove) {
                $_event->attendee->removeRecord($attenderToRemove);
            }
        }
        $_event->last_modified_time = $_iMIP->last_modified_time;
        $this->update($_event);
        return $_event;
    }

    /**
     * get and resolve all alarms of given record(s)
     * 
     * @param  Tinebase_Record_Interface|Tinebase_Record_RecordSet $_record
     */
    public function getAlarms($_record)
    {
        $events = $_record instanceof Tinebase_Record_RecordSet ? $_record->getClone(true) : new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_record));
        
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
        if (! in_array($_calUser->user_type, array(Calendar_Model_Attender::USERTYPE_USER, Calendar_Model_Attender::USERTYPE_GROUPMEMBER))) {
            throw new Tinebase_Exception_UnexpectedValue('Calendar user must be a contact');
        }
        $oldUser = $this->_calendarUser;
        $this->_calendarUser = $_calUser;
        $this->_eventController->setCalendarUser($_calUser);
        
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
    protected function _filterEventsByDTStarts($_events, $_dtstarts, $dtStartDiff=null)
    {
        $filteredSet = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $allDTStarts = $_events->getOriginalDtStart($dtStartDiff);
        
        $existingIdxs = array_intersect($allDTStarts, $_dtstarts);
        
        foreach($existingIdxs as $idx => $dtstart) {
            $filteredSet->addRecord($_events[$idx]);
        }
        
        return $filteredSet;
    }

    protected function _resolveData($events)
    {
        $eventSet = $events instanceof Tinebase_Record_RecordSet
            ? $events->getClone(true)
            : new Tinebase_Record_RecordSet('Calendar_Model_Event', array($events));

        // get recur exceptions
        foreach ($eventSet as $event) {
            if ($event->rrule && !$event->exdate instanceof Tinebase_Record_RecordSet) {
                $exdates = $this->_eventController->getRecurExceptions($event, TRUE, $this->getEventFilter());
                $event->exdate = $exdates;
                $eventSet->merge($exdates);
            }
        }

        $this->_eventController->getAlarms($eventSet);
        Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($eventSet);
    }

    /**
     * converts a tine20 event to an iTIP event
     * 
     * @param  Calendar_Model_Event $_event - must have exceptions, alarms & attachements resovled
     * @return Calendar_Model_Event 
     */
    protected function _toiTIP($_event)
    {
        $events = $_event instanceof Tinebase_Record_RecordSet
            ? $_event
            : new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_event));

        foreach ($events as $idx => $event) {
            // get exdates
            if ($event->getId() && $event->rrule) {
                $this->_toiTIP($event->exdate);
            }

            $this->_filterAttendeeWithoutEmail($event);
            
            $CUAttendee = Calendar_Model_Attender::getAttendee($event->attendee, $this->_calendarUser);
            $isOrganizer = $event->isOrganizer($this->_calendarUser);
            
            // apply perspective
            if ($CUAttendee && !$isOrganizer) {
                $event->transp = $CUAttendee->transp ? $CUAttendee->transp : $event->transp;
            }
            
            if ($event->alarms instanceof Tinebase_Record_RecordSet) {
                foreach($event->alarms as $alarm) {
                    if (! Calendar_Model_Attender::isAlarmForAttendee($this->_calendarUser, $alarm, $event)) {
                        $event->alarms->removeRecord($alarm);
                    }
                }
            }
        }
        
        return $_event;
    }
    
    /**
     * filter out attendee w.o. email
     * 
     * @param Calendar_Model_Event $event
     */
    protected function _filterAttendeeWithoutEmail($event)
    {
        if (! $event->attendee instanceof Tinebase_Record_RecordSet) {
            return;
        }
        
        foreach ($event->attendee as $attender) {
            $cacheId = $attender->user_type . $attender->user_id;
            
            // value is in array and true
            if (isset(self::$_attendeeEmailCache[$cacheId])) {
                continue;
            }
            
            // add value to cache if not existing already
            if (!array_key_exists($cacheId, self::$_attendeeEmailCache)) {
                $this->_fillResolvedAttendeeCache($event);

                try {
                    self::$_attendeeEmailCache[$cacheId] = !!$attender->getEmail($event);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    // skipping
                    self::$_attendeeEmailCache[$cacheId] = false;
                }
                
                // limit class cache to 100 entries
                if (count(self::$_attendeeEmailCache) > 100) {
                    array_shift(self::$_attendeeEmailCache);
                }
            }
            
            if (!self::$_attendeeEmailCache[$cacheId]) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' remove entry because attender has no email address: ' . $attender->user_id);
                $event->attendee->removeRecord($attender);
            }
        }
    }

    /**
     * re add attendee w.o. email
     * 
     * @param Calendar_Model_Event $event
     */
    protected function _addAttendeeWithoutEmail($event, $currentEvent)
    {
        if (! $currentEvent->attendee instanceof Tinebase_Record_RecordSet) {
            return;
        }
        $this->_fillResolvedAttendeeCache($currentEvent);
        
        if (! $event->attendee instanceof Tinebase_Record_RecordSet) {
            $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        }
        foreach ($currentEvent->attendee->getEmail() as $idx => $email) {
            if (! $email) {
                $event->attendee->addRecord($currentEvent->attendee[$idx]);
            }
        }
    }
    
    /**
     * this fills the resolved attendee cache without changing the event attendee recordset
     * 
     * @param Calendar_Model_Event $event
     */
    protected function _fillResolvedAttendeeCache($event)
    {
        if (! $event->attendee instanceof Tinebase_Record_RecordSet) {
            return;
        }
        
        Calendar_Model_Attender::fillResolvedAttendeesCache($event->attendee);
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

                $currExdate = $currExdates instanceof Tinebase_Record_RecordSet ?
                    $currExdates->find('recurid', $exdate->recurid) : null;

                $this->_preserveMetaData($_event, $exdate, null === $currExdate);
                
                if ($exdate->is_deleted) {
                    // reset implicit filter fallouts and mark as don't touch (seq = -1)
                    $currClientExdate = $currClientExdates instanceof Tinebase_Record_RecordSet ? $currClientExdates->find('recurid', $exdate->recurid) : NULL;
                    if ($currClientExdate && $currClientExdate->is_deleted) {
                        $_event->exdate[$idx] = $currExdate;
                        $currExdate->seq = -1;
                        continue;
                    }
                }
                $this->_fromiTIP($exdate, $currExdate ? $currExdate : clone $_currentEvent);
            }
        }
        
        // assert organizer
        $_event->organizer = $_event->organizer ?: ($_currentEvent->organizer ?: $this->_calendarUser->user_id);

        $this->_addAttendeeWithoutEmail($_event, $_currentEvent);
        
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
                    // we could map the alarm => save ack & snooze options
                    if ($dtAck = Calendar_Controller_Alarm::getAcknowledgeTime($alarmUpdate)) {
                        Calendar_Controller_Alarm::setAcknowledgeTime($currentAlarm, $dtAck, $this->getCalendarUser()->user_id);
                    }
                    if ($dtSnooze = Calendar_Controller_Alarm::getSnoozeTime($alarmUpdate)) {
                        Calendar_Controller_Alarm::setSnoozeTime($currentAlarm, $dtSnooze, $this->getCalendarUser()->user_id);
                    }
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

        // assert organizer for personal calendars to be calendar owner
        if ($this->_currentEventFacadeContainer && $this->_currentEventFacadeContainer->getId() == $_event->container_id
            && $this->_currentEventFacadeContainer->type == Tinebase_Model_Container::TYPE_PERSONAL
            && !$_event->hasExternalOrganizer() ) {

            $_event->organizer = $this->_calendarUser->user_id;
        }
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
     * @param DateInterval              $dtStartDiff
     * @return array
     */
    protected function _getExceptionsMigration($_currentPersistentExceptions, $_newPersistentExceptions, $dtStartDiff)
    {
        $migration = array();
        
        // add indices and sort to speedup things
        $_currentPersistentExceptions->addIndices(array('dtstart'))->sort('dtstart');
        $_newPersistentExceptions->addIndices(array('dtstart'))->sort('dtstart');
        
        // get dtstarts
        $currDtStart = $_currentPersistentExceptions->getOriginalDtStart();
        $newDtStart = $_newPersistentExceptions->getOriginalDtStart($dtStartDiff);

        // compute migration in terms of dtstart
        $toDeleteDtStart = array_diff($currDtStart, $newDtStart);
        $toCreateDtStart = array_diff($newDtStart, $currDtStart);
        $toUpdateDtSTart = array_intersect($currDtStart, $newDtStart);
        
        $migration['toDelete'] = $this->_filterEventsByDTStarts($_currentPersistentExceptions, $toDeleteDtStart);
        $migration['toCreate'] = $this->_filterEventsByDTStarts($_newPersistentExceptions, $toCreateDtStart, $dtStartDiff);
        $migration['toUpdate'] = $this->_filterEventsByDTStarts($_newPersistentExceptions, $toUpdateDtSTart, $dtStartDiff);
        
        // get ids for toUpdate
        $idxIdMap = $this->_filterEventsByDTStarts($_currentPersistentExceptions, $toUpdateDtSTart)->getId();
        $migration['toUpdate']->setByIndices('id', $idxIdMap, /* $skipMissing = */ true);
        
        // filter exceptions marked as don't touch 
        foreach ($migration['toUpdate'] as $toUpdate) {
            if ($toUpdate->seq === -1) {
                $migration['toUpdate']->removeRecord($toUpdate);
            }
        }

        return $migration;
    }

    /**
     * copies customfields, tags, relations, notes from base event to exception
     *
     * @param Calendar_Model_Event $_baseEvent
     * @param Calendar_Model_Event $_exception
     * @param boolean $_create
     */
    protected function _preserveMetaData(Calendar_Model_Event $_baseEvent, Calendar_Model_Event $_exception, $_create)
    {
        if ($_create) {
            $refEvent = $_baseEvent;
        } else {
            $refEvent = $this->_eventController->get($_exception->getId());
        }

        // initialize customfields from base event as clients don't support that and otherwise would lose them
        if (isset($refEvent->customfields) && !empty($refEvent->customfields)) {
            foreach ($refEvent->customfields as $name => $val) {
                $_exception->xprops('customfields')[$name] = $val;
            }
        }

        // initialize tags from base event as clients don't support that and otherwise would lose them
        if (isset($refEvent->tags) && !empty($refEvent->tags)) {
            $_exception->tags = $refEvent->tags;
        }

        // initialize relations from base event as clients don't support that and otherwise would lose them
        if (isset($refEvent->relations) && !empty($refEvent->relations)) {
            $relations = [];
            foreach (is_array($refEvent->relations) ?: $refEvent->relations->toArray() as $relation) {
                $relations[] = [
                    'related_id' => $relation['related_id'],
                    'related_model' => $relation['related_model'],
                    'related_degree' => $relation['related_degree'],
                    'related_backend' => $relation['related_backend'],
                    'type' => isset($relation['type']) ? $relation['type'] : null,
                ];
            }
            $_exception->relations = $relations;
        }

        // notes?!?
        if (isset($refEvent->notes) && !empty($refEvent->notes)) {
            if ($refEvent->notes instanceof Tinebase_Record_RecordSet) {
                $notes = clone $refEvent->notes;
                $notes->id = null;
                $notes = $notes->toArray();
            } else {
                $notes = [];
                foreach ($refEvent->notes as $note) {
                    if (is_array($note) && isset($note['id'])) {
                        unset($note['id']);
                    }
                    $notes[] = $note;
                }
            }
            $_exception->notes = $notes;
        }
    }

    /**
     * prepares an exception instance for persistence
     * 
     * @param  Calendar_Model_Event $_baseEvent
     * @param  Calendar_Model_Event $_exception
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _prepareException(Calendar_Model_Event $_baseEvent, Calendar_Model_Event $_exception)
    {
        if (! $_baseEvent->uid) {
            throw new Tinebase_Exception_InvalidArgument('base event has no uid');
        }
        
        if ($_exception->is_deleted == false) {
            $_exception->container_id = $_baseEvent->container_id;
        }
        $_exception->uid = $_baseEvent->uid;
        $_exception->base_event_id = $_baseEvent->getId();
        $_exception->recurid = $_baseEvent->uid . '-' . $_exception->getOriginalDtStart()->format(Tinebase_Record_Abstract::ISO8601LONG);
        
        // NOTE: we always refetch the base event as it might be touched in the meantime
        $currBaseEvent = $this->_eventController->get($_baseEvent, null, false);
        $_exception->last_modified_time = $currBaseEvent->last_modified_time;
    }

    public function getExistingEventByUID($_uid, $_assumeExternalOrganizer, $_action, $_grant, $_getDeleted = false)
    {
        $filters = new Calendar_Model_EventFilter(array(
            array('field' => 'uid',          'operator' => 'equals', 'value' => $_uid),
        ));
        if ($_getDeleted) {
            $filters->addFilter(new Tinebase_Model_Filter_Bool('is_deleted', 'equals',
                Tinebase_Model_Filter_Bool::VALUE_NOTSET));
        }

        $event = null;
        if ($_assumeExternalOrganizer) {
            $event = $this->_getExistingEventByUIDExternal($filters);
        }
        if (null === $event) {
            $event = $this->_getExistingEventByUIDInternal($filters, $_action, $_grant);
        }
        if (null === $event && !$_assumeExternalOrganizer) {
            $event = $this->_getExistingEventByUIDExternal($filters);
        }

        return $event;
    }

    /**
     * @param $_filter
     * @param $_grant
     * @return null|Tinebase_Record_Abstract
     */
    protected function _getExistingEventByUIDInternal($_filter, $_action, $_grant)
    {
        $events = Calendar_Controller_MSEventFacade::getInstance()->search($_filter, null, false, false, $_action);
        if (null !== $_grant) {
            $events = $events->filter($_grant, true);
        }
        return $events->getFirstRecord();
    }

    /**
     * @param $_filter
     * @param $_grant
     * @return null|Tinebase_Record_Abstract
     */
    protected function _getExistingEventByUIDExternal($_filter)
    {
        $calCtrl = Calendar_Controller_Event::getInstance();
        $oldCalenderAcl = $calCtrl->doContainerACLChecks(false);
        try {
            /** @var Calendar_Model_Event $event */
            $event = Calendar_Controller_MSEventFacade::getInstance()->search($_filter)->getFirstRecord();
        } finally {
            $calCtrl->doContainerACLChecks($oldCalenderAcl);
        }

        if (null !== $event && !$event->hasExternalOrganizer()) {
            return null;
        }

        return $event;
    }

    /**
     * checks if a records with identifiers $_ids exists, returns array of identifiers found
     *
     * @param array $_ids
     * @param bool $_getDeleted
     * @return array
     */
    public function has(array $_ids, $_getDeleted = false)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }
}
