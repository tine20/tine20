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

/**
 * Model of an rrule
 *
 * @package Calendar
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
    
    const TS_HOUR = 3600;
    const TS_DAY  = 86400;
    
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
        /*
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
    
        'cal_event_id'         => array('allowEmpty' => true,  'Alnum'),
        */
    
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
        //'creation_time', 
        //'last_modified_time', 
        //'deleted_time', 
        'until',
    );
    
    /**
     * Computes the recurance set of the given event leaving out $_event->exdate and $_exceptions
     *
     * @param  Calendar_Model_Event         $_event
     * @param  Tinebase_Record_RecordSet    $_exceptions
     * @param  Zend_Date                    $_from
     * @param  Zend_Date                    $_until
     * @return Tinebase_Record_RecordSet
     */
    public static function computeRecuranceSet($_event, $_exceptions, $_from, $_until)
    {
        // get timestamps
        $fromTS     = $_from->getTimestamp();
        $untilTS    = $_until->getTimestamp();
        $dtstartTS  = $_event->dtstart->getTimestamp();
        $dtendTS    = $_event->dtend->getTimestamp();
        
        $completeRecurSet = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $rrule = new Calendar_Model_Rrule();
        $rrule->setFromString($_event->rrule);
        
        switch ($rrule->freq) {
            case self::FREQ_DAILY:
                $computationStartDateTS = $dtstartTS;
                $computationOffsetDays = 0;
                
                // if dtstart is before $_from, we compute the offset where to start our calculations
                if (($dtstartTS - $fromTS) < 0) {
                    $computationOffsetDays = floor(($fromTS - $dtstartTS) / (self::TS_DAY * $rrule->interval)) * $rrule->interval;
                }
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' $computationOffsetDays: ' . $computationOffsetDays);
                
                $computationSpan = $untilTS - $fromTS;
                $numOfRecuances = floor($computationSpan / (self::TS_DAY * $rrule->interval - 1));
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' $numOfRecuances: ' . $numOfRecuances);
                
                $recurEventClone = clone $_event;
                $recurEventClone->setId(NULL);
                unset($recurEventClone->exdate);
                unset($recurEventClone->rrule);
                unset($recurEventClone->rrule_until);
                
                $eventLength = clone $_event->dtend;
                $eventLength->sub($_event->dtstart);
                
                // create recur events
                for ($i=1; $i<$numOfRecuances+1; $i++) {
                    $recurEvent = clone $recurEventClone;
                    
                    $recurEvent->dtstart = new Zend_Date($dtstartTS + ($computationOffsetDays + $i * $rrule->interval) * self::TS_DAY, Zend_Date::TIMESTAMP);
                    $recurEvent->dtend   = new Zend_Date($dtendTS   + ($computationOffsetDays + $i * $rrule->interval) * self::TS_DAY, Zend_Date::TIMESTAMP);
                    
                    $originatorsDtstart = clone $recurEvent->dtstart;
                    $originatorsDtstart->setTimezone($_event->originator_tz);
                    $recurEvent->dtstart->sub($originatorsDtstart->get(Zend_Date::DAYLIGHT) ? 1 : 0, Zend_Date::HOUR);
                    
                    // we calculate dtend from the event length, as events during 
                    // a dst boundary could get dtend less than dtstart otherwise 
                    $recurEvent->dtend = clone $recurEvent->dtstart;
                    $recurEvent->dtend->add($eventLength);
                    
                    $completeRecurSet->addRecord($recurEvent);
                }
                break;
                
            case self::FREQ_WEEKLY:
                break;
            case self::FREQ_MONTHLY:
                break;
            case self::FREQ_YEARLY:
                break;
        }
        
        // filter out exdates and exceptions
        $finalRecurSet = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        foreach ($completeRecurSet as $recurEvent) {
            $reucurIdDtstart = clone $recurEvent->dtstart;
            $recurEvent->recurid = $recurEvent->uid . '-' . $recurEvent->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
            
            // todo check exceptions & exdate
            $finalRecurSet->addRecord($recurEvent);
        }
        
        return $finalRecurSet;
    }
    
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