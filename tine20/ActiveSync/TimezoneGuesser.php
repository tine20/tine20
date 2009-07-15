<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @version     $Id$
 */

/**
 * ActiveSync Timezone Guesser
 * 
 * Guesses timezones (e.g. "Europe/Berlin") matching to given 
 * offsets relative to Coordinated UNiversal Time UTC
 * 
 * @example
 * $timezoneGuesser = new ActiveSync_TimezoneGuesser(array(                                                                                                                            
 *    'bias' => -60,
 *    'standardName' => null,
 *    'standardYear' => 0,
 *    'standardMonth' => 10,
 *    'standardDayOfWeek' => 0,
 *    'standardDay' => 5,
 *    'standardHour' => 3,
 *    'standardMinute' => 0,
 *    'standardSecond' => 0,
 *    'standardMilliseconds' => 0,
 *    'standardBias' => 0,
 *    'daylightName' => null,
 *    'daylightYear' => 0,
 *    'daylightMonth' => 3,
 *    'daylightDayOfWeek' => 0,
 *    'daylightDay' => 5,
 *    'daylightHour' => 2,
 *    'daylightMinute' => 0,
 *    'daylightSecond' => 0,
 *    'daylightMilliseconds' => 0,
 *    'daylightBias' => -60                                                         
 *	));
 *	$matchingTimezones = $timezoneGuesser->guessTimezones();
 *
 *  This will return an array containing all the timezones belonging to 
 *  CEST/CET (UTC/GMT +2 hours), including "Europe/Berlin"
 * 
 */
class ActiveSync_TimezoneGuesser {

	protected $_startDate          = array();
	protected $_offsets            = array();
	
	/**
	 * If set then timezone guessing will end when the expected timezon was matched and the expected timezone
	 * will be the first entry in the returned array of matching timezones.
	 * 
	 * This can speedup the timezone guessing process.
	 * 
	 * @var String
	 */
	protected $_expectedTimezone = null;
	
	protected $_logLevel = 0;
    
	/**
	 * If set then the timezone guessing results will be cached.
	 * This is strongly recommended for performance reasons.
	 * 
	 * @var Zend_CacheCore
	 */
    protected $_cache = null;
	
    /**
     * Construct TimezoneGUesser instance and optionally set object properties {@see $_offsets} and {@see $_startDate}.
     * 
     * @param String | array    $_offsets [{@see _setOffsets()}] 
     * @param String | int      $_startDate * @param string | array $_offsets [{@see _setStartDate()}]
     * @return unknown_type
     */
	public function __construct()
	{
	}
	
	/**
	 * {@see $_expectedTimezone}
	 * 
	 * @param String $_value
	 * @return void
	 */
	public function setExpectedTimezone($_value)
	{
		$this->_expectedTimezone = $_value;
	}
    
	/**
	 * {@see $_cache}
	 * 
	 * @param $_value
	 * @return void
	 */
    public function setCache($_value) {
        $this->_cache = $_value;
    }
	
    /**
     * Unpacks {@param $_packedTimezoneInfo} using {@see unpackTimezoneInfo} and then
     * calls {@see getTimezonesForOffsets} with the unpacked timezone info
     * 
     * @param String $_packedTimezoneInfo
     * @return array
     * 
     */
    public function getTimezonesForPackedTimezoneInfo($_packedTimezoneInfo)
    {
    	$offsets = $this->_unpackTimezoneInfo($_packedTimezoneInfo);
    	return $this->getTimezonesForOffsets($offsets);
    }
    
