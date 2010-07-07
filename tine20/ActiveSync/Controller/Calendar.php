<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * controller events class
 * 
 * current problems:
 *   n tine events become one AS event as AS events house all exceptions
 *   one AS event becomes n tine events
 *
 * @package     ActiveSync
 */
class ActiveSync_Controller_Calendar extends ActiveSync_Controller_Abstract
{
    /**
     * attendee status
     */
    const ATTENDEE_STATUS_UNKNOWN       = 0;
    const ATTENDEE_STATUS_TENTATIVE     = 2;
    const ATTENDEE_STATUS_ACCEPTED      = 3;
    const ATTENDEE_STATUS_DECLINED      = 4;
    const ATTENDEE_STATUS_NOTRESPONDED  = 5;
    
    /**
     * attendee types
     */
    const ATTENDEE_TYPE_REQUIRED = 1;
    const ATTENDEE_TYPE_OPTIONAL = 2;
    const ATTENDEE_TYPE_RESOURCE = 3;
    
    /**
     * recur types
     */
    const RECUR_TYPE_DAILY          = 0;     // Recurs daily.
    const RECUR_TYPE_WEEKLY         = 1;     // Recurs weekly
    const RECUR_TYPE_MONTHLY        = 2;     // Recurs monthly
    const RECUR_TYPE_MONTHLY_DAYN   = 3;     // Recurs monthly on the nth day
    const RECUR_TYPE_YEARLY         = 5;     // Recurs yearly
    const RECUR_TYPE_YEARLY_DAYN    = 6;     // Recurs yearly on the nth day
    
    /**
     * day of week constants
     */
    const RECUR_DOW_SUNDAY      = 1;
    const RECUR_DOW_MONDAY      = 2;
    const RECUR_DOW_TUESDAY     = 4;
    const RECUR_DOW_WEDNESDAY   = 8;
    const RECUR_DOW_THURSDAY    = 16;
    const RECUR_DOW_FRIDAY      = 32;
    const RECUR_DOW_SATURDAY    = 64;
    
    /**
     * filter types
     */
    const FILTER_NOTHING        = 0;
    const FILTER_2_WEEKS_BACK   = 4;
    const FILTER_1_MONTH_BACK   = 5;
    const FILTER_3_MONTHS_BACK  = 6;
    const FILTER_6_MONTHS_BACK  = 7;
    
    /**
     * available filters
     * 
     * @var array
     */
    protected $_filterArray = array(
        self::FILTER_2_WEEKS_BACK,
        self::FILTER_1_MONTH_BACK,
        self::FILTER_3_MONTHS_BACK,
        self::FILTER_6_MONTHS_BACK
    );
    
    /**
     * mapping of attendee status
     *
     * NOTE: not surjektive
     * @var array
     */
    protected $_attendeeStatusMapping = array(
        //self::ATTENDEE_STATUS_UNKNOWN       => Calendar_Model_Attender::STATUS_NEEDSACTION,
        self::ATTENDEE_STATUS_TENTATIVE     => Calendar_Model_Attender::STATUS_TENTATIVE,
        self::ATTENDEE_STATUS_ACCEPTED      => Calendar_Model_Attender::STATUS_ACCEPTED,
        self::ATTENDEE_STATUS_DECLINED      => Calendar_Model_Attender::STATUS_DECLINED,
        self::ATTENDEE_STATUS_NOTRESPONDED  => Calendar_Model_Attender::STATUS_NEEDSACTION
    );
    
    /**
     * mapping of attendee types
     * 
     * NOTE: recources need extra handling!
     * @var array
     */
    protected $_attendeeTypeMapping = array(
        self::ATTENDEE_TYPE_REQUIRED => Calendar_Model_Attender::ROLE_REQUIRED,
        self::ATTENDEE_TYPE_OPTIONAL => Calendar_Model_Attender::ROLE_OPTIONAL,
        self::ATTENDEE_TYPE_RESOURCE => Calendar_Model_Attender::USERTYPE_RESOURCE
    );
    
