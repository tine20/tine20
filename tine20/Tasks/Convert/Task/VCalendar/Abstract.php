<?php

use Sabre\VObject;

/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert single event (repeating with exceptions) to/from VCalendar
 *
 * @package     Tasks
 * @subpackage  Convert
 */
class Tasks_Convert_Task_VCalendar_Abstract implements Tinebase_Convert_Interface
{
    public static $cutypeMap = array(
        //Tasks_Model_Attender::USERTYPE_USER          => 'INDIVIDUAL',
        //Tasks_Model_Attender::USERTYPE_GROUPMEMBER   => 'INDIVIDUAL',
        //Tasks_Model_Attender::USERTYPE_GROUP         => 'GROUP',
        //Tasks_Model_Attender::USERTYPE_RESOURCE      => 'RESOURCE',
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
     * convert Tasks_Model_Task to \Sabre\VObject\Component
     *
     * @param  Tasks_Model_Task  $_record
     * @return \Sabre\VObject\Component
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' event ' . print_r($_record->toArray(), true));
        
        $vcalendar = new VObject\Component('VCALENDAR');
        
        // required vcalendar fields
        $version = Tinebase_Application::getInstance()->getApplicationByName('Tasks')->version;
        
        $vcalendar->PRODID   = "-//tine20.org//Tine 2.0 Tasks V$version//EN";
        $vcalendar->VERSION  = '2.0';
        $vcalendar->CALSCALE = 'GREGORIAN';
        