    /**
     * Returns an array of timezones that match to the {@param $_offsets}
     * 
     * If {@see $_expectedTimezone} is set then the method will terminate as soon
     * as the expected timezone has matched and the expected timezone will be the 
     * first entry fo the returned array.
     * 
     * @return array
     */
	public function getTimezonesForOffsets($_offsets)
	{
		$this->_setOffsets($_offsets);
		$this->_setDefaultStartDateIfEmpty();
		
        $cacheId = $this->_getCacheId(array(__FUNCTION__));        
        if ($this->_cache && false !== ($matchingTimezones = $this->_cache->load($cacheId)))
        {
        	return $matchingTimezones;
        }
        
        $matchingTimezones = array();
        $checkWithoutDST = empty($this->_offsets['daylightMonth']);
	    foreach (DateTimeZone::listIdentifiers() as $timezoneIdentifier) {
	    	$this->_log("Parsing timezone $timezoneIdentifier", 7);
	    	$timezone = new DateTimeZone($timezoneIdentifier);
	    	if (($checkWithoutDST && $this->_checkTimezoneWithoutDST($timezone)) || 
	    	   (!$checkWithoutDST && $this->_checkTimezoneWithDST($timezone))) {
	    		if ($this->_isExpectedTimezone($timezoneIdentifier)) {
                    array_unshift($matchingTimezones, $timezoneIdentifier);
                    break;
                } else {
                    $matchingTimezones[] = $timezoneIdentifier;
                }
	    	}
	    }
	    $this->_log('Matching timezones for '.print_r($this->_offsets, true) . ': ' . print_r($matchingTimezones, true), 5);
	    if ($this->_cache) {
	    	$this->_cache->save($matchingTimezones, $cacheId);
	    }
	    
	    return $matchingTimezones;
	}
	
	/**
	 * Return packed string for given {@param $_timezone}
	 * @param String               $_timezone
	 * @param String | int | null  $_startDate
	 * @return String
	 */
	public function getPackedTimezoneInfoForTimezone($_timezone, $_startDate = null)
	{
		$offsets = $this->getOffsetsForTimezone($_timezone, $_startDate);
		return $this->_packTimezoneInfo($offsets);
	}
	
	public function getOffsetsForTimezone($_timezone, $_startDate = null)
	{
        $this->_setStartDate($_startDate);
        
	    $cacheId = $this->_getCacheId(array(__FUNCTION__, $_timezone));        
        if ($this->_cache && false !== ($offsets = $this->_cache->load($cacheId)))
        {
            return $offsets;
        }
        
        $offsets = array(
            'bias' => 0,
            'standardName' => '',
            'standardYear' => 0,
            'standardMonth' => 0,
            'standardDayOfWeek' => 0,
            'standardDay' => 0,
            'standardHour' => 0,
            'standardMinute' => 0,
            'standardSecond' => 0,
            'standardMilliseconds' => 0,
            'standardBias' => 0,
            'daylightName' => '',
            'daylightYear' => 0,
            'daylightMonth' => 0,
            'daylightDayOfWeek' => 0,
            'daylightDay' => 0,
            'daylightHour' => 0,
            'daylightMinute' => 0,
            'daylightSecond' => 0,
            'daylightMilliseconds' => 0,
            'daylightBias' => 0                                                         
        );

        $timezone = new DateTimeZone($_timezone);
        list($standardTransition, $daylightTransition) = $this->_getTransitionsForTimezoneAndYear($timezone, $this->_startDate['year']);
        
        if ($standardTransition) {
	        $offsets['bias'] = $standardTransition['offset']/60*-1;
	        if ($daylightTransition) {          
	            $offsets = $this->_generateOffsetsForTransition($offsets, $standardTransition, 'standard');
	            $offsets = $this->_generateOffsetsForTransition($offsets, $daylightTransition, 'daylight');
	            $offsets['standardHour']    += $daylightTransition['offset']/3600;
	            $offsets['daylightHour']    += $standardTransition['offset']/3600;
	            
	            //@todo how do we get the standardBias (is usually 0)?
	            //$offsets['standardBias'] = ...
	            
	            $offsets['daylightBias'] = ($daylightTransition['offset'] - $standardTransition['offset'])/60*-1;
	        }	
        }
        
	    if ($this->_cache) {
            $this->_cache->save($offsets, $cacheId);
        }

        return $offsets;
	}

	
	/**
	 * 
	 * 
	 * @param array $_offsets
	 * @param array $_transition
	 * @param String $_type
	 * @return array
	 */
	protected function _generateOffsetsForTransition(Array $_offsets, Array $_transition, $_type) 
	{
	    $transitionDateParsed = getdate($_transition['ts']);
            
        $_offsets[$_type . 'Month']      = $transitionDateParsed['mon'];
        $_offsets[$_type . 'DayOfWeek']  = $transitionDateParsed['wday'];
        $_offsets[$_type . 'Minute']     = $transitionDateParsed['minutes'];
        $_offsets[$_type . 'Hour']       = $transitionDateParsed['hours'];
        
        for ($i=5; $i>0; $i--) {
            if ($this->_isNthOcurrenceOfWeekdayInMonth($_transition['ts'], $i)) {
                $_offsets[$_type . 'Day'] = $i;
                break;
            };
        }
        
        return $_offsets;
	}

