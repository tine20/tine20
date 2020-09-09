<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 */

/**
 * Tinebase_DateTime - Extensions around native php DateTime object
 * 
 * @package     Tinebase
 * 
 * @TODO: recheck addDay/Week ... rework dst fixes
 * @TODO: recheck 32 bit issues create / compare
 * 
 * NOTE: Tinebase_DateTime was introduced as replacement for Zend_Date in normal
 *       datetime operations for perfomance reasons. Most of the functions in this
 *       class are modeled after the Zend_Date class
 * 
 * NOTE: This class has nothing to do with localisation! If you need localisation
 *       use Zend_Date!
 */
class Tinebase_DateTime extends DateTime
{
    const TIMEZONE_UTC = "UTC";
    
    const MODIFIER_SECOND   = 'sec';
    const MODIFIER_MINUTE   = 'min';
    const MODIFIER_HOUR     = 'hour';
    const MODIFIER_DAY      = 'day';
    const MODIFIER_WEEK     = 'week';
    const MODIFIER_MONTH    = 'month';
    const MODIFIER_YEAR     = 'year';
    
    /**
     * holds datetime string when being serialised (needed for php < 5.3)
     * @see http://bugs.php.net/bug.php?id=46891
     * 
     * @var string
     */
    private $__sDT;
    
    /**
     * holds datetimezone string when being serialised (needed for php < 5.3)
     * @see http://bugs.php.net/bug.php?id=46891
     * 
     * @var string
     */
    private $__sDTZ;
    
    /**
     * this datetime represents a date only
     * 
     * @var bool
     */
    protected $_hasTime = TRUE;
    
    /**
     * @see http://bugs.php.net/bug.php?id=46891
     */
    public function __sleep(){
        $this->__sDT = $this->format('Y-m-d H:i:s');
        $tz = $this->getTimezone();
        $this->__sDTZ = $tz? $tz->getName() : 'UTC';
        return array('__sDT', '__sDTZ');
    }

    /**
     * @see http://bugs.php.net/bug.php?id=46891
     */
    public function __wakeup() {
        $this->__construct($this->__sDT, new DateTimeZone($this->__sDTZ ? $this->__sDTZ : 'UTC'));
        $this->__sDT = $this->__sDTZ = NULL;
    }
    
    /**
     * Returns new DateTime object
     * 
     * @param string|int|DateTime $_time
     * @param string|DateTimeZone $_timezone
     */
    public function __construct($_time = "now", $_timezone = NULL)
    {
        // allow to pass instanceof DateTime
        if ($_time instanceof DateTime) {
            if (! $_timezone) {
                $_timezone = $_time->getTimezone();
            } else {
                $_time = clone $_time;
                $_time->setTimezone($_timezone);
            }
            
            $time = $_time->format("Y-m-d H:i:s");
        } else {
            $time = (is_numeric($_time)) ? "@" . floor($_time) : $_time;
        }
        
        if ($_timezone) {
            if (! $_timezone instanceof DateTimeZone) {
                $_timezone = new DateTimeZone($_timezone);
            }
            
            parent::__construct($time, $_timezone);
        } else {
            parent::__construct($time);
        }

        if ($this->format('Y') === '0000') {
            throw new Exception('invalid year');
        }

        // Normalize Timezonename, as sometimes +00:00 is taken
        if (is_numeric($_time)) {
            $this->setTimezone('UTC');
        }

        if (PHP_VERSION_ID >= 70100) {
            list ($h, $m, $i) = explode(' ', $this->format('H i s'));
            parent::setTime($h, $m, $i, 0);
        }
    }
    
    /**
     * returns clone to be used with right hand operators
     * 
     * @return Tinebase_DateTime
     */
    public function getClone()
    {
        return clone $this;
    }
    
