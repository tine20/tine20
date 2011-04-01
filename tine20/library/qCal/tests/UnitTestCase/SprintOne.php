<?php
class UnitTestCase_SprintOne extends UnitTestCase {

	public function setUp() {
	
		// set up the test environment...
	
	}
	
	public function tearDown() {
	
		// tear down the test environment...
	
	}
	
	/**
	 * Date
	 * The date component also needs to be capable of efficiently computing things such as...
	 */
	
	/**
	 * Which weekday of the month or year it is (ie: 2nd Tuesday in January, last Sunday of
	 * the month, fourth from last Monday of the year, etc.)
	 */
	public function testDateComponentCanDetermineWhichWeekDayOfMonthOrYearItIs() {
	
		$date = new qCal_Date(2009, 2, 22);
		$this->assertEqual($date->getWeekDayName(), "Sunday");
		$this->assertEqual($date->getXthWeekdayOfMonth(-1), $date); // last sunday of the month
		$this->assertEqual($date->getXthWeekdayOfMonth(4), $date); // fourth sunday of the month
		$this->assertNotEqual($date->getXthWeekdayOfMonth(2), $date); // NOT the second Sunday of the month
		$this->assertEqual($date->getXthWeekdayOfYear(8), $date); // eighth sunday of the year
		$this->assertEqual($date->getXthWeekdayOfYear(-45), $date);
	
	}
	
	/**
	 * How many days from the beginning or end of the month or year it is
	 * (ie: February 15th is the 46th day of the year and 13 days from the end of the month)
	 */
	public function testHowManyDaysFromTheBeginningOrEndOfTheMonthOrYearItIs() {
	
		/**
		 * Year
		 */
		// days 'til end of year
		$date = new qCal_Date(2010, 1, 15, "GMT"); // mom's birthday!
		$this->assertEqual($date->getYearDay(), 14); // year day starts at zero
		$this->assertEqual($date->getYearDay(true), 15); // pass true to the method and it will start from one
		$this->assertEqual($date->getNumDaysUntilEndOfYear(), 350);
		$date2 = new qCal_Date(2010, 12, 25); // jesus's birthday (not really, but w/e)!
		$this->assertEqual($date2->getNumDaysUntilEndOfYear(), 6);
		
		/**
		 * Month
		 */
		$date3 = new qCal_Date(2010, 3, 20); // mom and dady's anniversary!
		$this->assertEqual($date3->getDay(), 20); // this one is pretty obvious...
		$this->assertEqual($date3->getNumDaysUntilEndOfMonth(), 11);
	
	}
	
	/**
	 * How many weeks from the beginning or end of the month or year it is
	 * (ie: January 3rd is in the first week of the year and 51 weeks from the end of the year)
	 * @todo This needs to be capable of setting the "week start day", which will have an impact on this method...
	 */
	public function testHowManyWeeksFromTheBeginningOrEndOfTheMonthOrYearItIs() {
	
		$date = new qCal_Date(2010, 1, 15);
		// $date->setWeekStart("Sunday");
		$this->assertEqual($date->getWeekOfYear(), 2);
		$date2 = new qCal_Date(2010, 4, 23);
		$this->assertEqual($date2->getWeekOfYear(), 16);
		
		// how many weeks until the end of the year?
		$date3 = new qCal_Date(2010, 12, 1);
		$this->assertEqual($date3->getWeeksUntilEndOfYear(), 4);
	
	}
	
	/**
	 * How many months are there left in the year?
	 */
	public function testHowManyMonthsLeftInTheYear() {
	
		$date = new qCal_Date(2010, 10, 23);
		$this->assertEqual($date->getNumMonthsUntilEndOfYear(), 2);
	
	}
	
	/**
	 * Timezone
	 */
	
	/**
	 * Certain timezones have daylight savings rules. The timezone component needs to be aware
	 * of those rules and adjust the time accordingly.
	 */
	public function testTimezoneIsAwareOfDaylightSavings() {
	
		// coming soon!
	
	}

}