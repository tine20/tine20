<?php
/**
 * Date/Time Recurrence
 * This class represents a single recurrence in a qCal_DateTime_Recur object.
 * It's basically a glorified qCal_DateTime object.
 * 
 * @package qCal
 * @subpackage qCal_DateTime_Recur
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */
class qCal_DateTime_Recur_Recurrence {

	/**
	 * @var qCal_DateTime The date/time that this recurrence represents
	 */
	protected $dt;
	
	/**
	 * Class Constructor
	 * Initializes the object with a qCal_DateTime object.
	 * @param qCal_DateTime $dt The date/time that this object represents
	 * @access public
	 */
	public function __construct(qCal_DateTime $dt) {
	
		$this->dt = $dt;
	
	}
	
	/**
	 * Retrieve the date/time object that this recurrence represents
	 * @return qCal_DateTime The internal date/time object
	 * @access public
	 */
	public function getDateTime() {
	
		return $this->dt;
	
	}

}