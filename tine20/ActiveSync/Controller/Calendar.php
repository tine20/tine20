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
 *   n tine evnets become one AS evnet as AS events house all exceptions
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
     * trivial mapping
     *
     * @var array
     */
    protected $_mapping = array(
        //'Timezone'          => 'timezone',
        'AllDayEvent'       => 'is_all_day_evnet',
        //'BusyStatus'        => 'transp',
        //'OrganizerName'     => 'organizer',
        //'OrganizerEmail'    => 'organizer',
        'DtStamp'           => 'last_modified_time',
        'EndTime'           => 'dtend',
        'Location'          => 'location',
        //'Reminder'          => 'alarms',
        //'Sensitivity'       => 'class_id',
        'Subject'           => 'summary',
        'Body'              => 'description',
        'StartTime'         => 'dtstart',
        'UID'               => 'uid',
        //'MeetingStatus'     => 'status_id',
        //'Attendees'         => 'attendee',
        //'Categories'        => 'tags',
        //'Recurrence'        => 'rrule',
        //'Exceptions'        => 'exdate',
    );
    
    
    /**
     * list of supported folders
     * @todo retrieve users real container
     * @var array
     */
    protected $_folders = array(array(
        'folderId'      => 'eventsroot',
        'parentId'      => 0,
        'displayName'   => 'Events',
        'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_CALENDAR
    ));
    
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
     * append contact to xml parent node
     *
     * @param DOMDocument $_xmlDocument
     * @param DOMElement $_xmlNode
     * @param string $_serverId
     */
    public function appendXML(DOMDocument $_xmlDocument, DOMElement $_xmlNode, $_folderId, $_serverId)
    {
        $data = $this->_contentController->get($_serverId);
        
        foreach($this->_mapping as $key => $value) {
            if(!empty($data->$value)) {
                switch($value) {
                    case 'bday':
                        // Zend_Date does not have direct milisecond support, and for birthdays we realy don't need it!
                        $bday = $data->bday->toString('yyyy-MM-ddTHH:mm:ss') . '.000Z';
                        $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Contacts', $key, $bday));
                        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Birthday " . $bday);
                        break;
                    default:
                        $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Contacts', $key, $data->$value));
                        break;
                }
            }
        }        
    }
        
    /**
     * convert contact from xml to Calendar_Model_Event
     *
     * @todo handle images
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
        
        $xmlData = $_data->children('uri:Calendar');

        foreach($this->_mapping as $fieldName => $value) {
            switch($value) {
                case 'bday':
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
        
        // contact should be valid now
        $event->isValid();
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " eventData " . print_r($event->toArray(), true));
        
        return $event;
    }
    
    /**
     * convert contact from xml to Calendar_Model_EventFilter
     *
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_ContactFilter
     */
    protected function _toTineFilter(SimpleXMLElement $_data)
    {
        $xmlData = $_data->children('uri:Calendar');
        
        $filter = new Calendar_Model_EventFilter(array(
            array(
                'field'     => 'containerType',
                'operator'  => 'equals',
                'value'     => 'all'
            )
        )); 
    
        foreach($this->_mapping as $fieldName => $value) {
            if($filter->has($value)) {
                $filter->$value = array(
                    'operator'  => 'equals',
                    'value'     => (string)$xmlData->$fieldName
                );
            }
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filter, true));
        
        return $filter;
    }
    
}