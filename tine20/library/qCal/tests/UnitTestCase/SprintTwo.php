<?php
class UnitTestCase_SprintTwo extends UnitTestCase {

	public function setUp() {
	
		// set up the test environment...
	
	}
	
	public function tearDown() {
	
		// tear down the test environment...
	
	}
	
	/**
	 * The following are tests for all of the examples in the documentation
	 */
	
	/**
	 * qCal_Date example tests
	 */
	
	public function testDateInstantiationDocumentationExamples() {
	
		$date1 = new qCal_Date(2010, 1, 10); // results in January 10th, 2010
		$this->assertEqual($date1->format("F jS, Y"), "January 10th, 2010");
		
		$date3 = new qCal_Date(2010, 1, 35, true); // results in February 4th, 2010 because the rollover parameter was set to true
		$this->assertEqual($date3->format("F jS, Y"), "February 4th, 2010");
		
		$this->expectException(new qCal_DateTime_Exception_InvalidDate('Invalid date specified for qCal_Date: "1/35/2010"'));
		$date2 = new qCal_Date(2010, 1, 35); // invalid, and will throw a qCal_DateTime_Exception_InvalidDate exception
	
	}
	
	public function testDateConvertToString() {
	
		$date = new qCal_Date(2010, 1, 10);
		$this->assertEqual($date->__toString(), "01/10/2010"); // will output "01/10/2010"
	
	}
	
	public function testDateConvertToStringUsingFormatMethod() {
	
		$date = new qCal_Date(2010, 1, 10);
		$date->setFormat("Y");
		//echo $date; // outputs "2010"

		$date->setFormat('l, F \the jS, Y'); 
		//echo $date; // outputs "Sunday, January the 10th, 2010"
	
	}
	
	public function testDateGetXthWeekdayOfMonthExamples() {
	
		$date = new qCal_Date(2010, 1, 10); // Sunday, January 10th, 2010
		
		$first_sunday_in_january = $date->getXthWeekdayOfMonth(1); // will return a qCal_Date object for January 3rd, 2010
		$this->assertEqual($first_sunday_in_january->__toString(), "01/03/2010");
		
		$first_tuesday_in_january = $date->getXthWeekdayOfMonth(1, "tuesday"); // will return a qCal_Date object for January 5th, 2010
		$this->assertEqual($first_tuesday_in_january->__toString(), "01/05/2010");
		
		$second_to_last_wednesday_in_january = $date->getXthWeekdayOfMonth(-2, "wednesday"); // will return a qCal_Date object for January 20th, 2010
		$this->assertEqual($second_to_last_wednesday_in_january->__toString(), "01/20/2010");
		
		$second_monday_in_february = $date->getXthWeekdayOfMonth(2, 1, 2); // will return qCal_Date object for February 8th, 2010
		$this->assertEqual($second_monday_in_february->__toString(), "02/08/2010");
		
		$second_monday_in_february = $date->getXthWeekdayOfMonth(2, "monday", "february"); // the previous example could also be done like this
		$this->assertEqual($second_monday_in_february->__toString(), "02/08/2010");
		
		$last_sunday_in_august = $date->getXthWeekdayOfMonth(-1, "sunday", "august"); // will return qCal_Date object for August 29th, 2010
		$this->assertEqual($last_sunday_in_august->__toString(), "08/29/2010");
		
		$last_sunday_in_december_2011 = $date->getXthWeekdayOfMonth(-1, "sunday", "december", 2011); // will return qCal_Date object for December 25th, 2011
		$this->assertEqual($last_sunday_in_december_2011->__toString(), "12/25/2011");
		
		$first_sunday_in_january_2012 = $date->getXthWeekdayOfMonth(1, 0, 1, 2012); // will return qCal_Date object for January 1st, 2012
		$this->assertEqual($first_sunday_in_january_2012->__toString(), "01/01/2012");
	
	}
	
