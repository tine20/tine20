<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Calendar Event Controller
 * 
 * @todo add handling to fetch all exceptions of a given event set (ActiveSync Frontend)
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
     * - the controller has to define a sendNotifications() function
     *
     * @var boolean
     */
    protected $_sendNotifications = TRUE;
    
    /**
     * @var Calendar_Controller_Event
     */
    private static $_instance = NULL;
    
    /**
     * @var Tinebase_Model_User
     */
    protected $_currentAccount = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Calendar';
        $this->_modelName       = 'Calendar_Model_Event';
        $this->_backend         = new Calendar_Backend_Sql();
        $this->_currentAccount  = Tinebase_Core::getUser();
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
     * checks if all attendee of given event are not busy for given event
     * 
     * @param Calendar_Model_Event $_event
     * @return void
     * @throws 
     */
    public function checkBusyConficts($_event)
    {
    	$ignoreUIDs = !empty($_event->uid) ? array($_event->uid) : array();
    	
    	// don't check if event is trasparent
    	if ($_event->transp == Calendar_Model_Event::TRANSP_TRANSP) {
    	    return;
    	}
    	
        $fbInfo = $this->getFreeBusyInfo($_event->dtstart, $_event->dtend, $_event->attendee, $ignoreUIDs);
        //Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') value: ' . print_r($fbInfo->toArray(), true));
        
        if (count($fbInfo) > 0) {
            $busyException = new Calendar_Exception_AttendeeBusy();
            $busyException->setFreeBusyInfo($fbInfo);
            throw $busyException;
        }
    }
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   bool                      $_checkBusyConficts
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_record, $_checkBusyConficts = FALSE)
    {
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            // we need to resolve groupmembers before free/busy checking
            Calendar_Model_Attender::resolveGroupMembers($_record->attendee);
            
            if ($_checkBusyConficts) {
                // ensure that all attendee are free
                $this->checkBusyConficts($_record);
            }
            
            $sendNotifications = $this->_sendNotifications;
            $this->_sendNotifications = FALSE;
                
            $event = parent::create($_record);
            $this->_saveAttendee($_record);
            
            $this->_sendNotifications = $sendNotifications;
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        $createdEvent = $this->get($event->getId());
        
        // send notifications
        if ($this->_sendNotifications) {
            $this->sendNotifications($createdEvent, $this->_currentAccount, 'created');
        }
            
        return $createdEvent;
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
     * @param  Zend_Date                                            $_from
     * @param  Zend_Date                                            $_until
     * @param  Tinebase_Record_RecordSet of Calendar_Model_Attender $_attendee
     * @param  array of UIDs                                        $_ignoreUIDs
     * @return Tinebase_Record_RecordSet of Calendar_Model_FreeBusy
     */
    public function getFreeBusyInfo($_from, $_until, $_attendee, $_ignoreUIDs = array())
    {
        // map groupmembers to users
        $attendee = clone $_attendee;
        $groupmembers = $attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER);
        $groupmembers->user_type = Calendar_Model_Attender::USERTYPE_USER;
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'period',   'operator' => 'within', 'value' => array('from' => $_from, 'until' => $_until)),
            array('field' => 'attender', 'operator' => 'in',     'value' => $_attendee),
            array('field' => 'transp',   'operator' => 'equals', 'value' => Calendar_Model_Event::TRANSP_OPAQUE)
        ));
        
        $events = $this->search($filter, new Tinebase_Model_Pagination(), FALSE, FALSE);
        Calendar_Model_Rrule::mergeRecuranceSet($events, $_from, $_until);
        //Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') value: ' . print_r($events->toArray(), true));
        
        // create a typemap
        $typeMap = array();
        foreach($attendee as $attender) {
            if (! array_key_exists($attender['user_type'], $typeMap)) {
                $typeMap[$attender['user_type']] = array();
            }
            
            $typeMap[$attender['user_type']][$attender['user_id']] = array();
        }
        //Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') value: ' . print_r($typeMap, true));
        
        // sort freebusy info into tyepmap
        foreach($events as $event) {
        	// skip events with ignoreUID
        	if (in_array($event->uid, $_ignoreUIDs)) {
        	    continue;
        	}
        	
        	// skip recuring base events
        	if ($event->dtend->isEarlier($_from)) {
        	    continue;
        	}
        	
        	// map groupmembers to users
        	$groupmembers = $event->attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER);
            $groupmembers->user_type = Calendar_Model_Attender::USERTYPE_USER;
        
            foreach ($event->attendee as $attender) {
            	// skip declined events
                if ($attender->status == Calendar_Model_Attender::STATUS_DECLINED) {
                    continue;
                }
                
                if (array_key_exists($attender->user_type, $typeMap) && array_key_exists($attender->user_id, $typeMap[$attender->user_type])) {
                    $typeMap[$attender->user_type][$attender->user_id][] = new Calendar_Model_FreeBusy(array(
                        'user_type' => $attender->user_type,
				        'user_id'   => $attender->user_id,
				        'dtstart'   => clone $event->dtstart,
				        'dtend'     => clone $event->dtend,
				        'type'      => Calendar_Model_FreeBusy::FREEBUSY_BUSY
                    ), true);
                }
            }
        }
        //Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') value: ' . print_r($typeMap, true));
        
        $resultArray = array();
        foreach ($typeMap as $type => $typeEntries) {
            foreach ($typeEntries as $id => $fbslice) {
                $resultArray = array_merge($resultArray, $fbslice);
            }
        }
        //Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') value: ' . print_r($resultArray, true));
        
        return new Tinebase_Record_RecordSet('Calendar_Model_FreeBusy', $resultArray);
    }
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     * 
     * @todo create filter group if it is NULL to make sure that acl filter is applied correctly ?
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        $events = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds);
        
        if (! $_onlyIds) {
        	$events->doFreeBusyCleanup();
        }
        
        return $events;
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   bool                      $_checkBusyConficts
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_record, $_checkBusyConficts = FALSE)
    {
    	try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
	        $event = $this->get($_record->getId());
	        if ($event->editGrant) {
	            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " updating event: {$_record->id} ");
		        
	            // we need to resolve groupmembers before free/busy checking
	            Calendar_Model_Attender::resolveGroupMembers($_record->attendee);
		        
	            if ($_checkBusyConficts) {
	                // only do free/busy check if start/endtime changed
	                if (! $event->dtstart->equals($_record->dtstart) || ! $event->dtend->equals($_record->dtend)) {
    	                // ensure that all attendee are free
    	                $this->checkBusyConficts($_record);
	                }
	            }
                
	            $sendNotifications = $this->_sendNotifications;
	            $this->_sendNotifications = FALSE;
	            
                parent::update($_record);
                $this->_saveAttendee($_record);
                
                $this->_sendNotifications = $sendNotifications;
                
            } else if ($_record->attendee instanceof Tinebase_Record_RecordSet) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " user has no editGrant for event: {$_record->id}, updating attendee status with valid authKey only");
                foreach ($_record->attendee as $attender) {
                    if ($attender->status_authkey) {
                        $this->attenderStatusUpdate($event, $attender, $attender->status_authkey);
                    }
                }
            }
            
    	    Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        $updatedEvent = $this->get($event->getId());
        
        // send notifications
        if ($this->_sendNotifications) {
            $this->sendNotifications($updatedEvent, $this->_currentAccount, 'changed', $event);
        }
            
        return $updatedEvent;
    }
    
    /**
     * updates a recur series
     *
     * @param  Calendar_Model_Event $_recurInstance
     * @param  bool                 $_checkBusyConficts
     * @return Calendar_Model_Event
     */
    public function updateRecurSeries($_recurInstance, $_checkBusyConficts = FALSE)
    {
        $baseEvent = $this->getRecurBaseEvent($_recurInstance);
        
        // compute time diff
        $instancesOriginalDtStart = new Zend_Date(substr($_recurInstance->recurid, -19), Tinebase_Record_Abstract::ISO8601LONG);
        $dtstartDiff = clone $_recurInstance->dtstart;
        $dtstartDiff->sub($instancesOriginalDtStart);
        
        $instancesEventDuration = clone $_recurInstance->dtend;
        $instancesEventDuration->sub($_recurInstance->dtstart);
        
        // replace baseEvent with adopted instance
        $newBaseEvent = clone $_recurInstance;
        
        $newBaseEvent->setId($baseEvent->getId());
        unset($newBaseEvent->recurid);
        
        $newBaseEvent->dtstart     = clone $baseEvent->dtstart;
        $newBaseEvent->dtstart->add($dtstartDiff);
        
        $newBaseEvent->dtend       = clone $newBaseEvent->dtstart;
        $newBaseEvent->dtend->add($instancesEventDuration);
        
        $newBaseEvent->exdate      = $baseEvent->exdate;
        
        return $this->update($newBaseEvent, $_checkBusyConficts);
    }
    
    /**
     * creates an exception instance of a recurring event
     *
     * NOTE: deleting persistent exceptions is done via a normal delte action
     *       and handled in the delteInspection
     * 
     * @param  Calendar_Model_Event  $_event
     * @param  bool                  $_deleteInstance
     * @param  bool                  $_deleteAllFollowing (technically croppes rrule_until)
     * @param  bool                  $_checkBusyConficts
     * @return Calendar_Model_Event  exception Event | updated baseEvent
     */
    public function createRecurException($_event, $_deleteInstance = FALSE, $_deleteAllFollowing = FALSE, $_checkBusyConficts = FALSE)
    {
        // NOTE: recurid is computed by rrule recur computations and therefore is already part of the event.
        if (empty($_event->recurid)) {
            throw new Exception('recurid must be present to create exceptions!');
        }
        
        $baseEvent = $this->getRecurBaseEvent($_event);
        
        if ($this->_doContainerACLChecks && !$baseEvent->editGrant) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " user has no editGrant for event: '{$baseEvent->getId()}'. Only creating exception for attendee status");
            if ($_event->attendee instanceof Tinebase_Record_RecordSet) {
                foreach ($_event->attendee as $attender) {
                    if ($attender->status_authkey) {
                        $exceptionAttender = $this->attenderStatusCreateRecurException($_event, $attender, $attender->status_authkey);
                    }
                }
            }
            
            return $this->get($exceptionAttender->cal_event_id);
        }
        
        if (! $_deleteInstance) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " creating persistent exception for: '{$_event->recurid}'");
            
            $_event->setId(NULL);
            unset($_event->rrule);
            unset($_event->exdate);
            
            if ($_event->attendee instanceof Tinebase_Record_RecordSet) {
                $_event->attendee->setId(NULL);
            }
            
            if ($_event->notes instanceof Tinebase_Record_RecordSet) {
                $_event->notes->setId(NULL);
            }
            
            if ($_event->alarms instanceof Tinebase_Record_RecordSet) {
                $_event->alarms->setId(NULL);
            }
            
            // mhh how to preserv the attendee status stuff
            $event = $this->create($_event, $_checkBusyConficts);
            
            // we need to touch the recur base event, so that sync action find the updates
            $this->update($baseEvent, FALSE);
            
            return $event;
            
        } else {
            $exdate = new Zend_Date(substr($_event->recurid, -19), Tinebase_Record_Abstract::ISO8601LONG);
            
            if ($_deleteAllFollowing) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " shorten rrule_until for: '{$_event->recurid}'");
                
                $rrule = Calendar_Model_Rrule::getRruleFromString($baseEvent->rrule);
                $rrule->until = $exdate->addDay(-1);
                
                $baseEvent->rrule = (string) $rrule;
            } else {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " deleting recur instance: '{$_event->recurid}'");
                
                if (is_array($baseEvent->exdate)) {
                    $exdates = $baseEvent->exdate;
                    array_push($exdates, $exdate);
                    $baseEvent->exdate = $exdates;
                } else {
                    $baseEvent->exdate = array($exdate);
                }
            }
            
            $sendNotifications = $this->_sendNotifications;
            $this->_sendNotifications = FALSE;
                
            $updatedEvent = $this->update($baseEvent, FALSE);
            
            $this->_sendNotifications = $sendNotifications;
            
            // send notifications
            if ($this->_sendNotifications) {
                // NOTE: recur exception is a fake event from client. 
                //       this might lead to problems, so we wrap the calls
                try {
                    $_event->attendee->bypassFilters = TRUE;
                    $_event->created_by = $baseEvent->created_by;
                    
                    $this->sendNotifications($_event, $this->_currentAccount, 'deleted');
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " could not send notification {$e->getMessage()}");
                }
            }
            
            return $updatedEvent;
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
        $baseEventId = array_value(0, $this->_backend->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $_event->uid),
            array('field' => 'recurid', 'operator' => 'isnull', 'value' => NULL)
        )), NULL, TRUE));
        
        // make shure we have a 'fully featured' event
        return $this->get($baseEventId);
    }
    
    /**
     * returns all persistent recur exceptions of recur series identified by uid of given event
     * 
     * NOTE: deleted instances are saved in the base events exception property
     * 
     * @param  Calendar_Model_Event $_event
     * @return Tinebase_Record_RecordSet of Calendar_Model_Event
     */
    public function getRecurExceptions($_event)
    {
        return $this->_backend->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals',  'value' => $_event->uid),
            array('field' => 'recurid', 'operator' => 'notnull', 'value' => NULL)
        )));
    }
    
    /****************************** overwritten functions ************************/
    
    /**
     * inspect alarm and set time
     * 
     * @param Tinebase_Record_Abstract $_record
     * @return void
     */
    protected function _inspectAlarmGet(Tinebase_Record_Abstract $_record)
    {
        foreach ($_record->alarms as $alarm) {
            $options = Zend_Json::decode($alarm->options);
            if (is_array($options) && array_key_exists('minutes_before', $options)) {
                $alarm->minutes_before = $options['minutes_before'];
            } else {
                $alarm->setMinutesBefore($_record->{$this->_recordAlarmField});
            }
        }
    }
    
    /**
     * inspect alarm and set time
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param Tinebase_Model_Alarm $_alarm
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _inspectAlarmSet(Tinebase_Record_Abstract $_record, Tinebase_Model_Alarm $_alarm)
    {
        if ($_record->rrule) {
            $exceptions = $this->getRecurExceptions($_record);
            $nextOccurrence = Calendar_Model_Rrule::computeNextOccurrence($_record, $exceptions, Zend_Date::now());
            if (! $nextOccurrence) {
                $_alarm->sent_status = Tinebase_Model_Alarm::STATUS_SUCCESS;
                $_alarm->sent_message = 'Nothing to send, series is over';
                return;
            }
            $eventStart = clone $nextOccurrence->dtstart;
        } else {
            $eventStart = clone $_record->dtstart;
        }
        
        $_alarm->setTime($eventStart);
        $_alarm->options = Zend_Json::encode(array(
            'minutes_before' => $_alarm->minutes_before,
            'recurid'        => $_record->recurid
        ));
    }
    
    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectCreate(Tinebase_Record_Interface $_record)
    {
        $_record->uid = $_record->uid ? $_record->uid : Tinebase_Record_Abstract::generateUID();
        $_record->originator_tz = $_record->originator_tz ? $_record->originator_tz : Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
    }
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectUpdate($_record, $_oldRecord)
    {
        // if dtstart of an event changes, we update the originator_tz and alarm times
        if (! $_oldRecord->dtstart->equals($_record->dtstart)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' dtstart changed -> adopting organizer_tz');
            $_record->originator_tz = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
            
            // update exdates and recurids if dtsart of an recurevent changes
            if (! empty($_record->rrule)) {
                $diff = clone $_record->dtstart;
                $diff->sub($_oldRecord->dtstart);
                
                // update rrule->until
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' dtstart of a series changed -> adopting rrule_until');
                
                
                $rrule = $_record->rrule instanceof Calendar_Model_Rrule ? $_record->rrule : Calendar_Model_Rrule::getRruleFromString($_record->rrule);
                if ($rrule->until instanceof Zend_Date) {
                    Calendar_Model_Rrule::addUTCDateDstFix($rrule->until, $diff, $_record->originator_tz);
                    $_record->rrule = (string) $rrule;
                }
                
                // update exdate(s)
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' dtstart of a series changed -> adopting '. count($_record->exdate) . ' exdate(s)');
                foreach ((array)$_record->exdate as $exdate) {
                    Calendar_Model_Rrule::addUTCDateDstFix($exdate, $diff, $_record->originator_tz);
                }
                
                // update exceptions
                $exceptions = $this->_backend->getMultipleByProperty($_record->uid, 'uid');
                unset($exceptions[$exceptions->getIndexById($_record->getId())]);
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' dtstart of a series changed -> adopting '. count($exceptions) . ' recurid(s)');
                foreach ($exceptions as $exception) {
                    $originalDtstart = new Zend_Date(substr($exception->recurid, -19), Tinebase_Record_Abstract::ISO8601LONG);
                    Calendar_Model_Rrule::addUTCDateDstFix($originalDtstart, $diff, $_record->originator_tz);
                    
                    $exception->recurid = $exception->uid . '-' . $originalDtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
                    $this->_backend->update($exception);
                }
            }
            
            //$this->_updateAlarms($_record);
        }
        
        // delete recur exceptions if update is not longer a recur series
        if (! empty($_oldRecord->rrule) && empty($_record->rrule)) {
            $exceptionIds = $this->_backend->getMultipleByProperty($_record->uid, 'uid')->getId();
            unset($exceptionIds[array_search($_record->getId(), $exceptionIds)]);
            $this->_backend->delete($exceptionIds);
        }
        
        // touch base event of a recur series if an persisten exception changes
        if ($_record->recurid) {
            $baseEvent = $this->getRecurBaseEvent($_record);
            $this->_touch($baseEvent, TRUE);
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
                $exceptionIds = $this->_backend->getMultipleByProperty($event->uid, 'uid')->getId();
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Implicitly deleting ' . (count($exceptionIds) - 1 ) . ' persistent exception(s) for recurring series with uid' . $event->uid);
                $_ids = array_merge($_ids, $exceptionIds);
            }
            
            // deleted persistent recur instances must be added to exdate of the baseEvent
            if (! empty($event->recurid)) {
                $this->createRecurException($event, true);
            }
        }
        
        $this->_deleteAlarmsForIds($_ids);
        
        return array_unique($_ids);
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
        if (    !$this->_doContainerACLChecks 
            // admin grant includes all others
            ||  ($_record->container_id && $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADMIN))) {
            return TRUE;
        }

        switch ($_action) {
            case 'get':
                // NOTE: free/busy is not a read grant!
                $hasGrant = (bool) $_record->readGrant;
                if (! $hasGrant) {
                	$_record->doFreeBusyCleanup();
                }
                break;
            case 'create':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADD);
                break;
            case 'update':
                $hasGrant = (bool) $_record->editGrant;
                break;
            case 'delete':
                $hasGrant = (bool) $_record->deleteGrant;
                break;
        }
        
        if (!$hasGrant) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            } else {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 'No permissions to ' . $_action . ' in container ' . $_record->container_id);
            }
        }
        
        return $hasGrant;
    }
    
    /**
     * touches (sets seq and last_modified_time) given event
     * 
     * @param  $_event
     * @return void
     */
    protected function _touch($_event, $_setModifier = FALSE) {
        $_event->last_modified_time = Zend_Date::now();
        //$_event->last_modified_time = Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
        $_event->seq = (int)$_event->seq + 1;
        if ($_setModifier) {
            $_event->last_modified_by = Tinebase_Core::getUser()->getId();
        }
        
        
        $this->_backend->update($_event);
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
     * @return Calendar_Model_Attender updated attender
     */
    public function attenderStatusCreateRecurException($_recurInstance, $_attender, $_authKey)
    {
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $baseEvent = $this->getRecurBaseEvent($_recurInstance);
            
            // check authkey on series
            $attender = $baseEvent->attendee->filter('status_authkey', $_authKey)->getFirstRecord();
            if ($attender->user_type != $_attender->user_type || $attender->user_id != $_attender->user_id) {
                throw new Tinebase_Exception_AccessDenied('Attender authkey mismatch');
            }
            
            // NOTE: recurid is computed by rrule recur computations and therefore is already part of the event.
            if (empty($_recurInstance->recurid)) {
                throw new Exception('recurid must be present to create exceptions!');
            }
            
            // check if this intance takes place
            if (in_array($_recurInstance->recurid, (array)$baseEvent->exdate)) {
                throw new Tinebase_Exception_AccessDenied('Event instance is deleted and may not be recreated via status setting!');
            }
            
            try {
                // check if we already have a persistent exception for this event
                $eventInsance = $this->_backend->getByProperty($_recurInstance->recurid, $_property = 'recurid');
            } catch (Exception $e) {
                // otherwise create it implicilty
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " creating recur exception for a exceptional attendee status");
                $this->_doContainerACLChecks = FALSE;
                // NOTE: the user might have no edit grants, so let's be carefull
                $diff = clone $baseEvent->dtend;
                $diff->sub($baseEvent->dtstart);
                
                $baseEvent->dtstart = new Zend_Date(substr($_recurInstance->recurid, -19), Tinebase_Record_Abstract::ISO8601LONG);
                $baseEvent->dtend   = clone $baseEvent->dtstart;
                $baseEvent->dtend->add($diff);
                
                $baseEvent->recurid = $_recurInstance->recurid;
                
                $attendee = $baseEvent->attendee;
                unset($baseEvent->attendee);
                
                $eventInsance = $this->createRecurException($baseEvent);
                $eventInsance->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
                $this->_doContainerACLChecks = TRUE;
                
                foreach ($attendee as $attender) {
                    $attender->setId(NULL);
                    $attender->cal_event_id = $eventInsance->getId();
                    
                    $attender = $this->_backend->createAttendee($attender);
                    $eventInsance->attendee->addRecord($attender);
                }
            }
            
            // set attender to the newly created exception attender
            $exceptionAttender = $eventInsance->attendee->filter('status_authkey', $_authKey)->getFirstRecord();
            $exceptionAttender->status = $_attender->status;
            
            $updatedAttender = $this->attenderStatusUpdate($eventInsance, $exceptionAttender, $exceptionAttender->status_authkey);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        return $updatedAttender;
    }
    
    /**
     * updates an attender status for a complete recurring event series
     * 
     * @param  Calendar_Model_Event    $_recurInstance
     * @param  Calendar_Model_Attender $_attender
     * @param  string                  $_authKey
     * @return Calendar_Model_Attender updated attender
     */
    public function attenderStatusUpdateRecurSeries($_recurInstance, $_attender, $_authKey)
    {
        $baseEvent = $this->getRecurBaseEvent($_recurInstance);
        
        return $this->attenderStatusUpdate($baseEvent, $_attender, $_authKey);
    }
    
    /**
     * updates an attender status of a event
     * 
     * @param  Calendar_Model_Event    $_event
     * @param  Calendar_Model_Attender $_attender
     * @param  string                  $_authKey
     * @return Calendar_Model_Attender updated attender
     */
    public function attenderStatusUpdate($_event, $_attender, $_authKey)
    {
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $event = $this->get($_event->getId());
            
            $currentAttender                      = $event->attendee[$event->attendee->getIndexById($_attender->getId())];
            $currentAttender->status              = $_attender->status;
            $currentAttender->displaycontainer_id = $_attender->displaycontainer_id;
            
            if ($currentAttender->status_authkey == $_authKey) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " update attender status for {$currentAttender->user_type} {$currentAttender->user_id}");
                $updatedAttender = $this->_backend->updateAttendee($currentAttender);
                
                $this->_touch($event, TRUE);
            } else {
                $updatedAttender = $currentAttender;
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " no permissions to update status for {$currentAttender->user_type} {$currentAttender->user_id}");
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
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
     */
    protected function _saveAttendee($_event)
    {
        $attendee = $_event->attendee instanceof Tinebase_Record_RecordSet ? 
            $_event->attendee : 
            new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        $attendee->cal_event_id = $_event->getId();
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " About to save attendee for event {$_event->id} ");
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " About to save attendee for event {$_event->id} " .  print_r($attendee->toArray(), true));
        
        $currentEvent = $this->get($_event->getId());
        $currentAttendee = $currentEvent->attendee;
        //$currentAttendee = $this->_backend->getEventAttendee($_event);
        
        $diff = $currentAttendee->getMigration($attendee->getArrayOfIds());
        $this->_backend->deleteAttendee($diff['toDeleteIds']);
        
        $calendar = Tinebase_Container::getInstance()->getContainerById($_event->container_id);
        
        foreach ($attendee as $attender) {
            $attenderId = $attender->getId();
            
            if ($attenderId) {
                $currentAttender = $currentAttendee[$currentAttendee->getIndexById($attenderId)];
                
                $this->_updateAttender($attender, $currentAttender, $calendar);
                
            } else {
                $this->_createAttender($attender, $calendar);
            }
        }
    }

    /**
     * creates a new attender
     * @todo add support for resources
     * 
     * @param Calendar_Model_Attender  $_attender
     * @param Tinebase_Model_Container $_calendar
     */
    protected function _createAttender($_attender, $_calendar) {
        
        // apply default user_type
        $_attender->user_type = $_attender->user_type ?  $_attender->user_type : Calendar_Model_Attender::USERTYPE_USER;
        
        $userAccountId = $_attender->getUserAccountId();
        
        // reset status if attender != currentuser        
        if ($_attender->user_type == Calendar_Model_Attender::USERTYPE_GROUP
                || $userAccountId != Tinebase_Core::getUser()->getId()) {

            $_attender->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        }
        
        // generate auth key
        $_attender->status_authkey = Tinebase_Record_Abstract::generateUID();
        
        // attach to display calendar if attender has/is a useraccount
        if ($userAccountId) {
            if ($_calendar->type == Tinebase_Model_Container::TYPE_PERSONAL && Tinebase_Container::getInstance()->hasGrant($userAccountId, $_calendar, Tinebase_Model_Container::GRANT_ADMIN)) {
                // if attender has admin grant to personal phisycal container, this phys. cal also gets displ. cal
                $_attender->displaycontainer_id = $_calendar->getId();
            } else if ($_attender->displaycontainer_id && $userAccountId == Tinebase_Core::getUser()->getId() && Tinebase_Container::getInstance()->hasGrant($userAccountId, $_attender->displaycontainer_id, Tinebase_Model_Container::GRANT_ADMIN)) {
                // allow user to set his own displ. cal
                $_attender->displaycontainer_id = $_attender->displaycontainer_id;
            } else {
                $displayCalId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $userAccountId);
                if ($displayCalId) {
                    $_attender->displaycontainer_id = $displayCalId;
                }
            }
        }
        
        $this->_backend->createAttendee($_attender);
    }
    
    /**
     * updates an attender
     * 
     * @param Calendar_Model_Attender  $_attender
     * @param Calendar_Model_Attender  $_currentAttender
     * @param Tinebase_Model_Container $_calendar
     */
    protected function _updateAttender($_attender, $_currentAttender, $_calendar) {
        
    	$userAccountId = $_currentAttender->getUserAccountId();
    	
        // reset status if attender != currentuser and wrong authkey
        if ($_attender->user_type == Calendar_Model_Attender::USERTYPE_GROUP
                || $userAccountId != Tinebase_Core::getUser()->getId()) {
            
            if ($_attender->status_authkey != $_currentAttender->status_authkey) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " wrong authkey -> resetting status ");
                $_attender->status = $_currentAttender->status;
            }
        }
        
        // preserv old authkey
        $_attender->status_authkey = $_currentAttender->status_authkey;
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " updating attender: " . print_r($_attender->toArray(), TRUE));
        
        // update display calendar if attender has/is a useraccount
        if ($userAccountId) {
            if ($_calendar->type == Tinebase_Model_Container::TYPE_PERSONAL && Tinebase_Container::getInstance()->hasGrant($userAccountId, $_calendar, Tinebase_Model_Container::GRANT_ADMIN)) {
                // if attender has admin grant to personal physical container, this phys. cal also gets displ. cal
                $_attender->displaycontainer_id = $_calendar->getId();
            } else if ($userAccountId == Tinebase_Core::getUser()->getId() && Tinebase_Container::getInstance()->hasGrant($userAccountId, $_attender->displaycontainer_id, Tinebase_Model_Container::GRANT_ADMIN)) {
                // allow user to set his own displ. cal
                $_attender->displaycontainer_id = $_attender->displaycontainer_id;
            } else {
                $_attender->displaycontainer_id = $_currentAttender->displaycontainer_id;
            }
        }
        
        $this->_backend->updateAttendee($_attender);
    }
    
    /**
     * event handler for group updates
     * 
     * @param Tinebase_Model_Group $_group
     * @return void
     */
    public function onUpdateGroup($_groupId)
    {
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'attender', 'operator' => 'equals', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
                'user_id'   => $_groupId
            ))
        ));
        
        $doContainerACLChecks = $this->_doContainerACLChecks;
        $this->_doContainerACLChecks = FALSE;
        
        $events = $this->search($filter, new Tinebase_Model_Pagination(), FALSE, FALSE);
        //Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') updated group ' . $events);
        
        foreach($events as $event) {
            Calendar_Model_Attender::resolveGroupMembers($event->attendee);
            $this->update($event, FALSE);
        }
        
        $this->_doContainerACLChecks = $doContainerACLChecks;
    }
    
    /****************************** alarm functions ************************/
    
    /**
     * send an alarm
     *
     * @param  Tinebase_Model_Alarm $_alarm
     * @return void
     * 
     * @todo make this working with recuring events
     * @todo throw exception on error
     */
    public function sendAlarm(Tinebase_Model_Alarm $_alarm) 
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " About to send alarm " . print_r($_alarm->toArray(), TRUE));
        
        $doContainerACLChecks = $this->_doContainerACLChecks;
        $this->_doContainerACLChecks = FALSE;
        
        $event = $this->get($_alarm->record_id);
        $this->_doContainerACLChecks = $doContainerACLChecks;
        
        $this->sendNotifications($event, $this->_currentAccount, 'alarm');
        
        //if ($event->rrule)
    }
    
    /**
     * send notifications 
     * 
     * @param Calendar_Model_Event       $_event
     * @param Tinebase_Model_FullAccount $_updater
     * @param Sting                      $_action
     * @param Calendar_Model_Event       $_oldEvent
     * @return void
     */
    public function sendNotifications($_event, $_updater, $_action, $_oldEvent=NULL)
    {
        Tinebase_ActionQueue::getInstance()->queueAction('Calendar.sendEventNotifications', 
            $_event, 
            $_updater,
            $_action, 
            $_oldEvent ? $_oldEvent : NULL
        );
    }
}
