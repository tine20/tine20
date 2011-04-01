<?php
/**
 * Time period object - rather than a point in time, this object represents a
 * period of time. It consists of a start and end point in time.
 * 
 * @package qCal
 * @subpackage qCal_DateTime
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */
class qCal_DateTime_Period {

	/**
	 * @var qCal_DateTime The start date/time of this time period
	 */
	protected $start;
	
	/**
	 * @var qCal_DateTime The end date/time of this time period
	 */
	protected $end;
	
	/**
	 * Constructor
	 * @param mixed $start Either a qCal_DateTime object or a string that can
	 * be converted to one (see qCal_DateTime::factory()). Represents the start date/time.
	 * @param mixed $end If this is either a qCal_DateTime object or a date
	 * string, then that will be the time period's end date. If a qCal_DateTime_Duration
	 * object is provided, then the time period's end date/time will be calculated by
	 * adding the duration to the start date.
	 * @throws qCal_DateTime_Exception_InvalidPeriod If the end date provided comes before
	 * the start date or the duration provided is negative.
	 * @access public
	 */
	public function __construct($start, $end) {
	
		if (!($start instanceof qCal_DateTime)) {
			$start = qCal_DateTime::factory($start);
		}
		if (!($end instanceof qCal_DateTime)) {
			if ($end instanceof qCal_DateTime_Duration) {
				$endTS = $start->getUnixTimestamp() + $end->getSeconds();
				$end = qCal_DateTime::factory($endTS);
			} else {
				$end = qCal_DateTime::factory($end);
			}
		}
		$this->start = $start;
		$this->end = $end;
		if ($this->getSeconds() < 0) {
			throw new qCal_DateTime_Exception_InvalidPeriod("The start date must come before the end date.");
		}
	
	}
	
	/**
	 * For the same reason that PHP uses seconds to calculate dates/times, this
	 * class uses seconds to determine the difference between start and end.
	 * @return integer The difference between start and end in seconds
	 * @access public
	 */
	public function getSeconds() {
	
		return $this->end->getUnixTimestamp() - $this->start->getUnixTimestamp();
	
	}
	
	/**
	 * Convert this object to a qCal_DateTime_Duration object.
	 * @return qCal_DateTime_Duration that represents the difference between
	 * the start end end date/time.
	 * @access public
	 */
	public function toDuration() {
	
		return new qCal_DateTime_Duration(array("seconds" => $this->getSeconds()));
	
	}
	
	/**
	 * Returns start date/time
	 * @return qCal_DateTime representing the start date/time
	 * @access public
	 */
	public function getStart() {
	
		return $this->start;
	
	}
	
	/**
	 * Returns end date/time
	 * @return qCal_DateTime representing the end date/time
	 * @access public
	 */
	public function getEnd() {
	
		return $this->end;
	
	}

}