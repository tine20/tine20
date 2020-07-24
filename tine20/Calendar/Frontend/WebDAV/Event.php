<?php

use Sabre\VObject;

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle a single event
 *
 * This class handles the creation, update and deletion of vevents
 *
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_WebDAV_Event extends Sabre\DAV\File implements Sabre\CalDAV\ICalendarObject, Sabre\DAVACL\IACL
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
        
        if (! $this->_event instanceof Calendar_Model_Event) {
            $this->_event = ($pos = strpos($this->_event, '.')) === false ? $this->_event : substr($this->_event, 0, $pos);
        }
        
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
        } else {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC;
            $version = null;
        }
        
        Calendar_Controller_MSEventFacade::getInstance()->assertEventFacadeParams($this->_container);
    }
    
    /**
     * add attachment to event
     * 
     * @param string $name
     * @param string $contentType
     * @param stream $attachment
     * @return string  id of attachment
     */
    public function addAttachment($rid, $name, $contentType, $attachment)
    {
        $record = $this->getRecord();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . 
                " add attachment $name ($contentType) to event {$record->getId()}");
        }
        
        $node = new Tinebase_Model_Tree_Node(array(
            'name'         => $name,
            'type'         => Tinebase_Model_Tree_FileObject::TYPE_FILE,
            'contenttype'  => $contentType,
            'stream'       => $attachment,
        ), true);
        
        $record->attachments->addRecord($node);
        
        $this->_event = Calendar_Controller_MSEventFacade::getInstance()->update($record);
        $newAttachmentNode = $this->_event->attachments->filter('name', $name)->getFirstRecord();
        
        return $newAttachmentNode->object_id;
    }
    
    /**
     * this function creates a Calendar_Model_Event and stores it in the database
     * 
     * @todo the header handling does not belong here. It should be moved to the DAV_Server class when supported
     * 
     * @param  Tinebase_Model_Container  $container
     * @param  stream|string             $vobjectData
     * @return Calendar_Frontend_WebDAV_Event
     */
    public static function create(Tinebase_Model_Container $container, $name, $vobjectData, $onlyCurrentUserOrganizer = false)
    {
        if (is_resource($vobjectData)) {
            $vobjectData = stream_get_contents($vobjectData);
        }
        // Converting to UTF-8, if needed
        $vobjectData = Sabre\DAV\StringUtil::ensureUTF8($vobjectData);
        
        #Sabre\CalDAV\ICalendarUtil::validateICalendarObject($vobjectData, array('VEVENT', 'VFREEBUSY'));
        
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory($backend, $version);

        try {
            /** @var Calendar_Model_Event $event */
            $event = $converter->toTine20Model($vobjectData);
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e);
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " " . $vobjectData);
            throw new Sabre\DAV\Exception\PreconditionFailed($e->getMessage());
        }

        if (true === $onlyCurrentUserOrganizer) {
            if ($event->organizer && $event->organizer != Tinebase_Core::getUser()->contact_id) {
                return null;
            }
        }
        
        $event->container_id = $container->getId();
        $id = ($pos = strpos($name, '.')) === false ? $name : substr($name, 0, $pos);
        if (strlen($id) > 40) {
            $id = sha1($id);
        }
        $event->setId($id);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Event to create: " . print_r($event->toArray(), TRUE));
        
        Calendar_Controller_MSEventFacade::getInstance()->assertEventFacadeParams($container);
        
        // check if there is already an existing event with this ID
        // this can happen when the invitation email is faster then the caldav update or
        // or when an event gets moved to another container
        $existingEvent = Calendar_Controller_MSEventFacade::getInstance()->getExistingEventByUID($event->uid,
            $event->hasExternalOrganizer(), 'sync', null, true);
        
        if ($existingEvent === null) {
            self::checkWriteAccess($converter);
            $retry = false;
            try {
                $event = Calendar_Controller_MSEventFacade::getInstance()->create($event);
                
            } catch (Zend_Db_Statement_Exception $zdse) {
                $retry = true;
                if (! Tinebase_Exception::isDbDuplicate($zdse)) {
                    Tinebase_Exception::log($zdse, true);
                }
            } catch (Tinebase_Exception_AccessDenied $tead) {
                $retry = true;
                Tinebase_Exception::log($tead, true);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e);
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " " . $vobjectData);
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " " . print_r($event->toArray(), true));
                throw new Sabre\DAV\Exception\PreconditionFailed($e->getMessage());
            }

            if ($retry) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Might be a duplicate exception, try with new id');

                unset($event->id);
                try {
                    $event = Calendar_Controller_MSEventFacade::getInstance()->create($event);
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e);
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " " . $vobjectData);
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " " . print_r($event->toArray(), true));
                    throw new Sabre\DAV\Exception\PreconditionFailed($e->getMessage());
                }
            }
            
            $vevent = new self($container, $event);
        } else {

            if ($existingEvent->hasExternalOrganizer() && is_numeric($existingEvent->external_seq) &&
                    (int)$event->external_seq < (int)$existingEvent->external_seq) {
                throw new Sabre\DAV\Exception\PreconditionFailed('updating existing event with outdated external seq');
            }

            if ($existingEvent->is_deleted) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' recovering already deleted event');

                // @TODO have a undelete/recover workflow beginning in controller
                $existingEvent->is_deleted = 0;
                $existingEvent->deleted_by = NULL;
                $existingEvent->deleted_time = NULL;

                $be = new Calendar_Backend_Sql();
                $be->updateMultiple($existingEvent->getId(), array(
                    'is_deleted'    => 0,
                    'deleted_by'    => NULL,
                    'deleted_time'  => NULL,
                ));
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' update existing event');

            $vevent = new self($container, $existingEvent);
            /** @var Calendar_Model_Event $existingEvent */
            $existingEvent = clone $existingEvent;
            $existingEvent->alarms = $event->alarms;
            $existingEvent->transp = $event->transp;
            if (null === ($contactId = $container->getOwner())) {
                $contactId = Tinebase_Core::getUser()->contact_id;
            } else {
                $contactId = Tinebase_User::getInstance()->getUserById($contactId)->contact_id;
            }
            if (null !== ($attender = $event->attendee->find('user_id', $contactId))) {
                if (null !== ($oldAttender = $existingEvent->attendee->find('user_id', $contactId))) {
                    $existingEvent->attendee->removeRecord($oldAttender);
                    $attender->setId($oldAttender->getId());
                }
                $existingEvent->attendee->addRecord($attender);
            }

            $calCtrl = Calendar_Controller_Event::getInstance();
            $oldCalenderAcl = $calCtrl->doContainerACLChecks();
            try {
                if ($existingEvent->hasExternalOrganizer()) {
                    $calCtrl->doContainerACLChecks(false);
                }
                $vobject = Calendar_Convert_Event_VCalendar_Abstract::getVObject($vobjectData);
                $xTine20Container = null;
                foreach ($vobject->children() as $component) {
                    if (isset($component->{'X-TINE20-CONTAINER'})) {
                        try {
                            $xTine20Container = Tinebase_Container::getInstance()
                                ->get($component->{'X-TINE20-CONTAINER'});
                        } catch (Tinebase_Exception_NotFound $e) {}
                        break;
                    }
                }
                $vcalendar = $converter->fromTine20Model($existingEvent);
                if (null !== $xTine20Container) {
                    static::_addXPropsToVEvent($vcalendar, $xTine20Container);
                }
                $vevent->put($vcalendar->serialize());
            } finally {
                $calCtrl->doContainerACLChecks($oldCalenderAcl);
            }
        }
        
        return $vevent;
    }

    /**
     * @param Calendar_Convert_Event_VCalendar_Abstract $converter
     * @throws \Sabre\DAV\Exception\Forbidden
     */
    public static function checkWriteAccess($converter)
    {
        $converterClass = get_class($converter);
        if (in_array($converterClass, [
            'Calendar_Convert_Event_VCalendar_Generic',
            'Calendar_Convert_Event_VCalendar_KDE',
        ])) {
            $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Update by '
                 . $converterClass . ' client not allowed. See Calendar_Convert_Event_VCalendar_Factory for supported clients.'
                 . ' User-Agent: ' . $useragent);
            throw new Sabre\DAV\Exception\Forbidden('write access denied for unknown client');
        }
    }
    
    /**
     * Deletes the card
     *
     * @todo improve handling
     * @return void
     * @throws Sabre\DAV\Exception\NotFound
     */
    public function delete() 
    {
        self::checkWriteAccess($this->_getConverter());

        // when a move occurs, thunderbird first sends to delete command and immediately a put command
        // we must delay the delete command, otherwise the put command fails
        sleep(5);
        
        // (re) fetch event as tree move does not refresh src node before delete
        Calendar_Controller_MSEventFacade::getInstance()->assertEventFacadeParams($this->_container);
        try {
            $event = Calendar_Controller_MSEventFacade::getInstance()->get($this->_event);
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre\DAV\Exception\NotFound("Event not found");
        }
        
        // disallow event cleanup in the past
        if (max($event->dtend, $event->rrule_until) < Tinebase_DateTime::now()->subMonth(2)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " deleting events in the past is not allowed via CalDAV");
            return;
        }

        // allow delete only if deleted in origin calendar
        if ($event->container_id == $this->_container->getId()) {
            if (strpos($_SERVER['REQUEST_URI'], Calendar_Frontend_CalDAV_ScheduleInbox::NAME) === false) {
                Calendar_Controller_MSEventFacade::getInstance()->delete($event->getId());
            }
        }
        // implicitly DECLINE event 
        else {
            $attendee = $event->attendee instanceof Tinebase_Record_RecordSet ? 
                $event->attendee->filter('displaycontainer_id', $this->_container->getId())->getFirstRecord() :
                NULL;
            
            // NOTE: don't allow organizer to instantly delete after update, otherwise we can't handle move @see{Calendar_Frontend_WebDAV_EventTest::testMoveOriginPersonalToShared}
            if ($attendee && ($attendee->user_id != $event->organizer || Tinebase_DateTime::now()->subSecond(20) > $event->last_modified_time)) {
                $attendee->status = Calendar_Model_Attender::STATUS_DECLINED;
                
                $this->_event = Calendar_Controller_MSEventFacade::getInstance()->update($event);
            } 
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
     * How to calculate the etag?
     * The etag consists of 2 parts. The part, which is equal for all users (subject, dtstart, dtend, ...) and
     * the part which is different for all users(X-MOZ-LASTACK for example).
     * Because of the this we have to generate the etag as the hash of the record id, the lastmodified time stamp and the
     * hash of the json encoded attendee object.
     * This way the etag changes when the general properties or the user specific properties change.
     * 
     * @return string
     * 
     * @todo add a unittest for this function to verify desired behavior
     */
    public function getETag() 
    {
        // NOTE: We don't distinguish between scheduling and attendee sequences.
        //       Every action increases the record sequence atm.
        //       If we once should implement different sequences we also need 
        //       to consider sequences for non-attendee for X-MOZ-LASTACK
        $record = $this->getRecord();
        return '"' . sha1($record->getId() . $record->seq) . '"';
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
     * @param bool $retry
     * @return void
     */
    public function put($cardData, $retry = true)
    {
        Calendar_Controller_MSEventFacade::getInstance()->assertEventFacadeParams($this->_container);
        self::checkWriteAccess($this->_getConverter());

        $this->_vevent = null;
        if (is_resource($cardData)) {
            $cardData = stream_get_contents($cardData);
        }
        // Converting to UTF-8, if needed
        $cardData = Sabre\DAV\StringUtil::ensureUTF8($cardData);
        
        #Sabre_CalDAV_ICalendarUtil::validateICalendarObject($cardData, array('VEVENT', 'VFREEBUSY'));
        
        $vobject = Calendar_Convert_Event_VCalendar_Abstract::getVObject($cardData);
        foreach ($vobject->children() as $component) {
            if (isset($component->{'X-TINE20-CONTAINER'})) {
                $xTine20Container = $component->{'X-TINE20-CONTAINER'};
                break;
            }
        }
        
        // concurrency management is based on etag in CalDAV
        $event = $this->_getConverter()->toTine20Model($vobject, $this->getRecord(), array(
            Calendar_Convert_Event_VCalendar_Abstract::OPTION_USE_SERVER_MODLOG => true,
        ));

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . print_r($event->toArray(), true));


        $currentEvent = $this->getRecord();
        $currentContainer = Tinebase_Container::getInstance()->getContainerById($currentEvent->container_id);

        // client sends CalDAV event -> handle a container move
        if (isset($xTine20Container)) {
            if ($xTine20Container->getValue() == $currentContainer->getId()) {
                $event->container_id = $this->_container->getId();
            } else {
                // @TODO allow organizer to move original cal when he edits the displaycal event?
                if ($this->_container->type == Tinebase_Model_Container::TYPE_PERSONAL) {
                    Calendar_Controller_MSEventFacade::getInstance()->setDisplaycontainer($event, $this->_container->getId());
                }
            }
        }

        // event was created by current user -> allow container move
        else if ($currentEvent->created_by == Tinebase_Core::getUser()->getId()) {
            $event->container_id = $this->_container->getId();
        }

        // client sends event from iMIP invitation -> only allow displaycontainer move
        else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " X-TINE20-CONTAINER not present -> restrict container moves");
            if ($this->_container->type == Tinebase_Model_Container::TYPE_PERSONAL) {
                Calendar_Controller_MSEventFacade::getInstance()->setDisplaycontainer($event, $this->_container->getId());
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . print_r($event->toArray(), true));

        try {
            $this->update($event, $cardData);

            // in case we have a deadlock, retry operation once
        } catch (Zend_Db_Statement_Exception $zdbse) {
            if ($retry && strpos($zdbse->getMessage(), 'Deadlock') !== false) {
                Tinebase_TransactionManager::getInstance()->rollBack();
                return $this->put($cardData, false);
            } else {
                throw $zdbse;
            }
        }
        
        return $this->getETag();
    }
    
    /**
     * update this node with given event
     * 
     * @param Calendar_Model_Event $event
     */
    public function update(Calendar_Model_Event $event, $cardData='')
    {
        try {
            $this->_event = Calendar_Controller_MSEventFacade::getInstance()->update($event);
        } catch (Tinebase_Exception_ConcurrencyConflict $ttecc) {
            throw new Sabre\DAV\Exception\PreconditionFailed('An If-Match header was specified, but none of the specified the ETags matched.','If-Match');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            throw new Sabre\DAV\Exception\Forbidden('forbidden update');
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre\DAV\Exception\PreconditionFailed('event not found');
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " " . $e);
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " " . $cardData);
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " " . print_r($event->toArray(), true));
            throw new Sabre\DAV\Exception\PreconditionFailed($e->getMessage());
        }
    }
    
    /**
     * reset alarms to previous values
     * 
     * we don't reset the alarms in the vcalendar parser already, because this it is a limitation
     * of our current calendar implementation to not allow user specific alarms
     * 
     * @param Calendar_Model_Event $event
     * @param Calendar_Model_Event $recordBeforeUpdate
     */
    protected function _resetAlarms(Calendar_Model_Event $event, Calendar_Model_Event $recordBeforeUpdate)
    {
        $event->alarms = $recordBeforeUpdate->alarms;
    
        if ($event->exdate instanceof Tinebase_Record_RecordSet) {
            foreach ($event->exdate as $exdate) {
                $recurId = $event->id . '-' . (string) $exdate->recurid;
                
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
     * return Calendar_Model_Event and convert contact id to model if needed
     * 
     * @return Calendar_Model_Event
     */
    public function getRecord()
    {
        if (! $this->_event instanceof Calendar_Model_Event) {
            Calendar_Controller_MSEventFacade::getInstance()->assertEventFacadeParams($this->_container);
            $this->_event = Calendar_Controller_MSEventFacade::getInstance()->get($this->_event);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . print_r($this->_event->toArray(), true));
        }

        return $this->_event;
    }
    
    /**
     * returns container of this event
     *
     * @return Tinebase_Model_Container
     */
    public function getContainer()
    {
        return $this->_container;
    }
    
    /**
     * create instance of Calendar_Convert_Event_VCalendar_*
     * 
     * @return Calendar_Convert_Event_VCalendar_Abstract
     */
    public function _getConverter()
    {
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
        
        if (!$this->_converter) {
            $this->_converter = Calendar_Convert_Event_VCalendar_Factory::factory($backend, $version);
        }
        
        return $this->_converter;
    }
    
    /**
     * return vcard and convert Calendar_Model_Event to vcard if needed
     * 
     * @return string
     */
    protected function _getVEvent()
    {
        if ($this->_vevent == null) {
            $this->_vevent = $this->_getConverter()->fromTine20Model($this->getRecord());

            static::_addXPropsToVEvent($this->_vevent, $this->_container);
        }
        
        return $this->_vevent->serialize();
    }

    protected static function _addXPropsToVEvent($_vevent, $_container)
    {
        foreach ($_vevent->children() as $component) {
            if ($component->name == 'VEVENT') {
                // NOTE: we store the requested container here to have an origin when the event is moved
                $component->add('X-TINE20-CONTAINER', $_container->getId());

                if (isset($component->{'VALARM'}) && !$_container->isPersonalOf(Tinebase_Core::getUser())) {
                    // prevent duplicate alarms
                    $component->add('X-MOZ-LASTACK', Tinebase_DateTime::now()->addYear(100)->setTimezone('UTC'), array('VALUE' => 'DATE-TIME'));
                }
            }
        }
    }
    
    /**
     * 
     */
    public function getSupportedPrivilegeSet()
    {
        return null;
    }
}
