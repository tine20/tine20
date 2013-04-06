<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync event
 *
 * @package     Model
 * @property    string  class
 * @property    string  collectionId
 * @property    bool    deletesAsMoves
 * @property    bool    getChanges
 * @property    string  syncKey
 * @property    int     windowSize
 */

class Syncroton_Model_Event extends Syncroton_Model_AXMLEntry
{
    /**
     * busy status constants
     */
    const BUSY_STATUS_FREE      = 0;
    const BUSY_STATUS_TENATTIVE = 1;
    const BUSY_STATUS_BUSY      = 2;
    
    protected $_dateTimeFormat = "Ymd\THis\Z";
    
    protected $_xmlBaseElement = 'ApplicationData';
    
    protected $_properties = array(
        'AirSyncBase' => array(
            'body'                      => array('type' => 'container', 'class' => 'Syncroton_Model_EmailBody')
        ),
        'Calendar' => array(
            'allDayEvent'               => array('type' => 'number'),
            'appointmentReplyTime'      => array('type' => 'datetime'),
            'attendees'                 => array('type' => 'container', 'childElement' => 'attendee', 'class' => 'Syncroton_Model_EventAttendee'),
            'busyStatus'                => array('type' => 'number'),
            'categories'                => array('type' => 'container', 'childElement' => 'category'),
            'disallowNewTimeProposal'   => array('type' => 'number'),
            'dtStamp'                   => array('type' => 'datetime'),
            'endTime'                   => array('type' => 'datetime'),
            'exceptions'                => array('type' => 'container', 'childElement' => 'exception', 'class' => 'Syncroton_Model_EventException'),
            'location'                  => array('type' => 'string'),
            'meetingStatus'             => array('type' => 'number'),
            'onlineMeetingConfLink'     => array('type' => 'string'),
            'onlineMeetingExternalLink' => array('type' => 'string'),
            'organizerEmail'            => array('type' => 'string'),
            'organizerName'             => array('type' => 'string'),
            'recurrence'                => array('type' => 'container'),
            'reminder'                  => array('type' => 'number'),
            'responseRequested'         => array('type' => 'number'),
            'responseType'              => array('type' => 'number'),
            'sensitivity'               => array('type' => 'number'),
            'startTime'                 => array('type' => 'datetime'),
            'subject'                   => array('type' => 'string'),
            'timezone'                  => array('type' => 'timezone'),
            'uID'                       => array('type' => 'string'),
        )
    );
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Model_IEntry::appendXML()
     * @todo handle Attendees element
     */
    public function appendXML(DOMElement $domParrent, Syncroton_Model_IDevice $device)
    {
        parent::appendXML($domParrent, $device);
        
        // remove all elements from event exceptions which have the same value as in the main event
        $xpath = new DomXPath($domParrent->ownerDocument);
        $xpath->registerNamespace('AirSync', 'uri:AirSync');
        $xpath->registerNamespace('Calendar', 'uri:Calendar');
        
        $exceptionElements = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Calendar:Exceptions/Calendar:Exception');
        
        if ($exceptionElements->length > 0) {
            $mainEventElement = $exceptionElements->item(0)->parentNode->parentNode;
            
            foreach ($mainEventElement->childNodes as $childNode) {

                $parentFields = array('AllDayEvent'/*, 'Attendees'*/, 'Body', 'BusyStatus'/*, 'Categories'*/, 'DtStamp', 'EndTime', 'Location', 'MeetingStatus', 'Reminder', 'ResponseType', 'Sensitivity', 'StartTime', 'Subject');
                
                if (in_array($childNode->localName, $parentFields)) {
                    
                    $elementsToLeftOut = $xpath->query('//AirSync:Sync/AirSync:ApplicationData/Calendar:Exceptions/Calendar:Exception/' . $childNode->nodeName);
                    
                    foreach ($elementsToLeftOut as $elementToLeftOut) {
                        if ($elementToLeftOut->nodeValue == $childNode->nodeValue) {
                            $elementToLeftOut->parentNode->removeChild($elementToLeftOut);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * some elements of an exception can be left out, if they have the same value 
     * like the main event
     * 
     * this function copies these elements to the exception for backends which need
     * this elements in the exceptions too. Tine 2.0 needs this for example.
     */
    public function copyFieldsFromParent()
    {
        if (isset($this->_elements['exceptions']) && is_array($this->_elements['exceptions'])) {
            foreach ($this->_elements['exceptions'] as $exception) {
                // no need to update deleted exceptions
                if ($exception->deleted == 1) {
                    continue;
                }
        
                $parentFields = array('allDayEvent', 'attendees', 'body', 'busyStatus', 'categories', 'dtStamp', 'endTime', 'location', 'meetingStatus', 'reminder', 'responseType', 'sensitivity', 'startTime', 'subject');
        
                foreach ($parentFields as $field) {
                    if (!isset($exception->$field) && isset($this->_elements[$field])) {
                        $exception->$field = $this->_elements[$field];
                    }
                }
            }
        }
    }
}