<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * iMIP (RFC 6047) frontend for calendar
 * 
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_iMIP
{
    /**
     * auto process given iMIP component 
     * 
     * @TODO autodelete REFRESH mails
     * 
     * @param  Calendar_Model_iMIP $_iMIP
     * @param  boolean               $_retry    retry in case a deadlock occured
     * @return mixed
     */
    public function autoProcess(Calendar_Model_iMIP $_iMIP, $_retry = true)
    {
        if ($_iMIP->method == Calendar_Model_iMIP::METHOD_COUNTER) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(
                __METHOD__ . '::' . __LINE__ . " skip auto processing of iMIP component with COUNTER method "
                . "-> must always be processed manually");
            return false;
        }

        try {
            if (! $this->getExistingEvent($_iMIP, TRUE) && $_iMIP->method != Calendar_Model_iMIP::METHOD_CANCEL) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' .
                    __LINE__ . " skip auto processing of iMIP component whose event is not in our db yet");
                return false;
            }

            // update existing event details _WITHOUT_ status updates
            return $this->_process($_iMIP);
        } catch (Zend_Db_Statement_Exception $zdbse) {
            if ($_retry && strpos($zdbse->getMessage(), 'Deadlock') !== false) {
                return $this->autoProcess($_iMIP, false);
            } else {
                throw $zdbse;
            }
        }
    }
    
    /**
     * manual process iMIP component and optionally set status
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  string                $_status
     * @param  boolean               $_retry    retry in case a deadlock occured
     * @return boolean
     */
    public function process($_iMIP, $_status = NULL, $_retry = true)
    {
        try {
            // client spoofing protection - throws exception if spoofed
            Tinebase_EmailUser_Factory::getInstance('Controller_Message')->getiMIP($_iMIP->getId());

            return $this->_process($_iMIP, $_status);
        } catch (Zend_Db_Statement_Exception $zdbse) {
            if ($_retry && strpos($zdbse->getMessage(), 'Deadlock') !== false) {
                return $this->process($_iMIP, $_status, false);
            } else {
                throw $zdbse;
            }
        }
    }
    
    /**
     * prepares iMIP component for client
     *  
     * @param Calendar_Model_iMIP $_iMIP
     * @param boolean $_throwException
     * @return Calendar_Model_iMIP
     */
    public function prepareComponent($_iMIP, $_throwException = false)
    {
        $this->_checkPreconditions($_iMIP, $_throwException);
        
        Calendar_Convert_Event_Json::resolveRelatedData($_iMIP->event);
        Tinebase_Model_Container::resolveContainerOfRecord($_iMIP->event);
        Tinebase_Model_Container::resolveContainerOfRecord($this->getExistingEvent($_iMIP));
        
        return $_iMIP;
    }
    
    /**
     * check precondtions
     * 
     * @param Calendar_Model_iMIP $_iMIP
     * @param boolean $_throwException
     * @param string $_status
     * @throws Calendar_Exception_iMIP
     * @return boolean
     * 
     * @todo add iMIP record to exception when it extends the Data exception
     */
    protected function _checkPreconditions(Calendar_Model_iMIP $_iMIP, $_throwException = FALSE, $_status = NULL)
    {
        if ($_iMIP->preconditionsChecked) {
            if (empty($_iMIP->preconditions) || ! $_throwException) {
                return;
            } else {
                throw new Calendar_Exception_iMIP('iMIP preconditions failed: ' . implode(', ', array_keys($_iMIP->preconditions)));
            }
        }
        
        $method = $_iMIP->method ? ucfirst(strtolower($_iMIP->method)) : 'MISSINGMETHOD';
        
        $preconditionMethodName  = '_check'     . $method . 'Preconditions';
        if (method_exists($this, $preconditionMethodName)) {
            $preconditionCheckSuccessful = $this->{$preconditionMethodName}($_iMIP, $_status);
        } else {
            $preconditionCheckSuccessful = TRUE;
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . " No preconditions check fn found for method " . $method);
        }
        
        $_iMIP->preconditionsChecked = TRUE;
        
        if ($_throwException && ! $preconditionCheckSuccessful) {
            throw new Calendar_Exception_iMIP('iMIP preconditions failed: ' . implode(', ', array_keys($_iMIP->preconditions)));
        }
        
        return $preconditionCheckSuccessful;
    }
    
    /**
     * assemble an iMIP component in the notification flow
     * 
     * @todo implement
     */
    public function assembleComponent()
    {
        // cancel normal vs. recur instance
    }
    
    /**
     * process iMIP component and optionally set status
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  string                $_status
     * @return mixed
     */
    protected function _process($_iMIP, $_status = NULL)
    {
        $method                  = ucfirst(strtolower($_iMIP->method));
        $processMethodName       = '_process'   . $method;
        
        if (! method_exists($this, $processMethodName)) {
            throw new Tinebase_Exception_UnexpectedValue("Method {$_iMIP->method} not supported");
        }

        $this->_checkPreconditions($_iMIP, true, $_status);
        $result = $this->{$processMethodName}($_iMIP, $_status);

        //clear existing event cache
        unset($_iMIP->existing_event);
        
        return $result;
    }
    
    /**
     * publish precondition
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @return boolean
     * 
     * @todo implement
     */
    protected function _checkPublishPreconditions($_iMIP)
    {
        $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing published events is not supported yet');
        
        return FALSE;
    }
    
    /**
     * process publish
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * 
     * @todo implement
     */
    protected function _processPublish($_iMIP)
    {
        // add/update event (if outdated) / no status stuff / DANGER of duplicate UIDs
        // -  no notifications!
    }
    
    /**
     * request precondition
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @return boolean
     */
    protected function _checkRequestPreconditions($_iMIP)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Checking REQUEST preconditions of iMIP ...');
        
        $result  = $this->_assertOwnAttender($_iMIP, TRUE, FALSE);
        $result &= $this->_assertOrganizer($_iMIP, TRUE, TRUE);
        
        $existingEvent = $this->getExistingEvent($_iMIP);
        if ($existingEvent) {
            $iMIPEvent = $_iMIP->getEvent();
            $isObsoleted = false;
            
            if (! $existingEvent->hasExternalOrganizer() && $iMIPEvent->isObsoletedBy($existingEvent)) {
                $isObsoleted = true;
            }
            
            else if ($iMIPEvent->external_seq < $existingEvent->external_seq) {
                $isObsoleted = true;
            }
            
            // allow if not rescheduled
            if ($isObsoleted && $existingEvent->isRescheduled($iMIPEvent)) {
                $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_RECENT, "old iMIP message");
                $result = FALSE;
            }
        } else {
            try {
                if ($this->getExistingEvent($_iMIP, TRUE, TRUE)) {
                    // Event was deleted/cancelled
                    $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_NOTDELETED, "old iMIP message is deleted");
                    $result = FALSE;
                }
            } catch (Tinebase_Exception_AccessDenied $e) {
                // attendee was removed from the event, continue ...
                $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_NOTCANCELLED, "event or attendee was cancelled from this event");
                $result = FALSE;
            }
        }
        
        return $result;
    }
    
    /**
    * returns and optionally asserts own attendee record
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  boolean               $_assertExistence
    * @param  boolean               $_assertOriginator
    * @return boolean
    */
    protected function _assertOwnAttender($_iMIP, $_assertExistence, $_assertOriginator)
    {
        $result = TRUE;
        
        $existingEvent = $this->getExistingEvent($_iMIP);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($existingEvent ? $existingEvent->attendee : $_iMIP->getEvent()->attendee);
        if ($_assertExistence && ! $ownAttender) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ATTENDEE, "processing {$_iMIP->method} for non attendee is not supported");
            $result = FALSE;
        }
        
        if ($_assertOriginator) {
            $result &= $this->_assertOriginator($_iMIP, $ownAttender->getResolvedUser(), 'own attendee');
        }
        
        return $result;
    }
    
    /**
     * assert originator
     * 
     * @param Calendar_Model_iMIP $_iMIP
     * @param Addressbook_Model_Contact $_contact
     * @param string $_who
     * @return boolean
     */
    protected function _assertOriginator(Calendar_Model_iMIP $_iMIP, $_contact, $_who)
    {
        if ($_contact === NULL) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORIGINATOR, $_who . " could not be found.");
            return FALSE;
        }
        
        $contactEmails = array($_contact->email, $_contact->email_home);
        if(! in_array(strtolower($_iMIP->originator), array_map('strtolower', $contactEmails))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__
            . ' originator ' . $_iMIP->originator . ' ! in_array() '. print_r($contactEmails, TRUE));
        
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORIGINATOR, $_who . " must be the same as originator of iMIP -> spoofing attempt?");
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    /**
     * 
     *
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  bool                  $_assertExistence
     * @param  bool                  $_assertOriginator
     * @param  bool                  $_assertAccount
     * @return Addressbook_Model_Contact
     * @throws Calendar_Exception_iMIP
     * 
     * @todo this needs to be splitted into assertExternalOrganizer / assertInternalOrganizer
     */
    protected function _assertOrganizer($_iMIP, $_assertExistence, $_assertOriginator, $_assertAccount = false)
    {
        $result = TRUE;
        
        $existingEvent = $this->getExistingEvent($_iMIP);
        $organizer = $existingEvent ? $existingEvent->resolveOrganizer() : $_iMIP->getEvent()->resolveOrganizer();
        
        if ($_assertExistence && ! $organizer) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORGANIZER, "processing {$_iMIP->method} without organizer is not possible");
            $result = FALSE;
        }
        
        // NOTE: originator might also be reply-to instead of from
        // NOTE: originator might act on behalf of organizer ("SENT-BY    ")
        // NOTE: an existing event might be updateable by an non organizer ("SENT-BY    ") originator
        // NOTE: CUA might skip the SENT-BY     param => bad luck
        /*
        if ($_assertOriginator) {
            $result &= $this->_assertOriginator($_iMIP, $organizer, 'organizer');
        }
        */
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Organizer: ' . ($organizer ? print_r($organizer->toArray(), true) : 'not found'));
        
        // config setting overwrites method param
        $assertAccount = Calendar_Config::getInstance()->get(Calendar_Config::DISABLE_EXTERNAL_IMIP, $_assertAccount);
        if ($assertAccount && (! $organizer || ! $organizer->account_id)) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORGANIZER, "processing {$_iMIP->method} without organizer user account is not possible");
            $result = FALSE;
        }
        
        return $result;
    }

    /**
     * find existing event by uid
     *
     * @param $_iMIP
     * @param bool $_refetch
     * @param bool $_getDeleted
     * @return NULL|Tinebase_Record_Interface
     */
    public function getExistingEvent($_iMIP, $_refetch = FALSE, $_getDeleted = FALSE)
    {
        if ($_refetch || ! $_iMIP->existing_event instanceof Calendar_Model_Event) {

            $iMIPEvent = $_iMIP->getEvent();

            $filters = new Calendar_Model_EventFilter(array(
                array('field' => 'uid',          'operator' => 'equals', 'value' => $iMIPEvent->uid),
            ));
            if ($_getDeleted) {
                $deletedFilter = new Tinebase_Model_Filter_Bool('is_deleted', 'equals', Tinebase_Model_Filter_Bool::VALUE_NOTSET);
                $filters->addFilter($deletedFilter);
            }
            $events = Calendar_Controller_MSEventFacade::getInstance()->search($filters);

            $event = $events->filter(Tinebase_Model_Grants::GRANT_READ, TRUE)->getFirstRecord();
            Calendar_Model_Attender::resolveAttendee($event['attendee'], true, $event);

            $_iMIP->existing_event = $event;
        }

        return $_iMIP->existing_event;
    }

    /**
     * process request
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  string                $_status
     */
    protected function _processRequest($_iMIP, $_status)
    {
        $existingEvent = $this->getExistingEvent($_iMIP);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($existingEvent ? $existingEvent->attendee : $_iMIP->getEvent()->attendee);
        $organizer = $existingEvent ? $existingEvent->resolveOrganizer() : $_iMIP->getEvent()->resolveOrganizer();
        
        // internal organizer:
        //  - event is up to date
        //  - status change could also be done by calendar method
        //  - normal notifications
        if ($organizer->account_id) {
            if (! $existingEvent) {
                // organizer has an account but no event exists, it seems that event was created from a non-caldav client
                // do not send notifications in this case + create event in context of organizer
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Organizer has an account but no event exists!');
                return; // not clear how to create in the organizers context...
                $sendNotifications = Calendar_Controller_Event::getInstance()->sendNotifications(FALSE);
                $existingEvent = Calendar_Controller_MSEventFacade::getInstance()->create($_iMIP->getEvent());
                Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications);
            }
            
            if ($_status && $_status != $ownAttender->status) {
                $ownAttender->status = $_status;
                Calendar_Controller_Event::getInstance()->attenderStatusUpdate($existingEvent, $ownAttender, $ownAttender->status_authkey);
            }
        }
        
        // external organizer:
        else {
            $sendNotifications = Calendar_Controller_Event::getInstance()->sendNotifications(false);
            $event = $_iMIP->getEvent();
            if (! $existingEvent) {
                if (! $event->container_id) {
                    $event->container_id = Tinebase_Core::getPreference('Calendar')->{Calendar_Preference::DEFAULTCALENDAR};
                }

                if (!empty($event->recurid) && empty($event->rrule)) {
                    if ($event->recurid instanceof Tinebase_DateTime) {
                        if (!isset($event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES])) {
                            $event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES] = [];
                        }
                        $event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['RECURRENCE-ID'] =
                            'RECURRENCE-ID:' . $event->recurid->setTimezone($event->originator_tz)->format('Ymd') . 'T'
                            . $event->recurid->format('His');
                    }
                    $event->recurid = null;
                }

                $event = $_iMIP->event = Calendar_Controller_MSEventFacade::getInstance()->create($event);
            } else {
                if ($event->external_seq > $existingEvent->external_seq ||
                        (isset($event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['LAST-MODIFIED']) &&
                        isset($existingEvent->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['LAST-MODIFIED'])
                        && $event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['LAST-MODIFIED'] >
                            $existingEvent->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['LAST-MODIFIED']) ||
                        (isset($event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['DTSTAMP']) &&
                            isset($existingEvent->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['DTSTAMP'])
                            && $event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['DTSTAMP'] >
                            $existingEvent->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['DTSTAMP'])) {
                    // updates event with .ics
                    $event->id = $existingEvent->id;
                    $event = $_iMIP->event = Calendar_Controller_MSEventFacade::getInstance()->update($event);
                } else {
                    // event is current
                    $event = $existingEvent;
                }
            }
            
            Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications);
            
            $ownAttender = Calendar_Model_Attender::getOwnAttender($event->attendee);
            
            // NOTE: we do the status update in a separate call to trigger the right notifications
            if ($ownAttender && $_status) {
                $ownAttender->status = $_status;
                $a = Calendar_Controller_Event::getInstance()->attenderStatusUpdate($event, $ownAttender, $ownAttender->status_authkey);
            }
        }
    }
    
    /**
     * reply precondition
     *
     * @TODO an internal reply should trigger a RECENT precondition
     * @TODO distinguish RECENT and PROCESSED preconditions?
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @return boolean
     */
    protected function _checkReplyPreconditions($_iMIP)
    {
        $result = TRUE;
        
        $existingEvent = $this->getExistingEvent($_iMIP);
        if (! $existingEvent) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_EVENTEXISTS, "cannot process REPLY to non existent/invisible event");
            return false;
        }
        
        $iMIPAttenderIdx = $_iMIP->getEvent()->attendee instanceof Tinebase_Record_RecordSet ? array_search(
            strtolower($_iMIP->originator),
            array_map('strtolower', $_iMIP->getEvent()->attendee->getEmail())
        ) : FALSE;
        /** @var Calendar_Model_Attender $iMIPAttender */
        $iMIPAttender = $iMIPAttenderIdx !== FALSE ? $_iMIP->getEvent()->attendee[$iMIPAttenderIdx] : NULL;
        $iMIPAttenderStatus = $iMIPAttender ? $iMIPAttender->status : NULL;
        $eventAttenderIdx = $existingEvent->attendee instanceof Tinebase_Record_RecordSet ? array_search(
            strtolower($_iMIP->originator),
            array_map('strtolower', $existingEvent->attendee->getEmail())
        ) : FALSE;
        /** @var Calendar_Model_Attender $eventAttender */
        $eventAttender = $eventAttenderIdx !== FALSE ? $existingEvent->attendee[$eventAttenderIdx] : NULL;
        $eventAttenderStatus = $eventAttender ? $eventAttender->status : NULL;

        if ($existingEvent->isRescheduled($_iMIP->getEvent())) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_RECENT, "event was rescheduled");
            $result = FALSE;
        }
        
        if (! $eventAttender) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORIGINATOR, "originator is not attendee in existing event -> party crusher?");
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' originator is not attendee in existing event - originator: ' . print_r($_iMIP->originator, true));
            $result = FALSE;
        } elseif (isset($eventAttender->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE])) {
            if ($eventAttender->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE] > $_iMIP->getEvent()->seq ||
                ($eventAttender->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE] == $_iMIP->getEvent()->seq &&
                    (!$_iMIP->getEvent()->last_modified_time instanceof Tinebase_DateTime ||
                        !isset($eventAttender->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]) ||
                        $_iMIP->getEvent()->last_modified_time->isEarlierOrEquals(new Tinebase_DateTime(
                        $eventAttender->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]))))) {
                $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_RECENT, "old iMIP message");
                $result = FALSE;
            }
        }

        if (! is_null($iMIPAttenderStatus) && $iMIPAttenderStatus == $eventAttenderStatus) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_TOPROCESS, "this REPLY was already processed");
            $result = FALSE;
        }
        
        if (! $iMIPAttender) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORIGINATOR,
                "originator is not attendee in iMIP transaction -> spoofing attempt?");
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' originator is not attendee in iMIP transaction - originator: ' . print_r($_iMIP->originator, true));
            $result = FALSE;
        }
        
        // TODO fix organizer account asserting
        if (! $this->_assertOrganizer($_iMIP, TRUE, FALSE/*, $_assertAccount = TRUE */)) {
            $result = FALSE;
        }
        
        return $result;
    }
    
    /**
     * process reply
     * 
     * some attender replied to my request (I'm Organizer) -> update status (seq++) / send notifications!
     * 
     * NOTE: only external replies should be processed here
     *       @todo check silence for internal replies
     *       
     * @param  Calendar_Model_iMIP   $_iMIP
     * @return boolean
     */
    protected function _processReply(Calendar_Model_iMIP $_iMIP)
    {
        // merge ics into existing event
        $existingEvent = $this->getExistingEvent($_iMIP);
        $event = $_iMIP->mergeEvent($existingEvent);
        $attendee = $event->attendee[array_search(strtolower($_iMIP->originator),
            array_map('strtolower', $existingEvent->attendee->getEmail()))];

        // do not use $event here! seq last_modified_time gets overridden by existingEvent
        $attendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE] = $_iMIP->getEvent()->seq;
        if ($_iMIP->getEvent()->last_modified_time instanceof Tinebase_DateTime) {
            $attendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP] = $_iMIP->getEvent()->last_modified_time
                ->toString();
        } else {
            unset($attendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]);
        }

        // NOTE: if current user has no rights to the calendar, status update is not applied
        Calendar_Controller_MSEventFacade::getInstance()->attenderStatusUpdate($event, $attendee);

        return true;
    }
    
    /**
    * add precondition
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @return boolean
    *
    * @todo implement
    */
    protected function _checkAddPreconditions($_iMIP)
    {
        $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing add requests is not supported yet');
    
        return FALSE;
    }
    
    /**
    * process add
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * 
    * @todo implement
    */
    protected function _processAdd($_iMIP)
    {
        // organizer added a meeting/recurrance to an existing event -> update event
        // internal organizer:
        //  - event is up to date nothing to do
        // external organizer:
        //  - update event
        //  - the iMIP is already the notification mail!
    }
    
    /**
    * cancel precondition
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @return boolean
    */
    protected function _checkCancelPreconditions($_iMIP)
    {
        $existingEvent = $this->getExistingEvent($_iMIP, FALSE, TRUE);
        $result = TRUE;

        if ($existingEvent) {
            if ($existingEvent->is_deleted) {
                $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_NOTDELETED, "old iMIP message is deleted");
                $result = FALSE;
            } else if (! $existingEvent->hasExternalOrganizer()) {
                $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_RECENT, "old iMIP message");
                $result = FALSE;
            }
        }
        return $result;
    }
    
    /**
    * process cancel
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  string                $_status
    */
    protected function _processCancel($_iMIP, $_status)
    {
        // organizer cancelled meeting/recurrence of an existing event -> update event
        // the iMIP is already the notification mail!
        $existingEvent = $this->getExistingEvent($_iMIP, FALSE, TRUE);
        $event = $_iMIP->getEvent();

        if ($existingEvent) {
            if (! $existingEvent->is_deleted) {
                if ($event->status == Calendar_Model_Event::STATUS_CANCELED) {
                    // Event cancelled
                    Calendar_Controller_MSEventFacade::getInstance()->delete($existingEvent->getId());
                } else {
                    // Attendees cancelled
                    Calendar_Controller_MSEventFacade::getInstance()->deleteAttendees($existingEvent, $event);
                }
            }
        } else {
            // create a deleted/cancelled event
            $sendNotifications = Calendar_Controller_Event::getInstance()->sendNotifications(FALSE);

            $event = $_iMIP->event = Calendar_Controller_MSEventFacade::getInstance()->create($event);
            Calendar_Controller_MSEventFacade::getInstance()->delete($event->getId());

            Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications);
        }
    }
    
    /**
    * refresh precondition
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @return boolean
    *
    * @todo implement
    */
    protected function _checkRefreshPreconditions($_iMIP)
    {
        $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing REFRESH is not supported yet');
    
        return FALSE;
    }
    
    /**
    * process refresh
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    *
    * @todo implement
    */
    protected function _processRefresh($_iMIP)
    {
        // always internal organizer
        //  - send message
        //  - mark iMIP message ANSWERED
    }
    
    /**
    * counter precondition
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @return boolean
    *
    * @todo implement
    */
    protected function _checkCounterPreconditions($_iMIP)
    {
        $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing COUNTER is not supported yet');
    
        return FALSE;
    }
    
    /**
    * process counter
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    *
    * @todo implement
    */
    protected function _processCounter($_iMIP)
    {
        // some attendee suggests to change the event
        // status: ACCEPT => update event, send notifications to all
        // status: DECLINE => send DECLINECOUNTER to originator
        // mark message ANSWERED
    }
    
    /**
    * declinecounter precondition
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @return boolean
    *
    * @todo implement
    */
    protected function _checkDeclinecounterPreconditions($_iMIP)
    {
        $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing DECLINECOUNTER is not supported yet');
    
        return FALSE;
    }
    
    /**
    * process declinecounter
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    *
    * @todo implement
    */
    protected function _processDeclinecounter($_iMIP)
    {
        // organizer declined my counter request of an existing event -> update event
    }
}
