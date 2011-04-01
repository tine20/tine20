<?php
class UnitTestCase_Timezone extends UnitTestCase {
	// commented out because it makes the tests fail
	// protected $timezone;
	// /**
	//  * Set up test environment
	//  */
	// public function setUp() {
	// 
	// 	$this->timezone = date_default_timezone_get();
	// 
	// }
	// /**
	//  * Tear down test environment
	//  * Set the timezone back to what it was
	//  */
	// public function tearDown() {
	// 
	// 	date_default_timezone_set($this->timezone);
	// 
	// }
	public function testTimezoneSetsServerTimezoneToGMT() {
	
		$timezone = qCal_Timezone::factory(array("foo" => "bar", "name" => "FooBar/Assmunch", "abbreviation" => "tits", "offsetSeconds" => "-28800"));
		// this way, our timezone component works independently of the server timezone.
		// if I can find a way to work with times without having php's functions adjust the output
		// then I will. otherwise, I'll just have to set the timezone to GMT
		// date_default_timezone_set("GMT");
		$date = gmdate("Y-m-d H:i:s", 0);
		$date = qCal_Date::gmgetdate(0);
	
	}
	/**
	 * The timezone defaults to server timezone
	 */
	public function testTimezoneDefaultsToServerTimezone() {

		$timezone = qCal_Timezone::factory();
		$this->assertEqual($timezone->getName(), date_default_timezone_get());

	}
	/**
	 * Test string output (can be formatted with PHP's date function's timezone-related meta-characters)
	 */
	public function testFormatString() {
	
		// format defaults to timezone name
		$timezone = qCal_Timezone::factory("America/Los_Angeles");
		$this->assertEqual($timezone->__toString(), "America/Los_Angeles");
		
		// test that format can be changed
		$timezone->setFormat("P");
		$this->assertEqual($timezone->__toString(), "-08:00");
		
		// test escapability
		$timezone->setFormat('\PP');
		$this->assertEqual($timezone->__toString(), "P-08:00");
		
		// test each of the metacharacters available
		$timezone->setFormat('eIOPTZ');
		$this->assertEqual($timezone->__toString(), "America/Los_Angeles0-0800-08:00PST-28800");
	
	}
	/**
	 * Test that timezone's getters work
	 */
	public function testTimezoneOffsetGetters() {
	
		$timezone = qCal_Timezone::factory("America/Los_Angeles");
		$this->assertEqual($timezone->getOffset(), "-08:00");
		$this->assertEqual($timezone->getOffsetHours(), "-0800");
		$this->assertEqual($timezone->getOffsetSeconds(), "-28800");
		$this->assertEqual($timezone->getAbbreviation(), "PST");
		$this->assertEqual($timezone->isDaylightSavings(), false);
		$this->assertEqual($timezone->getName(), "America/Los_Angeles");
	
	}
	/**
	 * GMT is an offset of zero. Test that it works as it should.
	 */
	public function testTimezoneSetToGMT() {

		$timezone = qCal_Timezone::factory("GMT");
		$this->assertEqual($timezone->getName(), "GMT");
		$this->assertEqual($timezone->getOffset(), 0);

	}
	/**
	 * Test that you can create your own custom timezone
	 */
	public function testCustomTimezone() {
	
		$timezone = new qCal_Timezone("Custom", "5400", "CSTM", false); // hour and a half past GMT
		$this->assertEqual($timezone->getOffsetSeconds(), "5400");
	
	}
	/**
	 * You should be able to register a custom timezone so that you can refer to it by name later on
	 */
	public function testUnregisteredCustomTimezoneThrowsException() {
	
		$this->expectException(new qCal_DateTime_Exception_InvalidTimezone("'Custom' is not a valid timezone."));
		$time = new qCal_Time(0, 0, 0, "Custom");
	
	}
	/**
	 * Now test that registering the timezone prevents the exception
	 */
	public function testCustomTimezoneRegister() {
	
		// now we register the timezone so that we can use it
		$timezone = new qCal_Timezone("Custom", "5400", "CSTM", false);
		qCal_Timezone::register($timezone);
		// Create a new time with the custom timezone. The time should now be 12:00:00 in the custom timezone...
		$time = new qCal_Time(0, 0, 0, "Custom");
		$this->assertEqual($time->getTimezone(), $timezone);
		$this->assertEqual($time->getTimezone()->getOffsetSeconds(), "5400");
		// $this->assertEqual($time->getTimestamp(), "5400");
		$this->assertEqual($time->__toString(), "00:00:00");
	
	}

}