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

class Syncroton_Model_EventAttendee extends Syncroton_Model_AEntry
{
    /**
     * attendee status
     */
    const ATTENDEE_STATUS_UNKNOWN       = 0;
    const ATTENDEE_STATUS_TENTATIVE     = 2;
    const ATTENDEE_STATUS_ACCEPTED      = 3;
    const ATTENDEE_STATUS_DECLINED      = 4;
    const ATTENDEE_STATUS_NOTRESPONDED  = 5;
    
    /**
     * attendee types
     */
    const ATTENDEE_TYPE_REQUIRED = 1;
    const ATTENDEE_TYPE_OPTIONAL = 2;
    const ATTENDEE_TYPE_RESOURCE = 3;
    
    // @todo handle body
    protected $_properties = array(
        'Calendar' => array(
            'AttendeeStatus'          => array('type' => 'number'),
            'AttendeeType'            => array('type' => 'number'),
            'Email'                   => array('type' => 'string'),
            'Name'                    => array('type' => 'string'),
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
                case 'Categories':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    foreach($value as $category) {
                        $categoryElement = $_domParrent->ownerDocument->createElementNS($nameSpace, 'Category');
                        $categoryElement->appendChild($_domParrent->ownerDocument->createTextNode($category));
                        
                        $element->appendChild($categoryElement);
                    }
                    
                    $_domParrent->appendChild($element);
                    
                    break;

                case 'Children':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    foreach($value as $child) {
                        $childElement = $_domParrent->ownerDocument->createElementNS($nameSpace, 'Child');
                        $childElement->appendChild($_domParrent->ownerDocument->createTextNode($child));
                        
                        $element->appendChild($childElement);
                    }
                    
                    $_domParrent->appendChild($element);
                    
                    break;

                case 'Picture':
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    if (is_resource($value)) {
                        stream_filter_append($value, 'convert.base64-encode');
                        $element->appendChild($_domParrent->ownerDocument->createTextNode(stream_get_contents($value)));
                    } else {
                        $element->appendChild($_domParrent->ownerDocument->createTextNode(base64_encode($value)));
                    }
                    
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
        if ($properties->getName() !== 'Attendee') {
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