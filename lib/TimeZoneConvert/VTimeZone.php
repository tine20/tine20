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
 * convert vtimezone to php timezone
 * 
 */
class TimeZoneConvert_VTimeZone
{
    
    /**
     * gets php's DateTimeZone identifier from given VTIMEZONE and optional prodid
     * 
     * @param  string         $VTimeZone
     * @param  string         $prodId
     * @param  string         $expectedTimezone
     * @return string
     */
    public function getTZIdentifier($VTimeZone, $prodId='', $expectedTimezoneId=NULL)
    {
        try {
            // known prodid/vtimezone -> match
            if ($timezone = TimeZoneConvert_VTimeZone_ChamberOfHorrors::getMatch($VTimeZone, $prodId)) {
                return $timezone;
            }
            
            // well known tz name -> match
            if ($timezone = $this->matchByName($VTimeZone)) {
                return $timezone;
            }
            
            // compute timezone
            $timezone = $this->computeTimezone($VTimeZone, $expectedTimezoneId=NULL);
            return $timezone->getName();
            
        } catch (Exception $e) {
            return NULL;
        }
    }
    
    /**
     * tries to match a timezone by name from
     *  TZID
     *  X-LIC-LOCATION
     *  X-MICROSOFT-CDO-TZID
     *  
     * @param string $VTimeZone
     * @return string
     */
    public function matchByName($VTimeZone)
    {
        $phpTZIDs = DateTimeZone::listIdentifiers();
        
        if (preg_match('/TZID:(.*)/', $VTimeZone, $tzid)) {
            // php timezones
            if (in_array($tzid[1], $phpTZIDs)) {
                return $tzid[1];
            }
            
            // known/m$ timezones
            if (array_key_exists($tzid[1], TimeZoneConvert_KnownNamesMap::$map)) {
                return TimeZoneConvert_KnownNamesMap::$map[$tzid[1]];
            }
        }
        
        // eventually an X-LIC-LOCATION is included
        if (preg_match('/X-LIC-LOCATION:(.*)/', $VTimeZone, $tzid)) {
            // php timezones
            if (in_array($tzid[1], $phpTZIDs)) {
                return $tzid[1];
            }
        }
        
        // X-MICROSOFT-CDO-TZID (Exchange)
        if (preg_match('/X-MICROSOFT-CDO-TZID:(.*)/', $VTimeZone, $tzid)) {
            if (array_key_exists($tzid[1], TimeZoneConvert_KnownNamesMap::$microsoftExchangeMap)) {
                return TimeZoneConvert_KnownNamesMap::$microsoftExchangeMap[$tzid[1]];
            }
        }
    }
    
    /**
     * compute timezone from vtimezone
     * 
     * @param string $VTimeZone
     * @throws TimeZoneConvert_Exception
     */
    public function computeTimezone($VTimeZone, $expectedTimezoneId=NULL)
    {
        // get transition rules
        $transitionRules = $this->getTransitionRules($VTimeZone);
        
        // get transitions
        $transitions = $this->getTransitions($transitionRules);
        
        $expectedTimezone = $expectedTimezoneId ? new DateTimeZone($expectedTimezoneId) : NULL;
        $timezone = TimeZoneConvert_Transition::getMatchingTimezone($transitions, $expectedTimezone);
        
        if (! $timezone instanceof DateTimeZone) {
            throw new TimeZoneConvert_Exception('no timezone matched');
        }
        
        return $timezone;
    }
    /**
     * compute transitions from transition rules
     * 
     * @param  TimeZoneConvert_Set $transitionRules
     * @return TimeZoneConvert_Set
     */
    public function getTransitions($transitionRules)
    {
        $transitions = new TimeZoneConvert_Set();
        
        foreach($transitionRules as $transitionRule) {
            if ($transitionRule->isRecurringRule()) {
                $transitionDates = array();
                
                $startYear = $transitionRule->from->format('Y');
                // NOTE: buggy clients such as lightning always start 1970 (start of unix timestamp)
                //       we can't take those transitions
                if (! in_array($startYear, array('1970', '1601')) || count($transitionRules) != 2) {
                    $transitionDates[] = $transitionRule->computeTransition($startYear);
                    
//                         for ($i=1;$i<20;$i++) {
//                             $transitionDates[] = $transitionRule->computeTransition($startYear + $i);
//                         }
                }
                
                $until = $transitionRule->until ? $transitionRule->until : new DateTime('now', new DateTimeZone('UTC'));
                $transitionDates[] = $transitionRule->computeTransition($until->format('Y'));
//                 if (! $transitionRule->until) {
//                     for ($i=1;$i<10;$i++) {
//                         $transitionDates[] = $transitionRule->computeTransition($until->format('Y') + $i);
//                     }
//                 }
            } else {
                // for rules having rdates/no ruule, take all rdates/dtstart
                $transitionDates = $transitionRule->getTransitionDates();
            }
            
            // create transitions
            foreach($transitionDates as $transitionDate) {
                $transitions->addModel(new TimeZoneConvert_Transition(array(
                    'ts'     => $transitionDate->getTimestamp(),
                    'date'   => $transitionDate->format(DateTime::ISO8601),
                    'offset' => $transitionRule->offset,
                    'isdst'  => $transitionRule->isdst,
                    'abbr'   => $transitionRule->abbr,
                )));
            }
        }
        
        return $transitions;
    }
    
