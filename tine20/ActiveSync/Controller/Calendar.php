<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * available filters
     * 
     * @var array
     */
    protected $_filterArray = array(
        Syncroton_Command_Sync::FILTER_2_WEEKS_BACK,
        Syncroton_Command_Sync::FILTER_1_MONTH_BACK,
        Syncroton_Command_Sync::FILTER_3_MONTHS_BACK,
        Syncroton_Command_Sync::FILTER_6_MONTHS_BACK
    );
    
    /**
     * mapping of attendee status
     *
     * NOTE: not surjektive
     * @var array
     */
    protected $_attendeeStatusMapping = array(
        Syncroton_Model_EventAttendee::ATTENDEE_STATUS_UNKNOWN       => Calendar_Model_Attender::STATUS_NEEDSACTION,
        Syncroton_Model_EventAttendee::ATTENDEE_STATUS_TENTATIVE     => Calendar_Model_Attender::STATUS_TENTATIVE,
        Syncroton_Model_EventAttendee::ATTENDEE_STATUS_ACCEPTED      => Calendar_Model_Attender::STATUS_ACCEPTED,
        Syncroton_Model_EventAttendee::ATTENDEE_STATUS_DECLINED      => Calendar_Model_Attender::STATUS_DECLINED,
        //self::ATTENDEE_STATUS_NOTRESPONDED  => Calendar_Model_Attender::STATUS_NEEDSACTION
    );
    
    /**
     * mapping of busy status
     *
     * NOTE: not surjektive
     * @var array
     */
    protected $_busyStatusMapping = array(
        Syncroton_Model_Event::BUSY_STATUS_FREE      => Calendar_Model_Attender::STATUS_DECLINED,
        Syncroton_Model_Event::BUSY_STATUS_TENATTIVE => Calendar_Model_Attender::STATUS_TENTATIVE,
        Syncroton_Model_Event::BUSY_STATUS_BUSY      => Calendar_Model_Attender::STATUS_ACCEPTED
    );
    
    /**
     * mapping of attendee types
     * 
     * NOTE: recources need extra handling!
     * @var array
     */
    protected $_attendeeTypeMapping = array(
        Syncroton_Model_EventAttendee::ATTENDEE_TYPE_REQUIRED => Calendar_Model_Attender::ROLE_REQUIRED,
        Syncroton_Model_EventAttendee::ATTENDEE_TYPE_OPTIONAL => Calendar_Model_Attender::ROLE_OPTIONAL,
        Syncroton_Model_EventAttendee::ATTENDEE_TYPE_RESOURCE => Calendar_Model_Attender::USERTYPE_RESOURCE
    );
    
    /**
     * mapping of recur types
     *
     * NOTE: not surjektive
     * @var array
     */
    protected $_recurTypeMapping = array(
        Syncroton_Model_EventRecurrence::TYPE_DAILY          => Calendar_Model_Rrule::FREQ_DAILY,
        Syncroton_Model_EventRecurrence::TYPE_WEEKLY         => Calendar_Model_Rrule::FREQ_WEEKLY,
        Syncroton_Model_EventRecurrence::TYPE_MONTHLY        => Calendar_Model_Rrule::FREQ_MONTHLY,
        Syncroton_Model_EventRecurrence::TYPE_MONTHLY_DAYN   => Calendar_Model_Rrule::FREQ_MONTHLY,
        Syncroton_Model_EventRecurrence::TYPE_YEARLY         => Calendar_Model_Rrule::FREQ_YEARLY,
        Syncroton_Model_EventRecurrence::TYPE_YEARLY_DAYN    => Calendar_Model_Rrule::FREQ_YEARLY,
    );
    
    /**
     * mapping of weekdays
     * 
     * NOTE: ActiveSync uses a bitmask
     * @var array
     */
    protected $_recurDayMapping = array(
        Calendar_Model_Rrule::WDAY_SUNDAY       => Syncroton_Model_EventRecurrence::RECUR_DOW_SUNDAY,
        Calendar_Model_Rrule::WDAY_MONDAY       => Syncroton_Model_EventRecurrence::RECUR_DOW_MONDAY,
        Calendar_Model_Rrule::WDAY_TUESDAY      => Syncroton_Model_EventRecurrence::RECUR_DOW_TUESDAY,
        Calendar_Model_Rrule::WDAY_WEDNESDAY    => Syncroton_Model_EventRecurrence::RECUR_DOW_WEDNESDAY,
        Calendar_Model_Rrule::WDAY_THURSDAY     => Syncroton_Model_EventRecurrence::RECUR_DOW_THURSDAY,
        Calendar_Model_Rrule::WDAY_FRIDAY       => Syncroton_Model_EventRecurrence::RECUR_DOW_FRIDAY,
        Calendar_Model_Rrule::WDAY_SATURDAY     => Syncroton_Model_EventRecurrence::RECUR_DOW_SATURDAY
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
        'Reminder'          => 'alarms',
        //'Sensitivity'       => 'class',
        'Subject'           => 'summary',
        'Body'              => 'description',
        'StartTime'         => 'dtstart',
        //'UID'               => 'uid',             // not used outside from Tine 2.0
        //'MeetingStatus'     => 'status_id',
        'Attendees'         => 'attendee',
        'Categories'        => 'tags',
        'Recurrence'        => 'rrule',
        'Exceptions'        => 'exdate',
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
    protected $_defaultFolderType   = Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR;
    
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
    protected $_folderType          = Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR_USER_CREATED;
    
    /**
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty      = 'calendarfilterId';
    
    /**
     * name of the contentcontoller class
     * 
     * @var string
     */
    protected $_contentControllerName = 'Calendar_Controller_MSEventFacade';
    
    /**
     * list of devicetypes with wrong busy status default (0 = FREE)
     * 
     * @var array
     */
    protected $_devicesWithWrongBusyStatusDefault = array(
        'samsunggti9100', // Samsung Galaxy S-2
        'samsunggtn7000', // Samsung Galaxy Note 
        'samsunggti9300', // Samsung Galaxy S-3
    );
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Controller_Abstract::toSyncrotonModel()
     * @todo handle BusyStatus
     */
    public function toSyncrotonModel($entry, array $options = array())
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . " calendar data " . print_r($entry->toArray(), true));
        
        $syncrotonEvent = new Syncroton_Model_Event();
        
        foreach ($this->_mapping as $syncrotonProperty => $tine20Property) {
            if (empty($entry->$tine20Property) && $entry->$tine20Property != '0' || count($entry->$tine20Property) === 0) {
                continue;
            }
            
            switch($tine20Property) {
                case 'alarms':
                    $entry->$tine20Property->sort('alarm_time');
                    $alarm = $entry->alarms->getFirstRecord();
                    
                    if($alarm instanceof Tinebase_Model_Alarm) {
                        // NOTE: option minutes_before is always calculated by Calendar_Controller_Event::_inspectAlarmSet
                        // @todo does not work as expected currently
                        $minutesBefore = (int) $alarm->getOption('minutes_before');
                        
                        // avoid negative alarms which may break phones
                        if ($minutesBefore >= 0) {
                            $syncrotonEvent->$syncrotonProperty = $minutesBefore;
                        }
                    }
                    
                    break;
                    
                case 'attendee':
                    // fill attendee cache
                    Calendar_Model_Attender::resolveAttendee($entry->$tine20Property, FALSE);
                    
                    $attendees = array();
                
                    foreach($entry->$tine20Property as $attenderObject) {
                        $attendee = new Syncroton_Model_EventAttendee();
                        $attendee->Name = $attenderObject->getName();
                        $attendee->Email = $attenderObject->getEmail();
                        
                        $acsType = array_search($attenderObject->role, $this->_attendeeTypeMapping);
                        $attendee->AttendeeType = $acsType ? $acsType : Syncroton_Model_EventAttendee::ATTENDEE_TYPE_REQUIRED;
            
                        $acsStatus = array_search($attenderObject->status, $this->_attendeeStatusMapping);
                        $attendee->AttendeeStatus = $acsStatus ? $acsStatus : Syncroton_Model_EventAttendee::ATTENDEE_STATUS_UNKNOWN;
                        
                        $attendees[] = $attendee;
                    }
                    
                    $syncrotonEvent->$syncrotonProperty = $attendees;
                    
                    // set own status
                    if (($ownAttendee = Calendar_Model_Attender::getOwnAttender($entry->attendee)) !== null && ($busyType = array_search($ownAttendee->status, $this->_busyStatusMapping)) !== false) {
                        $syncrotonEvent->BusyStatus = $busyType;
                    }
                    
                    break;
                    
                case 'description':
                    $syncrotonEvent->$syncrotonProperty = new Syncroton_Model_EmailBody(array(
                        'Type' => Syncroton_Model_EmailBody::TYPE_PLAINTEXT,
                        'Data' => $entry->$tine20Property
                    ));
                
                    break;
                
                case 'dtend':
                    if($entry->$tine20Property instanceof DateTime) {
                        if ($entry->is_all_day_event == true) {
                            // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in AS
                            $dtend = clone $entry->$tine20Property;
                            $dtend->addSecond($dtend->get('s') == 59 ? 1 : 0);
                            $dtend->addMinute($dtend->get('i') == 59 ? 1 : 0);

                            $syncrotonEvent->$syncrotonProperty = $dtend;
                        } else {
                            $syncrotonEvent->$syncrotonProperty = $entry->$tine20Property;
                        }
                    }
                    
                    break;
                    
                case 'dtstart':
                    if($entry->$tine20Property instanceof DateTime) {
                        $syncrotonEvent->$syncrotonProperty = $entry->$tine20Property;
                    }
                    
                    break;
                    
                case 'exdate':
                    // handle exceptions of repeating events
                    if($entry->$tine20Property instanceof Tinebase_Record_RecordSet && $entry->$tine20Property->count() > 0) {
                        $exceptions = array();
                    
                        foreach ($entry->exdate as $exdate) {
                            $exception = new Syncroton_Model_EventException();
                    
                            $exception->Deleted            = (int)$exdate->is_deleted;
                            $exception->ExceptionStartTime = $exdate->getOriginalDtStart();
                    
                            if ((int)$exdate->is_deleted === 0) {
                                $exceptionSyncrotonEvent = $this->toSyncrotonModel($exdate);
                                foreach ($exception->getProperties() as $property) {
                                    if (isset($exceptionSyncrotonEvent->$property)) {
                                        $exception->$property = $exceptionSyncrotonEvent->$property;
                                    }
                                }
                                unset($exceptionSyncrotonEvent);
                            }
                            
                            $exceptions[] = $exception;
                        }
                        
                        $syncrotonEvent->$syncrotonProperty = $exceptions;
                    }
                    
                    break;
                    
                case 'rrule':
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . " calendar rrule " . $entry->$tine20Property);
                        
                    $rrule = Calendar_Model_Rrule::getRruleFromString($entry->$tine20Property);
                    
                    $recurrence = new Syncroton_Model_EventRecurrence();
                    
                    // required fields
                    switch($rrule->freq) {
                        case Calendar_Model_Rrule::FREQ_DAILY:
                            $recurrence->Type = Syncroton_Model_EventRecurrence::TYPE_DAILY;
                            
                            break;
                    
                        case Calendar_Model_Rrule::FREQ_WEEKLY:
                            $recurrence->Type      = Syncroton_Model_EventRecurrence::TYPE_WEEKLY;
                            $recurrence->DayOfWeek = $this->_convertDayToBitMask($rrule->byday);
                            
                            break;
                    
                        case Calendar_Model_Rrule::FREQ_MONTHLY:
                            if(!empty($rrule->bymonthday)) {
                                $recurrence->Type       = Syncroton_Model_EventRecurrence::TYPE_MONTHLY;
                                $recurrence->DayOfMonth = $rrule->bymonthday;
                            } else {
                                $weekOfMonth = (int) substr($rrule->byday, 0, -2);
                                $weekOfMonth = ($weekOfMonth == -1) ? 5 : $weekOfMonth;
                                $dayOfWeek   = substr($rrule->byday, -2);
                    
                                $recurrence->Type        = Syncroton_Model_EventRecurrence::TYPE_MONTHLY_DAYN;
                                $recurrence->WeekOfMonth = $weekOfMonth;
                                $recurrence->DayOfWeek   = $this->_convertDayToBitMask($dayOfWeek);
                            }
                            
                            break;
                    
                        case Calendar_Model_Rrule::FREQ_YEARLY:
                            if(!empty($rrule->bymonthday)) {
                                $recurrence->Type        = Syncroton_Model_EventRecurrence::TYPE_YEARLY;
                                $recurrence->DayOfMonth  = $rrule->bymonthday;
                                $recurrence->MonthOfYear = $rrule->bymonth;
                            } else {
                                $weekOfMonth = (int) substr($rrule->byday, 0, -2);
                                $weekOfMonth = ($weekOfMonth == -1) ? 5 : $weekOfMonth;
                                $dayOfWeek   = substr($rrule->byday, -2);
                    
                                $recurrence->Type        = Syncroton_Model_EventRecurrence::TYPE_YEARLY_DAYN;
                                $recurrence->WeekOfMonth = $weekOfMonth;
                                $recurrence->DayOfWeek   = $this->_convertDayToBitMask($dayOfWeek);
                                $recurrence->MonthOfYear = $rrule->bymonth;
                            }
                            
                            break;
                    }
                    
                    // required field
                    $recurrence->Interval = $rrule->interval ? $rrule->interval : 1;
                    
                    if($rrule->count) {
                        $recurrence->Occurrences = $rrule->count;
                    } else if($rrule->until instanceof DateTime) {
                        $recurrence->Until = $rrule->until;
                    }
                    
                    $syncrotonEvent->$syncrotonProperty = $recurrence;
                    
                    break;
                    
                // @todo validate tags are working
                case 'tags':
                    $syncrotonEvent->$syncrotonProperty = (array) $entry->$tine20Property;
                    
                    break;
                    
                default:
                    $syncrotonEvent->$syncrotonProperty = $entry->$tine20Property;
                    
                    break;
            }
        }
        
        $timeZoneConverter = ActiveSync_TimezoneConverter::getInstance(
            Tinebase_Core::getLogger(),
            Tinebase_Core::get(Tinebase_Core::CACHE)
        );
        
        $syncrotonEvent->Timezone = $timeZoneConverter->encodeTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        
        $syncrotonEvent->MeetingStatus = 1;
        $syncrotonEvent->Sensitivity = 0;
        $syncrotonEvent->DtStamp = $entry->creation_time;
        $syncrotonEvent->UID = $entry->uid;
        
        $this->_addOrganizer($syncrotonEvent, $entry);
        
        return $syncrotonEvent;
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Controller_Abstract::updateEntry()
     */
    public function updateEntry($folderId, $serverId, Syncroton_Model_IEntry $entry)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $folderId Id: $serverId");
    
        $oldEntry = $this->_contentController->get($serverId);
    
        $updatedEmtry = $this->toTineModel($entry, $oldEntry);
        $updatedEmtry->last_modified_time = new Tinebase_DateTime($this->_syncTimeStamp);
        if ($folderId != $this->_specialFolderName) {
            $updatedEmtry->container_id = $folderId;
        }
                
        if ($updatedEmtry->exdate instanceof Tinebase_Record_RecordSet) {
            foreach ($updatedEmtry->exdate as $exdate) {
                if ($exdate->is_deleted == false) {
                    $exdate->container_id = $updatedEmtry->container_id;
                }
            }
        }

        if ($oldEntry->organizer == Tinebase_Core::getUser()->contact_id) {
            $updatedEmtry = $this->_contentController->update($updatedEmtry);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " current user is not organizer => update attendee status only ");
            
            $ownAttendee = Calendar_Model_Attender::getOwnAttender($updatedEmtry->attendee);
            
            if ($_folderId != $this->_specialFolderName) {
                $ownAttendee->displaycontainer_id = $_folderId;
            }
            
            $updatedEmtry = Calendar_Controller_MSEventFacade::getInstance()->attenderStatusUpdate($updatedEmtry, $ownAttendee);
        }
    
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " updated entry id " . $updatedEmtry->getId());
    
        return $updatedEmtry->getId();
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
        
        for($bitmask = 1; $bitmask <= Syncroton_Model_EventRecurrence::RECUR_DOW_SATURDAY; $bitmask = $bitmask << 1) {
            $dayMatch = $_days & $bitmask;
            if($dayMatch === $bitmask) {
                $daysArray[] = array_search($bitmask, $this->_recurDayMapping);
            }
        }
        $result = implode(',', $daysArray);
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync_Controller_Abstract::toTineModel()
     */
    public function toTineModel(Syncroton_Model_IEntry $data, $entry = null)
    {
        if($entry instanceof Calendar_Model_Event) {
            $event = $entry;
        } else {
            $event = new Calendar_Model_Event(array(), true);
        }
        
        foreach($this->_mapping as $syncrotonProperty => $tine20Property) {
            if (!isset($data->$syncrotonProperty)) {
                $event->$tine20Property = null;
            
                continue;
            }
            
            switch($tine20Property) {
                case 'alarms':
                    // handled after switch statement
                    
                    break;
                    
                case 'attendee':
                    if (! $event->$tine20Property instanceof Tinebase_Record_RecordSet) {
                        $event->$tine20Property = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
                    }
                    
                    $newAttendees = array();
                
                    foreach($data->$syncrotonProperty as $attendee) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . " attendee email " . $attendee->Email);
                
                        if(isset($attendee->AttendeeType) && array_key_exists($attendee->AttendeeType, $this->_attendeeTypeMapping)) {
                            $role = $this->_attendeeTypeMapping[$attendee->AttendeeType];
                        } else {
                            $role = Calendar_Model_Attender::ROLE_REQUIRED;
                        }
                
                        // AttendeeStatus send only on repsonse
                
                        if (preg_match('/(?P<firstName>\S*) (?P<lastNameName>\S*)/', $attendee->Name, $matches)) {
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
                            'email'     => $attendee->Email
                        );
                    }
                    
                    Calendar_Model_Attender::emailsToAttendee($event, $newAttendees);
                    
                    break;
                    
                case 'exdate':
                    // handle exceptions from recurrence
                    $exdates = new Tinebase_Record_RecordSet('Calendar_Model_Event');
                
                    foreach ($data->$syncrotonProperty as $exception) {
                        if ($exception->Deleted === 0) {
                            $eventException = $this->toTineModel($exception);
                            $eventException->recurid    = new Tinebase_DateTime($exception->ExceptionStartTime);
                            $eventException->is_deleted = false;
                        } else {
                            $eventException = new Calendar_Model_Event(array(
                                'recurid'    => new Tinebase_DateTime($exception->ExceptionStartTime),
                                'is_deleted' => true
                            ));
                        }
                
                        $exdates->addRecord($eventException);
                    }
                
                    $event->$tine20Property = $exdates;
                    
                    break;
                    
                case 'description':
                    // @todo check $data->$fieldName->Type and convert to/from HTML if needed
                    if ($data->$syncrotonProperty instanceof Syncroton_Model_EmailBody) {
                        $event->$tine20Property = $data->$syncrotonProperty->Data;
                    } else {
                        $event->$tine20Property = null;
                    }
                
                    break;
                    
                case 'rrule':
                    // handle recurrence
                    if ($data->$syncrotonProperty instanceof Syncroton_Model_EventRecurrence && isset($data->$syncrotonProperty->Type)) {
                        $rrule = new Calendar_Model_Rrule();
                    
                        switch($data->$syncrotonProperty->Type) {
                            case Syncroton_Model_EventRecurrence::TYPE_DAILY:
                                $rrule->freq = Calendar_Model_Rrule::FREQ_DAILY;
                                
                                break;
                    
                            case Syncroton_Model_EventRecurrence::TYPE_WEEKLY:
                                $rrule->freq  = Calendar_Model_Rrule::FREQ_WEEKLY;
                                $rrule->byday = $this->_convertBitMaskToDay($data->$syncrotonProperty->DayOfWeek);
                                
                                break;
                                 
                            case Syncroton_Model_EventRecurrence::TYPE_MONTHLY:
                                $rrule->freq       = Calendar_Model_Rrule::FREQ_MONTHLY;
                                $rrule->bymonthday = $data->$syncrotonProperty->DayOfMonth;
                                
                                break;
                                 
                            case Syncroton_Model_EventRecurrence::TYPE_MONTHLY_DAYN:
                                $rrule->freq = Calendar_Model_Rrule::FREQ_MONTHLY;
                    
                                $week   = $data->$syncrotonProperty->WeekOfMonth;
                                $day    = $data->$syncrotonProperty->DayOfWeek;
                                $byDay  = $week == 5 ? -1 : $week;
                                $byDay .= $this->_convertBitMaskToDay($day);
                    
                                $rrule->byday = $byDay;
                                
                                break;
                                 
                            case Syncroton_Model_EventRecurrence::TYPE_YEARLY:
                                $rrule->freq       = Calendar_Model_Rrule::FREQ_YEARLY;
                                $rrule->bymonth    = (int)$xmlData->Recurrence->MonthOfYear;
                                $rrule->bymonthday = (int)$xmlData->Recurrence->DayOfMonth;
                                
                                break;
                                 
                            case Syncroton_Model_EventRecurrence::TYPE_YEARLY_DAYN:
                                $rrule->freq    = Calendar_Model_Rrule::FREQ_YEARLY;
                                $rrule->bymonth = $data->$syncrotonProperty->MonthOfYear;
                    
                                $week = $data->$syncrotonProperty->WeekOfMonth;
                                $day  = $data->$syncrotonProperty->DayOfWeek;
                                $byDay  = $week == 5 ? -1 : $week;
                                $byDay .= $this->_convertBitMaskToDay($day);
                    
                                $rrule->byday = $byDay;
                                
                                break;
                        }
                        
                        $rrule->interval = isset($data->$syncrotonProperty->Interval) ? $data->$syncrotonProperty->Interval : 1;
                    
                        if(isset($data->$syncrotonProperty->Occurrences)) {
                            $rrule->count = $data->$syncrotonProperty->Occurrences;
                            $rrule->until = null;
                        } else if(isset($data->$syncrotonProperty->Until)) {
                            $rrule->count = null;
                            $rrule->until = new Tinebase_DateTime($data->$syncrotonProperty->Until);
                            // until ends at 23:59:59 in Tine 2.0 but at 00:00:00 in Windows CE (local user time)
                            if ($rrule->until->format('s') == '00') {
                                $rrule->until->addHour(23)->addMinute(59)->addSecond(59);
                            }
                        } else {
                            $rrule->count = null;
                            $rrule->until = null;
                        }
                    
                        $event->rrule = $rrule;                    
                    }
                    
                    break;
                    
                    
                default:
                    if ($data->$syncrotonProperty instanceof DateTime) {
                        $event->$tine20Property = new Tinebase_DateTime($data->$syncrotonProperty);
                    } else {
                        $event->$tine20Property = $data->$syncrotonProperty;
                    }
                    
                    break;
            }
        }
        
        // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in AS
        if(isset($event->is_all_day_event) && $event->is_all_day_event == 1) {
            $event->dtend->subSecond(1);
        }
        
        // reset exdates
        if ($event->rrule === null) {
            $event->exdate = null;
        }
        
        // decode timezone data
        if (isset($data->Timezone)) {
            $timeZoneConverter = ActiveSync_TimezoneConverter::getInstance(
                Tinebase_Core::getLogger(),
                Tinebase_Core::get(Tinebase_Core::CACHE)
            );
        
            try {
                $timezone = $timeZoneConverter->getTimezone(
                    $data->Timezone,
                    Tinebase_Core::get(Tinebase_Core::USERTIMEZONE)
                );
                $event->originator_tz = $timezone;
            } catch (ActiveSync_TimezoneNotFoundException $e) {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . " timezone data not found " . $data->Timezone);
                $event->originator_tz = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
            }
        
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . " timezone data " . $event->originator_tz);
        }
        
        $this->_handleAlarms($data, $event);
        
        $this->_addCurrentUserAttender($event);
        
        $this->_handleBusyStatus($data, $event);
        
        if (empty($event->organizer)) {
            $event->organizer = Tinebase_Core::getUser()->contact_id;
        }
        
        // event should be valid now
        $event->isValid();
        
        #var_dump($event->toArray());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . " eventData " . print_r($event->toArray(), true));
        
        return $event;
    }
    
    /**
     * handle alarms / Reminder
     * 
     * @param SimpleXMLElement $xmlData
     * @param Calendar_Model_Event $event
     */
    protected function _handleAlarms($data, $event)
    {
        // NOTE: existing alarms are already filtered for CU by MSEF
        $event->alarms = $event->alarms instanceof Tinebase_Record_RecordSet ? $event->alarms : new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        $event->alarms->sort('alarm_time');
        
        $currentAlarm = $event->alarms->getFirstRecord();
        $alarm = NULL;
        
        if (isset($data->Reminder)) {
            $dtstart = clone $event->dtstart;
            
            $alarm = new Tinebase_Model_Alarm(array(
                'alarm_time'        => $dtstart->subMinute($data->Reminder),
                'minutes_before'    => in_array($data->Reminder, array(0, 5, 15, 30, 60, 120, 720, 1440, 2880)) ? $data->Reminder : 'custom',
                'model'             => 'Calendar_Model_Event'
            ));
            
            $alarmUpdate = Calendar_Controller_Alarm::getMatchingAlarm($event->alarms, $alarm);
            if (!$alarmUpdate) {
                // alarm not existing -> add it
                $event->alarms->addRecord($alarm);
                
                if ($currentAlarm) {
                    // ActiveSync supports one alarm only -> current got deleted
                    $event->alarms->removeRecord($currentAlarm);
                }
            }
        } else if ($currentAlarm) {
            // current alarm got removed
            $event->alarms->removeRecord($currentAlarm);
        }
    }
    
    /**
     * always add current user as participant
     * 
     * @param Calendar_Model_Event $event
     */
    protected function _addCurrentUserAttender($event)
    {        
        $ownAttender = Calendar_Model_Attender::getOwnAttender($event->attendee);
        
        if ($ownAttender === NULL) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . " Add current user as attender.");
            
            $newAttender = new Calendar_Model_Attender(array(
                'user_id'   => Tinebase_Core::getUser()->contact_id,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'status'    => Calendar_Model_Attender::STATUS_ACCEPTED,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            ));
            
            if (! $event->attendee instanceof Tinebase_Record_RecordSet) {
                $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
            }
            $event->attendee->addRecord($newAttender);
        }
    }
    
    /**
     * append organizer name and email
     *
     * @param Syncroton_Model_Event $syncrotonEvent
     * @param Calendar_Model_Event $event
     */
    protected function _addOrganizer(Syncroton_Model_Event $syncrotonEvent, Calendar_Model_Event $event)
    {
        $organizer = NULL;
        
        if(! empty($event->organizer)) {
            try {
                $organizer = $event->resolveOrganizer();
            } catch (Tinebase_Exception_AccessDenied $tead) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $tead);
            }
        }
    
        if ($organizer instanceof Addressbook_Model_Contact) {
            $organizerName = $organizer->n_fileas;
            $organizerEmail = $organizer->getPreferedEmailAddress();
        } else {
            // set the current account as organizer
            // if organizer is not set, you can not edit the event on the Motorola Milestone
            $organizerName = Tinebase_Core::getUser()->accountFullName;
            $organizerEmail = Tinebase_Core::getUser()->accountEmailAddress;
        }
    
        $syncrotonEvent->OrganizerName = $organizerName;
        if ($organizerEmail) {
            $syncrotonEvent->OrganizerEmail = $organizerEmail;
        }
    }
    
    /**
     * set status of own attender depending on BusyStatus
     * 
     * @param SimpleXMLElement $xmlData
     * @param Calendar_Model_Event $event
     * 
     * @todo move detection of special handling / device type to device library
     */
    protected function _handleBusyStatus($data, $event)
    {
        if (! isset($data->BusyStatus)) {
            return;
        }
        
        $ownAttender = Calendar_Model_Attender::getOwnAttender($event->attendee);
        if ($ownAttender === NULL) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' No own attender found.');
            return;
        }
        
        $busyStatus = $data->BusyStatus;
        if (in_array(strtolower($this->_device->devicetype), $this->_devicesWithWrongBusyStatusDefault)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Device uses a bad default setting. BUSY and FREE are mapped to ACCEPTED.');
            $busyStatusMapping = array(
                Syncroton_Model_Event::BUSY_STATUS_BUSY      => Calendar_Model_Attender::STATUS_ACCEPTED,
                Syncroton_Model_Event::BUSY_STATUS_TENATTIVE => Calendar_Model_Attender::STATUS_TENTATIVE,
                Syncroton_Model_Event::BUSY_STATUS_FREE      => Calendar_Model_Attender::STATUS_ACCEPTED
            );
        } else {
            $busyStatusMapping = $this->_busyStatusMapping;
        }
        
        if (isset($busyStatusMapping[$busyStatus])) {
            $ownAttender->status = $busyStatusMapping[$busyStatus];
        } else {
            $ownAttender->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        }
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
     * @param  int $_filterType
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getContentFilter($_filterType)
    {
        $filter = parent::_getContentFilter($_filterType);
        
        // no persistent filter set -> add default filter
        // @todo what is this good for? I think should be handled in _addContainerFilter. LK
        #if ($filter->isEmpty()) {
        #    $defaultFilter = $filter->createFilter('container_id', 'equals', array(
        #        'path' => '/personal/' . Tinebase_Core::getUser()->getId()
        #    ));
        #    
        #    $filter->addFilter($defaultFilter);
        #}
        
        if(in_array($_filterType, $this->_filterArray)) {
            switch($_filterType) {
                case Syncroton_Command_Sync::FILTER_2_WEEKS_BACK:
                    $from = Tinebase_DateTime::now()->subWeek(2);
                    break;
                case Syncroton_Command_Sync::FILTER_1_MONTH_BACK:
                    $from = Tinebase_DateTime::now()->subMonth(2);
                    break;
                case Syncroton_Command_Sync::FILTER_3_MONTHS_BACK:
                    $from = Tinebase_DateTime::now()->subMonth(3);
                    break;
                case Syncroton_Command_Sync::FILTER_6_MONTHS_BACK:
                    $from = Tinebase_DateTime::now()->subMonth(6);
                    break;
            }
            // next 10 years
            $to = Tinebase_DateTime::now()->addYear(10);
            
            // remove all 'old' period filters
            $filter->removeFilter('period');
            
            // add period filter
            $filter->addFilter(new Calendar_Model_PeriodFilter('period', 'within', array(
                'from'  => $from,
                'until' => $to
            )));
        }
        
        return $filter;
    }
}
