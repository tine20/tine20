<?php
class qCal_Timezone {

	/**
	 * @var string The format to use to output this timezone as a string
	 * @access protected
	 */
	protected $format = "e";
	
	/**
	 * @var string The name of the timezone
	 * @access protected
	 */
	protected $name;
	
	/**
	 * @var integer The offset (in seconds) of this timezone
	 * @access protected
	 */
	protected $offsetSeconds;
	
	/**
	 * @var string The abbreviated name
	 * @access protected
	 */
	protected $abbreviation;
	
	/**
	 * @var boolean Whether or not this timezone observes daylight savings (need to figure this out)
	 * @access protected
	 */
	protected $isDaylightSavings;
	
	/**
	 * @var array An array of timezone metacharacters that this class allows (see PHP's date() function)
	 * @access protected
	 */
	protected $formatArray = array();
	
	/**
	 * @var array An array of timezones that have been registered with the class
	 * @access protected
	 * @static
	 */
	protected static $timezones = array();
	
	/**
	 * Class constructor
	 * A timezone must have a name, offset (in seconds), and optionsally an abbreviation. Daylight savings defaults to false.
	 * @param string $name The name of the timezone
	 * @param integer $offset The offset (in seconds) of this timezone from GMT
	 * @param string $abbreviation The abbreviated name
	 * @param boolean $daylightsavings Set to true if this timezone observes daylight savings
	 * @todo When $abbreviation isn't specified, and $name is a valid pre-defined PHP timezone identifier, use its
	 * 		 corresponding abbreviation rather than the name itself
	 * @todo When $offset isn't provided and $name is a valid timezone, use its corresponding offset, but if $name is not
	 * 		 a valid timezone identifier and no offset is provided, throw an exception
	 * @todo I don't really know what to do with the daylight savings parameter
	 */
	public function __construct($name, $offset, $abbreviation = null, $daylightsavings = null) {
	
		if (is_null($abbreviation)) $abbreviation = $name;
		
		$this->setName($name)
			->setOffsetSeconds($offset)
			->setAbbreviation($abbreviation)
			->setIsDaylightSavings($daylightsavings);
		
		$this->formatArray = array(
			'e' => $this->getName(),
			'I' => (integer) $this->isDaylightSavings(),
			'O' => $this->getOffsetHours(),
			'P' => $this->getOffset(),
			'T' => $this->getAbbreviation(),
			'Z' => $this->getOffsetSeconds(),
		);
	
	}
	
	/**
	 * @param string $name The name of the timezone
	 * @return $this
	 * @access public
	 */
	public function setName($name) {
	
		$this->name = (string) $name;
		return $this;
	
	}
	
	/**
	 * @param integer $offset The offset (in seconds) from GMT
	 * @return $this
	 * @access public
	 */
	public function setOffsetSeconds($offset) {
	
		$this->offsetSeconds = (integer) $offset;
		return $this;
	
	}
	
	/**
	 * @param string $abbreviation The abbreviated name of the timezone
	 * @return $this
	 * @access public
	 */
	public function setAbbreviation($abbreviation) {
	
		$this->abbreviation = (string) $abbreviation;
		return $this;
	
	}
	
	/**
	 * @param boolean $daylightSavings Whether or not this timezone observes daylight savings
	 * @return $this
	 * @access public
	 */
	public function setIsDaylightSavings($daylightSavings = null) {
	
		$this->isDaylightSavings = (boolean) $daylightSavings;
		return $this;
	
	}
	
	/**
	 * Generate a timezone from either an array of parameters, or a timezone
	 * name such as "America/Los_Angeles".
	 * @param mixed $timezone A string to be converted to a qCal_Timezone object
	 * @return qCal_Timezone
	 * @access public
	 * @link http://php.net/manual/en/timezones.php A directory of valid timezones
	 * @todo This method is FUGLY. Rewrite it and make it make sense. This is sort of nonsensical.
	 */
	public static function factory($timezone = null) {
	
		if (is_array($timezone)) {
			// remove anything irrelevant
			$vals = array_intersect_key($timezone, array_flip(array('name','offsetSeconds','abbreviation','isDaylightSavings')));
			if (!array_key_exists("name", $vals)) {
				// @todo throw an exception or something
			}
			if (!array_key_exists("offsetSeconds", $vals)) {
				// @todo throw an exception or something
			}
			$name = $vals['name'];
			$offsetSeconds = $vals['offsetSeconds'];
			$abbreviation = (array_key_exists('abbreviation', $vals)) ? $vals['abbreviation'] : null;
			$isDaylightSavings = (array_key_exists('isDaylightSavings', $vals)) ? $vals['isDaylightSavings'] : null;
			$timezone = new qCal_Timezone($name, $offsetSeconds, $abbreviation, $isDaylightSavings);
		} else {
			// get the timezone information out of the string
			$defaultTz = date_default_timezone_get();
			
			if (is_null($timezone)) $timezone = $defaultTz;
			
			// if the timezone being set is invalid, we will get a PHP notice, so error is suppressed here
			// @todo It would be more clean and probably more efficient to use php's error handling to throw an exception here...
			if (is_string($timezone)) {
				@date_default_timezone_set($timezone);
				// if the function above didn't work, this will be true
				if (date_default_timezone_get() != $timezone) {
					// if the timezone requested is registered, use it
					if (array_key_exists($timezone, self::$timezones)) {
						$timezone = self::$timezones[$timezone];
					} else {
						// otherwise, throw an exception
						throw new qCal_DateTime_Exception_InvalidTimezone("'$timezone' is not a valid timezone.");
					}
				} else {
					// if the timezone specified was a valid (native php) timezone, use it
					$name = date("e");
					$offset = date("Z");
					$abbr = date("T");
					$ds = date("I");
					$timezone = new qCal_Timezone($name, $offset, $abbr, $ds);
				}
			}
			
			// now set it back to what it was...
			date_default_timezone_set($defaultTz);
		}
		return $timezone;
	
	}
	