	public function testDateGetXthWeekdayOfYearExamples() {
	
		$date = new qCal_Date(2010, 1, 10); // Sunday, January 10th, 2010
		
		$first_sunday_of_year = $date->getXthWeekdayOfYear(1); // will return a qCal_Date object for January 3rd, 2010
		$this->assertEqual($first_sunday_of_year->__toString(), "01/03/2010");
		
		$first_monday_of_the_year = $date->getXthWeekdayOfYear(1, "monday"); // will return a qCal_Date object for January 4th, 2010
		$this->assertEqual($first_monday_of_the_year->__toString(), "01/04/2010");
		
		$last_monday_of_the_year = $date->getXthWeekdayOfYear(-1, "monday"); // will return a qCal_Date object for December 27th, 2010
		$this->assertEqual($last_monday_of_the_year->__toString(), "12/27/2010");
		
		$thirtieth_sunday_of_the_year = $date->getXthWeekdayOfYear(30, 0); // will return a qCal_Date object for July 25th, 2010
		$this->assertEqual($thirtieth_sunday_of_the_year->__toString(), "07/25/2010");
		
		$thirtieth_sunday_of_the_year = $date->getXthWeekdayOfYear(30, "sunday"); // the previous example could also be done like this
		$this->assertEqual($thirtieth_sunday_of_the_year->__toString(), "07/25/2010");
		
		$tenth_monday_of_2012 = $date->getXthWeekdayOfYear(10, "monday", 2012); // will return a qCal_Date object for March 5th, 2012
		$this->assertEqual($tenth_monday_of_2012->__toString(), "03/05/2012");
	
	}
	
	public function testGetFirstAndLastDayOfMonth() {
	
		$date = new qCal_Date(2010, 1, 10);
		$first = $date->getFirstDayOfMonth(); // will return a qCal_Date object for January 1st, 2010
		$this->assertEqual($first->__toString(), "01/01/2010");
		
		$date = new qCal_Date(2010, 1, 10);
		$last = $date->getLastDayOfMonth(); // will return a qCal_Date object for January 31st, 2010
		$this->assertEqual($last->__toString(), "01/31/2010");
	
	}
	
	/**
	 * Test qCal_Time examples
	 */
	
	public function testTimeConstructor() {
	
		$time = new qCal_Time(10, 30, 0, "GMT"); // will result in an object for 10:30:00am GMT
		$this->assertEqual($time->__toString(), "10:30:00");
		$this->assertEqual($time->getTimezone()->getName(), "GMT");
		
		$time = new qCal_Time(23, 0, 0, new qCal_Timezone("Custom_Timezone", -3600)); // will result in an object for 11:00:00pm with a custom timezone
		$this->assertEqual($time->__toString(), "23:00:00");
		$this->assertEqual($time->getTimezone()->getName(), "Custom_Timezone");
		
		$time = new qCal_Time(5, 70, 0, null, true); // will result in 6:10:00 using the server's default timezone
		$this->assertEqual($time->__toString(), "06:10:00");
		$this->assertEqual($time->getTimezone()->getName(), date_default_timezone_get());
	
	}
	
	public function testTimeFactory() {
	
		$time1 = qCal_Time::factory("4:00"); // will result in 4:00am using the server's timezone
		$this->assertEqual($time1->__toString(), "04:00:00");
		
		$time2 = qCal_Time::factory("tomorrow", "GMT"); // will result in whatever tomorrow's date is in GMT
		$time3 = qCal_Time::factory("now", "America/Los_Angeles"); // will result in the current time in America/Los_Angeles
	
	}
	
	public function testTimeToString() {
	
		$time = new qCal_Time(4, 0, 0);
		$this->assertEqual($time->__toString(), "04:00:00"); // will output "04:00:00"
		
		$time = new qCal_Time(15, 30, 30);
		$time->setFormat("g:ia");
		$this->assertEqual($time->__toString(), "3:30pm"); // outputs "3:30pm"

		$time->setFormat("H");
		$this->assertEqual($time->__toString(), "15"); // outputs "15"
		
		$time = new qCal_Time(6, 30, 0);
		$string = $time->format("H:i");
		$this->assertEqual($time->__toString(), "06:30:00"); // still outputs "06:30:00" because we did not call setFormat()
		$this->assertEqual($string, "06:30"); // outputs "06:30"
	
	}
	