    /**
     * call interceptor
     * 
     * @param string $name
     * @param array $arguments
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'php52compat_') === FALSE) {
            return call_user_func_array(array($this, "php52compat_$name"), $arguments);
        }
        throw new Tinebase_Exception_InvalidArgument('unknown method: ' . str_replace('php52compat_', '', $name));
    }
    
    /**
     * Adds a date or datepart to the existing date. For PHP < 5.3 supported parts are:
     *  sec, min, hour, day, week, month, year. For later versions check DateTime::add docu.
     *  Returns the modified DateTime object or FALSE on failure.
     *  
     *  For compability with Zend_Date, if $_interval is a DateTime, the timestamp of this datetime 
     *  will be added
     *
     * @param  integer|DateTime|DateInterval    $_interval    Date or datepart to add
     * @param  string                           $part    OPTIONAL Part of the date to add, if null the timestamp is added
     * @return Tinebase_DateTime
     */
    public function add($_interval, $_part = self::MODIFIER_SECOND)
    {
        if ($_interval instanceof DateInterval) {
            return parent::add($_interval);
//        } else if ($_interval instanceof DateIntervalLight) {
//            
        } else if ($_interval instanceof DateTime) {
            $_interval = $_interval->get('U');
            $_part = self::MODIFIER_SECOND;
        }
        
        $sign = $_interval < 0 ? '-' : '+';
        return $this->modify($sign . abs($_interval) . $_part);
    }
    
    /**
     * Adds days. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number days to add
     * @return Tinebase_DateTime
     */
    public function addDay($number)
    {
        return $this->add($number*86400, self::MODIFIER_SECOND);
    }
    
    /**
     * Adds hours. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number hours to add
     * @return Tinebase_DateTime
     */
    public function addHour($number)
    {
        return $this->add($number*3600, self::MODIFIER_SECOND);
    }
    
    /**
     * Adds minutes. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number minutes to add
     * @return Tinebase_DateTime
     */
    public function addMinute($number)
    {
        return $this->add($number, self::MODIFIER_MINUTE);
    }
    
    /**
     * Adds months. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number months to add
     * @return Tinebase_DateTime
     */
    public function addMonth($number)
    {
        return $this->add($number, self::MODIFIER_MONTH);
    }
    
    /**
     * Adds seconds. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number seconds to add
     * @return Tinebase_DateTime
     */
    public function addSecond($number)
    {
        return $this->add($number, self::MODIFIER_SECOND);
    }
    
    /**
     * Adds weeks. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number weeks to add
     * @return Tinebase_DateTime
     */
    public function addWeek($number)
    {
        return $this->add($number*7*86400, self::MODIFIER_SECOND);
    }
    
    /**
     * Adds years. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number years to add
     * @return Tinebase_DateTime
     */
    public function addYear($number)
    {
        return $this->add($number, self::MODIFIER_YEAR);
    }
    
    /**
     * Compares date or datepart of this with an other.
     * Returns -1 if earlier, 0 if equal and 1 if later.
     *
     * @param  DateTime                        $_date    Date to compare with the date object
     * @param  string                          $_part    OPTIONAL Part of the date to compare, if null the timestamp is subtracted
     * @return integer  0 = equal, 1 = later, -1 = earlier
     */
    public function compare($_date, $_part = 'c')
    {
        if (! $_date instanceof DateTime) {
            throw new Tinebase_Exception_Date(var_export($_date, TRUE) . ' is not an instance of DateTime');
        }
        
        $cmpTZ = $this->getTimezone();
        
        if ($cmpTZ != $_date->getTimezone()) {
            $_date = clone $_date;
            $_date->setTimezone($cmpTZ);
        };
        
        $thisValue = $this->format($_part);
        $dateValue = $_date->format($_part);
        
        if ($thisValue == $dateValue) {
            return 0;
        } elseif ($thisValue > $dateValue) {
            return 1;
        } else {
            return -1;
        }
    }
    
    /**
     * Compares this month with the other.
     * Returns -1 if earlier, 0 if equal and 1 if later.
     *
     * @param  DateTime                        $_date    Date to compare with the date object
     * @return integer  0 = equal, 1 = later, -1 = earlier
     */
    public function compareMonth($_date)
    {
        return $this->compare($_date, 'n');
    }
    
    /**
     * Returns a representation of a date or datepart
     * This could be for example a monthname, the time without date,
     * the era or only the fractional seconds. For a list of supported part identifiers
     * look into the php date docu. 
     * 
     * NOTE: This method does not use locales. All output is in English.
     *
     * @param  string  $part    OPTIONAL Part of the date to return, if null the timestamp is returned
     * @return string  date or datepart
     */
    public function get($_part = null)
    {
        return is_null($_part) ? $this->getTimestamp() : $this->format($_part);
    }
    
