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
 * generic data set
 */
class TimeZoneConvert_Set implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * @var array
     */
    protected $_models = array();
    
    /**
     * create set from data
     * 
     * @param  iteratable $models
     * @return TimeZoneConvert_Set
     */
    public static function create($models=NULL)
    {
        return new self($models);
    }
    
    /**
     * construct set
     * 
     * @param  iteratable $models
     */
    public function __construct($models=NULL)
    {
        if ($models) {
            $this->addModels($models);
        }
    }
    
    /**
     * add multiple models to this set
     * 
     * @param  iteratable $models
     * @return this
     */
    public function addModels($models)
    {
        foreach($models as $model) {
            $this->addModel($model);
        }
        
        return $this;
    }
    
    /**
     * add a single model
     * 
     * @param mixed $model
     * @return this
     */
    public function addModel($model)
    {
        $this->_models[] = $model;
        return $this;
    }
    
    /**
     * get first model of this set
     * 
     * @return mixed
     */
    public function getFirst()
    {
        $keys = array_keys($this->_models);
        return $this->_models[$keys[0]];
    }
    
    /**
     * returns new filtered set
     * 
     * @param  string $property
     * @param  mixed  $value
     * @return TimeZoneConvert_Set
     */
    public function filter($property, $value)
    {
        $filteredSet = new self;
        foreach($this->_models as $offset => $model) {
            if ($model[$property] == $value) {
                $filteredSet->addModel($model);
            }
        }
        
        return $filteredSet;
    }
    
    public function sort($property, $direction='ASC')
    {
        $map = $this->{$property};
        $fn = $direction == 'ASC' ? 'asort' : 'arsort';
        $fn($map);
        
        $sortedModels = array();
        foreach($map as $key => $value) {
            $sortedModels[$key] = $this->_models[$key];
        }
        
        $this->_models = $sortedModels;
        
        return $this;
    }
    
    /**
     * get array of data from this object
     * 
     * @return array
     */
    public function toArray()
    {
        $array = $this->_models;
        foreach($array as $key => $value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $array[$key] = $value->toArray();
            }
        }
        
        return $array;
    }
    
    /**
     * get a property from all models
     * 
     * @param  mixed $property
     * @return mixed
     */
    public function __get($property)
    {
        $properties = array();
        foreach($this->_models as $key => $model) {
            $properties[$key] = $model[$property];
        }
        
        return $properties;
    }
    
    /**
     * set property in all models
     * 
     * @param  mixed $property
     * @param  mixed $value
     * @return this
     */
    public function __set($property, $value)
    {
        foreach($this->_models as $key => $model) {
            $this->_models[$key] = $value;
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists ($offset)
    {
        return array_key_exists($offset, $this->_models);
    }
    
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet ($offset)
    {
        return $this->_models[$offset];
    }
    
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet ($offset, $value)
    {
        $this->_models[$offset] = $value;
    }
    
    /**
     * (non-PHPdoc)
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset ($offset)
    {
        unset ($this->_models[$offset]);
    }
    
    /**
     * (non-PHPdoc)
     * @see IteratorAggregate::getIterator()
     */
    public function getIterator () {
        return new ArrayIterator($this->_models);
    }
    
    /**
     * (non-PHPdoc)
     * @see Countable::count()
     */
    public function count () {
        return count($this->_models);
    }
}