    /**
     * get all transition rules from given vtimezone
     * 
     * @param string $VTimeZone
     * @return array of TimeZoneConvert_TransitionRule
     */
    public function getTransitionRules($VTimeZone)
    {
        $transitionRules = array();
        preg_match_all('/BEGIN:(?:STANDARD|DAYLIGHT)(?:.|\s)*END:(?:STANDARD|DAYLIGHT)/U', $VTimeZone, $vTransitionRules);
        foreach((array) $vTransitionRules[0] as $vTransitionRule) {
            $transitionRules[] = $this->vTransitionRuleToTransitionRule($vTransitionRule);
        }
        
        return $transitionRules;
    }
    
    /**
     * convert a single vTransitionRule
     * 
     * @param  string $vTransitionRule
     * @return TimeZoneConvert_TransitionRule
     */
    public function vTransitionRuleToTransitionRule($vTransitionRule)
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
            $invertedOffsetFromSign = $offsetFrom[1][0] == '-' ? '+' : '-';
            $offsetFromSeconds = substr($offsetFrom[1], -4, -2) * 3600 + substr($offsetFrom[1], -2) * 60;
            
            $dtstart->modify("$invertedOffsetFromSign  $offsetFromSeconds seconds");
            
            // parse TZOFFSETTO
            if (preg_match('/TZOFFSETTO:(.*)/', $vTransitionRule, $offsetTo)) {
                $offsetToSign = $offsetTo[1][0] == '-' ? '-' : '+';
                $offsetToSeconds = substr($offsetTo[1], -4, -2) * 3600 + substr($offsetTo[1], -2) * 60;
            }
            $offsetTo = $offsetTo ? ($offsetToSign . '1') * $offsetToSeconds : 0;
            
            // parse TZNAME
            $abbr = preg_match('/TZNAME:(.*)/', $vTransitionRule, $abbr) ? $abbr[1] : '';
            $isdst = (bool) preg_match('/BEGIN:DAYLIGHT/', $vTransitionRule);
            
            $transitionRule = new TimeZoneConvert_TransitionRule(array(
                'from'   => $dtstart,
                'offset' => $offsetTo,
                'isdst'  => $isdst,
                'abbr'   => $abbr,
            ));
            
            // transitions by RRULE 
            if (preg_match('/RRULE:(.*)/', $vTransitionRule, $rrule)) {
                $rrule = TimeZoneConvert_VTimeZone_Rrule::createFromString($rrule[1]);
                $transitionRule->append(array(
                    'hour'   => (int) $dtstart->format('G'),
                    'minute' => (int) $dtstart->format('i'),
                    'second' => (int) $dtstart->format('s'),
                    'month'  => $rrule->month,
                    'wkday'  => $rrule->wkday,
                    'numwk'  => $rrule->numwk,
                    'until'  => $rrule->until,
                ));
            } 
            
            // transitions by RDATE
            else if (preg_match('/RDATE.*:(?:\d{8}T\d{6}[^0-9A-Z]*)+/', $vTransitionRule, $rdate)) {
                preg_match_all('/(?:\d{8}T\d{6}[^0-9A-Z]*)+/U', $rdate[0], $rdates);
                foreach((array) $rdates[0] as $transitionDateString) {
                    $transitionDate = new DateTime($transitionDateString, new DateTimeZone('UTC'));
//                     echo $offsetFrom . "\n";
                    $transitionDate->modify("$invertedOffsetFromSign  $offsetFromSeconds seconds");
                    $transitionRule->addTransitionDate($transitionDate);
                }
            }
            
            // single transition
            else {
                $transitionRule->until = $dtstart;
                $transitionRule->addTransitionDate($dtstart);
            }
            
            return $transitionRule;
    }
}