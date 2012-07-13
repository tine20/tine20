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
 * represents a single transition
 *
 */
class TimeZoneConvert_Transition extends ArrayObject
{
    protected static $properties = array(
        'ts',
        'date',
        'offset',
        'isdst',
        'abbr',
    );
    
    public function __construct(array $array=array())
    {
        $data = array_flip(self::$properties);
        foreach($data as $key => $val) {
            $data[$key] = isset($data[$key]) ? $data[$key] : NULL;
        }
        
        parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
    }
    
    /**
     * check if given transition equals this one
     * 
     * @param  array $transition
     * @return bool
     */
    public function equals(array $transition)
    {
        
    }
    
    public static function getProperties()
    {
        return self::$properties;
    }
}