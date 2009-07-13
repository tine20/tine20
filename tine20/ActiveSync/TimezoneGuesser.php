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
 * @author      
 * @version     
 */

/**
 * @todo assure that the timestamps are generated in UTC!
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
	protected $_expectedTimezone   = null;
	
	protected $_logLevel           = 0;
    
	/**
	 * If set then the timezone guessing results will be cached
	 * 
	 * @var Zend_CacheCore
	 */
    protected $_cache              = null;
	
	public function __construct($_startDate = null, $_offsets = null)
	{
		$this->reset($_startDate, $_offsets);
	}
	
	public function reset($_startDate = null, $_offsets = null)
	{
		$this->_matchingTimezones = array();
		$this->_setStartDate($_startDate);
        $this->_setOffsets($_offsets);
	}
	
	public function setExpectedTimezone($_value)
	{
		$this->_expectedTimezone = $_value;
	}
    
    public function setCache($_value) {
        $this->_cache = $_value;
    }
	
	protected function _isExpectedTimezone($_value)
	{
		if ($_value === $this->_expectedTimezone) {
			return true;
		}
	}
	
	protected function _log($_message, $_level = 7)
	{
		if ($_level <= $this->_logLevel) {
            echo "\n$_message";			
		}
	}
	
	protected function _setStartDate($_startDate)
	{
        if (empty($_startDate)) {
            $this->_startDate = null;
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
	
	protected function _setOffsets($_offsets)
	{
		$this->_offsets = $_offsets;
	}
	
	protected function _checkTimezoneWithoutDST($_timezone)
	{
        if (empty($this->_offsets)) {
            throw new Tinebase_Exception('Missing object property _offsets');
        }

		$this->_log(__FUNCTION__.' - '.$_timezone->getName(), 7);
		if (empty($this->_offsets['daylightDay'])) { //only check without DST when no DST offset is specified
			$bias = ($_timezone->getOffset($this->_startDate['object'])/60)*-1;
	        if ($bias == $this->_offsets['bias']) {
	            return true;
	        }
		}
		return false;        
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
	
    protected function _checkTransition($standardTransition, $daylightTransition)
    {
    	$this->_log(__FUNCTION__.': Check regardless of the specified year', 7);
        $bias = ($standardTransition['offset']/60)*-1;
            
        //check each condition in a single if statement and break the chain when one condition is not met - for performance reasons            
        if ($bias == $this->_offsets['bias'] ) {
        	
            $daylightBias = ($daylightTransition['offset']/60)*-1 - $bias;
            if ($daylightBias == $this->_offsets['daylightBias']) {
            	
                $standardParsed = getdate($standardTransition['ts']);
                $daylightParsed = getdate($daylightTransition['ts']);

                $this->_log('$daylightParsed: '.print_r($daylightParsed, 1), 7);
                $this->_log('offsets: '.print_r($this->_offsets, 1), 7);
                if ($standardParsed['mon'] == $this->_offsets['standardMonth'] && 
                    $daylightParsed['mon'] == $this->_offsets['daylightMonth'] &&
                    $standardParsed['wday'] == $this->_offsets['standardDayOfWeek'] &&
                    $daylightParsed['wday'] == $this->_offsets['daylightDayOfWeek'] ) 
                    {
//                    	return true;
                        return $this->_isNthOcurrenceOfWeekdayInMonth($daylightTransition['ts'], $this->_offsets['daylightDay']) &&
                               $this->_isNthOcurrenceOfWeekdayInMonth($standardTransition['ts'], $this->_offsets['standardDay']);
                }
            }
        }
        return false;
    }
	
	protected function _checkTimezoneWithDST($_timezone) 
	{
        if (empty($this->_startDate) || empty($this->_offsets)) {
            throw new Tinebase_Exception('Missing object properties _startDate and/or _offsets');
        }
        
        $this->_log(__FUNCTION__.': '.$_timezone->getName(), 7);
        //@todo Since php version 3.3 getTransitions accepts optional start and end parameters.
        //      Using them would probably result in a performance gain.
        $transitions = $_timezone->getTransitions();
        if(count($transitions) > 1) {
	        if (empty($this->_offsets['standardYear'])) {
	        	//DST changes every year with the the same rules so we only have to check one transition
	        	//we check the last transition because sometimes the timezone transition behaviour changed over the time
	        	$this->_log(__FUNCTION__.': Check transitionregardless of the specified reference year', 7);
	        	
	            $lastTransition = $transitions[count($transitions)-1];
	          	$daylightTransition = $lastTransition['isdst'] ? $lastTransition : $transitions[count($transitions)-2];
	            $standardTransition = $lastTransition['isdst'] ? $transitions[count($transitions)-2] : $lastTransition;
	            return $this->_checkTransition($standardTransition, $daylightTransition);
	        }
	        else {
	        	//The specified offsets for DST change are only valid for the reference year given with {@see $this->_startDate}
	        	$this->_log(__FUNCTION__.': Check transition regarding the specified reference year', 7);
		        foreach ($transitions as $index => $transition) {
			        if (strftime('%Y', $transition['ts']) == $this->_startDate['year']) {
			            $this->_log('Found transition', 7);
			            if (isset($transitions[$index+1]) && strftime('%Y', $transition['ts']) == strftime('%Y', $transitions[$index+1]['ts'])) {
			                $daylightTransition = $transition['isdst'] ? $transition : $transitions[$index+1];
			                $standardTransition = $transition['isdst'] ? $transitions[$index+1] : $transition;
				            if ($this->_checkTransition($standardTransition, $daylightTransition)) {
	                            return true;       
	                        }
			            } else {
			                $this->_log(__FUNCTION__.": ##### Skipping timezone {$_timezone->getName()}", 3);
			            }
			        }
		        }        	
	        }
        }
        
        return false;
    }                  
	
	public function guessTimezones()
	{
        $matchingTimezones = array();
        $checkWithoutDST = empty($this->_offsets['daylightMonth']);
        
	    foreach (DateTimeZone::listIdentifiers() as $timezoneIdentifier) {
	    	$timezone = new DateTimeZone($timezoneIdentifier);
	    	if (($checkWithoutDST && $this->_checkTimezoneWithoutDST($timezone)) || (!$checkWithoutDST && $this->_checkTimezoneWithDST($timezone))) {
	    		if ($this->_isExpectedTimezone($timezoneIdentifier)) {
                    array_unshift($matchingTimezones, $timezoneIdentifier);
                    break;
                } else {
                    $matchingTimezones[] = $timezoneIdentifier;
                }
	    	}
	    }
	    $this->_log('Matching timezones for '.print_r($this->_offsets, true) . ': ' . print_r($matchingTimezones, true), 5);
	    return $matchingTimezones;
	}
	
    /**
     * decode timezone info from activesync
     * 
     * @param string $_packedTimezoneInfo the packed timezone info
     * @return array
     */
    public static function unpackTimezoneInfo($_packedTimezoneInfo)
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
    public static function packTimezoneInfo($_timezoneInfo) {
        
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
	
}

?>

