<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * abstract class to convert a single event (repeating with exceptions) to/from VCalendar
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
    
    protected $_supportedFields = array();
    
    protected $_version;
    
    /**
     * value of METHOD property
     * @var string
     */
    protected $_method;
    
    /**
     * @param  string  $version  the version of the client
     */
    public function __construct($version = null)
    {
        $this->_version = $version;
    }
    
    /**
     * convert Tinebase_Record_RecordSet to Sabre\VObject\Component
     *
     * @param  Tinebase_Record_RecordSet  $_records
     * @return Sabre\VObject\Component
     */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Events: ' . print_r($_records->toArray(), true));
        
        // required vcalendar fields
        $version = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->version;
        
        $vcalendar = new \Sabre\VObject\Component\VCalendar(array(
            'PRODID'   => "-//tine20.com//Tine 2.0 Calendar V$version//EN",
            'VERSION'  => '2.0',
            'CALSCALE' => 'GREGORIAN'
        ));
        
        if (isset($this->_method)) {
            $vcalendar->add('METHOD', $this->_method);
        }
        
        $originatorTz = $_records->getFirstRecord() ? $_records->getFirstRecord()->originator_tz : NULL;
        if (empty($originatorTz)) {
            throw new Tinebase_Exception_Record_Validation('originator_tz needed for conversion to Sabre\VObject\Component');
        }
        
        $vcalendar->add(new Sabre_VObject_Component_VTimezone($originatorTz));
        
        foreach ($_records as $_record) {
            $this->_convertCalendarModelEvent($vcalendar, $_record);
            
            if ($_record->exdate instanceof Tinebase_Record_RecordSet) {
                $_record->exdate->addIndices(array('is_deleted'));
                $eventExceptions = $_record->exdate->filter('is_deleted', false);
                
                foreach ($eventExceptions as $eventException) {
                    $this->_convertCalendarModelEvent($vcalendar, $eventException, $_record);
                }
                
            }
        }
        
        $this->_afterFromTine20Model($vcalendar);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' card ' . $vcalendar->serialize());
        
        return $vcalendar;
    }

    /**
     * convert Calendar_Model_Event to Sabre\VObject\Component
     *
     * @param  Calendar_Model_Event  $_record
     * @return Sabre\VObject\Component
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        $_records = new Tinebase_Record_RecordSet(get_class($_record), array($_record), true, false);
        
        return $this->fromTine20RecordSet($_records);
    }
    
    /**
     * convert calendar event to Sabre\VObject\Component
     * 
     * @param  \Sabre\VObject\Component\VCalendar $vcalendar
     * @param  Calendar_Model_Event               $_event
     * @param  Calendar_Model_Event               $_mainEvent
     */
    protected function _convertCalendarModelEvent(\Sabre\VObject\Component\VCalendar $vcalendar, Calendar_Model_Event $_event, Calendar_Model_Event $_mainEvent = null)
    {
        // clone the event and change the timezone
        $event = clone $_event;
        $event->setTimezone($event->originator_tz);
        
        $lastModifiedDateTime = $_event->last_modified_time ? $_event->last_modified_time : $_event->creation_time;
        if (! $event->creation_time instanceof Tinebase_DateTime) {
            throw new Tinebase_Exception_Record_Validation('creation_time needed for conversion to Sabre\VObject\Component');
        }
        
        $vevent = $vcalendar->create('VEVENT', array(
            'CREATED'       => $_event->creation_time->getClone()->setTimezone('UTC'),
            'LAST-MODIFIED' => $lastModifiedDateTime->getClone()->setTimezone('UTC'),
            'DTSTAMP'       => Tinebase_DateTime::now(),
            'UID'           => $event->uid,
            'SEQUENCE'      => $event->seq
        ));

        if ($event->isRecurException()) {
            $originalDtStart = $_event->getOriginalDtStart()->setTimezone($_event->originator_tz);
            
            $recurrenceId = $vevent->add('RECURRENCE-ID', $originalDtStart);
            
            if ($_mainEvent && $_mainEvent->is_all_day_event == true) {
                $recurrenceId['VALUE'] = 'DATE';
            }
        }
        
        // dtstart and dtend
        $dtstart = $vevent->add('DTSTART', $_event->dtstart->getClone()->setTimezone($event->originator_tz));
        
        if ($event->is_all_day_event == true) {
            $dtstart['VALUE'] = 'DATE';
            
            // whole day events ends at 23:59:(00|59) in Tine 2.0 but 00:00 the next day in vcalendar
            $event->dtend->addSecond($event->dtend->get('s') == 59 ? 1 : 0);
            $event->dtend->addMinute($event->dtend->get('i') == 59 ? 1 : 0);
            
            $dtend = $vevent->add('DTEND', $event->dtend);
            $dtend['VALUE'] = 'DATE';
        } else {
            $dtend = $vevent->add('DTEND', $event->dtend);
        }
        
        // auto status for deleted events
        if ($event->is_deleted) {
            $event->status = Calendar_Model_Event::STATUS_CANCELED;
        }
        
        // event organizer
        if (!empty($event->organizer)) {
            $organizerContact = $event->resolveOrganizer();

            if ($organizerContact instanceof Addressbook_Model_Contact && !empty($organizerContact->email)) {
                $organizer = $vevent->add(
                    'ORGANIZER', 
                    'mailto:' . $organizerContact->email, 
                    array('CN' => $organizerContact->n_fileas, 'EMAIL' => $organizerContact->email)
                );
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
                $vevent->add(strtoupper($property), $event->$property);
            }
        }
        
        // categories
        if(isset($event->tags) && count($event->tags) > 0) {
            $vevent->add('CATEGORIES', (array) $event->tags->name);
        }
        
        // repeating event properties
        if ($event->rrule) {
            if ($event->is_all_day_event == true) {
                $vevent->add('RRULE', preg_replace_callback('/UNTIL=([\d :-]{19})(?=;?)/', function($matches) {
                    $dtUntil = new Tinebase_DateTime($matches[1]);
                    $dtUntil->setTimezone((string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                    
                    return 'UNTIL=' . $dtUntil->format('Ymd');
                }, $event->rrule));
            } else {
                $vevent->add('RRULE', preg_replace('/(UNTIL=)(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', '$1$2$3$4T$5$6$7Z', $event->rrule));
            }
            
            if ($event->exdate instanceof Tinebase_Record_RecordSet) {
                $event->exdate->addIndices(array('is_deleted'));
                $deletedEvents = $event->exdate->filter('is_deleted', true);
                
                foreach ($deletedEvents as $deletedEvent) {
                    $dateTime = $deletedEvent->getOriginalDtStart();

                    $exdate = $vevent->add('EXDATE');
                    
                    if ($event->is_all_day_event == true) {
                        $dateTime->setTimezone($event->originator_tz);
                        $exdate['VALUE'] = 'DATE';
                    }
                    
                    $exdate->setValue($dateTime);
                }
            }
        }
        
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($event->attendee);
        
        if ($event->alarms instanceof Tinebase_Record_RecordSet) {
            $mozLastAck = NULL;
            $mozSnooze = NULL;
            
            foreach ($event->alarms as $alarm) {
                $valarm = $vcalendar->create('VALARM');
                $valarm->add('ACTION', 'DISPLAY');
                $valarm->add('DESCRIPTION', $event->summary);
                
                if ($dtack = Calendar_Controller_Alarm::getAcknowledgeTime($alarm)) {
                    $valarm->add('ACKNOWLEDGED', $dtack->getClone()->setTimezone('UTC')->format('Ymd\\THis\\Z'));
                    $mozLastAck = $dtack > $mozLastAck ? $dtack : $mozLastAck;
                }
                
                if ($dtsnooze = Calendar_Controller_Alarm::getSnoozeTime($alarm)) {
                    $mozSnooze = $dtsnooze > $mozSnooze ? $dtsnooze : $mozSnooze;
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
                    $trigger = $valarm->add('TRIGGER', $periodString);
                    $trigger['VALUE'] = "DURATION";
                } else {
                    # TRIGGER;VALUE=DATE-TIME:...
                    $trigger = $valarm->add('TRIGGER', $alarm->alarm_time->getClone()->setTimezone('UTC')->format('Ymd\\THis\\Z'));
                    $trigger['VALUE'] = "DATE-TIME";
                }
                
                $vevent->add($valarm);
            }
            
            if ($mozLastAck instanceof DateTime) {
                $vevent->add('X-MOZ-LASTACK', $mozLastAck->getClone()->setTimezone('UTC'), array('VALUE' => 'DATE-TIME'));
            }
            
            if ($mozSnooze instanceof DateTime) {
                $vevent->add('X-MOZ-SNOOZE-TIME', $mozSnooze->getClone()->setTimezone('UTC'), array('VALUE' => 'DATE-TIME'));
            }
        }
        
        $vcalendar->add($vevent);
    }
    
    /**
     * add event attendee to VEVENT object 
     * 
     * @param \Sabre\VObject\Component\VEvent $vevent
     * @param Calendar_Model_Event            $event
     */
    protected function _addEventAttendee(\Sabre\VObject\Component\VEvent $vevent, Calendar_Model_Event $event)
    {
        if (empty($event->attendee)) {
            return;
        }
        
        Calendar_Model_Attender::resolveAttendee($event->attendee, FALSE, $event);
        
        foreach($event->attendee as $eventAttendee) {
            $attendeeEmail = $eventAttendee->getEmail();
            
            $parameters = array(
                'CN'       => $eventAttendee->getName(),
                'CUTYPE'   => Calendar_Convert_Event_VCalendar_Abstract::$cutypeMap[$eventAttendee->user_type],
                'PARTSTAT' => $eventAttendee->status,
                'ROLE'     => "{$eventAttendee->role}-PARTICIPANT",
                'RSVP'     => 'FALSE'
            );
            if (strpos($attendeeEmail, '@') !== false) {
                $parameters['EMAIL'] = $attendeeEmail;
            }
            $vevent->add('ATTENDEE', (strpos($attendeeEmail, '@') !== false ? 'mailto:' : 'urn:uuid:') . $attendeeEmail, $parameters);
        }
    }
    
    /**
     * can be overwriten in extended class to modify/cleanup $_vcalendar
     * 
     * @param \Sabre\VObject\Component\VCalendar $vcalendar
     */
    protected function _afterFromTine20Model(\Sabre\VObject\Component\VCalendar $vcalendar)
    {
    }
    
    /**
     * set the METHOD for the generated VCALENDAR
     *
     * @param  string  $_method  the method
     */
    public function setMethod($method)
    {
        $this->_method = $method;
    }
    
    /**
     * converts vcalendar to Calendar_Model_Event
     * 
     * @param  mixed                 $_blob   the VCALENDAR to parse
     * @param  Calendar_Model_Event  $_record  update existing event
     * @return Calendar_Model_Event
     */
    public function toTine20Model($blob, Tinebase_Record_Abstract $_record = null)
    {
        $vcalendar = self::getVObject($blob);
        
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
        
        if (isset($vcalendar->METHOD)) {
            $this->setMethod($vcalendar->METHOD);
        }
        
        // find the main event - the main event has no RECURRENCE-ID
        $baseVevent = null;
        foreach ($vcalendar->VEVENT as $vevent) {
            if (! isset($vevent->{'RECURRENCE-ID'})) {
                $this->_convertVevent($vevent, $event);
                $baseVevent = $vevent;
                
                break;
            }
        }

        // TODO check if this is correct! spec?
        // @see 0009510: is it allowed to have no main vevent in ics?
        // if we have found no VEVENT component or something went wrong, lets stop here
        if (! $baseVevent) {
            throw new Tinebase_Exception_UnexpectedValue('no main VEVENT component found in VCALENDAR');
        }
        
        // TODO only do this for events with rrule?
        // if (! empty($event->rrule)) {
        $this->_parseEventExceptions($event, $vcalendar, $baseVevent);
        $event->isValid(true);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($event->toArray(), true));
        
        return $event;
    }
    
    /**
     * parse event exceptions and add them to Tine 2.0 event record
     * 
     * @param  Calendar_Model_Event                $event
     * @param  \Sabre\VObject\Component\VCalendar  $vcalendar
     * @param  \Sabre\VObject\Component\VCalendar  $baseVevent
     */
    protected function _parseEventExceptions(Calendar_Model_Event $event, \Sabre\VObject\Component\VCalendar $vcalendar, $baseVevent = null)
    {
        $oldExdates = $event->exdate instanceof Tinebase_Record_RecordSet ? $event->exdate->filter('is_deleted', false) : new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        foreach ($vcalendar->VEVENT as $vevent) {
            if (isset($vevent->{'RECURRENCE-ID'}) && $event->uid == $vevent->UID) {
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
                
                if ($baseVevent) {
                    $this->_adaptBaseEventProperties($vevent, $baseVevent);
                }
                
                $this->_convertVevent($vevent, $recurException);
                
                if (! $event->exdate instanceof Tinebase_Record_RecordSet) {
                    $event->exdate = new Tinebase_Record_RecordSet('Calendar_Model_Event');
                }
                $event->exdate->addRecord($recurException);
            }
        }
    }
    
    /**
     * adapt X-MOZ-LASTACK / X-MOZ-SNOOZE-TIME from base vevent
     * 
     * @see 0009396: alarm_ack_time and alarm_snooze_time are not updated
     */
    protected function _adaptBaseEventProperties($vevent, $baseVevent)
    {
        $propertiesToAdapt = array('X-MOZ-LASTACK', 'X-MOZ-SNOOZE-TIME');
        
        foreach ($propertiesToAdapt as $property) {
            if (isset($baseVevent->{$property})) {
                $vevent->{$property} = $baseVevent->{$property};
            }
        }
    }
    
    /**
     * convert VCALENDAR to Tinebase_Record_RecordSet of Calendar_Model_Event
     * 
     * @param  mixed  $blob  the vcalendar to parse
     * @return Tinebase_Record_RecordSet
     */
    public function toTine20RecordSet($blob)
    {
        $vcalendar = self::getVObject($blob);
        
        $result = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        foreach ($vcalendar->VEVENT as $vevent) {
            if (! isset($vevent->{'RECURRENCE-ID'})) {
                $event = new Calendar_Model_Event();
                $this->_convertVevent($vevent, $event);
                if (! empty($event->rrule)) {
                    $this->_parseEventExceptions($event, $vcalendar);
                }
                $result->addRecord($event);
            }
        }
        
        return $result;
    }
    
    /**
     * returns VObject of input data
     * 
     * @param   mixed  $blob
     * @return  \Sabre\VObject\Component\VCalendar
     */
    public static function getVObject($blob)
    {
        if ($blob instanceof \Sabre\VObject\Component\VCalendar) {
            return $blob;
        }
        
        if (is_resource($blob)) {
            $blob = stream_get_contents($blob);
        }
        
        $vcalendar = self::readVCalBlob($blob);
        
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
     * @throws Sabre\VObject\ParseException
     * @return Sabre\VObject\Component\VCalendar
     * 
     * @see 0006110: handle iMIP messages from outlook
     * 
     * @todo maybe we can remove this when #7438 is resolved
     */
    public static function readVCalBlob($blob, $failcount = 0, $spacecount = 0, $lastBrokenLineNumber = 0, $lastLines = array())
    {
        // convert to utf-8
        $blob = mbConvertTo($blob);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' ' . $blob);
        
        try {
            $vcalendar = \Sabre\VObject\Reader::read($blob);
        } catch (Sabre\VObject\ParseException $svpe) {
            // NOTE: we try to repair\Sabre\VObject\Reader as it fails to detect followup lines that do not begin with a space or tab
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
    
    /**
     * get METHOD of current VCALENDAR or supplied blob
     * 
     * @param  string  $blob
     * @return string|NULL
     */
    public function getMethod($blob = NULL)
    {
        if ($this->_method) {
            return $this->_method;
        }
        
        if ($blob !== NULL) {
            $vcalendar = self::getVObject($blob);
            return $vcalendar->METHOD;
        }
        
        return null;
    }

    /**
     * find a matching exdate or return an empty event record
     * 
     * @param  Tinebase_Record_RecordSet        $oldExdates
     * @param  \Sabre\VObject\Component\VEvent  $vevent
     * @return Calendar_Model_Event
     */
    protected function _getRecurException(Tinebase_Record_RecordSet $oldExdates,Sabre\VObject\Component\VEvent $vevent)
    {
        $exDate = clone $vevent->{'RECURRENCE-ID'}->getDateTime();
        $exDate->setTimeZone(new DateTimeZone('UTC'));
        $exDateString = $exDate->format('Y-m-d H:i:s');
        
        foreach ($oldExdates as $id => $oldExdate) {
            if ($exDateString == substr((string) $oldExdate->recurid, -19)) {
                unset($oldExdates[$id]);
                
                return $oldExdate;
            }
        }
        
        return new Calendar_Model_Event();
    }
    
    /**
     * get attendee array for given contact
     * 
     * @param  \Sabre\VObject\Property\ICalendar\CalAddress  $calAddress  the attendee row from the vevent object
     * @return array
     */
    protected function _getAttendee(\Sabre\VObject\Property\ICalendar\CalAddress $calAddress)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' attendee ' . $calAddress->serialize());
        
        if (isset($calAddress['CUTYPE']) && in_array($calAddress['CUTYPE']->getValue(), array('INDIVIDUAL', Calendar_Model_Attender::USERTYPE_GROUP, Calendar_Model_Attender::USERTYPE_RESOURCE))) {
            $type = $calAddress['CUTYPE']->getValue() == 'INDIVIDUAL' ? Calendar_Model_Attender::USERTYPE_USER : $calAddress['CUTYPE']->getValue();
        } else {
            $type = Calendar_Model_Attender::USERTYPE_USER;
        }
        
        if (isset($calAddress['ROLE']) && in_array($calAddress['ROLE']->getValue(), array(Calendar_Model_Attender::ROLE_OPTIONAL, Calendar_Model_Attender::ROLE_REQUIRED))) {
            $role = $calAddress['ROLE']->getValue();
        } else {
            $role = Calendar_Model_Attender::ROLE_REQUIRED;
        }
        
        if (isset($calAddress['PARTSTAT']) && in_array($calAddress['PARTSTAT']->getValue(), array(
            Calendar_Model_Attender::STATUS_ACCEPTED,
            Calendar_Model_Attender::STATUS_DECLINED,
            Calendar_Model_Attender::STATUS_NEEDSACTION,
            Calendar_Model_Attender::STATUS_TENTATIVE
        ))) {
            $status = $calAddress['PARTSTAT']->getValue();
        } else {
            $status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        }
        
        if (!empty($calAddress['EMAIL'])) {
            $email = $calAddress['EMAIL']->getValue();
        } else {
            if (!preg_match('/(?P<protocol>mailto:|urn:uuid:)(?P<email>.*)/i', $calAddress->getValue(), $matches)) {
                throw new Tinebase_Exception_UnexpectedValue('invalid attendee provided: ' . $calAddress->getValue());
            }
            $email = $matches['email'];
        }
        
        $fullName = isset($calAddress['CN']) ? $calAddress['CN']->getValue() : $email;
        
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
     * @param  \Sabre\VObject\Component\VEvent  $vevent  the VEVENT to parse
     * @param  Calendar_Model_Event             $event   the Tine 2.0 event to update
     */
    protected function _convertVevent(\Sabre\VObject\Component\VEvent $vevent, Calendar_Model_Event $event)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' vevent ' . $vevent->serialize());
        
        $newAttendees = array();
        
        // unset supported fields
        foreach ($this->_supportedFields as $field) {
            if ($field == 'alarms') {
                $event->$field = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
            } else {
                $event->$field = null;
            }
        }
        
        foreach ($vevent->children() as $property) {
            switch ($property->name) {
                case 'CREATED':
                case 'DTSTAMP':
                    // do nothing
                    break;
                    
                case 'LAST-MODIFIED':
                    $event->last_modified_time = new Tinebase_DateTime($property->getValue());
                    break;
                
                case 'ATTENDEE':
                    $newAttendee = $this->_getAttendee($property);
                    if ($newAttendee) {
                        $newAttendees[] = $newAttendee;
                    }
                    break;
                    
                case 'CLASS':
                    if (in_array($property->getValue(), array(Calendar_Model_Event::CLASS_PRIVATE, Calendar_Model_Event::CLASS_PUBLIC))) {
                        $event->class = $property->getValue();
                    } else {
                        $event->class = Calendar_Model_Event::CLASS_PUBLIC;
                    }
                    
                    break;
                    
                case 'STATUS':
                    if (in_array($property->getValue(), array(Calendar_Model_Event::STATUS_CONFIRMED, Calendar_Model_Event::STATUS_TENTATIVE, Calendar_Model_Event::STATUS_CANCELED))) {
                        $event->status = $property->getValue();
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
                    $event->seq = $property->getValue();
                    break;
                    
                case 'DESCRIPTION':
                case 'LOCATION':
                case 'UID':
                case 'SUMMARY':
                    $key = strtolower($property->name);
                    $event->$key = $property->getValue();
                    break;
                    
                case 'ORGANIZER':
                    $email = null;
                    
                    if (!empty($property['EMAIL'])) {
                        $email = $property['EMAIL'];
                    } elseif (preg_match('/mailto:(?P<email>.*)/i', $property->getValue(), $matches)) {
                        $email = $matches['email'];
                    }
                    
                    if ($email !== null) {
                        // it's not possible to change the organizer by spec
                        if (empty($event->organizer)) {
                            $name = isset($property['CN']) ? $property['CN']->getValue() : $email;
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
                    $rruleString = $property->getValue();
                    
                    // convert date format
                    $rruleString = preg_replace_callback('/UNTIL=([\dTZ]+)(?=;?)/', function($matches) {
                        if (strlen($matches[1]) < 10) {
                            $dtUntil = date_create($matches[1], new DateTimeZone ((string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE)));
                            $dtUntil->setTimezone(new DateTimeZone('UTC'));
                        } else {
                            $dtUntil = date_create($matches[1]);
                        }
                        
                        return 'UNTIL=' . $dtUntil->format(Tinebase_Record_Abstract::ISO8601LONG);
                    }, $rruleString);

                    // remove additional days from BYMONTHDAY property (BYMONTHDAY=11,15 => BYMONTHDAY=11)
                    $rruleString = preg_replace('/(BYMONTHDAY=)([\d]+),([,\d]+)/', '$1$2', $rruleString);
                    
                    $event->rrule = $rruleString;
                    
                    // process exceptions
                    if (isset($vevent->EXDATE)) {
                        $exdates = new Tinebase_Record_RecordSet('Calendar_Model_Event');
                        
                        foreach ($vevent->EXDATE as $exdate) {
                            foreach ($exdate->getDateTimes() as $exception) {
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
                    if (in_array($property->getValue(), array(Calendar_Model_Event::TRANSP_OPAQUE, Calendar_Model_Event::TRANSP_TRANSP))) {
                        $event->transp = $property->getValue();
                    } else {
                        $event->transp = Calendar_Model_Event::TRANSP_TRANSP;
                    }
                    
                    break;
                    
                case 'UID':
                    // it's not possible to change the uid by spec
                    if (!empty($event->uid)) {
                        continue;
                    }
                    
                    $event->uid = $property->getValue();
                
                    break;
                    
                case 'VALARM':
                    foreach ($property as $valarm) {
                        
                        if ($valarm->ACTION == 'NONE') {
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                                . ' We can\'t cope with action NONE: iCal 6.0 sends default alarms in the year 1976 with action NONE. Skipping alarm.');
                            continue;
                        }
                        
                        # TRIGGER:-PT15M
                        if (is_string($valarm->TRIGGER->getValue()) && $valarm->TRIGGER instanceof Sabre\VObject\Property\ICalendar\Duration) {
                            $valarm->TRIGGER->add('VALUE', 'DURATION');
                        }
                        
                        $trigger = is_object($valarm->TRIGGER['VALUE']) ? $valarm->TRIGGER['VALUE'] : (is_object($valarm->TRIGGER['RELATED']) ? $valarm->TRIGGER['RELATED'] : NULL);
                        
                        if ($trigger === NULL) {
                            // added Trigger/Related for eM Client alarms
                            // 2014-01-03 - Bullshit, why don't we have testdata for emclient alarms?
                            //              this alarm handling should be refactored, the logic is scrambled
                            // @see 0006110: handle iMIP messages from outlook
                            // @todo fix 0007446: handle broken alarm in outlook invitation message
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                                . ' Alarm has no TRIGGER value. Skipping it.');
                            continue;
                        }
                        
                        switch (strtoupper($trigger->getValue())) {
                            # TRIGGER;VALUE=DATE-TIME:20111031T130000Z
                            case 'DATE-TIME':
                                $alarmTime = new Tinebase_DateTime($valarm->TRIGGER->getValue());
                                $alarmTime->setTimezone('UTC');
                                
                                $alarm = new Tinebase_Model_Alarm(array(
                                    'alarm_time'        => $alarmTime,
                                    'minutes_before'    => 'custom',
                                    'model'             => 'Calendar_Model_Event'
                                ));
                                
                                break;
                                
                            # TRIGGER;VALUE=DURATION:-PT1H15M
                            case 'DURATION':
                            default:
                                $alarmTime = $this->_convertToTinebaseDateTime($vevent->DTSTART);
                                $alarmTime->setTimezone('UTC');
                                
                                preg_match('/(?P<invert>[+-]?)(?P<spec>P.*)/', $valarm->TRIGGER->getValue(), $matches);
                                $duration = new DateInterval($matches['spec']);
                                $duration->invert = !!($matches['invert'] === '-');

                                $alarm = new Tinebase_Model_Alarm(array(
                                    'alarm_time'        => $alarmTime->add($duration),
                                    'minutes_before'    => ($duration->format('%d') * 60 * 24) + ($duration->format('%h') * 60) + ($duration->format('%i')),
                                    'model'             => 'Calendar_Model_Event'
                                ));
                                
                                break;
                        }
                        
                        if ($valarm->ACKNOWLEDGED) {
                            $dtack = $valarm->ACKNOWLEDGED->getDateTime();
                            Calendar_Controller_Alarm::setAcknowledgeTime($alarm, $dtack);
                        }
                        
                        $event->alarms->addRecord($alarm);
                    }
                    
                    break;
                    
                case 'CATEGORIES':
                    $event->tags = Tinebase_Model_Tag::resolveTagNameToTag($property->getParts(), 'Calendar');
                    break;
                    
                case 'X-MOZ-LASTACK':
                    $lastAck = $this->_convertToTinebaseDateTime($property);
                    break;
                    
                case 'X-MOZ-SNOOZE-TIME':
                    $snoozeTime = $this->_convertToTinebaseDateTime($property);
                    break;
                    
                default:
                    // thunderbird saves snooze time for recurring event occurrences in properties with names like this -
                    // we just assume that the event/recur series has only one snooze time 
                    if (preg_match('/^X-MOZ-SNOOZE-TIME-[0-9]+$/', $property->name)) {
                        $snoozeTime = $this->_convertToTinebaseDateTime($property);
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                            . ' Found snooze time for recur occurrence: ' . $snoozeTime->toString());
                    }
                    break;
            }
        }
        
        if (isset($lastAck)) {
            Calendar_Controller_Alarm::setAcknowledgeTime($event->alarms, $lastAck);
        }
        if (isset($snoozeTime)) {
            Calendar_Controller_Alarm::setSnoozeTime($event->alarms, $snoozeTime);
        }
        
        // merge old and new attendee
        Calendar_Model_Attender::emailsToAttendee($event, $newAttendees);
        
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
     * @param  Sabre\VObject\Property  $dateTimeProperty
     * @param  boolean                 $_useUserTZ
     * @return Tinebase_DateTime
     * 
     * @todo try to guess some common timezones
     */
    protected function _convertToTinebaseDateTime(\Sabre\VObject\Property $dateTimeProperty, $_useUserTZ = FALSE)
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set((string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        
        if ($dateTimeProperty instanceof Sabre\VObject\Property\ICalendar\DateTime) {
            $dateTime = $dateTimeProperty->getDateTime();
            $tz = ($_useUserTZ || (isset($dateTimeProperty['VALUE']) && strtoupper($dateTimeProperty['VALUE']) == 'DATE')) ? 
                (string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE) : 
                $dateTime->getTimezone();
            
            $result = new Tinebase_DateTime($dateTime->format(Tinebase_Record_Abstract::ISO8601LONG), $tz);
        } else {
            $result = new Tinebase_DateTime($dateTimeProperty->getValue());
        }
        
        date_default_timezone_set($defaultTimezone);
        
        return $result;
    }
}