    /**
     * mapping of recur types
     *
     * NOTE: not surjektive
     * @var array
     */
    protected $_recurTypeMapping = array(
        self::RECUR_TYPE_DAILY          => Calendar_Model_Rrule::FREQ_DAILY,
        self::RECUR_TYPE_WEEKLY         => Calendar_Model_Rrule::FREQ_WEEKLY,
        self::RECUR_TYPE_MONTHLY        => Calendar_Model_Rrule::FREQ_MONTHLY,
        self::RECUR_TYPE_MONTHLY_DAYN   => Calendar_Model_Rrule::FREQ_MONTHLY,
        self::RECUR_TYPE_YEARLY         => Calendar_Model_Rrule::FREQ_YEARLY,
        self::RECUR_TYPE_YEARLY_DAYN    => Calendar_Model_Rrule::FREQ_YEARLY,
    );
    
    /**
     * mapping of weekdays
     * 
     * NOTE: ActiveSync uses a bitmask
     * @var array
     */
    protected $_recurDayMapping = array(
        Calendar_Model_Rrule::WDAY_SUNDAY       => self::RECUR_DOW_SUNDAY,
        Calendar_Model_Rrule::WDAY_MONDAY       => self::RECUR_DOW_MONDAY,
        Calendar_Model_Rrule::WDAY_TUESDAY      => self::RECUR_DOW_TUESDAY,
        Calendar_Model_Rrule::WDAY_WEDNESDAY    => self::RECUR_DOW_WEDNESDAY,
        Calendar_Model_Rrule::WDAY_THURSDAY     => self::RECUR_DOW_THURSDAY,
        Calendar_Model_Rrule::WDAY_FRIDAY       => self::RECUR_DOW_FRIDAY,
        Calendar_Model_Rrule::WDAY_SATURDAY     => self::RECUR_DOW_SATURDAY
    );
    
    /**
     * trivial mapping
     *
     * @var array
     */
    protected $_mapping = array(
        //'Timezone'          => 'timezone',
        'AllDayEvent'       => 'is_all_day_event',
        //'BusyStatus'        => 'transp',
        //'OrganizerName'     => 'organizer',
        //'OrganizerEmail'    => 'organizer',
        //'DtStamp'           => 'last_modified_time',  // not used outside from Tine 2.0
        'EndTime'           => 'dtend',
        'Location'          => 'location',
        //'Reminder'          => 'alarms',
        //'Sensitivity'       => 'class',
        'Subject'           => 'summary',
        //'Body'              => 'description',
        'StartTime'         => 'dtstart',
        //'UID'               => 'uid',             // not used outside from Tine 2.0
        //'MeetingStatus'     => 'status_id',
        //'Attendees'         => 'attendee',
        //'Categories'        => 'tags',
        //'Recurrence'        => 'rrule',
        //'Exceptions'        => 'exdate',
    );
    
    /**
     * name of Tine 2.0 backend application
     * 
     * @var string
     */
    protected $_applicationName     = 'Calendar';
    
    /**
     * name of Tine 2.0 model to use
     * 
     * @var string
     */
    protected $_modelName           = 'Event';
    
    /**
     * type of the default folder
     *
     * @var int
     */
    protected $_defaultFolderType   = ActiveSync_Command_FolderSync::FOLDERTYPE_CALENDAR;
    
    /**
     * type of user created folders
     *
     * @var int
     */
    protected $_folderType          = ActiveSync_Command_FolderSync::FOLDERTYPE_CALENDAR_USER_CREATED;
    
