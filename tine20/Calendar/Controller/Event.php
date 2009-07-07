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
 * @package Calendar
 */
class Calendar_Controller_Event extends Tinebase_Controller_Record_Abstract implements Tinebase_Controller_Alarm_Interface
{
    // todo in this controller:
    //
    // add free time search
    // add group attendee handling
    // add handling to fetch all exceptions of a given event set (ActiveSync Frontend)
    
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
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $event = parent::create($_record);
        
        $this->_saveAttendee($_record);
        $this->_saveAlarms($_record);
        
        return $this->get($event->getId());
    }
    
    /**
     * deletes a recur series
     *
     * @param  Calendar_Model_Event $_recurInstance
     * @return void
     */
    public function deleteRecurSeries($_recurInstance)
    {
        $baseEvent = $this->_getRecurBaseEvent($_recurInstance);
        $this->delete($baseEvent->getId());
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $event = $this->get($_record->getId());
        if ($event->editGrant) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "updating event: {$_record->id} ");
            $event = parent::update($_record);
            
            $this->_saveAttendee($_record);
            $this->_saveAlarms($_record);
        } else if ($_record->attendee instanceof Tinebase_Record_RecordSet) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " user has no editGrant for event: {$_record->id}, updating attendee status with valid authKey only");
            foreach ($_record->attendee as $attender) {
                if ($attender->status_authkey) {
                    $this->attenderStatusUpdate($event, $attender, $attender->status_authkey);
                }
            }
        }
        
        return $this->get($event->getId());
    }
    
    /**
     * updates a recur series
     *
     * @param Calendar_Model_Event $_recurInstance
     * @return Calendar_Model_Event
     */
    public function updateRecurSeries($_recurInstance)
    {
        $baseEvent = $this->_getRecurBaseEvent($_recurInstance);
        
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
        
        $newBaseEvent->rrule       = $baseEvent->rrule;
        $newBaseEvent->exdate      = $baseEvent->exdate;
        
        return $this->update($newBaseEvent);
    }
    
    /**
     * creates an exception instance of a recuring evnet
     *
     * NOTE: deleting persistent exceptions is done via a normal delte action
     *       and handled in the delteInspection
     * 
     * @param  Calendar_Model_Event  $_event
     * @param  bool                  $_deleteInstance
     * @param  bool                  $_deleteAllFollowing (technically croppes rrule_until)
     * @return Calendar_Model_Event  exception Event | updated baseEvent
     */
    public function createRecurException($_event, $_deleteInstance = FALSE, $_deleteAllFollowing = FALSE)
    {
        // NOTE: recurid is computed by rrule recur computations and therefore is already part of the event.
        if (empty($_event->recurid)) {
            throw new Exception('recurid must be present to create exceptions!');
        }
        
        $baseEvent = $this->_getRecurBaseEvent($_event);
        
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
            
            // we need to touch the recur base event, so that sync action find the updates
            $this->_backend->update($baseEvent);
            
            // mhh how to preserv the attendee status stuff
            return $this->create($_event);
            
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
            
            return $this->update($baseEvent);
        }
    }
    
    /**
     * returns base event of a recuring series
     *
     * @param  Calendar_Model_Event $_event
     * @return Calendar_Model_Event
     */
    protected function _getRecurBaseEvent($_event)
    {
        return $this->_backend->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $_event->uid),
            array('field' => 'recurid', 'operator' => 'isnull', 'value' => NULL)
        )))->getFirstRecord();
    }
    
    /****************************** overwritten functions ************************/
    
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
        // if dtstart of an event changes, we update the originator_tz
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
        }
        
        // delete recur exceptions if update is not longer a recur series
        if (! empty($_oldRecord->rrule) && empty($_record->rrule)) {
            $exceptionIds = $this->_backend->getMultipleByProperty($_record->uid, 'uid')->getId();
            unset($exceptionIds[array_search($_record->getId(), $exceptionIds)]);
            $this->_backend->delete($exceptionIds);
        }
        
        // touch base event of a recur series if an persisten exception changes
        if ($_record->recurid) {
            $baseEvent = $this->_getRecurBaseEvent($_record);
            $this->_backend->update($baseEvent);
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
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Implicitly deleting ' . (count($exceptionIds) - 1 ) . ' persistent exception(s) for recuring series with uid' . $event->uid);
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
            ||  $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADMIN)) {
            return TRUE;
        }

        switch ($_action) {
            case 'get':
                // NOTE: free/busy is not a read grant!
                $hasGrant = (bool) $_record->readGrant;
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
    
    /****************************** attendee functions ************************/
    
    /**
     * sets attendee status for an attendee on the given event
     * 
     * NOTE: for recur events we implicitly create an exceptions on demand
     *
     * @param  Calendar_Model_Event    $_event
     * @param  Calendar_Model_Attender $_attender
     * @param  string                  $_authKey
     * @return Calendar_Model_Attender updated attender
     *
    public function setAttenderStatus($_event, $_attender, $_authKey)
    {
        $eventId = $_event->getId();
        if (! $eventId) {
            if ($_event->recurid) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " creating recur exception for a exceptional attendee status");
                $this->_doContainerACLChecks = FALSE;
                $event = $this->createRecurException($_event);
                $this->_doContainerACLChecks = TRUE;
            } else {
                throw new Exception("cant set status, invalid event given");
            }
        } else {
            $event = $this->get($eventId);
        }
        
        $currentAttender = $event->attendee[$event->attendee->getIndexById($_attender->getId())];
        $currentAttender->status = $_attender->status;
        
        if ($currentAttender->status_authkey == $_authKey) {
            $updatedAttender = $this->_backend->updateAttendee($currentAttender);
            
            // touch event
            $event = $_event->recurid ? $this->_getRecurBaseEvent($_event) : $this->_backend->get($_event->getId());
            $this->_backend->update($event);
        } else {
            $updatedAttender = $currentAttender;
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " no permissions to update status for {$currentAttender->user_type} {$currentAttender->user_id}");
        }
        
        return $updatedAttender;
    }*/
    
    /**
     * creates an attender status exception of a recuring event series
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
            
            $baseEvent = $this->_getRecurBaseEvent($_recurInstance);
            
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
     * updates an attender status for a complete recuring event series
     * 
     * @param  Calendar_Model_Event    $_recurInstance
     * @param  Calendar_Model_Attender $_attender
     * @param  string                  $_authKey
     * @return Calendar_Model_Attender updated attender
     */
    public function attenderStatusUpdateRecurSeries($_recurInstance, $_attender, $_authKey)
    {
        $baseEvent = $this->_getRecurBaseEvent($_recurInstance);
        
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
                
                // touch event
                $event->last_modified_time = Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
                $event->last_modified_by = Tinebase_Core::getUser()->getId();
                $event->seq = (int)$_event->seq + 1;
                
                $this->_backend->update($event);
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " About to save attendee for event {$_event->id} " .  print_r($attendee->toArray(), true));
        
        $currentAttendee = $this->_backend->getEventAttendee($_event);
        
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
        
        // reset status if user != attender        
        if ($_attender->user_type == Calendar_Model_Attender::USERTYPE_GROUP
                || $_attender->user_id != Tinebase_Core::getUser()->getId()) {

            $_attender->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        }
        
        // generate auth key
        $_attender->status_authkey = Tinebase_Record_Abstract::generateUID();
        
        // attach to display calendar
        if ($_attender->user_type == Calendar_Model_Attender::USERTYPE_USER || $_attender->user_type == Calendar_Model_Attender::USERTYPE_GROUPMEMBER) {
            if ($_calendar->type == Tinebase_Model_Container::TYPE_PERSONAL && Tinebase_Container::getInstance()->hasGrant($_attender->user_id, $_calendar, Tinebase_Model_Container::GRANT_ADMIN)) {
                // if attender has admin grant to personal phisycal container, this phys. cal also gets displ. cal
                $_attender->displaycontainer_id = $_calendar->getId();
            } else if ($_attender->displaycontainer_id && $_attender->user_id == Tinebase_Core::getUser()->getId() && Tinebase_Container::getInstance()->hasGrant($_attender->user_id, $_attender->displaycontainer_id, Tinebase_Model_Container::GRANT_ADMIN)) {
                // allow user to set his own displ. cal
                $_attender->displaycontainer_id = $_attender->displaycontainer_id;
            } else {
                $displayCalId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $_attender->user_id);
                if ($displayCalId) {
                    $_attender->displaycontainer_id = $displayCalId;
                }
            }
        }
        
        $this->_backend->createAttendee($_attender);
    }
    
    /**
     * updates an attender
     * @todo add support for resources
     * 
     * @param Calendar_Model_Attender  $_attender
     * @param Calendar_Model_Attender  $_currentAttender
     * @param Tinebase_Model_Container $_calendar
     */
    protected function _updateAttender($_attender, $_currentAttender, $_calendar) {
        
        // reset status if user != attender
        if ($_currentAttender->user_type == Calendar_Model_Attender::USERTYPE_GROUP 
                || $_currentAttender->user_id != Tinebase_Core::getUser()->getId()) {

            $_attender->status = $_currentAttender->status;
        }
        
        // preserv old authkey
        $_attender->status_authkey = $_currentAttender->status_authkey;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r("updating atternder: " . $_attender->toArray(), true));
        // update display calendar
        if ($_attender->user_type == Calendar_Model_Attender::USERTYPE_USER || $_attender->user_type == Calendar_Model_Attender::USERTYPE_GROUPMEMBER) {
            if ($_calendar->type == Tinebase_Model_Container::TYPE_PERSONAL && Tinebase_Container::getInstance()->hasGrant($_attender->user_id, $_calendar, Tinebase_Model_Container::GRANT_ADMIN)) {
                // if attender has admin grant to personal physical container, this phys. cal also gets displ. cal
                $_attender->displaycontainer_id = $_calendar->getId();
            } else if ($_attender->user_id == Tinebase_Core::getUser()->getId() && Tinebase_Container::getInstance()->hasGrant($_attender->user_id, $_attender->displaycontainer_id, Tinebase_Model_Container::GRANT_ADMIN)) {
                // allow user to set his own displ. cal
                $_attender->displaycontainer_id = $_attender->displaycontainer_id;
            } else {
                $_attender->displaycontainer_id = $_currentAttender->displaycontainer_id;
            }
        }
        
        $this->_backend->updateAttendee($_attender);
    }
    
    /****************************** alarm functions ************************/
    
    /**
     * send an alarm
     *
     * @param  Tinebase_Model_Alarm $_alarm
     * @return void
     * 
     * @todo throw exception on error
     * @todo finish sending of alarms (get/resolve sender/recipient)
     * @todo add more event data to message body
     */
    public function sendAlarm(Tinebase_Model_Alarm $_alarm) 
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " About to send alarm " . print_r($_alarm->toArray(), TRUE)
        );
        
        $event = $this->get($_alarm->record_id);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($event->toArray(), TRUE));
        
        $translate = Tinebase_Translation::getTranslation($this->_applicationName);
        
        // create message
        $messageSubject = $translate->_('Notification for Event ' . $event->summary);
        $messageBody = $translate->_('Event description:<br/>' . $event->description);
        
        $notificationsBackend = Tinebase_Notification_Factory::getBackend(Tinebase_Notification_Factory::SMTP);
        
        // loop recipients
        foreach ($event->attendee as $attender) {
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($attender->toArray(), TRUE));
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Sending alarm to user id ' . print_r($attender->user_id, TRUE));
            
            //-- send message
            //$notificationsBackend->send($organizer, $attender, $messageSubject, $messageBody);
        }
    }

    /**
     * saves alarm of given event
     * 
     * @param Calendar_Model_Event $_event
     * @return Tinebase_Record_RecordSet
     * 
     * @todo move this to abstract record controller
     */
    protected function _saveAlarms($_event)
    {
        $alarms = $_event->alarms instanceof Tinebase_Record_RecordSet ? 
            $_event->alarms : 
            new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        
        if (count($alarms) == 0) {
            // no alarms
            return $alarms;
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " About to save " . count($alarms) . " alarms for event {$_event->id} " 
            //.  print_r($alarms->toArray(), true)
        );
        
        $currentAlarms = Tinebase_Alarm::getInstance()->getAlarmsOfRecord($this->_modelName, $_event->id);
        $diff = $currentAlarms->getMigration($alarms->getArrayOfIds());
        Tinebase_Alarm::getInstance()->delete($diff['toDeleteIds']);
        
        // create / update alarms
        foreach ($alarms as $alarm) {
            $id = $alarm->getId();
            
            if ($id) {
                $alarm = Tinebase_Alarm::getInstance()->update($alarm);
                
            } else {
                $alarm->record_id = $_event->getId();
                if (! $alarm->model) {
                    $alarm->model = 'Calendar_Model_Event';
                }
                $alarm = Tinebase_Alarm::getInstance()->create($alarm);
            }
        }
        
        return $alarms;
    }

    /**
     * delete alarms for events
     *
     * @param array $_eventIds
     * 
     * @todo move this to abstract record controller
     */
    protected function _deleteAlarmsForIds($_eventIds)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . " Deleting alarms for events " . print_r($_eventIds, TRUE)
        );
        
        Tinebase_Alarm::getInstance()->deleteAlarmsOfRecord($this->_modelName, $_eventIds);
    }
}
