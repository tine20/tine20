<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert single event (repeating with exceptions) to/from VCalendar
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_Abstract implements Tinebase_Convert_Interface
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
     * @param  Calendar_Model_Event  $_record
     * @return Sabre_VObject_Component
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' event ' . print_r($_record->toArray(), true));
        
        $vcalendar = new Sabre_VObject_Component('VCALENDAR');
        
        // required vcalendar fields
        $version = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->version;
        if (isset($this->_method)) {
            $vcalendar->METHOD = $this->_method;
        }
        $vcalendar->PRODID   = "-//tine20.org//Tine 2.0 Calendar V$version//EN";
        $vcalendar->VERSION  = '2.0';
        $vcalendar->CALSCALE = 'GREGORIAN';
        
        $vcalendar->add(new Sabre_VObject_Component_VTimezone($_record->originator_tz));
        
        $vevent = $this->_convertCalendarModelEvent($_record);
        $vcalendar->add($vevent);
        
        if ($_record->exdate instanceof Tinebase_Record_RecordSet) {
            $_record->exdate->addIndices(array('is_deleted'));
            $eventExceptions = $_record->exdate->filter('is_deleted', false);
            
            foreach ($eventExceptions as $eventException) {
                $vevent = $this->_convertCalendarModelEvent($eventException, $_record);
                $vcalendar->add($vevent);
            }
            
        }
        
        $this->_afterFromTine20Model($vcalendar);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' card ' . $vcalendar->serialize());
        
        return $vcalendar;
    }
    
    /**
     * convert calendar event to Sabre_VObject_Component
     * 
     * @param Calendar_Model_Event $_event
     * @param Calendar_Model_Event $_mainEvent
     * @return Sabre_VObject_Component
     */
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
        $dtstamp->setDateTime(Tinebase_DateTime::now(), Sabre_VObject_Element_DateTime::UTC);
        $vevent->add($dtstamp);
        
        $vevent->add(new Sabre_VObject_Property('UID', $event->uid));
        $vevent->add(new Sabre_VObject_Property('SEQUENCE', $event->seq));

        if ($event->isRecurException()) {
            $originalDtStart = $event->getOriginalDtStart();
            $originalDtStart->setTimezone($_event->originator_tz);
            
            $recurrenceId = new Sabre_VObject_Element_DateTime('RECURRENCE-ID');
            if ($_mainEvent && $_mainEvent->is_all_day_event == true) {
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
            
            // whole day events ends at 23:59:(00|59) in Tine 2.0 but 00:00 the next day in vcalendar
            $event->dtend->addSecond($event->dtend->get('s') == 59 ? 1 : 0);
            $event->dtend->addMinute($event->dtend->get('i') == 59 ? 1 : 0);
            
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
        
        // auto status for deleted events
        if ($event->is_deleted) {
            $event->status = Calendar_Model_Event::STATUS_CANCELED;
        }
        
        // event organizer
        if (!empty($event->organizer)) {
            $organizerContact = $event->resolveOrganizer();

            if ($organizerContact instanceof Addressbook_Model_Contact && !empty($organizerContact->email)) {
                $organizer = new Sabre_VObject_Property('ORGANIZER', 'mailto:' . $organizerContact->email);
                $organizer->add('CN', $organizerContact->n_fileas);
                $organizer->add('EMAIL', $organizerContact->email);
                $vevent->add($organizer);
            }
        }
        
        $this->_addEventAttendee($vevent, $event);
        
        $optionalProperties = array(
            'class',
            'status',
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
                $event->exdate->addIndices(array('is_deleted'));
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
        
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($event->attendee);
        
        if ($ownAttendee && $ownAttendee->alarm_ack_time instanceof Tinebase_DateTime) {
            $xMozLastAck = new Sabre_VObject_Element_DateTime('X-MOZ-LASTACK');
            $xMozLastAck->setDateTime($ownAttendee->alarm_ack_time, Sabre_VObject_Element_DateTime::UTC);
            $vevent->add($xMozLastAck);
        }
        
        if ($ownAttendee && $ownAttendee->alarm_snooze_time instanceof Tinebase_DateTime) {
            $xMozSnoozeTime = new Sabre_VObject_Element_DateTime('X-MOZ-SNOOZE-TIME');
            $xMozSnoozeTime->setDateTime($ownAttendee->alarm_snooze_time, Sabre_VObject_Element_DateTime::UTC);
            $vevent->add($xMozSnoozeTime);
        }
        
        if ($event->alarms instanceof Tinebase_Record_RecordSet) {
            foreach($event->alarms as $alarm) {
                $valarm = new Sabre_VObject_Component('VALARM');
                $valarm->add('ACTION', 'DISPLAY');
                $valarm->add(new Sabre_VObject_Property('DESCRIPTION', $event->summary));
                
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
        }
        
        return $vevent;
    }
    
    protected function _addEventAttendee(Sabre_VObject_Component $_vevent, Calendar_Model_Event $_event)
    {
        Calendar_Model_Attender::resolveAttendee($_event->attendee, FALSE, $_event);
        
        foreach($_event->attendee as $eventAttendee) {
            $attendeeEmail = $eventAttendee->getEmail();
            
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
     * @param  Calendar_Model_Event  $_record  update existing event
     * @return Calendar_Model_Event
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_record = null)
    {
        $vcalendar = self::getVcal($_blob);
        
        // contains the VCALENDAR any VEVENTS
        if (! isset($vcalendar->VEVENT)) {
            throw new Tinebase_Exception_UnexpectedValue('no vevents found');
        }
        
        // update a provided record or create a new one
        if ($_record instanceof Calendar_Model_Event) {
            $event = $_record;
        } else {
            $event = new Calendar_Model_Event(null, false);
        }
        
        if (!isset($vcalendar->METHOD)) {
            $this->_method = $vcalendar->METHOD;
        }
        
        // find the main event - the main event has no RECURRENCE-ID
        foreach($vcalendar->VEVENT as $vevent) {
            if(!isset($vevent->{'RECURRENCE-ID'})) {
                $this->_convertVevent($vevent, $event);
                
                break;
            }
        }

        // if we have found no VEVENT component something went wrong, lets stop here
        if (! $event->toArray()) {
            throw new Tinebase_Exception_UnexpectedValue('no main VEVENT component found in VCALENDAR');
        }
        
        // parse the event exceptions
        $oldExdates = $event->exdate instanceof Tinebase_Record_RecordSet ? $event->exdate->filter('is_deleted', false) : new Tinebase_Record_RecordSet('Calendar_Model_Events');
        foreach ($vcalendar->VEVENT as $vevent) {
            if(isset($vevent->{'RECURRENCE-ID'}) && $event->uid == $vevent->UID) {
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
                    
                if (! $event->exdate instanceof Tinebase_Record_RecordSet) {
                    $event->exdate = new Tinebase_Record_RecordSet('Calendar_Model_Event');
                }
                $event->exdate->addRecord($recurException);
            }
        }
        
        $event->isValid(true);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($event->toArray(), true));
        
        return $event;
    }
    
    /**
     * returns VObject of input data
     * 
     * @param mixed $_blob
     * @return Sabre_VObject_Component
     */
    public static function getVcal($_blob)
    {
        if ($_blob instanceof Sabre_VObject_Component) {
            $vcalendar = $_blob;
        } else {
            if (is_resource($_blob)) {
                $_blob = stream_get_contents($_blob);
            }
            $vcalendar = self::readVCalBlob($_blob);
        }
        
        return $vcalendar;
    }
    
    /**
     * reads vcal blob and tries to repair some parsing problems that Sabre has
     * 
     * @param string $blob
     * @param integer $failcount
     * @param integer $spacecount
     * @param integer $lastBrokenLineNumber
     * @param array $lastLines
     * @throws Sabre_VObject_ParseException
     * @return Sabre_VObject_Component
     * 
     * @see 0006110: handle iMIP messages from outlook
     * @see 0007438: update Sabre library
     * 
     * @todo maybe we can remove this when #7438 is resolved
     */
    public static function readVCalBlob($blob, $failcount = 0, $spacecount = 0, $lastBrokenLineNumber = 0, $lastLines = array())
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' ' . $blob);
        
        try {
            $vcalendar = Sabre_VObject_Reader::read($blob);
        } catch (Sabre_VObject_ParseException $svpe) {
            // NOTE: we try to repair Sabre_VObject_Reader as it fails to detect followup lines that do not begin with a space or tab
            if ($failcount < 10 && preg_match(
                '/Invalid VObject, line ([0-9]+) did not follow the icalendar\/vcard format/', $svpe->getMessage(), $matches
            )) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' ' . $svpe->getMessage() .
                    ' lastBrokenLineNumber: ' . $lastBrokenLineNumber);
                
                $brokenLineNumber = $matches[1] - 1 + $spacecount;
                
                if ($lastBrokenLineNumber === $brokenLineNumber) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                        ' Try again: concat this line to previous line.');
                    $lines = $lastLines;
                    $brokenLineNumber--;
                    // increase spacecount because one line got removed
                    $spacecount++;
                } else {
                    $lines = preg_split('/[\r\n]*\n/', $blob);
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                        ' Concat next line to this one.');
                    $lastLines = $lines; // for retry
                }
                $lines[$brokenLineNumber] .= $lines[$brokenLineNumber + 1];
                unset($lines[$brokenLineNumber + 1]);
                
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
                    ' failcount: ' . $failcount .
                    ' brokenLineNumber: ' . $brokenLineNumber .
                    ' spacecount: ' . $spacecount);
                
                $vcalendar = self::readVCalBlob(implode("\n", $lines), $failcount + 1, $spacecount, $brokenLineNumber, $lastLines);
            } else {
                throw $svpe;
            }
        }
        
        return $vcalendar;
    }
    
    public function getMethod($_blob = NULL)
    {
        $result = NULL;
        
        if ($this->_method) {
            $result = $this->_method;
        } else if ($_blob !== NULL) {
            $vcalendar = self::getVcal($_blob);
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
        $exDate = clone $_vevent->{'RECURRENCE-ID'}->getDateTime();
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
        
        if (isset($_attendee['EMAIL']) && !empty($_attendee['EMAIL']->value)) {
            $email = $_attendee['EMAIL']->value;
        } else {
            if (!preg_match('/(?P<protocol>mailto:|urn:uuid:)(?P<email>.*)/i', $_attendee->value, $matches)) {
                throw new Tinebase_Exception_UnexpectedValue('invalid attendee provided: ' . $_attendee->value);
            }
            $email = $matches['email'];
        }
        
        $fullName = isset($_attendee['CN']) ? $_attendee['CN']->value : $email;
        
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
            if ($field == 'alarms') {
                $event->$field = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
            } else {
                $event->$field = null;
            }
        }
        
        foreach($_vevent->children() as $property) {
            switch($property->name) {
                case 'CREATED':
                case 'DTSTAMP':
                    // do nothing
                    break;
                    
                case 'LAST-MODIFIED':
                    $event->last_modified_time = new Tinebase_DateTime($property->value);
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
                    
                case 'STATUS':
                    if (in_array($property->value, array(Calendar_Model_Event::STATUS_CONFIRMED, Calendar_Model_Event::STATUS_TENTATIVE, Calendar_Model_Event::STATUS_CANCELED))) {
                        $event->status = $property->value;
                    } else {
                        $event->status = Calendar_Model_Event::STATUS_CONFIRMED;
                    }
                    break;
                    
                case 'DTEND':
                    
                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        $event->is_all_day_event = true;
                        $dtend = $this->_convertToTinebaseDateTime($property, TRUE);
                        
                        // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in vcalendar
                        $dtend->subSecond(1);
                    } else {
                        $event->is_all_day_event = false;
                        $dtend = $this->_convertToTinebaseDateTime($property);
                    }
                    
                    $event->dtend = $dtend;
                    
                    break;
                    
                case 'DTSTART':
                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        $event->is_all_day_event = true;
                        $dtstart = $this->_convertToTinebaseDateTime($property, TRUE);
                    } else {
                        $event->is_all_day_event = false;
                        $dtstart = $this->_convertToTinebaseDateTime($property);
                    }
                    
                    $event->originator_tz = $dtstart->getTimezone()->getName();
                    $event->dtstart = $dtstart;
                    
                    break;
                    
                case 'SEQUENCE':
                    $event->seq = $property->value;
                    break;
                    
                case 'DESCRIPTION':
                case 'LOCATION':
                case 'UID':
                case 'SUMMARY':
                    $key = strtolower($property->name);
                    $event->$key = $property->value;
                    break;
                    
                case 'ORGANIZER':
                    $email = null;
                    
                    if (isset($property['EMAIL']) && !empty($property['EMAIL']->value)) {
                        $email = $property['EMAIL']->value;
                    } else if (preg_match('/mailto:(?P<email>.*)/i', $property->value, $matches)) {
                        $email = $matches['email'];
                    }
                    
                    if ($email !== null) {
                        // it's not possible to change the organizer by spec
                        if (empty($event->organizer)) {
                            $name = isset($property['CN']) ? $property['CN']->value : $email;
                            $contact = Calendar_Model_Attender::resolveEmailToContact(array(
                                'email'     => $email,
                                'lastName'  => $name,
                            ));
                        
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
                    $event->recurid = $this->_convertToTinebaseDateTime($property);
                    
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
                    foreach($property as $valarm) {
                        
                        if ($valarm->ACTION == 'NONE') {
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                                . ' We can\'t cope with action NONE: iCal 6.0 sends default alarms in the year 1976 with action NONE. Skipping alarm.');
                            continue;
                        }
                        
                        if (! is_object($valarm->TRIGGER['VALUE'])) {
                            // @see 0006110: handle iMIP messages from outlook
                            // @todo fix 0007446: handle broken alarm in outlook invitation message
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                                . ' Alarm has no TRIGGER value. Skipping it.');
                            continue;
                        }
                        
                        switch (strtoupper($valarm->TRIGGER['VALUE']->value)) {
                            # TRIGGER;VALUE=DATE-TIME:20111031T130000Z
                            case 'DATE-TIME':
                                //@TODO fixme
                                $alarmTime = new Tinebase_DateTime($valarm->TRIGGER->value);
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
                                $alarmTime = $this->_convertToTinebaseDateTime($_vevent->DTSTART);
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
                    $lastAck = $this->_convertToTinebaseDateTime($property);
                    break;
                    
                case 'X-MOZ-SNOOZE-TIME':
                    $snoozeTime = $this->_convertToTinebaseDateTime($property);
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
     * get datetime from sabredav datetime property (user TZ is fallback)
     * 
     * @param Sabre_VObject_Element_DateTime $dateTime
     * @param boolean $_useUserTZ
     * @return Tinebase_DateTime
     * 
     * @todo try to guess some common timezones
     */
    protected function _convertToTinebaseDateTime(Sabre_VObject_Element_DateTime $dateTimeProperty, $_useUserTZ = FALSE)
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set((string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $dateTime = $dateTimeProperty->getDateTime();
        
        // convert to Tinebase_DateTime
        date_default_timezone_set($defaultTimezone);
        
        $tz = ($_useUserTZ) ? (string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE) : $dateTime->getTimezone();
        $result = new Tinebase_DateTime($dateTime->format(Tinebase_Record_Abstract::ISO8601LONG), $tz);
        
        return $result;
    }
}
