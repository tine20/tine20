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

class TimeZoneConvert_VTimeZone
{
    protected static $_transitionRuleCache = array();
    
    /**
     * gets php's DateTimeZone identifier from given VTIMEZONE and optional prodid
     * 
     * @param  string $VTimeZone
     * @param  string $prodId
     * @return string
     * @throws TimeZoneConvert_Exception
     */
    public function getDateTimeIdentifier($VTimeZone, $prodId='')
    {
        // cache by shasum
        // known prodid/vtimezone -> match
        // well known tz name -> match
        // compute tz:
        
        // get/compute transitions
        $transitions = array();
        preg_match_all('/BEGIN:(?:STANDARD|DAYLIGHT)(?:.|\s)*END:(?:STANDARD|DAYLIGHT)/U', $VTimeZone, $vTransitionRules);
        foreach((array) $vTransitionRules[0] as $vTransitionRule) {
            // @TODO need to inject help from when two when due to buggy clients
            $transitions = array_merge($transitions, $this->vTransitionRuleToTransitions($vTransitionRule));
        }
        
        //  try match with timezones 
        
    }
    
    /**
     * get/compute transitions of a single vTransitionRule
     * 
     * @param  string $vTransitionRule
     * @return array of TimeZoneConvert_Transition
     */
    public function vTransitionRuleToTransitions($vTransitionRule)
    {
            // parse mandentory DTSTART
            if (! preg_match('/DTSTART:(.*)/', $vTransitionRule, $dtstart)) {
                throw new TimeZoneConvert_Exception('DTSTART missing');
            }
            $dtstart = new DateTime($dtstart[1], new DateTimeZone('UTC'));
            if ($dtstart === FALSE) {
                throw new TimeZoneConvert_Exception('could not parse dtstart');
            }
            
            // parse mandentory TZOFFSETFROM
            if (! preg_match('/TZOFFSETFROM:(.*)/', $vTransitionRule, $offsetFrom)) {
                throw new TimeZoneConvert_Exception('TZOFFSETFROM missing');
            }
            $offsetFromSign = $offsetFrom[1][0] == '-' ? '-' : '+';
            $offsetFromSeconds = substr($offsetFrom[1], -4, -2) * 3600 + substr($offsetFrom[1], -2) * 60;
            
            $dtstart->modify("$offsetFromSign $offsetFromSeconds seconds");
            
            // parse TZOFFSETTO
            if (preg_match('/TZOFFSETTO:(.*)/', $vTransitionRule, $offsetTo)) {
                $offsetToSign = $offsetTo[1][0] == '-' ? '-' : '+';
                $offsetToSeconds = substr($offsetTo[1], -4, -2) * 3600 + substr($offsetTo[1], -2) * 60;
            }
            $offsetTo = $offsetTo ? ($offsetToSign . '1') * $offsetToSeconds : 0;
            
            // parse TZNAME
            $abbr = preg_match('/TZNAME:(.*)/', $vTransitionRule, $abbr) ? $abbr[1] : '';
            $isdst = (bool) preg_match('/BEGIN:DAYLIGHT/', $vTransitionRule);
            $transitions = array();
            
            // transitions by RRULE 
            if (preg_match('/RRULE:(.*)/', $vTransitionRule, $rrule)) {
                $rrule = TimeZoneConvert_VTimeZone_Rrule::createFromString($rrule[1]);
                $transitionRule = new TimeZoneConvert_TransitionRule(array(
                    'hour'   => (int) $dtstart->format('G'),
                    'minute' => (int) $dtstart->format('i'),
                    'second' => (int) $dtstart->format('s'),
                    'month'  => $rrule->month,
                    'wkday'  => $rrule->wkday,
                    'numwk'  => $rrule->numwk,
//                     'until'  => $rrule->until,
                ));
                
                // get "some" transitions
                // clients often have a wrong start (dtstart) 
                //   -> from now? if no until / only two rules
                //   -> if more than two rules guess from is correct
            } 
            
            // transitions by RDATE
            else if (preg_match('/RDATE:(.*)/', $vTransitionRule, $rdate)) {
                
            }
            
            // single transition
            else {
                $transition = new TimeZoneConvert_Transition(array(
                    'ts'      => $dtstart->getTimestamp(),
                    'time'    => $dtstart->format('c'),
                    'offset'  => $offsetTo,
                    'isdst'   => $isdst,
                    'abbr'    => $abbr,
                ));
                
                $transitions[] = $transition;
            }
            
            return $transitions;
    }
    /**
     * calculates the transitaion date time for a given year
     * 
     * @param  string $rrule     transition rrule
     * @param  string $dtstart   effective onset date and local time
     * @param  string $year      year of the transition
     * @return DateTime
     */
    public function rrule2TransitionRule($rrule, $dtstart, $year=NULL)
    {
        $cacheKey = $rrule.$dtstart;
        
        if (! array_key_exists($cacheKey, self::$_transitionRuleCache)) {
            $rrule = TimeZoneConvert_VTimeZone_Rrule::createFromString($rrule);
            $dtstart = new DateTime($dtstart);
            if ($dtstart === FALSE) {
                throw new TimeZoneConvert_Exception('could not parse dtstart');
            }
            
            $transitionRule = new TimeZoneConvert_TransitionRule(array(
                'month'  => $rrule->month,
                'wkday'  => $rrule->wkday,
                'numwk'  => $rrule->numwk,
                'hour'   => (int) $dtstart->format('G'),
                'minute' => (int) $dtstart->format('i'),
                'second' => (int) $dtstart->format('s'),
            ));
            
            self::$_transitionRuleCache[$cacheKey] = $transitionRule;
        }
        
        return self::$_transitionRuleCache[$cacheKey]->getTransition($year);
    }
}