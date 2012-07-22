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

class Syncroton_Model_CalendarEvent extends Syncroton_Model_AEntry
{
    /**
     * busy status constants
     */
    const BUSY_STATUS_FREE      = 0;
    const BUSY_STATUS_TENATTIVE = 1;
    const BUSY_STATUS_BUSY      = 2;
    
    protected $_xmlBaseElement = 'ApplicationData';
    
    // @todo handle body
    protected $_properties = array(
        'Calendar' => array(
            'AllDayEvent'             => array('type' => 'number'),
            'AppointmentReplyTime'    => array('type' => 'datetime'),
            'Attendees'               => array('type' => 'container'),
            //'Body'                    => 0x0b,
            //'BodyTruncated'           => 0x0c,
            'BusyStatus'              => array('type' => 'number'),
            'Categories'              => array('type' => 'container'),
            'DisallowNewTimeProposal' => array('type' => 'number'),
            'DtStamp'                 => array('type' => 'datetime'),
            'EndTime'                 => array('type' => 'datetime'),
            'Exceptions'              => array('type' => 'container'),
            'Location'                => array('type' => 'string'),
            'MeetingStatus'           => array('type' => 'number'),
            'OnlineMeetingConfLink'   => array('type' => 'string'),
            'OnlineMeetingExternalLink' => array('type' => 'string'),
            'OrganizerEmail'          => array('type' => 'string'),
            'OrganizerName'           => array('type' => 'string'),
            'Recurrence'              => array('type' => 'container'),
            'Reminder'                => array('type' => 'number'),
            'ResponseRequested'       => array('type' => 'number'),
            'ResponseType'            => array('type' => 'number'),
            //'Rtf'                     => 0x10,
            'Sensitivity'             => array('type' => 'number'),
            'StartTime'               => array('type' => 'datetime'),
            'Subject'                 => array('type' => 'string'),
            'Timezone'                => array('type' => 'timezone'),
            'UID'                     => array('type' => 'string'),
        )
    );
    
    public function appendXML(DOMElement $_domParrent)
    {
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Calendar', 'uri:Calendar');
        
        foreach($this->_elements as $elementName => $value) {
            // skip empty values
            if($value === null || $value == '' || (is_array($value) && empty($value))) {
                continue;
            }
            
            $elementProperties = $this->_properties['Calendar'][$elementName]; 
            
            $nameSpace = 'uri:Calendar';
            
            // strip off any non printable control characters
            if (!ctype_print($value)) {
                #$value = $this->removeControlChars($value);
            }
            
            switch($elementName) {
                case 'Attendees':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    foreach ($value as $attendee) {
                        $attendeeElement = $_domParrent->ownerDocument->createElementNS($nameSpace, 'Attendee');
                        $attendee->appendXML($attendeeElement);
                        $element->appendChild($attendeeElement);
                    }
                    
                    $_domParrent->appendChild($element);
                    
                    break;
                    
                case 'Categories':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    foreach($value as $category) {
                        $categoryElement = $_domParrent->ownerDocument->createElementNS($nameSpace, 'Category');
                        $categoryElement->appendChild($_domParrent->ownerDocument->createTextNode($category));
                        
                        $element->appendChild($categoryElement);
                    }
                    
                    $_domParrent->appendChild($element);
                    
                    break;

                case 'Exceptions':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    foreach ($value as $exception) {
                        $exceptionElement = $_domParrent->ownerDocument->createElementNS($nameSpace, 'Exception');
                        $exception->appendXML($exceptionElement);
                        $element->appendChild($exceptionElement);
                    }
                    
                    $_domParrent->appendChild($element);
                                        
                    break;

                case 'Recurrence':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    $value->appendXML($element);
                    
                    $_domParrent->appendChild($element);
                    
                    break;
                    
                default:
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    if ($value instanceof DateTime) {
                        $value = $value->format("Ymd\THis\Z");
                    }
                    $element->appendChild($_domParrent->ownerDocument->createTextNode($value));
                    
                    $_domParrent->appendChild($element);
            }
        }
        
    }
    
    /**
     * 
     * @param SimpleXMLElement $xmlCollection
     * @throws InvalidArgumentException
     */
    public function setFromSimpleXMLElement(SimpleXMLElement $properties)
    {
        if ($properties->getName() !== $this->_xmlBaseElement) {
            throw new InvalidArgumentException('Unexpected element name: ' . $properties->getName());
        }
        
        $this->_elements = array();
        
        foreach (array_keys($this->_properties) as $namespace) {
            $functionName = '_parse' . $namespace . 'Namespace';
            $this->$functionName($properties);
        }
        
        $airSyncBaseData = $properties->children('uri:AirSyncBase');
        
        return;
    }
    
    protected function _parseCalendarNamespace(SimpleXMLElement $properties)
    {
        // fetch data from Contacts namespace
        $children = $properties->children('uri:Calendar');
    
        foreach ($children as $elementName => $xmlElement) {
    
            switch ($elementName) {
                case 'Attendees':
                    $attendees = array();
                    
                    foreach ($xmlElement->Attendee as $attendee) {
                        $attendees[] = new Syncroton_Model_CalendarAttendee($attendee);
                    }
                    
                    $this->$elementName = $attendees;
                    
                    break;
                    
                case 'Categories':
                    $categories = array();
                    
                    foreach ($xmlElement->$elementName as $category) {
                        $categories[] = (string) $category;
                    }
                    
                    $this->$elementName = $categories;
                    
                    break;
                    
                case 'Exceptions':
                    $exceptions = array();
                    
                    foreach ($xmlElement->Exception as $exception) {
                        $exceptions[] = new Syncroton_Model_CalendarException($exception);
                    }
                    
                    $this->$elementName = $exceptions;
                    
                    break;
                    
                case 'Recurrence':
                    $this->$elementName = new Syncroton_Model_CalendarRecurrence($xmlElement);
                    
                    break;
                    
                default:
                    $properties =  $this->_properties['Calendar'][$elementName];
                    
                    switch ($properties['type']) {
                        case 'datetime':
                            $this->$elementName = new DateTime((string) $xmlElement, new DateTimeZone('UTC'));
                            
                            break;
                            
                        case 'number':
                            $this->$elementName = (int) $xmlElement;
                            
                            break;
                        default:
                            $this->$elementName = (string) $xmlElement;
                            
                            break;
                    }
            }
        }
    }
    
    public function &__get($name)
    {
        if (!array_key_exists($name, $this->_properties['Calendar'])) {
            throw new InvalidArgumentException("$name is no valid property of this object");
        }
        
        return $this->_elements[$name];
    }
    
    public function __set($name, $value)
    {
        if (!array_key_exists($name, $this->_properties['Calendar'])) {
            throw new InvalidArgumentException("$name is no valid property of this object");
        }
        
        $properties = $this->_properties['Calendar'][$name];
        
        if ($properties['type'] == 'datetime' && !$value instanceof DateTime) {
            throw new InvalidArgumentException("value for $name must be an instance of DateTime");
        }
        
        $this->_elements[$name] = $value;
    }
}