	/**
	 * Test qCal_DateTime examples
	 */
	
	public function testDateTimeFactory() {
	
		$datetime1 = qCal_DateTime::factory("January 21st, 2010 3pm", "GMT");
		$this->assertEqual($datetime1->__toString(), "2010-01-21T15:00:00+00:00");
		
		// this is not very easy to test...
		// $datetime2 = qCal_DateTime::factory("noon tomorrow"); // will result in whatever tomorrow's date is and at noon
		
		// nor is this
		// $datetime3 = qCal_DateTime::factory("now"); // will result in today's date and time
		
		// nor is this
		// $datetime4 = qCal_DateTime::factory(time()); // will result in today's date and time
		
		$datetime5 = qCal_DateTime::factory("October 1st, 2009 9am", "America/Los_Angeles"); // will result in October 1st, 2009 at 9am in America/Los_Angeles time
		$this->assertEqual($datetime5->__toString(), "2009-10-01T09:00:00-08:00");
	
	}
	
	public function testDateTimeConvertToString() {
	
		$datetime = new qCal_DateTime(2010, 1, 12, 4, 30, 0, "America/Los_Angeles");
		$this->assertEqual($datetime->__toString(), "2010-01-12T04:30:00-08:00"); // will output "2010-01-12T04:00:00-08:00"
		
		$datetime = new qCal_DateTime(2010, 12, 10, 15, 30, 0, "GMT");
		$datetime->setFormat('m/d/Y \a\t g:ia');
		$this->assertEqual($datetime->__toString(), "12/10/2010 at 3:30pm"); // outputs "12/10/2010 at 3:30pm"

		$datetime->setFormat("H"); 
		$this->assertEqual($datetime->__toString(), "15"); // outputs "15"
		
		$datetime = new qCal_DateTime(2010, 11, 10, 6, 30, 0, "GMT");
		$string = $datetime->format("H:i");
		$this->assertEqual($datetime->__toString(), "2010-11-10T06:30:00+00:00"); // still outputs "2010-11-10T06:30:00+00:00" because we did not call setFormat()
		$this->assertEqual($string, "06:30"); // outputs "06:30"
	
	}
	
	public function testGetUtc() {
	
		$datetime = new qCal_DateTime(2009, 10, 31, 10, 30, 0, "America/Los_Angeles");
		$this->assertEqual($datetime->getUtc(), "20091031T183000Z");
		$this->assertEqual($datetime->getUtc(true), "2009-10-31T18:30:00Z");
	
	}
	
	/**
	 * Test qCal_Timezone examples
	 */
	
	public function testFactoryExamples() {
	
		$gmt = qCal_Timezone::factory("GMT"); // will result in Greenwich Mean Time
		$this->assertEqual($gmt->getOffsetSeconds(), 0);
		
		$la = qCal_Timezone::factory("America/Los_Angeles"); // will result in timezone for America/Los_Angeles, which is eight hours behind UTC
		$this->assertEqual($la->getOffsetSeconds(), -28800);
		
		$eastern = qCal_Timezone::factory("US/Eastern"); // will result in the timezone for Eastern Standard Time, which is five hours behind UTC
		$this->assertEqual($eastern->getOffsetSeconds(), (3600 * -5));
		
		$defaultTZ = qCal_Timezone::factory(); // will result in whatever timezone your server is currently set to
		$dfOffset = date("Z");
		$this->assertEqual($gmt->getOffsetSeconds(), $dfOffset);
	
	}
	
	public function testTimezoneToStringOutput() {
	
		$tz = new qCal_Timezone("America/Los_Angeles", -28800, "PST");
		$tz->setFormat("T P");
		$this->assertEqual($tz->__toString(), "PST -08:00");
		
		$tz->setFormat("Z"); 
		$this->assertEqual($tz->__toString(), "-28800");
	
	}

}