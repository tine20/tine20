<?php
/**
 * "ByDay" Recurrence Rule
 * This rule specifies something like "MO" for every Monday or "-1SU" for the
 * last Sunday of the month
 * 
 * @package qCal
 * @subpackage qCal_DateTime_Recur
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */
class qCal_DateTime_Recur_Rule_ByDay extends qCal_DateTime_Recur_Rule {

	/**
	 * Check a qCal_Date object to see if it falls within the rules specified
	 * when this object was created (or added later actually).
	 * @param qCal_Date The date that you want to check 
	 * @return boolean True if the date falls within the ruleset
	 * @access public
	 */
	public function checkDate(qCal_Date $date) {
	
		$byDay = $this->getValues();
		foreach ($byDay as $wday) {
			$char1 = substr($wday, 0, 1);
			$cwday = strtoupper(substr($date->getWeekDayName(), 0, 2));
			if (ctype_digit($char1) || $char1 == "-" || $char1 == "+") {
				// if the first character is a digit or a plus or minus, we
				// need to check that date is a specific weekday of the month
				if (preg_match('/([+-]?)([0-9]+)([a-z]+)/i', $wday, $matches)) {
					list($whole, $sign, $dig, $wd) = $matches;
					// find out if this day matches the specific weekday of month
					$xth = (integer) ($sign . $dig);
					// @todo Make sure that getXthWeekDayOfMonth doesn't need
					// to be passed any month or date here...
					$dtWdSpecific = $date->getXthWeekdayOfMonth($xth, $wd);
					if ($dtWdSpecific->__toString() == $date->__toString()) return true;
				}
			} else {
				if ($wday == $cwday) {
					return true;
				}
			}
		}
		return false;
	
	}

}