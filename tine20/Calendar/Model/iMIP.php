<?php
/**
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of an iMIP (RFC 6047) Message
 * 
 * @property    id           message <id>_<part> of iMIP mail part
 * @property    ics          ical string in UTF8
 * @property    event        iMIP message event
 * @property    method       method of iMIP transaction
 * @property    userAgent    userAgent origination iMIP transaction
 * @property    originator   originator /sender of iMIP transaction
 * @package     Calendar
 * @subpackage  Model
 */
class Calendar_Model_iMIP extends Tinebase_Record_Abstract
{
    /**
     * Used to publish an iCalendar object to one or more "Calendar Users".  
     * There is no interactivity between the publisher and any  other 
     * "Calendar User".
     */
    const METHOD_PUBLISH        = 'PUBLISH';
    
    /**
     * Used to schedule an iCalendar object with other "Calendar Users".  
     * Requests are interactive in that they require the receiver to 
     * respond using the reply methods.  Meeting requests, busy-time 
     * requests, and the assignment of tasks to other "Calendar Users" 
     * are all examples.  Requests are also used by the Organizer to 
     * update the status of an iCalendar object. 
     */
    const METHOD_REQUEST        = 'REQUEST';
    
    /**
     * A reply is used in response to a request to convey Attendee 
     * status to the Organizer. Replies are commonly used to respond 
     * to meeting and task requests. 
     */
    const METHOD_REPLY          = 'REPLY';
    
    /**
     * Add one or more new instances to an existing recurring iCalendar object.
     */
    const METHOD_ADD            = 'ADD';
    
    /**
     * Cancel one or more instances of an existing iCalendar object.
     */
    const METHOD_CANCEL         = 'CANCEL';
    
    /**
     * Used by an Attendee to request the latest version of an iCalendar object.
     */
    const METHOD_REFRESH        = 'REFRESH';
    
    /**
     * Used by an Attendee to negotiate a change in an iCalendar object.
     * Examples include the request to change a proposed event time or 
     * change the due date for a task.
     */
    const METHOD_COUNTER        = 'COUNTER';
    
    /**
     * Used by the Organizer to decline the proposedcounter proposal
     */
    const METHOD_DECLINECOUNTER = 'DECLINECOUNTER';
    
    /**
     * precondition that originator of iMIP is also:
     * 
     * organizer for PUBLISH/REQUEST/ADD/CANCEL/DECLINECOUNTER
     * attendee  for REPLY/REFRESH/COUNTER
     */
    const PRECONDITION_ORIGINATOR = 'ORIGINATOR';
    
    /**
     * precondition iMIP message is more recent than event stored in calendar backend
     */
    const PRECONDITION_RECENT     = 'RECENT';
    
    /**
     * precondition that current user is event attendee
     * 
     * for REQUEST/DECLINECOUNTER
     */
    const PRECONDITION_ATTENDEE   = 'ATTENDEE';
    
    /**
     * precondition that event has an organizer
     */
    const PRECONDITION_ORGANIZER  = 'ORGANIZER';
    
    /**
     * precondition that method is supported
     */
    const PRECONDITION_SUPPORTED  = 'SUPPORTED';
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Record_Abstract::_identifier
     */
    protected $_identifier = 'id';
    
    /**
     * @var Calendar_Convert_Event_VCalendar_Abstract
     */
    protected $_converter = NULL;
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Record_Abstract::_validators
     */
    protected $_validators = array(
        'id'                   => array('allowEmpty' => true,         ), 
        'ics'                  => array('allowEmpty' => true          ),
        'method'               => array('allowEmpty' => false,        ),
        'originator'           => array('allowEmpty' => false,        ), // email adddress
        'userAgent'            => array('allowEmpty' => true,         ),
        'event'                => array('allowEmpty' => true          ),
        'preconditions'        => array('allowEmpty' => true          ),
    );
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Record_Abstract::__set()
     */
    public function __set($_name, $_value)
    {
        if ($_name == 'ics') unset($this->event);
        if ($_name == 'method') $_value = trim(strtoupper($_value));
        
        return parent::__set($_name, $_value);
    }
    
    /**
     * get event record
     * 
     * @return Calendar_Model_Event
     */
    public function getEvent()
    {
        if (! $this->event instanceof Calendar_Model_Event) {
            if (! $this->ics) {
                throw new Tinebase_Exception_Record_NotDefined('ics is needed to generate event');
            }
            
            $this->event = $this->_getConverter()->toTine20Model($this->ics);
        }
        
        return $this->event;
    }
    
    /**
     * get ics
     * 
     * @return string UTF8 ics
     */
    public function getIcs()
    {
        if (! $this->event instanceof Calendar_Model_Event) {
            throw new Tinebase_Exception_Record_NotDefined('event is needed to generate ics');
        }
        
        return $this->_getConverter()->fromTine20Model($this->event);
    }
    
    /**
     * get ics converter
     * 
     * @return Calendar_Convert_Event_VCalendar_Abstract
     */
    protected function _getConverter()
    {
        if (! $this->_converter) {
            list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($this->userAgent);
            $this->_converter = Calendar_Convert_Event_VCalendar_Factory::factory($backend, $version);
        }
        
        return $this->_converter;
    }
    
}