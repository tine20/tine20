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
class TimeZoneConvert_Transition extends TimeZoneConvert_Model
{
    /**
     * timestamp
     * @var int
     */
    public $ts;
    
    /**
     * date
     * @var string ISO8601
     */
    public $date;
    
    /**
     * offset (to)
     * @var int
     */
    public $offset;
    
    /**
     * transition lead to dst
     * @var bool
     */
    public $isdst;
    
    /**
     * abbriviation
     * @var string
     */
    public $abbr;
    
    /**
     * match given transitions with timezones
     * 
     * if an expected timezone is given, this one will be matched first
     * 
     * @param  array          $transitions
     * @param  DateTimeZone   $expectedTimeZone
     * @return DateTimeZone
     */
    public static function getMatchingTimezone($transitions, $expectedTimeZone=NULL)
    {
        if ($expectedTimeZone && self::matchTimezone($transitions, $expectedTimeZone)) {
            return $expectedTimeZone;
        }
        
        // match with complete list
        $tzlist = DateTimeZone::listIdentifiers();
        
        foreach($tzlist as $tzid) {
            $timezone = new DateTimeZone($tzid);
            if (self::matchTimezone($transitions, $timezone)) {
                return $timezone;
            }
        }
    }
    
    /**
     * returns TRUE if given timezone matches given transitions
     * 
     * @param  array         $transitions
     * @param  DateTimeZone  $timezone
     * @return bool
     */
    public static function matchTimezone($transitions, $timezone)
    {
        $transitionsTss = $transitions->ts;
        
        $referenceTransitions = self::getTransitions($timezone, min($transitionsTss), max($transitionsTss) + 1);
        $referenceTss = $referenceTransitions->ts;
        
        $matchingReferenceTransitions = array_intersect($referenceTss, $transitionsTss);
        
        // if all given transistion had a match, check offsets
        if (count($matchingReferenceTransitions) == count($transitionsTss)) {
            asort($transitionsTss, SORT_NUMERIC);
            asort($matchingReferenceTransitions, SORT_NUMERIC);
            
            foreach($matchingReferenceTransitions as $refKey => $refTs) {
                $refOffset = $referenceTransitions[$refKey]['offset'];
                $matchOffset = $transitions[key($transitionsTss)]['offset'];
                
                if ($refOffset != $matchOffset) {
                    return FALSE;
                }
                next($transitionsTss);
            }
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
     * returns set of refernece transitions
     * 
     * @param  string/DateTimeZone $tzid
     * @param  DateTime $from
     * @param  DateTime $until
     * @return TimeZoneConvert_Set
     */
    public static function getTransitions($tzid, $from, $until)
    {
        $timezone = $tzid instanceof DateTimeZone ? $tzid : new DateTimeZone($tzid);
        $beginTS = $from instanceof DateTime ? $from->getTimestamp() : $from;
        $endTS = $until instanceof DateTime ? $until->getTimestamp() : $until;
        
        // NOTE: DateTimeZone::getTransitions first "transition" reflects $beginTS
        //       so we make sure to not match a transition with it and throw it away
        $transitions = $endTS ? $timezone->getTransitions(--$beginTS, $endTS) : $timezone->getTransitions(--$beginTS);
        array_shift($transitions);
        
        $transitions = new TimeZoneConvert_Set($transitions);
        
        return $transitions;
    }
}