<?php
/**
 * Monthly Date/Time Recurrence object.
 * This class is used to create recurrence rules that happen on a monthly basis.
 * 
 * @package qCal
 * @subpackage qCal_DateTime_Recur
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */
class qCal_DateTime_Recur_Monthly extends qCal_DateTime_Recur {
	
	/**
	 * @var boolean When this is set to true, the monthArray is regenerated when
	 * next() is called.
	 * @access protected
	 */
	protected $regenerateYearArray = true;
	
	/**
	 * @var array A list of all of the days in the "current" month. Organized like:
	 * array(
	 * 		'1' => array(
	 * 			'1' => qCal_Date(2010, 1, 1)
	 * 			'2' => qCal_Date(2010, 1, 2)
	 * 			...etc...
	 * 		)
	 * )
	 * For every month in the recurrence, this is regenerated
	 * @access protected
	 */
	protected $monthArray = array();
	
	/**
	 * Rewind the recurrence iterator
	 * @return void
	 * @access public
	 */
	public function rewind() {
	
		$this->regenerateMonthArray = true;
		parent::rewind();
	
	}
	
	/**
	 * Move the internal "pointer" to the next recurrence in the set
	 * and return it.
	 * @return qCal_DateTime The next date/time recurrence in the set
	 * @access public
	 */
	public function next() {
	
		// make a copy of the start date/time to work with
		$startDate = $this->current->getDate();
		$startTime = $this->current->getTime();
		$month = $startDate->getMonth();
		$year = $startDate->getYear();
		
		// if there is no "next" recurrence in the timeArray, we need to
		// regenerate it with new times for the next day in the recurrence list
		if (!$current = next($this->timeArray)) {
			// if there is no "next" day in the monthArray, we need to regenerate
			// the monthArray. It is possible that it hasn't been generated in
			// the first place, so we need to determine if it should be 
			// regenerated for the current year, or for the next year interval
			if (!$currentDay = next($this->monthArray)) {
				// determine if we need to generate the monthArray with the current
				// year or with the next year interval
				if (!empty($this->monthArray)) {
					$nextmonth = $month + $this->getInterval();
					if ($nextmonth > 12) {
						$nextmonth = $nextmonth - 12;
						$year++;
					}
					$month = $nextmonth;
				}
				// $this->currentDay will be assigned when the monthArray is created
				// we will now need to regenerate the monthArray with the data above
				$this->regenerateMonthArray = true;
			} else {
				$this->currentDay = $currentDay;
			}
			// regenerate the timeArray once the yearArray is regenerated
			$this->regenerateTimeArray = true;
		} else {
			$this->current = $current;
		}
		
		// create a multi-dimensional array of dates that will be looped over
		// when the object is looped over. Each date will have one or more 
		// times which will result in even more recurrences. We do not build
		// the time recurrences for the entire month because it takes too long.
		// @todo If this is not the first time we are building this array, we
		// need to skip ahead by $this->interval months. 
		if ($this->regenerateMonthArray) {
		
			$monthArray = array();
			// this date object is used to find out how many days are in the current month
			$ml = new qCal_Date($year, $month, 1);
			for ($d = 1; $d <= $ml->getNumDaysInMonth(); $d++) {
				$day = new qCal_Date($year, $month, $d);
				if ($this->checkDateAgainstRules($day)) {
					// if this day is equal to or greater than the start
					// date, add it to the yearArray
					if ($day->getUnixTimestamp() >= $startDate->getUnixTimestamp()) {
						$monthArray[$day->format("Ymd")] = $day;
					}
				}
			}
			$this->monthArray = $monthArray;
			$this->currentDay = current($this->monthArray);
			// now that we have cached the monthArray, we don't need to
			// regenerate it until we have gotten to the next year increment
			$this->regenerateMonthArray = false;
		
		}
		
		// if the time recurrences for the current date haven't been created
		// yet, then create them and assign the "current" value to the first
		// time recurrence in the set. If the time recurrences are already
		// available, move the "current" position ahead one recurrence.
		if ($this->regenerateTimeArray) {
			$this->timeArray = $this->findTimeRecurrences($this->currentDay);
			// now that we have cached the timeArray, we don't need to 
			// regenerate it until we have gotten to the next day
			$this->regenerateTimeArray = false;
			$this->current = current($this->timeArray);
		}
		
		// now find the "next" recurrence, advance the "current" date and
		// return the recurrence
		// @TODO When I come back to this tomorrow, I need to take what I have
		// right now, which is an array of dates that are within the recurrence
		// rule set, along with an array of times on each day, and I need to
		// make this object capable of properly looping over these things.
		
		return $this->current;
		
		/**
		 * This is the old code. It was used to create an array containing twelve arrays (one
		 * for each month) of 28-31 days (one for each day of the month)
		if (empty($this->yearArray) || $regenerateYearArray) {
			$yearArray = array();
			for($m = 1; $m <= 12; $m++) {
				$month = new qCal_Date($startDate->getYear(), $m, 1);
				$monthArray = array();
				for ($d = 1; $d <= $month->getNumDaysInMonth(); $d++) {
					$day = new qCal_Date($startDate->getYear(), $m, $d);
					$monthArray[$d] = $day;
				}
				$yearArray[$m] = $monthArray;
			}
			$this->yearArray = $yearArray;
		}
		*/
	
	}
	