    /**
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty = 'calendarfilter_id';
        
    /**
     * append event data to xml element
     *
     * @todo handle BusyStatus
     * @todo handle TimeZone data
     * 
     * @param DOMElement  $_xmlNode   the parrent xml node
     * @param string      $_folderId  the local folder id
     * @param string      $_serverId  the local entry id
     * @param boolean     $_withBody  retrieve body of entry
     */
    public function appendXML(DOMElement $_xmlNode, $_folderId, $_serverId, array $_options, $_neverTruncate = false)
    {
        $data = $this->_contentController->get($_serverId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " calendar data " . print_r($data->toArray(), true));
        
        foreach($this->_mapping as $key => $value) {
            $nodeContent = null;
            
            if(!empty($data->$value)) {
                
                switch($value) {
                    case 'dtend':
                    case 'dtstart':
                        $nodeContent = $data->$value->toString('yyyyMMddTHHmmss') . 'Z';
                        #$_xmlNode->appendChild(new DOMElement($key, $date, 'uri:Calendar'));
                        break;
                    default:
                        $nodeContent = $data->$value;
                        
                        break;
                }
                
                // skip empty elements
                if($nodeContent === null || $nodeContent == '') {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Value for $key is empty. Skip element.");
                    continue;
                }
                
                // create a new DOMElement ...
                $node = new DOMElement($key, null, 'uri:Calendar');

                // ... append it to parent node aka append it to the document ...
                $_xmlNode->appendChild($node);
                
                // ... and now add the content (DomText takes care of special chars)
                $node->appendChild(new DOMText($nodeContent));
            }
        }
        
        if(!empty($data->description)) {
            if (version_compare($this->_device->acsversion, '12.0', '>=') === true) {
                $body = $_xmlNode->appendChild(new DOMElement('Body', null, 'uri:AirSyncBase'));
                
                $body->appendChild(new DOMElement('Type', 1, 'uri:AirSyncBase'));
                
                // create a new DOMElement ...
                $dataTag = new DOMElement('Data', null, 'uri:AirSyncBase');

                // ... append it to parent node aka append it to the document ...
                $body->appendChild($dataTag);
                
                // ... and now add the content (DomText takes care of special chars)
                $dataTag->appendChild(new DOMText($data->description));
            } else {
                // create a new DOMElement ...
                $node = new DOMElement('Body', null, 'uri:Calendar');

                // ... append it to parent node aka append it to the document ...
                $_xmlNode->appendChild($node);
                
                // ... and now add the content (DomText takes care of special chars)
                $node->appendChild(new DOMText($data->description));
                
            }
        }
           
        if(!empty($data->alarms)) {
            $alarm = $data->alarms->getFirstRecord();
            if($alarm instanceof Tinebase_Model_Alarm) {
                $start = $data->dtstart;
                $reminder = $alarm->alarm_time;
                $reminderMinutes = ($start->getTimestamp() - $reminder->getTimestamp()) / 60;
                $_xmlNode->appendChild(new DOMElement('Reminder', $reminderMinutes, 'uri:Calendar'));
            }
        }
                
        
        if(!empty($data->rrule)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " calendar rrule " . $data->rrule);
            $rrule = Calendar_Model_Rrule::getRruleFromString($data->rrule);
            
            $recurrence = $_xmlNode->appendChild(new DOMElement('Recurrence', null, 'uri:Calendar'));
            // required fields
            switch($rrule->freq) {
                case Calendar_Model_Rrule::FREQ_DAILY:
                    $recurrence->appendChild(new DOMElement('Type', self::RECUR_TYPE_DAILY, 'uri:Calendar'));
                    break;
                    
                case Calendar_Model_Rrule::FREQ_WEEKLY:
                    $recurrence->appendChild(new DOMElement('Type',      self::RECUR_TYPE_WEEKLY,                    'uri:Calendar'));
                    $recurrence->appendChild(new DOMElement('DayOfWeek', $this->_convertDayToBitMask($rrule->byday), 'uri:Calendar'));
                    break;
                    
                case Calendar_Model_Rrule::FREQ_MONTHLY:
                    if(!empty($rrule->bymonthday)) {
                        $recurrence->appendChild(new DOMElement('Type',       self::RECUR_TYPE_MONTHLY, 'uri:Calendar'));
                        $recurrence->appendChild(new DOMElement('DayOfMonth', $rrule->bymonthday,       'uri:Calendar'));
                    } else {
                        $weekOfMonth = (int) substr($rrule->byday, 0, -2);
                        $weekOfMonth = ($weekOfMonth == -1) ? 5 : $weekOfMonth; 
                        $dayOfWeek   = substr($rrule->byday, -2);
                    	
                        $recurrence->appendChild(new DOMElement('Type',        self::RECUR_TYPE_MONTHLY_DAYN,           'uri:Calendar'));
                        $recurrence->appendChild(new DOMElement('WeekOfMonth', $weekOfMonth,                            'uri:Calendar'));
                        $recurrence->appendChild(new DOMElement('DayOfWeek',   $this->_convertDayToBitMask($dayOfWeek), 'uri:Calendar'));
                    }
                    break;
                    
                case Calendar_Model_Rrule::FREQ_YEARLY:
                    if(!empty($rrule->bymonthday)) {
                        $recurrence->appendChild(new DOMElement('Type',        self::RECUR_TYPE_YEARLY, 'uri:Calendar'));
                        $recurrence->appendChild(new DOMElement('DayOfMonth',  $rrule->bymonthday,      'uri:Calendar'));
                        $recurrence->appendChild(new DOMElement('MonthOfYear', $rrule->bymonth,         'uri:Calendar'));
                    } else {
                        $weekOfMonth = (int) substr($rrule->byday, 0, -2);
                        $weekOfMonth = ($weekOfMonth == -1) ? 5 : $weekOfMonth; 
                        $dayOfWeek   = substr($rrule->byday, -2);
                    	
                        $recurrence->appendChild(new DOMElement('Type',        self::RECUR_TYPE_YEARLY_DAYN, 'uri:Calendar'));
                        $recurrence->appendChild(new DOMElement('WeekOfMonth', $weekOfMonth,                 'uri:Calendar'));
                        $recurrence->appendChild(new DOMElement('DayOfWeek',   $this->_convertDayToBitMask($dayOfWeek), 'uri:Calendar'));
                        $recurrence->appendChild(new DOMElement('MonthOfYear', $rrule->bymonth,              'uri:Calendar'));
                    }
                    break;
            }
            
            if ($rrule->freq != Calendar_Model_Rrule::FREQ_YEARLY) {
                $recurrence->appendChild(new DOMElement('Interval', $rrule->interval, 'uri:Calendar'));
            }
            
            if($rrule->until instanceof Zend_Date) {
                $recurrence->appendChild(new DOMElement('Until', $rrule->until->toString('yyyyMMddTHHmmss') . 'Z', 'uri:Calendar'));
            }
                        
            //Occurences
        }
        
