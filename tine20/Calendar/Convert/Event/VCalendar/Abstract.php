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
 * class to convert single event to/from VCalendar
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
            foreach($_model->exdate as $exEvent) {
                if (! $exEvent->is_deleted) {
                    $eventSet->addRecord($exEvent);
                    $_model->exdate->removeRecord($exEvent);
                }
            }
            
            // remaining exdates are fallouts
            $_model->exdate = $_model->exdate->getOriginalDtStart();
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
        
        if ($_model instanceof Calendar_Model_Event) {
            $event = $_model;
        } else {
            $event = new Calendar_Model_Event(null, false);
        }
        
        foreach($vcalendar->children() as $property) {
            
            switch($property->name) {
                case 'VERSION':
                case 'PRODID':
                    // do nothing
                    break;
                    
                case 'VTIMEZONE':
                    $event->originator_tz = $property->TZID->value;
                    break;
                    
                case 'VEVENT':
                    // keep old attendees
                    if (isset($event->attendee) && $event->attendee instanceof Tinebase_Record_RecordSet) {
                        $oldAttendees = clone $event->attendee;
                    }
                    
                    // unset supported fields
                    foreach ($this->_supportedFields as $field) {
                        $event->$field = null;
                    }
                    
                    $this->_parseVevent($property, $event);
                    if (empty($event->seq)) {
                        $event->seq = 0;
                    }
                    if (empty($event->class)) {
                        $event->class = Calendar_Model_Event::CLASS_PUBLIC;
                    }
                    
                    // merge old and new attendees
                    if (isset($oldAttendees) && isset($event->attendee)) {
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
                    
                default:
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' unsupported property ' . $property->name);
                    break;
            }
        }
        
        $event->isValid(true);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($event->toArray(), true));
        
        return $event;
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
                            
                            if(! $_event->attendee instanceof Tinebase_Record_RecordSet) {
                                $_event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
                            }
                            $_event->attendee->addRecord($newAttendee);
                        }
                    }
                    
                    break;
                    
                case 'CLASS':
                    if (in_array($property->value, array(Calendar_Model_Event::CLASS_PRIVATE, Calendar_Model_Event::CLASS_PUBLIC))) {
                        $_event->class = $property->value;
                    } else {
                        $_event->class = Calendar_Model_Event::CLASS_PUBLIC;
                    }
                    break;
                    
                case 'DTEND':
                    $dtend = new Tinebase_DateTime($property->getDateTime()->format("c"), $property->getDateTime()->getTimezone());
                    $dtend->setTimezone('UTC');
                    
                    $_event->dtend = $dtend;
                    break;
                    
                case 'DTSTART':
                    $dtstart = new Tinebase_DateTime($property->getDateTime()->format("c"), $property->getDateTime()->getTimezone());
                    $dtstart->setTimezone('UTC');
                    
                    $_event->dtstart = $dtstart;
                    break;
                    
                case 'LOCATION':
                case 'UID':
                case 'SEQ':
                case 'SUMMARY':
                    $key = strtolower($property->name);
                    $_event->$key = $property->value;
                    break;
                    
                // @todo add organizer to attendees
                case 'ORGANIZER':
                    if (preg_match('/mailto:(?P<email>.*)/', $property->value, $matches)) {
                        $name = isset($property['CN']) ? $property['CN'] : $matches['email'];
                        $contact = $this->_resolveEmailToContact($matches['email'], $name);
                        
                        $_event->organizer = $contact;
                        
                        $newAttendee = $this->_getAttendee($property, $contact);
                        
                        if(! $_event->attendee instanceof Tinebase_Record_RecordSet) {
                            $_event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
                        }
                        $_event->attendee->addRecord($newAttendee);
                    }
                    break;
                
                case 'TRANSP':
                    if (in_array($property->value, array(Calendar_Model_Event::TRANSP_OPAQUE, Calendar_Model_Event::TRANSP_TRANSP))) {
                        $_event->transp = $property->value;
                    } else {
                        $_event->transp = Calendar_Model_Event::TRANSP_TRANSP;
                    }
                    break;
                    
                case 'CATEGORIES':
                    break;
                    
                default:
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . $property->name);
                    break;
            }
        }
        
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
