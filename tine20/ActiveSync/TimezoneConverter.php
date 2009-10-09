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
 * ActiveSync Timezone Converter
 * 
 * Guesses timezones (e.g. "Europe/Berlin") matching to given 
 * offsets relative to Coordinated Universal Time UTC.
 * 
 * Or generates offsets relative to Coordinated Universal Time UTC for a given timezone
 * 
 * @example
 * $timezoneGuesser = new ActiveSync_TimezoneGuesser();
 * $matchingTimezones = $timezoneGuesser->getTimezonesForOffsets(array(                                                                                                                            
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
 *
 *  This will return an array containing all the timezones belonging to 
 *  CEST/CET (UTC/GMT +2 hours), including "Europe/Berlin"
 * 
 */
class ActiveSync_TimezoneConverter 
{
    /**
     * holds the instance of the singleton
     *
     * @var ActiveSync_Controller
     */
    private static $_instance = NULL;
    
    protected $_startDate          = array();
		    
	/**
	 * If set then the timezone guessing results will be cached.
	 * This is strongly recommended for performance reasons.
	 * 
	 * @var Zend_CacheCore
	 */
    protected $_cache = null;
    
    /**
     * If set then the log messages will be sent to this logger object
     * 
     * @var Zend_Log
     */
    protected $_logger = null;
	    
    /**
     * array of offsets know by ActiceSync clients, but unknown by php
     * @var array
     */
    protected $_knownTimezones = array(
        '0AIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==' => array(
            'Pacific/Kwajalein' => 'MHT'
        )
    );
    
    /**
     * don't use the constructor. Use the singleton.
     * 
     * @param $_logger
     */
    private function __construct($_logger = null, $_cache = null)
    {
        if($_logger instanceof Zend_Log) {
            $this->setLogger($_logger);
        }
        
        if($_cache instanceof Zend_Cache_Core) {
            $this->setCache($_cache);
        }
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * the singleton pattern
     *
     * @return ActiveSync_TimezoneConverter
     */
    public static function getInstance($_logger = null, $_cache = null) 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new ActiveSync_TimezoneConverter($_logger, $_cache);
        }
        
        return self::$_instance;
    }

	/**
	 * {@see $_cache}
	 * 
	 * @param Zend_Cache_Core | null $_cache
	 * @return void
	 */
    public function setCache($_cache) {
    	if (!(is_null($_cache) || $_cache instanceof Zend_Cache_Core)) {
    		throw new ActiveSync_Exception('Invalid argument: $_cache has to be either an instance of Zend-Cache_Core or null');
    	}
        $this->_cache = $_cache;
    }
    
    /**
     * {@see $_cache}
     * 
     * @param Zend_Log $_logger
     * @return void
     */
    public function setLogger(Zend_Log $_logger) {
        $this->_logger = $_logger;
    }
	
    /**
     * Returns an array of timezones that match to the {@param $_offsets}
     * 
     * If {@see $_expectedTimezone} is set then the method will terminate as soon
     * as the expected timezone has matched and the expected timezone will be the 
     * first entry fo the returned array.
     * 
     * @param String | array $_offsets
     * @param String | optional $_expectedTimezone
     * @return array
     * 
     */
    public function getListOfTimezones($_offsets, $_expectedTimezone = null)
    {
        if (is_string($_offsets) && isset($this->_knownTimezones[$_offsets])) {
            $this->_log(__METHOD__, __LINE__, 'use internal hash table');
            $timezones = $this->_knownTimezones[$_offsets];
        } else {
            
			if (is_string($_offsets)) {
	            // unpack timezone info to array
	            $_offsets = $this->_unpackTimezoneInfo($_offsets);
	        }
	        
	        $this->_validateOffsets($_offsets);
	        $this->_setDefaultStartDateIfEmpty($_offsets);
	        
	        // don't use __METHOD__ ":" is not allowed as cache identifier
	        $cacheId = $this->_getCacheId(__CLASS__ . __FUNCTION__, $_offsets);     
	           
	        if (false === ($timezones = $this->_loadFromCache($cacheId))) {        
	            $timezones = array();
	            foreach (DateTimeZone::listIdentifiers() as $timezoneIdentifier) {
	                $timezone = new DateTimeZone($timezoneIdentifier);
	                if (false !== ($matchingTransition = $this->_checkTimezone($timezone, $_offsets))) {
	                    if ($timezoneIdentifier === $_expectedTimezone) {
	                        $timezones = array($timezoneIdentifier => $matchingTransition['abbr']);
	                        break;
	                    } else {
	                        $timezones[$timezoneIdentifier] = $matchingTransition['abbr'];
	                    }
	                }
	            }
	            $this->_saveInCache($timezones, $cacheId);
	        }
        }
        
        $this->_log(__METHOD__, __LINE__, 'Matching timezones: '.print_r($timezones, true));
            
        if (empty($timezones)) {
            throw new ActiveSync_TimezoneNotFoundException('No timezone found for the given offsets');
        }
    
        return $timezones;
    }
    
    /**
     * Returns a timezone abbreviation (e.g. CET, MST etc.) that matches to the {@param $_offsets}
     * 
     * If {@see $_expectedTimezone} is set then the method will return this timezone if it matches.
     *
     * @param String | array $_offsets
     * @return String [timezone abbreviation e.g. CET, MST etc.]
     */
    public function getTimezone($_offsets, $_expectedTimezone = null)
    {
        $timezones = $this->getListOfTimezones($_offsets, $_expectedTimezone);

        if(isset($timezones[$_expectedTimezone])) {
            return $_expectedTimezone; 
        } else {
            return current($timezones);
        }
    }
    
    
    /**
     * Unpacks {@param $_packedTimezoneInfo} using {@see unpackTimezoneInfo} and then
     * calls {@see getTimezoneForOffsets} with the unpacked timezone info
     * 
     * @param String $_packedTimezoneInfo
     * @return String [timezone abbreviation e.g. CET, MST etc.]
     * 
     */
//    public function getTimezoneForPackedTimezoneInfo($_packedTimezoneInfo)
//    {
//        $offsets = $this->_unpackTimezoneInfo($_packedTimezoneInfo);
//        $matchingTimezones = $this->getTimezoneForOffsets($offsets);
//        $maxMatches = 0;
//        $matchingAbbr = null;
//        foreach ($matchingTimezones as $abbr => $timezones) {
//        	if (count($timezones) > $maxMatches) {
//        		$maxMatches = count($timezones);
//        		$matchingAbbr = $abbr;
//        	}
//        }
//        return $matchingAbbr;
//    }
    
    
	/**
	 * Return packed string for given {@param $_timezone}
	 * @param String               $_timezone
	 * @param String | int | null  $_startDate
	 * @return String
	 */
	public function encodeTimezone($_timezone, $_startDate = null)
	{
		foreach ($this->_knownTimezones as $packedString => $knownTimezone) {
			if (array_key_exists($_timezone, $knownTimezone)) {		
				return $packedString;
			}
		}
		
		$offsets = $this->getOffsetsForTimezone($_timezone, $_startDate);
		return $this->_packTimezoneInfo($offsets);
	}
	
	/**
	 * get offsets for given timezone
	 * 
	 * @param string $_timezone
	 * @param $_startDate
	 * @return array
	 */
	public function getOffsetsForTimezone($_timezone, $_startDate = null)
	{
        $this->_setStartDate($_startDate);
        
        // don't use __METHOD__ ":" is not allowed as cache identifier
	    $cacheId = $this->_getCacheId(__CLASS__ . __FUNCTION__, array($_timezone));

	    if (false === ($offsets = $this->_loadFromCache($cacheId))) {
	        $offsets = $this->_getOffsetsTemplate();
	        
	        try {
	        	$timezone = new DateTimeZone($_timezone);
	        } catch (Exception $e) {
	        	$this->_log(__METHOD__, __LINE__, ": could not instantiate timezone {$_timezone}: {$e->getMessage()}");
	        	return null;
	        }
	        
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
	        
            $this->_saveInCache($offsets, $cacheId);
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
    protected function _checkTransition($_standardTransition, $_daylightTransition, $_offsets)
    {
        if (empty($_standardTransition) || empty($_offsets)) {
        	return false;
        }

        $standardBias = ($_standardTransition['offset']/60)*-1;
               
        //check each condition in a single if statement and break the chain when one condition is not met - for performance reasons            
        if ($standardBias == ($_offsets['bias']+$_offsets['standardBias']) ) {
            
        	if (empty($_offsets['daylightMonth']) && (empty($_daylightTransition) || empty($_daylightTransition['isdst']))) {
        		//No DST
        		return true;
        	}
        	
            $daylightBias = ($_daylightTransition['offset']/60)*-1 - $standardBias;
            if ($daylightBias == $_offsets['daylightBias']) {
                
                $standardParsed = getdate($_standardTransition['ts']);
                $daylightParsed = getdate($_daylightTransition['ts']);

                if ($standardParsed['mon'] == $_offsets['standardMonth'] && 
                    $daylightParsed['mon'] == $_offsets['daylightMonth'] &&
                    $standardParsed['wday'] == $_offsets['standardDayOfWeek'] &&
                    $daylightParsed['wday'] == $_offsets['daylightDayOfWeek'] ) 
                    {
                        return $this->_isNthOcurrenceOfWeekdayInMonth($_daylightTransition['ts'], $_offsets['daylightDay']) &&
                               $this->_isNthOcurrenceOfWeekdayInMonth($_standardTransition['ts'], $_offsets['standardDay']);
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
    protected function _packTimezoneInfo($_timezoneInfo) 
    {
        if (!is_array($_timezoneInfo)) {
        	return null;
        }

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
     * Returns complete offsets array with all fields empty
     * 
     * Used e.g. when reverse-generating ActiveSync Timezone Offset Information
     * based on a given Timezone, {@see getOffsetsForTimezone}
     * 
     * @return unknown_type
     */
    protected function _getOffsetsTemplate()
    {
        return array(
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
    }
    
    /**
     * Validate and set offsets
     * 
     * @param array|string $_value [if a string is provided then it will be unpacked using {@see unpackTimezoneInfo}]
     * 
     * @return unknown_type
     */
    protected function _validateOffsets($_value)
    {       
        //validate $_value
        if ((!empty($_value['standardMonth']) || !empty($_value['standardDay']) || !empty($_value['daylightMonth']) || !empty($_value['daylightDay'])) &&
            (empty($_value['standardMonth']) || empty($_value['standardDay']) || empty($_value['daylightMonth']) || empty($_value['daylightDay']))       
            ) {
              throw new ActiveSync_Exception('It is not possible not set standard offsets without setting daylight offsets and vice versa');
        }
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
     * @param array | null $_offsets [offsets may be avaluated for a given start year]
     * @return void
     */
    protected function _setDefaultStartDateIfEmpty($_offsets = null)
    {
        if (!empty($this->_startDate)) {
            return;
        }
        
        if (!empty($_offsets['standardYear'])) {
            $this->_setStartDate($_offsets['standardYear'].'-01-01');
        }
        else {
            $this->_setStartDate(time());
        }
    }

    /**
     * Check if the given {@param $_timezone} matches the {@see $_offsets}
     * and also evaluate the daylight saving time transitions for this timezone if necessary.
     * 
     * @param DateTimeZone $_timezone
     * @param array $_offsets
     * @return void
     */
    protected function _checkTimezone(DateTimeZone $_timezone, $_offsets) 
    {
    	#$this->_log(__METHOD__, __LINE__, 'Checking for matches with timezone: ' . $_timezone->getName());
        list($standardTransition, $daylightTransition) = $this->_getTransitionsForTimezoneAndYear($_timezone, $this->_startDate['year']);
        if ($this->_checkTransition($standardTransition, $daylightTransition, $_offsets)) {
        	#$this->_log(__METHOD__, __LINE__, 'Matching timezone ' . $_timezone->getName(), 7);
        	#$this->_log(__METHOD__, __LINE__, 'Matching daylight transition ' . print_r($daylightTransition, 1), 7);
        	#$this->_log(__METHOD__, __LINE__, 'Matching standard transition ' . print_r($standardTransition, 1), 7);
        	return $standardTransition;
        }
        
        return false;
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
    
    protected function _getCacheId($_prefix, $_offsets)
    {
        return $_prefix . md5(serialize($_offsets));
    }
    
    protected function _loadFromCache($_id)
    {
	    if ($this->_cache) {
	        $this->_log(__METHOD__, __LINE__, 'found in cache: ' . $_id);
	    	return $this->_cache->load($_id);
	    }
	    return false;
    }
    
    protected function _saveInCache($_value, $_id)
    {
        if ($this->_cache) {
            $this->_cache->save($_value, $_id);
        }
    }
    
    protected function _log($_method, $_line, $_message, $_priority = Zend_Log::DEBUG)
    {
    	if ($this->_logger instanceof Zend_Log) {
    		$this->_logger->log("$_method::$_line $_message", $_priority);
    	}
    }
	
}
