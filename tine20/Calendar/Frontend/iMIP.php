<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
        
        $exitingEvent = Calendar_Controller_MSEventFacade::getInstance()->lookupExistingEvent($_iMIP->getEvent());
        
        if (! $exitingEvent) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . " skip auto processing of iMIP component whose event is not in our db yet");
            return;
        }
        
        // update existing event details _WITHOUT_ status updates
        return $this->_process($_iMIP, $exitingEvent);
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
        
        $exitingEvent = Calendar_Controller_MSEventFacade::getInstance()->lookupExistingEvent($iMIP->getEvent());
        return $this->_process($_iMIP, $exitingEvent, $_status);
    }
    
    /**
     * prepares iMIP component for client
     * 
     * @TODO  move to Calendar_Frontend_Json / Model / Convert?
     *  
     * @param  Calendar_Model_iMIP $_iMIP
     * @return Calendar_Model_iMIP
     */
    public function prepareComponent($_iMIP)
    {
        // @todo check preconditions if not processed / add is_processed to model
        
        Calendar_Model_Attender::resolveAttendee($_iMIP->event->attendee);
        Tinebase_Model_Container::resolveContainer($_iMIP->event);
        
        return $_iMIP;
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
     * @param  Calendar_Model_Event  $_event
     * @param  string                $_status
     * @return mixed
     * 
     * @todo what to do with obsolete check?
     * @todo throw exception if precondition(s) failed
     */
    protected function _process($_iMIP, $_existingEvent, $_status = NULL)
    {
        $method                  = ucfirst(strtolower($_iMIP->method));
        $processMethodName       = '_process'   . $method;
        $preconditionMethodName  = '_check'     . $method . 'Preconditions';
        
        if (! method_exists($this, $processMethodName)) {
            throw new Tinebase_Exception_UnexpectedValue("Method {$_iMIP->method} not supported");
        }
        
        if (method_exists($this, $preconditionMethodName)) {
            $preconditionCheckSuccessful = $this->{$preconditionMethodName}($_iMIP, $_existingEvent, $_status);
        } else {
            $preconditionCheckSuccessful = TRUE;
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " No preconditions check fn found for method " . $method);
        }
        
        if ($preconditionCheckSuccessful) {
            $result = $this->{$processMethodName}($_iMIP, $_existingEvent, $_status);
        } else {
            $result = FALSE;
        }
        
        return $result;
        
        // not adequate for all methods
//         if ($_existingEvent && ! $_iMIP->obsoletes($_existingEvent->getEvent())) {
//             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . " skip processing of an old iMIP component");
//             return;
//         }
    }
    
    /**
     * publish precondition
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  Calendar_Model_Event  $_existingEvent
     * @return boolean
     * 
     * @todo implement
     */
    protected function _checkPublishPreconditions($_iMIP, $_existingEvent)
    {
        $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing published events is not supported yet');
        
        return FALSE;
    }
    
    /**
     * process publish
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  Calendar_Model_Event  $_existingEvent
     * 
     * @todo implement
     */
    protected function _processPublish($_iMIP, $_existingEvent)
    {
        // add/update event (if outdated) / no status stuff / DANGER of duplicate UIDs
        // -  no notifications!
    }
    
    /**
     * request precondition
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  Calendar_Model_Event  $_existingEvent
     * @return boolean
     * 
     * @todo do obsolete check?
     */
    protected function _checkRequestPreconditions($_iMIP, $_existingEvent)
    {
        $result  = $this->_assertOwnAttender($_iMIP, $_existingEvent, TRUE, FALSE)
                && $this->_assertOrganizer($_iMIP, $_existingEvent, TRUE, TRUE, TRUE);
        
         if (! $_iMIP->getEvent()->obsoletes($_existingEvent)) {
             $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_RECENT, "old iMIP message");
             return FALSE;
         }
        
        return $result;
    }
    
    /**
    * returns and optionally asserts own attendee record
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  string                $_status
    * @param  boolean               $_assertExistence
    * @param  boolean               $_assertOriginator
    * @return boolean
    */
    protected function _assertOwnAttender($_iMIP, $_existingEvent, $_assertExistence, $_assertOriginator)
    {
        $result = TRUE;
        
        $ownAttender = Calendar_Model_Attender::getOwnAttender($_existingEvent ? $_existingEvent->attendee : $_iMIP->getEvent()->attendee);
        if ($_assertExistence && ! $ownAttender) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ATTENDEE, "processing {$_iMIP->method} for non attendee is not supported");
            return FALSE;
        }
        
        if ($_assertOriginator) {
            $result = $this->_assertOriginator($_iMIP, $ownAttender->getResolvedUser(), 'own attendee');
        }
        
        return $result;
    }
    
    /**
     * assert originator
     * 
     * @param Calendar_Model_iMIP $_iMIP
     * @param Addressbook_Model_Contact $_contact
     */
    protected function _assertOriginator(Calendar_Model_iMIP $_iMIP, Addressbook_Model_Contact $_contact, $_who)
    {
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
    * @param  string                $_status
    * @param  bool                  $_assertExistence
    * @param  bool                  $_assertOriginator
    * @param  bool                  $_assertAccount
    * @return Addressbook_Model_Contact
    * @throws Calendar_Exception_iMIP
    */
    protected function _assertOrganizer($_iMIP, $_existingEvent, $_assertExistence, $_assertOriginator, $_assertAccount = FALSE)
    {
        $organizer = $_existingEvent ? $_existingEvent->resolveOrganizer() : $_iMIP->getEvent()->resolveOrganizer();
        if ($_assertExistence && ! $organizer) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORGANIZER, "processing {$_iMIP->method} without organizer is not possible");
            return FALSE;
        }
        
        if ($_assertOriginator) {
            $result = $this->_assertOriginator($_iMIP, $organizer, 'organizer');
        }
        
        if ($_assertAccount && ! $organizer->account_id) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORGANIZER, "processing {$_iMIP->method} without organizer user account is not possible");
            $result = FALSE;
        }
    
        return $result;
    }
    
    /**
     * process request
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  Calendar_Model_Event  $_existingEvent
     * @param  string                $_status
     * @throws Tinebase_Exception_NotImplemented
     */
    protected function _processRequest($_iMIP, $_existingEvent, $_status)
    {
        $ownAttender = Calendar_Model_Attender::getOwnAttender($_existingEvent ? $_existingEvent->attendee : $_iMIP->getEvent()->attendee);
        $organizer = $_existingEvent ? $_existingEvent->resolveOrganizer() : $_iMIP->getEvent()->resolveOrganizer();
        
        // internal organizer:
        //  - event is up to date
        //  - status change could also be done by calendar method
        //  - normal notifications
        if ($_existingEvent && $organizer->account_id) {
            if ($_status && $_status != $ownAttender->status) {
                $ownAttender->status = $_status;
                Calendar_Controller_Event::getInstance()->attenderStatusUpdate($_existingEvent, $ownAttender, $ownAttender->status_authkey);
            }
        }
        
        // external organizer:
        //  - update (might have acl problems)
        //  - set status
        //  - send reply to organizer
        //  - remove $_assertAccount precondition
    }
    
    /**
    * reply precondition
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  Calendar_Model_Event  $_existingEvent
    * @return boolean
    * 
    * @todo collect preconditions?
    * @todo use isObsoletedBy ?
    */
    protected function _checkReplyPreconditions($_iMIP, $_existingEvent)
    {
        if (! $_existingEvent) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_EVENTEXISTS, "cannot process REPLY to non existent/invisible event");
            return FALSE;
        }
        
         if ($_iMIP->getEvent()->isObsoletedBy($_existingEvent)) {
             $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_RECENT, "old iMIP message");
             return FALSE;
         }
        
        if (! $this->_assertOriginatorIsAttender($_iMIP, $_iMIP->getEvent())) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORIGINATOR, "originator is not attendee in iMIP transaction -> spoofing attempt?");
            return FALSE;
        }
        
        if (! $this->_assertOriginatorIsAttender($_iMIP, $_existingEvent)) {
            $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_ORIGINATOR, "originator is not attendee in existing event -> party crusher?");
            return FALSE;
        }
        
        if (! $this->_assertOrganizer($_iMIP, $_existingEvent, TRUE, FALSE, TRUE)) {
            return FALSE;
        }
        
        return TRUE;
    }
    
    /**
     * assert originator is attender in event
     * 
     * @param Calendar_Model_iMIP $_iMIP
     * @param Calendar_Model_Event $_event
     */
    protected function _assertOriginatorIsAttender($_iMIP, $_event)
    {
        $iMIPAttenderIdx = array_search($_iMIP->originator, $_event->attendee->getEmail());
        if ($iMIPAttenderIdx === FALSE) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__
                . ' originator ' . $_iMIP->originator . ' != '. print_r($_event->attendee->getEmail(), TRUE));
            return FALSE;
        }
        return TRUE; 
    }
    
    /**
     * process reply
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  Calendar_Model_Event  $_existingEvent
     * 
     * @todo implement
     */
    protected function _processReply($_iMIP, $_existingEvent)
    {
        // status update 
        // some attender replied to my request (I'm Organizer) -> update status (seq++) / send notifications!
    }
    
    /**
    * add precondition
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  Calendar_Model_Event  $_existingEvent
    * @return boolean
    *
    * @todo implement
    */
    protected function _checkAddPreconditions($_iMIP, $_existingEvent)
    {
        $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing add requests is not supported yet');
    
        return FALSE;
    }
    
    /**
    * process add
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  Calendar_Model_Event  $_existingEvent
    * 
    * @todo implement
    */
    protected function _processAdd($_iMIP, $_existingEvent)
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
    * @param  Calendar_Model_Event  $_existingEvent
    * @return boolean
    *
    * @todo implement
    */
    protected function _checkCancelPreconditions($_iMIP, $_existingEvent)
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
        // organizer caneled meeting/recurrence of an existing event -> update event
        // the iMIP is already the notification mail!
    }
    
    /**
    * refresh precondition
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  Calendar_Model_Event  $_existingEvent
    * @return boolean
    *
    * @todo implement
    */
    protected function _checkRefreshPreconditions($_iMIP, $_existingEvent)
    {
        $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing REFRESH is not supported yet');
    
        return FALSE;
    }
    
    /**
    * process refresh
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  Calendar_Model_Event  $_existingEvent
    *
    * @todo implement
    */
    protected function _processRefresh($_iMIP, $_existingEvent)
    {
        // always internal organizer
        //  - send message
        //  - mark iMIP message ANSWERED
    }
    
    /**
    * counter precondition
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  Calendar_Model_Event  $_existingEvent
    * @return boolean
    *
    * @todo implement
    */
    protected function _checkCounterPreconditions($_iMIP, $_existingEvent)
    {
        $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing COUNTER is not supported yet');
    
        return FALSE;
    }
    
    /**
    * process counter
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  Calendar_Model_Event  $_existingEvent
    *
    * @todo implement
    */
    protected function _processCounter($_iMIP, $_existingEvent)
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
    * @param  Calendar_Model_Event  $_existingEvent
    * @return boolean
    *
    * @todo implement
    */
    protected function _checkDeclinecounterPreconditions($_iMIP, $_existingEvent)
    {
        $_iMIP->addFailedPrecondition(Calendar_Model_iMIP::PRECONDITION_SUPPORTED, 'processing DECLINECOUNTER is not supported yet');
    
        return FALSE;
    }
    
    /**
    * process declinecounter
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  Calendar_Model_Event  $_existingEvent
    *
    * @todo implement
    */
    protected function _processDeclinecounter($_iMIP, $_existingEvent)
    {
        // organizer declined my counter request of an existing event -> update event
    }
}
