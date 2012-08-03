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
 * horror timezones found in the wild
 *
 */
class TimeZoneConvert_VTimeZone_ChamberOfHorrors
{
    /**
     * hashmap 
     */
    public static $hashMap = array(
        /* 
            PRODID:-//Novell Inc//Groupwise 8.0.2
            BEGIN:VTIMEZONE
            TZID:(GMT+0100) Westeuropäische Normalzeit
            BEGIN:STANDARD
            TZOFFSETFROM:+0200
            TZOFFSETTO:+0100
            DTSTART:20001028T020000
            RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10
            TZNAME:Westeuropäische Normalzeit
            END:STANDARD
            BEGIN:DAYLIGHT
            TZOFFSETFROM:+0100
            TZOFFSETTO:+0200
            DTSTART:20000325T010000
            RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3
            TZNAME:Westeuropäische Sommerzeit
            END:DAYLIGHT
            END:VTIMEZONE
        */
        'ab98fd6d9e433c2d373ca1cbf0a72f302cb05c46' => 'Europe/Berlin'
    );
    
    /**
     * returns timezone identifier if found in map
     * 
     * @param  string $VTimeZone
     * @param  string $prodId
     * @return string/NULL
     */
    public static function getMatch($VTimeZone, $prodId)
    {
        $key = sha1(self::getHash($VTimeZone, $prodId));
        
        return array_key_exists($key, self::$hashMap) ? self::$hashMap[$key] : NULL;
    }
    
    /**
     * compute key for hashmap
     * 
     * @param  string $VTimeZone
     * @param  string $prodId
     * @return string
     */
    public static function getHash($VTimeZone, $prodId)
    {
        return $prodId . str_replace(array("\r", "\r\n", "\n"), '', $VTimeZone);
    }
}