    /**
     * Test if the weekday of the given {@param $timestamp} is the {@param $_occurence}th occurence of this weekday within its month.
     * 
     * @param int $_timestamp
     * @param int $_occurence [1 to 5, where 5 indicates the final occurrence during the month if that day of the week does not occur 5 times]
     * @return bool
     */
    protected function _isNthOcurrenceOfWeekdayInMonth($_timestamp, $_occurence)
    {
       $original = new Zend_Date($_timestamp, 'UTC');
       $this->_log(__FUNCTION__.": Check if weekday of $original is the {$_occurence}th occurence in its month", 7);
       $modified = clone($original);
       if ($_occurence == 5) {
        $modified->addWeek(1);
        return $modified->compareMonth($original) === 1; //modified month is later than original
       }
       else {
         $modified->subWeek($_occurence);
         $modified2 = clone($original);
         $modified2->subWeek($_occurence-1);
         return $modified->compareMonth($original) === -1 && //modified month is earlier than original
                $modified2->compareMonth($original) === 0; //months are equal
       }
    }
    
    /**
     * Check if the given {@param $_standardTransition} and {@param $_daylightTransition}
     * match to the object property {@see $_offsets}
     * 
     * @param Array $standardTransition
     * @param Array $daylightTransition
     * 
     * @return bool
     */
    protected function _checkTransition($_standardTransition, $_daylightTransition)
    {
        if (empty($_standardTransition) || empty($_daylightTransition)) {
        	$this->_log(__FUNCTION__ . ': One of the parameters $_standardTransition/$_daylightTransition is missing');
        	return false;
        }

        $standardBias = ($_standardTransition['offset']/60)*-1;
            
        //check each condition in a single if statement and break the chain when one condition is not met - for performance reasons            
        if ($standardBias == ($this->_offsets['bias']+$this->_offsets['standardBias']) ) {
            
            $daylightBias = ($_daylightTransition['offset']/60)*-1 - $standardBias;
            if ($daylightBias == $this->_offsets['daylightBias']) {
                
                $standardParsed = getdate($_standardTransition['ts']);
                $daylightParsed = getdate($_daylightTransition['ts']);

                $this->_log('$daylightParsed: '.print_r($daylightParsed, 1), 7);
                $this->_log('offsets: '.print_r($this->_offsets, 1), 7);
                if ($standardParsed['mon'] == $this->_offsets['standardMonth'] && 
                    $daylightParsed['mon'] == $this->_offsets['daylightMonth'] &&
                    $standardParsed['wday'] == $this->_offsets['standardDayOfWeek'] &&
                    $daylightParsed['wday'] == $this->_offsets['daylightDayOfWeek'] ) 
                    {
                        return $this->_isNthOcurrenceOfWeekdayInMonth($_daylightTransition['ts'], $this->_offsets['daylightDay']) &&
                               $this->_isNthOcurrenceOfWeekdayInMonth($_standardTransition['ts'], $this->_offsets['standardDay']);
                }
            }
        }
        return false;
    }
	
