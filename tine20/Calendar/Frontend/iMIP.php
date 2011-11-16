<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo		add interface or abstract for iMIP classes
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
     * user agent string
     * 
     * @var string
     */
    protected $_userAgentString = NULL;
    
    /**
     * constructor
     * 
     * @param string $_userAgentString
     */
    public function __construct($_userAgentString = NULL)
    {
        $this->_userAgentString = $_userAgentString;
    }
    
    /**
     * prepares iMIP component for client
     * 
     * @param  string $_icalString (UTF8)
     * @param  array $_parameters
     * @return Calendar_Model_iMIP prepared data
     */
    public function prepareComponent($_icalString, $_parameters)
    {
        if (! isset($_parameters['method'])) {
            throw new Tinebase_Exception_InvalidArgument('method missing in params');
        }
        
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($this->_userAgentString);
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory($backend, $version);

        // NOTE: this event is prepared for MSEventFacade (exceptions in exdate property)
        $event = $converter->toTine20Model($_icalString);
        
        $this->_checkExistingEvent($event);
        
        Calendar_Model_Attender::resolveAttendee($event->attendee);
        Tinebase_Model_Container::resolveContainer($event);
        
        return new Calendar_Model_iMIP(array(
            'event'     => $event,
            'method'    => $_parameters['method'],
            'userAgent' => $this->_userAgentString
        ));
    }
    
    /**
    * check for existing event
    *
    * @param Calendar_Model_Event $_event
    *
    * @todo get more data from existing event?
    */
    protected function _checkExistingEvent(Calendar_Model_Event $_event)
    {
        $existingEvent = Calendar_Controller_Event::getInstance()->lookupExistingEvent($_event);
        if ($existingEvent) {
            if ($existingEvent->seq < $_event->seq) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Invitation is newer than the existing event.');
    
                $_event->setId($existingEvent->getId());
    
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Invitation is older than or the same as the existing event.');
    
                $_event = $existingEvent;
            }
        }
    }
}
