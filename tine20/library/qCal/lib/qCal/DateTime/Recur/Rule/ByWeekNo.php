<?php
/**
 * "ByWeekNo" Recurrence Rule
 * This rule specifies how week number plays into the recurrence rule.
 * 
 * @package qCal
 * @subpackage qCal_DateTime_Recur
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */
class qCal_DateTime_Recur_Rule_ByWeekNo extends qCal_DateTime_Recur_Rule {

	/**
	 * Check a qCal_Date object to see if it falls within the rules specified
	 * when this object was created (or added later actually).
	 * @param qCal_Date The date that you want to check 
	 * @return boolean True if the date falls within the ruleset
	 * @access public
	 */
	public function checkDate(qCal_Date $date) {
	
		return (boolean) in_array($date->getWeekOfYear(), $this->getValues());
	
	}

}