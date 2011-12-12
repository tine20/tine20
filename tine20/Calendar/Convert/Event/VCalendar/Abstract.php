<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert single event (repeating with exceptions) to/from VCalendar
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_Abstract
{
    public static $cutypeMap = array(
        Calendar_Model_Attender::USERTYPE_USER          => 'INDIVIDUAL',
        Calendar_Model_Attender::USERTYPE_GROUPMEMBER   => 'INDIVIDUAL',
        Calendar_Model_Attender::USERTYPE_GROUP         => 'GROUP',
        Calendar_Model_Attender::USERTYPE_RESOURCE      => 'RESOURCE',
    );
    
    protected $_supportedFields = array(
    );
    
    protected $_version;
    
    /**
     * value of METHOD property
     * @var string
     */
    protected $_method;
    
    /**
     * @param  string  $_version  the version of the client
     */
    public function __construct($_version = null)
    {
        $this->_version = $_version;
    }
        
    /**
     * convert Calendar_Model_Event to Sabre_VObject_Component
     *
     * @param  Calendar_Model_Event  $_model
     * @return Sabre_VObject_Component
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_model)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' event ' . print_r($_model->toArray(), true));
        
        $vcalendar = new Sabre_VObject_Component('VCALENDAR');
        
        // required vcalendar fields
        $version = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->version;
        if (isset($this->_method)) {
            $vcalendar->METHOD = $this->_method;
        }
        $vcalendar->PRODID   = "-//tine20.org//Tine 2.0 Calendar V$version//EN";
        $vcalendar->VERSION  = '2.0';
        $vcalendar->CALSCALE = 'GREGORIAN';
        
        $vtimezone = $this->_convertDateTimezone($_model->originator_tz);
        $vcalendar->add($vtimezone);
        
        $vevent = $this->_convertCalendarModelEvent($_model);
        $vcalendar->add($vevent);
        
        if ($_model->exdate instanceof Tinebase_Record_RecordSet) {
            $eventExceptions = $_model->exdate->filter('is_deleted', false);
            
            foreach($eventExceptions as $eventException) {
                // set timefields
                // @todo move to MS event facade
                $eventException->creation_time = $_model->creation_time;
                if (isset($_model->last_modified_time)) {
                    $eventException->last_modified_time = $_model->last_modified_time;
                }
                $vevent = $this->_convertCalendarModelEvent($eventException, $_model);
                $vcalendar->add($vevent);
            }
            
        }
        
        $this->_afterFromTine20Model($vcalendar);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' card ' . $vcalendar->serialize());
        
        return $vcalendar;
    }
    
    /**
     * convert DateTimezone to Sabre_VObject_Component('VTIMEZONE')
     * 
     * @param  string|DateTimeZone  $_timezone
     * @return Sabre_VObject_Component
     */
    protected function _convertDateTimezone($_timezone)
    {
        $timezone = new DateTimeZone($_timezone);
        
        $vtimezone = new Sabre_VObject_Component('VTIMEZONE');
        $vtimezone->add(new Sabre_VObject_Property('TZID', $timezone->getName()));
        $vtimezone->add(new Sabre_VObject_Property('X-LIC-LOCATION', $timezone->getName()));
        
        list($standardTransition, $daylightTransition) = $transitions = $this->_getTransitionsForTimezoneAndYear($timezone, date('Y'));
        
        $dtstart = new Sabre_VObject_Element_DateTime('DTSTART');
        $dtstart->setDateTime(new DateTime(), Sabre_VObject_Element_DateTime::LOCAL);
        
        if ($daylightTransition !== null) {
            $offsetTo   = ($daylightTransition['offset'] < 0 ? '-' : '+') . strftime('%H%M', abs($daylightTransition['offset']));
            $offsetFrom = ($standardTransition['offset'] < 0 ? '-' : '+') . strftime('%H%M', abs($standardTransition['offset']));
            
            $daylight  = new Sabre_VObject_Component('DAYLIGHT');
            $daylight->add('TZOFFSETFROM', $offsetFrom);
            $daylight->add('TZOFFSETTO',   $offsetTo);
            $daylight->add('TZNAME',       $daylightTransition['abbr']);
            $daylight->add($dtstart);
            #$daylight->add('RRULE', 'FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3');
            
            $vtimezone->add($daylight);
        }

        if ($standardTransition !== null) {
            $offsetTo   = ($standardTransition['offset'] < 0 ? '-' : '+') . strftime('%H%M', abs($standardTransition['offset']));
            if ($daylightTransition !== null) {
                $offsetFrom = ($daylightTransition['offset'] < 0 ? '-' : '+') . strftime('%H%M', abs($daylightTransition['offset']));
            } else {
                $offsetFrom = $offsetTo;
            }
            
            $standard  = new Sabre_VObject_Component('STANDARD');
            $standard->add('TZOFFSETFROM', $offsetFrom);
            $standard->add('TZOFFSETTO',   $offsetTo);
            $standard->add('TZNAME',       $standardTransition['abbr']);
            $standard->add($dtstart);
            #$standard->add('RRULE', 'FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10');
            
            $vtimezone->add($standard);
        }
        
        return $vtimezone;
    }
    
    /**
     * Returns the standard and daylight transitions for the given {@param $_timezone}
     * and {@param $_year}.
     *
     * @param DateTimeZone $_timezone
     * @param $_year
     * @return Array
     */
    protected function _getTransitionsForTimezoneAndYear(DateTimeZone $_timezone, $_year)
    {
        $standardTransition = null;
        $daylightTransition = null;
    
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            // Since php version 5.3.0 getTransitions accepts optional start and end parameters.
            $start = mktime(0, 0, 0, 12, 1, $_year - 1);
            $end   = mktime(24, 0, 0, 12, 31, $_year);
            $transitions = $_timezone->getTransitions($start, $end);
        } else {
            $transitions = $_timezone->getTransitions();
        }
    
        $index = 0;            //we need to access index counter outside of the foreach loop
        $transition = array(); //we need to access the transition counter outside of the foreach loop
        foreach ($transitions as $index => $transition) {
            if (strftime('%Y', $transition['ts']) == $_year) {
                if (isset($transitions[$index+1]) && strftime('%Y', $transitions[$index]['ts']) == strftime('%Y', $transitions[$index+1]['ts'])) {
                    $daylightTransition = $transition['isdst'] ? $transition : $transitions[$index+1];
                    $standardTransition = $transition['isdst'] ? $transitions[$index+1] : $transition;
                } else {
                    $daylightTransition = $transition['isdst'] ? $transition : null;
                    $standardTransition = $transition['isdst'] ? null : $transition;
                }
                break;
            } elseif ($index == count($transitions) -1) {
                $standardTransition = $transition;
            }
        }
         
        return array($standardTransition, $daylightTransition);
    }
    
    
    protected function _convertCalendarModelEvent(Calendar_Model_Event $_event, Calendar_Model_Event $_mainEvent = null)
    {
        // clone the event and change the timezone
        $event = clone $_event;
        $event->setTimezone($event->originator_tz);
        
        $vevent = new Sabre_VObject_Component('VEVENT');
                
        $lastModifiedDatTime = $event->last_modified_time ? $event->last_modified_time : $event->creation_time;
        
        $created = new Sabre_VObject_Element_DateTime('CREATED');
        $created->setDateTime($event->creation_time, Sabre_VObject_Element_DateTime::UTC);
        $vevent->add($created);
        
        $lastModified = new Sabre_VObject_Element_DateTime('LAST-MODIFIED');
        $lastModified->setDateTime($lastModifiedDatTime, Sabre_VObject_Element_DateTime::UTC);
        $vevent->add($lastModified);
        
        $dtstamp = new Sabre_VObject_Element_DateTime('DTSTAMP');
        $dtstamp->setDateTime($lastModifiedDatTime, Sabre_VObject_Element_DateTime::UTC);
        $vevent->add($dtstamp);
        
        $vevent->add(new Sabre_VObject_Property('UID', $event->uid));
        $vevent->add(new Sabre_VObject_Property('SEQUENCE', $event->seq));

        if ($event->isRecurException()) {
            $originalDtStart = $event->getOriginalDtStart();
            $originalDtStart->setTimezone($_event->originator_tz);
            
            $recurrenceId = new Sabre_VObject_Element_DateTime('RECURRENCE-ID');
            if ($_mainEvent->is_all_day_event == true) {
                $recurrenceId->setDateTime($originalDtStart, Sabre_VObject_Element_DateTime::DATE);
            } else {
                $recurrenceId->setDateTime($originalDtStart);
            }

            $vevent->add($recurrenceId);
        }
        
        // dtstart and dtend
        if ($event->is_all_day_event == true) {
            $dtstart = new Sabre_VObject_Element_DateTime('DTSTART');
            $dtstart->setDateTime($event->dtstart, Sabre_VObject_Element_DateTime::DATE);
            
            // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in vcalendar
            $event->dtend->addSecond(1);
            
            $dtend = new Sabre_VObject_Element_DateTime('DTEND');
            $dtend->setDateTime($event->dtend, Sabre_VObject_Element_DateTime::DATE);
        } else {
            $dtstart = new Sabre_VObject_Element_DateTime('DTSTART');
            $dtstart->setDateTime($event->dtstart);
            
            $dtend = new Sabre_VObject_Element_DateTime('DTEND');
            $dtend->setDateTime($event->dtend);
        }
        $vevent->add($dtstart);
        $vevent->add($dtend);
        
        // event organizer
        if (!empty($event->organizer)) {
            $organizerContact = Addressbook_Controller_Contact::getInstance()->getMultiple(array($event->organizer), TRUE)->getFirstRecord();

            if ($organizerContact instanceof Addressbook_Model_Contact && !empty($organizerContact->email)) {
                $organizer = new Sabre_VObject_Property('ORGANIZER', 'mailto:' . $organizerContact->email);
                $organizer->add('CN', $organizerContact->n_fileas);
                $vevent->add($organizer);
            }
        }
        
        $this->_addEventAttendee($vevent, $event);
        
        $optionalProperties = array(
            'class',
            'description',
            'geo',
            'location',
            'priority',
            'summary',
            'transp',
            'url'
        );
        
        foreach ($optionalProperties as $property) {
            if (!empty($event->$property)) {
                $vevent->add(new Sabre_VObject_Property(strtoupper($property), $event->$property));
            }
        }
        
        // categories
        if(isset($event->tags) && count($event->tags) > 0) {
            $vevent->add(new Sabre_VObject_Property_List('CATEGORIES', (array) $event->tags->name));
        }
        
        // repeating event properties
        if ($event->rrule) {
            if ($event->is_all_day_event == true) {
                $vevent->add(new Sabre_VObject_Property_Recure('RRULE', preg_replace_callback('/UNTIL=([\d :-]{19})(?=;?)/', function($matches) {
                    $dtUntil = new Tinebase_DateTime($matches[1]);
                    $dtUntil->setTimezone((string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                    
                    return 'UNTIL=' . $dtUntil->format('Ymd');
                }, $event->rrule)));
            } else {
                $vevent->add(new Sabre_VObject_Property_Recure('RRULE', preg_replace('/(UNTIL=)(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', '$1$2$3$4T$5$6$7Z', $event->rrule)));
            }
            if ($event->exdate instanceof Tinebase_Record_RecordSet) {
                $deletedEvents = $event->exdate->filter('is_deleted', true);
                
                foreach($deletedEvents as $deletedEvent) {
                    $exdate = new Sabre_VObject_Element_DateTime('EXDATE');
                    $dateTime = $deletedEvent->getOriginalDtStart();
                    
                    if ($event->is_all_day_event == true) {
                        $dateTime->setTimezone($event->originator_tz);
                        $exdate->setDateTime($dateTime, Sabre_VObject_Element_DateTime::DATE);
                    } else {
                        $exdate->setDateTime($dateTime, Sabre_VObject_Element_DateTime::UTC);
                    }
                    $vevent->add($exdate);
                }
            }
        }
        
        if ($event->alarms) {
            foreach($event->alarms as $alarm) {
                $valarm = new Sabre_VObject_Component('VALARM');
                $valarm->add('ACTION', 'DISPLAY');
                $valarm->add('DESCRIPTION', $event->summary);

                if (!empty($alarm->options)) {
                    $options = Zend_Json::decode($alarm->options);
                    if (is_array($options) && array_key_exists('custom', $options) && $options['custom'] === true) {
                        $alarm->minutes_before = 'custom';
                    }
                }
                
                if (is_numeric($alarm->minutes_before)) {
                    if ($event->dtstart == $alarm->alarm_time) {
                        $periodString = 'PT0S';
                    } else {
                        $interval = $event->dtstart->diff($alarm->alarm_time);
                        $periodString = sprintf('%sP%s%s%s%s',
                            $interval->format('%r'),
                            $interval->format('%d') > 0 ? $interval->format('%dD') : null,
                            ($interval->format('%h') > 0 || $interval->format('%i') > 0) ? 'T' : null,
                            $interval->format('%h') > 0 ? $interval->format('%hH') : null,
                            $interval->format('%i') > 0 ? $interval->format('%iM') : null
                        );
                    }
                    # TRIGGER;VALUE=DURATION:-PT1H15M
                    $trigger = new Sabre_VObject_Property('TRIGGER', $periodString);
                    $trigger->add('VALUE', "DURATION");
                    $valarm->add($trigger);
                } else {
                    # TRIGGER;VALUE=DATE-TIME:...
                    $trigger = new Sabre_VObject_Element_DateTime('TRIGGER');
                    $trigger->add('VALUE', "DATE-TIME");
                    $trigger->setDateTime($alarm->alarm_time, Sabre_VObject_Element_DateTime::UTC);
                    $valarm->add($trigger);
                }
                
                $vevent->add($valarm);
            }
            
            if (($ownAttendee = Calendar_Model_Attender::getOwnAttender($event->attendee)) !== null) {
                if ($ownAttendee->alarm_ack_time instanceof Tinebase_DateTime) {
                    $xMozLastAck = new Sabre_VObject_Element_DateTime('X-MOZ-LASTACK');
                    $xMozLastAck->setDateTime(Tinebase_DateTime::now(), Sabre_VObject_Element_DateTime::UTC);
                    $vevent->add($xMozLastAck);
                }
                if ($ownAttendee->alarm_snooze_time instanceof Tinebase_DateTime) {
                    $xMozSnoozeTime = new Sabre_VObject_Element_DateTime('X-MOZ-SNOOZE-TIME');
                    $xMozSnoozeTime->setDateTime(Tinebase_DateTime::now(), Sabre_VObject_Element_DateTime::UTC);
                    $vevent->add($xMozSnoozeTime);
                }
            }
        }
        
        return $vevent;
    }
    
    protected function _addEventAttendee(Sabre_VObject_Component $_vevent, Calendar_Model_Event $_event)
    {
        Calendar_Model_Attender::resolveAttendee($_event->attendee, FALSE);
        
        foreach($_event->attendee as $eventAttendee) {
            $attendeeEmail = $eventAttendee->getEmail();
            if ($attendeeEmail) {
                $attendee = new Sabre_VObject_Property('ATTENDEE', (strpos($attendeeEmail, '@') !== false ? 'mailto:' : 'urn:uuid:') . $attendeeEmail);
                $attendee->add('CN',       $eventAttendee->getName());
                $attendee->add('CUTYPE',   Calendar_Convert_Event_VCalendar_Abstract::$cutypeMap[$eventAttendee->user_type]);
                $attendee->add('PARTSTAT', $eventAttendee->status);
                $attendee->add('ROLE',     "{$eventAttendee->role}-PARTICIPANT");
                $attendee->add('RSVP',     'FALSE');
                if (strpos($attendeeEmail, '@') !== false) {
                    $attendee->add('EMAIL',    $attendeeEmail);
                }

                $_vevent->add($attendee);
            }
        }
    }
    
    /**
     * to be overwriten in extended classes to modify/cleanup $_vcalendar
     * 
     * @param Sabre_VObject_Component $_vcalendar
     */
    protected function _afterFromTine20Model(Sabre_VObject_Component $_vcalendar)
    {
        
    }
    
    /**
     * set the METHOD for the generated VCALENDAR
     *
     * @param  string  $_method  the method
     */
    public function setMethod($_method)
    {
        $this->_method = $_method;
    }
    
    /**
     * converts vcalendar to Calendar_Model_Event
     * 
     * @param  mixed                 $_blob   the vcalendar to parse
     * @param  Calendar_Model_Event  $_model  update existing event
     * @return Calendar_Model_Event
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_model = null)
    {
        $vcalendar = $this->_getVcal($_blob);
        
        // contains the VCALENDAR any VEVENTS
        if (!isset($vcalendar->VEVENT)) {
            throw new Tinebase_Exception_UnexpectedValue('no vevents found');
        }
        
        // update a provided record or create a new one
        if ($_model instanceof Calendar_Model_Event) {
            $event = $_model;
        } else {
            $event = new Calendar_Model_Event(null, false);
        }
        
        // keep current exdate's (only the not deleted ones)
        if ($event->exdate instanceof Tinebase_Record_RecordSet) {
            $oldExdates = $event->exdate->filter('is_deleted', false);
        } else {
            $oldExdates = new Tinebase_Record_RecordSet('Calendar_Model_Events');
        }
        
        if (!isset($vcalendar->METHOD)) {
            $this->_method = $vcalendar->METHOD;
        }
                
        // find the main event - the main event has no RECURRENCE-ID
        foreach($vcalendar->VEVENT as $vevent) {
            // "-" is not allowed in property names
            $RECURRENCEID = 'RECURRENCE-ID';
            if(!isset($vevent->$RECURRENCEID)) {
                $this->_convertVevent($vevent, $event);
                
                break;
            }
        }

        // if we have found no VEVENT component something went wrong, lets stop here
        if (!isset($event)) {
            throw new Tinebase_Exception_UnexpectedValue('no main VEVENT component found in VCALENDAR');
        }
        
        // parse the event exceptions
        foreach($vcalendar->VEVENT as $vevent) {
            if(isset($vevent->$RECURRENCEID) && $event->uid == $vevent->UID) {
                $recurException = $this->_getRecurException($oldExdates, $vevent);
                
                // initialize attendee with attendee from base events for new exceptions
                // this way we can keep attendee extra values like groupmember type
                // attendees which do not attend to the new exception will be removed in _convertVevent
                if (! $recurException->attendee instanceof Tinebase_Record_RecordSet) {
                    $recurException->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
                    foreach ($event->attendee as $attendee) {
                        $recurException->attendee->addRecord(new Calendar_Model_Attender(array(
                            'user_id'   => $attendee->user_id,
                        	'user_type' => $attendee->user_type,
                        	'role'      => $attendee->role,
                        	'status'    => $attendee->status
                        )));
                    }
                }
                
                $this->_convertVevent($vevent, $recurException);
                    
                if(! $event->exdate instanceof Tinebase_Record_RecordSet) {
                    $event->exdate = new Tinebase_Record_RecordSet('Calendar_Model_Event');
                }
                $event->exdate->addRecord($recurException);
            }
        }
        
        $event->isValid(true);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($event->toArray(), true));
        
        return $event;
    }
    
    protected function _getVcal($_blob)
    {
        if ($_blob instanceof Sabre_VObject_Component) {
            $vcalendar = $_blob;
        } else {
            if (is_resource($_blob)) {
                $_blob = stream_get_contents($_blob);
            }
            $vcalendar = Sabre_VObject_Reader::read($_blob);
        }
        
        return $vcalendar;
    }
    
    public function getMethod($_blob = NULL)
    {
        $result = NULL;
        
        if ($this->_method) {
            $result = $this->_method;
        } else if ($_blob !== NULL) {
            $vcalendar = $this->_getVcal($_blob);
            $result = $vcalendar->METHOD;
        }
        
        return $result;
    }

    /**
     * find a matching exdate or return an empty event record
     * 
     * @param  Tinebase_Record_RecordSet  $_oldExdates
     * @param  Sabre_VObject_Component    $_vevent
     * @return Calendar_Model_Event
     */
    protected function _getRecurException(Tinebase_Record_RecordSet $_oldExdates, Sabre_VObject_Component $_vevent)
    {
        // "-" is not allowed in property names
        $RECURRENCEID = 'RECURRENCE-ID';
        
        $exDate = clone $_vevent->$RECURRENCEID->getDateTime();
        $exDate->setTimeZone(new DateTimeZone('UTC'));
        $exDateString = $exDate->format('Y-m-d H:i:s');
        foreach ($_oldExdates as $id => $oldExdate) {
            if ($exDateString == substr((string) $oldExdate->recurid, -19)) {
                unset($_oldExdates[$id]);
                
                return $oldExdate;
            }
        }
        
        return new Calendar_Model_Event();
    }
    
    /**
     * get attendee object for given contact
     * 
     * @param Sabre_VObject_Property     $_attendee  the attendee row from the vevent object
     * @return array
     */
    protected function _getAttendee(Sabre_VObject_Property $_attendee)
    {
        if (isset($_attendee['CUTYPE']) && in_array($_attendee['CUTYPE']->value, array('INDIVIDUAL', Calendar_Model_Attender::USERTYPE_GROUP, Calendar_Model_Attender::USERTYPE_RESOURCE))) {
            $type = $_attendee['CUTYPE']->value == 'INDIVIDUAL' ? Calendar_Model_Attender::USERTYPE_USER : $_attendee['CUTYPE']->value;
        } else {
            $type = Calendar_Model_Attender::USERTYPE_USER;
        }
        
        if (isset($_attendee['ROLE']) && in_array($_attendee['ROLE']->value, array(Calendar_Model_Attender::ROLE_OPTIONAL, Calendar_Model_Attender::ROLE_REQUIRED))) {
            $role = $_attendee['ROLE']->value;
        } else {
            $role = Calendar_Model_Attender::ROLE_REQUIRED;
        }
        
        if (in_array($_attendee['PARTSTAT']->value, array(Calendar_Model_Attender::STATUS_ACCEPTED,
            Calendar_Model_Attender::STATUS_DECLINED,
            Calendar_Model_Attender::STATUS_NEEDSACTION,
            Calendar_Model_Attender::STATUS_TENTATIVE)
        )) {
            $status = $_attendee['PARTSTAT']->value;
        } else {
            $status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        }
        
        preg_match('/(?P<protocol>mailto:|urn:uuid:)(?P<email>.*)/', $_attendee->value, $matches);
        $email = $matches['email'];
        
        $fullName = isset($_attendee['CN']) ? $_attendee['CN'] : $email;
        
        if (preg_match('/(?P<firstName>\S*) (?P<lastNameName>\S*)/', $fullName, $matches)) {
            $firstName = $matches['firstName'];
            $lastName  = $matches['lastNameName'];
        } else {
            $firstName = null;
            $lastName  = $fullName;
        }

        $attendee = array(
            'userType'  => $type,
            'firstName' => $firstName,
        	'lastName'  => $lastName,
            'partStat'  => $status,
            'role'      => $role,
            'email'     => $email
        );
        
        return $attendee;
    }
    
    /**
     * parse VEVENT part of VCALENDAR
     * 
     * @param  Sabre_VObject_Component  $_vevent  the VEVENT to parse
     * @param  Calendar_Model_Event     $_event   the Tine 2.0 event to update
     */
    protected function _convertVevent(Sabre_VObject_Component $_vevent, Calendar_Model_Event $_event)
    {
        $event = $_event;
        $newAttendees = array();
        
        // unset supported fields
        foreach ($this->_supportedFields as $field) {
            $event->$field = null;
        }
        
        foreach($_vevent->children() as $property) {
            switch($property->name) {
                case 'CREATED':
                case 'LAST-MODIFIED':
                case 'DTSTAMP':
                    // do nothing
                    break;
                    
                case 'ATTENDEE':
                    $newAttendees[] = $this->_getAttendee($property);
                    
                    break;
                    
                case 'CLASS':
                    if (in_array($property->value, array(Calendar_Model_Event::CLASS_PRIVATE, Calendar_Model_Event::CLASS_PUBLIC))) {
                        $event->class = $property->value;
                    } else {
                        $event->class = Calendar_Model_Event::CLASS_PUBLIC;
                    }
                    
                    break;
                    
                case 'DTEND':
                    

                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        $event->is_all_day_event = true;
                        
                        $dtend = new Tinebase_DateTime($property->getDateTime()->format(Tinebase_Record_Abstract::ISO8601LONG), (string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                        
                        // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in vcalendar
                        $dtend->subSecond(1);
                    } else {
                        $event->is_all_day_event = false;
                        
                        $dtend = new Tinebase_DateTime($property->getDateTime()->format(Tinebase_Record_Abstract::ISO8601LONG), $property->getDateTime()->getTimezone());
                    }
                    
                    $event->dtend = $dtend;
                    
                    break;
                    
                case 'DTSTART':
                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        $event->is_all_day_event = true;

                        $dtstart = new Tinebase_DateTime($property->getDateTime()->format(Tinebase_Record_Abstract::ISO8601LONG), (string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                    } else {
                        $event->is_all_day_event = false;
                        
                        $dtstart = new Tinebase_DateTime($property->getDateTime()->format(Tinebase_Record_Abstract::ISO8601LONG), $property->getDateTime()->getTimezone());
                    }
                    
                    $event->originator_tz = $dtstart->getTimezone()->getName();
                    
                    $event->dtstart = $dtstart;
                    
                    break;
                    
                case 'DESCRIPTION':
                case 'LOCATION':
                case 'UID':
                case 'SEQ':
                case 'SUMMARY':
                    $key = strtolower($property->name);
                    $event->$key = $property->value;
                    
                    break;
                    
                case 'ORGANIZER':
                    if (preg_match('/mailto:(?P<email>.*)/', $property->value, $matches)) {
                        $name = isset($property['CN']) ? $property['CN'] : $matches['email'];
                        $contact = $this->_resolveEmailToContact($matches['email'], $name);
                        
                        // it's not possible to change the organizer by spec
                        if (empty($event->organizer)) {
                            $event->organizer = $contact->getId();
                        }
                        
                        // Lightning attaches organizer ATTENDEE properties to ORGANIZER property and does not add an ATTENDEE for the organizer
                        if (isset($property['PARTSTAT'])) {
                            $newAttendees[] = $this->_getAttendee($property);
                        }
                    }
                    
                    break;

                case 'RECURRENCE-ID':
                    // original start of the event
                    $event->recurid = new Tinebase_DateTime($property->getDateTime()->format(Tinebase_Record_Abstract::ISO8601LONG), $property->getDateTime()->getTimezone());
                    
                    // convert recurrence id to utc
                    $event->recurid->setTimezone('UTC');
                    
                    break;
                    
                case 'RRULE':
                    $event->rrule = $property->value;
                    
                    // convert date format
                    $event->rrule = preg_replace_callback('/UNTIL=([\dTZ]+)(?=;?)/', function($matches) {
                        if (strlen($matches[1]) < 10) {
                            $dtUntil = date_create($matches[1], new DateTimeZone ((string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE)));
                            $dtUntil->setTimezone(new DateTimeZone('UTC'));
                        } else {
                            $dtUntil = date_create($matches[1]);
                        }
                        
                        return 'UNTIL=' . $dtUntil->format(Tinebase_Record_Abstract::ISO8601LONG);
                    }, $event->rrule);

                    // remove additional days from BYMONTHDAY property
                    $event->rrule = preg_replace('/(BYMONTHDAY=)([\d]+)([,\d]+)/', '$1$2', $event->rrule);
                    
                    // process exceptions
                    if (isset($_vevent->EXDATE)) {
                        $exdates = new Tinebase_Record_RecordSet('Calendar_Model_Event');
                        
                        foreach($_vevent->EXDATE as $exdate) {
                            foreach($exdate->getDateTimes() as $exception) {
                                if (isset($exdate['VALUE']) && strtoupper($exdate['VALUE']) == 'DATE') {
                                    $recurid = new Tinebase_DateTime($exception->format(Tinebase_Record_Abstract::ISO8601LONG), (string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                                } else {
                                    $recurid = new Tinebase_DateTime($exception->format(Tinebase_Record_Abstract::ISO8601LONG), $exception->getTimezone());
                                }
                                $recurid->setTimezone(new DateTimeZone('UTC'));
                                                        
                                $eventException = new Calendar_Model_Event(array(
                                	'recurid'    => $recurid,
                                	'is_deleted' => true
                                ));
                        
                                $exdates->addRecord($eventException);
                            }
                        }
                    
                        $event->exdate = $exdates;
                    }     
                                   
                    break;
                    
                case 'TRANSP':
                    if (in_array($property->value, array(Calendar_Model_Event::TRANSP_OPAQUE, Calendar_Model_Event::TRANSP_TRANSP))) {
                        $event->transp = $property->value;
                    } else {
                        $event->transp = Calendar_Model_Event::TRANSP_TRANSP;
                    }
                    
                    break;
                    
                case 'UID':
                    // it's not possible to change the uid by spec
                    if (!empty($event->uid)) {
                        continue;
                    }
                    
                    $event->uid = $property->value;
                
                    break;
                    
                case 'VALARM':
                    $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
                    
                    foreach($property as $valarm) {
                        switch(strtoupper($valarm->TRIGGER['VALUE']->value)) {
                            # TRIGGER;VALUE=DATE-TIME:20111031T130000Z
                            case 'DATE-TIME':
                                $alarmTime = new Tinebase_DateTime($_vevent->DTSTART->getDateTime()->format(Tinebase_Record_Abstract::ISO8601LONG), $_vevent->DTSTART->getDateTime()->getTimezone());
                                $alarmTime->setTimezone('UTC');
                                
                                $alarm = new Tinebase_Model_Alarm(array(
                                    'alarm_time'        => $alarmTime,
                                    'minutes_before'    => 'custom',
                                    'model'             => 'Calendar_Model_Event'
                                ));
                                
                                $event->alarms->addRecord($alarm);
                                
                                break;
                                
                            # TRIGGER;VALUE=DURATION:-PT1H15M
                            case 'DURATION':
                            default:
                                $alarmTime = new Tinebase_DateTime($_vevent->DTSTART->getDateTime()->format(Tinebase_Record_Abstract::ISO8601LONG), $_vevent->DTSTART->getDateTime()->getTimezone());
                                $alarmTime->setTimezone('UTC');
                                
                                preg_match('/(?P<invert>[+-]?)(?P<spec>P.*)/', $valarm->TRIGGER->value, $matches);
                                $duration = new DateInterval($matches['spec']);
                                $duration->invert = !!($matches['invert'] === '-');

                                $alarm = new Tinebase_Model_Alarm(array(
                                    'alarm_time'        => $alarmTime->add($duration),
                                    'minutes_before'    => ($duration->format('%d') * 60 * 24) + ($duration->format('%h') * 60) + ($duration->format('%i')),
                                    'model'             => 'Calendar_Model_Event'
                                ));
                                
                                $event->alarms->addRecord($alarm);
                                
                                break;
                        }
                    }
                    
                    break;
                    
                case 'CATEGORIES':
                    // @todo handle categories
                    break;
                    
                case 'X-MOZ-LASTACK':
                    $lastAck = new Tinebase_DateTime($property->getDateTime()->format(Tinebase_Record_Abstract::ISO8601LONG), $property->getDateTime()->getTimezone());
                    break;
                    
                case 'X-MOZ-SNOOZE-TIME':
                    $snoozeTime = new Tinebase_DateTime($property->getDateTime()->format(Tinebase_Record_Abstract::ISO8601LONG), $property->getDateTime()->getTimezone());
                    break;
                    
                default:
                
                    break;
            }
        }
        
        // merge old and new attendees
        Calendar_Model_Attender::emailsToAttendee($event, $newAttendees);
        
        if (($ownAttendee = Calendar_Model_Attender::getOwnAttender($event->attendee)) !== null) {
            if (isset($lastAck)) {
                $ownAttendee->alarm_ack_time = $lastAck;
            }
            if (isset($snoozeTime)) {
                $ownAttendee->alarm_snooze_time = $snoozeTime;
            }
        }
        
        if (empty($event->seq)) {
            $event->seq = 0;
        }
        
        if (empty($event->class)) {
            $event->class = Calendar_Model_Event::CLASS_PUBLIC;
        }
        
        // convert all datetime fields to UTC
        $event->setTimezone('UTC');
    }
    
    /**
     * find contact identifed by personal of work email address
     * 
     * @param  string  $_email  the email address of the contact
     * @param  string  $_fn     the fullname of the contact (used to match mulitple contacts and used when creating a new contact)
     * @return Addressbook_Model_Contact
     */
    protected function _resolveEmailToContact($_email, $_fn)
    {
        // search contact from addressbook using the emailaddress
        $filterArray = array(
            array(
                'field'     => 'containerType',
                'operator'  => 'equals',
                'value'     => 'all'
            ),
            array('condition' => 'OR', 'filters' => array(
                array(
                    'field'     => 'email',
                    'operator'  => 'equals',
                    'value'     => $_email
                ),
                array(
                    'field'     => 'email_home',
                    'operator'  => 'equals',
                    'value'     => $_email
                )
            ))
        );
         
        $contacts = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter($filterArray));

        // @todo filter by fn if multiple matches
        if(count($contacts) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found # of contacts " . count($contacts));
            return $contacts->getFirstRecord();
        }
        
        $contact = new Addressbook_Model_Contact(array(
            'n_family' => $_fn,
        	'email'    => $_email,
            'note'     => 'added by syncronisation'
        ));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " add new contact " . print_r($contact->toArray(), true));
        
        return Addressbook_Controller_Contact::getInstance()->create($contact);
    }    
}
