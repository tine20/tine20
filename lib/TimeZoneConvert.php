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
 * wrapper
 */
class TimeZoneConvert
{
    /**
     * converts VTIMEZONE component to DateTimeZone
     * 
     * NOTE: If VTIMEZONE could not be converted, $expectedTimeZone is returned
     * 
     * @param  string                  $VTimeZone
     * @param  string                  $prodId
     * @param  string/DateTimeZone     $expectedTimeZone
     * @return DateTimeZone
     */
    public static function fromVTimeZone($VTimeZone, $prodId = "", $expectedTimeZone = NULL)
    {
        $expectedTimeZoneId = $expectedTimeZone instanceof DateTimeZone ? $expectedTimeZone->getName() : $expectedTimeZone;
        
        $converter = new TimeZoneConvert_VTimeZone();
        $timeZoneId = $converter->getTZIdentifier($VTimeZone, $prodId, $expectedTimeZone);
        
        $timezone = $expectedTimeZone;
        try {
            if ($timeZoneId) {
                try {
                    $timezone = new DateTimeZone($timeZoneId);
                } catch (Exception $e) {}
            }
            
            if (! $timezone instanceof DateTimeZone && $expectedTimeZone) {
                $timezone = new DateTimeZone($expectedTimeZone);
            }
            
            if (! $timezone instanceof DateTimeZone) {
                throw new Exception('can not convert');
            }
        } catch (Exception $e) {
            $timezone = date_create()->getTimezone();
        }
        
        return $timezone;
    }
    
    /**
     * converts DateTimeZone to VTIMEZONE
     * 
     * @param  string/DateTimeZone     $timezone
     * @param  DateTime                $from
     * @param  DateTime                $until
     * @return string
     */
    public static function toVTimeZone($timezone, $from = NULL, $until = NULL)
    {
        $tzid = $timezone instanceof DateTimeZone ? $timezone->getName() : $timezone;
        
        $converter = new TimeZoneConvert_VTimeZone();
        
        return $converter->getVTimezone($tzid, $from, $until);
    }
}