    /**
     * Returns the full ISO 8601 date from the this object.
     * Always the complete ISO 8601 specifiction is used. If an other ISO date is needed
     * (ISO 8601 defines several formats) use toString() instead.
     *
     * @return string
     */
    public function getIso()
    {
        return $this->format("c");
    }
    
    /**
     * Returns true when both date objects or date parts are equal.
     *
     * @param  DateTime                        $date    Date to equal with
     * @param  string                          $part    OPTIONAL Part of the date to compare, if null the timestamp is used
     * @return boolean
     */
    public function equals($_date, $_part = 'c')
    {
        return $this->compare($_date, $_part) == 0;
    }
    
    /**
     * set/get the hasTime flag 
     * 
     * @param bool optional
     */
    public function hasTime()
    {
        $currValue = $this->_hasTime;
        if (func_num_args() === 1) {
            $paramValue = (bool) func_get_arg(0);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Resetting _hasTime to ' . (int) $paramValue);
            $this->_hasTime = $paramValue;
            
            if ($this->_hasTime === FALSE) {
                $this->setTime(0,0,0);
            }
        }
        
        return $currValue;
    }
    
    /**
     * Returns true if this date or datepart is earlier than the given date
     * 
     * @param  DateTime                        $date    Date to compare with
     * @param  string                          $part    OPTIONAL Part of the date to compare, if null the timestamp is used
     * @return boolean
     */
    public function isEarlier($_date, $_part = 'c')
    {
        return $this->compare($_date, $_part) < 0;
    }
    
    /**
     * Returns true if this date or datepart is later than the given date
     * 
     * @param  DateTime                        $date    Date to compare with
     * @param  string                          $part    OPTIONAL Part of the date to compare, if null the timestamp is used
     * @return boolean
     */
    public function isLater($_date, $_part = 'c')
    {
        return $this->compare($_date, $_part) > 0;
    }
    
    /**
     * Returns true if this date or datepart is earlier or equals than the given date
     *
     * @param  DateTime                        $date    Date to compare with
     * @param  string                          $part    OPTIONAL Part of the date to compare, if null the timestamp is used
     * @return boolean
     */
    public function isEarlierOrEquals($_date, $_part = 'c')
    {
        return $this->compare($_date, $_part) < 1;
    }
    
    /**
     * Returns true if this date or datepart is later or equals than the given date
     *
     * @param  DateTime                        $date    Date to compare with
     * @param  string                          $part    OPTIONAL Part of the date to compare, if null the timestamp is used
     * @return boolean
     */
    public function isLaterOrEquals($_date, $_part = 'c')
    {
        return $this->compare($_date, $_part) > -1;
    }
    
