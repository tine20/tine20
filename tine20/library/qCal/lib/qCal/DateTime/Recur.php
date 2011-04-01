<?php
/**
 * Date/Time Recurrence object.
 * This class is used to define a date/time recurrence. It is capable of
 * creating recurrence rules for just about any type of recurrence. There are
 * many examples of recurrences below.
 * 
 * @package qCal
 * @subpackage qCal_DateTime_Recur
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */
abstract class qCal_DateTime_Recur implements Iterator, Countable {

	/**
	 * @var array A list of valid recurrence frequencies
	 * @access protected
	 */
	protected static $validFreq = array(
		"yearly",
		"monthly",
		"weekly",
		"daily",
		"hourly",
		"minutely",
		"secondly",
	);
	
	/**
	 * @var qCal_DateTime The start date/time of this recurrence
	 * @access protected
	 */
	protected $start;
	
	/**
	 * @var integer The interval for this recurrence. For instance, every other year
	 * would mean the interval is two. Every third year, interval is 3, etc.
	 * @access protected
	 */
	protected $interval = 1;
	
	/**
	 * @var array A list of qCal_DateTime_Recur_Rule objects that define this recurrence
	 * @access protected
	 */
	protected $rules = array();
	
	/**
	 * @var array A list of all of the date/time recurrences for the "current"
	 * day. It is a simple one-dimensional array. Every call to next() advances
	 * the pointer to the next item in this list until it is exhausted and then
	 * another is generated for the next day in the recurrence set. Once all of
	 * the days in the year are exhausted, the yearArray (see above) is 
	 * regenerated and the process starts all over again.
	 * @access protected
	 */
	protected $timeArray = array();
	
	/**
	 * @var boolean When this is set to true, the timeArray is regenerated when
	 * next() is called.
	 * @access protected
	 */
	protected $regenerateTimeArray = true;
	
	/**
	 * @var qCal_DateTime The current recurrence in the set (comes from timeArray)
	 * @todo might want to change this to a qCal_DateTime_Recur_Recurrence
	 * object or something because this isn't always used for recurring events.
	 * Sometimes it is to describe daylight savings time, and other things like
	 * that. If we wrap it in a qCal_DateTime_Recur_Recurrence object, we can
	 * do things to it that make it more flexible. We'll cross that bridge when
	 * we get to it though. For now, just use qCal_DateTime.
	 * @access protected
	 */
	protected $current;
	
	/**
	 * @var qCal_Date The current day in yearArray
	 * @access public
	 */
	protected $currentDay;
	
	/**
	 * @var integer The amount of recurrences allowed in the set. If this is
	 * set to false, then the recurrence set can recur an infinite number of times.
	 * @access protected
	 */
	protected $count = false;
	
	/**
	 * @var qCal_DateTime The date/time that all recurrences must come before.
	 * If this is set to false, then the recurrence can go on forever.
	 * @access protected
	 */
	protected $until = false;
	
	/**
	 * @var integer The number of recurrences that have been looped over. Used
	 * to determine at what point to stop recurring when setCount() is used.
	 * @access protected
	 */
	protected $recurrenceCount = 0;
	
	/**
	 * Class constructor
	 * This method instantiates the object by setting its "type" and its start date/time.
	 * @param mixed $start Either a qCal_DateTime or a string representing one
	 * @param integer $intvl An interval of years, months or whatever this recurrence type is
	 * @param array $rules A list of rules to apply to the date/time recurrence
	 * @throws qCal_DateTime_Exception_InvalidRecurrenceType If an invalid type is specified
	 * @access public
	 */
	public function __construct($start = null, $intvl = null, Array $rules = array()) {
	
		// set the start date/time and interval
		$this->setStart($start)
			->setInterval($intvl);
		
		// loop through the array of rules and add them to this recur object
		foreach ($rules as $rule) {
			// if rule is not a supported rule type, report it
			if (!($rule instanceof qCal_DateTime_Recur_Rule)) {
				// first we need to determine what was passed in so we can complain properly
				if (is_object($rule)) {
					$ruletype = get_class($rule);
				} elseif (is_array($rule)) {
					$ruletype = "Array";
				} else {
					$ruletype = $rule;
				}
				// now throw an exception explaining why we couldn't accept the rule
				throw new qCal_DateTime_Exception_InvalidRecurrenceRule("'$ruletype' is an unsupported recurrence rule.");
			}
			$this->addRule($rule);
		}
	
	}
	
