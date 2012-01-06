<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Controller
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * controller events class
 * 
 * @package     ActiveSync
 * @subpackage  Controller
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
     * busy status constants
     */
    const BUSY_STATUS_FREE      = 0;
    const BUSY_STATUS_TENATTIVE = 1;
    const BUSY_STATUS_BUSY      = 2;
    /**
     * available filters
     * 
     * @var array
     */
    protected $_filterArray = array(
        ActiveSync_Command_Sync::FILTER_2_WEEKS_BACK,
        ActiveSync_Command_Sync::FILTER_1_MONTH_BACK,
        ActiveSync_Command_Sync::FILTER_3_MONTHS_BACK,
        ActiveSync_Command_Sync::FILTER_6_MONTHS_BACK
    );
    
    /**
     * mapping of attendee status
     *
     * NOTE: not surjektive
     * @var array
     */
    protected $_attendeeStatusMapping = array(
        self::ATTENDEE_STATUS_UNKNOWN       => Calendar_Model_Attender::STATUS_NEEDSACTION,
        self::ATTENDEE_STATUS_TENTATIVE     => Calendar_Model_Attender::STATUS_TENTATIVE,
        self::ATTENDEE_STATUS_ACCEPTED      => Calendar_Model_Attender::STATUS_ACCEPTED,
        self::ATTENDEE_STATUS_DECLINED      => Calendar_Model_Attender::STATUS_DECLINED,
        //self::ATTENDEE_STATUS_NOTRESPONDED  => Calendar_Model_Attender::STATUS_NEEDSACTION
    );
    
    /**
     * mapping of busy status
     *
     * NOTE: not surjektive
     * @var array
     */
    protected $_busyStatusMapping = array(
        self::BUSY_STATUS_FREE      => Calendar_Model_Attender::STATUS_DECLINED,
        self::BUSY_STATUS_TENATTIVE => Calendar_Model_Attender::STATUS_TENTATIVE,
        self::BUSY_STATUS_BUSY      => Calendar_Model_Attender::STATUS_ACCEPTED
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
     * default container for new entries
     * 
     * @var string
     */
    protected $_defaultFolder       = ActiveSync_Preference::DEFAULTCALENDAR;
    
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
    protected $_filterProperty      = 'calendarfilter_id';
    
    /**
     * name of the contentcontoller class
     * 
     * @var string
     */
    protected $_contentControllerName = 'Calendar_Controller_MSEventFacade';
    
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
        $data = $_serverId instanceof Tinebase_Record_Abstract ? $_serverId : $this->_contentController->get($_serverId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " calendar data " . print_r($data->toArray(), true));
        
        // add calendar namespace
        $_xmlNode->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:Calendar', 'uri:Calendar');
        
        foreach($this->_mapping as $key => $value) {
            $nodeContent = null;
            
            if(!empty($data->$value)) {
                
                switch($value) {
                    case 'dtend':
                        if ($data->is_all_day_event == true) {
                            // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in AS
                            $dtend = clone $data->dtend;
                            
                            $dtend->addSecond($dtend->get('s') == 59 ? 1 : 0);
                            $dtend->addMinute($dtend->get('i') == 59 ? 1 : 0);

                            $nodeContent = $dtend->format('Ymd\THis') . 'Z';
                        } else {
                            $nodeContent = $data->dtend->format('Ymd\THis') . 'Z';
                        }
                        break;
                    case 'dtstart':
                        $nodeContent = $data->$value->format('Ymd\THis') . 'Z';
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
                
                // strip off any non printable control characters
                if (!ctype_print($nodeContent)) {
                    $nodeContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', null, $nodeContent);
                }
                
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
                // NOTE: option minutes_before is always calculated by Calendar_Controller_Event::_inspectAlarmSet
                $minutesBefore = (int) $alarm->getOption('minutes_before');
                if ($minutesBefore >= 0) {
                    $_xmlNode->appendChild(new DOMElement('Reminder', $minutesBefore, 'uri:Calendar'));
                }
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
            
            // required field
            $recurrence->appendChild(new DOMElement('Interval', $rrule->interval, 'uri:Calendar'));
            
            if($rrule->until instanceof DateTime) {
                $recurrence->appendChild(new DOMElement('Until', $rrule->until->format('Ymd\THis') . 'Z', 'uri:Calendar'));
            }
            
            // handle exceptions of repeating events
            if($data->exdate instanceof Tinebase_Record_RecordSet && $data->exdate->count() > 0) {
                $exceptionsTag = $_xmlNode->appendChild(new DOMElement('Exceptions', null, 'uri:Calendar'));
                
                foreach ($data->exdate as $exception) {
                    $exceptionTag = $exceptionsTag->appendChild(new DOMElement('Exception', null, 'uri:Calendar'));
                    
                    $exceptionTag->appendChild(new DOMElement('Deleted', (int)$exception->is_deleted, 'uri:Calendar'));
                    $exceptionTag->appendChild(new DOMElement('ExceptionStartTime', $exception->getOriginalDtStart()->format('Ymd\THis') . 'Z', 'uri:Calendar'));
                    
                    if ((int)$exception->is_deleted === 0) {
                        $this->appendXML($exceptionTag, $_folderId, $exception, $_options, $_neverTruncate);
                    }
                }
            }
            
        }

        if(count($data->attendee) > 0) {
            // fill attendee cache
            Calendar_Model_Attender::resolveAttendee($data->attendee, FALSE);
            
            $attendees = $_xmlNode->ownerDocument->createElementNS('uri:Calendar', 'Attendees');
            
            foreach($data->attendee as $attenderObject) {
                $attendee = $attendees->appendChild(new DOMElement('Attendee', null, 'uri:Calendar'));
                $attendee->appendChild(new DOMElement('Name', $attenderObject->getName(), 'uri:Calendar'));
                $attendee->appendChild(new DOMElement('Email', $attenderObject->getEmail(), 'uri:Calendar'));
                if(version_compare($this->_device->acsversion, '12.0', '>=') === true) {
                    $acsType = array_search($attenderObject->role, $this->_attendeeTypeMapping);
                    $attendee->appendChild(new DOMElement('AttendeeType', $acsType ? $acsType : self::ATTENDEE_TYPE_REQUIRED , 'uri:Calendar'));
                    
                    $acsStatus = array_search($attenderObject->status, $this->_attendeeStatusMapping);
                    $attendee->appendChild(new DOMElement('AttendeeStatus', $acsStatus ? $acsStatus : self::ATTENDEE_STATUS_UNKNOWN, 'uri:Calendar'));
                }
            }
            
            if ($attendees->hasChildNodes()) {
                $_xmlNode->appendChild($attendees);
            }
            
            // set own status
            if (($ownAttendee = Calendar_Model_Attender::getOwnAttender($data->attendee)) !== null && ($busyType = array_search($ownAttendee->status, $this->_busyStatusMapping)) !== false) {
                $_xmlNode->appendChild(new DOMElement('BusyStatus', $busyType, 'uri:Calendar'));
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
        $_xmlNode->appendChild(new DOMElement('Sensitivity', 0, 'uri:Calendar'));
        $_xmlNode->appendChild(new DOMElement('DtStamp', $data->creation_time->format('Ymd\THis') . 'Z', 'uri:Calendar'));
        $_xmlNode->appendChild(new DOMElement('UID', $data->uid, 'uri:Calendar'));
        
        if(!empty($data->organizer)) {
            try {
                $contact = Addressbook_Controller_Contact::getInstance()->get($data->organizer);
                
                $_xmlNode->appendChild(new DOMElement('OrganizerName', $contact->n_fileas, 'uri:Calendar'));
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
     * update existing entry
     *
     * @param unknown_type $_collectionId
     * @param string $_id
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function change($_folderId, $_id, SimpleXMLElement $_data)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_folderId Id: $_id");
    
        $oldEntry = $this->_contentController->get($_id);
    
        $entry = $this->toTineModel($_data, $oldEntry);
        $entry->last_modified_time = $this->_syncTimeStamp;
        if ($_folderId != $this->_specialFolderName) {
            $entry->container_id = $_folderId;
        }
                
        if ($entry->exdate instanceof Tinebase_Record_RecordSet) {
            foreach ($entry->exdate as $exdate) {
                if ($exdate->is_deleted == false) {
                    $exdate->container_id = $entry->container_id;
                }
            }
        }

        if ($oldEntry->organizer == Tinebase_Core::getUser()->contact_id) {
            $entry = $this->_contentController->update($entry);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " current user is not organizer => update attendee status only ");
            
            $ownAttendee = Calendar_Model_Attender::getOwnAttender($entry->attendee);
            
            if ($_folderId != $this->_specialFolderName) {
                $ownAttendee->displaycontainer_id = $_folderId;
            }
            
            $entry = Calendar_Controller_MSEventFacade::getInstance()->attenderStatusUpdate($entry, $ownAttendee);
        }
    
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " updated entry id " . $entry->getId());
    
        return $entry;
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
    public function toTineModel(SimpleXMLElement $_data, $_entry = null)
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
                        $event->$value = new Tinebase_DateTime((string)$xmlData->$fieldName);
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
        if (version_compare($this->_device->acsversion, '12.0', '>=') === true) {
            $event->description = isset($airSyncBase->Body) ? (string)$airSyncBase->Body->Data : null;
        } else {
            $event->description = isset($xmlData->Body) ? (string)$xmlData->Body : null;
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
        
        if(! $event->attendee instanceof Tinebase_Record_RecordSet) {
            $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        }
        
        if(isset($xmlData->Attendees)) {
            $newAttendees = array();
            
            foreach($xmlData->Attendees->Attendee as $attendee) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " attendee email " . $attendee->Email);
                
                if(isset($attendee->AttendeeType) && array_key_exists((int)$attendee->AttendeeType, $this->_attendeeTypeMapping)) {
                    $role = $this->_attendeeTypeMapping[(int)$attendee->AttendeeType];
                } else {
                    $role = Calendar_Model_Attender::ROLE_REQUIRED;
                }
                
                // AttendeeStatus send only on repsonse
                
                if (preg_match('/(?P<firstName>\S*) (?P<lastNameName>\S*)/', (string)$attendee->Name, $matches)) {
                    $firstName = $matches['firstName'];
                    $lastName  = $matches['lastNameName'];
                } else {
                    $firstName = null;
                    $lastName  = $attendee->Name;
                }
                
                // @todo handle resources
                $newAttendees[] = array(
                    'userType'  => Calendar_Model_Attender::USERTYPE_USER,
                    'firstName' => $firstName,
                	'lastName'  => $lastName,
                    #'partStat'  => $status,
                    'role'      => $role,
                    'email'     => (string)$attendee->Email
                );
            }   

            Calendar_Model_Attender::emailsToAttendee($event, $newAttendees);
        }
        
        // new event, add current user as participant
        if($event->getId() == null) {
            $selfContactId = Tinebase_Core::getUser()->contact_id;
            $selfAttender = $event->attendee
                ->filter('user_type', Calendar_Model_Attender::USERTYPE_USER)
                ->filter('user_id', $selfContactId);
                    
            if (count($selfAttender) == 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " added current user as attender for new event ");
                $newAttender = new Calendar_Model_Attender(array(
                    'user_id'   => $selfContactId,
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'status'    => Calendar_Model_Attender::STATUS_ACCEPTED,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED
                ));
                $event->attendee->addRecord($newAttender);
            }
        }
        
        if (isset($xmlData->BusyStatus) && ($ownAttendee = Calendar_Model_Attender::getOwnAttender($event->attendee)) !== null) {
            if (isset($this->_busyStatusMapping[(string)$xmlData->BusyStatus])) {
                $ownAttendee->status = $this->_busyStatusMapping[(string)$xmlData->BusyStatus];
            } else {
                $ownAttendee->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
            }
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
                $rrule->until = new Tinebase_DateTime((string)$xmlData->Recurrence->Until);
                // until ends at 23:59:59 in Tine 2.0 but at 00:00:00 in Windows CE (local user time)
                if ($rrule->until->format('s') == '00') {
                    $rrule->until->addHour(23)->addMinute(59)->addSecond(59);
                }
            } else {
                $rrule->until = null;
            }            
            
            $event->rrule = $rrule;
            
            // handle exceptions from recurrence
            if(isset($xmlData->Exceptions)) {
                $exdates = new Tinebase_Record_RecordSet('Calendar_Model_Event');
                
                foreach ($xmlData->Exceptions->Exception as $exception) {
                    $eventException = new Calendar_Model_Event(array(
                        'recurid' => new Tinebase_DateTime((string)$exception->ExceptionStartTime)
                    ));
                    
                    if ((int)$exception->Deleted === 0) {
                        $eventException->is_deleted = false;
                        $this->toTineModel($exception, $eventException);
                    } else {
                        $eventException->is_deleted = true;
                    }
                    
                    $exdates->addRecord($eventException);
                }
                
                $event->exdate = $exdates;
            }
        } else {
            $event->rrule  = null;
            $event->exdate = null;
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
        
        foreach($this->_mapping as $fieldName => $field) {
            if(isset($xmlData->$fieldName)) {
                switch ($field) {
                    case 'dtend':
                    case 'dtstart':
                        $value = new Tinebase_DateTime((string)$xmlData->$fieldName);
                        break;
                        
                    default:
                        $value = (string)$xmlData->$fieldName;
                        break;
                        
                }
                $filterArray[] = array(
                    'field'     => $field,
                    'operator'  => 'equals',
                    'value'     => $value
                );
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filterArray, true));
        
        return $filterArray;
    }
    
    /**
     * return contentfilter array
     * 
     * @param $_filterType
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getContentFilter(Tinebase_Model_Filter_FilterGroup $_filter, $_filterType)
    {
        if(in_array($_filterType, $this->_filterArray)) {
            switch($_filterType) {
                case ActiveSync_Command_Sync::FILTER_2_WEEKS_BACK:
                    $from = Tinebase_DateTime::now()->subWeek(2);
                    break;
                case ActiveSync_Command_Sync::FILTER_1_MONTH_BACK:
                    $from = Tinebase_DateTime::now()->subMonth(2);
                    break;
                case ActiveSync_Command_Sync::FILTER_3_MONTHS_BACK:
                    $from = Tinebase_DateTime::now()->subMonth(3);
                    break;
                case ActiveSync_Command_Sync::FILTER_6_MONTHS_BACK:
                    $from = Tinebase_DateTime::now()->subMonth(6);
                    break;
            }
            // next 10 years
            $to = Tinebase_DateTime::now()->addYear(10);
            
            // remove all 'old' period filters
            $_filter->removeFilter('period');
            
            // add period filter
            $_filter->addFilter(new Calendar_Model_PeriodFilter('period', 'within', array(
                'from'  => $from,
                'until' => $to
            )));
        }
    }
}