        if(count($data->attendee) > 0) {
            // fill attendee cache
            Calendar_Model_Attender::resolveAttendee($data->attendee, FALSE);
            
            $attendees = null;
            
            foreach($data->attendee as $attenderObject) {
                if($attendees === null) {
                    $attendees = $_xmlNode->appendChild(new DOMElement('Attendees', null, 'uri:Calendar'));
                }
                $attendee = $attendees->appendChild(new DOMElement('Attendee', null, 'uri:Calendar'));
                $attendee->appendChild(new DOMElement('Name', $attenderObject->getName(), 'uri:Calendar'));
                $attendee->appendChild(new DOMElement('Email', $attenderObject->getEmail(), 'uri:Calendar'));
                if(version_compare($this->_device->acsversion, '12.0', '>=') === true) {
                    $attendee->appendChild(new DOMElement('AttendeeType', array_search($attenderObject->role, $this->_attendeeTypeMapping), 'uri:Calendar'));
                    $attendee->appendChild(new DOMElement('AttendeeStatus', array_search($attenderObject->status, $this->_attendeeStatusMapping), 'uri:Calendar'));
                }
            }
        }
        
        $timeZoneConverter = ActiveSync_TimezoneConverter::getInstance(
            Tinebase_Core::getLogger(),
            Tinebase_Core::get(Tinebase_Core::CACHE)
        );
        
        $_xmlNode->appendChild(new DOMElement('Timezone', $timeZoneConverter->encodeTimezone(
            Tinebase_Core::get(Tinebase_Core::USERTIMEZONE)
        ), 'uri:Calendar'));
        