	/**
	 * Factory Class
	 * Generates a qCal_DateTime_Recur object that is specific to a certain
	 * frequency type (yearly, monthly, weekly, etc.).
	 * @param string $freq The recurrence frequency
	 * @param mixed $start Either a qCal_DateTime object or a string representing one
	 * @param integer $intvl The interval of years, months or whatever the recurrence type is
	 * @return qCal_DateTime_Recur A date/time recurrence object of the specified frequency
	 * @access public
	 * @static
	 */
	public static function factory($freq, $start, $intvl = null, Array $rules = array()) {
	
		$freq = strtolower($freq);
		if (!in_array($freq, self::$validFreq)) {
			throw new qCal_DateTime_Exception_InvalidRecurrenceFrequency("'$freq' is an unsupported recurrence frequency.");
		}
		$class = 'qCal_DateTime_Recur_' . ucfirst($freq);
		return new $class($start, $intvl, $rules);
	
	}
	
	/**
	 * Set the date/time recurrence's start date (required)
	 * @param mixed $start Either a qCal_DateTime object or a string representing one
	 * @return $this
	 * @access public
	 */
	public function setStart($start) {
	
		if (!($start instanceof qCal_DateTime)) {
			$start = qCal_DateTime::factory($start);
		}
		$this->start = $start;
		return $this;
	
	}
	
	/**
	 * Get the date/time recurrence's start date
	 * @return qCal_DateTime
	 * @access public
	 */
	public function getStart() {
	
		return $this->start;
	
	}
	
	/**
	 * Set the date/time interval. 
	 * @param integer The interval in years, months or whatever the recurrence type is
	 * @return $this
	 * @access public
	 */
	public function setInterval($intvl = null) { 
	
		if (is_null($intvl)) $intvl = 1;
		$this->interval = (integer) $intvl;
		return $this;
	
	}
	
	/**
	 * Retrieve the date/time interval
	 * @return integer The interval of time (for instance, every 3 years)
	 * @access public
	 */
	public function getInterval() {
	
		return (integer) $this->interval;
	
	}
	
	/**
	 * Set the number or recurrences that are allowed (recurring stops after
	 * this many recurrences).
	 * @param integer The number or recurrences to allow
	 * @return $this
	 * @access public
	 */
	public function setCount($count) { 
	
		$this->count = (integer) $count;
		return $this;
	
	}
	
	/**
	 * Retrieve the date/time interval
	 * @return integer The interval in years, months or whatever the recurrence type is
	 * @access public
	 */
	public function getCount() {
	
		return (integer) $this->count;
	
	}
	
	/**
	 * Set the end date/time for the recurrence set. No recurrences will be returned
	 * beyond this date/time
	 * @param mixed Either a qCal_DateTime object or a string representing one
	 * @return $this
	 * @access public
	 */
	public function setUntil($datetime) { 
	
		$this->until = ($datetime instanceof qCal_DateTime) ? $datetime : qCal_DateTime::factory($datetime);
		return $this;
	
	}
	
	/**
	 * Retrieve the date/time that recurrences must come before.
	 * @return qCal_DateTime The date/time that recurrences must come before
	 * @access public
	 */
	public function getUntil() {
	
		return $this->until;
	
	}
	
	/**
	 * Add a qCal_DateTime_Recur_Rule object to this recurrence, changing the
	 * way it recurs. Only one of each rule type is allowed, so if there is
	 * already a rule of the type you are adding, it is overwritten.
	 * @param qCal_DateTime_Recur_Rule $rule
	 * @return $this
	 * @access public
	 */
	public function addRule(qCal_DateTime_Recur_Rule $rule) {
	
		$this->rules[get_class($rule)] = $rule;
		return $this;
	
	}
	
	/**
	 * Determine if this recurrence contains the specified rule
	 * @param string $rule The rule we want to determine the existence of
	 * @return boolean Whether or not this recurrence contains the specified rule
	 * @access public
	 */
	public function hasRule($rule) {
	
		return (boolean) array_key_exists($rule, $this->rules);
	
	}
	
