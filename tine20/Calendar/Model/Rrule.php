<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


class Calendar_Model_Rrule extends Tinebase_Record_Abstract
{
    /**
     * supported freq types
     */
    const FREQ_DAILY     = 'DAILY';
    const FREQ_WEEKLY    = 'WEEKLY';
    const FREQ_MONTHLY   = 'MONTHLY';
    const FREQ_YEARLY    = 'YEARLY';

    /**
     * weekdays
     */
    const WDAY_SUNDAY    = 'SU';
    const WDAY_MONDAY    = 'MO';
    const WDAY_TUESDAY   = 'TU';
    const WDAY_WEDNESDAY = 'WE';
    const WDAY_THURSDAY  = 'TH';
    const WDAY_FRIDAY    = 'FR';
    const WDAY_SATURDAY  = 'SA';
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Calendar';
    
    /**
     * validators
     *
     * @var array
     */
    protected $_validators = array(
        // tine record fields
        'id'                   => array('allowEmpty' => true,  'Alnum'),
        'created_by'           => array('allowEmpty' => true,  'Int'  ),
        'creation_time'        => array('allowEmpty' => true          ),
        'last_modified_by'     => array('allowEmpty' => true          ),
        'last_modified_time'   => array('allowEmpty' => true          ),
        'is_deleted'           => array('allowEmpty' => true          ),
        'deleted_time'         => array('allowEmpty' => true          ),
        'deleted_by'           => array('allowEmpty' => true          ),
        'seq'                  => array('allowEmpty' => true,  'Int'  ),
    
        'event_id'             => array('allowEmpty' => true,  'Alnum'),
    
        'freq'                 => array('allowEmpty' => true, 'InArray' => array(self::FREQ_DAILY, self::FREQ_MONTHLY, self::FREQ_WEEKLY, self::FREQ_YEARLY)),
        'interval'             => array('allowEmpty' => true, 'Int'   ),
        'byday'                => array('allowEmpty' => true, 'Regex' => '/^[\-0-9A_Z,]{2,}$/'),
        'bymonth'              => array('allowEmpty' => true, 'Int'   ),
        'wkst'                 => array('allowEmpty' => true, 'InArray' => array(self::WDAY_SUNDAY, self::WDAY_MONDAY, self::WDAY_TUESDAY, self::WDAY_WEDNESDAY, self::WDAY_THURSDAY, self::WDAY_FRIDAY, self::WDAY_SATURDAY)),
        'until'                => array('allowEmpty' => true          ),
        
        'organizer_tz'          => array('allowEmpty' => true         ),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'creation_time', 
        'last_modified_time', 
        'deleted_time', 
        'until',
    );
    
    /**
     * @var array supported rrule parts
     */
    protected $_rruleParts = array('freq', 'interval', 'until', 'wkst', 'byday', 'bymonth');
    
    /**
     * set from ical rrule string
     *
     * @param string $_rrule
     */
    public function setFromString($_rrule)
    {
        $parts = explode(';', $_rrule);
        foreach ($parts as $part) {
            list($key, $value) = explode('=', $part);
            $part = strtolower($key);
            if (! in_array($part, $this->_rruleParts)) {
                throw new Tinebase_Exception_UnexpectedValue("$part is not a known rrule part");
            }
            $this->$part = $value;
        }
    }
    
    /**
     * returns a ical rrule string
     *
     * @return string
     */
    public function __toString()
    {
        $stringParts = array();
        
        foreach ($this->_rruleParts as $part) {
            if (!empty($this->$part)) {
                $value = $this->$part instanceof Zend_Date ? $this->$part->toString(self::ISO8601LONG) : $this->$part;
                $stringParts[] = strtoupper($part) . '=' . $value;
            }
        }
        
        return implode(';', $stringParts);
    }
    
    /**
     * set properties and convert them into internal representatin on the fly
     *
     * @param string $_name
     * @param mixed $_value
     * @return void
     */
    public function __set($_name, $_value) {
        switch ($_name) {
            case 'until':
                if (! empty($_value)) {
                    if ($_value instanceof Zend_Date) {
                        $this->_properties['until'] = $_value;
                    } else {
                        $this->_properties['until'] = new Zend_Date($_value, self::ISO8601LONG);
                    }
                }
                break;
            default:
                parent::__set($_name, $_value);
                break;
        }
    }
}