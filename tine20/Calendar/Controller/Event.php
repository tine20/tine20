<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar Event Controller
 * 
 * In the calendar application, the container grants concept is slightly extended:
 *  1. GRANTS for events are not only based on the events "calendar" (technically 
 *     a container) but additionally a USER gets implicit grants for an event if 
 *     he is ATTENDER (+READ GRANT) or ORGANIZER (+READ,EDIT GRANT).
 *  2. ATTENDER which are invited to a certain "event" can assign the "event" to
 *     one of their personal calenders as "display calendar" (technically personal 
 *     containers they are admin of). The "display calendar" of an ATTENDER is
 *     stored in the attendee table.  Each USER has a default calendar, as 
 *     PREFERENCE,  all invitations are assigned to.
 *  3. The "effective GRANT" a USER has on an event (read/update/delete/...) is the 
 *     maximum GRANT of the following sources: 
 *      - container: GRANT the USER has to the calender of the event
 *      - implicit:  Additional READ GRANT for an attender and READ,EDIT
 *                   GRANT for the organizer.
*       - inherited: FREEBUSY, READ, PRIVATE, SYNC, EXPORT can be inherited
*                    from the GRANTS USER has to the a display calendar
 * 
 * When Applying/Asuring grants, we have to deal with two differnt situations:
 *  A: Check: Check individual grants on a event (record) basis.
 *            This is required for create/update/delete actions and done by 
 *            this controllers _checkGrant method.
 *  B: Seach: From the grants perspective this is a multi step process
 *            1. fetch all records with appropriate grants from backend
 *            2. cleanup records user has only free/busy grant for
 * 
 *  NOTE: To empower the client for enabling/disabling of actions based on the 
 *        grants a user has to an event, we need to compute the "effective GRANT"
 *        for read/search operations.
 *                  
 * Case A is not critical, as the amount of data is low. 
 * Case B however is the hard one, as lots of events and calendars may be
 * involved.
 * 
 * NOTE: the backend always fetches full records for grant calculations.
 *       searching ids only does not hlep with performance
 * 
 * @package Calendar
 */
class Calendar_Controller_Event extends Tinebase_Controller_Record_Abstract implements Tinebase_Controller_Alarm_Interface
{
    /**
     * @var boolean
     * 
     * just set is_delete=1 if record is going to be deleted
     */
    protected $_purgeRecords = FALSE;
    
    /**
     * send notifications?
     *
     * @var boolean
     */
    protected $_sendNotifications = TRUE;
    
    /**
     * @see Tinebase_Controller_Record_Abstract
     * 
     * @var boolean
     */
    protected $_resolveCustomFields = TRUE;

    /**
     * @var Calendar_Model_Attender
     */
    protected $_calendarUser = NULL;

    /**
     * @var Calendar_Controller_Event
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'Calendar';
        $this->_modelName       = 'Calendar_Model_Event';
        $this->_backend         = new Calendar_Backend_Sql();

        // set default CU
        $this->setCalendarUser(new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => Calendar_Controller_MSEventFacade::getCurrentUserContactId()
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
     * @return Calendar_Controller_Event
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller_Event();
        }
        return self::$_instance;
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
     * checks if all attendee of given event are not busy for given event
     * 
     * @param Calendar_Model_Event $_event
     * @return void
     * @throws Calendar_Exception_AttendeeBusy
     */
    public function checkBusyConflicts($_event)
    {
        $ignoreUIDs = !empty($_event->uid) ? array($_event->uid) : array();
        
        if ($_event->transp == Calendar_Model_Event::TRANSP_TRANSP || count($_event->attendee) < 1) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Skipping free/busy check because event is transparent or has no attendee");
            return;
        }
        
