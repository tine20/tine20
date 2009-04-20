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
class Calendar_Controller_Event extends Tinebase_Controller_Record_Abstract
{
    // todo in this controller:
    //
    // add fns for participats state settings -> move to attendee controller?
    // add group attendee handling -> move to attendee controller?
    //
    // add handling to fetch all exceptions of a given event set (ActiveSync Frontend)
    //
    // handle alarms -> generic approach
    
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
        return $this->get($event->getId());
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
        $event = parent::update($_record);
        
        $this->_saveAttendee($_record);
        return $this->get($event->getId());
    }
    
    /**
     * creates an exception instance of a recuring evnet
     *
     * NOTE: deleting persistent exceptions is done via a normal delte action
     *       and handled in the delteInspection
     * 
     * @param  Calendar_Model_Event  $_event
     * @param  bool                  $_deleteInstance
     * @return Calendar_Model_Event  exception Event | updated baseEvent
     */
    public function createRecurException($_event, $_deleteInstance = FALSE)
    {
        // NOTE: recurd is computed by rrule recur computations and therefore is already
        //       part of the event.
        if (empty($_event->recurid)) {
            throw new Exception('recurid must be present to create exceptions!');
        }
        
        if (! $_deleteInstance) {
            $_event->setId(NULL);
            unset($_event->rrule);
            unset($_event->exdate);
            
            return $this->create($_event);
        } else {
            $baseEvent = $this->_backend->search(new Calendar_Model_EventFilter(array(
                array('field' => 'uid',     'operator' => 'equals', 'value' => $_event->uid),
                array('field' => 'recurid', 'operator' => 'isnull', 'value' => NULL)
            )))->getFirstRecord();
            
            $exdate = new Zend_Date(substr($_event->recurid, -19), Tinebase_Record_Abstract::ISO8601LONG);
            if (is_array($baseEvent->exdate)) {
                $exdates = $baseEvent->exdate;
                array_push($exdates, $exdate);
                $baseEvent->exdate = $exdates;
            } else {
                $baseEvent->exdate = array($exdate);
            }
            
            return $this->update($baseEvent);
        }
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
                $rrule = Calendar_Model_Rrule::getRruleFromString($_record->rrule);
                Calendar_Model_Rrule::addUTCDateDstFix($rrule->until, $diff, $_record->originator_tz);
                $_record->rrule = (string) $rrule;
                
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
            
            // deletetd persistent recur instances must be added to exdate of the baseEvent
            if (! empty($event->recurid)) {
                $this->createRecurException($event, true);
            }
        }
        
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
            ||  !$_record->has('container_id') 
            // admin grant includes all others
            ||  $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADMIN)) {
            return TRUE;
        }

        $hasGrant = FALSE;
        
        $currentAccountId = $this->_currentAccount->getId();
        
        switch ($_action) {
            case 'get':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_READ)
                            || $_record->organizer == $currentAccountId
                            || in_array($currentAccountId, $_record->attendee->filter('user_type', Calendar_Model_Attendee::USERTYPE_USER)->user_id)
                            || count(array_intersect(
                                   $_record->attendee->filter('user_type', Calendar_Model_Attendee::USERTYPE_GROUP)->user_id,
                                   Tinebase_Group::getInstance()->getGroupMemberships($currentAccountId)
                               )) > 0;
                break;
            case 'create':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_ADD);
                break;
            case 'update':
                $hasGrant = $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_EDIT)
                || $_record->organizer == $currentAccountId;
                break;
            case 'delete':
                $container = Tinebase_Container::getInstance()->getContainerById($_record->container_id);
                $hasGrant = ((
                    $this->_currentAccount->hasGrant($_record->container_id, Tinebase_Model_Container::GRANT_DELETE)
                    || $_record->organizer == $currentAccountId
                    ) && $container->type != Tinebase_Model_Container::TYPE_INTERNAL
                );
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
     * saves attendee of given event
     * 
     * @param Calendar_Model_Evnet $_event
     */
    protected function _saveAttendee($_event)
    {
        $attendee = $_event->attendee instanceof Tinebase_Record_RecordSet ? 
            $_event->attendee : 
            new Tinebase_Record_RecordSet('Calendar_Model_Attendee');
        $attendee->cal_event_id = $_event->getId();
            
        $currentAttendee = $this->_backend->getEventAttendee($_event);
        
        $diff = $currentAttendee->getMigration($attendee->getArrayOfIds());
        $this->_backend->deleteAttendee($diff['toDeleteIds']);
        
        foreach ($attendee as $attender) {
            $attenderId = $attender->getId();
            
            if ($attenderId) {
                $currentAttender = $currentAttendee[$currentAttendee->getIndexById($attenderId)];
                $attender->status_authkey = $currentAttender->status_authkey;
                
                switch ($attender->user_type) {
                    case Calendar_Model_Attendee::USERTYPE_GROUP:
                        // only updating role is supporte
                        $currentAttender->role = $attender->role;
                        $this->_backend->updateAttendee($currentAttender);
                        break;
                        
                    case Calendar_Model_Attendee::USERTYPE_GROUPMEMBER:
                    case Calendar_Model_Attendee::USERTYPE_USER:
                        // Updating is only allowed by attendee itselv
                        if ($attender->user_id == Tinebase_Core::getUser()->getId()) {
                            $this->_backend->updateAttendee($attender);
                        } else {
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "no permissions to update status for {$attender->user_type} {$attender->user_id}");
                        }
                        break;
                        
                    case Calendar_Model_Attendee::USERTYPE_RESOURCE:
                        // no acl yet
                        $this->_backend->updateAttendee($attender);
                        break;
                }
                
            } else {
                // NOTE: status_authkey gets generated by backend
                $this->_backend->createAttendee($attender);
            }
        }
    }

    //public function setAttendeeStatus($_event, $_attendee)
}
