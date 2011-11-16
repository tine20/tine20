<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * iMIP (RFC 6047) frontend for calendar
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_iMIP
{
    protected $_userAgentString = NULL;
    
    public function __construct($_userAgentString = NULL)
    {
        $this->_userAgentString = $_userAgentString;
    }
    
    /**
     * prepares iMIP component for client
     * 
     * @param  string $_method
     * @param  string $_icalString (UTF8)
     * @return Calendar_Model_iMIP prepared data
     */
    public function prepareComponent($_method, $_icalString)
    {
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($this->_userAgentString);
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory($backend, $version);

        // NOTE: this event is prepared for MSEventFacade (exceptions in exdate property)
        $event = $converter->toTine20Model($_icalString);
        
        Calendar_Model_Attender::resolveAttendee($event->attendee);
        Tinebase_Model_Container::resolveContainer($event->container_id);
        
        if (isset($event->container_id) && $event->container_id) {
            $event->container_id = $this->_resolveContainer($_record->invitation_event);
        }
        
        return new Calendar_Model_iMIP(array(
            'event'     => $event,
            'method'    => $_method,
            'userAgent' => $this->_userAgentString
        ));
    }
}