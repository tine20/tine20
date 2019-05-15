<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
 * @subpackage  Model
 *
 * @property string                 $freq
 * @property string                 $bymonth
 * @property string                 $byday
 * @property string                 $bymonthday
 * @property Tinebase_DateTime      $until
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
    
    static $WEEKDAY_MAP = array(
        self::WDAY_SUNDAY     => 'sun',
        self::WDAY_MONDAY     => 'mon',
        self::WDAY_TUESDAY    => 'tue',
        self::WDAY_WEDNESDAY  => 'wed',
        self::WDAY_THURSDAY   => 'thu',
        self::WDAY_FRIDAY     => 'fri',
        self::WDAY_SATURDAY   => 'sat'
    );

    static $WEEKDAY_MAP_REVERSE = array(
        'sun' => self::WDAY_SUNDAY,
        'mon' => self::WDAY_MONDAY,
        'tue' => self::WDAY_TUESDAY,
        'wed' => self::WDAY_WEDNESDAY,
        'thu' => self::WDAY_THURSDAY,
        'fri' => self::WDAY_FRIDAY,
        'sat' => self::WDAY_SATURDAY
    );

    const TS_HOUR = 3600;
    const TS_DAY  = 86400;
    const MAX_DAILY_RECUR_COUNT = 500;
    
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
        'id'                   => array('allowEmpty' => true,  /*'Alnum'*/),
        'freq'                 => array(
            'allowEmpty' => true,
            array('InArray', array(self::FREQ_DAILY, self::FREQ_MONTHLY, self::FREQ_WEEKLY, self::FREQ_YEARLY)),
        ),
        'interval'             => array('allowEmpty' => true, 'Int'   ),
        'byday'                => array('allowEmpty' => true, 'Regex' => '/^[\-0-9A_Z,]{2,}$/'),
        'bymonth'              => array('allowEmpty' => true, 'Int'   ),
        'bymonthday'           => array('allowEmpty' => true, 'Int'   ),
        'wkst'                 => array(
            'allowEmpty' => true,
            array('InArray', array(self::WDAY_SUNDAY, self::WDAY_MONDAY, self::WDAY_TUESDAY, self::WDAY_WEDNESDAY, self::WDAY_THURSDAY, self::WDAY_FRIDAY, self::WDAY_SATURDAY)),
        ),
        'until'                => array('allowEmpty' => true          ),
        'count'                => array('allowEmpty' => true, 'Int'   ),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'until',
    );
    
    /**
     * @var array supported standard rrule parts
     */
    protected $_rruleParts = array('freq', 'interval', 'until', 'count', 'wkst', 'byday', 'bymonth', 'bymonthday');
    
    /**
     * @see /Tinebase/Record/Abstract::__construct
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $rruleString = NULL;
        
        if (is_string($_data)) {
            $rruleString = $_data;
            $_data = NULL;
        }
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
        
        if ($rruleString) {
            $this->setFromString($rruleString);
        }
    }
    
    /**
     * set from ical rrule string
     *
     * @param string $_rrule
     */
    public function setFromString($_rrule)
    {
        if ($_rrule) {
            $parts = explode(';', $_rrule);
            $skipParts = array();
            foreach ($parts as $part) {
                list($key, $value) = explode('=', $part);
                $part = strtolower($key);
                if (in_array($part, $skipParts)) {
                    continue;
                }
                if (! in_array($part, $this->_rruleParts)) {
                    if ($part === 'bysetpos') {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                            . " Map bysetpos to a supported RRULE part: bymonthday");
                        $part = 'bymonthday';
                        $this->byday = null;
                        $skipParts[] = 'byday';
                    } else {
                        throw new Tinebase_Exception_UnexpectedValue("$part is not a known rrule part");
                    }
                }
                $this->$part = $value;
            }
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
        $rrule = new Calendar_Model_Rrule(NULL, TRUE);
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
                $value = $this->$part instanceof DateTime ? $this->$part->toString(self::ISO8601LONG) : $this->$part;
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
                    if ($_value instanceof DateTime) {
                        $this->_properties['until'] = $_value;
                    } else {
                        $this->_properties['until'] = new Tinebase_DateTime($_value);
                    }
                }
                break;
            case 'bymonth':
            case 'bymonthday':
                if (! empty($_value)) {
                    $values = explode(',', $_value);
                    $this->_properties[$_name] = (integer) $values[0];
                }
                break;
            case 'count':
                if ((int) $_value > self::MAX_DAILY_RECUR_COUNT) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . " skipping COUNT=" .  $_value ." clause of rrule -> client meant 'forever'");
                    break;
                }
                // fallthrough
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
    
    /**
     * validate and filter the the internal data
     *
     * @param $_throwExceptionOnInvalidData
     * @return bool
     * @throws Tinebase_Exception_Record_Validation
     */
    public function isValid($_throwExceptionOnInvalidData = false)
    {
        $isValid = parent::isValid($_throwExceptionOnInvalidData);
        
        if (isset($this->_properties['count']) && isset($this->_properties['until'])) {
            $isValid = $this->_isValidated = false;
            
            if ($_throwExceptionOnInvalidData) {
                $e = new Tinebase_Exception_Record_Validation('count and until can not be set both');
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . $e);
                throw $e;
            }
        }
        
        return $isValid;
    }
    
    /**
     * normalizes rrule by setting missing clauses. This is needed as some rrule computations
     * need all clauses and have no access to the event itself.
     * 
     * @param Calendar_Model_Event $event
     */
    public function normalize(Calendar_Model_Event $event)
    {
        // set originators TZ to get correct byday/bymonth/bymonthday rrules
        $originatorDtStart = clone($event->dtstart);
        if (! empty($event->originator_tz)) {
            $originatorDtStart->setTimezone($event->originator_tz);
        }
        
        switch ($this->freq) {
            case self::FREQ_WEEKLY:
                if (! $this->wkst ) {
                    $this->wkst = self::getWeekStart();
                }
            
                if (! $this->byday) {
                    $this->byday = array_search($originatorDtStart->format('w'), self::$WEEKDAY_DIGIT_MAP);
                }
                break;
            
            case self::FREQ_MONTHLY:
                if (! $this->byday && ! $this->bymonthday) {
                    $this->bymonthday = $originatorDtStart->format('j');
                }
                break;
                
            case self::FREQ_YEARLY:
                if (! $this->byday && ! $this->bymonthday) {
                    $this->bymonthday = $originatorDtStart->format('j');
                }
                if (! $this->bymonth) {
                    $this->bymonth = $originatorDtStart->format('n');
                }
                break;
            default:
                // do nothing
                break;
        }
    }
    
    /**
     * get human readable version of this rrule
     * 
     * @param  Zend_Translate   $translation
     * @return string
     */
    public function getTranslatedRule($translation)
    {
        $rule = '';
        $locale = new Zend_Locale($translation->getAdapter()->getLocale());
        $numberFormatter = null;
        $weekDays = Zend_Locale::getTranslationList('day', $locale);
        
        switch ($this->freq) {
            case self::FREQ_DAILY:
                $rule .= $this->interval > 1 ?
                    sprintf($translation->_('Every %s day'), $this->_formatInterval($this->interval, $translation, $numberFormatter)) :
                    $translation->_('Daily');
                break;
                
            case self::FREQ_WEEKLY:
                $rule .= $this->interval > 1 ?
                    sprintf($translation->_('Every %s week on') . ' ', $this->_formatInterval($this->interval, $translation, $numberFormatter)) :
                    $translation->_('Weekly on') . ' ';
                
                $recurWeekDays = explode(',', $this->byday);
                $recurWeekDaysCount = count($recurWeekDays);
                foreach ($recurWeekDays as $idx => $recurWeekDay) {
                    $rule .= $weekDays[self::$WEEKDAY_MAP[$recurWeekDay]];
                    if ($recurWeekDaysCount && $idx+1 != $recurWeekDaysCount) {
                        $rule .= $idx == $recurWeekDaysCount-2 ? ' ' . $translation->_('and') . ' ' : ', ';
                    }
                }
                break;
                
            case self::FREQ_MONTHLY:
                if ($this->byday) {
                    $byDayInterval = (int) substr($this->byday, 0, -2);
                    $byDayIntervalTranslation = $this->_getIntervalTranslation($byDayInterval, $translation);
                    $byDayWeekday  = substr($this->byday, -2);
                    
                    $rule .= $this->interval > 1 ?
                        sprintf($translation->_('Every %1$s month on the %2$s %3$s'), $this->_formatInterval($this->interval, $translation, $numberFormatter), $byDayIntervalTranslation, $weekDays[self::$WEEKDAY_MAP[$byDayWeekday]]) :
                        sprintf($translation->_('Monthly every %1$s %2$s'), $byDayIntervalTranslation, $weekDays[self::$WEEKDAY_MAP[$byDayWeekday]]);
                    
                } else {
                    $bymonthday = $this->bymonthday;
                    
                    $rule .= $this->interval > 1 ?
                        sprintf($translation->_('Every %1$s month on the %2$s'), $this->_formatInterval($this->interval, $translation, $numberFormatter), $this->_formatInterval($this->bymonthday, $translation, $numberFormatter)) :
                        sprintf($translation->_('Monthly on the %1$s'), $this->_formatInterval($this->bymonthday, $translation, $numberFormatter));
                }
                break;
            case self::FREQ_YEARLY:
                $month = Zend_Locale::getTranslationList('month', $locale);
                if ($this->byday) {
                    $byDayInterval = (int) substr($this->byday, 0, -2);
                    $byDayIntervalTranslation = $this->_getIntervalTranslation($byDayInterval, $translation);
                    $byDayWeekday  = substr($this->byday, -2);
                    $rule .= sprintf($translation->_('Yearly every %1$s %2$s of %3$s'), $byDayIntervalTranslation, $weekDays[self::$WEEKDAY_MAP[$byDayWeekday]], $month[$this->bymonth]);
                } else {
                    $rule .= sprintf($translation->_('Yearly on the %1$s of %2$s'), $this->_formatInterval($this->bymonthday, $translation, $numberFormatter), $month[$this->bymonth]);
                }
                
                break;
        }
        
        return $rule;
    }
    
    /**
     * format interval (use NumberFormatter if intl extension is found)
     * 
     * @param integer $number
     * @param Zend_Translate $translation
     * @param NumberFormatter|null $numberFormatter
     * @return string
     */
    protected function _formatInterval($number, $translation, $numberFormatter = null)
    {
        if ($numberFormatter === null && extension_loaded('intl')) {
            $locale = new Zend_Locale($translation->getAdapter()->getLocale());
            $numberFormatter = new NumberFormatter((string) $locale, NumberFormatter::ORDINAL);
        }
        
        $result = ($numberFormatter) ? $numberFormatter->format($number) : $this->_getIntervalTranslation($number, $translation);
        
        return $result;
    }
    
    /**
     * get translation string for interval (first, second, ...)
     * 
     * @param integer $interval
     * @param Zend_Translate $translation
     * @return string
     */
    protected function _getIntervalTranslation($interval, $translation)
    {
        switch ($interval) {
            case -2: 
                $result = $translation->_('second to last');
                break;
            case -1: 
                $result = $translation->_('last');
                break;
            case 0: 
                throw new Tinebase_Exception_UnexpectedValue('0 is not supported');
                break;
            case 1: 
                $result = $translation->_('first');
                break;
            case 2: 
                $result = $translation->_('second');
                break;
            case 3: 
                $result = $translation->_('third');
                break;
            case 4: 
                $result = $translation->_('fourth');
                break;
            case 5: 
                $result = $translation->_('fifth');
                break;
            default:
                switch ($interval % 10) {
                    case 1:
                        $result = $interval . $translation->_('st');
                        break;
                    case 2:
                        $result = $interval . $translation->_('nd');
                        break;
                    case 3:
                        $result = $interval . $translation->_('rd');
                        break;
                    default:
                        $result = $interval . $translation->_('th');
                }
        }
        
        return $result;
    }
    
    /************************* Recurrence computation *****************************/
    
    /**
     * merges Recurrences of given events into the given event set
     * 
     * @param  Tinebase_Record_RecordSet    $_events
     * @param  Tinebase_DateTime                    $_from
     * @param  Tinebase_DateTime                    $_until
     * @return void
     */
    public static function mergeRecurrenceSet($_events, $_from, $_until)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . " from: $_from until: $_until");
        
        //compute recurset
        $candidates = $_events->filter('rrule', "/^FREQ.*/", TRUE);
       
        foreach ($candidates as $candidate) {
            try {
                $exceptions = self::getExceptionsByCandidate($_events, $candidate);
                
                $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($candidate, $exceptions, $_from, $_until);
                foreach ($recurSet as $event) {
                    $_events->addRecord($event);
                }
                
                // check if candidate/baseEvent has an exception itself -> in this case remove baseEvent from set
                if (is_array($candidate->exdate) && in_array($candidate->dtstart, $candidate->exdate)) {
                    $_events->removeRecord($candidate);
                }
                
            } catch (Exception $e) {
               if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                       . " Could not compute recurSet of event: {$candidate->getId()}");
               Tinebase_Exception::log($e);
               continue;
            }
        }
    }

    public static function getExceptionsByCandidate($_events, $_candidate)
    {
        return $_events->filter('recurid', "/^".preg_quote($_candidate->uid)."-.*/", TRUE);
    }
    
    /**
     * add given recurrence to given set and to nessesary adoptions
     * 
     * @param Calendar_Model_Event      $_recurrence
     * @param Tinebase_Record_RecordSet $_eventSet
     */
    protected static function addRecurrence($_recurrence, $_eventSet)
    {
        $_recurrence->setId('fakeid' . $_recurrence->base_event_id . '/' . $_recurrence->dtstart->getTimeStamp());
        
        // adjust alarms
        if ($_recurrence->alarms instanceof Tinebase_Record_RecordSet) {
            foreach($_recurrence->alarms as $alarm) {
                $alarm->alarm_time = clone $_recurrence->dtstart;
                $alarm->alarm_time->subMinute($alarm->getOption('minutes_before'));
            }
        }
        
        $_eventSet->addRecord($_recurrence);
    }
    
    /**
     * merge recurrences amd remove all events that do not match period filter
     * 
     * @param Tinebase_Record_RecordSet $_events
     * @param Calendar_Model_EventFilter $_filter
     */
    public static function mergeAndRemoveNonMatchingRecurrences(Tinebase_Record_RecordSet $_events, Calendar_Model_EventFilter $_filter = null)
    {
        if (!$_filter) {
            return;
        }
        
        $period = $_filter->getFilter('period', false, true);
        if ($period) {
            self::mergeRecurrenceSet($_events, $period->getFrom(), $period->getUntil());
            
            foreach ($_events as $event) {
                if (! $event->isInPeriod($period)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' (' . __LINE__ 
                        . ') Removing not matching event ' . $event->summary);
                    $_events->removeRecord($event);
                }
            }
        }
    }
    
    /**
     * returns next occurrence _ignoring exceptions_ or NULL if there is none/not computable
     * 
     * NOTE: an ongoing event during $from [start, end[ is considered as next 
     * NOTE: for previous events on ongoing event is considered as previous
     *  
     * NOTE: computing the next occurrence of an open end rrule can be dangerous, as it might result
     *       in a endless loop. Therefore we only make a limited number of attempts before giving up.
     * 
     * @param  Calendar_Model_Event         $_event
     * @param  Tinebase_Record_RecordSet    $_exceptions
     * @param  Tinebase_DateTime            $_from
     * @param  Int                          $_which
     * @return Calendar_Model_Event|NULL
     */
    public static function computeNextOccurrence($_event, $_exceptions, $_from, $_which = 1)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' $from = ' . $_from->toString());
        
        if ($_which === 0 || ($_event->dtstart >= $_from && $_event->dtend > $_from)) {
            return $_event;
        }
        
        $freqMap = array(
            self::FREQ_DAILY   => Tinebase_DateTime::MODIFIER_DAY,
            self::FREQ_WEEKLY  => Tinebase_DateTime::MODIFIER_WEEK,
            self::FREQ_MONTHLY => Tinebase_DateTime::MODIFIER_MONTH,
            self::FREQ_YEARLY  => Tinebase_DateTime::MODIFIER_YEAR
        );
        
        $rrule = new Calendar_Model_Rrule(NULL, TRUE);
        $rrule->setFromString($_event->rrule);
        
        $from  = clone $_from;
        $until = clone $from;
        $interval = $_which * $rrule->interval;
        
        // we don't want to compute ourself
        $ownEvent = clone $_event;
        $ownEvent->setRecurId($_event->getId());
        $exceptions = clone $_exceptions;
        $exceptions->addRecord($ownEvent);
        $recurSet = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        if ($_from->isEarlier($_event->dtstart)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' from is ealier dtstart -> given event is next occurrence');
            return $_event;
        }
        
        $rangeDate = $_which > 0 ? $until : $from;
        
        if (! isset($freqMap[$rrule->freq])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Invalid RRULE:' . print_r($rrule->toArray(), true));
            throw new Calendar_Exception('Invalid freq in RRULE: ' . $rrule->freq);
        }
        $rangeDate->add($interval, $freqMap[$rrule->freq]);
        $attempts = 0;
        
        if ($_event->rrule_until instanceof DateTime && Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Event rrule_until: ' . $_event->rrule_until->toString());
        
        while (TRUE) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' trying to find next occurrence from ' . $from->toString());
            
            if ($_event->rrule_until instanceof DateTime && $from->isLater($_event->rrule_until)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' passed rrule_until -> no further occurrences');
                return NULL;
            }
            
            $until = ($_event->rrule_until instanceof DateTime && $until->isLater($_event->rrule_until))
                ? clone $_event->rrule_until 
                : $until;

            $recurSet->merge(self::computeRecurrenceSet($_event, $exceptions, $from, $until));
            $attempts++;

            // NOTE: computeRecurrenceSet also returns events during $from in some cases, but we need
            // to events later than $from.
            $recurSet = $recurSet->filter(function($event) use ($from) {
                return $event->dtstart >= $from;
            });
            
            if (count($recurSet) >= abs($_which)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " found next occurrence after $attempts attempt(s)");
                break;
            }
            
            if ($attempts > count($exceptions) + 5) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " could not find the next occurrence after $attempts attempts, giving up");
                return NULL;
            }
            
            $from->add($interval, $freqMap[$rrule->freq]);
            $until->add($interval, $freqMap[$rrule->freq]);
        }
        
        $recurSet->sort('dtstart', ($_which > 0 && $attempts == 1) ? 'ASC' : 'DESC');
        $nextOccurrence = $recurSet[abs($_which)-1];
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' $nextOccurrence->dtstart = ' . $nextOccurrence->dtstart->toString());
        return $nextOccurrence;
    }
    
    /**
     * Computes the Recurrence set of the given event leaving out $_event->exdate and $_exceptions
     * 
     * @todo respect rrule_until!
     *
     * @param  Calendar_Model_Event         $_event
     * @param  Tinebase_Record_RecordSet    $_exceptions
     * @param  Tinebase_DateTime            $_from
     * @param  Tinebase_DateTime            $_until
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public static function computeRecurrenceSet($_event, $_exceptions, $_from, $_until)
    {
        if (! $_event->dtstart instanceof Tinebase_DateTime) {
            throw new Tinebase_Exception_UnexpectedValue('Event needs DateTime dtstart: ' . print_r($_event->toArray(), TRUE));
        }
        
        $rrule = new Calendar_Model_Rrule(NULL, TRUE);
        $rrule->setFromString($_event->rrule);
        
        $exceptionRecurIds = self::getExceptionsRecurIds($_event, $_exceptions);
        $recurSet = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        switch ($rrule->freq) {
            case self::FREQ_DAILY:
                
                self::_computeRecurDaily($_event, $rrule, $exceptionRecurIds, $_from, $_until, $recurSet);
                break;
                
            case self::FREQ_WEEKLY:
                // default BYDAY clause
                if (! $rrule->byday) {
                    $rrule->byday = array_search($_event->dtstart->format('w'), self::$WEEKDAY_DIGIT_MAP);
                }
                
                if (! $rrule->wkst) {
                    $rrule->wkst = self::getWeekStart();
                }
                $weekDays = array_keys(self::$WEEKDAY_DIGIT_MAP);
                array_splice($weekDays, 0, 0, array_splice($weekDays, array_search($rrule->wkst, $weekDays)));
                    
                $dailyrrule = clone ($rrule);
                $dailyrrule->freq = self::FREQ_DAILY;
                $dailyrrule->interval = 7 * $rrule->interval;
                
                $eventLength = $_event->dtstart->diff($_event->dtend);
                
                foreach (explode(',', $rrule->byday) as $recurWeekDay) {
                    // NOTE: in weekly computation, each wdays base event is a recur instance itself
                    $baseEvent = clone $_event;
                    
                    // NOTE: skipping must be done in organizer_tz
                    $baseEvent->dtstart->setTimezone($_event->originator_tz);
                    $direction = array_search($recurWeekDay, $weekDays) >= array_search(array_search($baseEvent->dtstart->format('w'), self::$WEEKDAY_DIGIT_MAP), $weekDays) ? +1 : -1;
                    self::skipWday($baseEvent->dtstart, $recurWeekDay, $direction, TRUE);
                    $baseEvent->dtstart->setTimezone('UTC');
                    
                    $baseEvent->dtend = clone($baseEvent->dtstart);
                    $baseEvent->dtend->add($eventLength);
                    
                    self::_computeRecurDaily($baseEvent, $dailyrrule, $exceptionRecurIds, $_from, $_until, $recurSet);
                    
                    // check if base event (recur instance) needs to be added to the set
                    if ($baseEvent->dtstart > $_event->dtstart && $baseEvent->dtstart >= $_from && $baseEvent->dtstart < $_until) {
                        if (! in_array($baseEvent->setRecurId($baseEvent->getId()), $exceptionRecurIds)) {
                            self::addRecurrence($baseEvent, $recurSet);
                        }
                    }
                }
                $recurSet->sort('dtstart');
                break;
                
            case self::FREQ_MONTHLY:
                if ($rrule->byday) {
                    self::_computeRecurMonthlyByDay($_event, $rrule, $exceptionRecurIds, $_from, $_until, $recurSet);
                } else {
                    self::_computeRecurMonthlyByMonthDay($_event, $rrule, $exceptionRecurIds, $_from, $_until, $recurSet);
                }
                break;
                
            case self::FREQ_YEARLY:
                $yearlyrrule = clone $rrule;
                $yearlyrrule->freq = self::FREQ_MONTHLY;
                $yearlyrrule->interval = 12;
                
                $baseEvent = clone $_event;
                $originatorsDtstart = clone $baseEvent->dtstart;
                $originatorsDtstart->setTimezone($_event->originator_tz);
                
                // @TODO respect BYMONTH
                if ($rrule->bymonth && $rrule->bymonth != $originatorsDtstart->format('n')) {
                    // adopt
                    $diff = (12 + $rrule->bymonth - $originatorsDtstart->format('n')) % 12;
                    
                    // NOTE: skipping must be done in organizer_tz
                    $baseEvent->dtstart->setTimezone($_event->originator_tz);
                    $baseEvent->dtend->setTimezone($_event->originator_tz);
                    $baseEvent->dtstart->addMonth($diff);
                    $baseEvent->dtend->addMonth($diff);
                    $baseEvent->dtstart->setTimezone('UTC');
                    $baseEvent->dtend->setTimezone('UTC');
                    
                    // check if base event (recur instance) needs to be added to the set
                    if ($baseEvent->dtstart->isLater($_from) && $baseEvent->dtstart->isEarlier($_until)) {
                        if (! in_array($baseEvent->setRecurId($baseEvent->getId()), $exceptionRecurIds)) {
                            self::addRecurrence($baseEvent, $recurSet);
                        }
                    }
                }
                
                if ($rrule->byday) {
                    self::_computeRecurMonthlyByDay($baseEvent, $yearlyrrule, $exceptionRecurIds, $_from, $_until, $recurSet);
                } else {
                    self::_computeRecurMonthlyByMonthDay($baseEvent, $yearlyrrule, $exceptionRecurIds, $_from, $_until, $recurSet);
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
     * @param Tinebase_DateTime                 $_from
     * @param Tinebase_DateTime                 $_until
     * @param Tinebase_Record_RecordSet $_recurSet
     * @return void
     */
    protected static function _computeRecurDaily($_event, $_rrule, $_exceptionRecurIds, $_from, $_until, $_recurSet)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . " from: $_from until: $_until");
        
        $computationStartDate = clone $_event->dtstart;
        $endDate = ($_event->rrule_until instanceof DateTime
                && $_until instanceof Tinebase_DateTime
                && $_until->isLater($_event->rrule_until))
            ? $_event->rrule_until
            : $_until;
        if (! $endDate instanceof Tinebase_DateTime) {
            throw new Tinebase_Exception_InvalidArgument('End date is no DateTime');
        }
        $computationEndDate   = clone $endDate;

        // if dtstart is before $_from, we compute the offset where to start our calculations
        if ($_event->dtstart->isEarlier($_from)) {
            $originatorsOriginalDtend = $_event->dtend->getClone()->setTimezone($_event->originator_tz);
            $originatorsFrom = $_from->getClone()->setTimezone($_event->originator_tz);

            $dstDiff = $originatorsFrom->get('I') - $originatorsOriginalDtend->get('I');

            $computationOffsetDays = floor(($_from->getTimestamp() - $_event->dtend->getTimestamp() + $dstDiff * 3600) / (self::TS_DAY * $_rrule->interval)) * $_rrule->interval;
            $computationStartDate->add($computationOffsetDays, Tinebase_DateTime::MODIFIER_DAY);
        }

        $eventLength = $_event->dtstart->diff($_event->dtend);

        $originatorsOriginalDtstart = $_event->dtstart->getClone()->setTimezone($_event->originator_tz);

        // we only compute until $count == MAX_DAILY_RECUR_COUNT to avoid to get oom
        $count = 0;
        while ($count < self::MAX_DAILY_RECUR_COUNT) {
            $computationStartDate->addDay($_rrule->interval);

            $recurEvent = self::cloneEvent($_event);
            $recurEvent->dtstart = clone ($computationStartDate);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . " Checking candidate at " . $recurEvent->dtstart->format('c'));
            
            $originatorsDtstart = $recurEvent->dtstart->getClone()->setTimezone($_event->originator_tz);

            $recurEvent->dtstart->add($originatorsOriginalDtstart->get('I') - $originatorsDtstart->get('I'), Tinebase_DateTime::MODIFIER_HOUR);

            if ($computationEndDate->isEarlier($recurEvent->dtstart)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . " Leaving loop: end date " . $computationEndDate->format('c') . " is earlier than recurEvent->dtstart "
                    . $recurEvent->dtstart->format('c'));
                break;
            }
            
            // we calculate dtend from the event length, as events during a dst boundary could get dtend less than dtstart otherwise 
            $recurEvent->dtend = clone $recurEvent->dtstart;
            $recurEvent->dtend->add($eventLength);

            $recurEvent->setRecurId($_event->getId());

            if ($_from->compare($recurEvent->dtend) >= 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . " Skip event: end date $_from is after recurEvent->dtend " . $recurEvent->dtend);

                continue;
            }
            
            if (! in_array($recurEvent->recurid, $_exceptionRecurIds)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " Found recurrence at " . $recurEvent->dtstart);

                self::addRecurrence($recurEvent, $_recurSet);
                $count++;
            }
        }

        if ($count === self::MAX_DAILY_RECUR_COUNT) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . " We reached the MAX_DAILY_RECUR_COUNT of " . self::MAX_DAILY_RECUR_COUNT
                . " - something is fishy about the processed rrule: " . print_r($_rrule->toArray(), true));
        }
    }
    
    /**
     * computes monthly (bymonthday) recurring events and inserts them into given $_recurSet
     *
     * @param Calendar_Model_Event      $_event
     * @param Calendar_Model_Rrule      $_rrule
     * @param array                     $_exceptionRecurIds
     * @param Tinebase_DateTime                 $_from
     * @param Tinebase_DateTime                 $_until
     * @param Tinebase_Record_RecordSet $_recurSet
     * @return void
     */
    protected static function _computeRecurMonthlyByMonthDay($_event, $_rrule, $_exceptionRecurIds, $_from, $_until, $_recurSet)
    {
        
        $eventInOrganizerTZ = clone $_event;
        $eventInOrganizerTZ->setTimezone($_event->originator_tz);
        
        // some clients skip the monthday e.g. for yearly rrules
        if (! $_rrule->bymonthday) {
            $_rrule->bymonthday = $eventInOrganizerTZ->dtstart->format('j');
        }
        
        // NOTE: non existing dates will be discarded (e.g. 31. Feb.)
        //       for correct computations we deal with virtual dates, represented as arrays
        $computationStartDateArray = self::date2array($eventInOrganizerTZ->dtstart);
        // adopt startdate if rrule monthday != dtstart monthday
        // in this case, the first instance is not the base event!
        if ($_rrule->bymonthday != $computationStartDateArray['day']) {
            $computationStartDateArray['day'] = $_rrule->bymonthday;
            $computationStartDateArray = self::addMonthIgnoringDay($computationStartDateArray, -1 * $_rrule->interval);
        }
        
        $computationEndDate   = ($_event->rrule_until instanceof DateTime && $_until->isLater($_event->rrule_until)) ? $_event->rrule_until : $_until;
        
        
        
        // if dtstart is before $_from, we compute the offset where to start our calculations
        if ($eventInOrganizerTZ->dtstart->isEarlier($_from)) {
            $computationOffsetMonth = self::getMonthDiff($eventInOrganizerTZ->dtend, $_from);
            // NOTE: $computationOffsetMonth must be multiple of interval!
            $computationOffsetMonth = floor($computationOffsetMonth/$_rrule->interval) * $_rrule->interval;
            $computationStartDateArray = self::addMonthIgnoringDay($computationStartDateArray, $computationOffsetMonth - $_rrule->interval);
        }
        
        $eventLength = $eventInOrganizerTZ->dtstart->diff($eventInOrganizerTZ->dtend);
        
        $originatorsOriginalDtstart = clone $eventInOrganizerTZ->dtstart;
        
        while(true) {
            $computationStartDateArray = self::addMonthIgnoringDay($computationStartDateArray, $_rrule->interval);
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
            if (! Tinebase_DateTime::isDate(self::array2string($computationStartDateArray))) {
                continue;
            }
            
            // skip events ending before our period.
            // NOTE: such events could be included, cause our offset only calcs months and not seconds
            if ($_from->compare($recurEvent->dtend) >= 0) {
                continue;
            }
            
            $recurEvent->setRecurId($_event->getId());
            
            
            if (! in_array($recurEvent->recurid, $_exceptionRecurIds)) {
                self::addRecurrence($recurEvent, $_recurSet);
            }
        }
    }
    
    /**
     * computes monthly (byday) recurring events and inserts them into given $_recurSet
     *
     * @param Calendar_Model_Event      $_event
     * @param Calendar_Model_Rrule      $_rrule
     * @param array                     $_exceptionRecurIds
     * @param Tinebase_DateTime                 $_from
     * @param Tinebase_DateTime                 $_until
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
            $computationStartDateArray = self::addMonthIgnoringDay($computationStartDateArray, -1 * $_rrule->interval);
        }
        
        $computationEndDate   = ($_event->rrule_until instanceof DateTime && $_until->isLater($_event->rrule_until)) ? $_event->rrule_until : $_until;
        
        // if dtstart is before $_from, we compute the offset where to start our calculations
        if ($eventInOrganizerTZ->dtstart->isEarlier($_from)) {
            $computationOffsetMonth = self::getMonthDiff($eventInOrganizerTZ->dtend, $_from);
            // NOTE: $computationOffsetMonth must be multiple of interval!
            $computationOffsetMonth = floor($computationOffsetMonth/$_rrule->interval) * $_rrule->interval;
            $computationStartDateArray = self::addMonthIgnoringDay($computationStartDateArray, $computationOffsetMonth - $_rrule->interval);
        }
        
        $eventLength = $eventInOrganizerTZ->dtstart->diff($eventInOrganizerTZ->dtend);
        
        $computationStartDateArray['day'] = 1;
        
        $byDayInterval = (int) substr($_rrule->byday, 0, -2);
        $byDayWeekday  = substr($_rrule->byday, -2);
        
        if ($byDayInterval === 0 || ! (isset(self::$WEEKDAY_DIGIT_MAP[$byDayWeekday]) || array_key_exists($byDayWeekday, self::$WEEKDAY_DIGIT_MAP))) {
            throw new Exception('mal formated rrule byday part: "' . $_rrule->byday . '"');
        }
        
        while(true) {
            $computationStartDateArray = self::addMonthIgnoringDay($computationStartDateArray, $_rrule->interval);
            $computationStartDate = self::array2date($computationStartDateArray, $eventInOrganizerTZ->originator_tz);
            
            $recurEvent = self::cloneEvent($eventInOrganizerTZ);
            $recurEvent->dtstart = clone $computationStartDate;
            
            if ($byDayInterval < 0) {
                $recurEvent->dtstart = self::array2date(self::addMonthIgnoringDay($computationStartDateArray, 1), $eventInOrganizerTZ->originator_tz);
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
            if ($computationStartDate->get('m') != $recurEvent->dtstart->get('m')) {
                continue;
            }
            
            // skip events ending before our period.
            // NOTE: such events could be included, cause our offset only calcs months and not seconds
            if ($_from->compare($recurEvent->dtend) >= 0) {
                continue;
            }
            
            // skip instances begining before the baseEvent
            if ($recurEvent->dtstart->compare($_event->dtstart) < 0) {
                continue;
            }
            
            // skip if event equal baseevent
            if ($_event->dtstart->equals($recurEvent->dtstart)) {
                continue;
            }
            
            $recurEvent->setRecurId($_event->getId());
            
            if (! in_array($recurEvent->recurid, $_exceptionRecurIds)) {
                self::addRecurrence($recurEvent, $_recurSet);
            }
        }
    }

    /**
     * skips date to (n'th next/previous) occurance of $_wday
     *
     * @param Tinebase_DateTime  $_date
     * @param int|string $_wday
     * @param int        $_n
     * @param bool       $_considerDateItself
     */
    public static function skipWday($_date, $_wday, $_n = +1, $_considerDateItself = FALSE)
    {
        $wdayDigit = is_int($_wday) ? $_wday : self::$WEEKDAY_DIGIT_MAP[$_wday];
        $wdayOffset = $_date->get('w') - $wdayDigit;
                                
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
     * converts a Tinebase_DateTime to Array
     *
     * @param  Tinebase_DateTime $_date
     * @return array
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public static function date2array($_date)
    {
        if (! $_date instanceof Tinebase_DateTime) {
            throw new Tinebase_Exception_UnexpectedValue('DateTime expected');
        }
        
        return array_intersect_key($_date->toArray(), array_flip(array(
            'day' , 'month', 'year', 'hour', 'minute', 'second'
        )));
    }
    
    /**
     * converts date array to Tinebase_DateTime
     *
     * @param  array $_dateArray
     * @param  string $_timezone
     * @return Tinebase_DateTime
     */
    public static function array2date(array $_dateArray, $_timezone='UTC')
    {
        date_default_timezone_set($_timezone);
        
        $date = new Tinebase_DateTime(mktime($_dateArray['hour'], $_dateArray['minute'], $_dateArray['second'], $_dateArray['month'], $_dateArray['day'], $_dateArray['year']));
        $date->setTimezone($_timezone);
        
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
     * @param  Tinebase_DateTime|array $_from
     * @param  Tinebase_DateTime|array $_until
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
     * @param  Tinebase_DateTime|array  $_date
     * @param  int              $_months
     * @return array
     */
    public static function addMonthIgnoringDay($_date, $_months)
    {
        $dateArr = is_array($_date) ? $_date : self::date2array($_date);
        
        $totalMonth = 12 * $dateArr['year'] + $dateArr['month'] + $_months;
        $dateArr['year'] = $totalMonth % 12 ? floor($totalMonth/12) : $totalMonth/12 -1;
        $dateArr['month'] = $totalMonth % 12 ? $totalMonth % 12 : 12;
        
        return $dateArr;
    }
    
    /**
     * adds diff to date and applies dst fix
     *
     * @param Tinebase_DateTime $_dateInUTC
     * @param DateTimeInterval $_diff
     * @param string    $_timezoneForDstFix
     */
    public static function addUTCDateDstFix($_dateInUTC, $_diff, $_timezoneForDstFix)
    {
        $_dateInUTC->setTimezone($_timezoneForDstFix);
        $_dateInUTC->add($_dateInUTC->get('I') ? 1 : 0, Tinebase_DateTime::MODIFIER_HOUR);
        $_dateInUTC->add($_diff);
        $_dateInUTC->subHour($_dateInUTC->get('I') ? 1 : 0);
        $_dateInUTC->setTimezone('UTC');
    }
    
    /**
     * returns weekstart in iCal day format
     * 
     * @param  string $locale
     * @return string
     */
    public static function getWeekStart($locale = NULL) {
        $locale = $locale ?: Tinebase_Core::getLocale();
        
        $weekInfo = Zend_Locale::getTranslationList('week', $locale);
        if (!isset($weekInfo['firstDay'])) {
            $weekInfo['firstDay'] = 'mon';
        }
        return Tinebase_Helper::array_value($weekInfo['firstDay'], array_flip(self::$WEEKDAY_MAP));
    }
}
