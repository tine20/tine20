<?php
/**
 * Recurrence Rule
 * This is the base rule class from which all other recurrence rules are based.
 * 
 * @package qCal
 * @subpackage qCal_DateTime_Recur
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 * @todo I'm not sure that we need all of the subclasses for this. None of them
 * do anything so far. I'll keep them for now, but if I don't find a use for
 * them, they're going to get the boot.
 */
class qCal_DateTime_Recur_Rule {

	/**
	 * @var array The values that this rule represents. For instance, if the
	 * rule is a qCal_DateTime_Recur_Rule_ByMonth, this could be a list of
	 * integers from 1 to 12 or a list of strings (january through december)
	 */
	protected $values = array();
	
	/**
	 * Class Constructor
	 * @param mixed $values Either an array of values or a single value 
	 * @access public
	 */
	public function __construct($values) {
	
		if (!is_array($values)) {
			$values = (array) $values;
		}
		$this->values = $values;
	
	}
	
	/**
	 * Factory Method
	 * Generate a qCal_DateTime_Recur_Rule object from strings
	 * @param string $rule The rule type you want to generate. The following
	 * are valid: day, hour, minute, month, monthday, second, setpos, weekno,
	 * yearday, count, interval, until, wkst
	 */
	public static function factory($rule, $values) {
	
		// set up error message in case we can't find the specified rule
		$errorMsg = "'$rule' is an unsupported recurrence rule.";
		$rule = strtolower((string) $rule);
		switch ($rule) {
			case "day":
			case "hour":
			case "minute":
			case "month":
			case "second":
				$rule = 'By' . ucfirst($rule);
				break;
			case "count":	
			case "interval":
			case "until":
			case "wkst":
				$rule = ucfirst($rule);
			case "monthday":
				$rule = "ByMonthDay";
				break;
			case "setpos":
				$rule = "BySetPos";
				break;
			case "weekno":
				$rule = "ByWeekNo";
				break;
			case "yearday":
				$rule = "ByYearDay";
				break;
			default:
				throw new qCal_DateTime_Exception_InvalidRecurrenceRule($errorMsg);
		}
		$class = "qCal_DateTime_Recur_Rule_" . $rule;
		$values = explode(",", $values);
		return new $class($values);
	
	}
	
	/**
	 * Check that the date provided falls within this rule's values.
	 * @param qCal_Date The date that you want to check 
	 * @return boolean True if the date falls within the fules
	 * @access public
	 * @todo There is probably a lot of functionality that could be moved up
	 * on to this level, but for now, I have the child classes doing the work
	 */
	public function checkDate(qCal_Date $date) {}
	
	/**
	 * Retrieve the values contained in this rule
	 * @return array A list of values for this rule
	 * @access public
	 */
	public function getValues() {
	
		return $this->values;
	
	}

}