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
    }

    /**
     * Turns the object back into a serialized blob. 
     * 
     * @return string 
     */
    public function serialize()
    {
        return TimeZoneConvert::toVTimeZone($this->timezone);
    }
}
