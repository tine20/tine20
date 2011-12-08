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
 * class to handle a single event
 *
 * This class handles the creation, update and deletion of vevents
 *
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_WebDAV_Event extends Sabre_DAV_File implements Sabre_CalDAV_ICalendarObject, Sabre_DAVACL_IACL
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_container;
    
    /**
     * @var Calendar_Model_Event
     */
    protected $_event;
    
    /**
     * holds the vevent returned to the client
     * 
     * @var string
     */
    protected $_vevent;
    
    /**
     * @var Calendar_Convert_Event_VCalendar
     */
    protected $_converter;
    
    /**
     * Constructor 
     * 
     * @param  string|Calendar_Model_Event  $_event  the id of a event or the event itself 
     */
    public function __construct(Tinebase_Model_Container $_container, $_event = null) 
    {
        $this->_container = $_container;
        $this->_event     = $_event;
        
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
        
        $this->_converter = Calendar_Convert_Event_VCalendar_Factory::factory($backend, $version);
    }
    
    /**
     * this function creates a Calendar_Model_Event and stores it in the database
     * 
     * @todo the header handling does not belong here. It should be moved to the DAV_Server class when supported
     * 
     * @param  Tinebase_Model_Container  $container
     * @param  stream|string             $vobjectData
     */
    public static function create(Tinebase_Model_Container $container, $name, $vobjectData)
    {
        if (is_resource($vobjectData)) {
            $vobjectData = stream_get_contents($vobjectData);
        }
        // Converting to UTF-8, if needed
        $vobjectData = Sabre_DAV_StringUtil::ensureUTF8($vobjectData);
        
        Sabre_CalDAV_ICalendarUtil::validateICalendarObject($vobjectData, array('VEVENT', 'VFREEBUSY'));
        
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
        
        $event = Calendar_Convert_Event_VCalendar_Factory::factory($backend, $version)->toTine20Model($vobjectData);
        $event->container_id = $container->getId();
        $id = ($pos = strpos($name, '.')) === false ? $name : substr($name, 0, $pos);
        $event->setId($id);
        
        self::enforceEventParameters($event);
        
        if ($event->exdate instanceof Tinebase_Record_RecordSet) {
            foreach($event->exdate as $exdate) {
                if ($exdate->is_deleted == false && $exdate->organizer != $event->organizer) {
                    throw new Sabre_DAV_Exception_PreconditionFailed('Organizer for exdate must be the same like base event');
                }
            }
        }
        
        // check if there is already an existing event with this ID
        // this can happen when the invitation email is faster then the caldav update or
        // or when an event gets moved to another container
        
        $filter = new Calendar_Model_EventFilter(array(
            array(
            	'field' => 'containerType', 
            	'operator' => 'equals', 
            	'value' => 'all'
        	),
            array(
            	'field' => 'dtstart', 
            	'operator' => 'equals', 
            	'value' => $event->dtstart
        	),
            array(
            	'field' => 'dtend', 
            	'operator' => 'equals', 
            	'value' => $event->dtend
        	),
            array('condition' => 'OR', 'filters' => array(
                array(
                    'field'     => 'id',
                    'operator'  => 'equals',
                    'value'     => $id
                ),
                array(
                    'field'     => 'uid',
                    'operator'  => 'equals',
                    'value'     => $id
                )
            ))
        ));
        $existingEvent = Calendar_Controller_MSEventFacade::getInstance()->search($filter, null, false, false, 'sync')->getFirstRecord();
        
        if ($existingEvent === null) {
            if ($event->organizer != Tinebase_Core::getUser()->contact_id) {
                throw new Sabre_DAV_Exception_PreconditionFailed('invalid organizer provided');
            }
            
            $event = Calendar_Controller_MSEventFacade::getInstance()->create($event);
            
            $vevent = new self($container, $event);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' update existing event');
            $vevent = new self($container, $existingEvent);
            $vevent->put($vobjectData);
        }
        
        return $vevent;
    }
    
    public static function enforceEventParameters(Calendar_Model_Event $_event)
    {
        // got there any attendees added?
        if(! $_event->attendee instanceof Tinebase_Record_RecordSet) {
            $_event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        }

        // can happen only during create not on update
        if (empty($_event->organizer)) {
            $_event->organizer = Tinebase_Core::getUser()->contact_id;
        }
        
        // the organizer must always be an attendee
        if ($_event->organizer === Tinebase_Core::getUser()->contact_id && ($ownAttendee = Calendar_Model_Attender::getOwnAttender($_event->attendee)) == null) {
            $_event->attendee->addRecord(new Calendar_Model_Attender(array(
            	'user_id'   => Tinebase_Core::getUser()->contact_id,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                'status'    => Calendar_Model_Attender::STATUS_ACCEPTED
            )));
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " added organizer as attendee ");            
        }
        
        if (empty($_event->transp)) {
            $_event->transp = Calendar_Model_Event::TRANSP_OPAQUE;
        }
        
        // check also attached exdates
        if ($_event->exdate instanceof Tinebase_Record_RecordSet) {
            foreach($_event->exdate as $exdate) {
                if ($exdate->is_deleted == false) {
                    $exdate->container_id = $_event->container_id;
                    self::enforceEventParameters($exdate);
                }
            }
        }
    }
    
    /**
     * Deletes the card
     *
     * @todo improve handling
     * @return void
     */
    public function delete() 
    {
        $id = $this->_event instanceof Calendar_Model_Event ? $this->_event->getId() : $this->_event;
        
        if (strpos($_SERVER['REQUEST_URI'], Calendar_Frontend_CalDAV_ScheduleInbox::NAME) === false) {
            Calendar_Controller_MSEventFacade::getInstance()->delete($id);
        }
    }
    
    /**
     * Returns the VCard-formatted object 
     * 
     * @return stream
     */
    public function get() 
    {
        $s = fopen('php://temp','r+');
        fwrite($s, $this->_getVEvent());
        rewind($s);
        
        return $s;
    }
    
    /**
     * Returns the uri for this object 
     * 
     * @return string 
     */
    public function getName() 
    {
        return $this->getRecord()->getId() . '.ics';
    }
    
    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner 
     * 
     * @todo add real owner
     * @return string|null
     */
    public function getOwner() 
    {
        return null;
        return $this->addressBookInfo['principaluri'];
    }

    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     * 
     * @todo add real group
     * @return string|null 
     */
    public function getGroup() 
    {
        return null;
    }
    
    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are 
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to 
     *      be updated. 
     * 
     * @todo add the real logic
     * @return array 
     */
    public function getACL() 
    {
        return null;
        
        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->addressBookInfo['principaluri'],
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $this->addressBookInfo['principaluri'],
                'protected' => true,
            ),
        );

    }
    
    /**
     * Returns the mime content-type
     *
     * @return string
     */
    public function getContentType() {
    
        return 'text/calendar';
    
    }
    
    /**
     * Returns an ETag for this object
     *
     * @return string
     */
    public function getETag() 
    {
        return '"' . md5($this->getRecord()->getId() . $this->getLastModified()) . '"'; 
    }
    
    /**
     * Returns the last modification date as a unix timestamp
     *
     * @return time
     */
    public function getLastModified() 
    {
        return ($this->getRecord()->last_modified_time instanceof Tinebase_DateTime) ? $this->getRecord()->last_modified_time->toString() : $this->getRecord()->creation_time->toString();
    }
    
    /**
     * Returns the size of the vcard in bytes
     *
     * @return int
     */
    public function getSize() 
    {
        return strlen($this->_getVEvent());
    }
    
    /**
     * Updates the VCard-formatted object
     *
     * @param string $cardData
     * @return void
     */
    public function put($cardData) 
    {
        if (get_class($this->_converter) == 'Calendar_Convert_Event_VCalendar_Generic') {
            throw new Sabre_DAV_Exception_Forbidden('Update denied for unknow client');
        }
        
        if (is_resource($cardData)) {
            $cardData = stream_get_contents($cardData);
        }
        // Converting to UTF-8, if needed
        $cardData = Sabre_DAV_StringUtil::ensureUTF8($cardData);
        
        Sabre_CalDAV_ICalendarUtil::validateICalendarObject($cardData, array('VEVENT', 'VFREEBUSY'));
        
        $event = $this->_converter->toTine20Model($cardData, $this->getRecord());
        $event->container_id = $this->_container->getId();
        
        self::enforceEventParameters($event);
        
        if ($this->getRecord()->organizer == Tinebase_Core::getUser()->contact_id) {
            $this->_event = Calendar_Controller_MSEventFacade::getInstance()->update($event);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " current user is not organizer => update attendee status only ");
            
            if (($ownAttendee = Calendar_Model_Attender::getOwnAttender($this->getRecord()->attendee)) !== null) {
                $ownAttendee->displaycontainer_id = $this->_container->getId();
                
                try {
                    $this->_event = Calendar_Controller_MSEventFacade::getInstance()->attenderStatusUpdate($event, $ownAttendee);
                } catch (Tinebase_Exception_AccessDenied $tead) {
                    throw new Sabre_DAV_Exception_Forbidden($tead->getMessage());
                }
            } else {
                throw new Sabre_DAV_Exception_Forbidden('not attendee of this event');
            }
            
        }
        
        // avoid sending headers during unit tests
        if (php_sapi_name() != 'cli') {
            // @todo this belong to DAV_Server, but it currently not supported
            header('ETag: ' . $this->getETag());
        }
    }
    
    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's. 
     * 
     * @param array $acl 
     * @return void
     */
    public function setACL(array $acl) 
    {
        throw new Sabre_DAV_Exception_MethodNotAllowed('Changing ACL is not yet supported');
    }
    
    /**
     * return Calendar_Model_Event and convert contact id to model if needed
     * 
     * @return Calendar_Model_Event
     */
    public function getRecord()
    {
        if (! $this->_event instanceof Calendar_Model_Event) {
            $id = ($pos = strpos($this->_event, '.')) === false ? $this->_event : substr($this->_event, 0, $pos);
            
            $this->_event = Calendar_Controller_MSEventFacade::getInstance()->get($id);
            
        }

        // resolve alarms
        if (empty($this->_event->alarms)) {
            Calendar_Controller_MSEventFacade::getInstance()->getAlarms($this->_event);
        }
        
        return $this->_event;
    }
    
    /**
     * return vcard and convert Calendar_Model_Event to vcard if needed
     * 
     * @return string
     */
    protected function _getVEvent()
    {
        if ($this->_vevent == null) {
            $this->_vevent = $this->_converter->fromTine20Model($this->getRecord());
        }
                
        return $this->_vevent->serialize();
    }
}
