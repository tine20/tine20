<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     */
    public function autoProcess($_iMIP)
    {
        if ($_iMIP->method == Calendar_Model_iMIP::METHOD_COUNTER) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . " skip auto processing of iMIP component with COUNTER method -> must always be processed manually");
            return;
        }
        
        if (! $_iMIP->getExistingEvent(TRUE)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . " skip auto processing of iMIP component whose event is not in our db yet");
            return;
        }
        
        // update existing event details _WITHOUT_ status updates
        return $this->_process($_iMIP);
    }
    
    /**
     * manual process iMIP component and optionally set status
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  string                $_status
     */
    public function process($_iMIP, $_status = NULL)
    {
        // client spoofing protection
        $iMIP = Felamimail_Controller_Message::getInstance()->getiMIP($_iMIP->getId());
        
        return $this->_process($_iMIP, $_status);
    }
    
    /**
     * prepares iMIP component for client
     *  
     * @param  Calendar_Model_iMIP $_iMIP
     * @return Calendar_Model_iMIP
     */
    public function prepareComponent($_iMIP)
    {
        $this->_checkPreconditions($_iMIP);
        
        Calendar_Convert_Event_Json::resolveRelatedData($_iMIP->event);
        Tinebase_Model_Container::resolveContainerOfRecord($_iMIP->event);
        Tinebase_Model_Container::resolveContainerOfRecord($_iMIP->getExistingEvent());
        
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
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " No preconditions check fn found for method " . $method);
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
        
        $this->_checkPreconditions($_iMIP, TRUE, $_status);
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
        $result  = $this->_assertOwnAttender($_iMIP, TRUE, FALSE);
        $result &= $this->_assertOrganizer($_iMIP, TRUE, TRUE);
        
        $existingEvent = $_iMIP->getExistingEvent();
        if ($existingEvent && $_iMIP->getEvent()->isObsoletedBy($existingEvent)) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_RECENT, "old iMIP message");
            $result = FALSE;
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
        
        $existingEvent = $_iMIP->getExistingEvent();
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
        if(! in_array($_iMIP->originator, $contactEmails)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__
            . ' originator ' . $_iMIP->originator . ' ! in_array() '. print_r($contactEmails, TRUE));
        
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORIGINATOR, $_who . " must be the same as originator of iMIP -> spoofing attempt?");
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    /**
    * returns and optionally asserts own attendee record
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
    protected function _assertOrganizer($_iMIP, $_assertExistence, $_assertOriginator, $_assertAccount = FALSE)
    {
        $result = TRUE;
        
        $existingEvent = $_iMIP->getExistingEvent();
        $organizer = $existingEvent ? $existingEvent->resolveOrganizer() : $_iMIP->getEvent()->resolveOrganizer();
        
        if ($_assertExistence && ! $organizer) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORGANIZER, "processing {$_iMIP->method} without organizer is not possible");
            $result = FALSE;
        }
        
        // NOTE: originator might also be reply-to instead of from
        // NOTE: originator might act on behalf of organizer ("SEND-BY")
        // NOTE: an existing event might be updateable by an non organizer ("SEND-BY") originator
        // NOTE: CUA might skip the SEND-BY param => bad luck
        /*
        if ($_assertOriginator) {
            $result &= $this->_assertOriginator($_iMIP, $organizer, 'organizer');
        }
        */
        
        /*
        if ($_assertAccount && (! $organizer || ! $organizer->account_id)) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORGANIZER, "processing {$_iMIP->method} without organizer user account is not possible");
            $result = FALSE;
        }
        */
        
        return $result;
    }
    
    /**
     * process request
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  string                $_status
     * @throws Tinebase_Exception_NotImplemented
     * 
     * @todo handle external organizers
     * @todo create event in the organizers context
     */
    protected function _processRequest($_iMIP, $_status)
    {
        $existingEvent = $_iMIP->getExistingEvent();
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
            if ($ownAttender && $_status) {
                $ownAttender->status = $_status;
            }
            
            if (! $existingEvent) {
                $event = $_iMIP->getEvent();
                if (! $event->container_id) {
                    $event->container_id = Tinebase_Core::getPreference('Calendar')->{Calendar_Preference::DEFAULTCALENDAR};
                }
                
                $_iMIP->event = Calendar_Controller_MSEventFacade::getInstance()->create($event);
            } else {
                $_iMIP->event = Calendar_Controller_MSEventFacade::getInstance()->update($existingEvent);
            }
            
            //  - send reply to organizer
        }
    }
    
    /**
    * reply precondition
    *
    * @TODO an internal reply should trigge a RECENT precondition
    * @TODO distinguish RECENT and PROCESSED preconditions?
    * 
    * @param  Calendar_Model_iMIP   $_iMIP
    * @return boolean
    */
    protected function _checkReplyPreconditions($_iMIP)
    {
        $result = TRUE;
        
        $existingEvent = $_iMIP->getExistingEvent();
        if (! $existingEvent) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_EVENTEXISTS, "cannot process REPLY to non existent/invisible event");
            $result = FALSE;
        }
        
        $iMIPAttenderIdx = $_iMIP->getEvent()->attendee instanceof Tinebase_Record_RecordSet ? array_search($_iMIP->originator, $_iMIP->getEvent()->attendee->getEmail()) : FALSE;
        $iMIPAttender = $iMIPAttenderIdx !== FALSE ? $_iMIP->getEvent()->attendee[$iMIPAttenderIdx] : NULL;
        $iMIPAttenderStatus = $iMIPAttender ? $iMIPAttender->status : NULL;
        $eventAttenderIdx = $existingEvent->attendee instanceof Tinebase_Record_RecordSet ? array_search($_iMIP->originator, $existingEvent->attendee->getEmail()) : FALSE;
        $eventAttender = $eventAttenderIdx !== FALSE ? $existingEvent->attendee[$eventAttenderIdx] : NULL;
        $eventAttenderStatus = $eventAttender ? $eventAttender->status : NULL;
        
        if ($_iMIP->getEvent()->isObsoletedBy($existingEvent)) {
            
            // allow non RECENT replies if no reschedule and STATUS_NEEDSACTION
            if ($eventAttenderStatus != Calendar_Model_Attender::STATUS_NEEDSACTION || $existingEvent->isRescheduled($_iMIP->getEvent())) {
                $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_RECENT, "old iMIP message");
                $result = FALSE;
            }
        }
        
        if (! is_null($iMIPAttenderStatus) && $iMIPAttenderStatus == $eventAttenderStatus) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_TOPROCESS, "this REPLY was already processed");
            $result = FALSE;
        }
        
        if (! $eventAttender) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORIGINATOR, "originator is not attendee in existing event -> party crusher?");
            $result = FALSE;
        }
        
        if (! $iMIPAttender) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORIGINATOR, "originator is not attendee in iMIP transaction -> spoofing attempt?");
            $result = FALSE;
        }
        
        if (! $this->_assertOrganizer($_iMIP, TRUE, FALSE, TRUE)) {
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
     */
    protected function _processReply(Calendar_Model_iMIP $_iMIP)
    {
        // merge ics into existing event
        $existingEvent = $_iMIP->getExistingEvent();
        $event = $_iMIP->mergeEvent($existingEvent);
        $attendee = $event->attendee[array_search($_iMIP->originator, $existingEvent->attendee->getEmail())];
        
        // NOTE: if current user has no rights to the calendar, status update is not applied
        Calendar_Controller_MSEventFacade::getInstance()->attenderStatusUpdate($event, $attendee);
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
    *
    * @todo implement
    */
    protected function _checkCancelPreconditions($_iMIP)
    {
        $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing CANCEL is not supported yet');
    
        return FALSE;
    }
    
    /**
    * process cancel
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  Calendar_Model_Event  $_existingEvent
    * 
    * @todo implement
    */
    protected function _processCancel($_iMIP, $_existingEvent)
    {
        // organizer cancelled meeting/recurrence of an existing event -> update event
        // the iMIP is already the notification mail!
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