	/**
	 * Check a qCal_Date object against all of the rules for this recurrence.
	 * @param qCal_Date The date that needs to be checked against the rules
	 * @return boolean If the rules allow this day, return true
	 * @access protected
	 */
	protected function checkDateAgainstRules(qCal_Date $date) {
	
		/**
		 * Neither byMonth or byWeekNo have a meaningful affect on the recurrences

		$withinMonth = true;
		$withinWeek = true;
		if ($this->hasRule('qCal_DateTime_Recur_Rule_ByMonth')) {
			// if the byMonth rule is defined, then withinMonth defaults to false
			$withinMonth = false;
			$byMonth = $this->getRule('qCal_DateTime_Recur_Rule_ByMonth');
			if ($byMonth->checkDate($date)) {
				$withinMonth = true;
			}
		}
		
		if ($this->hasRule('qCal_DateTime_Recur_Rule_ByWeekNo')) {
			// if the byMonth rule is defined, then withinMonth defaults to false
			$withinWeek = false;
			$byWeekNo = $this->getRule('qCal_DateTime_Recur_Rule_ByWeekNo');
			if ($byWeekNo->checkDate($date)) {
				$withinWeek = true;
			}
		}
		 */
		
		// if within the month, check month day
		if ($this->hasRule('qCal_DateTime_Recur_Rule_ByMonthDay')) {
			$byMonthDay = $this->getRule('qCal_DateTime_Recur_Rule_ByMonthDay');
			if ($byMonthDay->checkDate($date)) {
				return true;
			}
		}
		
		/**
		 * @todo I am not sure if byYearDay should be possible with a monthly rule...
		if ($this->hasRule('qCal_DateTime_Recur_Rule_ByYearDay')) {
			$byYearDay = $this->getRule('qCal_DateTime_Recur_Rule_ByYearDay');
			if ($byYearDay->checkDate($date)) {
				return true;
			}
		}
		 */
		
		if ($this->hasRule('qCal_DateTime_Recur_Rule_ByDay')) {
			$byDay = $this->getRule('qCal_DateTime_Recur_Rule_ByDay');
			if ($byDay->checkDate($date)) {
				return true;
			}
		}
		
		return false;
	
	}
	
	/**
	 * Provided a date/time object, use this recurrence's rules to determine
	 * all of the recurrence times for the date and return them in an array.
	 * @param qCal_Date The date object to find time recurrences for
	 * @return array A list of time recurrences for the specified date/time
	 * @access protected
	 * @todo I don't really like the way this is done. Definitely a code smell here.
	 * Each of the rules should do their own logic. Something like:
	 * 	$seconds = $bySecond->getTimeInstances();
	 * 	$minutes = $byMinute->getTimeInstances($seconds);
	 * 	$hours = $byHour->getTimeInstances($minutes);
	 */
	protected function findTimeRecurrences(qCal_Date $date) {
	
		// find all of the bySeconds
		$seconds = array();
		if ($this->hasRule('qCal_DateTime_Recur_Rule_BySecond')) {
			$seconds = $this->getRule('qCal_DateTime_Recur_Rule_BySecond')->getValues();
			sort($seconds);
		} else {
			$seconds = array($this->getStart()->getTime()->getSecond());
		}
		
		// find all of the byMinutes
		$minutes = array();
		if ($this->hasRule('qCal_DateTime_Recur_Rule_ByMinute')) {
			$minutesRules = $this->getRule('qCal_DateTime_Recur_Rule_ByMinute')->getValues();
			sort($minutesRules);
		} else {
			$minutesRules = array($this->getStart()->getTime()->getMinute());
		}
		foreach ($minutesRules as $minute) {
			$minutes[$minute] = $seconds;
		}
		
		// find all of the byHours
		$hours = array();
		if ($this->hasRule('qCal_DateTime_Recur_Rule_ByHour')) {
			$hoursRules = $this->getRule('qCal_DateTime_Recur_Rule_ByHour')->getValues();
			sort($hoursRules);
		} else {
			$hoursRules = array($this->getStart()->getTime()->getHour());
		}
		foreach ($hoursRules as $hour) {
			$hours[$hour] = $minutes;
		}
		
		// create an array to store times
		$times = array();
		foreach ($hours as $hour => $minutes) {
			foreach ($minutes as $minute => $seconds) {
				foreach ($seconds as $second) {
					try {
						// try to build a date/time object
						$datetime = new qCal_DateTime($date->getYear(), $date->getMonth(), $date->getDay(),  $hour, $minute, $second);
						$times[$datetime->format('YmdHis')] = $datetime;
					} catch (qCal_DateTime_Exception_InvalidTime $e) {	
						// if the date/time object instantiation fails, this exception will be thrown
						// @todo Recover from this error and report it. Maybe catch the error and pass it to a log or something?
						// qCal_Log::logException($e, get_class($this));
						throw $e;
					}
				}
			}
		}
		
		return $times;
	
	}

}