        $_xmlNode->appendChild(new DOMElement('MeetingStatus', 1, 'uri:Calendar'));
        $_xmlNode->appendChild(new DOMElement('BusyStatus', 2, 'uri:Calendar'));
        $_xmlNode->appendChild(new DOMElement('Sensitivity', 0, 'uri:Calendar'));
        $_xmlNode->appendChild(new DOMElement('DtStamp', $data->creation_time->toString('yyyyMMddTHHmmss') . 'Z', 'uri:Calendar'));
        $_xmlNode->appendChild(new DOMElement('UID', $data->getId(), 'uri:Calendar'));
        if(!empty($data->organizer)) {
            try {
                $contact = Addressbook_Controller_Contact::getInstance()->get($data->organizer);
                
                $_xmlNode->appendChild(new DOMElement('OrganizerName', $contact->n_fn, 'uri:Calendar'));
                $_xmlNode->appendChild(new DOMElement('OrganizerEmail', $contact->email, 'uri:Calendar'));
            } catch (Tinebase_Exception_AccessDenied $e) {
                // set the current account as organizer
                // if organizer is not set, you can not edit the event on the Motorola Milestone
                $_xmlNode->appendChild(new DOMElement('OrganizerName', Tinebase_Core::getUser()->accountFullName, 'uri:Calendar'));
                if(isset(Tinebase_Core::getUser()->accountEmailAddress)) {
                    $_xmlNode->appendChild(new DOMElement('OrganizerEmail', Tinebase_Core::getUser()->accountEmailAddress, 'uri:Calendar'));
                }
            }
        } else {
            // set the current account as organizer
            // if organizer is not set, you can not edit the event on the Motorola Milestone
            $_xmlNode->appendChild(new DOMElement('OrganizerName', Tinebase_Core::getUser()->accountFullName, 'uri:Calendar'));
            if(isset(Tinebase_Core::getUser()->accountEmailAddress)) {
                $_xmlNode->appendChild(new DOMElement('OrganizerEmail', Tinebase_Core::getUser()->accountEmailAddress, 'uri:Calendar'));
            }
        }
        
