<?php
/**
 * qCal_Time
 * 
 * This class is used to represent a time that is not associated with any specific date.
 * 
 * @package qCal
 * @subpackage qCal_DateTime
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */
class qCal_Time {

	/**
	 * @var integer Timestamp (represents time at GMT, so must have timezone's offset
	 * applied before it will be accurate for your specified timezone)
	 */
	protected $time;
	
	/**
	 * @var string The default format that time is output as
	 */
	protected $format = "H:i:s";
	
	/**
	 * @var qCal_Timezone The timezone
	 */
	protected $timezone;
	
	/**
	 * @var array Time array (contains hour, minute, second, etc.)
	 */
	protected $timeArray = array();
	
	/**
	 * Class constructor
	 * This component is immutable. It can only be created, not modified.
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $second
	 * @param mixed $timezone Either a qCal_Timezone or a string representing one
	 * @param integer $rollover Set this to true if you want to be able to use "rollover" time intervals.
	 * For instance, you could specify 100 seconds, which would translate to 1 minute, 40 seconds.
	 * @access public
	 */
	public function __construct($hour = null, $minute = null, $second = null, $timezone = null, $rollover = null) {
	
		$this->setTimezone($timezone)
			 ->setTime($hour, $minute, $second, $rollover);
	
	}
	/**
	 * Set the time
	 * This class is immutable, so this is protected. Only the constructor calls it.
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $second
	 * @param integer $rollover (see above)
	 * @access protected
	 * @return $this
	 */
	protected function setTime($hour = null, $minute = null, $second = null, $rollover = null) {
	
		if (is_null($hour)) {
			$hour = gmdate("H");
		}
		if (is_null($minute)) {
			$minute = gmdate("i");
		}
		if (is_null($second)) {
			$second = gmdate("s");
		}
		if (is_null($rollover)) $rollover = false;
		if (!$rollover) {
			if ($hour > 23 || $minute > 59 || $second > 59) {
				throw new qCal_DateTime_Exception_InvalidTime(sprintf("Invalid time specified for qCal_Time: \"%02d:%02d:%02d\"", $hour, $minute, $second));
			}
		}
		// since PHP is incapable of storing a time without a date, we use the first day of
		// the unix epoch so that we only have the amount of seconds since the zero of unix epoch
		// we only use gm here because we don't want the server's timezone to interfere
		$time = gmmktime($hour, $minute, $second, 1, 1, 1970);
		$this->time = $time;
		$formatString = "a|A|B|g|G|h|H|i|s|u";
		$keys = explode("|", $formatString);
		$vals = explode("|", gmdate($formatString, $this->getTimestamp(false)));
		$this->timeArray = array_merge($this->timeArray, array_combine($keys, $vals));
		return $this;
	
	}
	
	/**
	 * Set the timezone
	 * @param mixed $timezone Either a qCal_Timezone object or a string representing one
	 * @return $this
	 * @access protected
	 */
	protected function setTimezone($timezone) {
	
		if (is_null($timezone) || !($timezone instanceof qCal_Timezone)) {
			$timezone = qCal_Timezone::factory($timezone);
		}
		$this->timezone = $timezone;
		return $this;
	
	}
	/**
	 * Get the timezone
	 * @return qCal_Timezone
	 * @access public
	 */
	public function getTimezone() {
	
		return $this->timezone;
	
	}
	/**
	 * Generate a qCal_Time object via a string or a number of other methods
	 * @param string A time string to convert into a qCal_Time object (ex: 4:00)
	 * @param mixed Either a qCal_Timezone object or a string representing one
	 * @access public
	 * @static
	 */
	public static function factory($time, $timezone = null) {
	
		if (is_null($timezone) || !($timezone instanceof qCal_Timezone)) {
			$timezone = qCal_Timezone::factory($timezone);
		}
		// get the default timezone so we can set it back to it later
		$tz = date_default_timezone_get();
		// set the timezone to GMT temporarily
		date_default_timezone_set("GMT");
		
		if (is_integer($time)) {
			// @todo Handle timestamps
			// @maybe not...
		}
		if (is_string($time)) {
			if ($time == "now") {
				$time = new qCal_Time(null, null, null, $timezone);
			} else {
				$tstring = "01/01/1970 $time";
				if (!$timestamp = strtotime($tstring)) {
					// if unix timestamp can't be created throw an exception
					throw new qCal_DateTime_Exception_InvalidTime("Invalid or ambiguous time string passed to qCal_Time::factory()");
				}
				list($hour, $minute, $second) = explode(":", gmdate("H:i:s", $timestamp));
				$time = new qCal_Time($hour, $minute, $second, $timezone);
			}
		}
		
		// set the timezone back to what it was
		date_default_timezone_set($tz);
		
		return $time;
	
	}
	/**
	 * Get the hour
	 * @return integer The hour
	 * @access public
	 */
	public function getHour() {
	
		return $this->timeArray['G'];
	
	}
	/**
	 * Get the minute
	 * @return integer The minute
	 * @access public
	 */
	public function getMinute() {
	
		return $this->timeArray['i'];
	
	}
	/**
	 * Get the second
	 * @return integer The second
	 * @access public
	 */
	public function getSecond() {
	
		return $this->timeArray['s'];
	
	}
	/**
	 * Get the timestamp
	 * @param boolean $useOffset Set to true to get a timestamp that takes the
	 * timezone offset into consideration
	 * @return integer This is not a unix timestamp because there is no date
	 * associated with this time. It is a timestamp from the beginning of the day
	 * @access public
	 */
	public function getTimestamp($useOffset = true) {
	
		$time = ($useOffset) ?
			$this->time - $this->getTimezone()->getOffsetSeconds() : 
			$this->time;
		return $time;
	
	}
	/**
	 * Set the format to use when outputting as a string
	 * @param string $format Use PHP's date() function's time-related
	 * metacharacters to set the format used when this object is printed
	 * @return $this
	 * @access public
	 */
	public function setFormat($format) {
	
		$this->format = (string) $format;
		return $this;
	
	}
	/**
	 * Output the object using PHP's date() function's meta-characters
	 * @param string $format Use PHP's date() function's time-related
	 * metacharacters to set the format that this returns
	 * @return string This object formatted as a string
	 * @access public
	 */
	public function format($format) {
	
		$escape = false;
		$meta = str_split($format);
		$output = array();
		foreach($meta as $char) {
			if ($char == '\\') {
				$escape = true;
				continue;
			}
			if (!$escape && array_key_exists($char, $this->timeArray)) {
				$output[] = $this->timeArray[$char];
			} else {
				$output[] = $char;
			}
			// reset this to false after every iteration that wasn't "continued"
			$escape = false;
		}
		return implode($output);
	
	}
	/**
	 * Output the object as a string
	 * @return string This object formatted as a string
	 * @access public
	 */
	public function __toString() {
	
		return $this->format($this->format);
	
	}

}