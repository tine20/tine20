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
    protected $_supportedFields = array(
    );
    
    protected $_version;
    
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
        $eventId = $_model->getId();
        $lastModified = $_model->last_modified_time ? $_model->last_modified_time : $_model->creation_time;
        
        // we always use a event set to return exdates at once
        $eventSet = new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_model));
        
        if ($_model->rrule) {
        #    foreach($_model->exdate as $exEvent) {
        #        if (! $exEvent->is_deleted) {
        #            $eventSet->addRecord($exEvent);
        #            $_model->exdate->removeRecord($exEvent);
        #        }
        #    }
        #    
        #    // remaining exdates are fallouts
        #    $_model->exdate = $_model->exdate->getOriginalDtStart();
        }
        
        $exporter = new Calendar_Export_Ical();
        $ics = $exporter->eventToIcal($eventSet);
        
        return $ics->render();
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
        if ($_blob instanceof Sabre_VObject_Component) {
            $vcalendar = $_blob;
        } else {
            if (is_resource($_blob)) {
                $_blob = stream_get_contents($_blob);
            }
            $vcalendar = Sabre_VObject_Reader::read($_blob);
        }
        
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
        
        // find the main event - the main event has no RECURRENCE-ID
        foreach($vcalendar->VEVENT as $vevent) {
            // "-" is not allowed in property names
            $RECURRENCEID = 'RECURRENCE-ID';
            if(!isset($vevent->$RECURRENCEID)) {
                $this->_parseVevent($vevent, $event);
                
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
                
                $this->_parseVevent($vevent, $recurException);
                    
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
     * @param Addressbook_Model_Contact  $_contact   the contact for the given attendee
     * @return Calendar_Model_Attender
     */
    protected function _getAttendee(Sabre_VObject_Property $_attendee, Addressbook_Model_Contact $_contact)
    {
        $newAttendee = new Calendar_Model_Attender(array(
        	'user_id'   => $_contact->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
        ));
        
        if (in_array($_attendee['ROLE'], array(Calendar_Model_Attender::ROLE_OPTIONAL, Calendar_Model_Attender::ROLE_REQUIRED))) {
            $newAttendee->role = $_attendee['ROLE'];
        } else {
            $newAttendee->role = Calendar_Model_Attender::ROLE_REQUIRED;
        }
        
        if (in_array($_attendee['PARTSTAT'], array(Calendar_Model_Attender::STATUS_ACCEPTED,
            Calendar_Model_Attender::STATUS_DECLINED,
            Calendar_Model_Attender::STATUS_NEEDSACTION,
            Calendar_Model_Attender::STATUS_TENTATIVE)
        )) {
            $newAttendee->status = $_attendee['PARTSTAT']->value;
        } else {
            $newAttendee->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        }
        
        return $newAttendee;
    }
    
    /**
     * parse VEVENT part of VCALENDAR
     * 
     * @param  Sabre_VObject_Component  $_vevent  the VEVENT to parse
     * @param  Calendar_Model_Event     $_event   the Tine 2.0 event to update
     */
    protected function _parseVevent(Sabre_VObject_Component $_vevent, Calendar_Model_Event $_event)
    {
        $event = $_event;
        
        if (isset($event->attendee) && $event->attendee instanceof Tinebase_Record_RecordSet) {
            $oldAttendees = clone $event->attendee;
        }
        
        // unset supported fields
        foreach ($this->_supportedFields as $field) {
            $event->$field = null;
        }
        if(! $event->attendee instanceof Tinebase_Record_RecordSet) {
            $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        }
        
        foreach($_vevent->children() as $property) {
            switch($property->name) {
                case 'CREATED':
                case 'LAST-MODIFIED':
                case 'DTSTAMP':
                    // do nothing
                    break;
                    
                case 'ATTENDEE':
                    foreach($property as $attendee) {
                        if (preg_match('/mailto:(?P<email>.*)/', $attendee->value, $matches)) {
                            $name    = isset($attendee['CN']) ? $attendee['CN'] : $matches['email'];
                            $contact = $this->_resolveEmailToContact($matches['email'], $name);
                            
                            $newAttendee = $this->_getAttendee($attendee, $contact);
                            
                            $event->attendee->addRecord($newAttendee);
                        }
                    }
                                        
                    // merge old and new attendees
                    if (isset($oldAttendees)) {
                        foreach ($event->attendee as $id => $attendee) {
                    
                            // detect if the contact_id is already attending the event
                            $matchingAttendees = $oldAttendees
                                ->filter('user_type', $attendee->user_type)
                                ->filter('user_id',   $attendee->user_id);
                    
                            if(count($matchingAttendees) > 0) {
                                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " updating attendee");
                                $oldAttendee = $matchingAttendees[0];
                                $oldAttendee->role = $attendee->role;
                                $oldAttendee->status = $attendee->status;
                    
                                $event->attendee[$id] = $oldAttendee;
                            }
                        }
                    
                        unset($oldAttendees);
                    }
                    
                    break;
                    
                case 'CLASS':
                    if (in_array($property->value, array(Calendar_Model_Event::CLASS_PRIVATE, Calendar_Model_Event::CLASS_PUBLIC))) {
                        $event->class = $property->value;
                    } else {
                        $event->class = Calendar_Model_Event::CLASS_PUBLIC;
                    }
                    
                    break;
                    
                case 'DTEND':
                    $dtend = new Tinebase_DateTime($property->getDateTime()->format("c"), $property->getDateTime()->getTimezone());

                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        $event->is_all_day_event = true;
                        
                        // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in vcalendar
                        $dtend->subSecond(1);
                    } else {
                        $event->is_all_day_event = false;
                    }
                    
                    $event->dtend = $dtend;
                    
                    break;
                    
                case 'DTSTART':
                    $event->originator_tz = $property->getDateTime()->getTimezone()->getName();
                    
                    $dtstart = new Tinebase_DateTime($property->getDateTime()->format("c"), $property->getDateTime()->getTimezone());
                    
                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        $event->is_all_day_event = true;
                    } else {
                        $event->is_all_day_event = false;
                    }
                    
                    $event->dtstart = $dtstart;
                    
                    break;
                    
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
                        
                        $event->organizer = $contact->getId();
                        
                        $newAttendee = $this->_getAttendee($property, $contact);
                        
                        $event->attendee->addRecord($newAttendee);
                    }
                    
                    break;

                case 'RECURRENCE-ID':
                    // original start of the event
                    $event->recurid = new Tinebase_DateTime($property->getDateTime()->format("c"), $property->getDateTime()->getTimezone());
                    
                    // convert recurrence id to utc
                    $event->recurid->setTimezone('UTC');
                    
                    break;
                    
                case 'RRULE':
                    $event->rrule = preg_replace('/(UNTIL=)(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z/', '$1$2-$3-$4 $5:$6:$7', $property->value);
                    
                    // process exceptions
                    if (isset($_vevent->EXDATE)) {
                        $exdates = new Tinebase_Record_RecordSet('Calendar_Model_Event');
                        
                        foreach($_vevent->EXDATE as $exdate) {
                            foreach($exdate->getDateTimes() as $exception) {
                                $exception->setTimezone(new DateTimeZone('UTC'));
                                                        
                                $eventException = new Calendar_Model_Event(array(
                                	'recurid'    => new Tinebase_DateTime($exception->format("c"), 'UTC'),
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
                    
                
                case 'CATEGORIES':
                    // @todo handle categories
                    break;
                    
                default:
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . $property->name);
                
                    break;
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
