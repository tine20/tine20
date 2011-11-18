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
            // counter request must always be decided manually
            return;
        }
        
        $exitingEvent = Calendar_Controller_MSEventFacade::getInstance()->lookupExistingEvent($_iMIP->getEvent());
        
        if (! $exitingEvent) {
            // no autoprocessing for events not in our backend yet
            return;
        }
        
        // update existing event details _WITHOUT_ status updates
        $this->_process($_iMIP, NULL, $exitingEvent);
        
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
        $this->_process($_iMIP, $exitingEvent, $_action);
    }
    
    /**
     * process iMIP component and optionally set status
     * 
     * @param  Calendar_Model_iMIP   $_iMIP
     * @param  string                $_status
     */
    protected function _process($iMIP, $_status = NULL, $_existingEvent)
    {
    
        switch ($_iMIP->method) {
                case Calendar_Model_iMIP::METHOD_PUBLISH:
                    // add/update event (if outdated) / no status stuff / DANGER of duplicate UIDs
                    // no notifications!
                    break;
                    
                case Calendar_Model_iMIP::METHOD_REQUEST:
                    // organizer requests/updates event
                    // internal organizer:
                    //  - event is up to date
                    //  - status change could also be done by calendar method
                    //  - normal notifications
                    // external organizer:
                    //  - update (might have acl problems)
                    //  - set status
                    //  - send reply to organizer
                    $this->_applyUpdate($_iMIP, $exitingEvent);
                    break;
                    
                case Calendar_Model_iMIP::METHOD_REPLY:
                    // always internal organizer
                    // some attender replied to my request (I'm Organizer) -> update status (seq++) / send notifications!
                    break;
                    
                case Calendar_Model_iMIP::METHOD_ADD:
                    // organizer added a meeting/recurrance to an existing event -> update event
                    // internal organizer:
                    //  - event is up to date nothing to do
                    // external organizer:
                    //  - update event
                    //  - the iMIP is already the notification mail!
                    break;
                    
                case Calendar_Model_iMIP::METHOD_CANCEL:
                    // organizer caneled meeting/recurrence of an existing event -> update event
                    // the iMIP is already the notification mail!
                    break;
                    
                case Calendar_Model_iMIP::METHOD_REFRESH:
                    // always internal organizer
                    //  - send message
                    //  - mark iMIP message ANSWERED
                    break;
                    
                case Calendar_Model_iMIP::METHOD_COUNTER:
                    // some attendee suggests to change the event
                    // status: ACCEPT => update event, send notifications to all
                    // status: DECLINE => send DECLINECOUNTER to originator
                    // mark message ANSWERED
                    
                    
                case Calendar_Model_iMIP::METHOD_DECLINECOUNTER:
                    // organizer declined my counter request of an existing event -> update event
                    break;
                default:
                    throw new Tinebase_Exception_UnexpectedValue("method {$_iMIP->method} not supported");
                    break;
            }
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
    protected function _apply()
    {
        //isObsoletedBy
    }
}