        // catch exceptions for unknown timezones
        try {
            $vcalendar->add(new Sabre_VObject_Component_VTimezone($_record->originator_tz));
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . ' timezone exception ' . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' timezone exception ' . $e->getTraceAsString());
        }
        
        $vtodo = $this->_convertTasksModelTask($_record);
        $vcalendar->add($vtodo);
        
        # Tasks application does not yet support repeating tasks
        #
        #if ($_record->exdate instanceof Tinebase_Record_RecordSet) {
        #    $eventExceptions = $_record->exdate->filter('is_deleted', false);
        #    
        #    foreach($eventExceptions as $eventException) {
        #        // set timefields
        #        // @todo move to MS event facade
        #        $eventException->creation_time = $_record->creation_time;
        #        if (isset($_record->last_modified_time)) {
        #            $eventException->last_modified_time = $_record->last_modified_time;
        #        }
        #        $vevent = $this->_convertTasksModelTask($eventException, $_record);
        #        $vcalendar->add($vevent);
        #    }
        #
        #}
        
        $this->_afterFromTine20Model($vcalendar);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' card ' . $vcalendar->serialize());
        
        return $vcalendar;
    }
    
    /**
     * convert calendar event to \Sabre\VObject\Component
     * 
     * @param Tasks_Model_Task $_vtodo
     * @param Tasks_Model_Task $mainTask
     * @return \Sabre\VObject\Component
     */
    protected function _convertTasksModelTask(Tasks_Model_Task $task, Tasks_Model_Task $mainTask = null)
    {
        // clone the event and change the timezone
        $task = clone $task;
        if (!empty($task->originator_tz)) {
            $task->setTimezone($task->originator_tz);
        }
        
        $vtodo = new VObject\Component('VTODO');
        
        $lastModifiedDatTime = $task->last_modified_time ? $task->last_modified_time : $task->creation_time;
        
        $vtodo->CREATED = VObject\Property::create('CREATED');
        $vtodo->CREATED->setDateTime($task->creation_time, VObject\Property\DateTime::UTC);
        
        $vtodo->{'LAST-MODIFIED'} = VObject\Property::create('LAST-MODIFIED');
        $vtodo->{'LAST-MODIFIED'}->setDateTime($lastModifiedDatTime, VObject\Property\DateTime::UTC);
        
        $vtodo->DTSTAMP = VObject\Property::create('DTSTAMP');
        $vtodo->DTSTAMP->setDateTime(Tinebase_DateTime::now(), VObject\Property\DateTime::UTC);

        $vtodo->UID      = $task->uid;
        $vtodo->SEQUENCE = !empty($task->seq) ? $task->seq : 1;
        
        if(isset($task->dtstart)){
            $vtodo->DTSTART = VObject\Property::create('DTSTART');
            $vtodo->DTSTART->setDateTime($task->dtstart);
        }
        
        if(isset($task->due)){
            $vtodo->DUE = VObject\Property::create('DUE');
            $vtodo->DUE->setDateTime($task->due);
        }
        
        if(isset($task->completed)){
             $vtodo->COMPLETED = VObject\Property::create('COMPLETED');
             $vtodo->COMPLETED->setDateTime($task->completed);
        }

        switch($task->priority) {
             case 'LOW':
                 $vtodo->PRIORITY = VObject\Property::create('PRIORITY','9');
                 break;
                 
             case 'NORMAL':
                 $vtodo->PRIORITY = VObject\Property::create('PRIORITY','0');
                 break;
                 
             case 'HIGH':
                 $vtodo->PRIORITY = VObject\Property::create('PRIORITY','1');
                 break;
                 
             case 'URGENT':
                 $vtodo->PRIORITY = VObject\Property::create('PRIORITY','1');
                 break;
        }


        if(!empty($task->percent)){
            $vtodo->add(VObject\Property::create('PERCENT-COMPLETE', $task->percent));
        }

        // task organizer
        if (!empty($task->organizer)) {
            $organizerContact = $task->resolveOrganizer();
            if ($organizerContact instanceof Addressbook_Model_Contact && !empty($organizerContact->email)) {
                $organizer = VObject\Property::create('ORGANIZER', 'mailto:' . $organizerContact->email);
                $organizer->add('CN', $organizerContact->n_fileas);
                $organizer->add('EMAIL', $organizerContact->email);
                $vtodo->add($organizer);
            }
        }
        
        $optionalProperties = array(
            'class',
            'description',
            'geo',
            'location',
            #'priority',
            'summary',
            'status',
            'url'
        );
        
        foreach ($optionalProperties as $property) {
            if (!empty($task->$property)) {
                $vtodo->add(VObject\Property::create($property, $task->$property));
            }
        }
        
        // categories
        if(isset($task->tags) && count($task->tags) > 0) {
            $vtodo->CATEGORIES = VObject\Property::create('CATEGORIES');
            $vtodo->CATEGORIES->setParts((array) $task->tags->name);
        }
        
        // repeating event properties
        /*if ($event->rrule) {
            if ($event->is_all_day_event == true) {
                $vtodo->add(new Sabre_VObject_Property_Recure('RRULE', preg_replace_callback('/UNTIL=([\d :-]{19})(?=;?)/', function($matches) {
                    $dtUntil = new Tinebase_DateTime($matches[1]);
                    $dtUntil->setTimezone((string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                    
                    return 'UNTIL=' . $dtUntil->format('Ymd');
                }, $event->rrule)));
            } else {
                $vtodo->add(new Sabre_VObject_Property_Recure('RRULE', preg_replace('/(UNTIL=)(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', '$1$2$3$4T$5$6$7Z', $event->rrule)));
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
                    $vtodo->add($exdate);
                }
            }
        }
        */
        // add alarms only to vcalendar if current user attends to this event
        if ($task->alarms) {
            //$ownAttendee = Calendar_Model_Attender::getOwnAttender($task->attendee);
            
            //if ($ownAttendee && $ownAttendee->alarm_ack_time instanceof Tinebase_DateTime) {
            //    $xMozLastAck = new Sabre_VObject_Element_DateTime('X-MOZ-LASTACK');
            //    $xMozLastAck->setDateTime($ownAttendee->alarm_ack_time, Sabre_VObject_Element_DateTime::UTC);
            //    $vtodo->add($xMozLastAck);
            //}
            
            //if ($ownAttendee && $ownAttendee->alarm_snooze_time instanceof Tinebase_DateTime) {
            //    $xMozSnoozeTime = new Sabre_VObject_Element_DateTime('X-MOZ-SNOOZE-TIME');
            //    $xMozSnoozeTime->setDateTime($ownAttendee->alarm_snooze_time, Sabre_VObject_Element_DateTime::UTC);
            //    $vtodo->add($xMozSnoozeTime);
            //}
            
            // fake X-MOZ-LASTACK
            $vtodo->{'X-MOZ-LASTACK'} = new VObject\Property\DateTime('X-MOZ-LASTACK');
            $vtodo->{'X-MOZ-LASTACK'}->setDateTime($task->creation_time, VObject\Property\DateTime::UTC);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
                __METHOD__ . '::' . __LINE__ . ' event ' . print_r($task->alarms,TRUE));
 
            foreach($task->alarms as $alarm) {
                $valarm = new VObject\Component('VALARM');
                $valarm->add('ACTION', 'DISPLAY');
                $valarm->add('DESCRIPTION', $task->summary);
                
                if (is_numeric($alarm->minutes_before)) {
                    if ($task->dtstart == $alarm->alarm_time) {
                        $periodString = 'PT0S';
                    } else {
                        $interval = $task->due->diff($alarm->alarm_time);
                        $periodString = sprintf('%sP%s%s%s%s',
                            $interval->format('%r'),
                            $interval->format('%d') > 0 ? $interval->format('%dD') : null,
                            ($interval->format('%h') > 0 || $interval->format('%i') > 0) ? 'T' : null,
                            $interval->format('%h') > 0 ? $interval->format('%hH') : null,
                            $interval->format('%i') > 0 ? $interval->format('%iM') : null
                        );
                    }
                    # TRIGGER;VALUE=DURATION:-PT1H15M
                    $trigger = VObject\Property::create('TRIGGER', $periodString);
                    $trigger->add('VALUE', "DURATION");
                    $valarm->add($trigger);
                } else {
                    # TRIGGER;VALUE=DATE-TIME:...
                    $trigger = VObject\Property::create('TRIGGER');
                    $trigger->add('VALUE', "DATE-TIME");
                    $trigger->setDateTime($alarm->alarm_time, VObject\Property\DateTime::UTC);
                    $valarm->add($trigger);
                }

                $vtodo->add($valarm);
            }
        }
         
        return $vtodo;
    }
    
    /**
     * to be overwriten in extended classes to modify/cleanup $_vcalendar
     * 
     * @param \Sabre\VObject\Component $_vcalendar
     */
    protected function _afterFromTine20Model(VObject\Component $_vcalendar)
    {
        
    }
    
    /**
     * converts vcalendar to Tasks_Model_Task
     * 
     * @param  mixed                 $_blob   the vcalendar to parse
     * @param  Calendar_Model_Event  $_record  update existing event
     * @return Calendar_Model_Event
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_record = null)
    {
        $vcalendar = self::getVcal($_blob);
        
        // contains the VCALENDAR any VEVENTS
        if (!isset($vcalendar->VTODO)) {
            throw new Tinebase_Exception_UnexpectedValue('no vevents found');
        }
        
        // update a provided record or create a new one
        if ($_record instanceof Tasks_Model_Task) {
            $task = $_record;
        } else {
            $task = new Tasks_Model_Task(null);
        }
        
        // bypass filters until end of this funtion
        $task->bypassFilters = true;
        
        // keep current exdate's (only the not deleted ones)
        /* if ($task->exdate instanceof Tinebase_Record_RecordSet) {
            $oldExdates = $task->exdate->filter('is_deleted', false);
        } else {
            $oldExdates = new Tinebase_Record_RecordSet('Calendar_Model_Events');
        }
        */
        if (!isset($vcalendar->METHOD)) {
            $this->_method = $vcalendar->METHOD;
        }
        
        // find the main event - the main event has no RECURRENCE-ID
        foreach($vcalendar->VTODO as $vtodo) {
            if(!isset($vtodo->{"RECURRENCE-ID"})) {
                $this->_convertVtodo($vtodo, $task);
                
                break;
            }
        }

        // if we have found no VEVENT component something went wrong, lets stop here
        if (!isset($task)) {
            throw new Tinebase_Exception_UnexpectedValue('no main VEVENT component found in VCALENDAR');
        }
        
        #// parse the event exceptions
        #foreach($vcalendar->VTODO as $vtodo) {
        #    if(isset($vtodo->{"RECURRENCE-ID"}) && $task->id == $vtodo->UID) {
        #        $recurException = $this->_getRecurException($oldExdates, $vtodo);
        #        
        #        // initialize attendee with attendee from base events for new exceptions
        #        // this way we can keep attendee extra values like groupmember type
        #        // attendees which do not attend to the new exception will be removed in _convertVtodo
        #        /*if (! $recurException->attendee instanceof Tinebase_Record_RecordSet) {
        #            $recurException->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        #            foreach ($task->attendee as $attendee) {
        #                $recurException->attendee->addRecord(new Calendar_Model_Attender(array(
        #                    'user_id'   => $attendee->user_id,
        #                    'user_type' => $attendee->user_type,
        #                    'role'      => $attendee->role,
        #                    'status'    => $attendee->status
        #                )));
        #            }
        #        }*/
        #        
        #        $this->_convertVtodo($vtodo, $recurException);
        #            
        #        //if(! $task->exdate instanceof Tinebase_Record_RecordSet) {
        #        //    $task->exdate = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        #        //}
        #        $task->exdate->addRecord($recurException);
        #    }
        #}
        
        // enable filters again
        $task->bypassFilters = false;
        
        $task->isValid(true);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' FLAMENGO3 ' . print_r($task->toarray(),TRUE));
        
        return $task;
    }
    
    /**
     * returns VObject of input data
     * 
     * @param mixed $_blob
     * @return Sabre\VObject\Component
     */
    public static function getVcal($_blob)
    {
        if ($_blob instanceof VObject\Component) {
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
     * @throws Sabre\VObject\ParseException
     * @return Sabre\VObject\Component
     * 
     * @see 0006110: handle iMIP messages from outlook
     * @see 0007438: update Sabre library
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
            $vcalendar = VObject\Reader::read($blob);
        } catch (Sabre\VObject\ParseException $svpe) {
            // NOTE: we try to repair VObject\Reader as it fails to detect followup lines that do not begin with a space or tab
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
     * find a matching exdate or return an empty event record
     * 
     * @param  Tinebase_Record_RecordSet  $_oldExdates
     * @param  \Sabre\VObject\Component    $_vevent
     * @return Tasks_Model_Task
     */
    protected function _getRecurException(Tinebase_Record_RecordSet $_oldExdates, VObject\Component $_vevent)
    {
        $exDate = clone $_vevent->{"RECURRENCE-ID"}->getDateTime();
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
     * parse VTODO part of VCALENDAR
     * 
     * @param  \Sabre\VObject\Component  $_vevent  the VTODO to parse
     * @param  Tasks_Model_Task     $_vtodo   the Tine 2.0 event to update
     */
    protected function _convertVtodo(VObject\Component $_vtodo, Tasks_Model_Task $_task)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' vevent ' . $_vtodo->serialize());  
        
        $task = $_task;
        
        // unset supported fields
        foreach ($this->_supportedFields as $field) {
            switch ($field) {
                case 'alarms':
                    $task->$field = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
                    break;
                    
                case 'priority':
                     $task->$field = 'NORMAL';
                     break;
                     
                case 'status':
                     $task->$field = 'NEEDS-ACTION';
                     break;
                     
                default:
                     $task->$field = null;
                     break;
            }
        }

        foreach($_vtodo->children() as $property) {
            switch($property->name) {
                case 'CREATED':
                case 'DTSTAMP':
                    // do nothing
                    break;
                    
                case 'LAST-MODIFIED':
                    $task->last_modified_time = new Tinebase_DateTime($property->value);
                    break;
                
                case 'CLASS':
                    if (in_array($property->value, array(Tasks_Model_Task::CLASS_PRIVATE, Tasks_Model_Task::CLASS_PUBLIC))) {
                        $task->class = $property->value;
                    } else {
                        $task->class = Tasks_Model_Task::CLASS_PUBLIC;
                    }
                    
                    break;
                    
                case 'COMPLETED':
                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        //$task->is_all_day_event = true;
                        $dtend = $this->_convertToTinebaseDateTime($property, TRUE);
                        
                        // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in vcalendar
                        $dtend->subSecond(1);
                    } else {
                        //$task->is_all_day_event = false;
                        $dtend = $this->_convertToTinebaseDateTime($property);
                    }
                    
                    $task->completed = $dtend;
                    
                    break;
                    
                case 'DUE':
                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        //$task->is_all_day_event = true;
                        $due = $this->_convertToTinebaseDateTime($property, TRUE);
                    } else {
                        //$task->is_all_day_event = false;
                        $due = $this->_convertToTinebaseDateTime($property);
                    }
                    
                    $task->originator_tz = $due->getTimezone()->getName();
                    $task->due = $due;
  
                    break;
                    
                case 'DTSTART':
                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        //$task->is_all_day_event = true;
                        $dtstart = $this->_convertToTinebaseDateTime($property, TRUE);
                        // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in vcalendar
                        $dstart->subSecond(1);
                    } else {
                        //$task->is_all_day_event = false;
                        $dtstart = $this->_convertToTinebaseDateTime($property);
                    }
                    
                    $task->originator_tz = $dtstart->getTimezone()->getName();
                    $task->dtstart = $dtstart;
                    
                    break;
                    
                case 'STATUS':
                    $task->status = $property->value;
                    
                    break;
                    
                case 'PERCENT-COMPLETE':
                    $task->percent = $property->value;
                    
                    break;
                           
                case 'SEQUENCE':
                    $task->seq = $property->value;
                    
                    break;
                    
                case 'PRIORITY':
                    if (is_numeric($property->value)) {
                        switch ($property->value) {
                            case '0':
                                $task->priority = 'NORMAL';
                                
                                break;
                                
                            case '1':
                                $task->priority = 'HIGH';
                                
                                break;
                                
                            case '9':
                                $task->priority = 'LOW';
                                
                                break;
                            }
                    } else {
                        $task->priority = $property->value;
                    }
                    
                    break;
                    
                case 'DESCRIPTION':
                case 'LOCATION':
                case 'SUMMARY':
                    $key = strtolower($property->name);
                    //$task->$key = empty($property->value) ?  "With aout summary" : $property->value;
                    $task->$key = $property->value;
                    break;
                    
                case 'ORGANIZER':
                    if (preg_match('/mailto:(?P<email>.*)/i', $property->value, $matches)) {
                        // it's not possible to change the organizer by spec
                        if (empty($task->organizer)) {
                            $name = isset($property['CN']) ? $property['CN']->value : $matches['email'];
                            $contact = Calendar_Model_Attender::resolveEmailToContact(array(
                                'email'     => $matches['email'],
                                'lastName'  => $name,
                            ));
                        
                            $task->organizer = $contact->getId();
                        }
                    }
                    
                    break;

                case 'RECURRENCE-ID':
                    // original start of the event
                    $task->recurid = $this->_convertToTinebaseDateTime($property);
                    
                    // convert recurrence id to utc
                    $task->recurid->setTimezone('UTC');
                    
                    break;
                    
                case 'RRULE':
                    $task->rrule = $property->value;
                    
                    // convert date format
                    $task->rrule = preg_replace_callback('/UNTIL=([\dTZ]+)(?=;?)/', function($matches) {
                        if (strlen($matches[1]) < 10) {
                            $dtUntil = date_create($matches[1], new DateTimeZone ((string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE)));
                            $dtUntil->setTimezone(new DateTimeZone('UTC'));
                        } else {
                            $dtUntil = date_create($matches[1]);
                        }
                        
                        return 'UNTIL=' . $dtUntil->format(Tinebase_Record_Abstract::ISO8601LONG);
                    }, $task->rrule);

                    // remove additional days from BYMONTHDAY property
                    $task->rrule = preg_replace('/(BYMONTHDAY=)([\d]+)([,\d]+)/', '$1$2', $task->rrule);
                    
                    // process exceptions
                    if (isset($_vtodo->EXDATE)) {
                        $exdates = new Tinebase_Record_RecordSet('Tasks_Model_Task');
                        
                        foreach($_vtodo->EXDATE as $exdate) {
                            foreach($exdate->getDateTimes() as $exception) {
                                if (isset($exdate['VALUE']) && strtoupper($exdate['VALUE']) == 'DATE') {
                                    $recurid = new Tinebase_DateTime($exception->format(Tinebase_Record_Abstract::ISO8601LONG), (string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                                } else {
                                    $recurid = new Tinebase_DateTime($exception->format(Tinebase_Record_Abstract::ISO8601LONG), $exception->getTimezone());
                                }
                                $recurid->setTimezone(new DateTimeZone('UTC'));
                                                        
                                $taskException = new Calendar_Model_Event(array(
                                    'recurid'    => $recurid,
                                    'is_deleted' => true
                                ));
                        
                                $exdates->addRecord($taskException);
                            }
                        }
                    
                        $task->exdate = $exdates;
                    }     
                                   
                    break;
                    
                case 'TRANSP':
                    if (in_array($property->value, array(Calendar_Model_Event::TRANSP_OPAQUE, Calendar_Model_Event::TRANSP_TRANSP))) {
                        $task->transp = $property->value;
                    } else {
                        $task->transp = Calendar_Model_Event::TRANSP_TRANSP;
                    }
                    
                    break;
                    
                case 'UID':
                    // it's not possible to change the uid by spec
                    if (!empty($task->uid)) {
                        continue;
                    }
                    
                    $task->uid = $property->value;
                
                    break;
                    
                case 'VALARM':
                    foreach($property as $valarm) {
                        switch(strtoupper($valarm->TRIGGER['VALUE']->value)) {
                            # TRIGGER;VALUE=DATE-TIME:20111031T130000Z
                            case 'DATE-TIME':
                                //@TODO fixme
                                $alarmTime = new Tinebase_DateTime($valarm->TRIGGER->value);
                                $alarmTime->setTimezone('UTC');
                                
                                $alarm = new Tinebase_Model_Alarm(array(
                                    'alarm_time'        => $alarmTime,
                                    'minutes_before'    => 'custom',
                                    'model'             => 'Tasks_Model_Task'
                                ));
                                
                                $task->alarms->addRecord($alarm);
                                
                                break;
                                
                            # TRIGGER;VALUE=DURATION:-PT1H15M
                            case 'DURATION':
                            default:
                                # @todo the alarm should be based on DTSTART
                                $alarmTime = $this->_convertToTinebaseDateTime($_vtodo->DUE);
                                $alarmTime->setTimezone('UTC');
                                
                                preg_match('/(?P<invert>[+-]?)(?P<spec>P.*)/', $valarm->TRIGGER->value, $matches);
                                $duration = new DateInterval($matches['spec']);
                                $duration->invert = !!($matches['invert'] === '-');

                                $alarm = new Tinebase_Model_Alarm(array(
                                    'alarm_time'        => $alarmTime->add($duration),
                                    'minutes_before'    => ($duration->format('%d') * 60 * 24) + ($duration->format('%h') * 60) + ($duration->format('%i')),
                                    'model'             => 'Tasks_Model_Task'
                                ));
                                
                                $task->alarms->addRecord($alarm);
                                
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
        
        if (empty($task->percent)) {
            $task->percent = 0;
        }
        
        if (empty($task->class)) {
            $task->class = Tasks_Model_Task::CLASS_PUBLIC;
        }
        
        // convert all datetime fields to UTC
        $task->setTimezone('UTC');
    }
    
    /**
     * get datetime from sabredav datetime property (user TZ is fallback)
     * 
     * @param Sabre\VObject\Property $dateTimeProperty
     * @param boolean $_useUserTZ
     * @return Tinebase_DateTime
     * 
     * @todo try to guess some common timezones
     */
    protected function _convertToTinebaseDateTime(VObject\Property $dateTimeProperty, $_useUserTZ = FALSE)
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set((string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        
        if ($dateTimeProperty instanceof VObject\Property\DateTime) {
            $dateTime = $dateTimeProperty->getDateTime();
            $tz = ($_useUserTZ) ? (string) Tinebase_Core::get(Tinebase_Core::USERTIMEZONE) : $dateTime->getTimezone();
            $result = new Tinebase_DateTime($dateTime->format(Tinebase_Record_Abstract::ISO8601LONG), $tz);
        } else {
            $result = new Tinebase_DateTime($dateTimeProperty->value);
        }
        
        date_default_timezone_set($defaultTimezone);
        
        return $result;
    }
}