        if (isset($data->tags) && count($data->tags) > 0) {
            $categories = $_xmlNode->appendChild(new DOMElement('Categories', null, 'uri:Calendar'));
            foreach ($data->tags as $tag) {
                $categories->appendChild(new DOMElement('Category', $tag, 'uri:Calendar'));
            }
        }
    }
    
    /**
     * convert string of days (TU,TH) to bitmask used by ActiveSync
     *  
     * @param $_days
     * @return int
     */
    protected function _convertDayToBitMask($_days)
    {
        $daysArray = explode(',', $_days);
        
        $result = 0;
        
        foreach($daysArray as $dayString) {
            $result = $result + $this->_recurDayMapping[$dayString];
        }
        
        return $result;
    }
    
    /**
     * convert bitmask used by ActiveSync to string of days (TU,TH) 
     *  
     * @param int $_days
     * @return string
     */
    protected function _convertBitMaskToDay($_days)
    {
        $daysArray = array();
        
        for($bitmask = 1; $bitmask <= self::RECUR_DOW_SATURDAY; $bitmask = $bitmask << 1) {
            $dayMatch = $_days & $bitmask;
            if($dayMatch === $bitmask) {
                $daysArray[] = array_search($bitmask, $this->_recurDayMapping);
            }
        }
        $result = implode(',', $daysArray);
        
        return $result;
    }
    
    /**
     * convert contact from xml to Calendar_Model_Event
     *
     * @todo handle BusyStatus
     * @param SimpleXMLElement $_data
     * @return Calendar_Model_Event
     */
    protected function _toTineModel(SimpleXMLElement $_data, $_entry = null)
    {
        if($_entry instanceof Calendar_Model_Event) {
            $event = $_entry;
        } else {
            $event = new Calendar_Model_Event(array(), true);
        }
        
        $xmlData     = $_data->children('uri:Calendar');
        $airSyncBase = $_data->children('uri:AirSyncBase');

        foreach($this->_mapping as $fieldName => $value) {
            switch($value) {
                case 'dtend':
                case 'dtstart':
                    if(isset($xmlData->$fieldName)) {
                        $timeStamp = $this->_convertISOToTs((string)$xmlData->$fieldName);
                        $event->$value = new Zend_Date($timeStamp, NULL);
                    } else {
                        $event->$value = null;
                    }
                    break;
                default:
                    if(isset($xmlData->$fieldName)) {
                        $event->$value = (string)$xmlData->$fieldName;
                    } else {
                        $event->$value = null;
                    }
                    break;
            }
        }
        
        // get body
        if (isset($xmlData->body)) {
            // ActiveSync 2.5
            $event->description = (string)$xmlData->body;
        } elseif(isset($airSyncBase->Body)) {
            // ActiveSync >= 12.0
            $event->description = (string)$airSyncBase->Body->Data;
        } else {
            $event->description = null;
        }
        
        // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in AS
        if(isset($xmlData->AllDayEvent) && $xmlData->AllDayEvent == 1) {
            $event->dtend->subSecond(1);
        }
        
        if(isset($xmlData->Reminder)) {
            $alarm = clone $event->dtstart;
            
            $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(array(
                'alarm_time'        => $alarm->subMinute((int)$xmlData->Reminder),
                'minutes_before'    => (int)$xmlData->Reminder,
                'model'             => 'Calendar_Model_Event'
            )));
        }
        
        // decode timezone data
        if(isset($xmlData->Timezone)) {
            $timeZoneConverter = ActiveSync_TimezoneConverter::getInstance(
                Tinebase_Core::getLogger(),
                Tinebase_Core::get(Tinebase_Core::CACHE)
            );

            try {
                $timezone = $timeZoneConverter->getTimezone(
                    (string)$xmlData->Timezone, 
                    Tinebase_Core::get(Tinebase_Core::USERTIMEZONE)
                );
                $event->originator_tz = $timezone;
            } catch (ActiveSync_TimezoneNotFoundException $e) {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . " timezone data not found " . (string)$xmlData->Timezone);
                $event->originator_tz = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
            }
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " timezone data " . $event->originator_tz);
        }
        
        // handle attendees
        $addressbook = Addressbook_Controller_Contact::getInstance();
        
        if(! $event->attendee instanceof Tinebase_Record_RecordSet) {
            $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        }
        
        // new event, add current user as participant
        if($event->getId() == null) {
            $contactId = Tinebase_Core::getUser()->contact_id;
            $newAttender = new Calendar_Model_Attender(array(
                'user_id'   => $contactId,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'status'    => Calendar_Model_Attender::STATUS_ACCEPTED,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            ));
            $event->attendee->addRecord($newAttender);
        }
        
        if(isset($xmlData->Attendees)) {
            $newAttendee = array();
            
            foreach($xmlData->Attendees->Attendee as $attendee) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " attendee email " . $attendee->Email);

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
                            'value'     => (string)$attendee->Email
                        ),
                        array(
                            'field'     => 'email_home',
                            'operator'  => 'equals',
                            'value'     => (string)$attendee->Email
                        ),
                    )),
                );
                                 
                #$contacts = $addressbook->search(new Addressbook_Model_ContactFilter($filterArray), null, true);
                $contacts = $addressbook->search(new Addressbook_Model_ContactFilter($filterArray));
                
                if(count($contacts) > 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found # of contacts " . count($contacts));
                    $contactId = $contacts->getFirstRecord()->getId();
                } else {
                    $contactData = array(
                        'note'        => 'added by syncronisation',
                        'email'       => (string)$attendee->Email,
                        'n_family'    => (string)$attendee->Name,
                    );
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " add new contact " . print_r($contactData, true));
                    $contact = new Addressbook_Model_Contact($contactData);
                    $contactId = $addressbook->create($contact)->getId();
                }
                $newAttendee[$contactId] = $contactId;
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " contactId " . $contactId);
                
                // find out if the contact_id is already attending the event
                $matchingAttendee = $event->attendee
                    ->filter('user_type', Calendar_Model_Attender::USERTYPE_USER)
                    ->filter('user_id', $contactId);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " add new contact " . count($matchingAttendee));
                
                if(count($matchingAttendee) == 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " attendee not found, adding as new");
                    $newAttender = new Calendar_Model_Attender(array(
                        'user_id'   => $contactId,
                        'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    ));
                    if(isset($attendee->AttendeeType)) {
                        $newAttender->role = $this->_attendeeTypeMapping[(int)$attendee->AttendeeType];
                    } else {
                        $newAttender->role = Calendar_Model_Attender::ROLE_REQUIRED;
                    }
                    if(isset($attendee->AttendeeStatus)) {
                        $newAttender->status = $this->_attendeeStatusMapping[(int)$attendee->AttendeeStatus];
                    } else {
                        $newAttender->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
                    }
                    
                    $event->attendee->addRecord($newAttender);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " updating attendee");
                    $currentAttendee = $matchingAttendee->getFirstRecord();
                    if(isset($attendee->AttendeeType)) {
                        $currentAttendee->role = $this->_attendeeTypeMapping[(int)$attendee->AttendeeType];
                    }
                    if(isset($attendee->AttendeeStatus)) {
                        $newAttender->status = $this->_attendeeStatusMapping[(int)$attendee->AttendeeStatus];
                    }
                }
            }
            
            foreach($event->attendee as $index => $attender) {
                if(!isset($newAttendee[$attender->user_id])) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " removed attender from event " . $attender->user_id);
                    unset($event->attendee[$index]);
                }
            }
        } elseif(count($event->attendee) == 0) {
            $contactId = Tinebase_Core::getUser()->contact_id;
            $newAttender = new Calendar_Model_Attender(array(
                'user_id'   => $contactId,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'status'    => Calendar_Model_Attender::STATUS_ACCEPTED,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            ));
            $event->attendee->addRecord($newAttender);
        }
        
        // handle recurrence
        if(isset($xmlData->Recurrence) && isset($xmlData->Recurrence->Type)) {
            $rrule = new Calendar_Model_Rrule();
            
            switch((int)$xmlData->Recurrence->Type) {
                case self::RECUR_TYPE_DAILY:
                    $rrule->freq = Calendar_Model_Rrule::FREQ_DAILY;
                    break; 
                    
                case self::RECUR_TYPE_WEEKLY:
                    $rrule->freq  = Calendar_Model_Rrule::FREQ_WEEKLY;
                    $rrule->byday = $this->_convertBitMaskToDay((int)$xmlData->Recurrence->DayOfWeek);
                    break;
                     
                case self::RECUR_TYPE_MONTHLY:
                    $rrule->freq = Calendar_Model_Rrule::FREQ_MONTHLY;
                    $rrule->bymonthday = (int)$xmlData->Recurrence->DayOfMonth;
                    break;
                     
                case self::RECUR_TYPE_MONTHLY_DAYN:
                    $rrule->freq = Calendar_Model_Rrule::FREQ_MONTHLY;
                    
                    $week = (int)$xmlData->Recurrence->WeekOfMonth;
                    $day  = (int)$xmlData->Recurrence->DayOfWeek;
                    $byDay  = $week == 5 ? -1 : $week;
                    $byDay .= $this->_convertBitMaskToDay($day);
                    
                    $rrule->byday = $byDay;
                    break;
                     
                case self::RECUR_TYPE_YEARLY:
                    $rrule->freq       = Calendar_Model_Rrule::FREQ_YEARLY;
                    $rrule->bymonth    = (int)$xmlData->Recurrence->MonthOfYear;
                    $rrule->bymonthday = (int)$xmlData->Recurrence->DayOfMonth;
                    break;
                     
                case self::RECUR_TYPE_YEARLY_DAYN:
                    $rrule->freq    = Calendar_Model_Rrule::FREQ_YEARLY;
                    $rrule->bymonth = (int)$xmlData->Recurrence->MonthOfYear;
                    
                    $week = (int)$xmlData->Recurrence->WeekOfMonth;
                    $day  = (int)$xmlData->Recurrence->DayOfWeek;
                    $byDay  = $week == 5 ? -1 : $week;
                    $byDay .= $this->_convertBitMaskToDay($day);
                    
                    $rrule->byday = $byDay;
                    break; 
            }
            $rrule->interval = isset($xmlData->Recurrence->Interval) ? (int)$xmlData->Recurrence->Interval : 1;
            
            if(isset($xmlData->Recurrence->Until)) {
                $timeStamp = $this->_convertISOToTs((string)$xmlData->Recurrence->Until);
                $rrule->until = new Zend_Date($timeStamp, NULL);
            } else {
                $rrule->until = null;
            }            
            
            $event->rrule = $rrule;
        }
        
        if(empty($event->organizer)) {
            $event->organizer = Tinebase_Core::getUser()->contact_id;
        }
        
        // event should be valid now
        $event->isValid();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " eventData " . print_r($event->toArray(), true));
        
        return $event;
    }
    
    /**
     * convert contact from xml to Calendar_Model_EventFilter
     *
     * @param SimpleXMLElement $_data
     * @return array
     */
    protected function _toTineFilterArray(SimpleXMLElement $_data)
    {
        $xmlData = $_data->children('uri:Calendar');
        
        $filterArray = array();
        
        foreach($this->_mapping as $fieldName => $value) {
            if(isset($xmlData->$fieldName)) {
                $filterArray[] = array(
                    'field'     => $value,
                    'operator'  => 'equals',
                    'value'     => (string)$xmlData->$fieldName
                );
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filterArray, true));
        
        return $filterArray;
    }
    
    /**
     * converts an iso formated date into a timestamp
     *
     * @param  string Zend_Date::ISO8601 representation of a datetime filed
     * @return int    UNIX Timestamp
     */
    protected function _convertISOToTs($_ISO)
    {
        $matches = array();
        
        preg_match("/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z/", $_ISO, $matches);

        if (count($matches) !== 7) {
            throw new Tinebase_Exception_UnexpectedValue("invalid date format $_ISO");
        }
        
        list($match, $year, $month, $day, $hour, $minute, $second) = $matches;
        return  mktime($hour, $minute, $second, $month, $day, $year);
    }
    
    /**
     * return contentfilter array
     * 
     * @param $_filterType
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getContentFilter(Tinebase_Model_Filter_FilterGroup $_filter, $_filterType)
    {
        $_filter->addFilter(new Tinebase_Model_Filter_Text('recurid', 'isnull', null));
        
        if(in_array($_filterType, $this->_filterArray)) {
            switch($_filterType) {
                case self::FILTER_2_WEEKS_BACK:
                    $from = Zend_Date::now()->subWeek(2);
                    break;
                case self::FILTER_1_MONTH_BACK:
                    $from = Zend_Date::now()->subMonth(2);
                    break;
                case self::FILTER_3_MONTHS_BACK:
                    $from = Zend_Date::now()->subMonth(3);
                    break;
                case self::FILTER_6_MONTHS_BACK:
                    $from = Zend_Date::now()->subMonth(6);
                    break;
            }
            // next 10 years
            $to = Zend_Date::now()->addYear(10);
            
            // remove all 'old' period filters
            $_filter->removeFilter('period');
            
            // add period filter
            $_filter->addFilter(new Calendar_Model_PeriodFilter('period', 'within', array(
                'from'  => $from,
                'until' => $to
            )));
        }
    }
    
    /**
     * return list of supported folders for this backend
     *
     * @return array
     */
    public function getSupportedFolders()
    {
        // only the IPhone supports multiple folders for calendars currently
        if(strtolower($this->_device->devicetype) == 'iphone') {
        
            // get the folders the user has access to
            $allowedFolders = $this->_getSyncableFolders();
            
            $wantedFolders = null;
            // maybe the user has defined a filter to limit the search results
            if(!empty($this->_device->calendarfilter_id)) {
                $persistentFilter = Tinebase_PersistentFilter::getFilterById($this->_device->calendarfilter_id);
                
                foreach($persistentFilter as $filter) {
                    if($filter instanceof Tinebase_Model_Filter_Container) {
                        $wantedFolders = array_flip($filter->getContainerIds());
                    }
                }
            }
            
            $folders = $wantedFolders === null ? $allowedFolders : array_intersect_key($allowedFolders, $wantedFolders);
            
        } else {
            
            $folders[$this->_specialFolderName] = array(
                'folderId'      => $this->_specialFolderName,
                'parentId'      => 0,
                'displayName'   => $this->_applicationName,
                'type'          => $this->_defaultFolderType
            );
            
        }
        
        return $folders;
    }
    
    protected function _getSyncableFolders()
    {
        $folders = array();
        
        $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_SYNC);
        foreach ($containers as $container) {
            $folders[$container->id] = array(
                'folderId'      => $container->id,
                'parentId'      => 0,
                'displayName'   => $container->name,
                'type'          => (count($folders) == 0) ? $this->_defaultFolderType : $this->_folderType
            );
        }
                
        // we ignore the folders of others users and shared folders for now
                
        return $folders;
    }
}