<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle a single task
 *
 * This class handles the creation, update and deletion of vevents
 *
 * @package     Tasks
 * @subpackage  Frontend
 */
class Tasks_Frontend_WebDAV_Task extends Sabre\DAV\File implements Sabre\CalDAV\ICalendarObject, Sabre\DAVACL\IACL
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_container;
    
    /**
     * @var Tasks_Model_Event
     */
    protected $_task;
    
    /**
     * holds the vevent returned to the client
     * 
     * @var string
     */
    protected $_vevent;
    
    /**
     * @var Tasks_Convert_Task_VCalendar
     */
    protected $_converter;
    
    /**
     * Constructor 
     * 
     * @param  string|Tasks_Model_Event  $_task  the id of a event or the event itself 
     */
    public function __construct(Tinebase_Model_Container $_container, $_task = null) 
    {
        $this->_container = $_container;
        $this->_task      = $_task;
        
        if (! $this->_task instanceof Tasks_Model_Task) {
            $this->_task = ($pos = strpos($this->_task, '.')) === false ? $this->_task : substr($this->_task, 0, $pos);
        } else {
            // resolve alarms
            Tasks_Controller_Task::getInstance()->getAlarms($this->_task);
        }
        
        list($backend, $version) = Tasks_Convert_Task_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
        
        $this->_converter = Tasks_Convert_Task_VCalendar_Factory::factory($backend, $version);
    }
    
    /**
     * this function creates a Tasks_Model_Task and stores it in the database
     * 
     * @param  Tinebase_Model_Container  $container
     * @param  stream|string             $vobjectData
     */
    public static function create(Tinebase_Model_Container $container, $name, $vobjectData, $onlyCurrentUserOrganizer = 'unused')
    {
        if (is_resource($vobjectData)) {
            $vobjectData = stream_get_contents($vobjectData);
        }
        // Converting to UTF-8, if needed
        $vobjectData = Sabre\DAV\StringUtil::ensureUTF8($vobjectData);
        
        #Sabre_CalDAV_ICalendarUtil::validateICalendarObject($vobjectData, array('VTODO', 'VFREEBUSY'));
        
        list($backend, $version) = Tasks_Convert_Task_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
        
        $task = Tasks_Convert_Task_VCalendar_Factory::factory($backend, $version)->toTine20Model($vobjectData);
        $task->container_id = $container->getId();
        $id = ($pos = strpos($name, '.')) === false ? $name : substr($name, 0, $pos);
        $task->setId($id);
        
        self::enforceEventParameters($task);
        
        #if ($task->exdate instanceof Tinebase_Record_RecordSet) {
        #    foreach($task->exdate as $exdate) {
        #        if ($exdate->is_deleted == false && $exdate->organizer != $task->organizer) {
        #            throw new Sabre\DAV\Exception\PreconditionFailed('Organizer for exdate must be the same like base task');
        #        }
        #    }
        #}
        
        // check if there is already an existing task with this ID
        // this can happen when the invitation email is faster then the caldav update or
        // or when an task gets moved to another container
        
        $filter = new Tasks_Model_TaskFilter(array(
            #array(
            #    'field' => 'containerType', 
            #    'operator' => 'equals', 
            #    'value' => 'all'
            #),
            #array(
            #    'field' => 'completed', 
            #    'operator' => 'equals', 
            #    'value' => $task->completed
            #),
            array(
                'field' => 'due', 
                'operator' => 'equals', 
                'value' => $task->due
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
        $existingEvent = Tasks_Controller_Task::getInstance()->search($filter, null, false, false, 'sync')->getFirstRecord();
        
        if ($existingEvent === null) {
            $task = Tasks_Controller_Task::getInstance()->create($task);
            
            $vevent = new self($container, $task);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' update existing task with id: ' . $existingEvent->getId());
            $vevent = new self($container, $existingEvent);
            $vevent->put($vobjectData);
        }
        
        return $vevent;
    }
    
    public static function enforceEventParameters(Tasks_Model_Task $task)
    {
        // can happen only during create not on update
        if (empty($task->organizer)) {
            $task->organizer = Tinebase_Core::getUser()->getId();
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
        // when a move occurs, thunderbird first sends to delete command and immediately a put command
        // we must delay the delete command, otherwise the put command fails
        sleep(1);
        
        // (re) fetch task as tree move does not refresh src node before delete
        $task = Tasks_Controller_Task::getInstance()->get($this->_task);
        
        // allow delete only if deleted in origin calendar
        if ($task->container_id == $this->_container->getId()) {
            //if (strpos($_SERVER['REQUEST_URI'], Calendar_Frontend_CalDAV_ScheduleInbox::NAME) === false) {
                Tasks_Controller_Task::getInstance()->delete($task->getId());
            //}
        }
        
        // implicitly DECLINE event 
        //else {
            //$attendee = $task->attendee instanceof Tinebase_Record_RecordSet ? 
            //    $task->attendee->filter('displaycontainer_id', $this->_container->getId())->getFirstRecord() :
            //    NULL;
            
            // NOTE: don't allow organizer to instantly delete after update, otherwise we can't handle move @see{Calendar_Frontend_WebDAV_EventTest::testMoveOriginPersonalToShared}
        //    if ($attendee && $attendee->user_id != $task->organizer || Tinebase_DateTime::now()->subSecond(10) > $task->last_modified_time) {
        //        $attendee->status = Calendar_Model_Attender::STATUS_DECLINED;
       //         
       //         self::enforceEventParameters($task);
       //         $this->_task = Calendar_Controller_MSEventFacade::getInstance()->update($task);
         //   } 
       // }
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
     * How to calculate the etag?
     * The etag consists of 2 parts. The part, which is equal for all users (subject, dtstart, dtend, ...) and
     * the part which is different for all users(X-MOZ-LASTACK for example).
     * Because of the this we have to generate the etag as the hash of the record id, the lastmodified time stamp and the
     * hash of the json encoded attendee object.
     * This way the etag changes when the general properties or the user specific properties change.
     * 
     * @return string
     */
    public function getETag() 
    {
        $attendeeHash = null;
        
        //if ( ($ownAttendee = Calendar_Model_Attender::getOwnAttender($this->getRecord()->attendee)) instanceof Calendar_Model_Attender) {
        //    $attendeeHash = sha1(Zend_Json::encode($ownAttendee->toArray()));
        //}
        
        //if ($this->getRecord()->exdate instanceof Tinebase_Record_RecordSet) {
        //    foreach ($this->getRecord()->exdate as $exdate) {
        //        if ( ($ownAttendee = Calendar_Model_Attender::getOwnAttender($exdate->attendee)) instanceof Calendar_Model_Attender) {
        //            $attendeeHash = sha1($attendeeHash . Zend_Json::encode($ownAttendee->toArray()));
        //        }
        //    }
        // }
        
        return '"' . sha1($this->getRecord()->getId() . $this->getLastModified()) . $attendeeHash . '"'; 
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
        if (get_class($this->_converter) == 'Tasks_Convert_Task_VCalendar_Generic') {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) 
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " update by generic client not allowed. See Tasks_Convert_Task_VCalendar_Factory for supported clients.");
            throw new Sabre\DAV\Exception\Forbidden('Update denied for unknow client');
        }
        
        if (is_resource($cardData)) {
            $cardData = stream_get_contents($cardData);
        }
        // Converting to UTF-8, if needed
        $cardData = Sabre\DAV\StringUtil::ensureUTF8($cardData);
        
        #Sabre_CalDAV_ICalendarUtil::validateICalendarObject($cardData, array('VTODO', 'VFREEBUSY'));
        
        $vobject = Tasks_Convert_Task_VCalendar_Abstract::getVObject($cardData);
        foreach ($vobject->children() as $component) {
            if (isset($component->{'X-TINE20-CONTAINER'})) {
                $xContainerId = $component->{'X-TINE20-CONTAINER'};
                break;
            }
        }
        
        // keep old record for reference
        $recordBeforeUpdate = clone $this->getRecord();
        
        $task = $this->_converter->toTine20Model($vobject, $this->getRecord(), array(
            Tasks_Convert_Task_VCalendar_Abstract::OPTION_USE_SERVER_MODLOG => true,
        ));
        
        // iCal does sends back an old value, because it does not refresh the vcalendar after 
        // update. Therefor we must reapply the value of last_modified_time after the convert
        $task->last_modified_time = $recordBeforeUpdate->last_modified_time;
        
        $currentContainer = Tinebase_Container::getInstance()->getContainerById($this->getRecord()->container_id);
        //$ownAttendee = Calendar_Model_Attender::getOwnAttender($this->getRecord()->attendee);
        
        // task 'belongs' current user -> allow container move
        if ($currentContainer->isPersonalOf(Tinebase_Core::getUser())) {
            $task->container_id = $this->_container->getId();
        }
        
        // client sends CalDAV task -> handle a container move
        /*else if (isset($xContainerId)) {
            if ($xContainerId == $currentContainer->getId()) {
                $task->container_id = $this->_container->getId();
            } else {
                // @TODO allow organizer to move original cal when he edits the displaycal task?
                if ($ownAttendee && $this->_container->type == Tinebase_Model_Container::TYPE_PERSONAL) {
                    $ownAttendee->displaycontainer_id = $this->_container->getId();
                }
            }
        }
        */
        // client sends task from iMIP invitation -> only allow displaycontainer move
        /*else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " X-TINE20-CONTAINER not present -> restrict container moves");
            if ($ownAttendee && $this->_container->type == Tinebase_Model_Container::TYPE_PERSONAL) {
                if ($ownAttendee->displaycontainer_id == $currentContainer->getId()) {
                    $task->container_id = $this->_container->getId();
                }
                
                $ownAttendee->displaycontainer_id = $this->_container->getId();
            }
        }*/
        
        self::enforceEventParameters($task);
        
        // don't allow update of alarms for non organizer if oganizer is Tine 2.0 user
        if ($task->organizer !== Tinebase_Core::getUser()->getId()) {
            $organizerContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($task->organizer,TRUE);
            
            // reset alarms if organizer is Tine 2.0 user
            if (!empty($organizerContact->account_id)) {
                $this->_resetAlarms($task, $recordBeforeUpdate);
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . " " . print_r($task->toArray(), true));
        
        try {
            $this->_task = Tasks_Controller_Task::getInstance()->update($task);
        } catch (Tinebase_Timemachine_Exception_ConcurrencyConflict $ttecc) {
            throw new Sabre\DAV\Exception\PreconditionFailed('An If-Match header was specified, but none of the specified the ETags matched.','If-Match');
        }
        
        return $this->getETag();
    }
    
    /**
     * reset alarms to previous values
     * 
     * we don't reset the alarms in the vcalendar parser already, because this it is a limitation
     * of our current calendar implementation to not allow user specific alarms
     * 
     * @param Calendar_Model_Event $task
     * @param Calendar_Model_Event $recordBeforeUpdate
     */
    protected function _resetAlarms(Tasks_Model_Task $task, Tasks_Model_Task $recordBeforeUpdate)
    {
        $task->alarms = $recordBeforeUpdate->alarms;
    
        if ($task->exdate instanceof Tinebase_Record_RecordSet) {
            foreach ($task->exdate as $exdate) {
                $recurId = $task->id . '-' . (string) $exdate->recurid;
                
                if ($recordBeforeUpdate->exdate instanceof Tinebase_Record_RecordSet && ($matchingRecord = $recordBeforeUpdate->exdate->find('recurid', $recurId)) !== null) {
                    $exdate->alarms = $matchingRecord->alarms;
                } else {
                    $exdate->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
                }
            }
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
        throw new Sabre\DAV\Exception\MethodNotAllowed('Changing ACL is not yet supported');
    }
    
    /**
     * return Tasks_Model_Task and convert contact id to model if needed
     * 
     * @return Tasks_Model_Task
     */
    public function getRecord()
    {
        if (! $this->_task instanceof Tasks_Model_Task) {
            $this->_task = Tasks_Controller_Task::getInstance()->get($this->_task);
            
            // resolve alarms
            //Tasks_Controller_Task::getInstance()->getAlarms($this->_task);
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . print_r($this->_task->toArray(), true));
        }

        return $this->_task;
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
            
            foreach ($this->_vevent->children() as $component) {
                if ($component->name == 'VTODO') {
                    // NOTE: we store the requested container here to have an origin when the event is moved
                    $component->{'X-TINE20-CONTAINER'} = $this->_container->getId();
                    
                    if (isset($component->{'VALARM'}) && !$this->_container->isPersonalOf(Tinebase_Core::getUser())) {
                        // prevent duplicate alarms
                        $component->add('X-MOZ-LASTACK', Tinebase_DateTime::now()->addYear(100)->setTimezone('UTC'), array('VALUE' => 'DATE-TIME'));
                    }
                }
            }
        }
        
        return $this->_vevent->serialize();
    }
    
    /**
     * 
     */
    public function getSupportedPrivilegeSet()
    {
        return null;
    }
}
