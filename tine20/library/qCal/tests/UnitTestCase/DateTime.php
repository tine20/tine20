<?php
class UnitTestCase_DateTime extends UnitTestCase {

	/**
	 * Set up test environment
	 */
	public function setUp() {
	
		
	
	}
	/**
	 * Tear down test environment
	 * Set the timezone back to what it was
	 */
	public function tearDown() {
	
		
	
	}
	/**
	 * DateTime is instantiated in the same way qCal_Date and qCal_Time are instantiated
	 * There is also a factory method very similar to those classes (which we'll test next)
	 */
	public function testInstantiateDateTime() {
	
		$year = "2009";
		$month = "4";
		$day = "23";
		$hour = "12";
		$minute = "30";
		$second = "00";
		$timezone = "America/Los_Angeles";
		$rollover = false;
		$datetime = new qCal_DateTime($year, $month, $day, $hour, $minute, $second, $timezone, $rollover); // 4/23/2009 at 12:30:00
		$this->assertEqual($datetime->getDate()->getYear(), $year);
		$this->assertEqual($datetime->getDate()->getMonth(), $month);
		$this->assertEqual($datetime->getDate()->getDay(), $day);
		$this->assertEqual($datetime->getTime()->getHour(), $hour);
		$this->assertEqual($datetime->getTime()->getMinute(), $minute);
		$this->assertEqual($datetime->getTime()->getSecond(), $second);
		$this->assertEqual($datetime->getTime()->getTimezone()->getName(), $timezone);
	
	}
	/**
	 * Test the factory method
	 */
	public function testFactoryMethod() {
	
		$datetime = qCal_DateTime::factory("03/20/1990 10:00:00pm");
		$this->assertEqual($datetime->getDate()->getYear(), 1990);
		$this->assertEqual($datetime->getDate()->getMonth(), 3);
		$this->assertEqual($datetime->getDate()->getDay(), 20);
		$this->assertEqual($datetime->getTime()->getHour(), 22);
		$this->assertEqual($datetime->getTime()->getMinute(), 0);
		$this->assertEqual($datetime->getTime()->getSecond(), 0);
	
	}
	/**
	 * Factory should accept unix timestamps
	FIGURE OUT HOW TIMEZONES SHOULD WORK HERE...

	public function testFactoryAcceptsUnixTimestamps() {
	
		$dt = qCal_DateTime::factory(1262603182, "GMT");
		$this->assertEqual($dt->__toString(), "2010-01-04T11:06:22");
		
		$dt2 = qCal_DateTime::factory("1262603182", "GMT");
		$this->assertEqual($dt2->__toString(), "2010-01-04T11:06:22");
		
		$dt3 = qCal_DateTime::factory("1262603182", "America/Los_Angeles");
		pre(date("m-d-Y H:i:s", 1262603182));
		//$this->assertEqual($dt3->__toString(), "2010-01-04T03:06:22");
	
	}
	 */
	/**
	 * Test that date/time can be converted to timestamp
	 */
	public function testTimestampConversion() {
	
		$datetime = qCal_DateTime::factory("03/20/1993 01:00:00pm", "America/Los_Angeles");
		// this will result in the time specified above plus 8 hours because the tz offset is -8
		$this->assertEqual(gmdate("g:i:sa", $datetime->getUnixTimestamp(true)), "9:00:00pm");
		// this will result in the time specified above
		$this->assertEqual(gmdate("g:i:sa", $datetime->getUnixTimestamp(false)), "1:00:00pm");
		
		$defaultTz = date_default_timezone_get();
		
		date_default_timezone_set("America/Los_Angeles");
		// this will result in the time specified above because date adjusts the timestamp that is returned
		$this->assertEqual(date("g:i", $datetime->getUnixTimestamp()), "1:00");
		
		date_default_timezone_set("GMT");
		$this->assertEqual(date("H:i", $datetime->getUnixTimestamp()), "21:00");
		
		date_default_timezone_set($defaultTz);
	
	}
	/**
	 * Test string output
	 */
	public function testStringOutput() {
	
		$dt = new qCal_DateTime(2000, 10, 1, 5, 0, 0, "America/Los_Angeles");
		$this->assertEqual($dt->__toString(), "2000-10-01T05:00:00-08:00");
	
	}
	/**
	 * Test that format method allows date() function's meta-characters
	 */
	public function testDateTimeFormat() {
	
		$dt = new qCal_DateTime(2000, 10, 1, 5, 0, 0);
		$this->assertEqual($dt->format("m/d/Y H:i:s"), "10/01/2000 05:00:00");
	
	}
	/**
	 * Test conversion to UTC
	 * @todo The entire process for UTC conversion is hacky at best. Fix it up in the next release.
	 */
	public function testUTCConversion() {
	
		$datetime = qCal_DateTime::factory("2/22/1988 5:52am", "America/Denver"); // February 22, 1988 at 5:52am Mountain Standard Time (-7 hours)
		// UTC is GMT time, which means that the result of this should be the time specified plus seven hours
		$this->assertEqual($datetime->getUtc(), "19880222T125200Z");
	
	}
	/**
	 * @todo I need to add a test so that something like "19970101T180000Z", when passed to qCal_DateTime::factory()
	 * knows to assign the GMT timezone to it because the "Z" stands for UTC
	 */

}