    /**
     * decode timezone info from activesync
     * 
     * @param string $_packedTimezoneInfo the packed timezone info
     * @return array
     */
    protected function _unpackTimezoneInfo($_packedTimezoneInfo)
    {
        $timezoneUnpackString = 'lbias/a64standardName/vstandardYear/vstandardMonth/vstandardDayOfWeek/vstandardDay/vstandardHour/vstandardMinute/vstandardSecond/vstandardMilliseconds/lstandardBias/a64daylightName/vdaylightYear/vdaylightMonth/vdaylightDayOfWeek/vdaylightDay/vdaylightHour/vdaylightMinute/vdaylightSecond/vdaylightMilliseconds/ldaylightBias';

        $timezoneInfo = unpack($timezoneUnpackString, base64_decode($_packedTimezoneInfo));
        
        return $timezoneInfo;
    }
    
    /**
     * encode timezone info to activesync
     * 
     * @param array $_timezoneInfo
     * @return string
     */
    protected function _packTimezoneInfo($_timezoneInfo) {
        
        $packed = pack(
            "la64vvvvvvvvla64vvvvvvvvl",
            $_timezoneInfo["bias"], 
            $_timezoneInfo["standardName"], 
            $_timezoneInfo['standardYear'],
            $_timezoneInfo["standardMonth"], 
            $_timezoneInfo['standardDayOfWeek'],
            $_timezoneInfo["standardDay"], 
            $_timezoneInfo["standardHour"], 
            $_timezoneInfo["standardMinute"], 
            $_timezoneInfo['standardSecond'],
            $_timezoneInfo['standardMilliseconds'],
            $_timezoneInfo["standardBias"], 
            $_timezoneInfo["daylightName"], 
            $_timezoneInfo['daylightYear'],
            $_timezoneInfo["daylightMonth"], 
            $_timezoneInfo['daylightDayOfWeek'],
            $_timezoneInfo["daylightDay"], 
            $_timezoneInfo["daylightHour"], 
            $_timezoneInfo["daylightMinute"], 
            $_timezoneInfo['daylightSecond'],
            $_timezoneInfo['daylightMilliseconds'],
            $_timezoneInfo["daylightBias"] 
        );

        return base64_encode($packed);
    }
    
    /**
     * Validate and set offsets
     * 
     * @param array|string $_value [if a string is provided then it will be unpacked using {@see unpackTimezoneInfo}]
     * 
     * @return unknown_type
     */
    protected function _setOffsets($_value)
    {       
        //validate $_value
        if ((!empty($_value['standardMonth']) || !empty($_value['standardDay']) || !empty($_value['daylightMonth']) || !empty($_value['daylightDay'])) &&
            (empty($_value['standardMonth']) || empty($_value['standardDay']) || empty($_value['daylightMonth']) || empty($_value['daylightDay']))       
            ) {
              throw new Tinebase_Exception_InvalidArgument('It is not possible not set standard offsets without setting daylight offsets and vice versa');
        }

        $this->_offsets = $_value;
    }
    
    /**
     * Parse and set object property {@see $_startDate}
     * 
     * @param String | int      $_startDate
     * @return void
     */
    protected function _setStartDate($_startDate)
    {
        if (empty($_startDate)) {
            $this->_setDefaultStartDateIfEmpty();
            return;
        }

        $startDateParsed = array();
        if (is_string($_startDate)) {
            $startDateParsed['string'] = $_startDate;
            $startDateParsed['ts']     = strtotime($_startDate);
        } elseif (is_int($_startDate)) {
            $startDateParsed['ts']     = $_startDate;
            $startDateParsed['string'] = strftime('%F', $_startDate);
        }
        else {
            throw new Tinebase_Exception_InvalidArgument('$startDate Parameter should be either a Timestamp or a Datestring parseable by strtotime.');
        }
        $startDateParsed['object'] = new DateTime($startDateParsed['string']);
        
        $startDateParsed = array_merge($startDateParsed, getdate($startDateParsed['ts']));
        
        $this->_startDate = $startDateParsed;
    }
    
