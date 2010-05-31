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
 * @todo move calculations to rrule controller
 * @todo move date helpers to Tinebase_DateHelpers
 * @todo rrule->until must be adopted to orginator tz for computations
 * @todo rrule models should  string-->model converted from backend and viceavice
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
    
    /**
     * maps weeksdays to digits
     */
    static $WEEKDAY_DIGIT_MAP = array(
        self::WDAY_SUNDAY     => 0,
        self::WDAY_MONDAY     => 1,
        self::WDAY_TUESDAY    => 2,
        self::WDAY_WEDNESDAY  => 3,
        self::WDAY_THURSDAY   => 4,
        self::WDAY_FRIDAY     => 5,
        self::WDAY_SATURDAY   => 6
    );
    
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
        'bymonthday'           => array('allowEmpty' => true, 'Int'   ),
        'wkst'                 => array('allowEmpty' => true, 'InArray' => array(self::WDAY_SUNDAY, self::WDAY_MONDAY, self::WDAY_TUESDAY, self::WDAY_WEDNESDAY, self::WDAY_THURSDAY, self::WDAY_FRIDAY, self::WDAY_SATURDAY)),
        'until'                => array('allowEmpty' => true          ),
        
        //'organizer_tz'          => array('allowEmpty' => true         ),
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
     * @var array supported rrule parts
     */
    protected $_rruleParts = array('freq', 'interval', 'until', 'wkst', 'byday', 'bymonth', 'bymonthday');
    
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
     * creates a rrule from string
     *
     * @param string $_rruleString
     * @return Calendar_Model_Rrule
     */
    public static function getRruleFromString($_rruleString)
    {
        $rrule = new Calendar_Model_Rrule();
        $rrule->setFromString($_rruleString);
        
        return $rrule;
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
    
    /**
     * gets record related properties
     * 
     * @param string _name of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @return mixed value of property
     */
    public function __get($_name)
    {
        $value = parent::__get($_name);
        
        switch ($_name) {
        	case 'interval':
        		return (int) $value > 1 ? (int) $value : 1;
        		break;
        	default:
        		return $value;
        		break;
        }
    }
    
    /************************* recurance computation *****************************/
    
    /**
     * merges recurances of given events into the given event set
     * 
     * @param  Tinebase_Record_RecordSet    $_events
     * @param  Zend_Date                    $_from
     * @param  Zend_Date                    $_until
     * @return void
     */
    public static function mergeRecuranceSet($_events, $_from, $_until)
    {
        //compute recurset
        $candidates = $_events->filter('rrule', "/^FREQ.*/", TRUE);
        
        foreach ($candidates as $candidate) {
            try {
                $exceptions = $_events->filter('recurid', "/^{$candidate->uid}-.*/", TRUE);
                
                $recurSet = Calendar_Model_Rrule::computeRecuranceSet($candidate, $exceptions, $_from, $_until);
                foreach ($recurSet as $event) {
                    $_events->addRecord($event);
                    $event->setId('fakeid' . $candidate->uid . $event->dtstart->getTimeStamp());
                }
            } catch (Exception $e) {
               if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " could not compute recurSet of event: {$candidate->getId()} ");
               continue;
            }
        }
    }
    
    /**
     * returns next occurrence _ignoring exceptions_ or NULL if there is none/not computable
     * 
     * NOTE: computing the next occurrence of an open end rrule can be dangoures, as it might result
     *       in a endless loop. Therefore we only make a limited number of attempts before giving up.
     * 
     * @param  Calendar_Model_Event         $_event
     * @param  Tinebase_Record_RecordSet    $_exceptions
     * @param  Zend_Date                    $_from
     * @return Calendar_Model_Event
     */
    public static function computeNextOccurrence($_event, $_exceptions, $_from)
    {
        $freqMap = array(
            self::FREQ_DAILY   => Zend_Date::DAY,
            self::FREQ_WEEKLY  => Zend_Date::WEEK,
            self::FREQ_MONTHLY => Zend_Date::MONTH,
            self::FREQ_YEARLY  => Zend_Date::YEAR
        );
        
        $rrule = new Calendar_Model_Rrule();
        $rrule->setFromString($_event->rrule);
        
        $from  = clone $_from;
        $until = clone $from;
        
        if ($_from->isEarlier($_event->dtstart)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' from is ealier dtstart -> given event is next occurrence');
            return $_event;
        }
        
        $until->add($rrule->interval, $freqMap[$rrule->freq]);
        $attempts = 0;
        while (TRUE) {
            if ($rrule->until instanceof Zend_Date && $from->isLater($rrule->until)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' passed rrule_until -> no furthor occurrences');
                return NULL;
            }
            
            $until   = ($rrule->until instanceof Zend_Date && $until->isLater($rrule->until)) ? $rrule->until : $until;
            $recurSet = self::computeRecuranceSet($_event, $_exceptions, $from, $until);
            $attempts++;
            
            if (count($recurSet) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found next occurrence after $attempts attempt(s)");
                break;
            }
            
            if ($attempts > count($_exceptions) + 5) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " could not find the next occurrence after $attempts attempts, giving up");
                return NULL;
            }
            
            $from->add($rrule->interval, $freqMap[$rrule->freq]);
            $until->add($rrule->interval, $freqMap[$rrule->freq]);
        }
        
        $recurSet->sort('dtstart', 'ASC');
        return $recurSet->getFirstRecord();
    }
    
    /**
     * Computes the recurance set of the given event leaving out $_event->exdate and $_exceptions
     * 
     * @todo respect rrule_until!
     *
     * @param  Calendar_Model_Event         $_event
     * @param  Tinebase_Record_RecordSet    $_exceptions
     * @param  Zend_Date                    $_from
     * @param  Zend_Date                    $_until
     * @return Tinebase_Record_RecordSet
     */
    public static function computeRecuranceSet($_event, $_exceptions, $_from, $_until)
    {
        $rrule = new Calendar_Model_Rrule();
        $rrule->setFromString($_event->rrule);
        
        $_exceptionRecurIds = self::getExceptionsRecurIds($_event, $_exceptions);
        $recurSet = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        switch ($rrule->freq) {
            case self::FREQ_DAILY:
                self::_computeRecurDaily($_event, $rrule, $_exceptionRecurIds, $_from, $_until, $recurSet);
                break;
                
            case self::FREQ_WEEKLY:
                $dailyrrule = clone ($rrule);
                $dailyrrule->freq = self::FREQ_DAILY;
                $dailyrrule->interval = 7 * $rrule->interval;
                
                $eventLength = clone $_event->dtend;
                $eventLength->sub($_event->dtstart);
                
                foreach (explode(',', $rrule->byday) as $recurWeekDay) {
                    $baseEvent = clone $_event;
                    
                    // NOTE: skipping must be done in organizer_tz
                    $baseEvent->dtstart->setTimezone($_event->originator_tz);
                    self::skipWday($baseEvent->dtstart, $recurWeekDay, 1, TRUE);
                    $baseEvent->dtstart->setTimezone('UTC');
                    
                    $baseEvent->dtend = clone($baseEvent->dtstart);
                    $baseEvent->dtend->add($eventLength);
                    
                    if ($baseEvent->dtstart->isLater($_event->dtstart) && $baseEvent->dtstart->isLater($_from) && $baseEvent->dtstart->isEarlier($_until)) {
                    	$baseEvent->recurid = $baseEvent->uid . '-' . $baseEvent->dtstart->toString(Tinebase_Record_Abstract::ISO8601LONG);
                    	if (! in_array($baseEvent->recurid, $_exceptionRecurIds)) {
                            $recurSet->addRecord($baseEvent);
                    	}
                    }
                    
                    self::_computeRecurDaily($baseEvent, $dailyrrule, $_exceptionRecurIds, $_from, $_until, $recurSet);
                }
                break;
                
            case self::FREQ_MONTHLY:
                if ($rrule->bymonthday) {
                    self::_computeRecurMonthlyByMonthDay($_event, $rrule, $_exceptionRecurIds, $_from, $_until, $recurSet);
                } else if ($rrule->byday) {
                    self::_computeRecurMonthlyByDay($_event, $rrule, $_exceptionRecurIds, $_from, $_until, $recurSet);
                    
                } else {
                    throw new Exception('mal formated rrule');
                }
                break;
                
            case self::FREQ_YEARLY:
                //$computitionEvent = clone $_event;
                //$computitionEvent->dtstart->setMonth($rrule->bymonth);
                
                $yearlyrrule = clone $rrule;
                $yearlyrrule->freq = self::FREQ_MONTHLY;
                $yearlyrrule->interval = 12;
                
                if ($rrule->byday) {
                	self::_computeRecurMonthlyByDay($_event, $yearlyrrule, $_exceptionRecurIds, $_from, $_until, $recurSet);
                } else {
                	$yearlyrrule->bymonthday = $yearlyrrule->bymonthday ? $yearlyrrule->bymonthday : $_event->dtstart->get(Zend_Date::DAY);
                	self::_computeRecurMonthlyByMonthDay($_event, $yearlyrrule, $_exceptionRecurIds, $_from, $_until, $recurSet);
                }

                break;
                
        }
        
        return $recurSet;
    }
    
    /**
     * returns array of exception recurids
     *
     * @param  Calendar_Model_Event         $_event
     * @param  Tinebase_Record_RecordSet    $_exceptions
     * @return array
     */
    public static function getExceptionsRecurIds($_event, $_exceptions)
    {
        $recurIds = $_exceptions->recurid;
        
        if (! empty($_event->exdate)) {
            $exdates = is_array($_event->exdate) ? $_event->exdate : array($_event->exdate);
            foreach ($exdates as $exdate) {
                $recurIds[] = $_event->uid . '-' . $exdate->toString(Tinebase_Record_Abstract::ISO8601LONG);
            }
        }
        return array_values($recurIds);
    }
    
    /**
     * gets an cloned event to be used for new recur events
     * 
     * @param  Calendar_Model_Event         $_event
     * @return Calendar_Model_Event         $_event
     */
    public static function cloneEvent($_event)
    {
        $clone = clone $_event;
        $clone->setId(NULL);
        //unset($clone->exdate);
        //unset($clone->rrule);
        //unset($clone->rrule_until);
        
        return $clone;
    }
    
    /**
     * computes daily recurring events and inserts them into given $_recurSet
     *
     * @param Calendar_Model_Event      $_event
     * @param Calendar_Model_Rrule      $_rrule
     * @param array                     $_exceptionRecurIds
     * @param Zend_Date                 $_from
     * @param Zend_Date                 $_until
     * @param Tinebase_Record_RecordSet $_recurSet
     * @return void
     */
    protected static function _computeRecurDaily($_event, $_rrule, $_exceptionRecurIds, $_from, $_until, $_recurSet)
    {
        $computationStartDate = clone $_event->dtstart;
        $computationEndDate   = ($_rrule->until instanceof Zend_Date && $_until->isLater($_rrule->until)) ? $_rrule->until : $_until;
        
        // if dtstart is before $_from, we compute the offset where to start our calculations
        if ($_event->dtstart->isEarlier($_from)) {
            $computationOffsetDays = floor(($_from->getTimestamp() - $_event->dtend->getTimestamp()) / (self::TS_DAY * $_rrule->interval)) * $_rrule->interval;
            $computationStartDate->add(new Zend_Date($computationOffsetDays * self::TS_DAY, Zend_Date::TIMESTAMP));
        }
        
        $eventLength = clone $_event->dtend;
        $eventLength->sub($_event->dtstart);
        
        $originatorsOriginalDtstart = clone $_event->dtstart;
        $originatorsOriginalDtstart->setTimezone($_event->originator_tz);
        
        while (true) {
            $computationStartDate->addDay($_rrule->interval);
            
            $recurEvent = self::cloneEvent($_event);
            $recurEvent->dtstart = clone ($computationStartDate);
            
            $originatorsDtstart = clone $recurEvent->dtstart;
            $originatorsDtstart->setTimezone($_event->originator_tz);
            $recurEvent->dtstart->add($originatorsOriginalDtstart->get(Zend_Date::DAYLIGHT) - $originatorsDtstart->get(Zend_Date::DAYLIGHT), Zend_Date::HOUR);
            //$recurEvent->dtstart->sub($originatorsDtstart->get(Zend_Date::DAYLIGHT) ? 1 : 0, Zend_Date::HOUR);
            
            if ($computationEndDate->isEarlier($recurEvent->dtstart)) {
                break;
            }
            
            // we calculate dtend from the event length, as events during a dst boundary could get dtend less than dtstart otherwise 
            $recurEvent->dtend = clone $recurEvent->dtstart;
            $recurEvent->dtend->add($eventLength);
            
            $recurEvent->recurid = $recurEvent->uid . '-' . $recurEvent->dtstart->toString(Tinebase_Record_Abstract::ISO8601LONG);
            
            if (! in_array($recurEvent->recurid, $_exceptionRecurIds)) {
                $_recurSet->addRecord($recurEvent);
            }
        }
    }
    
    /**
     * computes monthly (bymonthday) recurring events and inserts them into given $_recurSet
     *
     * @param Calendar_Model_Event      $_event
     * @param Calendar_Model_Rrule      $_rrule
     * @param array                     $_exceptionRecurIds
     * @param Zend_Date                 $_from
     * @param Zend_Date                 $_until
     * @param Tinebase_Record_RecordSet $_recurSet
     * @return void
     */
    protected static function _computeRecurMonthlyByMonthDay($_event, $_rrule, $_exceptionRecurIds, $_from, $_until, $_recurSet)
    {
    	$eventInOrganizerTZ = clone $_event;
    	$eventInOrganizerTZ->setTimezone($_event->originator_tz);
    	
        // NOTE: non existing dates will be discarded (e.g. 31. Feb.)
        //       for correct computations we deal with virtual dates, represented as arrays
        $computationStartDateArray = self::date2array($eventInOrganizerTZ->dtstart);
        // adopt startdate if rrule monthday != dtstart monthday
        // in this case, the first instance is not the base event!
        if ($_rrule->bymonthday != $computationStartDateArray['day']) {
            $computationStartDateArray['day'] = $_rrule->bymonthday;
            $computationStartDateArray = self::addMonthIngnoringDay($computationStartDateArray, -1 * $_rrule->interval);
        }
        
        $computationEndDate   = ($_rrule->until instanceof Zend_Date && $_until->isLater($_rrule->until)) ? $_rrule->until : $_until;
        
        // if dtstart is before $_from, we compute the offset where to start our calculations
        if ($eventInOrganizerTZ->dtstart->isEarlier($_from)) {
            $computationOffsetMonth = self::getMonthDiff($eventInOrganizerTZ->dtend, $_from);
            // NOTE: $computationOffsetMonth must be multiple of interval!
            $computationOffsetMonth = floor($computationOffsetMonth/$_rrule->interval) * $_rrule->interval;
            $computationStartDateArray = self::addMonthIngnoringDay($computationStartDateArray, $computationOffsetMonth - $_rrule->interval);
        }
        
        $eventLength = clone $eventInOrganizerTZ->dtend;
        $eventLength->sub($eventInOrganizerTZ->dtstart);
        
        $originatorsOriginalDtstart = clone $eventInOrganizerTZ->dtstart;
        
        while(true) {
            $computationStartDateArray = self::addMonthIngnoringDay($computationStartDateArray, $_rrule->interval);
            $recurEvent = self::cloneEvent($eventInOrganizerTZ);
            $recurEvent->dtstart = self::array2date($computationStartDateArray, $eventInOrganizerTZ->originator_tz);
            
            // we calculate dtend from the event length, as events during a dst boundary could get dtend less than dtstart otherwise 
            $recurEvent->dtend = clone $recurEvent->dtstart;
            $recurEvent->dtend->add($eventLength);
            
            $recurEvent->setTimezone('UTC');
            
            if ($computationEndDate->isEarlier($recurEvent->dtstart)) {
                break;
            }
            
            // skip non existing dates
            if (! Zend_Date::isDate(self::array2string($computationStartDateArray), Tinebase_Record_Abstract::ISO8601LONG)) {
                continue;
            }
            
            // skip events ending before our period.
            // NOTE: such events could be included, cause our offset only calcs months and not seconds
            if ($_from->compare($recurEvent->dtend) >= 0) {
                continue;
            }
            
            $recurEvent->recurid = $recurEvent->uid . '-' . $recurEvent->dtstart->toString(Tinebase_Record_Abstract::ISO8601LONG);
            
            if (! in_array($recurEvent->recurid, $_exceptionRecurIds)) {
                $_recurSet->addRecord($recurEvent);
            }
        }
    }
    
    /**
     * computes monthly (byday) recurring events and inserts them into given $_recurSet
     *
     * @param Calendar_Model_Event      $_event
     * @param Calendar_Model_Rrule      $_rrule
     * @param array                     $_exceptionRecurIds
     * @param Zend_Date                 $_from
     * @param Zend_Date                 $_until
     * @param Tinebase_Record_RecordSet $_recurSet
     * @return void
     */
    protected static function _computeRecurMonthlyByDay($_event, $_rrule, $_exceptionRecurIds, $_from, $_until, $_recurSet)
    {
    	$eventInOrganizerTZ = clone $_event;
        $eventInOrganizerTZ->setTimezone($_event->originator_tz);
        
        $computationStartDateArray = self::date2array($eventInOrganizerTZ->dtstart);
        
        // if period contains base events dtstart, we let computation start one intervall to early to catch
        // the cases when dtstart of base event not equals the first instance. If it fits, we filter the additional 
        // instance out later
        if ($eventInOrganizerTZ->dtstart->isLater($_from) && $eventInOrganizerTZ->dtstart->isEarlier($_until)) {
            $computationStartDateArray = self::addMonthIngnoringDay($computationStartDateArray, -1 * $_rrule->interval);
        }
        
        $computationEndDate   = ($_rrule->until instanceof Zend_Date && $_until->isLater($_rrule->until)) ? $_rrule->until : $_until;
        
        // if dtstart is before $_from, we compute the offset where to start our calculations
        if ($eventInOrganizerTZ->dtstart->isEarlier($_from)) {
            $computationOffsetMonth = self::getMonthDiff($eventInOrganizerTZ->dtend, $_from);
            // NOTE: $computationOffsetMonth must be multiple of interval!
            $computationOffsetMonth = floor($computationOffsetMonth/$_rrule->interval) * $_rrule->interval;
            $computationStartDateArray = self::addMonthIngnoringDay($computationStartDateArray, $computationOffsetMonth - $_rrule->interval);
        }
        
        $eventLength = clone $eventInOrganizerTZ->dtend;
        $eventLength->sub($eventInOrganizerTZ->dtstart);
        
        $computationStartDateArray['day'] = 1;
        
        $byDayInterval = (int) substr($_rrule->byday, 0, -2);
        $byDayWeekday  = substr($_rrule->byday, -2);
        
        if ($byDayInterval === 0 || ! array_key_exists($byDayWeekday, self::$WEEKDAY_DIGIT_MAP)) {
            throw new Exception('mal formated rrule byday part: "' . $_rrule->byday . '"');
        }
        
        while(true) {
            $computationStartDateArray = self::addMonthIngnoringDay($computationStartDateArray, $_rrule->interval);
            $computationStartDate = self::array2date($computationStartDateArray, $eventInOrganizerTZ->originator_tz);
            
            $recurEvent = self::cloneEvent($eventInOrganizerTZ);
            $recurEvent->dtstart = clone $computationStartDate;
            
            if ($byDayInterval < 0) {
            	$recurEvent->dtstart = self::array2date(self::addMonthIngnoringDay($computationStartDateArray, 1), $eventInOrganizerTZ->originator_tz);
                $recurEvent->dtstart->subDay(1);
            }
            self::skipWday($recurEvent->dtstart, $byDayWeekday, $byDayInterval, TRUE);
            
            // we calculate dtend from the event length, as events during a dst boundary could get dtend less than dtstart otherwise 
            $recurEvent->dtend = clone $recurEvent->dtstart;
            $recurEvent->dtend->add($eventLength);
            
            $recurEvent->setTimezone('UTC');
            
            if ($computationEndDate->isEarlier($recurEvent->dtstart)) {
                break;
            }
            
            // skip non existing dates
            if ($computationStartDate->get(Zend_Date::MONTH) != $recurEvent->dtstart->get(Zend_Date::MONTH)) {
                continue;
            }
            
            // skip events ending before our period.
            // NOTE: such events could be included, cause our offset only calcs months and not seconds
            if ($_from->compare($recurEvent->dtend) >= 0) {
                continue;
            }
            
            // skip if event equal baseevent
            if ($eventInOrganizerTZ->dtstart->equals($recurEvent->dtstart)) {
                continue;
            }
            
            $recurEvent->recurid = $recurEvent->uid . '-' . $recurEvent->dtstart->toString(Tinebase_Record_Abstract::ISO8601LONG);
            
            if (! in_array($recurEvent->recurid, $_exceptionRecurIds)) {
                $_recurSet->addRecord($recurEvent);
            }
        }
    }
    
    /**
     * skips date to (n'th next/previous) occurance of $_wday
     *
     * @param Zend_Date  $_date
     * @param int|string $_wday
     * @param int        $_n
     * @param bool       $_considerDateItself
     */
    public static function skipWday($_date, $_wday, $_n = +1, $_considerDateItself = FALSE)
    {
        $wdayDigit = is_int($_wday) ? $_wday : self::$WEEKDAY_DIGIT_MAP[$_wday];
        $wdayOffset = $_date->get(Zend_Date::WEEKDAY_DIGIT) - $wdayDigit;
        
        if ($_n == 0) {
            throw new Exception('$_n must not be 0');
        }
        
        $direction = $_n > 0 ? 'forward' : 'backward';
        $weeks = abs($_n);
        
        if ($_considerDateItself && $wdayOffset == 0) {
            $weeks--;
        }
        
        switch ($direction) {
            case 'forward':
                if ($wdayOffset >= 0) {
                    $_date->addDay(($weeks * 7) - $wdayOffset);
                } else {
                    $_date->addDay(abs($wdayOffset) + ($weeks -1) * 7);
                }
                break;
            case 'backward':
                if ($wdayOffset > 0) {
                    $_date->subDay(abs($wdayOffset) + ($weeks -1) * 7);
                } else {
                    $_date->subDay(($weeks * 7) + $wdayOffset);
                }
                break;
        }
            
        return $_date;
    }
    
    /**
     * converts a Zend_Date to Array
     *
     * @param  Zend_Date $_date
     * $return array
     */
    public static function date2array($_date)
    {
        return array_intersect_key($_date->toArray(), array_flip(array(
            'day' , 'month', 'year', 'hour', 'minute', 'second'
        )));
    }
    
    /**
     * converts date array to Zend_Date
     *
     * @param  array $_dateArray
     * @param  string $_timezone
     * @return Zend_Date
     */
    public static function array2date(array $_dateArray, $_timezone='UTC')
    {
    	date_default_timezone_set($_timezone);
        $date = new Zend_Date(mktime($_dateArray['hour'], $_dateArray['minute'], $_dateArray['second'], $_dateArray['month'], $_dateArray['day'], $_dateArray['year']), Zend_Date::TIMESTAMP);
        date_default_timezone_set('UTC');
        
        return $date;
    }
    
    /**
     * converts date array to string
     *
     * @param  array $_dateArray
     * @return string
     */
    public static function array2string(array $_dateArray)
    {
        return $_dateArray['year'] . '-' . str_pad($_dateArray['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($_dateArray['day'], 2, '0', STR_PAD_LEFT) . ' ' . 
                str_pad($_dateArray['hour'], 2, '0', STR_PAD_LEFT) . ':' . str_pad($_dateArray['minute'], 2, '0', STR_PAD_LEFT) . ':' . str_pad($_dateArray['second'], 2, '0', STR_PAD_LEFT);
    }
    
    /**
     * get number of month different from $_date1 to $_date2
     *
     * @param  Zend_Date|array $_from
     * @param  Zend_Date|array $_until
     * @return int
     */
    public static function getMonthDiff($_from, $_until)
    {
        $date1Array = is_array($_from) ? $_from : self::date2array($_from);
        $date2Array = is_array($_until) ? $_until : self::date2array($_until);
        
        return (12 * $date2Array['year'] + $date2Array['month']) - (12 * $date1Array['year'] + $date1Array['month']);
    }
    
    /**
     * add month and don't touch the day.
     * NOTE: The resulting date may no exist e.g. 31. Feb. -> virtual date 
     *
     * @param  Zend_Date|array  $_date
     * @param  int              $_months
     * @return array
     */
    public static function addMonthIngnoringDay($_date, $_months)
    {
        $dateArr = is_array($_date) ? $_date : self::date2array($_date);
        
        $totalMonth = 12 * $dateArr['year'] + $dateArr['month'] + $_months;
        $dateArr['year'] = $totalMonth % 12 ? floor($totalMonth/12) : $totalMonth/12 -1;
        $dateArr['month'] = $totalMonth % 12 ? $totalMonth % 12 : 12;
        
        return $dateArr;
    }
    
    /**
     * adds date to date and applies dst fix
     *
     * @param Zend_Date $_dateInUTC
     * @param Zend_Date $_addDateInUTC
     * @param string    $_timezoneForDstFix
     */
    public static function addUTCDateDstFix($_dateInUTC, $_addDateInUTC, $_timezoneForDstFix)
    {
        $_dateInUTC->setTimezone($_timezoneForDstFix);
        $_dateInUTC->add($_dateInUTC->get(Zend_Date::DAYLIGHT) ? 1 : 0, Zend_Date::HOUR);
        $_dateInUTC->add($_addDateInUTC);
        $_dateInUTC->sub($_dateInUTC->get(Zend_Date::DAYLIGHT) ? 1 : 0, Zend_Date::HOUR);
        $_dateInUTC->setTimezone('UTC');
    }
}