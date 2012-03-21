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
 * @property    id               message <id>_<part> of iMIP mail part
 * @property    ics              ical string in UTF8
 * @property    event            iMIP message event
 * @property    method           method of iMIP transaction
 * @property    userAgent        userAgent origination iMIP transaction
 * @property    originator       originator /sender of iMIP transaction
 * @property    preconditions     array of checked processing preconditions
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
     * precondition that iMIP message is not already processed
     */
    const PRECONDITION_TOPROCESS = 'TOPROCESS';
    
    /**
     * precondition that event has an organizer
     */
    const PRECONDITION_ORGANIZER  = 'ORGANIZER';
    
    /**
     * precondition that method is supported
     */
    const PRECONDITION_SUPPORTED  = 'SUPPORTED';
    
    /**
     * precondition that event exists
     */
    const PRECONDITION_EVENTEXISTS  = 'EVENTEXISTS';
    
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
        'method'               => array('allowEmpty' => true,         ),
        'originator'           => array('allowEmpty' => false,        ), // email adddress
        'userAgent'            => array('allowEmpty' => true,         ),
        'event'                => array('allowEmpty' => true          ),
        'existing_event'       => array('allowEmpty' => true          ),
        'preconditions'        => array('allowEmpty' => true          ),
        'preconditionsChecked' => array('allowEmpty' => true          ),
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
     * (non-PHPdoc)
     * @see Tinebase_Record_Abstract::__get()
     */
    public function __get($_name) {
        if ($_name == 'method' && !$this->_properties['method'] && $this->_properties['ics']) {
            $this->getEvent();
        }
        
        return parent::__get($_name);
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
            
            if (! $this->_properties['method']) {
                $this->method = $this->_getConverter()->getMethod($this->ics);
            }
        }
        
        return $this->event;
    }

    /**
    * get existing event record
    *
    * @param boolean $_refetch the event
    * @return Calendar_Model_Event
    */
    public function getExistingEvent($_refetch = FALSE)
    {
        if ($_refetch || ! $this->existing_event instanceof Calendar_Model_Event) {
            $this->existing_event = Calendar_Controller_MSEventFacade::getInstance()->lookupExistingEvent($this->getEvent());
        }
        
        return $this->existing_event;
    }
    
    /**
     * merge ics data into given event
     * 
     * @param Calendar_Model_Event $_event
     */
    public function mergeEvent($_event)
    {
        return $this->_getConverter()->toTine20Model($this->ics, $_event);
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
    
    /**
     * add failed precondtion check
     * 
     * @param string $_preconditionName
     * @param string $_message
     */
    public function addFailedPrecondition($_preconditionName, $_message)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . " Preconditions check failed for " . $_preconditionName . ' with message: ' . $_message);
        
        $this->_addPrecondition($_preconditionName, FALSE, $_message);
    }
    
    /**
     * add failed precondtion check
     * 
     * @param string $_preconditionName
     * @param string $_message
     */
    public function addSuccessfulPrecondition($_preconditionName)
    {
        $this->_addPrecondition($_preconditionName, TRUE);
    }
    
    /**
     * add precondition
     * 
     * @param string $_preconditionName
     * @param boolean $_check
     * @param string $_message
     */
    protected function _addPrecondition($_preconditionName, $_check, $_message = NULL)
    {
        $preconditions = (is_array($this->preconditions)) ? $this->preconditions : array();
        
        if (! isset($preconditions[$_preconditionName])) {
            $preconditions[$_preconditionName] = array();
        }
        
        $preconditions[$_preconditionName][] = array(
            'check'     => $_check,
            'message'    => $_message,
        );
        
        $this->preconditions = $preconditions;
    }
}