    /**
     * Set default value for object property {@see $_startdate} if it is not set yet.
     * Tries to guewss the correct startDate depending on object property {@see $_offsets} and
     * falls back to current date. 
     *  
     * @return void
     */
    protected function _setDefaultStartDateIfEmpty()
    {
        if (!empty($this->_startDate)) {
            return;
        }
        
        if (!empty($this->_offsets['standardYear'])) {
            $this->_setStartDate($this->_offsets['standardYear'].'-01-01');
        }
        else {
            $this->_setStartDate(time());
        }
    }
    
    /**
     * Returns true if {@param $_value} equals object property {@see $_expectedTimezone}
     * 
     * @param String $_value
     * @return bool
     */
    protected function _isExpectedTimezone($_value)
    {
        if ($_value === $this->_expectedTimezone) {
           return true;
       }
    }

    /**
     * Print out log messages
     * 
     * @todo allow to set a Logger object and use that object instead of this pretty dull log method
     *   
     * @param String $_message
     * @param int $_level
     * @return void
     */
    protected function _log($_message, $_level = 7)
    {
       if ($_level <= $this->_logLevel) {
            echo "\n$_message";         
       }
    }
    
    /**
     * Check if the given {@param $_timezone} matches the {@see $_offsets}
     * without checking the daylight saving time transitions
     * 
     * @param DateTimeZone $_timezone
     * @return void
     */
    protected function _checkTimezoneWithoutDST(DateTimeZone $_timezone)
    {
        if (empty($this->_offsets)) {
            throw new Tinebase_Exception('Missing object property _offsets');
        }

        $this->_log(__FUNCTION__.' - '.$_timezone->getName(), 7);
        $bias = ($_timezone->getOffset($this->_startDate['object'])/60)*-1;
        if ($bias == $this->_offsets['bias']) {
            return true;
        }
        return false;        
    }

    /**
     * Check if the given {@param $_timezone} matches the {@see $_offsets}
     * and also evaluate the daylight saving time transitions for this timezone.
     * 
     * @param DateTimeZone $_timezone
     * @return void
     */
    protected function _checkTimezoneWithDST(DateTimeZone $_timezone) 
    {
        list($standardTransition, $daylightTransition) = $this->_getTransitionsForTimezoneAndYear($_timezone, $this->_startDate['year']);
        return $this->_checkTransition($standardTransition, $daylightTransition);
    }
    
    /**
     * Returns the standard and daylight transitions for the given {@param $_timezone}
     * and {@param $_year}.
     * 
     * @param $_timezone
     * @param $_startDate
     * @return Array
     */
    protected function _getTransitionsForTimezoneAndYear($_timezone, $_year)
    {
        $standardTransition = null;
        $daylightTransition = null;
        
        //@todo Since php version 3.3 getTransitions accepts optional start and end parameters.
        //      Using them would probably result in a performance gain.
        $transitions = $_timezone->getTransitions();
        $index = 0; //we need to access index counter outside of the foreach loop
        $transition = array(); //we need to access the transition counter outside of the foreach loop
        foreach ($transitions as $index => $transition) {
            if (strftime('%Y', $transition['ts']) == $_year) {
                if (isset($transitions[$index+1]) && strftime('%Y', $transition['ts']) == strftime('%Y', $transitions[$index+1]['ts'])) {
                    $daylightTransition = $transition['isdst'] ? $transition : $transitions[$index+1];
                    $standardTransition = $transition['isdst'] ? $transitions[$index+1] : $transition;
                } else {
                    $daylightTransition = $transition['isdst'] ? $transition : null;
                    $standardTransition = $transition['isdst'] ? null : $transition;
                }
                break;
            }
            elseif ($index == count($transitions) -1) {
                $standardTransition = $transition;
            }
        }
         
        return array($standardTransition, $daylightTransition);
    }
    
    protected function _getCacheId($additionlIdParams = array())
    {
        return 'ActiveSync_TimezoneGuesser_' . md5(serialize(array($additionlIdParams, $this->_expectedTimezone, $this->_offsets, $this->_startDate)));
    }
	
}
