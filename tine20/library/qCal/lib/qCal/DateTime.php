<?php
/**
 * Date/Time Object
 * 
 * In order to perform all the complex date/time based math and logic required to
 * implement the iCalendar spec, we need a complex date/time class. This class represents
 * a specific point in time, including the time. Internally it makes use of qCal_Date and
 * qCal_Time. If only a date or only a time needs to be represented, then one of those
 * classes should be used.
 * 
 * @package qCal
 * @subpackage qCal_DateTime
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */
class qCal_DateTime {

	/**
	 * @var qCal_Date An object that represents the date
	 */
	protected $date;
	
	/**
	 * @var qCal_Time An object that represents the time
	 */
	protected $time;
	
	/**
	 * @var string The default string representation of datetime is a direct
	 * correlation to the date function's "c" metacharacter
	 */
	protected $format = "Y-m-d\TH:i:sP";
	
	/**
	 * Class constructor
	 * @param integer $year
	 * @param integer $month
	 * @param integer $day
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $second
	 * @param mixed $timezone Either a qCal_Timezone object or a string representing one
	 * @param boolean $rollover Set to true to be able to specify things like
	 * 100 seconds and have it roll over to one minute, forty seconds
	 * @access public
	 * @todo Make this default to "now"
	 * @todo It is possible that the timezone could put the date back (or forward?) a day. This does not account for that
	 */
	public function __construct($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $timezone = null, $rollover = null) {
	
		$date = new qCal_Date($year, $month, $day, $rollover);
		$time = new qCal_Time($hour, $minute, $second, $timezone, $rollover);
		$this->setDate($date);
		$this->setTime($time);
	
	}
	
	/**
	 * Generate a datetime object from a string (much like PHP's strtotime function)
	 * @param string A date/time string to convert into a qCal_DateTime object
	 * @param mixed Either a qCal_Timezone object or a string representing one
	 * @return qCal_DateTime
	 * @access public
	 * @todo Should this accept qCal_Date and qCal_DateTime objects?
	 */
	public static function factory($datetime, $timezone = null) {
	
		if (is_null($timezone) || !($timezone instanceof qCal_Timezone)) {
			// @todo Make sure this doesn't cause any issues 
			// detect if we're working with a UTC string like "19970101T180000Z", where the Z means use UTC time
			if (strtolower(substr($datetime, -1)) == "z") {
				$timezone = "UTC";
			}
			$timezone = qCal_Timezone::factory($timezone);
		}
		// get the default timezone so we can set it back to it later
		$tz = date_default_timezone_get();
		// set the timezone to GMT temporarily
		date_default_timezone_set("GMT");
		
		// handles unix timestamp
		if (is_integer($datetime) || ctype_digit((string) $datetime)) {
			$timestamp = $datetime;
		} else {
			// handles just about any string representation of date/time (strtotime)
			if (is_string($datetime) || empty($datetime)) {
				if (!$timestamp = strtotime($datetime)) {
					// if unix timestamp can't be created throw an exception
					throw new qCal_DateTime_Exception("Invalid or ambiguous date/time string passed to qCal_DateTime::factory()");
				}
			}
		}
		
		if (!isset($timestamp)) {
			throw new qCal_DateTime_Exception("Could not generate a qCal_DateTime object.");
		}
		
		list($year, $month, $day, $hour, $minute, $second) = explode("|", gmdate("Y|m|d|H|i|s", $timestamp));
		
		// set the timezone back to what it was
		date_default_timezone_set($tz);
		
		return new qCal_DateTime($year, $month, $day, $hour, $minute, $second, $timezone);
	
	}
	
	/**
	 * Set the date component
	 * @param qCal_Date The date component portion of this object
	 * @return $this
	 * @access protected
	 */
	protected function setDate(qCal_Date $date) {
	
		$this->date = $date;
		return $this;
	
	}
	
	/**
	 * Set the time component
	 * @param qCal_Time The time component portion of this object
	 * @return $this
	 * @access protected
	 */
	protected function setTime(qCal_Time $time) {
	
		$this->time = $time;
		return $this;
	
	}
	
	/**
	 * Get time portion as object
	 * @return qCal_Time The time component portion of this object
	 * @access public
	 */
	public function getTime() {
	
		return $this->time;
	
	}
	
	/**
	 * Get date portion as object
	 * @return qCal_Date The date component portion of this object
	 * @access public
	 */
	public function getDate() {
	
		return $this->date;
	
	}
	
	/**
	 * Get unix timestamp
	 * @param boolean $useOffset Set to true if you want the timestamp to
	 * consider the timezone's offset
	 * @return integer The unix timestamp of this object
	 * @access public
	 */
	public function getUnixTimestamp($useOffset = true) {
	
		return $this->date->getUnixTimestamp() + $this->time->getTimestamp($useOffset);
	
	}
	
	/**
	 * Set the format to use when outputting as a string
	 * @param string $format The format that is to be used when this object is
	 * converted to a string. Use the same metacharacters that are used in PHP's date function
	 * @return $this
	 * @access public
	 */
	public function setFormat($format) {
	
		$this->format = (string) $format;
		return $this;
	
	}
	
	/**
	 * Format the date/time using PHP's date() function's meta-characters
	 * @param string $format Use the PHP date() function's meta-characters to convert this object to a string
	 * @return string The formatted date/time string
	 * @access public
	 * @todo It's obvious I need to find a better solution to formatting since I have repeated this method
	 * in three classes now...
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
			if (!$escape && $this->convertChar($char) != $char) {
				$output[] = $this->convertChar($char);
			} else {
				$output[] = $char;
			}
			// reset this to false after every iteration that wasn't "continued"
			$escape = false;
		}
		return implode($output);
	
	}
	
	/**
	 * Converts a metacharacter into its date/time counter-part. 
	 * @param string $char The metacharacter that is to be converted
	 * @return string The converted date/time value
	 * @access public
	 */
	protected function convertChar($char) {
	
		$char = $this->date->format($char);
		$char = $this->time->format($char);
		$char = $this->time->getTimezone()->format($char);
		return $char;
	
	}
	
	/**
	 * Output date/time as string 
	 * @return string The string representation of this object
	 * @access public
	 */
	public function __toString() {
	
		return $this->format($this->format);
	
	}
	
	/**
	 * Get date/time as UTC
	 * @param boolean $humanReadable Set to true if you want the result to be a more human-oriented format
	 * @return string The UTC-formatted date/time
	 * @access public
	 */
	public function getUtc($humanReadable = false) {
	
		if ($humanReadable) return gmdate('Y-m-d', $this->date->getUnixTimestamp()) . gmdate('\TH:i:s\Z', $this->time->getTimestamp());
		else return gmdate('Ymd', $this->date->getUnixTimestamp()) . gmdate('\THis\Z', $this->time->getTimestamp());
	
	}

}