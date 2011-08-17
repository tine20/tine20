<?php

/**
 * Zend_Config like access to array data
 */
class Tinebase_Config_Struct extends ArrayObject
{
    
    /**
     * Retrieve a value and return $default if there is no element set.
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function get($_name, $_default = null)
    {
        return array_key_exists($_name, $this) ? $this[$_name] : $_default;
    }
    
    /**
     * Return an associative array of the stored data.
     *
     * @return array
     */
    public function toArray()
    {
        $array = (array) $this;
        
        foreach($array as $key => $value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $array[$key] = $value->toArray();
            }
        }

        return $array;
    }
    
    /**
     * Magic function so that $obj->value will work.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($_name)
    {
        return $this->get($_name);
    }
    
    /**
     * Support isset() overloading on PHP 5.1
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this[$name]);
    }
}