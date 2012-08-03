<?php
/**
 * TimeZoneConvert
 *
 * @package     TimeZoneConvert
 * @license     MIT, BSD, and GPL
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

/**
 * abstract model
 * 
 * all public properties are treaded as the entries of this model
 */
abstract class TimeZoneConvert_Model implements ArrayAccess
{
    /**
     * construct new transition rule from data
     * 
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->append($data);
    }
    
    /**
     * attempt to set an undeclared property
     * 
     * @param  string $property
     * @param  mixed $value
     * @throws TimeZoneConvert_Exception
     */
    public function __set($property, $value)
    {
        throw new TimeZoneConvert_Exception("no such property $property");
    }
    
    /**
     * attempt to get an undeclared property
     * 
     * @param  string $property
     * @param  mixed $value
     * @throws TimeZoneConvert_Exception
     */
    public function __get($property)
    {
        throw new TimeZoneConvert_Exception("no such property $property");
    }
    
    /**
     * append data from given array
     * 
     * @param  array $data
     * @return $this
     */
    public function append(array $data = array())
    {
        foreach($data as $property => $value) {
            $this->{$property} = $value;
        }
    }
    
    /**
     * get array of data from this object
     * 
     * @return array
     */
    public function toArray()
    {
        $array = array();
        foreach($this as $prop => $value) {
            $array[$prop] = $value;
        }
        
        return $array;
    }
    
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists ($offset)
    {
        return property_exists($this, $offset);
    }
    
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet ($offset)
    {
        return $this->$offset;
    }
    
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet ($offset, $value)
    {
        $this->$offset = $value;
    }
    
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset ($offset)
    {
        $this->$offset = null;
    }
}