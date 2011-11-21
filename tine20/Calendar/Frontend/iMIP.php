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
     * @TODO  move to Calendar_Frontend_Json / Model?
     *  
     * @param  Calendar_Model_iMIP $_iMIP
     * @return Calendar_Model_iMIP
     */
    public function prepareComponent($_iMIP)
    {
        Calendar_Model_Attender::resolveAttendee($_iMIP->event->attendee);
        Tinebase_Model_Container::resolveContainer($_iMIP->event->container_id);
        
        return $_iMIP;
    }
    
    /**
     * assemble an iMIP component in the notification flow
     * 
     * 
     */
    public function assembleComponent()
    {
        // cancle normal vs. recur instance
        
    }
    
    /**
     * process iMIP component and optionally set status
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  Calendar_Model_Event  $_event
     * @param  string                $_status
     */
    protected function _process($_iMIP, $_existingEvent, $_status = NULL)
    {
        $methodName = '_process' . ucfirst(strtolower($_iMIP->method));
        if (! method_exists($this, $methodName)) {
            throw new Tinebase_Exception_UnexpectedValue("method {$_iMIP->method} not supported");
        }
        
        return $this->{$methodName}($_iMIP, $_existingEvent, $_status);
        
        // not adequate for all methods
        if ($_existingEvent && ! $_iMIP->obsoletes($_existingEvent->getEvent())) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . " skip processing of an old iMIP component");
            return;
        }
    }
    
    protected function _processPublish($_iMIP, $_existingEvent)
    {
        // add/update event (if outdated) / no status stuff / DANGER of duplicate UIDs
        // no notifications!
        throw new Tinebase_Exception_NotImplemented('processing published events is not supported yet');
    }
    
    protected function _processRequest($_iMIP, $_existingEvent)
    {
        $ownAttender = $this->_getOwnAttender($_iMIP, $_existingEvent, TRUE, FALSE);
        $organizer = $this->_getOrganizer($_iMIP, $_existingEvent, TRUE, TRUE);
        
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
        else {
            throw new Tinebase_Exception_NotImplemented('processing external requests is not supported yet');
        }
    }
    
    protected function _processReply($_iMIP, $_existingEvent)
    {
        if (! $_existingEvent) {
            throw new Calendar_Exception_iMIP('cannot process REPLY to non existent/invisible event');
        }
        
        if ($_iMIP->getEvent()->obsoletes($_existingEvent)) {
            // old iMIP message
            return;
        }
        
        $iMIPAttenderIdx = array_search($_iMIP->originator, $_iMIP->getEvent()->attendee->getEmail());
        if ($iMIPAttenderIdx === FALSE) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__
                . ' originator ' . $_iMIP->originator . ' != '. $_existingEvent->attendee->getEmail());
            throw new Calendar_Exception_iMIP('originator is not attendee in iMIP transaction-> spoofing attempt?');
        }
        $iMIPAttender = $_iMIP->getEvent()->attendee[$iMIPAttenderIdx];
        
        $existingAttenderIdx = array_search($_iMIP->originator, $_existingEvent->attendee->getEmail());
        if ($existingAttenderIdx === FALSE) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__
                . ' originator ' . $_iMIP->originator . ' != '. $_existingEvent->attendee->getEmail());
            throw new Calendar_Exception_iMIP('originator is not attendee in existing event -> party crusher?');
        }
        $existingAttender = $_existingEvent->attendee[$existingAttenderIdx];
        
        $organizer = $this->_getOrganizer($_iMIP, $_existingEvent, TRUE, FALSE);
        if (! $organizer->account_id) {
            throw new Calendar_Exception_iMIP('cannot process reply to externals organizers event');
        }
        
        // status update 
        
        // some attender replied to my request (I'm Organizer) -> update status (seq++) / send notifications!
    }
    
    protected function _processAdd($_iMIP, $_existingEvent)
    {
        // organizer added a meeting/recurrance to an existing event -> update event
        // internal organizer:
        //  - event is up to date nothing to do
        // external organizer:
        //  - update event
        //  - the iMIP is already the notification mail!
        throw new Tinebase_Exception_NotImplemented('processing add requests is not supported yet');
    }
    
    protected function _processCancel($_iMIP, $_existingEvent)
    {
        // organizer caneled meeting/recurrence of an existing event -> update event
        // the iMIP is already the notification mail!
        throw new Tinebase_Exception_NotImplemented('processing CANCEL is not supported yet');
    }
    
    protected function _processRefresh($_iMIP, $_existingEvent)
    {
        // always internal organizer
        //  - send message
        //  - mark iMIP message ANSWERED
        throw new Tinebase_Exception_NotImplemented('processing REFRESH is not supported yet');
    }
    
    protected function _processCounter($_iMIP, $_existingEvent)
    {
        // some attendee suggests to change the event
        // status: ACCEPT => update event, send notifications to all
        // status: DECLINE => send DECLINECOUNTER to originator
        // mark message ANSWERED
        throw new Tinebase_Exception_NotImplemented('processing COUNTER is not supported yet');
    }
    
    protected function _processDeclinecounter($_iMIP, $_existingEvent)
    {
        // organizer declined my counter request of an existing event -> update event
        throw new Tinebase_Exception_NotImplemented('processing DECLINECOUNTER is not supported yet');
    }
    
    /**
     * returns and optionally asserts own attendee record
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  string                $_status
     * @param  bool                  $_assertExistence
     * @param  bool                  $_assertExistence
     * @return Calendar_Model_Attender
     * @throws Calendar_Exception_iMIP
     */
    protected function _getOwnAttender($_iMIP, $_existingEvent, $_assertExistence, $_assertOriginator)
    {
        $ownAttender = Calendar_Model_Attender::getOwnAttender($_existingEvent ? $_existingEvent->attendee : $_iMIP->getEvent()->attendee);
        if ($_assertExistence && ! $ownAttender) {
            throw new Calendar_Exception_iMIP("processing {$_iMIP->method} for non attendee is not supported");
        }
        if ($_assertOriginator) {
            $contact = $ownAttender->getResolvedUser();
            $contactEmails = array($contact->email, $contact->email_home);
            if(! in_array($_iMIP->originator, $contactEmails)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__
                    . ' originator ' . $_iMIP->originator . ' ! in_array() '. print_r($contactEmails, TRUE));
                
                throw new Calendar_Exception_iMIP("own attendee must be the same as originator of iMIP -> spoofing attempt?");
            }
        }
        return $ownAttender;
    }
    
    /**
     * returns and optionally asserts own attendee record
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  string                $_status
     * @param  bool                  $_assertExistence
     * @param  bool                  $_assertOriginator
     * @return Addressbook_Model_Contact
     * @throws Calendar_Exception_iMIP
     */
    protected function _getOrganizer($_iMIP, $_existingEvent, $_assertExistence, $_assertOriginator)
    {
        $organizer = $_existingEvent ? $_existingEvent->resolveOrganizer() : $_iMIP->getEvent()->resolveOrganizer();
        if ($_assertExistence && ! $organizer) {
            throw new Calendar_Exception_iMIP("processing {$_iMIP->method} without organizer is not possible");
        }
        
        $organizerEmails =  array($organizer->email, $organizer->email_home);
        if ($_assertOriginator && ! in_array($_iMIP->originator, $organizerEmails)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__
                . ' originator ' . $_iMIP->originator . ' ! in_array() '. print_r($organizerEmails, TRUE));
            
            throw new Calendar_Exception_iMIP("organizer of event must be the same as originator of iMIP -> spoofing attempt?");
        }
        
        return $organizer;
    }
}