    /**
     * (non-PHPdoc)
     * @see DateTime::setDate()
     * @note PHP 5.3.0 changed the return value on success from NULL to DateTime.
     */
    public function setDate($year ,$month ,$day)
    {
        parent::setDate($year ,$month ,$day);
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see DateTime::setTime()
     * @note PHP 5.3.0 changed the return value on success from NULL to DateTime.
     * @note PHP 7.1 added param $microseconds
     */
    public function setTime($hour, $minute, $second = 0, $microseconds = null)
    {
        if (PHP_VERSION_ID < 70100) {
            parent::setTime($hour, $minute, $second);
        } else {
            parent::setTime($hour, $minute, $second, $microseconds);
        }
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see DateTime::setISODate()
     * @note PHP 5.3.0 changed the return value on success from NULL to DateTime.
     */
    public function setISODate($year ,$week, $day = 1)
    {
        parent::setISODate($year ,$week, $day);
        return $this;
    }
    
    /**
     * Substracts a date or datepart to the existing date. For PHP < 5.3 supported parts are:
     *  sec, min, hour, day, week, month, year. For later versions check DateTime::sub docu.
     *  Returns the modified DateTime object or FALSE on failure.
     *  
     *  For compability with Zend_Date, if $_interval is a DateTime, the timestamp of this datetime 
     *  will be substracted
     *
     * @param  integer|DateTime|DateInterval    $_interval    Date or datepart to substract
     * @param  string                           $part    OPTIONAL Part of the date to substract, if null the timestamp is substracted
     * @return Tinebase_DateTime
     */
    public function sub($_interval, $_part = self::MODIFIER_SECOND)
    {
        if ($_interval instanceof DateInterval) {
            return parent::sub($_interval);
        }
        
        $_interval = is_numeric($_interval) ? -1 * $_interval : $_interval;
        return $this->add($_interval, $_part);
    }
    
    /**
     * Substracts days. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number days to substract
     * @return Tinebase_DateTime
     */
    public function subDay($number)
    {
        return $this->addDay(-1 * $number);
    }
    
    /**
     * Substracts hours. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number hours to substract
     * @return Tinebase_DateTime
     */
    public function subHour($number)
    {
        return $this->addHour(-1 * $number);
    }
    
    /**
     * Substracts minutes. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number minutes to substract
     * @return Tinebase_DateTime
     */
    public function subMinute($number)
    {
        return $this->addMinute(-1 * $number);
    }
    
    /**
     * Substracts months. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number months to substract
     * @return Tinebase_DateTime
     */
    public function subMonth($number)
    {
        return $this->addMonth(-1 * $number);
    }
    
    /**
     * Substracts seconds. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number seconds to substract
     * @return Tinebase_DateTime
     */
    public function subSecond($number)
    {
        return $this->addSecond(-1 * $number);
    }
    
    /**
     * Substracts minutes. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number minutes to substract
     * @return Tinebase_DateTime
     */
    public function subWeek($number)
    {
        return $this->addWeek(-1 * $number);
    }
    
    /**
     * Substracts minutes. The parameter is always a number.
     * Returns the modified DateTime object or FALSE on failure.
     *
     * @param  integer              $number minutes to substract
     * @return Tinebase_DateTime
     */
    public function subYear($number)
    {
        return $this->addYear(-1 * $number);
    }
    
    /**
     * Alters the timestamp
     * NOTE: PHP 5.3.0 Changelog: Changed the return value on success from NULL to DateTime
     *
     * @param  string $modify A date/time string. Valid formats are explained in Date and Time Formats.
     * @return Tinebase_DateTime $this
     */
    public function modify($modify)
    {
        parent::modify($modify);
        return $this;
    }

    /**
     * apply time part of diff only
     *
     * @param DateInterval $diff
     * @return Tinebase_DateTime $this
     */
    public function modifyTime(DateInterval $diff)
    {
        $timeString = $this->getClone()->add($diff)->format('H:i:s');
        list($hours, $minutes, $seconds) = explode(':', $timeString);

        return $this->setTime($hours, $minutes, $seconds);
    }

    /**
     * Returns the difference between two DateTime objects
     * 
     * @param  DateTime $datetime2
     * @param  bool     $absolute
     * @return DateInterval
     */
    public function php52compat_diff(DateTime $datetime2, $absolute = false)
    {
        // use Zend_Date for 32 bit compat
        $thisZD = new Zend_Date($this->format('U'));
        $datetime2ZD = new Zend_Date($datetime2->format('U'));
        
        return $datetime2ZD->sub($thisZD, Zend_Date::TIMESTAMP);
    }
    
    /**
     * Returns a string representation of the object
     * For Supported format tokens see: php.net/date
     * 
     * @return String
     */
    public function __toString()
    {
        return $this->format("Y-m-d H:i:s");
    }
    
    /**
     * Returns a string representation of the object
     * For Supported format tokens see: php.net/date
     * 
     * @param  String $_format  OPTIONAL Rule for formatting output. If null the default date format is used
     * @return String
     */
    public function toString($_format = "Y-m-d H:i:s")
    {
        return $this->format($_format);
    }
    
    /**
     * Sets a new hour
     * The hour is always a number.
     * Returned is the new date object
     * Example: 04.May.1993 13:07:25 -> setHour(7); -> 04.May.1993 07:07:25
     *
     * @param  string|integer $modify    Hour to set
     * @return Tinebase_DateTime  $this
     */
    public function setHour($modify)
    {
        list ($i, $s) = explode(' ', $this->format('i s'));
        
        $this->setTime($modify, $i, $s);
        return $this;
    }
    
    /**
     * Sets a new minute
     * The minute is always a number.
     * Returned is the new date object
     * Example: 04.May.1993 13:23:25 -> setMinute(7); -> 04.May.1993 13:07:25
     *
     * @param  string|integer $modify    Minute to set
     * @return Tinebase_DateTime  $this
     */
    public function setMinute($modify)
    {
        list ($h, $s) = explode(' ', $this->format('H s'));
        
        $this->setTime($h, $modify, $s);
        return $this;
    }
    
    /**
     * Sets a new second
     * The second is always a number.
     * Returned is the new date object
     * Example: 04.May.1993 13:07:25 -> setSecond(7); -> 04.May.1993 13:07:07
     *
     * @param  string|integer $modify    Second to set
     * @return Tinebase_DateTime  $this
     */
    public function setSecond($modify)
    {
        list ($h, $i) = explode(' ', $this->format('H i'));
        
        $this->setTime($h, $i, $modify);
        return $this;
    }
    
    /**
     * Sets a new timezone.
     * For a list of supported timezones look here: http://php.net/timezones
     *
     * @param  string|DateTimeZone  $_timezone  timezone for date calculation
     * @return Tinebase_DateTime    $this
     */
    public function setTimezone($_timezone)
    {
        if ($this->_hasTime === FALSE) $date = $this->format('Y-m-d');
        
        $timezone = $_timezone instanceof DateTimeZone ? $_timezone : new DateTimeZone($_timezone);
        parent::setTimezone($timezone);
        
        // if we contain no time info, we are timezone invariant
        if ($this->_hasTime === FALSE) {
            call_user_func_array(array($this, 'setDate'), explode('-', $date));
            $this->setTime(0,0,0);
        }
        
        return $this;
    }
    
    /**
     * Sets a new week. The week is always a number. The day of week is not changed.
     * Returned is the new date object
     * Example: 09.Jan.2007 13:07:25 -> setWeek(1); -> 02.Jan.2007 13:07:25
     *
     * @param  string|integer     $week    Week to set
     * @return Tinebase_DateTime  $this
     */
    public function setWeek($week)
    {
        $currentWeek = (int)$this->format("W");
        $operator = ($currentWeek > $week) ? "-" : "+";
        return $this->modify($operator . abs($currentWeek - $week) . "week");
    }
    
    /**
     * Sets a new weekday
     * The weekday can be a number or a string.
     * Returned is the new date object.
     * Example: setWeekday(3); will set the wednesday of this week as day.
     *
     * @param  string|integer                  $weekDay   Weekday to set
     * @return Tinebase_DateTime  $this
     */
    public function setWeekDay($weekDay)
    {
        $currentWeekDay = (int)$this->format("N");
        $operator = ($currentWeekDay > $weekDay) ? "-" : "+";
        return $this->modify($operator . abs($currentWeekDay - $weekDay) . "day");
    }
    
    /**
     * Checks if the given date is a real date or datepart.
     * Returns false if a expected datepart is missing or a datepart exceeds its possible border.
     *
     * @param  string             $_date   Date to parse for correctness
     * @return boolean            True when all date parts are correct
     */
    public static function isDate($_date)
    {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            $result = date_parse($_date);
            return empty($result['warning_count']) && empty($result['error_count']);
        } else {
            return Zend_Date::isDate($_date, 'yyyy-MM-dd HH:mm:ss');
        }
    }
    
    /**
     * Returns an array representation of the object
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'day'       => $this->format('j'),
            'month'     => $this->format('n'),
            'year'      => $this->format('Y'),
            'hour'      => $this->format('G'),
            'minute'    => $this->format('i'),
            'second'    => $this->format('s'),
            'timezone'  => $this->format('e'),
            'timestamp' => $this->format('U'),
            'weekday'   => $this->format('N'),
            'dayofyear' => $this->format('z'),
            'week'      => $this->format('W'),
            'gmtsecs'   => $this->getOffset()
        );
    }
    
    /**
     * Returns the current datetime
     *
     * @return Tinebase_DateTime
     */
    public static function now()
    {
        return new Tinebase_DateTime();
    }

    /**
     * returns the current date
     *
     * @return Tinebase_DateTime
     */
    public static function today()
    {
        $date = static::now();
        $date->hasTime(false);
        return $date;
    }
}

