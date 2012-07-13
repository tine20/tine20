<?php
/**
 * TimeZoneConvert
 *
 * @package     TimeZoneConvert
 * @subpackage  VTimeZone
 * @license     MIT, BSD, and GPL
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

/**
 * Simple rrule class of ical (RFC 5546) RRULES within VTIMEZONE components
 * 
 * NOTE: RRULEs within VTIMEZONE components are always of the format:
 *       FREQ=YEARLY;BYDAY=<numwk><wkday>;BYMONTH=<month>
 *       
 * NOTE: RRULES don't necessarily describe timezones correctly. For example:
 *       Olson:
 *       "Fri>=26" to mean April 1 in years like 2005, 2011, 2016, ...
 *       asia:Rule       Zion    2012    2015    -       Mar     Fri>=26 2:00    1:00    D
 *       This can't be expressed in a single RRule and in fact is represended 
 *       in multiple rules in the olson tz database. Moreover in ical RRULES 
 *       for 2015 could be BYDAY=3FR or BYDAY=-1FR.
 *       
 */
class TimeZoneConvert_VTimeZone_Rrule
{
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
    
    public $wkday = null;
    public $numwk = null;
    public $month = null;
    
    protected static $_cache = array();
    
    /**
     * parse rrule string and return rrule object
     * 
     *  @param  $rruleString
     *  @return TimeZoneConvert_VTimeZone_Rrule
     */
    public static function createFromString($rruleString)
    {
        if (! array_key_exists($rruleString, self::$_cache)) {
            $rrule = new self();
            
            $parts = explode(';', $rruleString);
            foreach ($parts as $part) {
                list($key, $value) = explode('=', $part);
                switch (strtolower($key)) {
                    case 'bymonth':
                        $rrule->month = (int) $value;
                        if (! $rrule->month) {
                            throw new TimeZoneConvert_Exception('invalid BYDAY month');
                        }
                        break;
                    case 'byday':
                        $icsWkDay = substr($value, -2);
                        if (! array_key_exists($icsWkDay, self::$WEEKDAY_DIGIT_MAP)) {
                            throw new TimeZoneConvert_Exception('invalid BYDAY wkday');
                        }
                        $rrule->wkday = self::$WEEKDAY_DIGIT_MAP[$icsWkDay];
                        $rrule->numwk = (int) substr($value, 0, -2);
                        if (! $rrule->numwk) {
                            throw new TimeZoneConvert_Exception('invalid BYDAY numwk');
                        }
                        break;
                }
            }
            self::$_cache[$rruleString] = $rrule;
        }
        
        return clone self::$_cache[$rruleString];
    }
}