	/**
	 * Register a timezone object so that it can be referenced by name and qCal
	 * components will be able to figure out how to apply its offset.
	 * @param qCal_Timezone $timezone The timezone you want to register
	 * @access public
	 * @return void
	 * @static
	 */
	public static function register(qCal_Timezone $timezone) {
	
		self::$timezones[$timezone->getName()] = $timezone;
	
	}
	
	/**
	 * Unregisters a timezone by name
	 * @param string $timezone The name of the timezone to be unregistered
	 * @return void
	 * @access public
	 * @static
	 */
	public static function unregister($timezone) {
	
		unset(self::$timezones[(string) $timezone]);
	
	}
	
	/**
	 * Return the name of this timezone
	 * @return string The name of the timezone
	 * @access public
	 */
	public function getName() {
	
		return $this->name;
	
	}
	
	/**
	 * Return the offset in hours (+08:00) of this timezone with colon between hours and minutes
	 * @return string The offset of the timezone
	 * @access public
	 */
	public function getOffset() {
	
		$seconds = $this->getOffsetSeconds();
		$negpos = "+";
		if ($seconds < 0) {
			$negpos = "-";
		}
		$hours = (integer) ($seconds / 60 / 60);
		$minutes = $hours * 60;
		$minutes = ($seconds / 60) - $minutes;
		return sprintf("%s%02d:%02d", $negpos, abs($hours), abs($minutes));
	
	}
	
	/**
	 * Return the offset in hours (+0800) of this timezone
	 * @return string The offset of the timezone (in hours)
	 * @access public
	 */
	public function getOffsetHours() {
	
		$seconds = $this->getOffsetSeconds();
		$negpos = "+";
		if ($seconds < 0) {
			$negpos = "-";
		}
		$hours = (integer) ($seconds / 60 / 60);
		$minutes = $hours * 60;
		$minutes = ($seconds / 60) - $minutes;
		return sprintf("%s%02d%02d", $negpos, abs($hours), abs($minutes));
	
	}
	
	/**
	 * Return the offset in seconds of this timezone
	 * @return integer The offset of the timezone
	 * @access public
	 */
	public function getOffsetSeconds() {
	
		return $this->offsetSeconds;
	
	}
	
	/**
	 * Return the abbreviated name of this timezone
	 * @return string The abbreviated name of the timezone
	 * @access public
	 */
	public function getAbbreviation() {
	
		return $this->abbreviation;
	
	}
	
	/**
	 * Return true if this timezone observes daylight savings
	 * @return boolean Whether or not this timezone observes daylight savings
	 * @access public
	 */
	public function isDaylightSavings() {
	
		return $this->isDaylightSavings;
	
	}
	
	/**
	 * Set the format that should be used when calling either __toString() or format() without an argument.
	 * @param string $format
	 * @return $this
	 * @link http://php.net/manual/en/timezones.php A directory of valid timezones
	 * @access public
	 */
	public function setFormat($format) {
	
		$this->format = (string) $format;
		return $this;
	
	}
	
	/**
	 * Return the timezone as a formatted string
	 * @param string $format The string used to format the object (see PHP's date function)
	 * @return string The formatted timezone
	 * @link http://php.net/manual/en/timezones.php A directory of valid timezones
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
			if (!$escape && array_key_exists($char, $this->formatArray)) {
				$output[] = $this->formatArray[$char];
			} else {
				$output[] = $char;
			}
			// reset this to false after every iteration that wasn't "continued"
			$escape = false;
		}
		return implode($output);
	
	}
	
	/**
	 * Returns this object formatted as a string (used when this object is printed)
	 * @return string The formatted timezone
	 * @access public
	 */
	public function __toString() {
	
		return $this->format($this->format);
	
	}

}