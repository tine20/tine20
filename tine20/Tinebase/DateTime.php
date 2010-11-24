<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * @version     $Id$
 */

/**
 * Tinebase_DateTime - Extensions around native php DateTime object
 * 
 * @TODO: recheck date creation from custom formats in record/set
 * @TODO: recheck timezone handling on date creation in record
 * @TODO: recheck localisation support -> Tinebase_Translation -> back to Zend_Date
 * @TODO: recheck addDay/Week ... rework dst fixes
 * @TODO: clean up this class and add more docu
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
     * Returns new DateTime object
     * 
     * @param string|int    $_time
     * @param DateTimeZone  $_timezone
     */
    public function __construct($_time = "now", $_timezone = NULL)
    {
        $time = (is_numeric($_time)) ? "@" . $_time : $_time;
        if ($_timezone) {
            if (! $_timezone instanceof DateTimeZone) {
                $_timezone = new DateTimeZone($_timezone);
            }
            
            parent::__construct($time, $_timezone);
        } else {
            parent::__construct($time);
        }
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
            throw new Tinebase_Exception_Date('given $_date is not an instance of DateTime');
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
     * Returns the UNIX timestamp representation of this datetime.
     * 
     * NOTE: PHP < 5.3.0 dosn't have this fn, so we overwrite it
     *
     * @return string  UNIX timestamp
     */
    public function getTimestamp()
    {
        return $this->format('U');
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
    
    
    
    
    
    
    
    
    
    
    
    
    public function __toString()
    {
        return $this->format("Y-m-d H:i:s");
    }
    
    public function toString($_format = "Y-m-d H:i:s")
    {
        return $this->format($_format);
    }
    
    public function setHour($modify)
    {
        list ($i, $s) = explode(' ', $this->format('i s'));
        
        $this->setTime($modify, $i, $s);
        return $this;
    }
    
    public function setMinute($modify)
    {
        list ($h, $s) = explode(' ', $this->format('H s'));
        
        $this->setTime($h, $modify, $s);
        return $this;
    }
    
    public function setTimezone($_timezone)
    {
        parent::setTimezone(new DateTimeZone($_timezone));
        return $this;
    }
    
    public function setWeek($week)
    {
        $currentWeek = (int)$this->format("W");
        $operator = ($currentWeek > $week) ? "-" : "+";
        return $this->modify($operator . abs($currentWeek - $week) . "week");
    }
    
    public function setWeekDay($weekDay)
    {
        $currentWeekDay = (int)$this->format("N");
        $operator = ($currentWeekDay > $weekDay) ? "-" : "+";
        return $this->modify($operator . abs($currentWeekDay - $weekDay) . "day");
    }
    
    
    public static function isDate($_date)
    {
        $result = date_parse($_date);
        return empty($result['warning_count']) && empty($result['error_count']);
    }
    
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
            'timestamp' => $this->format('U'), // $this->getTimestamp() for php >= 5.3.0
            'weekday'   => $this->format('N'),
            'dayofyear' => $this->format('z'),
            'week'      => $this->format('W'),
            'gmtsecs'   => $this->getOffset()
        );
    }
    
    public static function now()
    {
        return new Tinebase_DateTime();
    }
    
}