        $eventSet = new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_event));
        
        if (! empty($_event->rrule)) {
            $checkUntil = clone $_event->dtstart;
            $checkUntil->add(1, Tinebase_DateTime::MODIFIER_MONTH);
            Calendar_Model_Rrule::mergeRecurrenceSet($eventSet, $_event->dtstart, $checkUntil);
        }
        
        $periods = array();
        foreach ($eventSet as $event) {
            $periods[] = array('from' => $event->dtstart, 'until' => $event->dtend);
        }
        
        $fbInfo = $this->getFreeBusyInfo($periods, $_event->attendee, $ignoreUIDs);
        
        if (count($fbInfo) > 0) {
            $busyException = new Calendar_Exception_AttendeeBusy();
            $busyException->setFreeBusyInfo($fbInfo);
            
            Calendar_Model_Attender::resolveAttendee($_event->attendee, /* resolve_display_containers = */ false);
            $busyException->setEvent($_event);
            
            throw $busyException;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Free/busy check: no conflict found");
    }
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   bool                      $_checkBusyConflicts
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_record, $_checkBusyConflicts = FALSE)
    {
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $this->_inspectEvent($_record);
            
            // we need to resolve groupmembers before free/busy checking
            Calendar_Model_Attender::resolveGroupMembers($_record->attendee);
            
            if ($_checkBusyConflicts) {
                // ensure that all attendee are free
                $this->checkBusyConflicts($_record);
            }
            
            $sendNotifications = $this->_sendNotifications;
            $this->_sendNotifications = FALSE;
            
            $createdEvent = parent::create($_record);
            
            $this->_sendNotifications = $sendNotifications;
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        // send notifications
        if ($this->_sendNotifications && $_record->mute != 1) {
            $this->doSendNotifications($createdEvent, Tinebase_Core::getUser(), 'created');
        }        

        return $createdEvent;
    }
    
    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        $this->_saveAttendee($_record, $_createdRecord);
    }
    
    /**
     * deletes a recur series
     *
     * @param  Calendar_Model_Event $_recurInstance
     * @return void
     */
    public function deleteRecurSeries($_recurInstance)
    {
        $baseEvent = $this->getRecurBaseEvent($_recurInstance);
        $this->delete($baseEvent->getId());
    }
    
    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC') 
    {
        throw new Tinebase_Exception_NotImplemented('not implemented');
    }
    
    /**
     * returns freebusy information for given period and given attendee
     * 
     * @todo merge overlapping events to one freebusy entry
     * 
     * @param  array of array with from and until                   $_periods
     * @param  Tinebase_Record_RecordSet of Calendar_Model_Attender $_attendee
     * @param  array of UIDs                                        $_ignoreUIDs
     * @return Tinebase_Record_RecordSet of Calendar_Model_FreeBusy
     */
    public function getFreeBusyInfo($_periods, $_attendee, $_ignoreUIDs = array())
    {
        $fbInfoSet = new Tinebase_Record_RecordSet('Calendar_Model_FreeBusy');
        
        // map groupmembers to users
        $attendee = clone $_attendee;
        $attendee->addIndices(array('user_type'));
        $groupmembers = $attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER);
        $groupmembers->user_type = Calendar_Model_Attender::USERTYPE_USER;
        
        // base filter data
        $filterData = array(
            array('field' => 'attender', 'operator' => 'in',     'value' => $_attendee),
            array('field' => 'transp',   'operator' => 'equals', 'value' => Calendar_Model_Event::TRANSP_OPAQUE)
        );
        
        // add all periods to filterdata
        $periodFilters = array();
        foreach ($_periods as $period) {
            $periodFilters[] = array(
                'field' => 'period', 
                'operator' => 'within', 
                'value' => array(
                    'from' => $period['from'], 
                    'until' => $period['until']
            ));
        }
        $filterData[] = array('condition' => 'OR', 'filters' => $periodFilters);
        
        // finaly create filter
        $filter = new Calendar_Model_EventFilter($filterData);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__
            . ' free/busy filter: ' . print_r($filter->toArray(), true));
        
        $events = $this->search($filter, new Tinebase_Model_Pagination(), FALSE, FALSE);
        
        foreach ($_periods as $period) {
            Calendar_Model_Rrule::mergeRecurrenceSet($events, $period['from'], $period['until']);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__
            . ' value: ' . print_r($events->toArray(), true));
        
        // create a typemap
        $typeMap = array();
        foreach ($attendee as $attender) {
            if (! (isset($typeMap[$attender['user_type']]) || array_key_exists($attender['user_type'], $typeMap))) {
                $typeMap[$attender['user_type']] = array();
            }
            
            $typeMap[$attender['user_type']][$attender['user_id']] = array();
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__
            . ' value: ' . print_r($typeMap, true));
        
        // generate freeBusyInfos
        foreach ($events as $event) {
            // skip events with ignoreUID
            if (in_array($event->uid, $_ignoreUIDs)) {
                continue;
            }
            
            // check if event is conflicting one of the given periods
            $conflicts = FALSE;
            foreach($_periods as $period) {
                if ($event->dtstart->isEarlier($period['until']) && $event->dtend->isLater($period['from'])) {
                    $conflicts = TRUE;
                    break;
                }
            }
            if (! $conflicts) {
                continue;
            }
            
            // map groupmembers to users
            $event->attendee->addIndices(array('user_type'));
            $groupmembers = $event->attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER);
            $groupmembers->user_type = Calendar_Model_Attender::USERTYPE_USER;
        
            foreach ($event->attendee as $attender) {
                // skip declined/transp events
                if ($attender->status == Calendar_Model_Attender::STATUS_DECLINED ||
                    $attender->transp == Calendar_Model_Event::TRANSP_TRANSP) {
                    continue;
                }
                
                if ((isset($typeMap[$attender->user_type]) || array_key_exists($attender->user_type, $typeMap)) && (isset($typeMap[$attender->user_type][$attender->user_id]) || array_key_exists($attender->user_id, $typeMap[$attender->user_type]))) {
                    $fbInfo = new Calendar_Model_FreeBusy(array(
                        'user_type' => $attender->user_type,
                        'user_id'   => $attender->user_id,
                        'dtstart'   => clone $event->dtstart,
                        'dtend'     => clone $event->dtend,
                        'type'      => Calendar_Model_FreeBusy::FREEBUSY_BUSY,
                    ), true);
                    
                    if ($event->{Tinebase_Model_Grants::GRANT_READ}) {
                        $fbInfo->event = clone $event;
                        unset($fbInfo->event->attendee);
                    }
                    
                    //$typeMap[$attender->user_type][$attender->user_id][] = $fbInfo;
                    $fbInfoSet->addRecord($fbInfo);
                }
            }
        }
        
        return $fbInfoSet;
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
        $events = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
        if (! $_onlyIds) {
            $this->_freeBusyCleanup($events, $_action);
        }
        
        return $events;
    }
    
    /**
     * Returns a set of records identified by their id's
     *
     * @param   array $_ids       array of record identifiers
     * @param   bool  $_ignoreACL don't check acl grants
     * @return  Tinebase_Record_RecordSet of $this->_modelName
     */
    public function getMultiple($_ids, $_ignoreACL = false)
    {
        $events = parent::getMultiple($_ids, $_ignoreACL = false);
        if ($_ignoreACL !== true) {
            $this->_freeBusyCleanup($events, 'get');
        }
        
        return $events;
    }
    
    /**
     * cleanup search results (freebusy)
     * 
     * @param Tinebase_Record_RecordSet $_events
     * @param string $_action
     */
    protected function _freeBusyCleanup(Tinebase_Record_RecordSet $_events, $_action)
    {
        foreach ($_events as $event) {
            $doFreeBusyCleanup = $event->doFreeBusyCleanup();
            if ($doFreeBusyCleanup && $_action !== 'get') {
                $_events->removeRecord($event);
            }
        }
    }
    
    /**
     * returns freeTime (suggestions) for given period of given attendee
     * 
     * @param  Tinebase_DateTime                                            $_from
     * @param  Tinebase_DateTime                                            $_until
     * @param  Tinebase_Record_RecordSet of Calendar_Model_Attender $_attendee
     * 
     * ...
     */
    public function searchFreeTime($_from, $_until, $_attendee/*, $_constains, $_mode*/)
    {
        $fbInfoSet = $this->getFreeBusyInfo(array(array('from' => $_from, 'until' => $_until)), $_attendee);
        
//        $fromTs = $_from->getTimestamp();
//        $untilTs = $_until->getTimestamp();
//        $granularity = 1800;
//        
//        // init registry of granularity
//        $eventRegistry = array_combine(range($fromTs, $untilTs, $granularity), array_fill(0, ceil(($untilTs - $fromTs)/$granularity)+1, ''));
//        
//        foreach ($fbInfoSet as $fbInfo) {
//            $startIdx = $fromTs + $granularity * floor(($fbInfo->dtstart->getTimestamp() - $fromTs) / $granularity);
//            $endIdx = $fromTs + $granularity * ceil(($fbInfo->dtend->getTimestamp() - $fromTs) / $granularity);
//            
//            for ($idx=$startIdx; $idx<=$endIdx; $idx+=$granularity) {
//                //$eventRegistry[$idx][] = $fbInfo;
//                $eventRegistry[$idx] .= '.';
//            }
//        }
        
        //print_r($eventRegistry);
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   bool                      $_checkBusyConflicts
     * @param   string                    $range
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_record, $_checkBusyConflicts = FALSE, $range = Calendar_Model_Event::RANGE_THIS)
    {
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $sendNotifications = $this->sendNotifications(FALSE);

            $event = $this->get($_record->getId());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    .' Going to update the following event. rawdata: ' . print_r($event->toArray(), true));

            //NOTE we check via get(full rights) here whereas _updateACLCheck later checks limited rights from search
            if ($this->_doContainerACLChecks === FALSE || $event->hasGrant(Tinebase_Model_Grants::GRANT_EDIT)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " updating event: {$_record->id} (range: {$range})");
                
                // we need to resolve groupmembers before free/busy checking
                Calendar_Model_Attender::resolveGroupMembers($_record->attendee);
                $this->_inspectEvent($_record);
               
                if ($_checkBusyConflicts) {
                    if ($event->isRescheduled($_record) ||
                           count(array_diff($_record->attendee->user_id, $event->attendee->user_id)) > 0
                       ) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . " Ensure that all attendee are free with free/busy check ... ");
                        $this->checkBusyConflicts($_record);
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . " Skipping free/busy check because event has not been rescheduled and no new attender has been added");
                    }
                }
                
                parent::update($_record);
                
            } else if ($_record->attendee instanceof Tinebase_Record_RecordSet) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " user has no editGrant for event: {$_record->id}, updating attendee status with valid authKey only");
                foreach ($_record->attendee as $attender) {
                    if ($attender->status_authkey) {
                        $this->attenderStatusUpdate($_record, $attender, $attender->status_authkey);
                    }
                }
            }

            if ($_record->isRecurException() && in_array($range, array(Calendar_Model_Event::RANGE_ALL, Calendar_Model_Event::RANGE_THISANDFUTURE))) {
                $this->_updateExdateRange($_record, $range, $event);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Rolling back because: ' . $e);
            Tinebase_TransactionManager::getInstance()->rollBack();
            $this->sendNotifications($sendNotifications);
            throw $e;
        }
        
        $updatedEvent = $this->get($event->getId());
        
        // send notifications
        $this->sendNotifications($sendNotifications);
        if ($this->_sendNotifications && $_record->mute != 1) {
            $this->doSendNotifications($updatedEvent, Tinebase_Core::getUser(), 'changed', $event);
        }
        return $updatedEvent;
    }
    
    /**
     * inspect update of one record (after update)
     *
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        $this->_saveAttendee($record, $currentRecord, $record->isRescheduled($currentRecord));
        // need to save new attendee set in $updatedRecord for modlog
        $updatedRecord->attendee = clone($record->attendee);
    }
    
    /**
     * update range of events starting with given recur exception
     * 
     * @param Calendar_Model_Event $exdate
     * @param string $range
     */
    protected function _updateExdateRange($exdate, $range, $oldExdate)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Updating events (range: ' . $range . ') belonging to recur exception event ' . $exdate->getId());
        
        $baseEvent = $this->getRecurBaseEvent($exdate);
        $diff = $oldExdate->diff($exdate);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Exdate diff: ' . print_r($diff->toArray(), TRUE));
        
        if ($range === Calendar_Model_Event::RANGE_ALL) {
            $events = $this->getRecurExceptions($baseEvent);
            $events->addRecord($baseEvent);
            $this->_applyExdateDiffToRecordSet($exdate, $diff, $events);
        } else if ($range === Calendar_Model_Event::RANGE_THISANDFUTURE) {
            $nextRegularRecurEvent = Calendar_Model_Rrule::computeNextOccurrence($baseEvent, new Tinebase_Record_RecordSet('Calendar_Model_Event'), $exdate->dtstart);
            
            if ($nextRegularRecurEvent == $baseEvent) {
                // NOTE if a fist instance exception takes place before the
                //      series would start normally, $nextOccurence is the
                //      baseEvent of the series. As createRecurException can't
                //      deal with this situation we update whole series here
                $this->_updateExdateRange($exdate, Calendar_Model_Event::RANGE_ALL, $oldExdate);
            } else if ($nextRegularRecurEvent !== NULL && ! $nextRegularRecurEvent->dtstart->isEarlier($exdate->dtstart)) {
                $this->_applyDiff($nextRegularRecurEvent, $diff, $exdate, FALSE);
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Next recur exception event at: ' . $nextRegularRecurEvent->dtstart->toString());
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' ' . print_r($nextRegularRecurEvent->toArray(), TRUE));
                
                $nextRegularRecurEvent->mute = $exdate->mute;
                $newBaseEvent = $this->createRecurException($nextRegularRecurEvent, FALSE, TRUE);
                // @todo this should be done by createRecurException
                $exdatesOfNewBaseEvent = $this->getRecurExceptions($newBaseEvent);
                $this->_applyExdateDiffToRecordSet($exdate, $diff, $exdatesOfNewBaseEvent);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' No upcoming occurrences found.');
            }
        }
    }
    
    /**
     * apply exdate diff to a recordset of events
     * 
     * @param Calendar_Model_Event $exdate
     * @param Tinebase_Record_Diff $diff
     * @param Tinebase_Record_RecordSet $events
     */
    protected function _applyExdateDiffToRecordSet($exdate, $diff, $events)
    {
        // make sure baseEvent gets updated first to circumvent concurrency conflicts
        $events->sort('recurdid', 'ASC');

        foreach ($events as $event) {
            if ($event->getId() === $exdate->getId()) {
                // skip the exdate
                continue;
            }
            $this->_applyDiff($event, $diff, $exdate, FALSE);
            $this->update($event);
        }
    }
    
    /**
     * merge updates from exdate into event
     * 
     * @param Calendar_Model_Event $event
     * @param Tinebase_Record_Diff $diff
     * @param Calendar_Model_Event $exdate
     * @param boolean $overwriteMods
     * 
     * @todo is $overwriteMods needed?
     */
    protected function _applyDiff($event, $diff, $exdate, $overwriteMods = TRUE)
    {
        if (! $overwriteMods) {
            $recentChanges = Tinebase_Timemachine_ModificationLog::getInstance()->getModifications('Calendar', $event, NULL, 'Sql', $exdate->creation_time);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Recent changes (since ' . $exdate->creation_time->toString() . '): ' . print_r($recentChanges->toArray(), TRUE));
        } else {
            $recentChanges = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog');
        }
        
        $diffIgnore = array('organizer', 'seq', 'last_modified_by', 'last_modified_time', 'dtstart', 'dtend');
        foreach ($diff->diff as $key => $newValue) {
            if ($key === 'attendee') {
                if (in_array($key, $recentChanges->modified_attribute)) {
                    $attendeeDiff = $diff->diff['attendee'];
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' Attendee diff: ' . print_r($attendeeDiff->toArray(), TRUE));
                    foreach ($attendeeDiff['added'] as $attenderToAdd) {
                        $attenderToAdd->setId(NULL);
                        $event->attendee->addRecord($attenderToAdd);
                    }
                    foreach ($attendeeDiff['removed'] as $attenderToRemove) {
                        $attenderInCurrentSet = Calendar_Model_Attender::getAttendee($event->attendee, $attenderToRemove);
                        if ($attenderInCurrentSet) {
                            $event->attendee->removeRecord($attenderInCurrentSet);
                        }
                    }
                } else {
                    // remove ids of new attendee
                    $attendee = clone($exdate->attendee);
                    foreach ($attendee as $attender) {
                        if (! $event->attendee->getById($attender->getId())) {
                            $attender->setId(NULL);
                        }
                    }
                    $event->attendee = $attendee;
                }
            } else if (! in_array($key, $diffIgnore) && ! in_array($key, $recentChanges->modified_attribute)) {
                $event->{$key} = $exdate->{$key};
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' Ignore / recently changed: ' . $key);
            }
        }
        
        if ((isset($diff->diff['dtstart']) || array_key_exists('dtstart', $diff->diff)) || (isset($diff->diff['dtend']) || array_key_exists('dtend', $diff->diff))) {
            $this->_applyTimeDiff($event, $exdate);
        }
    }
    
    /**
     * update multiple records
     * 
     * @param   Tinebase_Model_Filter_FilterGroup $_filter
     * @param   array $_data
     * @return  integer number of updated records
     */
    public function updateMultiple($_filter, $_data)
    {
        $this->_checkRight('update');
        $this->checkFilterACL($_filter, 'update');
        
        // get only ids
        $ids = $this->_backend->search($_filter, NULL, TRUE);
        
        foreach ($ids as $eventId) {
            $event = $this->get($eventId);
            foreach ($_data as $field => $value) {
                $event->$field = $value;
            }
            
            $this->update($event);
        }
        
        return count($ids);
    }
    
    /**
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array $_ids array of record identifiers
     * @param   string $range
     * @return  NULL
     * @throws Tinebase_Exception_NotFound|Tinebase_Exception
     */
    public function delete($_ids, $range = Calendar_Model_Event::RANGE_THIS)
    {
        if ($_ids instanceof $this->_modelName) {
            $_ids = (array)$_ids->getId();
        }
        
        $records = $this->_backend->getMultiple((array) $_ids);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Deleting " . count($records) . ' with range ' . $range . ' ...');
        
        foreach ($records as $record) {
            if ($record->isRecurException() && in_array($range, array(Calendar_Model_Event::RANGE_ALL, Calendar_Model_Event::RANGE_THISANDFUTURE))) {
                $this->_deleteExdateRange($record, $range);
            }
            
            try {
                $db = $this->_backend->getAdapter();
                $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
                
                // delete if delete grant is present
                if ($this->_doContainerACLChecks === FALSE || $record->hasGrant(Tinebase_Model_Grants::GRANT_DELETE)) {
                    // NOTE delete needs to update sequence otherwise iTIP based protocolls ignore the delete
                    $record->status = Calendar_Model_Event::STATUS_CANCELED;
                    $this->_touch($record);
                    if ($record->isRecurException()) {
                        try {
                            $baseEvent = $this->getRecurBaseEvent($record);
                            $this->_touch($baseEvent);
                        } catch (Tinebase_Exception_NotFound $tnfe) {
                            // base Event might be gone already
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                                . " BaseEvent of exdate {$record->uid} to delete not found ");

                        }
                    }
                    parent::delete($record);
                }
                
                // otherwise update status for user to DECLINED
                else if ($record->attendee instanceof Tinebase_Record_RecordSet) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " user has no deleteGrant for event: " . $record->id . ", updating own status to DECLINED only");
                    $ownContact = Tinebase_Core::getUser()->contact_id;
                    foreach ($record->attendee as $attender) {
                        if ($attender->user_id == $ownContact && in_array($attender->user_type, array(Calendar_Model_Attender::USERTYPE_USER, Calendar_Model_Attender::USERTYPE_GROUPMEMBER))) {
                            $attender->status = Calendar_Model_Attender::STATUS_DECLINED;
                            $this->attenderStatusUpdate($record, $attender, $attender->status_authkey);
                        }
                    }
                }
                
                // increase display container content sequence for all attendee of deleted event
                if ($record->attendee instanceof Tinebase_Record_RecordSet) {
                    foreach ($record->attendee as $attender) {
                        $this->_increaseDisplayContainerContentSequence($attender, $record, Tinebase_Model_ContainerContent::ACTION_DELETE);
                    }
                }
                
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            } catch (Exception $e) {
                Tinebase_TransactionManager::getInstance()->rollBack();
                throw $e;
            }
        }
    }
    
    /**
     * delete range of events starting with given recur exception
     *
     * NOTE: if exdate is persistent, it will not be deleted by this function
     *       but by the original call of delete
     *
     * @param Calendar_Model_Event $exdate
     * @param string $range
     */
    protected function _deleteExdateRange($exdate, $range)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Deleting events (range: ' . $range . ') belonging to recur exception event ' . $exdate->getId());
        
        $baseEvent = $this->getRecurBaseEvent($exdate);
        
        if ($range === Calendar_Model_Event::RANGE_ALL) {
            $this->deleteRecurSeries($exdate);
        } else if ($range === Calendar_Model_Event::RANGE_THISANDFUTURE) {
            $nextRegularRecurEvent = Calendar_Model_Rrule::computeNextOccurrence($baseEvent, new Tinebase_Record_RecordSet('Calendar_Model_Event'), $exdate->dtstart);
            
            if ($nextRegularRecurEvent == $baseEvent) {
                // NOTE if a fist instance exception takes place before the
                //      series would start normally, $nextOccurence is the
                //      baseEvent of the series. As createRecurException can't
                //      deal with this situation we delete whole series here
                $this->_deleteExdateRange($exdate, Calendar_Model_Event::RANGE_ALL);
            } else {
                $this->createRecurException($nextRegularRecurEvent, TRUE, TRUE);
            }
        }
    }
    
    /**
     * updates a recur series
     *
     * @param  Calendar_Model_Event $_recurInstance
     * @param  bool                 $_checkBusyConflicts
     * @return Calendar_Model_Event
     */
    public function updateRecurSeries($_recurInstance, $_checkBusyConflicts = FALSE)
    {
        $baseEvent = $this->getRecurBaseEvent($_recurInstance);
        
        // replace baseEvent with adopted instance
        $newBaseEvent = clone $_recurInstance;
        $newBaseEvent->setId($baseEvent->getId());
        unset($newBaseEvent->recurid);
        $newBaseEvent->exdate = $baseEvent->exdate;
        
        $this->_applyTimeDiff($newBaseEvent, $_recurInstance, $baseEvent);
        
        return $this->update($newBaseEvent, $_checkBusyConflicts);
    }
    
    /**
     * apply time diff
     * 
     * @param Calendar_Model_Event $newEvent
     * @param Calendar_Model_Event $fromEvent
     * @param Calendar_Model_Event $baseEvent
     */
    protected function _applyTimeDiff($newEvent, $fromEvent, $baseEvent = NULL)
    {
        if (! $baseEvent) {
            $baseEvent = $newEvent;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' New event: ' . print_r($newEvent->toArray(), TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' From event: ' . print_r($fromEvent->toArray(), TRUE));
        
        // compute time diff (NOTE: if the $fromEvent is the baseEvent, it has no recurid)
        $originalDtStart = $fromEvent->recurid ? new Tinebase_DateTime(substr($fromEvent->recurid, -19), 'UTC') : clone $baseEvent->dtstart;
        
        $dtstartDiff = $originalDtStart->diff($fromEvent->dtstart);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Dtstart diff: " . $dtstartDiff->format('%H:%M:%i'));
        $eventDuration = $fromEvent->dtstart->diff($fromEvent->dtend);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Duration diff: " . $dtstartDiff->format('%H:%M:%i'));
        
        $newEvent->dtstart = clone $baseEvent->dtstart;
        $newEvent->dtstart->add($dtstartDiff);
        
        $newEvent->dtend = clone $newEvent->dtstart;
        $newEvent->dtend->add($eventDuration);
    }
    
    /**
     * creates an exception instance of a recurring event
     *
     * NOTE: deleting persistent exceptions is done via a normal delete action
     *       and handled in the deleteInspection
     * 
     * @param  Calendar_Model_Event  $_event
     * @param  bool                  $_deleteInstance
     * @param  bool                  $_allFollowing
     * @param  bool                  $_checkBusyConflicts
     * @return Calendar_Model_Event  exception Event | updated baseEvent
     * 
     * @todo replace $_allFollowing param with $range
     * @deprecated replace with create/update/delete
     */
    public function createRecurException($_event, $_deleteInstance = FALSE, $_allFollowing = FALSE, $_checkBusyConflicts = FALSE)
    {
        $baseEvent = $this->getRecurBaseEvent($_event);
        
        if ($baseEvent->last_modified_time != $_event->last_modified_time) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . " It is not allowed to create recur instance if it is clone of base event");
            throw new Tinebase_Timemachine_Exception_ConcurrencyConflict('concurrency conflict!');
        }

