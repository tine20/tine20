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
 * @property    int       CalendarType
 * @property    int       DayOfMonth
 * @property    int       DayOfWeek
 * @property    int       FirstDayOfWeek
 * @property    int       Interval
 * @property    int       IsLeapMonth
 * @property    int       MonthOfYear
 * @property    int       Occurrences
 * @property    int       Type
 * @property    DateTime  Until
 * @property    int       WeekOfMonth
 */

class Syncroton_Model_EventRecurrence extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'Recurrence';
    
    /**
     * recur types
     */
    const TYPE_DAILY          = 0;     // Recurs daily.
    const TYPE_WEEKLY         = 1;     // Recurs weekly
    const TYPE_MONTHLY        = 2;     // Recurs monthly
    const TYPE_MONTHLY_DAYN   = 3;     // Recurs monthly on the nth day
    const TYPE_YEARLY         = 5;     // Recurs yearly
    const TYPE_YEARLY_DAYN    = 6;     // Recurs yearly on the nth day
    
    /**
     * day of week constants
     */
    const RECUR_DOW_SUNDAY      = 1;
    const RECUR_DOW_MONDAY      = 2;
    const RECUR_DOW_TUESDAY     = 4;
    const RECUR_DOW_WEDNESDAY   = 8;
    const RECUR_DOW_THURSDAY    = 16;
    const RECUR_DOW_FRIDAY      = 32;
    const RECUR_DOW_SATURDAY    = 64;
        
    // @todo handle body
    protected $_properties = array(
        'Calendar' => array(
            'CalendarType'            => array('type' => 'number'),
            'DayOfMonth'              => array('type' => 'number'),
            'DayOfWeek'               => array('type' => 'number'),
            'FirstDayOfWeek'          => array('type' => 'number'),
            'Interval'                => array('type' => 'number'),
            'IsLeapMonth'             => array('type' => 'number'),
            'MonthOfYear'             => array('type' => 'number'),
            'Occurrences'             => array('type' => 'number'),
            'Type'                    => array('type' => 'number'),
            'Until'                   => array('type' => 'datetime'),
            'WeekOfMonth'             => array('type' => 'number'),
        )
    );
    
    public function appendXML(DOMElement $_domParrent)
    {
        $this->_addXMLNamespaces($_domParrent);
        
        foreach($this->_elements as $elementName => $value) {
            // skip empty values
            if($value === null || $value === '' || (is_array($value) && empty($value))) {
                continue;
            }
            
            list ($nameSpace, $elementProperties) = $this->_getElementProperties($elementName);
            
            $nameSpace = 'uri:' . $nameSpace;
            
            // strip off any non printable control characters
            if (!ctype_print($value)) {
                #$value = $this->removeControlChars($value);
            }
            
            switch($elementName) {
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
}