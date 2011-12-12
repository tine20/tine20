<?php

/**
 * VTimezone Component
 *
 * This class represents a VTIMEZONE component. 
 *
 * @package    Sabre
 * @subpackage VObject
 * @copyright  Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Component_VTimezone extends Sabre_VObject_Component {

    /**
     * Name, for example VEVENT 
     * 
     * @var string 
     */
    public $name = 'VTIMEZONE';

    /**
     * @var DateTimeZone
     */
    public $timezone;
    
    /**
     * Creates a new component.
     *
     * By default this object will iterate over its own children, but this can 
     * be overridden with the iterator argument
     * 
     * @param string|DateTimeZone $timezone
     * @param Sabre_VObject_ElementList $iterator
     */
    public function __construct($timezone, Sabre_VObject_ElementList $iterator = null) {

        if (!is_null($iterator)) $this->iterator = $iterator;
        
        $this->setTimezone($timezone);
    }
    
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);
        
        $this->TZID = $this->timezone->getName();
        
        list($standardTransition, $daylightTransition) = $transitions = $this->_getTransitionsForTimezoneAndYear($this->timezone, date('Y'));
        
        $dtstart = new Sabre_VObject_Property_DateTime('DTSTART');
        $dtstart->setDateTime(new DateTime(), Sabre_VObject_Element_DateTime::LOCAL);
        
        if ($daylightTransition !== null) {
            $offsetTo   = ($daylightTransition['offset'] < 0 ? '-' : '+') . strftime('%H%M', abs($daylightTransition['offset']));
            $offsetFrom = ($standardTransition['offset'] < 0 ? '-' : '+') . strftime('%H%M', abs($standardTransition['offset']));
        
            $daylight  = new Sabre_VObject_Component('DAYLIGHT');
            $daylight->TZOFFSETFROM = $offsetFrom;
            $daylight->TZOFFSETTO   = $offsetTo;
            $daylight->TZNAME       = $daylightTransition['abbr'];
            $daylight->DTSTART      = $dtstart;
            #$daylight->RRULE       = 'FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3';
        
            $this->add($daylight);
        }
        
        if ($standardTransition !== null) {
            $offsetTo   = ($standardTransition['offset'] < 0 ? '-' : '+') . strftime('%H%M', abs($standardTransition['offset']));
            if ($daylightTransition !== null) {
                $offsetFrom = ($daylightTransition['offset'] < 0 ? '-' : '+') . strftime('%H%M', abs($daylightTransition['offset']));
            } else {
                $offsetFrom = $offsetTo;
            }
        
            $standard  = new Sabre_VObject_Component('STANDARD');
            $standard->TZOFFSETFROM  = $offsetFrom;
            $standard->TZOFFSETTO    = $offsetTo;
            $standard->TZNAME        = $standardTransition['abbr'];
            $standard->DTSTART       = $dtstart;
            #$standard->RRULE         = 'FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10';
        
            $this->add($standard);
        }
    }

    /**
     * Turns the object back into a serialized blob. 
     * 
     * @return string 
     */
    public function serialize() {

        $str = "BEGIN:" . $this->name . "\r\n";
        foreach($this->children as $child) $str.=$child->serialize();
        $str.= "END:" . $this->name . "\r\n";
        
        return $str;
    }
    
    /**
     * Returns the standard and daylight transitions for the given {@param $_timezone}
     * and {@param $_year}.
     *
     * @param DateTimeZone $_timezone
     * @param $_year
     * @return Array
     */
    protected function _getTransitionsForTimezoneAndYear(DateTimeZone $_timezone, $_year)
    {
        $standardTransition = null;
        $daylightTransition = null;
    
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            // Since php version 5.3.0 getTransitions accepts optional start and end parameters.
            $start = mktime(0, 0, 0, 12, 1, $_year - 1);
            $end   = mktime(24, 0, 0, 12, 31, $_year);
            $transitions = $_timezone->getTransitions($start, $end);
        } else {
            $transitions = $_timezone->getTransitions();
        }
    
        $index = 0;            //we need to access index counter outside of the foreach loop
        $transition = array(); //we need to access the transition counter outside of the foreach loop
        foreach ($transitions as $index => $transition) {
            if (strftime('%Y', $transition['ts']) == $_year) {
                if (isset($transitions[$index+1]) && strftime('%Y', $transitions[$index]['ts']) == strftime('%Y', $transitions[$index+1]['ts'])) {
                    $daylightTransition = $transition['isdst'] ? $transition : $transitions[$index+1];
                    $standardTransition = $transition['isdst'] ? $transitions[$index+1] : $transition;
                } else {
                    $daylightTransition = $transition['isdst'] ? $transition : null;
                    $standardTransition = $transition['isdst'] ? null : $transition;
                }
                break;
            } elseif ($index == count($transitions) -1) {
                $standardTransition = $transition;
            }
        }
         
        return array($standardTransition, $daylightTransition);
    }
}
