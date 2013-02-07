<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * abstract class to handle ActiveSync entry
 *
 * @package     Syncroton
 * @subpackage  Model
 */

abstract class Syncroton_Model_AEntry implements Syncroton_Model_IEntry, IteratorAggregate, Countable
{
    protected $_elements = array();
    
    protected $_isDirty;
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Model_IEntry::__construct()
     */
    public function __construct($properties = null)
    {
        if (is_array($properties)) {
            $this->setFromArray($properties);
        }
        
        $this->_isDirty = false;
    }
    
    
    /**
     * (non-PHPdoc)
     * @see Countable::count()
     */    
    public function count()
    {
        return count($this->_elements);
    }
    
    /**
     * (non-PHPdoc)
     * @see IteratorAggregate::getIterator()
     */
    public function getIterator() 
    {
        return new ArrayIterator($this->_elements);
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Model_IEntry::isDirty()
     */
    public function isDirty()
    {
        return $this->_isDirty;
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Model_IEntry::setFromArray()
     */
    public function setFromArray(array $properties)
    {
        foreach($properties as $key => $value) {
            try {
                $this->$key = $value; //echo __LINE__ . PHP_EOL;
            } catch (InvalidArgumentException $iae) {
                //ignore invalid properties
                //echo __LINE__ . PHP_EOL; echo $iae->getMessage(); echo $iae->getTraceAsString();
            }
        }
    }
        
    public function &__get($name)
    {
        return $this->_elements[$name];
    }
    
    public function __set($name, $value)
    {
        if (!array_key_exists($name, $this->_elements) || $this->_elements[$name] != $value) {
            $this->_elements[$name] = $value;
            
            $this->_isDirty = true;
        }
    }
    
    public function __isset($name)
    {
        return isset($this->_elements[$name]);
    }
    
    public function __unset($name)
    {
        unset($this->_elements[$name]);
    }
}