//        // Maybe Later
//        // exdates needs to stay in baseEvents container
//        if ($_event->container_id != $baseEvent->container_id) {
//            throw new Calendar_Exception_ExdateContainer();
//        }

        // check if this is an exception to the first occurence
        if ($baseEvent->getId() == $_event->getId()) {
            if ($_allFollowing) {
                throw new Exception('please edit or delete complete series!');
            }
            // NOTE: if the baseEvent gets a time change, we can't compute the recurdid w.o. knowing the original dtstart
            $recurid = $baseEvent->setRecurId($baseEvent->getId());
            unset($baseEvent->recurid);
            $_event->recurid = $recurid;
        }
        
        // just do attender status update if user has no edit grant
        if ($this->_doContainerACLChecks && !$baseEvent->{Tinebase_Model_Grants::GRANT_EDIT}) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " user has no editGrant for event: '{$baseEvent->getId()}'. Only creating exception for attendee status");
            if ($_event->attendee instanceof Tinebase_Record_RecordSet) {
                foreach ($_event->attendee as $attender) {
                    if ($attender->status_authkey) {
                        $exceptionAttender = $this->attenderStatusCreateRecurException($_event, $attender, $attender->status_authkey, $_allFollowing);
                    }
                }
            }
            
            if (! isset($exceptionAttender)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG) && $_event->attendee instanceof Tinebase_Record_RecordSet) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Failed to update attendee: " . print_r($_event->attendee->toArray(), true));
                }
                throw new Tinebase_Exception_AccessDenied('Failed to update attendee, status authkey might be missing');
            }
            
            return $this->get($exceptionAttender->cal_event_id);
        }
        
        // NOTE: recurid is computed by rrule recur computations and therefore is already part of the event.
        if (empty($_event->recurid)) {
            throw new Exception('recurid must be present to create exceptions!');
        }
        
        // we do notifications ourself
        $sendNotifications = $this->sendNotifications(FALSE);
        
        // EDIT for baseEvent is checked above, CREATE, DELETE for recur exceptions is implied with it
        $doContainerACLChecks = $this->doContainerACLChecks(FALSE);
        
        $db = $this->_backend->getAdapter();
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
        
        $exdate = new Tinebase_DateTime(substr($_event->recurid, -19));
        $exdates = is_array($baseEvent->exdate) ? $baseEvent->exdate : array();
        $originalDtstart = $_event->getOriginalDtStart();
        $originalEvent = Calendar_Model_Rrule::computeNextOccurrence($baseEvent, new Tinebase_Record_RecordSet('Calendar_Model_Event'), $originalDtstart);
        
        if ($_allFollowing != TRUE) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . " Adding exdate for: '{$_event->recurid}'");
            
            array_push($exdates, $exdate);
            $baseEvent->exdate = $exdates;
            $updatedBaseEvent = $this->update($baseEvent, FALSE);
            
            if ($_deleteInstance == FALSE) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . " Creating persistent exception for: '{$_event->recurid}'");
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                    . " Recur exception: " . print_r($_event->toArray(), TRUE));

                $_event->base_event_id = $baseEvent->getId();
                $_event->setId(NULL);
                unset($_event->rrule);
                unset($_event->exdate);
            
                foreach (array('attendee', 'notes', 'alarms') as $prop) {
                    if ($_event->{$prop} instanceof Tinebase_Record_RecordSet) {
                        $_event->{$prop}->setId(NULL);
                    }
                }
                
                $originalDtstart = $_event->getOriginalDtStart();
                $dtStartHasDiff = $originalDtstart->compare($_event->dtstart) != 0; // php52 compat
                
                if (! $dtStartHasDiff) {
                    $attendees = $_event->attendee;
                    unset($_event->attendee);
                }
                $note = $_event->notes;
                unset($_event->notes);
                $persistentExceptionEvent = $this->create($_event, $_checkBusyConflicts);
                
                if (! $dtStartHasDiff) {
                    // we save attendee seperatly to preserve their attributes
                    if ($attendees instanceof Tinebase_Record_RecordSet) {
                        $attendees->cal_event_id = $persistentExceptionEvent->getId();
                        $calendar = Tinebase_Container::getInstance()->getContainerById($_event->container_id);
                        foreach ($attendees as $attendee) {
                            $this->_createAttender($attendee, $_event, TRUE, $calendar);
                            $this->_increaseDisplayContainerContentSequence($attendee, $persistentExceptionEvent, Tinebase_Model_ContainerContent::ACTION_CREATE);
                        }
                    }
                }
                
                // @todo save notes and add a update note -> what was updated? -> modlog is also missing
                $persistentExceptionEvent = $this->get($persistentExceptionEvent->getId());
            }
            
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " shorten recur series for/to: '{$_event->recurid}'");
                
            // split past/future exceptions
            $pastExdates = array();
            $futureExdates = array();
            foreach($exdates as $exdate) {
                $exdate->isLater($_event->dtstart) ? $futureExdates[] = $exdate : $pastExdates[] = $exdate;
            }
            
            $persistentExceptionEvents = $this->getRecurExceptions($_event);
            $pastPersistentExceptionEvents = new Tinebase_Record_RecordSet('Calendar_Model_Event');
            $futurePersistentExceptionEvents = new Tinebase_Record_RecordSet('Calendar_Model_Event');
            foreach ($persistentExceptionEvents as $persistentExceptionEvent) {
                $persistentExceptionEvent->dtstart->isLater($_event->dtstart) ? $futurePersistentExceptionEvents->addRecord($persistentExceptionEvent) : $pastPersistentExceptionEvents->addRecord($persistentExceptionEvent);
            }
            
            // update baseEvent
            $rrule = Calendar_Model_Rrule::getRruleFromString($baseEvent->rrule);
            if (isset($rrule->count)) {
                // get all occurences and find the split
                
                $exdate = $baseEvent->exdate;
                $baseEvent->exdate = NULL;
                    //$baseCountOccurrence = Calendar_Model_Rrule::computeNextOccurrence($baseEvent, new Tinebase_Record_RecordSet('Calendar_Model_Event'), $baseEvent->rrule_until, $baseCount);
                $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($baseEvent, new Tinebase_Record_RecordSet('Calendar_Model_Event'), $baseEvent->dtstart, $baseEvent->rrule_until);
                $baseEvent->exdate = $exdate;
                
                $originalDtstart = $_event->getOriginalDtStart();
                foreach($recurSet as $idx => $rInstance) {
                    if ($rInstance->dtstart >= $originalDtstart) break;
                }
                
                $rrule->count = $idx+1;
            } else {
                $lastBaseOccurence = Calendar_Model_Rrule::computeNextOccurrence($baseEvent, new Tinebase_Record_RecordSet('Calendar_Model_Event'), $_event->getOriginalDtStart()->subSecond(1), -1);
                $rrule->until = $lastBaseOccurence ? $lastBaseOccurence->getOriginalDtStart() : $baseEvent->dtstart;
            }
            $baseEvent->rrule = (string) $rrule;
            $baseEvent->exdate = $pastExdates;

            // NOTE: we don't want implicit attendee updates
            //$updatedBaseEvent = $this->update($baseEvent, FALSE);
            $this->_inspectEvent($baseEvent);
            $updatedBaseEvent = parent::update($baseEvent);
            
            if ($_deleteInstance == TRUE) {
                // delete all future persistent events
                $this->delete($futurePersistentExceptionEvents->getId());
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " create new recur series for/at: '{$_event->recurid}'");
                
                // NOTE: in order to move exceptions correctly in time we need to find out the original dtstart
                //       and create the new baseEvent with this time. A following update also updates its exceptions
                $originalDtstart = new Tinebase_DateTime(substr($_event->recurid, -19));
                $adoptedDtstart = clone $_event->dtstart;
                $dtStartHasDiff = $adoptedDtstart->compare($originalDtstart) != 0; // php52 compat
                $eventLength = $_event->dtstart->diff($_event->dtend);
                
                $_event->dtstart = clone $originalDtstart;
                $_event->dtend = clone $originalDtstart;
                $_event->dtend->add($eventLength);
                
                // adopt count
                if (isset($rrule->count)) {
                    $baseCount = $rrule->count;
                    $rrule = Calendar_Model_Rrule::getRruleFromString($_event->rrule);
                    $rrule->count = $rrule->count - $baseCount;
                    $_event->rrule = (string) $rrule;
                }
                
                $_event->setId(Tinebase_Record_Abstract::generateUID());
                $_event->uid = $futurePersistentExceptionEvents->uid = Tinebase_Record_Abstract::generateUID();
                $_event->setId(Tinebase_Record_Abstract::generateUID());
                $futurePersistentExceptionEvents->setRecurId($_event->getId());
                unset($_event->recurid);
                unset($_event->base_event_id);
                foreach(array('attendee', 'notes', 'alarms') as $prop) {
                    if ($_event->{$prop} instanceof Tinebase_Record_RecordSet) {
                        $_event->{$prop}->setId(NULL);
                    }
                }
                $_event->exdate = $futureExdates;

                $attendees = $_event->attendee; unset($_event->attendee);
                $note = $_event->notes; unset($_event->notes);
                $persistentExceptionEvent = $this->create($_event, $_checkBusyConflicts && $dtStartHasDiff);
                
                // we save attendee separately to preserve their attributes
                if ($attendees instanceof Tinebase_Record_RecordSet) {
                    foreach($attendees as $attendee) {
                        $this->_createAttender($attendee, $persistentExceptionEvent, true);
                    }
                }
                
                // @todo save notes and add a update note -> what was updated? -> modlog is also missing
                
                $persistentExceptionEvent = $this->get($persistentExceptionEvent->getId());
                
                foreach($futurePersistentExceptionEvents as $futurePersistentExceptionEvent) {
                    $this->update($futurePersistentExceptionEvent, FALSE);
                }
                
                if ($dtStartHasDiff) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " new recur series has adpted dtstart -> update to adopt exceptions'");
                    $persistentExceptionEvent->dtstart = clone $adoptedDtstart;
                    $persistentExceptionEvent->dtend = clone $adoptedDtstart;
                    $persistentExceptionEvent->dtend->add($eventLength);
                    
                    $persistentExceptionEvent = $this->update($persistentExceptionEvent, $_checkBusyConflicts);
                }
            }
        }
        
        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        
        // restore original notification handling
        $this->sendNotifications($sendNotifications);
        $notificationAction = $_deleteInstance ? 'deleted' : 'changed';
        $notificationEvent = $_deleteInstance ? $_event : $persistentExceptionEvent;
        
        // restore acl
        $this->doContainerACLChecks($doContainerACLChecks);
        
        // send notifications
        if ($this->_sendNotifications && $_event->mute != 1) {
            // NOTE: recur exception is a fake event from client. 
            //       this might lead to problems, so we wrap the calls
            try {
                if (count($_event->attendee) > 0) {
                    $_event->attendee->bypassFilters = TRUE;
                }
                $_event->created_by = $baseEvent->created_by;
                
                $this->doSendNotifications($notificationEvent, Tinebase_Core::getUser(), $notificationAction, $originalEvent);
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $e->getTraceAsString());
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " could not send notification {$e->getMessage()}");
            }
        }
        
        return $_deleteInstance ? $updatedBaseEvent : $persistentExceptionEvent;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Controller_Record_Abstract::get()
     */
    public function get($_id, $_containerId = NULL, $_getRelatedData = TRUE)
    {
        if (preg_match('/^fakeid/', $_id)) {
            // get base event when trying to fetch a non-persistent recur instance
            return $this->getRecurBaseEvent(new Calendar_Model_Event(array('uid' => substr(str_replace('fakeid', '', $_id), 0, 40))), TRUE);
        } else {
            return parent::get($_id, $_containerId, $_getRelatedData);
        }
    }
    
    /**
     * returns base event of a recurring series
     *
     * @param  Calendar_Model_Event $_event
     * @return Calendar_Model_Event
     */
    public function getRecurBaseEvent($_event)
    {
        $baseEventId = $_event->base_event_id ?: $_event->id;

        if (! $baseEventId) {
            throw new Tinebase_Exception_NotFound('base event of a recurring series not found');
        }

        // make sure we have a 'fully featured' event
        return $this->get($baseEventId);
    }

    /**
     * returns all persistent recur exceptions of recur series
     * 
     * NOTE: deleted instances are saved in the base events exception property
     * NOTE: returns all exceptions regardless of current filters and access restrictions
     * 
     * @param  Calendar_Model_Event        $_event
     * @param  boolean                     $_fakeDeletedInstances
     * @param  Calendar_Model_EventFilter  $_eventFilter
     * @return Tinebase_Record_RecordSet of Calendar_Model_Event
     */
    public function getRecurExceptions($_event, $_fakeDeletedInstances = FALSE, $_eventFilter = NULL)
    {
        $baseEventId = $_event->base_event_id ?: $_event->id;

        $exceptionFilter = new Calendar_Model_EventFilter(array(
            array('field' => 'base_event_id', 'operator' => 'equals',  'value' => $baseEventId),
            array('field' => 'recurid',       'operator' => 'notnull', 'value' => NULL)
        ));
        
        if ($_eventFilter instanceof Calendar_Model_EventFilter) {
            $exceptionFilter->addFilterGroup($_eventFilter);
        }
        
        $exceptions = $this->_backend->search($exceptionFilter);
        
        if ($_fakeDeletedInstances) {
            $baseEvent = $this->getRecurBaseEvent($_event);
            $this->fakeDeletedExceptions($baseEvent, $exceptions);
        }
        
        $exceptions->exdate = NULL;
        $exceptions->rrule = NULL;
        $exceptions->rrule_until = NULL;
        
        return $exceptions;
    }

    /**
     * add exceptions events for deleted instances
     *
     * @param Calendar_Model_Event $baseEvent
     * @param Tinebase_Record_RecordSet $exceptions
     */
    public function fakeDeletedExceptions($baseEvent, $exceptions)
    {
        $eventLength = $baseEvent->dtstart->diff($baseEvent->dtend);

        // compute remaining exdates
        $deletedInstanceDtStarts = array_diff(array_unique((array) $baseEvent->exdate), $exceptions->getOriginalDtStart());

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Faking ' . count($deletedInstanceDtStarts) . ' deleted exceptions');

        foreach((array) $deletedInstanceDtStarts as $deletedInstanceDtStart) {
            $fakeEvent = clone $baseEvent;
            $fakeEvent->setId(NULL);

            $fakeEvent->dtstart = clone $deletedInstanceDtStart;
            $fakeEvent->dtend = clone $deletedInstanceDtStart;
            $fakeEvent->dtend->add($eventLength);
            $fakeEvent->is_deleted = TRUE;
            $fakeEvent->setRecurId($baseEvent->getId());
            $fakeEvent->rrule = null;

            $exceptions->addRecord($fakeEvent);
        }
    }

   /**
    * adopt alarm time to next occurrence for recurring events
    *
    * @param Tinebase_Record_Abstract $_record
    * @param Tinebase_Model_Alarm $_alarm
    * @param bool $_nextBy {instance|time} set recurr alarm to next from given instance or next by current time
    * @return void
    */
    public function adoptAlarmTime(Tinebase_Record_Abstract $_record, Tinebase_Model_Alarm $_alarm, $_nextBy = 'time')
    {
        if ($_record->rrule) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                 ' Adopting alarm time for next recur occurrence (by ' . $_nextBy . ')');
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
                 ' ' . print_r($_record->toArray(), TRUE));
            
            if ($_nextBy == 'time') {
                // NOTE: this also finds instances running right now
                $from = Tinebase_DateTime::now();
            
            } else {
                $recurid = $_alarm->getOption('recurid');
                $instanceStart = $recurid ? new Tinebase_DateTime(substr($recurid, -19)) : clone $_record->dtstart;
                $eventLength = $_record->dtstart->diff($_record->dtend);
                
                $instanceStart->setTimezone($_record->originator_tz);
                $from = $instanceStart->add($eventLength);
                $from->setTimezone('UTC');
            }
            
            // compute next
            $exceptions = $this->getRecurExceptions($_record);
            $nextOccurrence = Calendar_Model_Rrule::computeNextOccurrence($_record, $exceptions, $from);
            
            if ($nextOccurrence === NULL) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                    ' Recur series is over, no more alarms pending');
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' Found next occurrence, adopting alarm to dtstart ' . $nextOccurrence->dtstart->toString());
            }
            
            // save recurid so we know for which recurrance the alarm is for
            $_alarm->setOption('recurid', isset($nextOccurrence) ? $nextOccurrence->recurid : NULL);
            
            $_alarm->sent_status = $nextOccurrence ? Tinebase_Model_Alarm::STATUS_PENDING : Tinebase_Model_Alarm::STATUS_SUCCESS;
            $_alarm->sent_message = $nextOccurrence ?  '' : 'Nothing to send, series is over';
            
            $eventStart = $nextOccurrence ? clone $nextOccurrence->dtstart : clone $_record->dtstart;
        } else {
            $eventStart = clone $_record->dtstart;
        }
        
        // save minutes before / compute it for custom alarms
        $minutesBefore = $_alarm->minutes_before == Tinebase_Model_Alarm::OPTION_CUSTOM 
            ? ($_record->dtstart->getTimestamp() - $_alarm->alarm_time->getTimestamp()) / 60 
            : $_alarm->minutes_before;
        $minutesBefore = round($minutesBefore);
        
        $_alarm->setOption('minutes_before', $minutesBefore);
        $_alarm->alarm_time = $eventStart->subMinute($minutesBefore);
        
        if ($_record->rrule && $_alarm->sent_status == Tinebase_Model_Alarm::STATUS_PENDING && $_alarm->alarm_time < $_alarm->sent_time) {
            $this->adoptAlarmTime($_record, $_alarm, 'instance');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' alarm: ' . print_r($_alarm->toArray(), true));
    }
    
    /****************************** overwritten functions ************************/
    
    /**
     * restore original alarm time of recurring events
     * 
     * @param Tinebase_Record_Abstract $_record
     * @return void
     */
    protected function _inspectAlarmGet(Tinebase_Record_Abstract $_record)
    {
        foreach ($_record->alarms as $alarm) {
            if ($recurid = $alarm->getOption('recurid')) {
                $alarm->alarm_time = clone $_record->dtstart;
                $alarm->alarm_time->subMinute((int) $alarm->getOption('minutes_before'));
            }
        }
        
        parent::_inspectAlarmGet($_record);
    }
    
    /**
     * adopt alarm time to next occurance for recurring events
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param Tinebase_Model_Alarm $_alarm
     * @param bool $_nextBy {instance|time} set recurr alarm to next from given instance or next by current time
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _inspectAlarmSet(Tinebase_Record_Abstract $_record, Tinebase_Model_Alarm $_alarm, $_nextBy = 'time')
    {
        parent::_inspectAlarmSet($_record, $_alarm);
        $this->adoptAlarmTime($_record, $_alarm, 'time');
    }
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        // if dtstart of an event changes, we update the originator_tz, alarm times
        if (! $_oldRecord->dtstart->equals($_record->dtstart)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' dtstart changed -> adopting organizer_tz');
            $_record->originator_tz = Tinebase_Core::getUserTimezone();
            if (! empty($_record->rrule)) {
                $diff = $_oldRecord->dtstart->diff($_record->dtstart);
                $this->_updateRecurIdOfExdates($_record, $diff);
            }
        }
        
        // delete recur exceptions if update is no longer a recur series
        if (! empty($_oldRecord->rrule) && empty($_record->rrule)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' deleting recur exceptions as event is no longer a recur series');
            $this->_backend->delete($this->getRecurExceptions($_record));
        }
        
        // touch base event of a recur series if an persistent exception changes
        if ($_record->recurid) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' touch base event of a persistent exception');
            $baseEvent = $this->getRecurBaseEvent($_record);
            $this->_touch($baseEvent, TRUE);
        }
    }
    
    /**
     * update exdates and recurids if dtstart of an recurevent changes
     * 
     * @param Calendar_Model_Event $_record
     * @param DateInterval $diff
     */
    protected function _updateRecurIdOfExdates($_record, $diff)
    {
        // update exceptions
        $exceptions = $this->getRecurExceptions($_record);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' dtstart of a series changed -> adopting '. count($exceptions) . ' recurid(s)');
        $exdates = array();
        foreach ($exceptions as $exception) {
            $exception->recurid = new Tinebase_DateTime(substr($exception->recurid, -19));
            Calendar_Model_Rrule::addUTCDateDstFix($exception->recurid, $diff, $_record->originator_tz);
            $exdates[] = $exception->recurid;
            
            $exception->setRecurId($_record->getId());
            $this->_backend->update($exception);
        }
        
        $_record->exdate = $exdates;
    }
    
    /**
     * inspect before create/update
     * 
     * @TODO move stuff from other places here
     * @param   Calendar_Model_Event $_record      the record to inspect
     */
    protected function _inspectEvent($_record)
    {
        $_record->uid = $_record->uid ? $_record->uid : Tinebase_Record_Abstract::generateUID();
        $_record->organizer = $_record->organizer ? $_record->organizer : Tinebase_Core::getUser()->contact_id;
        $_record->transp = $_record->transp ? $_record->transp : Calendar_Model_Event::TRANSP_OPAQUE;

        $this->_inspectOriginatorTZ($_record);

        if ($_record->hasExternalOrganizer()) {
            // assert calendarUser as attendee. This is important to keep the event in the loop via its displaycontianer(s)
            try {
                $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
                $owner = $container->getOwner();
                $calendarUserId = Addressbook_Controller_Contact::getInstance()->getContactByUserId($owner, true)->getId();
            } catch (Exception $e) {
                $container = NULL;
                $calendarUserId = Tinebase_Core::getUser()->contact_id;
            }
            
            $attendee = $_record->assertAttendee(new Calendar_Model_Attender(array(
                'user_type'    => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'      => $calendarUserId
            )), false, false, true);
            
            if ($attendee && $container instanceof Tinebase_Model_Container) {
                $attendee->displaycontainer_id = $container->getId();
            }
            
            if (! $container instanceof Tinebase_Model_Container || $container->type == Tinebase_Model_Container::TYPE_PERSONAL) {
                // move into special (external users) container
                $container = Calendar_Controller::getInstance()->getInvitationContainer($_record->resolveOrganizer());
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Setting container_id to ' . $container->getId() . ' for external organizer ' . $_record->organizer->email);
                $_record->container_id = $container->getId();
            }
            
        }
        
        if ($_record->is_all_day_event) {
            // harmonize datetimes of all day events
            $_record->setTimezone($_record->originator_tz);
            if (! $_record->dtend) {
                $_record->dtend = clone $_record->dtstart;
                $_record->dtend->setTime(23,59,59);
            }
            $_record->dtstart->setTime(0,0,0);
            $_record->dtend->setTime(23,59,59);
            $_record->setTimezone('UTC');
        }
        $_record->setRruleUntil();
        
        if ($_record->rrule instanceof Calendar_Model_Rrule) {
            $_record->rrule->normalize($_record);
        }

        if ($_record->isRecurException()) {
            $baseEvent = $this->getRecurBaseEvent($_record);

            // remove invalid rrules
            if ($_record->rrule !== NULL) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Removing invalid rrule from recur exception: ' . $_record->rrule);
                $_record->rrule = NULL;
            }

//            // Maybe Later
//            // exdates needs to stay in baseEvents container
//            if($_record->container_id != $baseEvent->container_id) {
//                throw new Calendar_Exception_ExdateContainer();
//            }
        }
    }

    /**
     * checks/sets originator timezone
     *
     * @param $record
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _inspectOriginatorTZ($record)
    {
        $record->originator_tz = $record->originator_tz ? $record->originator_tz : Tinebase_Core::getUserTimezone();

        try {
            new DateTimeZone($record->originator_tz);
        } catch (Exception $e) {
            throw new Tinebase_Exception_Record_Validation('Bad Timezone: ' . $record->originator_tz);
        }
    }
    
    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     */
    protected function _inspectDelete(array $_ids) {
        $events = $this->_backend->getMultiple($_ids);
        
        foreach ($events as $event) {
            
            // implicitly delete persistent recur instances of series
            if (! empty($event->rrule)) {
                $exceptionIds = $this->getRecurExceptions($event)->getId();
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Implicitly deleting ' . (count($exceptionIds) - 1 ) . ' persistent exception(s) for recurring series with uid' . $event->uid);
                $_ids = array_merge($_ids, $exceptionIds);
            }
        }
        
        $this->_deleteAlarmsForIds($_ids);
        
        return array_unique($_ids);
    }
    
    /**
     * redefine required grants for get actions
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $hasGrantsFilter = FALSE;
        foreach($_filter->getAclFilters() as $aclFilter) {
            if ($aclFilter instanceof Calendar_Model_GrantFilter) {
                $hasGrantsFilter = TRUE;
                break;
            }
        }
        
        if (! $hasGrantsFilter) {
            // force a grant filter
            // NOTE: actual grants are set via setRequiredGrants later
            $grantsFilter = $_filter->createFilter('grants', 'in', '@setRequiredGrants');
            $_filter->addFilter($grantsFilter);
        }
        
        parent::checkFilterACL($_filter, $_action);
        
        if ($_action == 'get') {
            $_filter->setRequiredGrants(array(
                Tinebase_Model_Grants::GRANT_FREEBUSY,
                Tinebase_Model_Grants::GRANT_READ,
                Tinebase_Model_Grants::GRANT_ADMIN,
            ));
        }
    }
    
    /**
     * check grant for action (CRUD)
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Tinebase_Record_Interface $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     * 
     * @todo use this function in other create + update functions
     * @todo invent concept for simple adding of grants (plugins?) 
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (    ! $this->_doContainerACLChecks 
            // admin grant includes all others (only if class is PUBLIC)
            ||  (! empty($this->class) && $this->class === Calendar_Model_Event::CLASS_PUBLIC 
                && $_record->container_id && Tinebase_Core::getUser()->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_ADMIN))
            // external invitations are in a spechial invitaion calendar. only attendee can see it via displaycal
            ||  $_record->hasExternalOrganizer()
        ) {
            return true;
        }
        
        switch ($_action) {
            case 'get':
                // NOTE: free/busy is not a read grant!
                $hasGrant = $_record->hasGrant(Tinebase_Model_Grants::GRANT_READ);
                if (! $hasGrant) {
                    $_record->doFreeBusyCleanup();
                }
                break;
            case 'create':
                $hasGrant = Tinebase_Core::getUser()->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_ADD);
                break;
            case 'update':
                $hasGrant = (bool) $_oldRecord->hasGrant(Tinebase_Model_Grants::GRANT_EDIT);
                
                if ($_oldRecord->container_id != $_record->container_id) {
                    $hasGrant &= Tinebase_Core::getUser()->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_ADD)
                                 && $_oldRecord->hasGrant(Tinebase_Model_Grants::GRANT_DELETE);
                }
                break;
            case 'delete':
                $hasGrant = (bool) $_record->hasGrant(Tinebase_Model_Grants::GRANT_DELETE);
                break;
            case 'sync':
                $hasGrant = (bool) $_record->hasGrant(Tinebase_Model_Grants::GRANT_SYNC);
                break;
            case 'export':
                $hasGrant = (bool) $_record->hasGrant(Tinebase_Model_Grants::GRANT_EXPORT);
                break;
        }
        
        if (! $hasGrant) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' No permissions to ' . $_action . ' in container ' . $_record->container_id);
            }
        }
        
        return $hasGrant;
    }
    
    /**
     * touches (sets seq, last_modified_time and container content sequence) given event
     * 
     * @param  $_event
     * @return void
     */
    protected function _touch($_event, $_setModifier = FALSE)
    {
        $_event->last_modified_time = Tinebase_DateTime::now();
        $_event->seq = (int)$_event->seq + 1;
        if ($_setModifier) {
            $_event->last_modified_by = Tinebase_Core::getUser()->getId();
        }
        
        $this->_backend->update($_event);
        
        $this->_increaseContainerContentSequence($_event, Tinebase_Model_ContainerContent::ACTION_UPDATE);
    }
    
    /**
     * increase container content sequence
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $action
     */
    protected function _increaseContainerContentSequence(Tinebase_Record_Interface $record, $action = NULL)
    {
        parent::_increaseContainerContentSequence($record, $action);
        
        if ($record->attendee instanceof Tinebase_Record_RecordSet) {
            $updatedContainerIds = array($record->container_id);
            foreach ($record->attendee as $attender) {
                if (isset($attender->displaycontainer_id) && ! in_array($attender->displaycontainer_id, $updatedContainerIds)) {
                    Tinebase_Container::getInstance()->increaseContentSequence($attender->displaycontainer_id, $action, $record->getId());
                    $updatedContainerIds[] = $attender->displaycontainer_id;
                }
            }
        }
    }
    
    
    /****************************** attendee functions ************************/
    
    /**
     * creates an attender status exception of a recurring event series
     * 
     * NOTE: Recur exceptions are implicitly created
     *
     * @param  Calendar_Model_Event    $_recurInstance
     * @param  Calendar_Model_Attender $_attender
     * @param  string                  $_authKey
     * @param  bool                    $_allFollowing
     * @return Calendar_Model_Attender updated attender
     */
    public function attenderStatusCreateRecurException($_recurInstance, $_attender, $_authKey, $_allFollowing = FALSE)
    {
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $baseEvent = $this->getRecurBaseEvent($_recurInstance);
            $baseEventAttendee = Calendar_Model_Attender::getAttendee($baseEvent->attendee, $_attender);
            
            if ($baseEvent->getId() == $_recurInstance->getId()) {
                // exception to the first occurence
                $_recurInstance->setRecurId($baseEvent->getId());
            }
            
            // NOTE: recurid is computed by rrule recur computations and therefore is already part of the event.
            if (empty($_recurInstance->recurid)) {
                throw new Exception('recurid must be present to create exceptions!');
            }
            
            try {
                // check if we already have a persistent exception for this event
                $eventInstance = $this->_backend->getByProperty($_recurInstance->recurid, $_property = 'recurid');
                
                // NOTE: the user must exist (added by someone with appropriate rights by createRecurException)
                $exceptionAttender = Calendar_Model_Attender::getAttendee($eventInstance->attendee, $_attender);
                if (! $exceptionAttender) {
                    throw new Tinebase_Exception_AccessDenied('not an attendee');
                }
                
                
                if ($exceptionAttender->status_authkey != $_authKey) {
                    // NOTE: it might happen, that the user set her status from the base event without knowing about 
                    //       an existing exception. In this case the base event authkey is also valid
                    if (! $baseEventAttendee || $baseEventAttendee->status_authkey != $_authKey) {
                        throw new Tinebase_Exception_AccessDenied('Attender authkey mismatch');
                    }
                }
                
            } catch (Tinebase_Exception_NotFound $e) {
                // otherwise create it implicilty
                
                // check if this intance takes place
                if (in_array($_recurInstance->dtstart, (array)$baseEvent->exdate)) {
                    throw new Tinebase_Exception_AccessDenied('Event instance is deleted and may not be recreated via status setting!');
                }
                
                if (! $baseEventAttendee) {
                    throw new Tinebase_Exception_AccessDenied('not an attendee');
                }
                
                if ($baseEventAttendee->status_authkey != $_authKey) {
                    throw new Tinebase_Exception_AccessDenied('Attender authkey mismatch');
                }
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " creating recur exception for a exceptional attendee status");
                
                $doContainerAclChecks = $this->doContainerACLChecks(FALSE);
                $sendNotifications = $this->sendNotifications(FALSE);
                
                // NOTE: the user might have no edit grants, so let's be carefull
                $diff = $baseEvent->dtstart->diff($baseEvent->dtend);
                
                $baseEvent->dtstart = new Tinebase_DateTime(substr($_recurInstance->recurid, -19), 'UTC');
                $baseEvent->dtend   = clone $baseEvent->dtstart;
                $baseEvent->dtend->add($diff);

                $baseEvent->base_event_id = $baseEvent->id;
                $baseEvent->id = $_recurInstance->id;
                $baseEvent->recurid = $_recurInstance->recurid;

                $attendee = $baseEvent->attendee;
                unset($baseEvent->attendee);
                
                $eventInstance = $this->createRecurException($baseEvent, FALSE, $_allFollowing);
                $eventInstance->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
                $this->doContainerACLChecks($doContainerAclChecks);
                $this->sendNotifications($sendNotifications);
                
                foreach ($attendee as $attender) {
                    $attender->setId(NULL);
                    $attender->cal_event_id = $eventInstance->getId();
                    
                    $attender = $this->_backend->createAttendee($attender);
                    $eventInstance->attendee->addRecord($attender);
                    $this->_increaseDisplayContainerContentSequence($attender, $eventInstance, Tinebase_Model_ContainerContent::ACTION_CREATE);
                }
                
                $exceptionAttender = Calendar_Model_Attender::getAttendee($eventInstance->attendee, $_attender);
            }
            
            $exceptionAttender->status = $_attender->status;
            $exceptionAttender->transp = $_attender->transp;
            $eventInstance->alarms     = clone $_recurInstance->alarms;
            $eventInstance->alarms->setId(NULL);
            
            $updatedAttender = $this->attenderStatusUpdate($eventInstance, $exceptionAttender, $exceptionAttender->status_authkey);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        return $updatedAttender;
    }
    
    /**
     * updates an attender status of a event
     * 
     * @param  Calendar_Model_Event    $_event
     * @param  Calendar_Model_Attender $_attender
     * @param  string                  $_authKey
     * @return Calendar_Model_Attender updated attender
     */
    public function attenderStatusUpdate(Calendar_Model_Event $_event, Calendar_Model_Attender $_attender, $_authKey)
    {
        try {
            $event = $this->get($_event->getId());
            
            if (! $event->attendee) {
                throw new Tinebase_Exception_NotFound('Could not find any attendee of event.');
            }
            
            if (($currentAttender = Calendar_Model_Attender::getAttendee($event->attendee, $_attender)) == null) {
                throw new Tinebase_Exception_NotFound('Could not find attender in event.');
            }
            
            $updatedAttender = clone $currentAttender;
            
            if ($currentAttender->status_authkey !== $_authKey) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " no permissions to update status for {$currentAttender->user_type} {$currentAttender->user_id}");
                return $updatedAttender;
            }
            
            Calendar_Controller_Alarm::enforceACL($_event, $event);
            
            $currentAttenderDisplayContainerId = $currentAttender->displaycontainer_id instanceof Tinebase_Model_Container ? 
                $currentAttender->displaycontainer_id->getId() : 
                $currentAttender->displaycontainer_id;
            
            $attenderDisplayContainerId = $_attender->displaycontainer_id instanceof Tinebase_Model_Container ? 
                $_attender->displaycontainer_id->getId() : 
                $_attender->displaycontainer_id;
            
            // check if something what can be set as user has changed
            if ($currentAttender->status == $_attender->status &&
                $currentAttenderDisplayContainerId  == $attenderDisplayContainerId   &&
                $currentAttender->transp            == $_attender->transp            &&
                ! Calendar_Controller_Alarm::hasUpdates($_event, $event)
            ) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                    Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . "no status change -> do nothing");
                return $updatedAttender;
            }
            
            $updatedAttender->status              = $_attender->status;
            $updatedAttender->displaycontainer_id = isset($_attender->displaycontainer_id) ? $_attender->displaycontainer_id : $updatedAttender->displaycontainer_id;
            $updatedAttender->transp              = isset($_attender->transp) ? $_attender->transp : Calendar_Model_Event::TRANSP_OPAQUE;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " update attender status to {$_attender->status} for {$currentAttender->user_type} {$currentAttender->user_id}");
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' set alarm_ack_time / alarm_snooze_time: ' . $updatedAttender->alarm_ack_time . ' / ' . $updatedAttender->alarm_snooze_time);
            }
            
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            
            $updatedAttender = $this->_backend->updateAttendee($updatedAttender);
            if ($_event->alarms instanceof Tinebase_Record_RecordSet) {
                foreach($_event->alarms as $alarm) {
                    $this->_inspectAlarmSet($event, $alarm);
                }
                
                Tinebase_Alarm::getInstance()->setAlarmsOfRecord($_event);
            }
            
            $this->_increaseDisplayContainerContentSequence($updatedAttender, $event);

            if ($currentAttender->status != $updatedAttender->status) {
                $this->_touch($event, TRUE);
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        // send notifications
        if ($currentAttender->status != $updatedAttender->status && $this->_sendNotifications && $_event->mute != 1) {
            $updatedEvent = $this->get($event->getId());
            $this->doSendNotifications($updatedEvent, Tinebase_Core::getUser(), 'changed', $event);
        }
        
        return $updatedAttender;
    }
    
    /**
     * saves all attendee of given event
     * 
     * NOTE: This function is executed in a create/update context. As such the user
     *       has edit/update the event and can do anything besides status settings of attendee
     * 
     * @todo add support for resources
     * 
     * @param Calendar_Model_Event $_event
     * @param Calendar_Model_Event $_currentEvent
     * @param bool                 $_isRescheduled event got rescheduled reset all attendee status
     */
    protected function _saveAttendee($_event, $_currentEvent = NULL, $_isRescheduled = FALSE)
    {
        if (! $_event->attendee instanceof Tinebase_Record_RecordSet) {
            $_event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        }
        
        Calendar_Model_Attender::resolveEmailOnlyAttendee($_event);
        
        $_event->attendee->cal_event_id = $_event->getId();
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " About to save attendee for event {$_event->id} ");
        
        $currentAttendee = $_currentEvent->attendee;
        
        $diff = $currentAttendee->getMigration($_event->attendee->getArrayOfIds());

        $calendar = Tinebase_Container::getInstance()->getContainerById($_event->container_id);
        
        // delete attendee
        $this->_backend->deleteAttendee($diff['toDeleteIds']);
        foreach ($diff['toDeleteIds'] as $deleteAttenderId) {
            $idx = $currentAttendee->getIndexById($deleteAttenderId);
            if ($idx !== FALSE) {
                $currentAttenderToDelete = $currentAttendee[$idx];
                $this->_increaseDisplayContainerContentSequence($currentAttenderToDelete, $_event, Tinebase_Model_ContainerContent::ACTION_DELETE);
            }
        }
        
        // create/update attendee
        foreach ($_event->attendee as $attender) {
            $attenderId = $attender->getId();
            $idx = ($attenderId) ? $currentAttendee->getIndexById($attenderId) : FALSE;
            
            if ($idx !== FALSE) {
                $currentAttender = $currentAttendee[$idx];
                $this->_updateAttender($attender, $currentAttender, $_event, $_isRescheduled, $calendar);
            } else {
                $this->_createAttender($attender, $_event, FALSE, $calendar);
            }
        }
    }

    /**
     * creates a new attender
     * 
     * @param Calendar_Model_Attender  $attender
     * @param Tinebase_Model_Container $_calendar
     * @param boolean $preserveStatus
     * @param Tinebase_Model_Container $calendar
     */
    protected function _createAttender(Calendar_Model_Attender $attender, Calendar_Model_Event $event, $preserveStatus = FALSE, Tinebase_Model_Container $calendar = NULL)
    {
        // apply defaults
        $attender->user_type         = isset($attender->user_type) ? $attender->user_type : Calendar_Model_Attender::USERTYPE_USER;
        $attender->cal_event_id      =  $event->getId();
        $calendar = ($calendar) ? $calendar : Tinebase_Container::getInstance()->getContainerById($event->container_id);
        
        $userAccountId = $attender->getUserAccountId();
        
        // generate auth key
        if (! $attender->status_authkey) {
            $attender->status_authkey = Tinebase_Record_Abstract::generateUID();
        }
        
        // attach to display calendar if attender has/is a useraccount
        if ($userAccountId) {
            if ($calendar->type == Tinebase_Model_Container::TYPE_PERSONAL && Tinebase_Container::getInstance()->hasGrant($userAccountId, $calendar, Tinebase_Model_Grants::GRANT_ADMIN)) {
                // if attender has admin grant to personal physical container, this phys. cal also gets displ. cal
                $attender->displaycontainer_id = $calendar->getId();
            } else if ($attender->displaycontainer_id && $userAccountId == Tinebase_Core::getUser()->getId() && Tinebase_Container::getInstance()->hasGrant($userAccountId, $attender->displaycontainer_id, Tinebase_Model_Grants::GRANT_ADMIN)) {
                // allow user to set his own displ. cal
                $attender->displaycontainer_id = $attender->displaycontainer_id;
            } else {
                $displayCalId = self::getDefaultDisplayContainerId($userAccountId);
                $attender->displaycontainer_id = $displayCalId;
            }

        } else if ($attender->user_type === Calendar_Model_Attender::USERTYPE_RESOURCE) {
            $resource = Calendar_Controller_Resource::getInstance()->get($attender->user_id);
            $attender->displaycontainer_id = $resource->container_id;
        }
        
        if ($attender->displaycontainer_id) {
            // check if user is allowed to set status
            if (! $preserveStatus && ! Tinebase_Core::getUser()->hasGrant($attender->displaycontainer_id, Tinebase_Model_Grants::GRANT_EDIT)) {
                if ($attender->user_type === Calendar_Model_Attender::USERTYPE_RESOURCE) {
                    //If resource has an default status use this
                    $attender->status = isset($resource->status) ? $resource->status : Calendar_Model_Attender::STATUS_NEEDSACTION;
                } else {
                    $attender->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " New attender: " . print_r($attender->toArray(), TRUE));

        Tinebase_Timemachine_ModificationLog::getInstance()->setRecordMetaData($attender, 'create');
        $this->_backend->createAttendee($attender);
        $this->_increaseDisplayContainerContentSequence($attender, $event, Tinebase_Model_ContainerContent::ACTION_CREATE);
    }
    
        
    /**
     * returns the default calendar
     * 
     * @return Tinebase_Model_Container
     */
    public function getDefaultCalendar()
    {
        return Tinebase_Container::getInstance()->getDefaultContainer($this->_applicationName, NULL, Calendar_Preference::DEFAULTCALENDAR);
    }
    
    /**
     * returns default displayContainer id of given attendee
     *
     * @param string $userAccountId
     */
    public static function getDefaultDisplayContainerId($userAccountId)
    {
        $userAccountId = Tinebase_Model_User::convertUserIdToInt($userAccountId);
        $displayCalId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $userAccountId);
        
        try {
            // assert that displaycal is of type personal
            $container = Tinebase_Container::getInstance()->getContainerById($displayCalId);
            if ($container->type != Tinebase_Model_Container::TYPE_PERSONAL) {
                $displayCalId = NULL;
            }
        } catch (Exception $e) {
            $displayCalId = NULL;
        }
        
        if (! isset($displayCalId)) {
            $containers = Tinebase_Container::getInstance()->getPersonalContainer($userAccountId, 'Calendar_Model_Event', $userAccountId, 0, true);
            if ($containers->count() > 0) {
                $displayCalId = $containers->getFirstRecord()->getId();
            }
        }
        
        return $displayCalId;
    }
    
    /**
     * increases content sequence of attender display container
     * 
     * @param Calendar_Model_Attender $attender
     * @param Calendar_Model_Event $event
     * @param string $action
     */
    protected function _increaseDisplayContainerContentSequence($attender, $event, $action = Tinebase_Model_ContainerContent::ACTION_UPDATE)
    {
        if ($event->container_id === $attender->displaycontainer_id || empty($attender->displaycontainer_id)) {
            // no need to increase sequence
            return;
        }
        
        Tinebase_Container::getInstance()->increaseContentSequence($attender->displaycontainer_id, $action, $event->getId());
    }
    
    /**
     * updates an attender
     * 
     * @param Calendar_Model_Attender  $attender
     * @param Calendar_Model_Attender  $currentAttender
     * @param Calendar_Model_Event     $event
     * @param bool                     $isRescheduled event got rescheduled reset all attendee status
     * @param Tinebase_Model_Container $calendar
     */
    protected function _updateAttender($attender, $currentAttender, $event, $isRescheduled, $calendar = NULL)
    {
        $userAccountId = $currentAttender->getUserAccountId();

        // update display calendar if attender has/is a useraccount
        if ($userAccountId) {
            if ($calendar->type == Tinebase_Model_Container::TYPE_PERSONAL && Tinebase_Container::getInstance()->hasGrant($userAccountId, $calendar, Tinebase_Model_Grants::GRANT_ADMIN)) {
                // if attender has admin grant to personal physical container, this phys. cal also gets displ. cal
                $attender->displaycontainer_id = $calendar->getId();
            } else if ($userAccountId == Tinebase_Core::getUser()->getId() && Tinebase_Container::getInstance()->hasGrant($userAccountId, $attender->displaycontainer_id, Tinebase_Model_Grants::GRANT_ADMIN)) {
                // allow user to set his own displ. cal
                $attender->displaycontainer_id = $attender->displaycontainer_id;
            } else {
                $attender->displaycontainer_id = $currentAttender->displaycontainer_id;
            }
        }

        // reset status if user has no right and authkey is wrong
        if ($attender->displaycontainer_id) {
            if (! Tinebase_Core::getUser()->hasGrant($attender->displaycontainer_id, Tinebase_Model_Grants::GRANT_EDIT)
                    && $attender->status_authkey != $currentAttender->status_authkey) {

                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Wrong authkey, resetting status (' . $attender->status . ' -> ' . $currentAttender->status . ')');
                $attender->status = $currentAttender->status;
            }
        }

        // reset all status but calUser on reschedule except resources (Resources might have a configured default value)
        if ($isRescheduled && !$attender->isSame($this->getCalendarUser())) {
            if ($attender->user_type === Calendar_Model_Attender::USERTYPE_RESOURCE) {
                //If resource has a default status reset to this
                $resource = Calendar_Controller_Resource::getInstance()->get($attender->user_id);
                $attender->status = isset($resource->status) ? $resource->status : Calendar_Model_Attender::STATUS_NEEDSACTION;
            } else {
                $attender->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
            }
            $attender->transp = null;
        }

        // preserve old authkey
        $attender->status_authkey = $currentAttender->status_authkey;

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . " Updating attender: " . print_r($attender->toArray(), TRUE));


        Tinebase_Timemachine_ModificationLog::getInstance()->setRecordMetaData($attender, 'update', $currentAttender);
        Tinebase_Timemachine_ModificationLog::getInstance()->writeModLog($attender, $currentAttender, get_class($attender), $this->_getBackendType(), $attender->getId());
        $this->_backend->updateAttendee($attender);
        
        if ($attender->displaycontainer_id !== $currentAttender->displaycontainer_id) {
            $this->_increaseDisplayContainerContentSequence($currentAttender, $event, Tinebase_Model_ContainerContent::ACTION_DELETE);
            $this->_increaseDisplayContainerContentSequence($attender, $event, Tinebase_Model_ContainerContent::ACTION_CREATE);
        } else {
            $this->_increaseDisplayContainerContentSequence($attender, $event);
        }
    }
    
    /**
     * event handler for group updates
     * 
     * @param Tinebase_Model_Group $_group
     * @return void
     */
    public function onUpdateGroup($_groupId)
    {
        $doContainerACLChecks = $this->doContainerACLChecks(FALSE);
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'attender', 'operator' => 'equals', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
                'user_id'   => $_groupId
            )),
            array('field' => 'period', 'operator' => 'within', 'value' => array(
                'from'  => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
                'until' => Tinebase_DateTime::now()->addYear(100)->get(Tinebase_Record_Abstract::ISO8601LONG))
            )
        ));
        $events = $this->search($filter, new Tinebase_Model_Pagination(), FALSE, FALSE);
        
        foreach($events as $event) {
            try {
                if (! $event->rrule) {
                    // update non recurring futrue events
                    Calendar_Model_Attender::resolveGroupMembers($event->attendee);
                    $this->update($event);
                } else {
                    // update thisandfuture for recurring events
                    $nextOccurrence = Calendar_Model_Rrule::computeNextOccurrence($event, $this->getRecurExceptions($event), Tinebase_DateTime::now());
                    Calendar_Model_Attender::resolveGroupMembers($nextOccurrence->attendee);
                    
                    if ($nextOccurrence->dtstart != $event->dtstart) {
                        $this->createRecurException($nextOccurrence, FALSE, TRUE);
                    } else {
                        $this->update($nextOccurrence);
                    }
                }
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->NOTICE(__METHOD__ . '::' . __LINE__ . " could not update attendee");
            }
        }
        
        $this->doContainerACLChecks($doContainerACLChecks);
    }
    
    /****************************** alarm functions ************************/
    
    /**
     * send an alarm
     *
     * @param  Tinebase_Model_Alarm $_alarm
     * @return void
     * 
     * NOTE: the given alarm is raw and has not passed _inspectAlarmGet
     *  
     * @todo throw exception on error
     */
    public function sendAlarm(Tinebase_Model_Alarm $_alarm) 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " About to send alarm " . print_r($_alarm->toArray(), TRUE));
        
        $doContainerACLChecks = $this->doContainerACLChecks(FALSE);

        try {
            $event = $this->get($_alarm->record_id);
            $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array($_alarm));
            $this->_inspectAlarmGet($event);
        } catch (Exception $e) {
            $this->doContainerACLChecks($doContainerACLChecks);
            throw($e);
        }

        $this->doContainerACLChecks($doContainerACLChecks);

        if ($event->rrule) {
            $recurid = $_alarm->getOption('recurid');
            
            // adopts the (referenced) alarm and sets alarm time to next occurance
            parent::_inspectAlarmSet($event, $_alarm);
            $this->adoptAlarmTime($event, $_alarm, 'instance');
            
            // sent_status might have changed in adoptAlarmTime()
            if ($_alarm->sent_status !== Tinebase_Model_Alarm::STATUS_PENDING) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Not sending alarm for event at ' . $event->dtstart->toString() . ' with status ' . $_alarm->sent_status);
                return;
            }
            
            if ($recurid) {
                // NOTE: In case of recuring events $event is always the baseEvent,
                //       so we might need to adopt event time to recur instance.
                $diff = $event->dtstart->diff($event->dtend);
                
                $event->dtstart = new Tinebase_DateTime(substr($recurid, -19));
                
                $event->dtend = clone $event->dtstart;
                $event->dtend->add($diff);
            }
            
            if ($event->exdate && in_array($event->dtstart, $event->exdate)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . " Not sending alarm because instance at " . $event->dtstart->toString() . ' is an exception.');
                return;
            }
        }
        
        Calendar_Controller_EventNotifications::getInstance()->doSendNotifications($event, Tinebase_Core::getUser(), 'alarm', NULL, $_alarm);
    }
    
    /**
     * send notifications 
     * 
     * @param Calendar_Model_Event       $_event
     * @param Tinebase_Model_FullAccount $_updater
     * @param Sting                      $_action
     * @param Calendar_Model_Event       $_oldEvent
     * @param Array                      $_additionalRecipients
     * @return void
     */
    public function doSendNotifications($_event, $_updater, $_action, $_oldEvent = NULL)
    {
        Tinebase_ActionQueue::getInstance()->queueAction('Calendar.sendEventNotifications', 
            $_event, 
            $_updater,
            $_action, 
            $_oldEvent ? $_oldEvent : NULL
        );
    }

    public function compareCalendars($cal1, $cal2, $from, $until)
    {
        $matchingEvents = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $changedEvents = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $missingEventsInCal1 = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $missingEventsInCal2 = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $cal2EventIdsAlreadyProcessed = array();
        
        while ($from->isEarlier($until)) {
    
            $endWeek = $from->getClone()->addWeek(1);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Comparing period ' . $from . ' - ' . $endWeek);
    
            // get all events from cal1+cal2 for the week
            $cal1Events = $this->_getEventsForPeriodAndCalendar($cal1, $from, $endWeek);
            $cal1EventsClone = clone $cal1Events;
            $cal2Events = $this->_getEventsForPeriodAndCalendar($cal2, $from, $endWeek);
            $cal2EventsClone = clone $cal2Events;
            
            $from->addWeek(1);
            if (count($cal1Events) == 0 && count($cal2Events) == 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' No events found');
                continue;
            }
    
            foreach ($cal1Events as $event) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Checking event "' . $event->summary . '" ' . $event->dtstart . ' - ' . $event->dtend);
                
                if ($event->container_id != $cal1) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Event is in another calendar - skip');
                    $cal1Events->removeRecord($event);
                    continue;
                }
                
                $summaryMatch = $cal2Events->filter('summary', $event->summary);
                if (count($summaryMatch) > 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . " Found " . count($summaryMatch) . ' events with matching summaries');
                    
                    $dtStartMatch = $summaryMatch->filter('dtstart', $event->dtstart);
                    if (count($dtStartMatch) > 0) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . " Found " . count($summaryMatch) . ' events with matching dtstarts and summaries');
                        
                        $matchingEvents->merge($dtStartMatch);
                        // remove from cal1+cal2
                        $cal1Events->removeRecord($event);
                        $cal2Events->removeRecords($dtStartMatch);
                        $cal2EventIdsAlreadyProcessed = array_merge($cal2EventIdsAlreadyProcessed, $dtStartMatch->getArrayOfIds());
                    } else {
                        $changedEvents->merge($summaryMatch);
                        $cal1Events->removeRecord($event);
                        $cal2Events->removeRecords($summaryMatch);
                        $cal2EventIdsAlreadyProcessed = array_merge($cal2EventIdsAlreadyProcessed, $summaryMatch->getArrayOfIds());
                    }
                }
            }
            
            // add missing events
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Found " . count($cal1Events) . ' events missing in cal2');
            $missingEventsInCal2->merge($cal1Events);
            
            // compare cal2 -> cal1 and add events as missing from cal1 that we did not detect before
            foreach ($cal2EventsClone as $event) {
                if (in_array($event->getId(), $cal2EventIdsAlreadyProcessed)) {
                    continue;
                }
                if ($event->container_id != $cal2) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Event is in another calendar - skip');
                    continue;
                }
                
                $missingEventsInCal1->addRecord($event);
            }
        }
        
        $result = array(
            'matching'      => $matchingEvents,
            'changed'       => $changedEvents,
            'missingInCal1' => $missingEventsInCal1,
            'missingInCal2' => $missingEventsInCal2,
        );
        return $result;
    }
    
    protected function _getEventsForPeriodAndCalendar($calendarId, $from, $until)
    {
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'period', 'operator' => 'within', 'value' =>
                array("from" => $from, "until" => $until)
            ),
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $calendarId),
        ));
    
        $events = Calendar_Controller_Event::getInstance()->search($filter);
        Calendar_Model_Rrule::mergeAndRemoveNonMatchingRecurrences($events, $filter);
        return $events;
    }
    
    /**
     * add calendar owner as attendee if not already set
     * 
     * @param string $calendarId
     * @param Tinebase_DateTime $from
     * @param Tinebase_DateTime $until
     * @param boolean $dry run
     * 
     * @return number of updated events
     */
    public function repairAttendee($calendarId, $from, $until, $dry = false)
    {
        $container = Tinebase_Container::getInstance()->getContainerById($calendarId);
        if ($container->type !== Tinebase_Model_Container::TYPE_PERSONAL) {
            throw new Calendar_Exception('Only allowed for personal containers!');
        }
        if ($container->owner_id !== Tinebase_Core::getUser()->getId()) {
            throw new Calendar_Exception('Only allowed for own containers!');
        }
        
        $updateCount = 0;
        while ($from->isEarlier($until)) {
            $endWeek = $from->getClone()->addWeek(1);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Repairing period ' . $from . ' - ' . $endWeek);
            
            
            // TODO we need to detect events with DECLINED/DELETED attendee
            $events = $this->_getEventsForPeriodAndCalendar($calendarId, $from, $endWeek);
            
            $from->addWeek(1);
            
            if (count($events) == 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' No events found');
                continue;
            }
            
            foreach ($events as $event) {
                // add attendee if not already set
                if ($event->isRecurInstance()) {
                    // TODO get base event
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Skip recur instance ' . $event->toShortString());
                    continue;
                }
                
                $ownAttender = Calendar_Model_Attender::getOwnAttender($event->attendee);
                if (! $ownAttender) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Add missing attender to event ' . $event->toShortString());
                    
                    $attender = new Calendar_Model_Attender(array(
                        'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                        'user_id'   => Tinebase_Core::getUser()->contact_id,
                        'status'    => Calendar_Model_Attender::STATUS_ACCEPTED
                    ));
                    $event->attendee->addRecord($attender);
                    if (! $dry) {
                        $this->update($event);
                    }
                    $updateCount++;
                }
            }
        }
        
        return $updateCount;
    }
}