	/**
	 * Retrieves the specified rule from the recurrence
	 * @param string $rule The rule we want to retrieve
	 * @return qCal_DateTime_Recur_Rule
	 * @access public
	 */
	public function getRule($rule) {
	
		if (!$this->hasRule($rule)) {
			throw new qCal_DateTime_Exception_MissingRecurrenceRule("This recurrence does not contain a '$rule' rule.");
		}
		return $this->rules[$rule];
	
	}
	
	/**
	 * Retrieve the array of rules that this recurrence contains
	 * @return array A list of rules that make up this recurrence
	 * @access public
	 */
	public function getRules() {
	
		return $this->rules;
	
	}
	
	/**
	 * Begin Magic Methods
	 */
	
	/**
	 * Convert this object to a string (used in cases where you do something
	 * like 'print $datetime')
	 * @return string The current recurrence as a string
	 * @access public
	 */
	public function __toString() {
	
		$recurrence = $this->current();
		return $recurrence->__toString();
	
	}
	
	/**
	 * Begin Iterator Methods
	 */
	
	/**
	 * Current
	 * Retrieve the current recurrence in the set
	 * @return qCal_DateTime_Recur_Recurrence The current recurrence in the set
	 * @access public
	 */
	public function current() {
	
		if (!$this->current) $this->rewind();
		return $this->current;
	
	}
	
	/**
	 * Key
	 * Retrieve the current recurrence's key
	 * @return integer Each recurrence in the set has an associated key from 1
	 * to however many recurrences are in the set
	 * @access public
	 * @todo Calling $this->get('20100423123000') should return a recurrence if
	 * there is a recurrence at that date/time. So, returning a "key" should be
	 * the date/time you want in YmdHis format.
	 */
	public function key() {
	
		return $this->current->format('YmdHis');
	
	}
	
	/**
	 * Next
	 * Move the pointer to the next recurrence in the set. This method is
	 * delegated to its children because (at least for now), there is no method
	 * to get the next recurrence for any type of recurrence (yearly, monthly,
	 * etc.). Once I am finished with all of the children classes, I may try to
	 * refactor this class to be capable of finding recurrences for any type.
	 * @return void
	 * @access public
	 * @abstract I want this method to be abstract, but for some reason I can't :(
	 */
	public function next() {}
	
	/**
	 * Rewind
	 * Rewind the pointer to the first recurrence in the set
	 * @return void
	 * @access public
	 */
	public function rewind() {
	
		// reset the recurrence count
		$this->recurrenceCount = 0;
		
		// set the "current" variable to the start date to rewind the "pointer"
		$this->current = $this->getStart();
		
		// tell the object to regenerate date and time arrays
		$this->regenerateTimeArray = true;
		
		// now use the "next()" method to set "current" to the first actual recurrence
		$this->next();
		return true;
	
	}
	
	/**
	 * Valid
	 * Determine if the current recurrence is within the boundaries of the recurrence set.
	 * Recurrence count is computed in this method as well.
	 * @return boolean If the current recurrence is valid, return true
	 * @access public
	 * @todo Child classes may have their own logic to apply here, so check that out...
	 */
	public function valid() {
	
		// check to see if this date is past the "until" date
		if ($this->getUntil()) {
			if ($this->current->getUnixTimestamp() > $this->getUntil()->getUnixTimestamp()) {
				return false;
			}
		}
		// check to see if this recurrence makes more than the "count" allows
		$this->recurrenceCount++;
		if ($this->getCount()) {
			if ($this->recurrenceCount > $this->getCount()) {
				return false;
			}
		}
		
		return true;
	
	}
	
	/**
	 * Count
	 * If there is a finite number of recurrences, that number is returned.
	 * If there is an infinite number of recurrences, -1 is returned.
	 * Use this method only if you need it. This is a very expensive method.
	 * @return integer The number of recurrences in the set
	 * @access public
	 */
	public function count() {
	
		if ($this->getCount() || $this->getUntil()) {
			$count = 0;
			foreach ($this as $recurrence) {
				$count++;
			}
		} else {
			$count = -1;
		}
		$this->rewind();
		return $count;
	
	}

}