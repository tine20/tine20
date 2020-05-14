<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * abstract class to convert a single event (repeating with exceptions) to/from VCalendar
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_Abstract extends Tinebase_Convert_VCalendar_Abstract implements Tinebase_Convert_Interface
{
    /**
     * add attachment content as binary base64 encoded string
     * @const
     */
    const OPTION_ADD_ATTACHMENTS_BINARY = 'addAttachmentsBinary';
    /**
     * add attachment url
     * @const
     */
    const OPTION_ADD_ATTACHMENTS_URL = 'addAttachmentsURL';

    public static $cutypeMap = array(
        Calendar_Model_Attender::USERTYPE_USER          => 'INDIVIDUAL',
        Calendar_Model_Attender::USERTYPE_GROUPMEMBER   => 'INDIVIDUAL',
        Calendar_Model_Attender::USERTYPE_GROUP         => 'GROUP',
        Calendar_Model_Attender::USERTYPE_RESOURCE      => 'RESOURCE',
    );
    
    protected $_modelName = 'Calendar_Model_Event';

    /**
     * value of METHOD property
     * @var string
     */
    protected $_method;

    /**
     * @var Calendar_Model_Attender
     */
    protected $_calendarUser = NULL;

    /**
     * options array
     * @var array
     * 
     * TODO allow more options
     * 
     * current options:
     *  - onlyBasicData (only use basic event data when converting from VCALENDAR to Tine 2.0)
     *  - addAttachmentsURL
     */
    protected $_options = array();
    
    /**
     * set options
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->_options = $options;
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
     * @param Tinebase_Record_Interface $_record
     * @return \Sabre\VObject\Component\VCalendar
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_Validation
     */
    public function createVCalendar(Tinebase_Record_Interface $_record)
    {
        // required vcalendar fields
        $version = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->version;

        $vcalendar = new \Sabre\VObject\Component\VCalendar(array(
            'PRODID'   => "-//tine20.com//Tine 2.0 Calendar V$version//EN",
            'VERSION'  => '2.0',
            'CALSCALE' => 'GREGORIAN'
        ));

        $originatorTz = $_record ? $_record->originator_tz : NULL;
        if (empty($originatorTz)) {
            throw new Tinebase_Exception_Record_Validation('originator_tz needed for conversion to Sabre\VObject\Component');
        }

        try {
            $vcalendar->add(new Sabre_VObject_Component_VTimezone($originatorTz));
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            throw new Tinebase_Exception_Record_Validation('Bad Timezone: ' . $originatorTz);
        }

        if (isset($this->_method)) {
            $vcalendar->add('METHOD', $this->_method);
        }

        return $vcalendar;
    }

    /**
     * convert Tinebase_Record_RecordSet to Sabre\VObject\Component
     *
     * @param  Tinebase_Record_RecordSet  $_records
     * @return Sabre\VObject\Component
     */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records)
    {
        $vcalendar = $this->createVCalendar($_records->getFirstRecord());

        foreach ($_records as $record) {
            $this->addEventToVCalendar($vcalendar, $record);
        }
        
        $this->_afterFromTine20Model($vcalendar);
        
        return $vcalendar;
    }

    /**
     * @param \Sabre\VObject\Component\VCalendar $vcalendar
     * @param Calendar_Model_Event $event
     * @throws Tinebase_Exception_Record_Validation
     */
    public function addEventToVCalendar(\Sabre\VObject\Component\VCalendar $vcalendar, Calendar_Model_Event $event)
    {
        $this->_convertCalendarModelEvent($vcalendar, $event);

        if ($event->exdate instanceof Tinebase_Record_RecordSet) {
            $event->exdate->addIndices(array('is_deleted'));
            $eventExceptions = $event->exdate->filter('is_deleted', false);

            foreach ($eventExceptions as $eventException) {
                $this->_convertCalendarModelEvent($vcalendar, $eventException, $event);
            }
        }
    }

    /**
     * convert Calendar_Model_Event to Sabre\VObject\Component
     *
     * @param  Calendar_Model_Event  $_record
     * @return Sabre\VObject\Component
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
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

        /** @var \Sabre\VObject\Component\VEvent $vevent */
        $vevent = $vcalendar->create('VEVENT', array(
            'CREATED'       => $_event->creation_time->getClone()->setTimezone('UTC'),
            'LAST-MODIFIED' => $lastModifiedDateTime->getClone()->setTimezone('UTC'),
            'DTSTAMP'       => Tinebase_DateTime::now(),
            'UID'           => (!$_mainEvent && $event->isRecurException()) ? $event->getId() : $event->uid,
        ));
        
        $vevent->add('SEQUENCE', $event->hasExternalOrganizer() ? $event->external_seq : $event->seq);
        
        if (null !== $_mainEvent) {
            if (! $event->isRecurException()) {
                Tinebase_Exception::log(new Tinebase_Exception_UnexpectedValue('event ' . $event->getId() .
                    'is not a recure exception though a mainEvent was passed along'));
                return;
            }

            $originalDtStart = $_event->getOriginalDtStart()->setTimezone($_event->originator_tz);
            
            $recurrenceId = $vevent->add('RECURRENCE-ID', $originalDtStart);
            
            if ($_mainEvent->is_all_day_event == true) {
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

        $class = $event->class == Calendar_Model_Event::CLASS_PUBLIC ? 'PUBLIC' : 'CONFIDENTIAL';
        $vevent->add('X-CALENDARSERVER-ACCESS', $class);
        if (! $_mainEvent && $vcalendar->{'X-CALENDARSERVER-ACCESS'} === null) {
            // add one time only
            $vcalendar->add('X-CALENDARSERVER-ACCESS', $class);
        }

        // categories
        if (!isset($event->tags)) {
            $event->tags = Tinebase_Tags::getInstance()->getTagsOfRecord($event);
        }
        if (count($event->tags) > 0 && $event->tags instanceof Tinebase_Record_RecordSet) {
            $vevent->add('CATEGORIES', (array) $event->tags->name);
        }
        
        // repeating event properties
        if ($event->rrule) {
            $event->rrule = $event->rrule instanceof Calendar_Model_Rrule ? $event->rrule : Calendar_Model_Rrule::getRruleFromString($event->rrule);
            $event->rrule->setTimezone('UTC');
            if ($event->is_all_day_event == true) {
                $vevent->add('RRULE', preg_replace_callback('/UNTIL=([\d :-]{19})(?=;?)/', function($matches) {
                    $dtUntil = new Tinebase_DateTime($matches[1], 'UTC');
                    $dtUntil->setTimezone((string) Tinebase_Core::getUserTimezone());
                    
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

        if (isset($event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES])) {
            $sabrePropertyParser = new Calendar_Convert_Event_VCalendar_SabrePropertyParser($vcalendar);
            foreach ($event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES] as $prop) {
                try {
                    $propObj = $sabrePropertyParser->parseProperty($prop);
                    $vevent->__set($propObj->name, $propObj);
                } catch (\Sabre\VObject\ParseException $svope) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ .
                        '::' . __LINE__ . ' failed adding events imip x-property: ' . $prop . ' -> ' .
                        $svope->getMessage());
                }
            }
        }
        
        if ($event->attachments instanceof Tinebase_Record_RecordSet) {
            $baseUrl = Tinebase_Core::getHostname() . "/webdav/Calendar/records/Calendar_Model_Event/{$event->getId()}/";
            foreach ($event->attachments as $attachment) {
                $filename = rawurlencode($attachment->name);
                if (isset($this->_options[self::OPTION_ADD_ATTACHMENTS_BINARY])
                    && $this->_options[self::OPTION_ADD_ATTACHMENTS_BINARY]
                ) {
                    $content = Tinebase_FileSystem::getInstance()->getNodeContents($attachment);
                    $value = base64_encode($content);
                    $attachmentData = [
                        'ENCODING' => 'BASE64',
                        'VALUE' => 'BINARY',
                        'FILENAME'   => $filename,
                    ];
                } else {
                    $value = "{$baseUrl}{$filename}";
                    $attachmentData = [
                        'MANAGED-ID' => $attachment->hash,
                        'FMTTYPE'    => $attachment->contenttype,
                        'SIZE'       => $attachment->size,
                        'FILENAME'   => $filename,
                    ];
                }
                $attach = $vcalendar->createProperty('ATTACH', $value, $attachmentData, 'TEXT');
                $vevent->add($attach);
            }
            if ($event->attachments->count()
                && isset($this->_options[self::OPTION_ADD_ATTACHMENTS_URL])
                && $this->_options[self::OPTION_ADD_ATTACHMENTS_URL]
            ) {
                $vevent->add($vcalendar->createProperty('URL', $baseUrl));
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
                'CUTYPE'   => $this->_getAttendeeCUType($eventAttendee),
                'PARTSTAT' => $eventAttendee->status,
                'ROLE'     => "{$eventAttendee->role}-PARTICIPANT",
                'RSVP'     => $eventAttendee->isSame($this->_calendarUser) ? 'TRUE' : 'FALSE',
            );
            if (strpos($attendeeEmail, '@') !== false) {
                $parameters['EMAIL'] = $attendeeEmail;
            }
            $vevent->add('ATTENDEE', (strpos($attendeeEmail, '@') !== false ? 'mailto:' : 'urn:uuid:') . $attendeeEmail, $parameters);
        }
    }

    /**
     * returns CUTYPE for given attendee
     *
     * @param Calendar_Model_Attender $eventAttendee
     * @return string
     */
    protected function _getAttendeeCUType($eventAttendee)
    {
        return Calendar_Convert_Event_VCalendar_Abstract::$cutypeMap[$eventAttendee->user_type];
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
     * @param  mixed                 $_blob    the VCALENDAR to parse
     * @param  Calendar_Model_Event  $_record  update existing event
     * @param  array                 $options  array of options
     * @return Calendar_Model_Event
     */
    public function toTine20Model($blob, Tinebase_Record_Interface $_record = null, $options = array())
    {
        $vcalendar = self::getVObject($blob);
        
        // contains the VCALENDAR any VEVENTS
        if (! isset($vcalendar->VEVENT)) {
            throw new Tinebase_Exception_UnexpectedValue('no vevents found');
        }
        
        // update a provided record or create a new one
        if ($_record instanceof Calendar_Model_Event) {
            $event = $_record;
            $existingDtStart = clone $event->dtstart;
        } else {
            $event = new Calendar_Model_Event(null, false);
        }
        
        if (isset($vcalendar->METHOD)) {
            $this->setMethod($vcalendar->METHOD);
        }
        
        $baseVevent = $this->_findMainEvent($vcalendar);
        
        if (! $baseVevent) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' No main VEVENT found');
            
            if (! $_record && count($vcalendar->VEVENT) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Convert recur exception without existing event using first VEVENT');
                $this->_convertVevent($vcalendar->VEVENT[0], $event, $options);
            }
        } else {
            $this->_convertVevent($baseVevent, $event, $options);
        }

        if (isset($existingDtStart)) {
            $options['dtStartDiff'] = $event->dtstart->getClone()->setTimezone($event->originator_tz)
            ->diff($existingDtStart->getClone()->setTimezone($event->originator_tz));
        }
        $this->_parseEventExceptions($event, $vcalendar, $baseVevent, $options);

        // check for removed exdates ?
        //   => this is also done by msEventFacade, lets skip it here

        $event->isValid(true);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Event: ' . print_r($event->toArray(), true));
        
        return $event;
    }
    
    /**
     * find the main event - the main event has no RECURRENCE-ID
     * 
     * @param \Sabre\VObject\Component\VCalendar $vcalendar
     * @return \Sabre\VObject\Component\VCalendar | null
     */
    protected function _findMainEvent(\Sabre\VObject\Component\VCalendar $vcalendar)
    {
        foreach ($vcalendar->VEVENT as $vevent) {
            if (! isset($vevent->{'RECURRENCE-ID'})) {
                return $vevent;
            }
        }
        
        return null;
    }
    
    /**
     * parse event exceptions and add them to Tine 2.0 event record
     * 
     * @param  Calendar_Model_Event                $event
     * @param  \Sabre\VObject\Component\VCalendar  $vcalendar
     * @param  \Sabre\VObject\Component\VCalendar  $baseVevent
     * @param  array                               $options
     */
    protected function _parseEventExceptions(Calendar_Model_Event $event, \Sabre\VObject\Component\VCalendar $vcalendar, $baseVevent = null, $options = array())
    {
        if (! $event->exdate instanceof Tinebase_Record_RecordSet) {
            $event->exdate = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        }
        $recurExceptions = $event->exdate->filter('is_deleted', false);
        
        foreach ($vcalendar->VEVENT as $vevent) {
            if (isset($vevent->{'RECURRENCE-ID'}) && $event->uid == $vevent->UID) {
                $recurException = $this->_getRecurException($recurExceptions, $vevent, $options);
                
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
                
                // initialize attachments from base event as clients may skip parameters like
                // name and content type and we can't backward relove them from managedId
                if ($event->attachments instanceof Tinebase_Record_RecordSet && 
                        ! $recurException->attachments instanceof Tinebase_Record_RecordSet) {
                    $recurException->attachments = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
                    foreach ($event->attachments as $attachment) {
                        $recurException->attachments->addRecord(new Tinebase_Model_Tree_Node(array(
                            'name'         => $attachment->name,
                            'type'         => Tinebase_Model_Tree_FileObject::TYPE_FILE,
                            'contenttype'  => $attachment->contenttype,
                            'hash'         => $attachment->hash,
                        ), true));
                    }
                }
                
                if ($baseVevent) {
                    $this->_adaptBaseEventProperties($vevent, $baseVevent);
                }
                
                $this->_convertVevent($vevent, $recurException, $options);
                
                if (! $recurException->getId()) {
                    $event->exdate->addRecord($recurException);
                }

                // remove 'processed' so we know which exceptions no longer exist
                $recurExceptions->removeRecord($recurException);
            }
        }

        // delete exceptions not longer in data
        foreach($recurExceptions as $noLongerExisting) {
            $toRemove = $event->exdate->getById($noLongerExisting->getId());
            if ($toRemove) {
                $event->exdate->removeRecord($toRemove);
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
     * template method
     * 
     * implement if client has support for sending attachments
     * 
     * @param Calendar_Model_Event          $event
     * @param Tinebase_Record_RecordSet     $attachments
     */
    protected function _manageAttachmentsFromClient($event, $attachments) {}
    
    /**
     * convert VCALENDAR to Tinebase_Record_RecordSet of Calendar_Model_Event
     * 
     * @param  mixed  $blob  the vcalendar to parse
     * @param  array  $options
     * @return Tinebase_Record_RecordSet
     */
    public function toTine20RecordSet($blob, $options = array())
    {
        $vcalendar = self::getVObject($blob);
        
        $result = new Tinebase_Record_RecordSet('Calendar_Model_Event');

        foreach ($vcalendar->VEVENT as $vevent) {
            if (! isset($vevent->{'RECURRENCE-ID'})) {
                $event = new Calendar_Model_Event();
                $this->_convertVevent($vevent, $event, $options);
                if (! empty($event->rrule)) {
                    $this->_parseEventExceptions($event, $vcalendar, $options);
                }
                $result->addRecord($event);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' Converted ' . count($result) . ' events from VCALENDAR blob.');
            }
        }
        
        return $result;
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
     * @param   array                           $options
     * @return Calendar_Model_Event
     */
    protected function _getRecurException(Tinebase_Record_RecordSet $oldExdates,Sabre\VObject\Component\VEvent $vevent, $options)
    {
        $exDate = $this->_convertToTinebaseDateTime($vevent->{'RECURRENCE-ID'});
        // dtstart might have been updated
        if (isset($options['dtStartDiff'])) {
            $exDate->modifyTime($options['dtStartDiff']);
        }
        $exDate->setTimezone('UTC');

        $exDateString = $exDate->format('Y-m-d H:i:s');

        foreach ($oldExdates as $id => $oldExdate) {
            if ($exDateString == substr((string) $oldExdate->recurid, -19)) {
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
            if (! preg_match('/(?P<protocol>mailto:|urn:uuid:)(?P<email>.*)/i', $calAddress->getValue(), $matches)) {
                if (preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $calAddress->getValue())) {
                    $email = $calAddress->getValue();
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) 
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' invalid attendee provided: ' . $calAddress->getValue());
                    return null;
                }
            } else {
                $email = $matches['email'];
            }
        }
        
        $fullName = isset($calAddress['CN']) ? $calAddress['CN']->getValue() : $email;
        
        $parsedName = Addressbook_Model_Contact::splitName($fullName);

        $attendee = array(
            'userType'  => $type,
            'firstName' => $parsedName['n_given'],
            'lastName'  => $parsedName['n_family'],
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
     * @param  array                            $options
     */
    protected function _convertVevent(\Sabre\VObject\Component\VEvent $vevent, Calendar_Model_Event $event, $options)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' vevent ' . $vevent->serialize());
        
        $newAttendees = array();
        $attachments = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        $skipFieldsIfOnlyBasicData = array('ATTENDEE', 'UID', 'ORGANIZER', 'VALARM', 'ATTACH', 'CATEGORIES');
        $imipProps = [];

        /** @var \Sabre\VObject\Property $property */
        foreach ($vevent->children() as $property) {
            if (isset($this->_options['onlyBasicData'])
                && $this->_options['onlyBasicData']
                && in_array((string) $property->name, $skipFieldsIfOnlyBasicData))
            {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Skipping '
                            . $property->name . ' (using option onlyBasicData)');
                continue;
            }
            
            switch ($property->name) {
                case 'DTSTAMP':
                    $imipProps['DTSTAMP'] = trim($property->serialize());
                    if (! isset($options[self::OPTION_USE_SERVER_MODLOG]) || $options[self::OPTION_USE_SERVER_MODLOG] !== true) {
                        $event->last_modified_time = $this->_convertToTinebaseDateTime($property);
                    }
                    break;
                case 'CREATED':
                    $imipProps['CREATED'] = trim($property->serialize());
                    if (! isset($options[self::OPTION_USE_SERVER_MODLOG]) || $options[self::OPTION_USE_SERVER_MODLOG] !== true) {
                        $event->creation_time = $this->_convertToTinebaseDateTime($property);
                    }
                    break;
                    
                case 'LAST-MODIFIED':
                    $event->last_modified_time = $this->_convertToTinebaseDateTime($property);
                    $imipProps['LAST-MODIFIED'] = trim($property->serialize());
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
                    } else if ($property->getValue() == 'CANCELLED'){
                        $event->status = Calendar_Model_Event::STATUS_CANCELED;
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
                    
                case 'DESCRIPTION':
                case 'LOCATION':
                case 'SUMMARY':
                    $key = strtolower($property->name);
                    $value = $property->getValue();
                    if (in_array($key, array('location', 'summary')) && extension_loaded('mbstring')) {
                        $value = mb_substr($value, 0, 1024, 'UTF-8');
                    }

                    $event->$key = Tinebase_Core::filterInputForDatabase($value);
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
                        $dtUntil = Calendar_Convert_Event_VCalendar_Abstract::getUTCDateFromStringInUsertime($matches[1]);
                        return 'UNTIL=' . $dtUntil->format(Tinebase_Record_Abstract::ISO8601LONG);
                    }, $rruleString);

                    // remove additional days from BYMONTHDAY property (BYMONTHDAY=11,15 => BYMONTHDAY=11)
                    $rruleString = preg_replace('/(BYMONTHDAY=)([\d]+),([,\d]+)/', '$1$2', $rruleString);

                    // remove COUNT=9999 as we can't handle this large recordsets
                    $rruleString = preg_replace('/;{0,1}COUNT=9999/', '', $rruleString);
                    
                    $event->rrule = $rruleString;

                    if ($event->exdate instanceof Tinebase_Record_RecordSet) {
                        foreach($event->exdate as $exdate) {
                            if ($exdate->is_deleted) {
                                $event->exdate->removeRecord($exdate);
                            }
                        }
                    }

                    // NOTE: EXDATE in ical are fallouts only!
                    if (isset($vevent->EXDATE)) {
                        $event->exdate = $event->exdate instanceof Tinebase_Record_RecordSet ?
                            $event->exdate :
                            new Tinebase_Record_RecordSet('Calendar_Model_Event');
                        
                        foreach ($vevent->EXDATE as $exdate) {
                            foreach ($exdate->getDateTimes() as $exception) {
                                if (isset($exdate['VALUE']) && strtoupper($exdate['VALUE']) == 'DATE') {
                                    $recurid = new Tinebase_DateTime($exception->format(Tinebase_Record_Abstract::ISO8601LONG), (string) Tinebase_Core::getUserTimezone());
                                } else {
                                    $recurid = new Tinebase_DateTime($exception->format(Tinebase_Record_Abstract::ISO8601LONG), $exception->getTimezone());
                                }
                                $recurid->setTimezone(new DateTimeZone('UTC'));
                                
                                $eventException = new Calendar_Model_Event(array(
                                    'recurid'    => $recurid,
                                    'is_deleted' => true
                                ));

                                $event->exdate->addRecord($eventException);
                            }
                        }
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
                    if (empty($event->uid)) {
                        $event->uid = $property->getValue();
                    }
                    break;
                    
                case 'VALARM':
                    $this->_parseAlarm($event, $property, $vevent);
                    break;
                    
                case 'CATEGORIES':
                    $tags = Tinebase_Model_Tag::resolveTagNameToTag($property->getParts(), 'Calendar');
                    if (! isset($event->tags)) {
                        $event->tags = $tags;
                    } else {
                        $event->tags->merge($tags);
                    }
                    break;
                    
                case 'ATTACH':
                    $name = (string) $property['FILENAME'];
                    $managedId = (string) $property['MANAGED-ID'];
                    $value = (string) $property['VALUE'];
                    $attachment = NULL;
                    $readFromURL = false;
                    $url = '';
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' attachment found: ' . $name . ' ' . $managedId);
                    
                    if ($managedId) {
                        $attachment = $event->attachments instanceof Tinebase_Record_RecordSet ?
                            $event->attachments->filter('hash', $property['MANAGED-ID'])->getFirstRecord() :
                            NULL;
                        
                        // NOTE: we might miss a attachment here for the following reasons
                        //       1. client reuses a managed id (we are server):
                        //          We havn't observerd this yet. iCal client reuse manged id's
                        //          from base events in exceptions but this is covered as we 
                        //          initialize new exceptions with base event attachments
                        //          
                        //          When a client reuses a managed id it's not clear yet if
                        //          this managed id needs to be in the same series/calendar/server
                        //
                        //          As we use the object hash the managed id might be used in the 
                        //          same files with different names. We need to evaluate the name
                        //          (if attached) in this case as well.
                        //       
                        //       2. server send his managed id (we are client)
                        //          * we need to download the attachment (here?)
                        //          * we need to have a mapping externalid / internalid (where?)
                        
                        if (! $attachment) {
                            $readFromURL = true;
                            $url = $property->getValue();
                        } else {
                            $attachments->addRecord($attachment);
                        }
                    } elseif('URI' === $value) {
                        /*
                         * ATTACH;VALUE=URI:https://server.com/calendars/__uids__/0AA0
 3A3B-F7B6-459A-AB3E-4726E53637D0/dropbox/4971F93F-8657-412B-841A-A0FD913
 9CD61.dropbox/Canada.png
                         */
                        $url = $property->getValue();
                        $urlParts = parse_url($url);
                        $host = $urlParts['host'];
                        $name = pathinfo($urlParts['path'], PATHINFO_BASENAME);

                        // iCal 10.7 places URI before uploading
                        if (parse_url(Tinebase_Core::getHostname(), PHP_URL_HOST) != $host) {
                            $readFromURL = true;
                        }
                    }
                    // base64
                    else {
                        // @TODO: implement (check if add / update / update is needed)
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' attachment found that could not be imported due to missing managed id');
                    }
                    
                    if ($readFromURL) {
                        if (preg_match('#^(https?://)(.*)$#', str_replace(array("\n","\r"), '', $url), $matches)) {
                            // we are client and found an external hosted attachment that we need to import
                            $userCredentialCache = Tinebase_Core::getUserCredentialCache();
                            $url = $matches[1] . $userCredentialCache->username . ':' . $userCredentialCache->password . '@' . $matches[2];
                            $attachmentInfo = $matches[1] . $matches[2]. ' ' . $name . ' ' . $managedId;
                            if (Tinebase_Helper::urlExists($url)) {
                                if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                                            . ' Downloading attachment: ' . $attachmentInfo);
                                
                                $stream = @fopen($url, 'r');
                                if ($stream) {
                                    $attachment = new Tinebase_Model_Tree_Node(array(
                                        'name' => rawurldecode($name),
                                        'type' => Tinebase_Model_Tree_FileObject::TYPE_FILE,
                                        'contenttype' => (string)$property['FMTTYPE'],
                                        'tempFile' => $stream,
                                    ), true);
                                    $attachments->addRecord($attachment);
                                } else {
                                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                        . ' Could not open url (maybe no permissions?): ' . $attachmentInfo . ' - Skipping attachment');
                                }
                            } else {
                                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                                    . ' Url not found (got 404): ' . $attachmentInfo . ' - Skipping attachment');
                            }
                        } else {
                            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                                    . ' Attachment found with malformed url: ' . $url);
                        }
                    }
                    break;
                    
                case 'X-MOZ-LASTACK':
                    $lastAck = $this->_convertToTinebaseDateTime($property);
                    break;
                    
                case 'X-MOZ-SNOOZE-TIME':
                    $snoozeTime = $this->_convertToTinebaseDateTime($property);
                    break;

                case 'EXDATE':
                    // ignore this, we dont want it to land in default -> imipProps!
                    break;

                default:
                    // thunderbird saves snooze time for recurring event occurrences in properties with names like this -
                    // we just assume that the event/recur series has only one snooze time 
                    if (preg_match('/^X-MOZ-SNOOZE-TIME-[0-9]+$/', $property->name)) {
                        $snoozeTime = $this->_convertToTinebaseDateTime($property);
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                            . ' Found snooze time for recur occurrence: ' . $snoozeTime->toString());
                    } elseif ($property instanceof \Sabre\VObject\Property) {
                        $imipProps[$property->name] = trim($property->serialize());
                    }
                    break;
            }
        }
        if (!empty($imipProps) && !$event->hasExternalOrganizer()) {
            unset($imipProps['DTSTAMP']);
            unset($imipProps['CREATED']);
            unset($imipProps['LAST-MODIFIED']);
        }
        if (!empty($imipProps)) {
            if (isset($event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES])) {
                $event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES] += $imipProps;
            } else {
                $event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES] = $imipProps;
            }
        }

        // evaluate seq after organizer is parsed
        if ($vevent->SEQUENCE) {
            $seq = $vevent->SEQUENCE->getValue();
            if (!$event->hasExternalOrganizer()) {
                if (!isset($options[self::OPTION_USE_SERVER_MODLOG]) || $options[self::OPTION_USE_SERVER_MODLOG] !== true) {
                    $event->seq = $seq;
                }
            } else {
                $event->external_seq = $seq;
            }
        }

        // NOTE: X-CALENDARSERVER-ACCESS overwrites CLASS
        if (isset($vevent->{'X-CALENDARSERVER-ACCESS'})) {
            $event->class = $vevent->{'X-CALENDARSERVER-ACCESS'} == 'PUBLIC' ?
                Calendar_Model_Event::CLASS_PUBLIC :
                Calendar_Model_Event::CLASS_PRIVATE;
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
            $event->seq = 1;
        }
        
        if (empty($event->class)) {
            $event->class = Calendar_Model_Event::CLASS_PUBLIC;
        }
        
        $this->_manageAttachmentsFromClient($event, $attachments);
        
        if (empty($event->dtend)) {
            if (empty($event->dtstart)) {
                throw new Tinebase_Exception_UnexpectedValue("Got event without dtstart and dtend");
            }
            
            // TODO find out duration (see TRIGGER DURATION)
//             if (isset($vevent->DURATION)) {
//             }
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Got event without dtend. Assuming 30 minutes duration');
            $event->dtend = clone $event->dtstart;
            $event->dtend->addMinute(30);
        }
        
        $this->_manageAttachmentsFromClient($event, $attachments);
        
        // convert all datetime fields to UTC
        $event->setTimezone('UTC');
    }
    
    /**
     * get utc datetime from date string and handle dates (ie 20140922) in usertime
     * 
     * @param string $dateString
     * 
     * TODO maybe this can be replaced with _convertToTinebaseDateTime
     */
    static public function getUTCDateFromStringInUsertime($dateString)
    {
        if (strlen($dateString) < 10) {
            $date = date_create($dateString, new DateTimeZone ((string) Tinebase_Core::getUserTimezone()));
        } else {
            $date = date_create($dateString);
        }
        $date->setTimezone(new DateTimeZone('UTC'));
        return $date;
    }
}
